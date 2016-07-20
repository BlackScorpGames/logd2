<?php
// translator ready
// addnews ready
// mail ready
require_once("common.php");
require_once("lib/http.php");

SuAccess::check_su_access(SU_EDIT_CREATURES);

Translator::tlschema("creatures");

$creaturestats = array();

require_once 'lib/creatures.php';
for ($i=1;$i<18;$i++){
	$creaturestats[$i] = creature_stats($i);
}

PageParts::page_header("Creature Editor");

require_once("lib/superusernav.php");
SuperUserNavClass::superusernav();

$op = Http::httpget("op");
$subop = Http::httpget("subop");
if ($op == "save"){
	$forest = (int)(Http::httppost('forest'));
	$grave = (int)(Http::httppost('graveyard'));
	$id = Http::httppost('creatureid');
	if (!$id) $id = Http::httpget("creatureid");
	if ($subop == "") {
		$post = Http::httpallpost();
		$lev = (int)Http::httppost('creaturelevel');
		if ($id){
			$sql = "";
			reset($post);
			while (list($key,$val)=each($post)){
				if (substr($key,0,8)=="creature") $sql.="$key = '$val', ";
			}
			reset($creaturestats[$lev]);
			while (list($key,$val)=each($creaturestats[$lev])){
				if ( $key!="creaturelevel" && substr($key,0,8)=="creature"){
					$sql.="$key = \"".addslashes($val)."\", ";
				}
			}
			$sql.=" forest='$forest', ";
			$sql.=" graveyard='$grave' ";
			$sql="UPDATE " . db_prefix("creatures") . " SET " . $sql . " WHERE creatureid='$id'";
			db_query($sql) or OutputClass::output("`\$".db_error(LINK)."`0`n`#$sql`0`n");
		}else{
			$cols = array();
			$vals = array();

			reset($post);
			while (list($key,$val)=each($post)){
				if (substr($key,0,8)=="creature") {
					array_push($cols,$key);
					array_push($vals,$val);
				}
			}
			array_push($cols, "forest");
			array_push($vals, $forest);
			array_push($cols, "graveyard");
			array_push($vals, $grave);
			reset($creaturestats[$lev]);
			while (list($key,$val)=each($creaturestats[$lev])){
				if ($key!="creaturelevel"&& substr($key,0,8)=="creature"){
					array_push($cols,$key);
					array_push($vals,$val);
				}
			}
			$sql="INSERT INTO " . db_prefix("creatures") . " (".join(",",$cols).",createdby) VALUES (\"".join("\",\"",$vals)."\",\"".addslashes($session['user']['login'])."\")";
			db_query($sql);
			$id = db_insert_id();
		}
		if (db_affected_rows()) {
			OutputClass::output("`^Creature saved!`0`n");
		} else {
			OutputClass::output("`^Creature `\$not`^ saved!`0`n");
		}
	} elseif ($subop == "module") {
		// Save module settings
		$module = Http::httpget("module");
		$post = Http::httpallpost();
		reset($post);
		while(list($key, $val) = each($post)) {
			Modules::set_module_objpref("creatures", $id, $key, $val, $module);
		}
		OutputClass::output("`^Saved!`0`n");
	}
	// Set the Http::httpget id so that we can do the editor once we save
	Http::httpset("creatureid", $id, true);
	// Set the Http::httpget op so we drop back into the editor
	Http::httpset("op", "edit");
}

