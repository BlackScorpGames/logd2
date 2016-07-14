<?php
// translator ready
// addnews ready
// mail ready
require_once("common.php");
require_once("lib/commentary.php");
require_once("lib/sanitize.php");
require_once("lib/http.php");

check_su_access(0xFFFFFFFF &~ SU_DOESNT_GIVE_GROTTO);
addcommentary();
Translator::tlschema("superuser");

require_once("lib/superusernav.php");
superusernav();

$op = Http::httpget('op');
if ($op=="keepalive"){
	$sql = "UPDATE " . db_prefix("accounts") . " SET laston='".date("Y-m-d H:i:s")."' WHERE acctid='{$session['user']['acctid']}'";
	db_query($sql);
	global $REQUEST_URI;
	echo '<html><meta http-equiv="Refresh" content="30;url='.$REQUEST_URI.'"></html><body>'.date("Y-m-d H:i:s")."</body></html>";
	exit();
}elseif ($op=="newsdelete"){
	$sql = "DELETE FROM " . db_prefix("news") . " WHERE newsid='".Http::httpget('newsid')."'";
	db_query($sql);
	$return = Http::httpget('return');
	$return = cmd_sanitize($return);
	$return = substr($return,strrpos($return,"/")+1);
	redirect($return);
}

PageParts::page_header("Superuser Grotto");

OutputClass::output("`^You duck into a secret cave that few know about. ");
if ($session['user']['sex']){
  	OutputClass::output("Inside you are greeted by the sight of numerous muscular bare-chested men who wave palm fronds at you and offer to feed you grapes as you lounge on Greco-Roman couches draped with silk.`n`n");
}else{
	OutputClass::output("Inside you are greeted by the sight of numerous scantily clad buxom women who wave palm fronds at you and offer to feed you grapes as you lounge on Greco-Roman couches draped with silk.`n`n");
}
commentdisplay("", "superuser","Engage in idle conversation with other gods:",25);
OutputClass::addnav("Actions");
if ($session['user']['superuser'] & SU_EDIT_PETITIONS) OutputClass::addnav("Petition Viewer","viewpetition.php");
if ($session['user']['superuser'] & SU_EDIT_COMMENTS) OutputClass::addnav("C?Recent Commentary","moderate.php");
if ($session['user']['superuser'] & SU_EDIT_COMMENTS) OutputClass::addnav("B?Player Bios","bios.php");
if ($session['user']['superuser'] & SU_EDIT_DONATIONS) OutputClass::addnav("Donator Page","donators.php");
if (file_exists("paylog.php")  &&
		($session['user']['superuser'] & SU_EDIT_PAYLOG)) {
	OutputClass::addnav("Payment Log","paylog.php");
}
if ($session['user']['superuser'] & SU_RAW_SQL) OutputClass::addnav("Q?Run Raw SQL", "rawsql.php");
if ($session['user']['superuser'] & SU_IS_TRANSLATOR) OutputClass::addnav("U?Untranslated Texts", "untranslated.php");

OutputClass::addnav("Editors");
if ($session['user']['superuser'] & SU_EDIT_USERS) OutputClass::addnav("User Editor","user.php");
if ($session['user']['superuser'] & SU_EDIT_USERS) OutputClass::addnav("Title Editor","titleedit.php");
if ($session['user']['superuser'] & SU_EDIT_CREATURES) OutputClass::addnav("E?Creature Editor","creatures.php");
if ($session['user']['superuser'] & SU_EDIT_MOUNTS) OutputClass::addnav("Mount Editor","mounts.php");
if ($session['user']['superuser'] & SU_EDIT_MOUNTS) OutputClass::addnav("Companion Editor","companions.php");
if ($session['user']['superuser'] & SU_EDIT_CREATURES) OutputClass::addnav("Taunt Editor","taunt.php");
if ($session['user']['superuser'] & SU_EDIT_CREATURES) OutputClass::addnav("Master Editor","masters.php");
if (file_exists("looteditor.php") &&
		$session['user']['superuser'] & SU_EDIT_ITEMS) {
	OutputClass::addnav("Loot Editor","looteditor.php");
}
if ($session['user']['superuser'] & SU_EDIT_EQUIPMENT) OutputClass::addnav("Weapon Editor","weaponeditor.php");
if ($session['user']['superuser'] & SU_EDIT_EQUIPMENT) OutputClass::addnav("Armor Editor","armoreditor.php");
if ($session['user']['superuser'] & SU_EDIT_COMMENTS) OutputClass::addnav("Nasty Word Editor","badword.php");
if ($session['user']['superuser'] & SU_MANAGE_MODULES) OutputClass::addnav("Manage Modules","modules.php");

if ($session['user']['superuser'] & SU_EDIT_CONFIG) OutputClass::addnav("Mechanics");
if ($session['user']['superuser'] & SU_EDIT_CONFIG) OutputClass::addnav("Game Settings","configuration.php");
if ($session['user']['superuser'] & SU_EDIT_CONFIG) OutputClass::addnav("Referring URLs","referers.php");
if ($session['user']['superuser'] & SU_EDIT_CONFIG) OutputClass::addnav("Stats","stats.php");
/*//*/if (file_exists("gamelog.php") &&
/*//*/		$session['user']['superuser'] & SU_EDIT_CONFIG) {
/*//*/	OutputClass::addnav("Gamelog Viewer","gamelog.php");
/*//*/}

OutputClass::addnav("Module Configurations");

modulehook("superuser", array(), true);

page_footer();
?>