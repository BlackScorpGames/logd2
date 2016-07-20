<?php
// addnews ready
// mail ready
// translator ready

// hilarious copy of mounts.php
require_once("common.php");
require_once("lib/http.php");
require_once("lib/showform.php");

SuAccess::check_su_access(SU_EDIT_MOUNTS);

Translator::tlschema("companions");

PageParts::page_header("Companion Editor");

require_once("lib/superusernav.php");
SuperUserNavClass::superusernav();

OutputClass::addnav("Companion Editor");
OutputClass::addnav("Add a companion","companions.php?op=add");

$op = Http::httpget('op');
$id = Http::httpget('id');
if ($op=="deactivate"){
	$sql = "UPDATE " . db_prefix("companions") . " SET companionactive=0 WHERE companionid='$id'";
	db_query($sql);
	$op="";
	Http::httpset("op", "");
	DataCache::invalidatedatacache("companionsdata-$id");
} elseif ($op=="activate"){
	$sql = "UPDATE " . db_prefix("companions") . " SET companionactive=1 WHERE companionid='$id'";
	db_query($sql);
	$op="";
	Http::httpset("op", "");
	DataCache::invalidatedatacache("companiondata-$id");
} elseif ($op=="del") {
	//drop the companion.
	$sql = "DELETE FROM " . db_prefix("companions") . " WHERE companionid='$id'";
	db_query($sql);
	module_delete_objprefs('companions', $id);
	$op = "";
	Http::httpset("op", "");
	DataCache::invalidatedatacache("companiondata-$id");
} elseif ($op=="take"){
	$sql = "SELECT * FROM " . db_prefix("companions") . " WHERE companionid='$id'";
	$result = db_query($sql);
	if ($row = db_fetch_assoc($result)) {
		$row['attack'] = $row['attack'] + $row['attackperlevel'] * $session['user']['level'];
		$row['defense'] = $row['defense'] + $row['defenseperlevel'] * $session['user']['level'];
		$row['maxhitpoints'] = $row['maxhitpoints'] + $row['maxhitpointsperlevel'] * $session['user']['level'];
		$row['hitpoints'] = $row['maxhitpoints'];
		$row = Modules::modulehook("alter-companion", $row);
		$row['abilities'] = @unserialize($row['abilities']);
		require_once("lib/buffs.php");
		Buffs::apply_companion($row['name'], $row);
		OutputClass::output("`\$Succesfully taken `^%s`\$ as companion.", $row['name']);
	}
	$op = "";
	Http::httpset("op", "");
} elseif ($op=="save"){
	$subop = Http::httpget("subop");
	if ($subop == "") {
		$companion = Http::httppost('companion');
		if ($companion) {
			if (!isset($companion['allowinshades'])) {
				$companion['allowinshades'] = 0;
			}
			if (!isset($companion['allowinpvp'])) {
				$companion['allowinpvp'] = 0;
			}
			if (!isset($companion['allowintrain'])) {
				$companion['allowintrain'] = 0;
			}
			if (!isset($companion['abilities']['fight'])) {
				$companion['abilities']['fight'] = false;
			}
			if (!isset($companion['abilities']['defend'])) {
				$companion['abilities']['defend'] = false;
			}
			if (!isset($companion['cannotdie'])) {
				$companion['cannotdie'] = false;
			}
			if (!isset($companion['cannotbehealed'])) {
				$companion['cannotbehealed'] = false;
			}
			$sql = "";
			$keys = "";
			$vals = "";
			$i = 0;
			while(list($key, $val) = each($companion)) {
				if (is_array($val)) $val = addslashes(serialize($val));
				$sql .= (($i > 0) ? ", " : "") . "$key='$val'";
				$keys .= (($i > 0) ? ", " : "") . "$key";
				$vals .= (($i > 0) ? ", " : "") . "'$val'";
				$i++;
			}
			if ($id>""){
				$sql="UPDATE " . db_prefix("companions") .
					" SET $sql WHERE companionid='$id'";
			}else{
				$sql="INSERT INTO " . db_prefix("companions") .
					" ($keys) VALUES ($vals)";
			}
			db_query($sql);
			DataCache::invalidatedatacache("companiondata-$id");
			if (db_affected_rows()>0){
				OutputClass::output("`^Companion saved!`0`n`n");
			}else{
//				if (strlen($sql) > 400) $sql = substr($sql,0,200)." ... ".substr($sql,strlen($sql)-200);
				OutputClass::output("`^Companion `\$not`^ saved: `\$%s`0`n`n", $sql);
			}
		}
	} elseif ($subop=="module") {
		// Save modules settings
		$module = Http::httpget("module");
		$post = Http::httpallpost();
		reset($post);
		while(list($key, $val) = each($post)) {
			set_module_objpref("companions", $id, $key, $val, $module);
		}
		OutputClass::output("`^Saved!`0`n");
	}
	if ($id) {
		$op="edit";
	} else {
		$op = "";
	}
	Http::httpset("op", $op);
}

