<?php
// translator ready
// addnews ready
// mail ready
require_once("common.php");
require_once("lib/showform.php");
require_once("lib/http.php");

check_su_access(SU_EDIT_EQUIPMENT);

Translator::tlschema("weapon");

PageParts::page_header("Weapon Editor");
$weaponlevel = (int)Http::httpget("level");
require_once("lib/superusernav.php");
superusernav();

OutputClass::addnav("Editor");
OutputClass::addnav("Weapon Editor Home","weaponeditor.php?level=$weaponlevel");

OutputClass::addnav("Add a weapon","weaponeditor.php?op=add&level=$weaponlevel");
$values = array(1=>48,225,585,990,1575,2250,2790,3420,4230,5040,5850,6840,8010,9000,10350);
OutputClass::rawoutput("<h3>");
if ($weaponlevel == 1) {
	OutputClass::output("`&Weapons for 1 Dragon Kill`0");
} else {
	OutputClass::output("`&Weapons for %s Dragon Kills`0",$weaponlevel);
}
OutputClass::rawoutput("<h3>");

$weaponarray=array(
	"Weapon,title",
	"weaponid"=>"Weapon ID,hidden",
	"weaponname"=>"Weapon Name",
	"damage"=>"Damage,range,1,15,1");
$op = Http::httpget('op');
$id = Http::httpget('id');
if($op=="edit" || $op=="add"){
	if ($op=="edit"){
		$sql = "SELECT * FROM " . db_prefix("weapons") . " WHERE weaponid='$id'";
		$result = db_query($sql);
		$row = db_fetch_assoc($result);
	}else{
		$sql = "SELECT max(damage+1) AS damage FROM " . db_prefix("weapons") . " WHERE level=$weaponlevel";
		$result = db_query($sql);
		$row = db_fetch_assoc($result);
	}
	OutputClass::rawoutput("<form action='weaponeditor.php?op=save&level=$weaponlevel' method='POST'>");
	OutputClass::addnav("","weaponeditor.php?op=save&level=$weaponlevel");
	ShowFormClass::showform($weaponarray,$row);
	OutputClass::rawoutput("</form>");
}else if($op=="del"){
	$sql = "DELETE FROM " . db_prefix("weapons") . " WHERE weaponid='$id'";
	db_query($sql);
	$op = "";
	httpset("op", $op);
}else if($op=="save"){
	$weaponid = (int)Http::httppost("weaponid");
	$damage = Http::httppost("damage");
	$weaponname = Http::httppost("weaponname");
	if ($weaponid>0){
		$sql = "UPDATE " . db_prefix("weapons") . " SET weaponname=\"$weaponname\",damage=\"$damage\",value=" .  $values[$damage]." WHERE weaponid='$weaponid'";
	}else{
		$sql = "INSERT INTO " . db_prefix("weapons") . " (level,damage,weaponname,value) VALUES ($weaponlevel,\"$damage\",\"$weaponname\",".$values[$damage].")";
	}
	db_query($sql);
	//OutputClass::output($sql);
	$op = "";
	httpset("op", $op);
}
if ($op==""){
	$sql = "SELECT max(level+1) as level FROM " . db_prefix("weapons");
	$res = db_query($sql);
	$row = db_fetch_assoc($res);
	$max = $row['level'];
	for ($i=0;$i<=$max;$i++){
		if ($i == 1)
			OutputClass::addnav("Weapons for 1 DK","weaponeditor.php?level=$i");
		else
			OutputClass::addnav(array("Weapons for %s DKs",$i),"weaponeditor.php?level=$i");
	}
	$sql = "SELECT * FROM " . db_prefix("weapons") . " WHERE level=$weaponlevel ORDER BY damage";
	$result= db_query($sql);
	$ops = Translator::translate_inline("Ops");
	$name = Translator::translate_inline("Name");
	$cost = Translator::translate_inline("Cost");
	$damage = Translator::translate_inline("Damage");
	$level = Translator::translate_inline("Level");
	$edit = Translator::translate_inline("Edit");
	$del = Translator::translate_inline("Del");
	$delconfirm = Translator::translate_inline("Are you sure you wish to delete this weapon?");

	OutputClass::rawoutput("<table border=0 cellpadding=2 cellspacing=1 bgcolor='#999999'>");
	OutputClass::rawoutput("<tr class='trhead'><td>$ops</td><td>$name</td><td>$cost</td><td>$damage</td><td>$level</td></tr>");
	$number=db_num_rows($result);
	for ($i=0;$i<$number;$i++){
		$row = db_fetch_assoc($result);
		OutputClass::rawoutput("<tr class='".($i%2?"trdark":"trlight")."'>");
		OutputClass::rawoutput("<td>[<a href='weaponeditor.php?op=edit&id={$row['weaponid']}&level=$weaponlevel'>$edit</a>|<a href='weaponeditor.php?op=del&id={$row['weaponid']}&level=$weaponlevel' onClick='return confirm(\"Are you sure you wish to delete this weapon?\");'>$del</a>]</td>");
		OutputClass::addnav("","weaponeditor.php?op=edit&id={$row['weaponid']}&level=$weaponlevel");
		OutputClass::addnav("","weaponeditor.php?op=del&id={$row['weaponid']}&level=$weaponlevel");
		OutputClass::rawoutput("<td>");
		OutputClass::output_notl($row['weaponname']);
		OutputClass::rawoutput("</td><td>");
		OutputClass::output_notl($row['value']);
		OutputClass::rawoutput("</td><td>");
		OutputClass::output_notl($row['damage']);
		OutputClass::rawoutput("</td><td>");
		OutputClass::output_notl($row['level']);
		OutputClass::rawoutput("</td>");
		OutputClass::rawoutput("</tr>");
	}
	OutputClass::rawoutput("</table>");
}
PageParts::page_footer();
?>