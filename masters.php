<?php
// Initially written as a module by Chris Vorndran.
// Moved into core by JT Traub

require_once("common.php");
require_once("lib/http.php");

check_su_access(SU_EDIT_CREATURES);

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
	httpset("op", "");
} elseif ($op == "save") {
	$name = addslashes(httppost('name'));
	$weapon = addslashes(httppost('weapon'));
	$win = addslashes(httppost('win'));
	$lose = addslashes(httppost('lose'));
	$lev = (int)httppost('level');
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
	httpset("op", "");
} elseif ($op == "edit") {
	addnav("Functions");
	addnav("Return to Masters Editor", "masters.php");
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
	addnav("","masters.php?op=save&id=$id");
	rawoutput("<form action='masters.php?op=save&id=$id' method='POST'>");
	OutputClass::output("`^Master's level:`n");
	rawoutput("<input id='input' name='level' value='".htmlentities($row['creaturelevel'], ENT_COMPAT, Settings::getsetting("charset", "ISO-8859-1"))."' SIZE=5>");
	output_notl("`n");
	OutputClass::output("`^Master's name:`n");
	rawoutput("<input id='input' name='name' value='".htmlentities($row['creaturename'], ENT_COMPAT, Settings::getsetting("charset", "ISO-8859-1"))."'>");
	output_notl("`n");
	OutputClass::output("`^Master's weapon:`n");
	rawoutput("<input id='input' name='weapon' value='".htmlentities($row['creatureweapon'], ENT_COMPAT, Settings::getsetting("charset", "ISO-8859-1"))."'>");
	output_notl("`n");
	OutputClass::output("`^Master's speech when player wins:`n");
	rawoutput("<textarea name='lose' rows='5' cols='30' class='input'>".htmlentities($row['creaturelose'], ENT_COMPAT, Settings::getsetting("charset", "ISO-8859-1"))."</textarea>");
	output_notl("`n");
	OutputClass::output("`^Master's speech when player loses:`n");
	rawoutput("<textarea name='win' rows='5' cols='30' class='input'>".htmlentities($row['creaturewin'], ENT_COMPAT, Settings::getsetting("charset", "ISO-8859-1"))."</textarea>");
	output_notl("`n");
	$submit = translate_inline("Submit");
	rawoutput("<input type='submit' class='button' value='$submit'>");
	rawoutput("</form>");
	output_notl("`n`n");
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
	addnav("Functions");
	addnav("Refresh list", "masters.php");
	addnav("Add master", "masters.php?op=edit&id=0");
	$sql = "SELECT * FROM ".db_prefix("masters")." ORDER BY creaturelevel";
	$res = db_query($sql);
	$count = db_num_rows($res);
	$ops = translate_inline("Ops");
	$edit = translate_inline("edit");
	$del = translate_inline("del");
	$delconfirm = translate_inline("Are you sure you wish to delete this master.");
	$name = translate_inline("Name");
	$level = translate_inline ("Level");
	$lose = translate_inline("Lose to Master");
	$win = translate_inline("Win against Master");
	$weapon = translate_inline("Weapon");
	rawoutput("<table border='0' cellpadding='2' cellspacing='1' align='center' bgcolor='#999999'>");
	rawoutput("<tr class='trhead'><td>$ops</td><td>$level</td><td>$name</td><td>$weapon</td><td>$win</td><td>$lose</tr>");
	$i = 0;
	while ($row = db_fetch_assoc($res)) {
		$id = $row['creatureid'];
		rawoutput("<tr class='".($i%2?"trdark":"trlight")."'><td nowrap>");
		rawoutput("[ <a href='masters.php?op=edit&id=$id'>");
		output_notl($edit);
		rawoutput("</a> | <a href='masters.php?op=del&id=$id' onClick='return confirm(\"$delconfirm\");'>");
		output_notl($del);
		rawoutput("] </a>");
		addnav("","masters.php?op=edit&id=$id");
		addnav("","masters.php?op=del&id=$id");
		rawoutput("</td><td>");
		output_notl("`%%s`0",$row['creaturelevel']);
		rawoutput("</td><td>");
		output_notl("`#%s`0",stripslashes($row['creaturename']));
		rawoutput("</td><td>");
		output_notl("`!%s`0",stripslashes($row['creatureweapon']));
		rawoutput("</td><td>");
		output_notl("`&%s`0",stripslashes($row['creaturelose']));
		rawoutput("</td><td>");
		output_notl("`^%s`0",stripslashes($row['creaturewin']));
		rawoutput("</td></tr>");
		$i++;
	}
	rawoutput("</table>");
	OutputClass::output("`n`#You can change the names, weapons and messages of all of the Training Masters.");
	OutputClass::output("It is suggested, that you do not toy around with this, unless you know what you are doing.`0`n");
}
page_footer();
?>