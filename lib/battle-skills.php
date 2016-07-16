<?php
// translator ready
// addnews ready
// mail ready
require_once("lib/bell_rand.php");
require_once("lib/e_rand.php");
require_once("lib/buffs.php");

function rolldamage(){
	global $badguy,$session,$creatureattack,$creatureatkmod,$adjustment;
	global $creaturedefmod,$defmod,$atkmod,$buffset,$atk,$def,$options;

	if ($badguy['creaturehealth']>0 && $session['user']['hitpoints']>0){
		if ($options['type']=='pvp') {
			$adjustedcreaturedefense = $badguy['creaturedefense'];
		} else {
			$adjustedcreaturedefense =
				($creaturedefmod*$badguy['creaturedefense'] /
				 ($adjustment*$adjustment));
		}

		$creatureattack = $badguy['creatureattack']*$creatureatkmod;
		$adjustedselfdefense = ($session['user']['defense'] * $adjustment * $defmod);

		/*
		OutputClass::debug("Base creature defense: " . $badguy['creaturedefense']);
		OutputClass::debug("Creature defense mod: $creaturedefmod");
		OutputClass::debug("Adjustment: $adjustment");
		OutputClass::debug("Adjusted creature defense: $adjustedcreaturedefense");
		OutputClass::debug("Adjusted creature attack: $creatureattack");
		OutputClass::debug("Adjusted self defense: $adjustedselfdefense");
		*/

		while(!isset($creaturedmg) || !isset($selfdmg) || $creaturedmg==0 && $selfdmg==0){
			$atk = $session['user']['attack']*$atkmod;
			if (Erand::e_rand(1,20)==1 && $options['type'] != "pvp") $atk*=3;
			/*
			OutputClass::debug("Attack score: $atk");
			*/

			$patkroll = bell_rand(0,$atk);
			/*
			OutputClass::debug("Player Attack roll: $patkroll");
			*/

			// Set up for crit detection
			$atk = $patkroll;
			$catkroll = bell_rand(0,$adjustedcreaturedefense);
			/*
			OutputClass::debug("Creature defense roll: $catkroll");
			*/

			$creaturedmg = 0-(int)($catkroll - $patkroll);
			if ($creaturedmg<0) {
				$creaturedmg = (int)($creaturedmg/2);
				$creaturedmg = round($buffset['badguydmgmod'] *
						$creaturedmg, 0);
			}
			if ($creaturedmg > 0) {
				$creaturedmg = round($buffset['dmgmod']*$creaturedmg,0);
			}
			$pdefroll = bell_rand(0,$adjustedselfdefense);
			$catkroll = bell_rand(0,$creatureattack);
			/*
			   OutputClass::debug("Creature attack roll: $catkroll");
			   OutputClass::debug("Player defense roll: $pdefroll");
			 */
			$selfdmg = 0-(int)($pdefroll - $catkroll);
			if ($selfdmg<0) {
				$selfdmg=(int)($selfdmg/2);
				$selfdmg = round($selfdmg*$buffset['dmgmod'], 0);
			}
			if ($selfdmg > 0) {
				$selfdmg = round($selfdmg*$buffset['badguydmgmod'], 0);
			}
		}
	}else{
		$creaturedmg=0;
		$selfdmg=0;
	}
	// Handle god mode's invulnerability
	if ($buffset['invulnerable']) {
		$creaturedmg = abs($creaturedmg);
		$selfdmg = -abs($selfdmg);
	}
	return array("creaturedmg"=>(isset($creaturedmg)?$creaturedmg:0),"selfdmg"=>(isset($selfdmg)?$selfdmg:0));
}

function report_power_move($crit, $dmg) {
	global $session;
	$uatk = $session['user']['attack'];
	if ($crit > $uatk) {
		$power = 0;
		if ($crit > $uatk*4) {
			$msg = "`&`bYou execute a `%MEGA`& power move!!!`b`n";
			$power=1;

		} elseif ($crit > $uatk*3) {
			$msg = "`&`bYou execute a `^DOUBLE`& power move!!!`b`n";
			$power=1;
		} elseif ($crit > $uatk*2) {
			$msg = "`&`bYou execute a power move!!!`b`0`n";
			$power=1;
		}elseif ($crit > ($uatk * 1.5)) {
			$msg = "`7`bYou execute a minor power move!`b`0`n";
			$power=1;
		}
		if ($power) {
			Translator::tlschema("battle");
			OutputClass::output($msg);
			Translator::tlschema();

			$dmg += Erand::e_rand($crit/4, $crit/2);
			$dmg = max($dmg, 1);
		}
	}
	return $dmg;
}


