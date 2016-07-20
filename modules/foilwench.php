<?php
// mail ready
// addnews ready
// translator ready
function foilwench_getmoduleinfo(){
	$info = array(
		"name"=>"Foilwench",
		"version"=>"1.1",
		"author"=>"Eric Stevens",
		"category"=>"Forest Specials",
		"download"=>"core_module",
	);
	return $info;
}

function foilwench_install(){
	module_addeventhook("forest", "return 100;");
	return true;
}

function foilwench_uninstall(){
	return true;
}

function foilwench_dohook($hookname,$args){
	return $args;
}

function foilwench_runevent($type)
{
	require_once("lib/increment_specialty.php");
	global $session;
	// We assume this event only shows up in the forest currently.
	$from = "forest.php?";
	$session['user']['specialinc'] = "module:foilwench";

	$colors = array(""=>"`7");
	$colors = Modules::modulehook("specialtycolor", $colors);
	$c = $colors[$session['user']['specialty']];
	if (!$c) $c = "`7";
	if ($session['user']['specialty'] == "") {
		OutputClass::output("You have no direction in the world, you should rest and make some important decisions about your life.");
		$session['user']['specialinc']="";
		return;
	}
	$skills = Modules::modulehook("specialtynames");

	$op = Http::httpget('op');
	if ($op=="give"){
		if ($session['user']['gems']>0){
			OutputClass::output("%sYou give `@Foil`&wench%s a gem, and she hands you a slip of parchment with instructions on how to advance in your specialty.`n`n", $c, $c);
			OutputClass::output("You study it intensely, shred it up, and eat it lest infidels get ahold of the information.`n`n");
			OutputClass::output("`@Foil`&wench%s sighs... \"`&You didn't have to eat it...  Oh well, now be gone from here!%s\"`3", $c, $c);
			increment_specialty("`3");
			$session['user']['gems']--;
			DebugLogClass::debuglog("gave 1 gem to Foilwench");
		}else{
			OutputClass::output("%sYou hand over your imaginary gem.", $c);
			OutputClass::output("`@Foil`&wench%s stares blankly back at you.", $c);
			OutputClass::output("\"`&Come back when you have a `breal`b gem you simpleton.%s\"`n`n", $c);
			OutputClass::output("\"`#Simpleton?%s\" you ask.`n`n", $c);
			OutputClass::output("With that, `@Foil`&wench%s throws you out.`0", $c);
		}
		$session['user']['specialinc']="";
	}elseif($op=="dont"){
		OutputClass::output("%sYou inform `@Foil`&wench%s that if she would like to get rich, she will have to do so on her efforts, and stomp away.", $c, $c);
		$session['user']['specialinc']="";
	}elseif($session['user']['specialty']!=""){
		OutputClass::output("%sYou are seeking prey in the forest when you stumble across a strange hut.", $c);
		OutputClass::output("Ducking inside, you are met by the grizzled face of a battle-hardened old woman.");
		OutputClass::output("\"`&Greetings %s`&, I am `@Foil`&wench, master of all.%s\"`n`n", $session['user']['name'], $c);
		OutputClass::output("\"`#Master of all?%s\" you inquire.`n`n", $c);
		OutputClass::output("\"`&Yes, master of all.  All the skills are mine to control, and to teach.%s\"`n`n", $c);
		OutputClass::output("\"`#Yours to teach?%s\" you query.`n`n", $c);
		OutputClass::output("The old woman sighs, \"`&Yes, mine to teach.  I will teach you how to advance in %s on two conditions.%s\"`n`n", $skills[$session['user']['specialty']], $c);
		OutputClass::output("\"`#Two conditions?%s\" you repeat inquisitively.`n`n", $c);
		OutputClass::output("\"`&Yes.  First, you must give me a gem, and second you must stop repeating what I say in the form of a question!%s\"`n`n", $c);
		OutputClass::output("\"`#A gem!%s\" you state definitively.`n`n", $c);
		OutputClass::output("\"`&Well... I guess that wasn't a question.  So how about that gem?%s\"", $c);
		OutputClass::addnav("Give her a gem", $from."op=give");
		OutputClass::addnav("Don't give her a gem",$from."op=dont");
	}
}

function foilwench_run(){
}
?>
