<?php
if ($com=="" && !$comment && $op!="fleedragon") {
	if (Modules::module_events("inn", Settings::getsetting("innchance", 0)) != 0) {
		if (OutputClass::checknavs()) {
			PageParts::page_footer();
		} else {
			$skipinndesc = true;
			$session['user']['specialinc'] = "";
			$session['user']['specialmisc'] = "";
			$op = "";
			Http::httpset("op", "");
		}
	}
}

OutputClass::addnav("Things to do");
$args = Modules::modulehook("blockcommentarea", array("section"=>"inn"));
if (!isset($args['block']) || $args['block'] != 'yes') {
	OutputClass::addnav("Converse with patrons","inn.php?op=converse");
}
OutputClass::addnav(array("B?Talk to %s`0 the Barkeep",$barkeep),"inn.php?op=bartender");

OutputClass::addnav("Other");
OutputClass::addnav("Get a room (log out)","inn.php?op=room");


if (!$skipinndesc) {
	if ($op=="strolldown"){
		OutputClass::output("You stroll down the stairs of the inn, once again ready for adventure!`n");
	} elseif ($op=="fleedragon") {
		OutputClass::output("You pelt into the inn as if the Devil himself is at your heels.  Slowly you catch your breath and look around.`n");
		OutputClass::output("%s`0 catches your eye and then looks away in disgust at your cowardice!`n`n",$partner);
		OutputClass::output("You `\$lose`0 a charm point.`n`n");
		if ($session['user']['charm'] > 0) $session['user']['charm']--;
	} else {
		OutputClass::output("You duck into a dim tavern that you know well.");
		OutputClass::output("The pungent aroma of pipe tobacco fills the air.`n");
	}

	OutputClass::output("You wave to several patrons that you know.");
	if ($session['user']['sex']) {
		OutputClass::output("You give a special wave and wink to %s`0 who is tuning his harp by the fire.",$partner);
	} else {
		OutputClass::output("You give a special wave and wink to %s`0 who is serving drinks to some locals.",$partner);
	}
	OutputClass::output("%s`0 the innkeep stands behind his counter, chatting with someone.",$barkeep);

	$chats = array(
		Translator::translate_inline("dragons"),
		Translator::translate_inline(Settings::getsetting("bard", "`^Seth")),
		Translator::translate_inline(Settings::getsetting("barmaid", "`%Violet")),
		Translator::translate_inline("`#MightyE"),
		Translator::translate_inline("fine drinks"),
		$partner,
	);
	$chats = Modules::modulehook("innchatter", $chats);
	$talk = $chats[e_rand(0, count($chats)-1)];
	OutputClass::output("You can't quite make out what he is saying, but it's something about %s`0.`n`n", $talk);
	OutputClass::output("The clock on the mantle reads `6%s`0.`n", GameDateTime::getgametime());
	Modules::modulehook("inn-desc", array());
}
Modules::modulehook("inn", array());
Modules::module_display_events("inn", "inn.php");
?>