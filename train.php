<?php
//addnews ready
// mail ready
// translator ready
require_once("common.php");
require_once("lib/systemmail.php");
require_once("lib/increment_specialty.php");
require_once("lib/fightnav.php");
require_once("lib/http.php");
require_once("lib/taunt.php");
require_once("lib/substitute.php");
require_once("lib/villagenav.php");
require_once("lib/experience.php");

Translator::tlschema("train");

PageParts::page_header("Bluspring's Warrior Training");

$battle = false;
$victory = false;
$defeat = false;

OutputClass::output("`b`cBluspring's Warrior Training`c`b");

$mid = Http::httpget("master");
if ($mid) {
	$sql = "SELECT * FROM " . db_prefix("masters") . " WHERE creatureid=$mid";
} else {
	$sql = "SELECT max(creaturelevel) as level FROM " . db_prefix("masters") . " WHERE creaturelevel <= " . $session['user']['level'];
	$res = db_query($sql);
	$row = db_fetch_assoc($res);
	$l = $row['level'];

	$sql = "SELECT * FROM " . db_prefix("masters") . " WHERE creaturelevel=$l ORDER BY RAND(".Erand::e_rand().") LIMIT 1";
}

$result = db_query($sql);
if (db_num_rows($result) > 0 && $session['user']['level'] <= 14){
	$master = db_fetch_assoc($result);
	$mid = $master['creatureid'];
	$master['creaturename'] = stripslashes($master['creaturename']);
	$master['creaturewin'] = stripslashes($master['creaturewin']);
	$master['creaturelose'] = stripslashes($master['creaturelose']);
	$master['creatureweapon'] = stripslashes($master['creatureweapon']);
	if ($master['creaturename'] == "Gadriel the Elven Ranger" &&
			$session['user']['race'] == "Elf") {
		$master['creaturewin'] = "You call yourself an Elf?? Maybe Half-Elf! Come back when you've been better trained.";
		$master['creaturelose'] = "It is only fitting that another Elf should best me.  You make good progress.";
	}
	$level = $session['user']['level'];
	$dks = $session['user']['dragonkills'];
	$exprequired=Experience::exp_for_next_level($level, $dks);

	$op = Http::httpget('op');
	if ($op==""){
		GameDateTime::checkday();
		OutputClass::output("The sound of conflict surrounds you.  The clang of weapons in grisly battle inspires your warrior heart. ");
		OutputClass::output("`n`n`^%s stands ready to evaluate you.`0",
				$master['creaturename']);
		OutputClass::addnav("Question Master","train.php?op=question&master=$mid");
		OutputClass::addnav("M?Challenge Master","train.php?op=challenge&master=$mid");
		if ($session['user']['superuser'] & SU_DEVELOPER) {
			OutputClass::addnav("Superuser Gain level","train.php?op=challenge&victory=1&master=$mid");
		}
		VillageNavClass::villagenav();
	}else if($op=="challenge"){
		if (Http::httpget('victory')) {
			$victory=true;
			$defeat=false;
			if ($session['user']['experience'] < $exprequired)
				$session['user']['experience'] = $exprequired;
			$session['user']['seenmaster'] = 0;
		}
		if ($session['user']['seenmaster']){
			OutputClass::output("You think that, perhaps, you've seen enough of your master for today, the lessons you learned earlier prevent you from so willingly subjecting yourself to that sort of humiliation again.");
			VillageNavClass::villagenav();
		}else{
			/* OK, let's fix the multimaster thing */
			$session['user']['seenmaster'] = 1;
			debuglog("Challenged master, setting seenmaster to 1");

			if ($session['user']['experience']>=$exprequired){
				$dk = 0;
				Buffs::restore_buff_fields();
				while(list($key, $val)=each($session['user']['dragonpoints'])) {
					if ($val=="at" || $val=="de") $dk++;
				}
				$dk += (int)(($session['user']['maxhitpoints'] -
					($session['user']['level']*10))/5);

				$dk = round($dk * .33, 0);

				$atkflux = Erand::e_rand(0, $dk);
				$atkflux = min($atkflux, round($dk*.25));
				$defflux = Erand::e_rand(0, ($dk-$atkflux));
				$defflux = min($defflux, round($dk*.25));

				$hpflux = ($dk - ($atkflux+$defflux)) * 5;
				OutputClass::debug("OutputClass::debug: $dk modification points total.`n");
				OutputClass::debug("OutputClass::debug: +$atkflux allocated to attack.`n");
				OutputClass::debug("OutputClass::debug: +$defflux allocated to defense.`n");
				OutputClass::debug("OutputClass::debug: +".($hpflux/5)."*5 to hitpoints`n");
				Buffs::calculate_buff_fields();

				$master['creatureattack']+=$atkflux;
				$master['creaturedefense']+=$defflux;
				$master['creaturehealth']+=$hpflux;
				$attackstack['enemies'][0] = $master;
				$attackstack['options']['type'] = 'train';
				$session['user']['badguy']=ArrayUtil::createstring($attackstack);

				$battle=true;
				if ($victory) {
					$badguy = unserialize($session['user']['badguy']);
					$badguy = $badguy['enemies'][0];
					OutputClass::output("With a flurry of blows you dispatch your master.`n");
				}
			}else{
				OutputClass::output("You ready your %s and %s and approach `^%s`0.`n`n",$session['user']['weapon'],$session['user']['armor'],$master['creaturename']);
				OutputClass::output("A small crowd of onlookers has gathered, and you briefly notice the smiles on their faces, but you feel confident. ");
				OutputClass::output("You bow before `^%s`0, and execute a perfect spin-attack, only to realize that you are holding NOTHING!", $master['creaturename']);
				OutputClass::output("`^%s`0 stands before you holding your weapon.",$master['creaturename']);
				OutputClass::output("Meekly you retrieve your %s, and slink out of the training grounds to the sound of boisterous guffaws.",$session['user']['weapon']);
				VillageNavClass::villagenav();
			}
		}
	}else if($op=="question"){
		GameDateTime::checkday();
		OutputClass::output("You approach `^%s`0 timidly and inquire as to your standing in the class.",$master['creaturename']);
		if($session['user']['experience']>=$exprequired){
			OutputClass::output("`n`n`^%s`0 says, \"Gee, your muscles are getting bigger than mine...\"",$master['creaturename']);
		}else{
			OutputClass::output("`n`n`^%s`0 states that you will need `%%s`0 more experience before you are ready to challenge him in battle.",$master['creaturename'],($exprequired-$session['user']['experience']));
		}
		OutputClass::addnav("Question Master","train.php?op=question&master=$mid");
		OutputClass::addnav("M?Challenge Master","train.php?op=challenge&master=$mid");
		if ($session['user']['superuser'] & SU_DEVELOPER) {
			OutputClass::addnav("Superuser Gain level","train.php?op=challenge&victory=1&master=$mid");
		}
		VillageNavClass::villagenav();
	}else if($op=="autochallenge"){
		OutputClass::addnav("Fight Your Master","train.php?op=challenge&master=$mid");
		OutputClass::output("`^%s`0 has heard of your prowess as a warrior, and heard of rumors that you think you are so much more powerful than he that you don't even need to fight him to prove anything. ",$master['creaturename']);
		OutputClass::output("His ego is understandably bruised, and so he has come to find you.");
		OutputClass::output("`^%s`0 demands an immediate battle from you, and your own pride prevents you from refusing the demand.",$master['creaturename']);
		if ($session['user']['hitpoints']<$session['user']['maxhitpoints']){
			OutputClass::output("`n`nBeing a fair person, your master gives you a healing potion before the fight begins.");
			$session['user']['hitpoints']=$session['user']['maxhitpoints'];
		}
		Modules::modulehook("master-autochallenge");
		if (Settings::getsetting('displaymasternews',1)) AddNewsClass::addnews("`3%s`3 was hunted down by their master, `^%s`3, for being truant.",$session['user']['name'],$master['creaturename']);
	}
	if ($op=="fight"){
		$battle=true;
	}
	if ($op=="run"){
		OutputClass::output("`\$Your pride prevents you from running from this conflict!`0");
		$op="fight";
		$battle=true;
	}

	if($battle){
		require_once("lib/battle-skills.php");
		require_once("lib/extended-battle.php");
		suspend_buffs('allowintrain', "`&Your pride prevents you from using extra abilities during the fight!`0`n");
		suspend_companions("allowintrain");
		if (!$victory) {
			require_once("battle.php");
		}
		if ($victory){
			$badguy['creaturelose']=substitute_array($badguy['creaturelose']);
			OutputClass::output_notl("`b`&");
 	 	 	OutputClass::output($badguy['creaturelose']);
 	 	 	OutputClass::output_notl("`0`b`n");
 	 	 	OutputClass::output("`b`\$You have defeated %s!`0`b`n",$badguy['creaturename']);

			$session['user']['level']++;
			$session['user']['maxhitpoints']+=10;
			$session['user']['soulpoints']+=5;
			$session['user']['attack']++;
			$session['user']['defense']++;
			// Fix the multimaster bug
			if (Settings::getsetting("multimaster", 1) == 1) {
				$session['user']['seenmaster']=0;
				debuglog("Defeated master, setting seenmaster to 0");
			}
			OutputClass::output("`#You advance to level `^%s`#!`n",$session['user']['level']);
			OutputClass::output("Your maximum hitpoints are now `^%s`#!`n",$session['user']['maxhitpoints']);
			OutputClass::output("You gain an attack point!`n");
			OutputClass::output("You gain a defense point!`n");
			if ($session['user']['level']<15){
				OutputClass::output("You have a new master.`n");
			}else{
				OutputClass::output("None in the land are mightier than you!`n");
			}
			if ($session['user']['referer']>0 && ($session['user']['level']>=Settings::getsetting("referminlevel",4) || $session['user']['dragonkills'] > 0) && $session['user']['refererawarded']<1){
				$sql = "UPDATE " . db_prefix("accounts") . " SET donation=donation+".Settings::getsetting("refereraward",25)." WHERE acctid={$session['user']['referer']}";
				db_query($sql);
				$session['user']['refererawarded']=1;
				$subj=array("`%One of your referrals advanced!`0");
				$body=array("`&%s`# has advanced to level `^%s`#, and so you have earned `^%s`# points!", $session['user']['name'], $session['user']['level'], Settings::getsetting("refereraward", 25));
				systemmail($session['user']['referer'],$subj,$body);
			}
			increment_specialty("`^");

			// Level-Up companions
			// We only get one level per pageload. So we just add the per-level-values.
			// No need to multiply and/or substract anything.
			if (Settings::getsetting("companionslevelup", 1) == true) {
				$newcompanions = $companions;
				foreach ($companions as $name => $companion) {
					$companion['attack'] = $companion['attack'] + $companion['attackperlevel'];
					$companion['defense'] = $companion['defense'] + $companion['defenseperlevel'];
					$companion['maxhitpoints'] = $companion['maxhitpoints'] + $companion['maxhitpointsperlevel'];
					$companion['hitpoints'] = $companion['maxhitpoints'];
					$newcompanions[$name] = $companion;
				}
				$companions = $newcompanions;
			}

			DataCache::invalidatedatacache("list.php-warsonline");

			OutputClass::addnav("Question Master","train.php?op=question");
			OutputClass::addnav("M?Challenge Master","train.php?op=challenge");
			if ($session['user']['superuser'] & SU_DEVELOPER) {
				OutputClass::addnav("Superuser Gain level","train.php?op=challenge&victory=1");
			}
			VillageNavClass::villagenav();
			if ($session['user']['age'] == 1) {
 	 	 	 	if (Settings::getsetting('displaymasternews',1)) AddNewsClass::addnews("`%%s`3 has defeated ".($session['user']['sex']?"her":"his")." master, `%%s`3 to advance to level `^%s`3 after `^1`3 day!!", $session['user']['name'],$badguy['creaturename'],$session['user']['level']);
 	 	 	} else {
 	 	 	 	if (Settings::getsetting('displaymasternews',1)) AddNewsClass::addnews("`%%s`3 has defeated ".($session['user']['sex']?"her":"his")." master, `%%s`3 to advance to level `^%s`3 after `^%s`3 days!!", $session['user']['name'],$badguy['creaturename'],$session['user']['level'],$session['user']['age']);
 	 	 	}
			if ($session['user']['hitpoints'] < $session['user']['maxhitpoints'])
				$session['user']['hitpoints'] = $session['user']['maxhitpoints'];
			Modules::modulehook("training-victory", $badguy);
		}elseif($defeat){
			$taunt = Taunt::select_taunt_array();

			if (Settings::getsetting('displaymasternews',1)) AddNewsClass::addnews("`%%s`5 has challenged their master, %s and lost!`n%s",$session['user']['name'],$badguy['creaturename'],$taunt);
			$session['user']['hitpoints']=$session['user']['maxhitpoints'];
			OutputClass::output("`&`bYou have been defeated by `%%s`&!`b`n",$badguy['creaturename']);
			OutputClass::output("`%%s`\$ halts just before delivering the final blow, and instead extends a hand to help you to your feet, and hands you a complementary healing potion.`n",$badguy['creaturename']);
			$badguy['creaturewin']=substitute_array($badguy['creaturewin']);
			OutputClass::output_notl("`^`b");
			OutputClass::output($badguy['creaturewin']);
			OutputClass::output_notl("`b`0`n");
			OutputClass::addnav("Question Master","train.php?op=question&master=$mid");
			OutputClass::addnav("M?Challenge Master","train.php?op=challenge&master=$mid");
			if ($session['user']['superuser'] & SU_DEVELOPER) {
				OutputClass::addnav("Superuser Gain level","train.php?op=challenge&victory=1&master=$mid");
			}
			VillageNavClass::villagenav();
			Modules::modulehook("training-defeat", $badguy);
		}else{
		  FightNavClass::fightnav(false,false, "train.php?master=$mid");
		}
		if ($victory || $defeat) {
			BattleSkills::unsuspend_buffs('allowintrain', "`&You now feel free to make use of your buffs again!`0`n");
			unsuspend_companions("allowintrain");
		}
	}
}else{
	GameDateTime::checkday();
	OutputClass::output("You stroll into the battle grounds.");
	OutputClass::output("Younger warriors huddle together and point as you pass by.");
	OutputClass::output("You know this place well.");
	OutputClass::output("Bluspring hails you, and you grasp her hand firmly.");
	OutputClass::output("There is nothing left for you here but memories.");
	OutputClass::output("You remain a moment longer, and look at the warriors in training before you turn to return to the village.");
	VillageNavClass::villagenav();
}
PageParts::page_footer();
?>