function suspend_buffs($susp=false, $msg=false){
	global $session, $badguy;
	$suspendnotify = 0;
	reset($session['bufflist']);
	while (list($key,$buff)=each($session['bufflist'])){
		if (array_key_exists('suspended', $buff) && $buff['suspended'])
			continue;
		// Suspend non pvp allowed buffs when in pvp
		if ($susp && (!isset($buff[$susp]) || !$buff[$susp])) {
			$session['bufflist'][$key]['suspended'] = 1;
			$suspendnotify = 1;
		}
		// reset the 'used this round state'
		$buff['used']=0;
	}

	if ($suspendnotify) {
		$schema = false;
		if ($msg === false) {
			$schema = "battle";
			$msg = "`&The gods have suspended some of your enhancements!`n";
		}
		if ($schema) Translator::tlschema($schema);
		OutputClass::output($msg);
		if ($schema) Translator::tlschema();
	}
}

function suspend_buff_by_name($name, $msg=false) {
	global $session;
	// If it's not already suspended.
	if ($session['bufflist'][$name] &&
			!$session['bufflist'][$name]['suspended']) {
		$session['bufflist'][$name]['suspended'] = 1;

		// And notify.
		$schema = false;
		if ($msg === false) {
			$schema = "battle";
			$msg = "`&The gods have suspended some of your enhancements!`n";
		}
		if ($schema) Translator::tlschema($schema);
		OutputClass::output($msg);
		if ($schema) Translator::tlschema();
	}
}

function unsuspend_buff_by_name($name, $msg=false) {
	global $session;
	// If it's not already suspended.
	if ($session['bufflist'][$name] &&
			$session['bufflist'][$name]['suspended']) {
		$session['bufflist'][$name]['suspended'] = 0;

		// And notify.
		$schema = false;
		if ($msg === false) {
			$schema = "battle";
			$msg = "`&The gods have restored all suspended enhancements.`n`n";
		}
		if ($schema) Translator::tlschema($schema);
		OutputClass::output($msg);
		if ($schema) Translator::tlschema();
	}
}

function is_buff_active($name) {
	global $session;
	// If it's not already suspended.
	return (($session['bufflist'][$name] && !$session['bufflist'][$name]['suspended'])?1:0);
}


function unsuspend_buffs($susp=false,$msg=false) {
	global $session, $badguy;
	$unsuspendnotify = 0;
	reset($session['bufflist']);
	while (list($key,$buff)=each($session['bufflist'])){
		if (array_key_exists("expireafterfight",$buff) && $buff['expireafterfight']) unset($session['bufflist'][$key]);
		elseif (array_key_exists("suspended",$buff) && $buff['suspended'] && $susp && (!array_key_exists($susp, $buff) || !$buff[$susp])) {
			$session['bufflist'][$key]['suspended'] = 0;
			$unsuspendnotify=1;
		}
	}

	if ($unsuspendnotify) {
		$schema = false;
		if ($msg === false) {
			$schema = "battle";
			$msg = "`&The gods have restored all suspended enhancements.`n`n";
		}
		if ($schema) Translator::tlschema($schema);
		OutputClass::output($msg);
		if ($schema) Translator::tlschema();
	}
}

function apply_bodyguard($level){
	global $session, $badguy;
	if (!isset($session['bufflist']['bodyguard'])) {
		switch($level){
		case 1:
			$badguyatkmod=1.05;
			$defmod=0.95;
			$rounds=-1;
			break;
		case 2:
			$badguyatkmod=1.1;
			$defmod=0.9;
			$rounds=-1;
			break;
		case 3:
			$badguyatkmod=1.2;
			$defmod=0.8;
			$rounds=-1;
			break;
		case 4:
			$badguyatkmod=1.3;
			$defmod=0.7;
			$rounds=-1;
			break;
		case 5:
			$badguyatkmod=1.4;
			$defmod=0.6;
			$rounds=-1;
			break;
		}
		apply_buff('bodyguard' , array(
				"startmsg"=>"`\${badguy}'s bodyguard protects them!",
				"name"=>"`&Bodyguard",
				"wearoff"=>"The bodyguard seems to have fallen asleep.",
				"badguyatkmod"=>$badguyatkmod,
				"defmod"=>$defmod,
				"rounds"=>$rounds,
				"allowinpvp"=>1,
				"expireafterfight"=>1,
				"schema"=>"pvp"
			)
		);
	}
}

function apply_skill($skill,$l){
	global $session;
	if ($skill=="godmode"){
		apply_buff('godmode',array(
			"name"=>"`&GOD MODE",
			"rounds"=>1,
			"wearoff"=>"You feel mortal again.",
			"atkmod"=>25,
			"defmod"=>25,
			"invulnerable"=>1,
			"startmsg"=>"`&`bYou feel godlike.`b",
			"schema"=>"skill"
		));
	}
	Modules::modulehook("apply-specialties");
}
?>
