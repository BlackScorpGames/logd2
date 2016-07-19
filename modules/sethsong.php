<?php

//Seth's songs as a module
//Converted by Zach Lawson with some minor modification

/*
Version History:

Version 1.0 - First public release
Version 1.1 - Fixed a small bug that caused 2 "Return to the Inn" navs to
              show up

*/

require_once("lib/villagenav.php");
require_once("lib/http.php");

function sethsong_getmoduleinfo(){
	$info = array(
		"name"=>"Seth the Bard's Songs",
		"version"=>"1.1",
		"author"=>"Eric Stevens",
		"category"=>"Inn",
		"download"=>"core_module",
		"settings"=>array(
			"Seth the Bard's Songs,title",
			"bhploss"=>"Percent of hitpoints that can be lost when Seth burps,range,2,100,2|10",
			"shploss"=>"Percent of hitpoints that can be lost when a string breaks,range,2,100,2|20",
			"hpgain"=>"Percent of max hitpoints that can be gained,range,2,100,2|20",
			//I realize adding 100% of max hitpoints or killing them when they go to Seth is a little outrageous, but might as well give admins the option
			"maxgems"=>"Most gems that can be found,int|1",
			"mingems"=>"Fewest gems that can be found,int|1",
			"Set these equal to each other for a fixed amount,note",
			"mingold"=>"Minimum amount gold you can find,int|10",
			"maxgold"=>"Maximum amount gold you can find,int|50",
			"goldloss"=>"Amount of gold that can be lost,int|5",
			"Warning: If a player's gold is less than this amount they loose nothin!,note",
			"visits"=>"How many times per day can a player listen to Seth,int|1",
		),
		"prefs"=>array(
			"Seth the Bard's Songs,title",
			"been"=>"How many times have they listened Seth today,int|0",
		),
	);
	return $info;
}

function sethsong_install(){
	// Convert the seenbard field.
	$sql = "DESCRIBE " . db_prefix("accounts");
	$result = db_query($sql);
	while ($row = db_fetch_assoc($result)){
		if ($row['Field']=="seenbard"){
			$sql = "SELECT seenbard,acctid FROM " . db_prefix("accounts") . " WHERE seenbard>0";
			$result1 = db_query($sql);
			OutputClass::debug("Migrating seenbard.`n");
			while ($row1 = db_fetch_assoc($result1)){
				$sql = "INSERT INTO " . db_prefix("module_userprefs") . " (modulename,setting,userid,value) VALUES ('seth','been',{$row1['acctid']},{$row1['seenbard']})";
				db_query($sql);
			}//end while
			OutputClass::debug("Dropping seenbard column from the user table.`n");
			$sql = "ALTER TABLE " . db_prefix("accounts") . " DROP seenbard";
			db_query($sql);
			//drop it from the user's session too.
			unset($session['user']['seenbard']);
		}//end if
	}//end while

	module_addhook("inn");
	module_addhook("newday");
	return true;
}

function sethsong_uninstall(){
	return true;
}

function sethsong_dohook($hookname,$args){
	switch($hookname){
	case "inn":
		$op = Http::httpget("op");
		if ($op == "" || $op == "strolldown" || $op == "fleedragon") {
			OutputClass::addnav("Things to do");
			OutputClass::addnav(array("L?Listen to %s`0 the Bard", Settings::getsetting("bard", "`^Seth")),"runmodule.php?module=sethsong");
		}
		break;
	case "newday":
		set_module_pref("been",0);
		break;
	}
	return $args;
}