if ($op==""){
	$sql = "SELECT * FROM " . db_prefix("companions") . " ORDER BY category, name";
	$result = db_query($sql);

	$ops = Translator::translate_inline("Ops");
	$name = Translator::translate_inline("Name");
	$cost = Translator::translate_inline("Cost");

	$edit = Translator::translate_inline("Edit");
	$del = Translator::translate_inline("Del");
	$take = Translator::translate_inline("Take");
	$deac = Translator::translate_inline("Deactivate");
	$act = Translator::translate_inline("Activate");

	OutputClass::rawoutput("<table border=0 cellpadding=2 cellspacing=1 bgcolor='#999999'>");
	OutputClass::rawoutput("<tr class='trhead'><td nowrap>$ops</td><td>$name</td><td>$cost</td></tr>");
	$cat = "";
	$count=0;

	while ($row=db_fetch_assoc($result)) {
		if ($cat!=$row['category']){
			OutputClass::rawoutput("<tr class='trlight'><td colspan='5'>");
			OutputClass::output("Category: %s", $row['category']);
			OutputClass::rawoutput("</td></tr>");
			$cat = $row['category'];
			$count=0;
		}
		if (isset($companions[$row['companionid']])) {
			$companions[$row['companionid']] = (int)$companions[$row['companionid']];
		} else {
			$companions[$row['companionid']] = 0;
		}
		OutputClass::rawoutput("<tr class='".($count%2?"trlight":"trdark")."'>");
		OutputClass::rawoutput("<td nowrap>[ <a href='companions.php?op=edit&id={$row['companionid']}'>$edit</a> |");
		OutputClass::addnav("","companions.php?op=edit&id={$row['companionid']}");
		if ($row['companionactive']){
			OutputClass::rawoutput("$del |");
		}else{
			$mconf = sprintf($conf, $companions[$row['companionid']]);
			OutputClass::rawoutput("<a href='companions.php?op=del&id={$row['companionid']}'>$del</a> |");
			OutputClass::addnav("","companions.php?op=del&id={$row['companionid']}");
		}
		if ($row['companionactive']) {
			OutputClass::rawoutput("<a href='companions.php?op=deactivate&id={$row['companionid']}'>$deac</a> | ");
			OutputClass::addnav("","companions.php?op=deactivate&id={$row['companionid']}");
		}else{
			OutputClass::rawoutput("<a href='companions.php?op=activate&id={$row['companionid']}'>$act</a> | ");
			OutputClass::addnav("","companions.php?op=activate&id={$row['companionid']}");
		}
		OutputClass::rawoutput("<a href='companions.php?op=take&id={$row['companionid']}'>$take</a> ]</td>");
		OutputClass::addnav("", "companions.php?op=take&id={$row['companionid']}");
		OutputClass::rawoutput("<td>");
		OutputClass::output_notl("`&%s`0", $row['name']);
		OutputClass::rawoutput("</td><td>");
		OutputClass::output("`%%s gems`0, `^%s gold`0",$row['companioncostgems'], $row['companioncostgold']);
		OutputClass::rawoutput("</td></tr>");
		$count++;
	}
	OutputClass::rawoutput("</table>");
	OutputClass::output("`nIf you wish to delete a companion, you have to deactivate it first.");
}elseif ($op=="add"){
	OutputClass::output("Add a companion:`n");
	OutputClass::addnav("Companion Editor Home","companions.php");
	companionform(array());
}elseif ($op=="edit"){
	OutputClass::addnav("Companion Editor Home","companions.php");
	$sql = "SELECT * FROM " . db_prefix("companions") . " WHERE companionid='$id'";
	$result = db_query_cached($sql, "companiondata-$id", 3600);
	if (db_num_rows($result)<=0){
		OutputClass::output("`iThis companion was not found.`i");
	}else{
		OutputClass::addnav("Companion properties", "companions.php?op=edit&id=$id");
		module_editor_navs("prefs-companions", "companions.php?op=edit&subop=module&id=$id&module=");
		$subop=Http::httpget("subop");
		if ($subop=="module") {
			$module = Http::httpget("module");
			OutputClass::rawoutput("<form action='companions.php?op=save&subop=module&id=$id&module=$module' method='POST'>");
			module_objpref_edit("companions", $module, $id);
			OutputClass::rawoutput("</form>");
			OutputClass::addnav("", "companions.php?op=save&subop=module&id=$id&module=$module");
		} else {
			OutputClass::output("Companion Editor:`n");
			$row = db_fetch_assoc($result);
			$row['abilities'] = @unserialize($row['abilities']);
			companionform($row);
		}
	}
}

