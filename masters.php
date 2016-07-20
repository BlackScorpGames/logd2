<?php
// Initially written as a module by Chris Vorndran.
// Moved into core by JT Traub

require_once("common.php");
require_once("lib/http.php");

SuAccess::check_su_access(SU_EDIT_CREATURES);

Translator::tlschema("masters");

$op = Http::httpget('op');
$id = (int)Http::httpget('id');
$act = Http::httpget('act');

PageParts::page_header("Masters Editor");
require_once("lib/superusernav.php");
superusernav();

if ($op == "del") {
	$sql = "DELETE FROM " . db_prefix("masters") . " WHERE creatureid=$id";
	db_query($sql);
	OutputClass::output("`^Master deleted.`0");
	$op = "";
	Http::httpset("op", "");
} elseif ($op == "save") {
	$name = addslashes(Http::httppost('name'));
	$weapon = addslashes(Http::httppost('weapon'));
	$win = addslashes(Http::httppost('win'));
	$lose = addslashes(Http::httppost('lose'));
	$lev = (int)Http::httppost('level');
	if ($id != 0) {
		$sql = "UPDATE " . db_prefix("masters") . " SET creaturelevel=$lev, creaturename='$name', creatureweapon='$weapon',  creaturewin='$win', creaturelose='$lose' WHERE creatureid=$id";
	} else {
		$atk = $lev * 2;
		$def = $lev * 2;
		$hp = $lev*11;
		if ($hp == 11) $hp++;
		$sql = "INSERT INTO " . db_prefix("masters") . " (creatureid,creaturelevel,creaturename,creatureweapon,creaturewin,creaturelose,creaturehealth,creatureattack,creaturedefense) VALUES ($id,$lev,'$name', '$weapon', '$win', '$lose', '$hp', '$atk', '$def')";
	}
	db_query($sql);
	if ($id == 0) {
		OutputClass::output("`^Master %s`^ added.", stripslashes($name));
	} else {
		OutputClass::output("`^Master %s`^ updated.", stripslashes($name));
	}
	$op = "";
	Http::httpset("op", "");
} elseif ($op == "edit") {
	OutputClass::addnav("Functions");
	OutputClass::addnav("Return to Masters Editor", "masters.php");
	$sql = "SELECT * FROM ".db_prefix("masters")." WHERE creatureid=$id";
	$res = db_query($sql);
	if (db_num_rows($res) == 0) {
		$row = array(
			'creaturelevel'=>1,
			'creaturename'=>'',
			'creatureweapon'=>'',
			'creaturewin'=>'',
			'creaturelose'=>''
		);
	} else {
		$row = db_fetch_assoc($res);
	}
	OutputClass::addnav("","masters.php?op=save&id=$id");
	OutputClass::rawoutput("<form action='masters.php?op=save&id=$id' method='POST'>");
	OutputClass::output("`^Master's level:`n");
	OutputClass::rawoutput("<input id='input' name='level' value='".htmlentities($row['creaturelevel'], ENT_COMPAT, Settings::getsetting("charset", "ISO-8859-1"))."' SIZE=5>");
	OutputClass::output_notl("`n");
	OutputClass::output("`^Master's name:`n");
	OutputClass::rawoutput("<input id='input' name='name' value='".htmlentities($row['creaturename'], ENT_COMPAT, Settings::getsetting("charset", "ISO-8859-1"))."'>");
	OutputClass::output_notl("`n");
	OutputClass::output("`^Master's weapon:`n");
	OutputClass::rawoutput("<input id='input' name='weapon' value='".htmlentities($row['creatureweapon'], ENT_COMPAT, Settings::getsetting("charset", "ISO-8859-1"))."'>");
	OutputClass::output_notl("`n");
	OutputClass::output("`^Master's speech when player wins:`n");
	OutputClass::rawoutput("<textarea name='lose' rows='5' cols='30' class='input'>".htmlentities($row['creaturelose'], ENT_COMPAT, Settings::getsetting("charset", "ISO-8859-1"))."</textarea>");
	OutputClass::output_notl("`n");
	OutputClass::output("`^Master's speech when player loses:`n");
	OutputClass::rawoutput("<textarea name='win' rows='5' cols='30' class='input'>".htmlentities($row['creaturewin'], ENT_COMPAT, Settings::getsetting("charset", "ISO-8859-1"))."</textarea>");
	OutputClass::output_notl("`n");
	$submit = Translator::translate_inline("Submit");
	OutputClass::rawoutput("<input type='submit' class='button' value='$submit'>");
	OutputClass::rawoutput("</form>");
	OutputClass::output_notl("`n`n");
	OutputClass::output("`#The following codes are supported in both the win and lose speeches (case matters):`n");
	OutputClass::output("%w = The players's name (can be specified as {goodguy}`n");
	OutputClass::output("%W = The masters's name (can be specified as {badguy}`n");
	OutputClass::output("%x = The players's weapon (can be specified as {weapon}`n");
	OutputClass::output("%X = The master's weapon (can be specified as {creatureweapon}`n");
	OutputClass::output("%a = The players's armor (can be specified as {armor}`n");
	OutputClass::output("%s = Subjective pronoun for the player (him her)`n");
	OutputClass::output("%p = Possessive pronoun for the player (his her)`n");
	OutputClass::output("%o = Objective pronoun for the player (he she)`n");
}

