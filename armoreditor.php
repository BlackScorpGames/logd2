<?php
// translator ready
// addnews ready
// mail ready
require_once("common.php");
require_once("lib/showform.php");
require_once("lib/http.php");

SuAccess::check_su_access(SU_EDIT_EQUIPMENT);

Translator::tlschema("armor");

PageParts::page_header("Armor Editor");
$armorlevel = (int)Http::httpget('level');
require_once("lib/superusernav.php");
SuperUserNavClass::superusernav();
OutputClass::addnav("Armor Editor");
OutputClass::addnav("Armor Editor Home","armoreditor.php?level=$armorlevel");

OutputClass::addnav("Add armor","armoreditor.php?op=add&level=$armorlevel");
$values = array(1=>48,225,585,990,1575,2250,2790,3420,4230,5040,5850,6840,8010,9000,10350);
OutputClass::output("`&<h3>Armor for %s Dragon Kills</h3>`0",$armorlevel,true);

$armorarray=array(
	"Armor,title",
	"armorid"=>"Armor ID,hidden",
	"armorname"=>"Armor Name",
	"defense"=>"Defense,range,1,15,1");
$op = Http::httpget('op');
$id = Http::httpget('id');
if($op=="edit" || $op=="add"){
	if ($op=="edit"){
		$sql = "SELECT * FROM " . db_prefix("armor") . " WHERE armorid='$id'";
		$result = db_query($sql);
		$row = db_fetch_assoc($result);
	}else{
		$sql = "SELECT max(defense+1) AS defense FROM " . db_prefix("armor") . " WHERE level=$armorlevel";
		$result = db_query($sql);
		$row = db_fetch_assoc($result);
	}
	OutputClass::rawoutput("<form action='armoreditor.php?op=save&level=$armorlevel' method='POST'>");
	OutputClass::addnav("","armoreditor.php?op=save&level=$armorlevel");
	ShowFormClass::showform($armorarray,$row);
	OutputClass::rawoutput("</form>");
}else if($op=="del"){
	$sql = "DELETE FROM " . db_prefix("armor") . " WHERE armorid='$id'";
	db_query($sql);
	//OutputClass::output($sql);
	$op = "";
	Http::httpset("op", $op);
}else if($op=="save"){
	$armorid = Http::httppost('armorid');
	$armorname = Http::httppost('armorname');
	$defense = Http::httppost('defense');
	if ($armorid>0){
		$sql = "UPDATE " . db_prefix("armor") . " SET armorname=\"$armorname\",defense=\"$defense\",value=".$values[$defense]." WHERE armorid='$armorid'";
	}else{
		$sql = "INSERT INTO " . db_prefix("armor") . " (level,defense,armorname,value) VALUES ($armorlevel,\"$defense\",\"$armorname\",".$values[$defense].")";
	}
	db_query($sql);
	$op = "";
	Http::httpset("op", $op);
}
if ($op==""){
	$sql = "SELECT max(level+1) AS level FROM " . db_prefix("armor");
	$res = db_query($sql);
	$row = db_fetch_assoc($res);
	$max = $row['level'];
	for ($i=0;$i<=$max;$i++){
		if ($i == 1)
			OutputClass::addnav(array("Armor for %s DK",$i),"armoreditor.php?level=$i");
		else
			OutputClass::addnav(array("Armor for %s DKs",$i),"armoreditor.php?level=$i");
	}
	$sql = "SELECT * FROM " . db_prefix("armor") . " WHERE level=$armorlevel ORDER BY defense";
	$result= db_query($sql);
	$ops = Translator::translate_inline("Ops");
	$name = Translator::translate_inline("Name");
	$cost = Translator::translate_inline("Cost");
	$defense = Translator::translate_inline("Defense");
	$level = Translator::translate_inline("Level");
	$edit = Translator::translate_inline("Edit");
	$del = Translator::translate_inline("Del");
	$delconfirm = Translator::translate_inline("Are you sure you wish to delete this armor?");

	OutputClass::rawoutput("<table border=0 cellpadding=2 cellspacing=1 bgcolor='#999999'>");
	OutputClass::rawoutput("<tr class='trhead'><td>$ops</td><td>$name</td><td>$cost</td><td>$defense</td><td>$level</td></tr>");
	$number=db_num_rows($result);
	for ($i=0;$i<$number;$i++){
		$row = db_fetch_assoc($result);
		OutputClass::rawoutput("<tr class='".($i%2?"trdark":"trlight")."'>");
		OutputClass::rawoutput("<td>[<a href='armoreditor.php?op=edit&id={$row['armorid']}&level=$armorlevel'>$edit</a>|<a href='armoreditor.php?op=del&id={$row['armorid']}&level=$armorlevel' onClick='return confirm(\"$delconfirm\");'>$del</a>]</td>");
		OutputClass::addnav("","armoreditor.php?op=edit&id={$row['armorid']}&level=$armorlevel");
		OutputClass::addnav("","armoreditor.php?op=del&id={$row['armorid']}&level=$armorlevel");
		OutputClass::rawoutput("<td>");
		OutputClass::output_notl($row['armorname']);
		OutputClass::rawoutput("</td><td>");
		OutputClass::output_notl($row['value']);
		OutputClass::rawoutput("</td><td>");
		OutputClass::output_notl($row['defense']);
		OutputClass::rawoutput("</td><td>");
		OutputClass::output_notl($row['level']);
		OutputClass::rawoutput("</td>");
		OutputClass::rawoutput("</tr>");
	}
	OutputClass::rawoutput("</table>");
}
PageParts::page_footer();
?>