$op = Http::httpget('op');
$id = Http::httpget('creatureid');
if ($op=="del"){
	$sql = "DELETE FROM " . db_prefix("creatures") . " WHERE creatureid = '$id'";
	db_query($sql);
	if (db_affected_rows()>0){
		OutputClass::output("Creature deleted`n`n");
	}else{
		OutputClass::output("Creature not deleted: %s", db_error(LINK));
	}
	$op="";
	Http::httpset('op', "");
}
if ($op=="" || $op=="search"){
	$level = Http::httpget("level");
	if (!$level) $level = 1;
	$q = Http::httppost("q");
	if ($q) {
		$where = "creaturename LIKE '%$q%' OR creatureweapon LIKE '%$q%' OR creaturelose LIKE '%$q%' OR createdby LIKE '%$q%'";
	} else {
		$where = "creaturelevel='$level'";
	}
	$sql = "SELECT * FROM " . db_prefix("creatures") . " WHERE $where ORDER BY creaturelevel,creaturename";
	$result = db_query($sql);
	// Search form
	$search = Translator::translate_inline("Search");
	OutputClass::rawoutput("<form action='creatures.php?op=search' method='POST'>");
	OutputClass::output("Search by field: ");
	OutputClass::rawoutput("<input name='q' id='q'>");
	OutputClass::rawoutput("<input type='submit' class='button' value='$search'>");
	OutputClass::rawoutput("</form>");
	OutputClass::rawoutput("<script language='JavaScript'>document.getElementById('q').focus();</script>",true);
	OutputClass::addnav("","creatures.php?op=search");

	OutputClass::addnav("Levels");
	$sql1 = "SELECT count(creatureid) AS n,creaturelevel FROM " . db_prefix("creatures") . " group by creaturelevel order by creaturelevel";
	$result1 = db_query($sql1);
	while ($row = db_fetch_assoc($result1)) {
		OutputClass::addnav(array("Level %s: (%s creatures)", $row['creaturelevel'], $row['n']),
				"creatures.php?level={$row['creaturelevel']}");
	}
	// There is no reason to allow players to add creatures to level 17 and 18.
	// Players aren't supposed to stay at level 15 at all.
	if ($level <= 16) {
		OutputClass::addnav("Edit");
		OutputClass::addnav("Add a creature","creatures.php?op=add&level=$level");
	}
	$opshead = Translator::translate_inline("Ops");
	$idhead = Translator::translate_inline("ID");
	$name = Translator::translate_inline("Name");
	$lev = Translator::translate_inline("Level");
	$weapon = Translator::translate_inline("Weapon");
	$winmsg = Translator::translate_inline("Win");
	$diemsg = Translator::translate_inline("Die");
	$author = Translator::translate_inline("Author");
	$edit = Translator::translate_inline("Edit");
	$confirm = Translator::translate_inline("Are you sure you wish to delete this creature?");
	$del = Translator::translate_inline("Del");

	OutputClass::rawoutput("<table border=0 cellpadding=2 cellspacing=1 bgcolor='#999999'>");
	OutputClass::rawoutput("<tr class='trhead'>");
	OutputClass::rawoutput("<td>$opshead</td><td>$idhead</td><td>$name</td><td>$lev</td><td>$weapon</td><td>$winmsg</td><td>$diemsg</td><td>$author</td></tr>");
	OutputClass::addnav("","creatures.php");
	$number=db_num_rows($result);
	for ($i=0;$i<$number;$i++){
		$row = db_fetch_assoc($result);
		OutputClass::rawoutput("<tr class='".($i%2==0?"trdark":"trlight")."'>", true);
		OutputClass::rawoutput("<td>[ <a href='creatures.php?op=edit&creatureid={$row['creatureid']}'>");
		OutputClass::output_notl("%s", $edit);
		OutputClass::rawoutput("</a> | <a href='creatures.php?op=del&creatureid={$row['creatureid']}&level={$row['creaturelevel']}' onClick='return confirm(\"$confirm\");'>");
		OutputClass::output_notl("%s", $del);
		OutputClass::rawoutput("</a> ]</td><td>");
		OutputClass::addnav("","creatures.php?op=edit&creatureid={$row['creatureid']}");
		OutputClass::addnav("","creatures.php?op=del&creatureid={$row['creatureid']}&level={$row['creaturelevel']}");
		OutputClass::output_notl("%s", $row['creatureid']);
		OutputClass::rawoutput("</td><td>");
		OutputClass::output_notl("%s", $row['creaturename']);
		OutputClass::rawoutput("</td><td>");
		OutputClass::output_notl("%s", $row['creaturelevel']);
		OutputClass::rawoutput("</td><td>");
		OutputClass::output_notl("%s", $row['creatureweapon']);
		OutputClass::rawoutput("</td><td>");
		OutputClass::output_notl("%s", $row['creaturewin']);
		OutputClass::rawoutput("</td><td>");
		OutputClass::output_notl("%s", $row['creaturelose']);
		OutputClass::rawoutput("</td><td>");
		OutputClass::output_notl("%s", $row['createdby']);
		OutputClass::rawoutput("</td></tr>");
	}
	OutputClass::rawoutput("</table>");
}else{
	$level = Http::httpget('level');
	if (!$level) $level = 1;
	if ($op=="edit" || $op=="add"){
		require_once("lib/showform.php");
		OutputClass::addnav("Edit");
		OutputClass::addnav("Creature properties", "creatures.php?op=edit&creatureid=$id");
		OutputClass::addnav("Add");
		OutputClass::addnav("Add Another Creature", "creatures.php?op=add&level=$level");
		module_editor_navs("prefs-creatures", "creatures.php?op=edit&subop=module&creatureid=$id&module=");
		if ($subop == "module") {
			$module = Http::httpget("module");
			OutputClass::rawoutput("<form action='creatures.php?op=save&subop=module&creatureid=$id&module=$module' method='POST'>");
			module_objpref_edit("creatures", $module, $id);
			OutputClass::rawoutput("</form>");
			OutputClass::addnav("", "creatures.php?op=save&subop=module&creatureid=$id&module=$module");
		} else {
			if ($op=="edit" && $id!=""){
				$sql = "SELECT * FROM " . db_prefix("creatures") . " WHERE creatureid=$id";
				$result = db_query($sql);
				if (db_num_rows($result)<>1){
					OutputClass::output("`4Error`0, that creature was not found!");
				}else{
					$row = db_fetch_assoc($result);
				}
				$level = $row['creaturelevel'];
			} else {
				$row = array("creatureid"=>0,"creaturelevel"=>$level);
			}
			$form = array(
				"Creature Properties,title",
				"creatureid"=>"Creature id,hidden",
				"creaturename"=>"Creature Name",
				"creatureweapon"=>"Weapon",
				"creaturewin"=>"Win Message (Displayed when the creature kills the player)",
				"creaturelose"=>"Death Message (Displayed when the creature is killed by the player)",
				// 18 to make a non-forest available monster
				// (ie, graveyard only)_
				"creaturelevel"=>"Level,range,1,18,1",
				"forest"=>"Creature is in forest?,bool",
				"graveyard"=>"Creature is in graveyard?,bool",
				"creatureaiscript"=>"Creature's A.I.,textarearesizeable",
			);
			OutputClass::rawoutput("<form action='creatures.php?op=save' method='POST'>");
			ShowFormClass::showform($form, $row);
			OutputClass::rawoutput("</form>");
			OutputClass::addnav("","creatures.php?op=save");
		}
	}else{
		$module = Http::httpget("module");
		OutputClass::rawoutput("<form action='mounts.php?op=save&subop=module&creatureid=$id&module=$module' method='POST'>");
		module_objpref_edit("creatures", $module, $id);
		OutputClass::rawoutput("</form>");
		OutputClass::addnav("", "creatures.php?op=save&subop=module&creatureid=$id&module=$module");
	}
	OutputClass::addnav("Navigation");
	OutputClass::addnav("Return to the creature editor","creatures.php?level=$level");
}
PageParts::page_footer();
?>