function sethsong_run(){
	$op=Http::httpget('op');
	$visits=get_module_setting("visits");
	$been=get_module_pref("been");
	$iname = Settings::getsetting("innname", LOCATION_INN);
	Translator::tlschema("inn");
	PageParts::page_header($iname);

	OutputClass::rawoutput("<span style='color: #9900FF'>");
	OutputClass::output_notl("`c`b");
	OutputClass::output($iname);
	OutputClass::output_notl("`b`c");
	Translator::tlschema();

	// Short circuit out if we've heard enough
	if ($been >= $visits) {
		OutputClass::output("%s`0 clears his throat and drinks some water.", Settings::getsetting("bard", "`^Seth"));
		OutputClass::output("\"I'm sorry, my throat is just too dry.\"");
	} else {
		sethsong_sing();
	}

	OutputClass::addnav("Where to?");
	OutputClass::addnav("I?Return to the Inn","inn.php");
	VillageNavClass::villagenav();
	OutputClass::rawoutput("</span>");
	PageParts::page_footer();
}

function sethsong_sing()
{
	global $session;
	$mostgold=get_module_setting("maxgold");
	$leastgold=get_module_setting("mingold");
	$lgain=get_module_setting("hpgain");
	$bloss=get_module_setting("bhploss");
	$sloss=get_module_setting("shploss");
	$gold=get_module_setting("goldloss");
	$mostgems=get_module_setting("maxgems");
	$leastgems=get_module_setting("mingems");
	$visits=get_module_setting("visits");
	$been=get_module_pref("been");

	$been++;
	set_module_pref("been",$been);
	$rnd = Erand::e_rand(0,18);
	OutputClass::output("%s`0 clears his throat and begins:`n`n`^", Settings::getsetting("bard", "`^Seth"));
	switch ($rnd){
	case 0:
		OutputClass::output("`@Green Dragon`^ is green,`n`@Green Dragon`^ is fierce.`n");
		OutputClass::output("I fancy for a`n`@Green Dragon`^ to pierce.`n`n");
		OutputClass::output("`0You gain TWO forest fights for today!");
		$session['user']['turns'] += 2;
		break;
	case 1:
		// Since masters are now editable, pick a random one.
		$sql = "SELECT creaturename FROM " . db_prefix("masters") . " ORDER BY RAND(".Erand::e_rand().") LIMIT 1";
		$res = db_query($sql);
		if (db_num_rows($res)) {
			$row = db_fetch_assoc($res);
			$name = $row['creaturename'];
		} else {
			$name = "MightyE";
		}
		OutputClass::output("%s, I scoff at thee and tickleth your toes.`n", $name);
		OutputClass::output("For they smell most foul and seethe a stench far greater than you know!`n`n");
		OutputClass::output("`0You feel jovial, and gain an extra forest fight.");
		$session['user']['turns']++;
		break;
	case 2:
		OutputClass::output("Membrane Man, Membrane Man.`n");
		OutputClass::output("Membrane man hates %s`^ man.`n", $session['user']['name']);
		OutputClass::output("They have a fight, Membrane wins.`n");
		OutputClass::output("Membrane Man.`n`n");
		OutputClass::output("`0You're not quite sure what to make of this.");
		OutputClass::output("You merely back away, and think you'll visit %s`0 when he's feeling better.", Settings::getsetting("bard", "`^Seth"));
		OutputClass::output("Having rested a while though, you think you could face another forest creature.");
		$session['user']['turns']++;
		break;
	case 3:
		OutputClass::output("Gather 'round and I'll tell you a tale`nmost terrible and dark`nof %s`^ and his unclean beer`nand how he hates this bard!`n`n", Settings::getsetting('barkeep', '`tCedrik'));
		OutputClass::output("`0You realize he's right, %s`0's beer really is nasty.", Settings::getsetting('barkeep', '`tCedrik'));
		OutputClass::output("That's why most patrons prefer his ale.");
		OutputClass::output("Though you don't really gain anything from the tale from %s`0, you do happen to notice a few gold on the ground!", Settings::getsetting("bard", "`^Seth"));
		$gain = Erand::e_rand($leastgold,$mostgold);
		$session['user']['gold']+=$gain;
		debuglog("found $gain gold near Seth");
		break;
	case 4:
		OutputClass::output("So a pirate goes into a bar with a steering wheel in his pants.`n");
		OutputClass::output("The bartender says, \"You know you have a steering wheel in your pants?\"`n");
		OutputClass::output("The pirate replies, \"Yaaarr, 'tis drivin' me nuts!\"`n`n");
		OutputClass::output("`0With a good hearty chuckle in your soul, you advance on the world, ready for anything!");
		$session['user']['hitpoints']=round(max($session['user']['maxhitpoints'],$session['user']['hitpoints'])*(($lgain/100)+1),0);
		break;
	case 5:
		OutputClass::output("Listen close and hear me well: every second we draw even closer to death.  *wink*`n`n");
		OutputClass::output("`0Depressed, you head for home... and lose a forest fight!");
		$session['user']['turns']--;
		if ($session['user']['turns']<0)
			$session['user']['turns']=0;
		break;
	case 6:
		OutputClass::output("I love MightyE, MightyE weaponry, I love MightyE, MightyE weaponry, I love MightyE, MightyE weaponry, nothing kills as good as MightyE... WEAPONRY!`n`n");
		OutputClass::output("`0You think %s`0 is quite correct.`n", Settings::getsetting("bard", "`^Seth"));
		OutputClass::output("You want to go out and kill something.");
		OutputClass::output("You leave and think about bees and fish for some reason.");
		$session['user']['turns']++;
		break;
	case 7:
		OutputClass::output("%s`0 seems to sit up and prepare himself for something impressive.",Settings::getsetting("bard","`^Seth"));
		OutputClass::output("He then burps loudly in your face.");
		OutputClass::output("\"`^Was that entertaining enough?`0\"`n`n");
		OutputClass::output("`0The smell is overwhelming.");
		OutputClass::output("You feel a little ill and lose some hitpoints.");
		$session['user']['hitpoints']-=round($session['user']['maxhitpoints'] * ($bloss/100),0);
		if ($session['user']['hitpoints']<=0)
			$session['user']['hitpoints']=1;
		break;
	case 8:
		OutputClass::output("`0\"`^What is the sound of one hand clapping?`0\" asks %s`0.", Settings::getsetting("bard", "`^Seth"));
		if ($session['user']['gold'] >=$gold ) {
			OutputClass::output("While you ponder this conundrum, %s`0 \"liberates\" a small entertainment fee from your purse.`n`n", Settings::getsetting("bard", "`^Seth"));
			OutputClass::output("You lose %s gold!",$gold);
			$session['user']['gold']-=$gold;
			debuglog("lost $gold gold to Seth");
		} else {
			OutputClass::output("While you ponder this conundrum, %s`0 attempts to \"liberate\" a small entertainment fee from your purse, but doesn't find enough to bother with.", Settings::getsetting("bard", "`^Seth"));
		}
		break;
	case 9:
		$gems=Erand::e_rand($leastgems,$mostgems);
		OutputClass::output("What do you call a fish with no eyes?`n`n");
		OutputClass::output("A fsshh.`n`n");
		OutputClass::output("`0You groan as %s`0 laughs heartily.", Settings::getsetting("bard", "`^Seth"));
		if($gems==0){
			OutputClass::output("Shaking your head, you turn to go back to the inn.");
		}
		if($gems==1){
			OutputClass::output("Shaking your head, you notice a gem in the dust.");
			$session['user']['gems']++;
		}else{
			OutputClass::output("Shaking your head, you notice %s gems in the dust.",$gems);
			$session['user']['gems']+=$gems;
		}
		debuglog("got $gems gem\\(s\\) from Seth");
		break;
	case 10:
		OutputClass::output("%s`0 plays a soft but haunting melody.`n`n", Settings::getsetting("bard", "`^Seth"));
		OutputClass::output("You feel relaxed, and your wounds seem to fade away.");
		if ($session['user']['hitpoints'] < $session['user']['maxhitpoints'])
			$session['user']['hitpoints'] = $session['user']['maxhitpoints'];
		break;
	case 11:
		OutputClass::output("%s`0 plays a melancholy dirge for you.`n`n", Settings::getsetting("bard", "`^Seth"));
		OutputClass::output("You feel lower in spirits, you may not be able to face as many villains today.");
		$session['user']['turns']--;
		if ($session['user']['turns']<0)
			$session['user']['turns']=0;
		break;
	case 12:
		OutputClass::output("The ants go marching one by one, hoorah, hoorah.`n");
		OutputClass::output("The ants go marching one by one, hoorah, hoorah!`n");
		OutputClass::output("The ants go marching one by one and the littlest one stops to suck his thumb, and they all go marching down, to the ground, to get out of the rain...`n");
		OutputClass::output("bum bum bum`n");
		OutputClass::output("The ants go marching two by two, hoorah, hoorah!....`n`n");
		OutputClass::output("%s`0 continues to sing, but not wishing to learn how high he can count, you quietly leave.`n`n", Settings::getsetting("bard", "`^Seth"));
		OutputClass::output("Having rested a while, you feel refreshed.");
		if($session['user']['hitpoints'] < $session['user']['maxhitpoints'])
			$session['user']['hitpoints'] = $session['user']['maxhitpoints'];
		break;
	case 13:
		OutputClass::output("There once was a lady from Venus, her body was shaped like a ...`n`n");
		if ($session['user']['sex']==SEX_FEMALE){
			OutputClass::output("%s`0 is cut short by a curt slap across his face!", Settings::getsetting("bard", "`^Seth"));
			OutputClass::output("Feeling rowdy, you gain a forest fight.");
		}else{
			OutputClass::output("%s`0 is cut short as you burst out in laughter, not even having to hear the end of the rhyme.", Settings::getsetting("bard", "`^Seth"));
			OutputClass::output("Feeling inspired, you gain a forest fight.");
		}
		$session['user']['turns']++;
		break;
	case 14:
		OutputClass::output("%s`0 plays a rousing call-to-battle that wakes the warrior spirit inside of you.`n`n", Settings::getsetting("bard", "`^Seth"));
		OutputClass::output("`0You gain a forest fight!");
		$session['user']['turns']++;
		break;
	case 15:
		OutputClass::output("%s`0 seems preoccupied with your... eyes.`n`n", Settings::getsetting("bard", "`^Seth"));
		if ($session['user']['sex']==SEX_FEMALE){
			OutputClass::output("`0You receive one charm point!");
			$session['user']['charm']++;
		}else{
			OutputClass::output("`0Furious, you stomp out of the bar!");
			OutputClass::output("You gain a forest fight in your fury.");
			$session['user']['turns']++;
		}
		break;
	case 16:
		OutputClass::output("%s`0 begins to play, but a lute string snaps, striking you square in the eye.`n`n", Settings::getsetting("bard", "`^Seth"));
		OutputClass::output("`0\"`^Whoops, careful, you'll shoot your eye out kid!`0\"`n`n");
		OutputClass::output("You lose some hitpoints!");
		$session['user']['hitpoints']-=round($session['user']['maxhitpoints']*($sloss/100),0);
		if ($session['user']['hitpoints']<1)
			$session['user']['hitpoints']=1;
		break;
	case 17:
		OutputClass::output("%s`0 begins to play, but a rowdy patron stumbles past, spilling beer on you.", Settings::getsetting("bard", "`^Seth"));
		OutputClass::output("You miss the performance as you wipe the swill from your %s.", $session['user']['armor']);
		break;
	case 18:
		OutputClass::output("%s`0 stares at you thoughtfully, obviously rapidly composing an epic poem...`n`n", Settings::getsetting("bard", "`^Seth"));
		OutputClass::output("`^U-G-L-Y, You ain't got no alibi -- you ugly, yeah yeah, you ugly!`n`n");
		$session['user']['charm']--;
		if ($session['user']['charm']<0){
			OutputClass::output("`0If you had any charm, you'd have been offended, instead, %s`0 breaks a lute string.", Settings::getsetting("bard", "`^Seth"));
		}else{
			OutputClass::output("`n`n`0Depressed, you lose a charm point.");
		}
		break;
	}
}
?>