function companionform($companion){
	// Let's sanitize the data
	if (!isset($companion['companionactive'])) $companion['companionactive'] = "";
	if (!isset($companion['name'])) $companion['name'] = "";
	if (!isset($companion['companionid'])) $companion['companionid'] = "";
	if (!isset($companion['description'])) $companion['description'] = "";
	if (!isset($companion['dyingtext'])) $companion['dyingtext'] = "";
	if (!isset($companion['jointext'])) $companion['jointext'] = "";
	if (!isset($companion['category'])) $companion['category'] = "";
	if (!isset($companion['companionlocation'])) $companion['companionlocation']  = 'all';
	if (!isset($companion['companioncostdks'])) $companion['companioncostdks']  = 0;

	if (!isset($companion['companioncostgems'])) $companion['companioncostgems']  = 0;
	if (!isset($companion['companioncostgold'])) $companion['companioncostgold']  = 0;

	if (!isset($companion['attack'])) $companion['attack'] = "";
	if (!isset($companion['attackperlevel'])) $companion['attackperlevel'] = "";
	if (!isset($companion['defense'])) $companion['defense'] = "";
	if (!isset($companion['defenseperlevel'])) $companion['defenseperlevel'] = "";
	if (!isset($companion['hitpoints'])) $companion['hitpoints'] = "";
	if (!isset($companion['maxhitpoints'])) $companion['maxhitpoints'] = "";
	if (!isset($companion['maxhitpointsperlevel'])) $companion['maxhitpointsperlevel'] = "";

	if (!isset($companion['abilities']['fight'])) $companion['abilities']['fight'] = 0;
	if (!isset($companion['abilities']['defend'])) $companion['abilities']['defend'] =  0;
	if (!isset($companion['abilities']['heal'])) $companion['abilities']['heal'] =  0;
	if (!isset($companion['abilities']['magic'])) $companion['abilities']['magic'] =  0;

	if (!isset($companion['cannotdie'])) $companion['cannotdie'] = 0;
	if (!isset($companion['cannotbehealed'])) $companion['cannotbehealed'] = 1;
	if (!isset($companion['allowinshades'])) $companion['allowinshades'] = 0;
	if (!isset($companion['allowinpvp'])) $companion['allowinpvp'] = 0;
	if (!isset($companion['allowintrain'])) $companion['allowintrain'] = 0;

	OutputClass::rawoutput("<form action='companions.php?op=save&id={$companion['companionid']}' method='POST'>");
	OutputClass::rawoutput("<input type='hidden' name='companion[companionactive]' value=\"".$companion['companionactive']."\">");
	OutputClass::addnav("","companions.php?op=save&id={$companion['companionid']}");
	OutputClass::rawoutput("<table width='100%'>");
	OutputClass::rawoutput("<tr><td nowrap>");
	OutputClass::output("Companion Name:");
	OutputClass::rawoutput("</td><td><input name='companion[name]' value=\"".htmlentities($companion['name'], ENT_COMPAT, Settings::getsetting("charset", "ISO-8859-1"))."\" maxlength='50'></td></tr>");
	OutputClass::rawoutput("<tr><td nowrap>");
	OutputClass::output("Companion Dyingtext:");
	OutputClass::rawoutput("</td><td><input name='companion[dyingtext]' value=\"".htmlentities($companion['dyingtext'], ENT_COMPAT, Settings::getsetting("charset", "ISO-8859-1"))."\"></td></tr>");
	OutputClass::rawoutput("<tr><td nowrap>");
	OutputClass::output("Companion Description:");
	OutputClass::rawoutput("</td><td><textarea cols='25' rows='5' name='companion[description]'>".htmlentities($companion['description'], ENT_COMPAT, Settings::getsetting("charset", "ISO-8859-1"))."</textarea></td></tr>");
	OutputClass::rawoutput("<tr><td nowrap>");
	OutputClass::output("Companion join text:");
	OutputClass::rawoutput("</td><td><textarea cols='25' rows='5' name='companion[jointext]'>".htmlentities($companion['jointext'], ENT_COMPAT, Settings::getsetting("charset", "ISO-8859-1"))."</textarea></td></tr>");
	OutputClass::rawoutput("<tr><td nowrap>");
	OutputClass::output("Companion Category:");
	OutputClass::rawoutput("</td><td><input name='companion[category]' value=\"".htmlentities($companion['category'], ENT_COMPAT, Settings::getsetting("charset", "ISO-8859-1"))."\" maxlength='50'></td></tr>");
	OutputClass::rawoutput("<tr><td nowrap>");
	OutputClass::output("Companion Availability:");
	OutputClass::rawoutput("</td><td nowrap>");
	// Run a Modules::modulehook to find out where camps are located.  By default
	// they are located in 'Degolburg' (ie, getgamesetting('villagename'));
	// Some later module can remove them however.
	$vname = Settings::getsetting('villagename', LOCATION_FIELDS);
	$locs = array($vname => Translator::sprintf_translate("The Village of %s", $vname));
	$locs = Modules::modulehook("camplocs", $locs);
	$locs['all'] = Translator::translate_inline("Everywhere");
	ksort($locs);
	reset($locs);
	OutputClass::rawoutput("<select name='companion[companionlocation]'>");
	foreach($locs as $loc=>$name) {
		OutputClass::rawoutput("<option value='$loc'".($companion['companionlocation']==$loc?" selected":"").">$name</option>");
	}

	OutputClass::rawoutput("<tr><td nowrap>");
	OutputClass::output("Maxhitpoints / Bonus per level:");
	OutputClass::rawoutput("</td><td><input name='companion[maxhitpoints]' value=\"".htmlentities($companion['maxhitpoints'], ENT_COMPAT, Settings::getsetting("charset", "ISO-8859-1"))."\"> / <input name='companion[maxhitpointsperlevel]' value=\"".htmlentities($companion['maxhitpointsperlevel'], ENT_COMPAT, Settings::getsetting("charset", "ISO-8859-1"))."\"></td></tr>");
	OutputClass::rawoutput("<tr><td nowrap>");
	OutputClass::output("Attack / Bonus per level:");
	OutputClass::rawoutput("</td><td><input name='companion[attack]' value=\"".htmlentities($companion['attack'], ENT_COMPAT, Settings::getsetting("charset", "ISO-8859-1"))."\"> / <input name='companion[attackperlevel]' value=\"".htmlentities($companion['attackperlevel'], ENT_COMPAT, Settings::getsetting("charset", "ISO-8859-1"))."\"></td></tr>");
	OutputClass::rawoutput("<tr><td nowrap>");
	OutputClass::output("Defense / Bonus per level:");
	OutputClass::rawoutput("</td><td><input name='companion[defense]' value=\"".htmlentities($companion['defense'], ENT_COMPAT, Settings::getsetting("charset", "ISO-8859-1"))."\"> / <input name='companion[defenseperlevel]' value=\"".htmlentities($companion['defenseperlevel'], ENT_COMPAT, Settings::getsetting("charset", "ISO-8859-1"))."\"></td></tr>");

	OutputClass::rawoutput("<tr><td nowrap>");
	OutputClass::output("Fighter?:");
	OutputClass::rawoutput("</td><td><input id='fighter' type='checkbox' name='companion[abilities][fight]' value='1'".($companion['abilities']['fight']==true?" checked":"")." onClick='document.getElementById(\"defender\").disabled=document.getElementById(\"fighter\").checked; if(document.getElementById(\"defender\").disabled==true) document.getElementById(\"defender\").checked=false;'></td></tr>");
	OutputClass::rawoutput("<tr><td nowrap>");
	OutputClass::output("Defender?:");
	OutputClass::rawoutput("</td><td><input id='defender' type='checkbox' name='companion[abilities][defend]' value='1'".($companion['abilities']['defend']==true?" checked":"")." onClick='document.getElementById(\"fighter\").disabled=document.getElementById(\"defender\").checked; if(document.getElementById(\"fighter\").disabled==true) document.getElementById(\"fighter\").checked=false;'></td></tr>");
	OutputClass::rawoutput("<tr><td nowrap>");
	OutputClass::output("Healer level:");
	OutputClass::rawoutput("</td><td valign='top'><select name='companion[abilities][heal]'>");
	for($i=0;$i<=30;$i++) {
		OutputClass::rawoutput("<option value='$i'".($companion['abilities']['heal']==$i?" selected":"").">$i</option>");
	}
	OutputClass::rawoutput("</select></td></tr>");
	OutputClass::rawoutput("<tr><td colspan='2'>");
	OutputClass::output("`iThis value determines the maximum amount of HP healed per round`i");
	OutputClass::rawoutput("</td></tr>");
	OutputClass::rawoutput("<tr><td nowrap>");
	OutputClass::output("Magician?:");
	OutputClass::rawoutput("</td><td valign='top'><select name='companion[abilities][magic]'>");
	for($i=0;$i<=30;$i++) {
		OutputClass::rawoutput("<option value='$i'".($companion['abilities']['magic']==$i?" selected":"").">$i</option>");
	}
	OutputClass::rawoutput("</select></td></tr>");
	OutputClass::rawoutput("<tr><td colspan='2'>");
	OutputClass::output("`iThis value determines the maximum amount of damage caused per round`i");
	OutputClass::rawoutput("</td></tr>");

	OutputClass::rawoutput("<tr><td nowrap>");
	OutputClass::output("Companion cannot die:");
	OutputClass::rawoutput("</td><td><input type='checkbox' name='companion[cannotdie]' value='1'".($companion['cannotdie']==true?" checked":"")."></td></tr>");
	OutputClass::rawoutput("<tr><td nowrap>");
	OutputClass::output("Companion cannot be healed:");
	OutputClass::rawoutput("</td><td><input type='checkbox' name='companion[cannotbehealed]' value='1'".($companion['cannotbehealed']==true?" checked":"")."></td></tr>");
	OutputClass::rawoutput("<tr><td nowrap>");

	OutputClass::output("Companion Cost (DKs):");
	OutputClass::rawoutput("</td><td><input name='companion[companioncostdks]' value=\"".htmlentities((int)$companion['companioncostdks'], ENT_COMPAT, Settings::getsetting("charset", "ISO-8859-1"))."\"></td></tr>");
	OutputClass::rawoutput("<tr><td nowrap>");
	OutputClass::output("Companion Cost (Gems):");
	OutputClass::rawoutput("</td><td><input name='companion[companioncostgems]' value=\"".htmlentities((int)$companion['companioncostgems'], ENT_COMPAT, Settings::getsetting("charset", "ISO-8859-1"))."\"></td></tr>");
	OutputClass::rawoutput("<tr><td nowrap>");
	OutputClass::output("Companion Cost (Gold):");
	OutputClass::rawoutput("</td><td><input name='companion[companioncostgold]' value=\"".htmlentities((int)$companion['companioncostgold'], ENT_COMPAT, Settings::getsetting("charset", "ISO-8859-1"))."\"></td></tr>");
	OutputClass::rawoutput("<tr><td nowrap>");
	OutputClass::output("Allow in shades:");
	OutputClass::rawoutput("</td><td><input type='checkbox' name='companion[allowinshades]' value='1'".($companion['allowinshades']==true?" checked":"")."></td></tr>");
	OutputClass::rawoutput("<tr><td nowrap>");
	OutputClass::output("Allow in PvP:");
	OutputClass::rawoutput("</td><td><input type='checkbox' name='companion[allowinpvp]' value='1'".($companion['allowinpvp']==true?" checked":"")."></td></tr>");
	OutputClass::rawoutput("<tr><td nowrap>");
	OutputClass::output("Allow in train:");
	OutputClass::rawoutput("</td><td><input type='checkbox' name='companion[allowintrain]' value='1'".($companion['allowintrain']==true?" checked":"")."></td></tr>");
	OutputClass::rawoutput("</table>");
	$save = Translator::translate_inline("Save");
	OutputClass::rawoutput("<input type='submit' class='button' value='$save'></form>");
}

PageParts::page_footer();
?>
