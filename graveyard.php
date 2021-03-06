<?php
// addnews ready.
// translator ready
// mail ready
require_once("common.php");
require_once("lib/http.php");
require_once("lib/buffs.php");
require_once("lib/events.php");

Translator::tlschema("graveyard");

PageParts::page_header("The Graveyard");
$skipgraveyardtext = Events::handle_event("graveyard");
$deathoverlord=Settings::getsetting('deathoverlord','`$Ramius');
if (!$skipgraveyardtext) {
	if ($session['user']['alive']) {
		RedirectClass::redirect("village.php");
	}

	GameDateTime::checkday();
}
$battle = false;
Buffs::strip_all_buffs();

$op = Http::httpget('op');
switch ($op) {
	case "search":
		require_once("lib/graveyard/case_battle_search.php");
		break;
case "run":
	if (Erand::e_rand(0,2)==1) {
		OutputClass::output("`\$%s`) curses you for your cowardice.`n`n",$deathoverlord);
		$favor = 5 + Erand::e_rand(0, $session['user']['level']);
		if ($favor > $session['user']['deathpower'])
			$favor = $session['user']['deathpower'];
		if ($favor > 0) {
			OutputClass::output("`)You have `\$LOST `^%s`) favor with `\$%s`).",$favor,$deathoverlord);
			$session['user']['deathpower']-=$favor;
		}
		Translator::tlschema("nav");
		OutputClass::addnav("G?Return to the Graveyard","graveyard.php");
		Translator::tlschema();
	} else {
		OutputClass::output("`)As you try to flee, you are summoned back to the fight!`n`n");
		$battle=true;
	}
	break;
case "fight":
		$battle = true;

}

if ($battle){
	//make some adjustments to the user to put them on mostly even ground
	//with the undead guy.
	$originalhitpoints = $session['user']['hitpoints'];
	$session['user']['hitpoints'] = $session['user']['soulpoints'];
	$originalattack = $session['user']['attack'];
	$originaldefense = $session['user']['defense'];
	$session['user']['attack'] =
		10 + round(($session['user']['level'] - 1) * 1.5);
	$session['user']['defense'] =
		10 + round(($session['user']['level'] - 1) * 1.5);

	require_once("battle.php");

	//reverse those adjustments, battle calculations are over.
	$session['user']['attack'] = $originalattack;
	$session['user']['defense'] = $originaldefense;
	$session['user']['soulpoints'] = $session['user']['hitpoints'];
	$session['user']['hitpoints'] = $originalhitpoints;
	if ($victory || $defeat) $badguy = $newenemies[0]; // Only one badguy
	if ($victory) {
		Translator::tlschema("battle");
		$msg = Translator::translate_inline($badguy['creaturelose']);
		Translator::tlschema();
		OutputClass::output_notl("`b`&%s`0`b`n", $msg);
		OutputClass::output("`b`\$You have tormented %s!`0`b`n", $badguy['creaturename']);
		OutputClass::output("`#You receive `^%s`# favor with `\$%s`#!`n`0", $badguy['creatureexp'],$deathoverlord);
		$session['user']['deathpower']+=$badguy['creatureexp'];
		$op = "";
		Http::httpset('op', "");
		$skipgraveyardtext=true;
	}else{
		if ($defeat){
			require_once("lib/taunt.php");
			$taunt = Taunt::select_taunt_array();
			AddNewsClass::addnews("`)%s`) has been defeated in the graveyard by %s.`n%s",$session['user']['name'],$badguy['creaturename'],$taunt);
			OutputClass::output("`b`&You have been defeated by `%%s`&!!!`n", $badguy['creaturename']);
			OutputClass::output("You may not torment any more souls today.");
			$session['user']['gravefights']=0;
			Translator::tlschema("nav");
			OutputClass::addnav("G?Return to the Graveyard","graveyard.php");
			Translator::tlschema();
		}else{
			require_once("lib/fightnav.php");
			FightNavClass::fightnav(false, true, "graveyard.php");
		}
	}
}

switch ($op) {
	case "search": case "run": case "fight":
		break;
	case "enter":
		require_once("lib/graveyard/case_enter.php");
		break;
	case "restore":
		require_once("lib/graveyard/case_restore.php");
		break;
	case "resurrection":
		require_once("lib/graveyard/case_resurrection.php");
		break;
	case "question":
		require_once("lib/graveyard/case_question.php");
		break;
	case "haunt":
		require_once("lib/graveyard/case_haunt.php");
		break;
	case "haunt2":
		require_once("lib/graveyard/case_haunt2.php");
		break;
	case "haunt3":
		require_once("lib/graveyard/case_haunt3.php");
		break;
	default:
		require_once("lib/graveyard/case_default.php");
		break;
}

PageParts::page_footer();
?>