if ($op == "") {
	OutputClass::addnav("Functions");
	OutputClass::addnav("Refresh list", "masters.php");
	OutputClass::addnav("Add master", "masters.php?op=edit&id=0");
	$sql = "SELECT * FROM ".db_prefix("masters")." ORDER BY creaturelevel";
	$res = db_query($sql);
	$count = db_num_rows($res);
	$ops = Translator::translate_inline("Ops");
	$edit = Translator::translate_inline("edit");
	$del = Translator::translate_inline("del");
	$delconfirm = Translator::translate_inline("Are you sure you wish to delete this master.");
	$name = Translator::translate_inline("Name");
	$level = Translator::translate_inline ("Level");
	$lose = Translator::translate_inline("Lose to Master");
	$win = Translator::translate_inline("Win against Master");
	$weapon = Translator::translate_inline("Weapon");
	OutputClass::rawoutput("<table border='0' cellpadding='2' cellspacing='1' align='center' bgcolor='#999999'>");
	OutputClass::rawoutput("<tr class='trhead'><td>$ops</td><td>$level</td><td>$name</td><td>$weapon</td><td>$win</td><td>$lose</tr>");
	$i = 0;
	while ($row = db_fetch_assoc($res)) {
		$id = $row['creatureid'];
		OutputClass::rawoutput("<tr class='".($i%2?"trdark":"trlight")."'><td nowrap>");
		OutputClass::rawoutput("[ <a href='masters.php?op=edit&id=$id'>");
		OutputClass::output_notl($edit);
		OutputClass::rawoutput("</a> | <a href='masters.php?op=del&id=$id' onClick='return confirm(\"$delconfirm\");'>");
		OutputClass::output_notl($del);
		OutputClass::rawoutput("] </a>");
		OutputClass::addnav("","masters.php?op=edit&id=$id");
		OutputClass::addnav("","masters.php?op=del&id=$id");
		OutputClass::rawoutput("</td><td>");
		OutputClass::output_notl("`%%s`0",$row['creaturelevel']);
		OutputClass::rawoutput("</td><td>");
		OutputClass::output_notl("`#%s`0",stripslashes($row['creaturename']));
		OutputClass::rawoutput("</td><td>");
		OutputClass::output_notl("`!%s`0",stripslashes($row['creatureweapon']));
		OutputClass::rawoutput("</td><td>");
		OutputClass::output_notl("`&%s`0",stripslashes($row['creaturelose']));
		OutputClass::rawoutput("</td><td>");
		OutputClass::output_notl("`^%s`0",stripslashes($row['creaturewin']));
		OutputClass::rawoutput("</td></tr>");
		$i++;
	}
	OutputClass::rawoutput("</table>");
	OutputClass::output("`n`#You can change the names, weapons and messages of all of the Training Masters.");
	OutputClass::output("It is suggested, that you do not toy around with this, unless you know what you are doing.`0`n");
}
PageParts::page_footer();
?>