<?php
// addnews ready
// translator ready
// mail ready
require_once("common.php");
require_once("lib/commentary.php");
require_once("lib/pvpwarning.php");
require_once("lib/sanitize.php");
require_once("lib/pvplist.php");
require_once("lib/http.php");
require_once("lib/buffs.php");
require_once("lib/events.php");
require_once("lib/villagenav.php");

Translator::tlschema("inn");

addcommentary();
$iname = Settings::getsetting("innname", LOCATION_INN);
$vname = Settings::getsetting("villagename", LOCATION_FIELDS);
$barkeep = Settings::getsetting('barkeep','`tCedrik');

$op = Http::httpget('op');
// Correctly reset the location if they fleeing the dragon
// This needs to be done up here because a special could alter your op.
if ($op == "fleedragon") {
	$session['user']['location'] = $vname;
}

PageParts::page_header(array("%s",sanitize($iname)));
$skipinndesc = handle_event("inn");

if (!$skipinndesc) {
	checkday();
	OutputClass::rawoutput("<span style='color: #9900FF'>");
	OutputClass::output_notl("`c`b");
	OutputClass::output($iname);
	OutputClass::output_notl("`b`c");
}

$subop = Http::httpget('subop');

$com = Http::httpget('comscroll');
$comment = httppost('insertcommentary');

require_once("lib/partner.php");
$partner = get_partner();
OutputClass::addnav("Other");
villagenav();
OutputClass::addnav("I?Return to the Inn","inn.php");

switch ($op) {
	case "": case "strolldown": case "fleedragon":
		require("lib/inn/inn_default.php");
		blocknav("inn.php");
		break;
	case "converse":
		commentdisplay("You stroll over to a table, place your foot up on the bench and listen in on the conversation:`n", "inn","Add to the conversation?",20);
		break;
	case "bartender":
		require("lib/inn/inn_bartender.php");
		break;
	case "room":
		require("lib/inn/inn_room.php");
		break;
}

if (!$skipinndesc) OutputClass::rawoutput("</span>");

PageParts::page_footer();
?>