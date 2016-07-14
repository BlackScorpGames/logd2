<?php
	PageParts::page_header("Clan Halls");
	OutputClass::addnav("Clan Options");
	OutputClass::output("`b`c`&Clan Halls`c`b");
	OutputClass::output("You stroll off to the side where there are some plush leather chairs, and take a seat.");
	OutputClass::output("There are several other warriors sitting here talking amongst themselves.");
	OutputClass::output("Some Ye Olde Muzak is coming from a fake rock sitting at the base of a potted bush.`n`n");
	commentdisplay("", "waiting","Speak",25);
	if ($session['user']['clanrank']==CLAN_APPLICANT) {
		OutputClass::addnav("Return to the Lobby","clan.php");
	} else {
		OutputClass::addnav("Return to your Clan Rooms","clan.php");
	}
?>