<?php
// translator ready
// addnews ready
// mail ready
require_once("common.php");
require_once("lib/commentary.php");
require_once("lib/sanitize.php");
require_once("lib/http.php");

Translator::tlschema("moderate");

Commentary::addcommentary();

SuAccess::check_su_access(SU_EDIT_COMMENTS);

require_once("lib/superusernav.php");
SuperUserNavClass::superusernav();

OutputClass::addnav("Other");
OutputClass::addnav("Commentary Overview","moderate.php");
OutputClass::addnav("Reset Seen Comments","moderate.php?seen=".rawurlencode(date("Y-m-d H:i:s")));
OutputClass::addnav("B?Player Bios","bios.php");
if ($session['user']['superuser'] & SU_AUDIT_MODERATION){
	OutputClass::addnav("Audit Moderation","moderate.php?op=audit");
}
OutputClass::addnav("Review by Moderator");
OutputClass::addnav("Commentary");
OutputClass::addnav("Sections");
OutputClass::addnav("Modules");
OutputClass::addnav("Clan Halls");

$op = Http::httpget("op");
if ($op=="commentdelete"){
	$comment = Http::httppost('comment');
	if (Http::httppost('delnban')>''){
		$sql = "SELECT DISTINCT uniqueid,author FROM " . db_prefix("commentary") . " INNER JOIN " . db_prefix("accounts") . " ON acctid=author WHERE commentid IN ('" . join("','",array_keys($comment)) . "')";
		$result = db_query($sql);
		$untildate = date("Y-m-d H:i:s",strtotime("+3 days"));
		$reason = Http::httppost("reason");
		$reason0 = Http::httppost("reason0");
		$default = "Banned for comments you posted.";
		if ($reason0 != $reason && $reason0 != $default) $reason = $reason0;
		if ($reason=="") $reason = $default;
		while ($row = db_fetch_assoc($result)){
			$sql = "SELECT * FROM " . db_prefix("bans") . " WHERE uniqueid = '{$row['uniqueid']}'";
			$result2 = db_query($sql);
			$sql = "INSERT INTO " . db_prefix("bans") . " (uniqueid,banexpire,banreason,banner) VALUES ('{$row['uniqueid']}','$untildate','$reason','".addslashes($session['user']['name'])."')";
			$sql2 = "UPDATE " . db_prefix("accounts") . " SET loggedin=0 WHERE acctid={$row['author']}";
			if (db_num_rows($result2)>0){
				$row2 = db_fetch_assoc($result2);
				if ($row2['banexpire'] < $untildate){
					//don't enter a new ban if a longer lasting one is
					//already here.
					db_query($sql);
					db_query($sql2);
				}
			}else{
				db_query($sql);
				db_query($sql2);
			}
		}
	}
	if (!isset($comment) || !is_array($comment)) $comment = array();
	$sql = "SELECT " .
		db_prefix("commentary").".*,".db_prefix("accounts").".name,".
		db_prefix("accounts").".login, ".db_prefix("accounts").".clanrank,".
		db_prefix("clans").".clanshort FROM ".db_prefix("commentary").
		" INNER JOIN ".db_prefix("accounts")." ON ".
		db_prefix("accounts").".acctid = " . db_prefix("commentary").
		".author LEFT JOIN ".db_prefix("clans")." ON ".
		db_prefix("clans").".clanid=".db_prefix("accounts").
		".clanid WHERE commentid IN ('".join("','",array_keys($comment))."')";
	$result = db_query($sql);
	$invalsections = array();
	while ($row = db_fetch_assoc($result)){
		$sql = "INSERT LOW_PRIORITY INTO ".db_prefix("moderatedcomments").
			" (moderator,moddate,comment) VALUES ('{$session['user']['acctid']}','".date("Y-m-d H:i:s")."','".addslashes(serialize($row))."')";
		db_query($sql);
		$invalsections[$row['section']] = 1;
	}
	$sql = "DELETE FROM " . db_prefix("commentary") . " WHERE commentid IN ('" . join("','",array_keys($comment)) . "')";
	db_query($sql);
	$return = Http::httpget('return');
	$return = SanitizeClass::cmd_sanitize($return);
	$return = substr($return,strrpos($return,"/")+1);
	if (strpos($return,"?")===false && strpos($return,"&")!==false){
		$x = strpos($return,"&");
		$return = substr($return,0,$x-1)."?".substr($return,$x+1);
	}
	foreach($invalsections as $key=>$dummy) {
		DataCache::invalidatedatacache("comments-$key");
	}
	//update moderation cache
	DataCache::invalidatedatacache("comments-or11");
	RedirectClass::redirect($return);
}

$seen = Http::httpget("seen");
if ($seen>""){
	$session['user']['recentcomments']=$seen;
}

PageParts::page_header("Comment Moderation");


if ($op==""){
	$area = Http::httpget('area');
	$link = "moderate.php" . ($area ? "?area=$area" : "");
	$refresh = Translator::translate_inline("Refresh");
	OutputClass::rawoutput("<form action='$link' method='POST'>");
	OutputClass::rawoutput("<input type='submit' class='button' value='$refresh'>");
	OutputClass::rawoutput("</form>");
	OutputClass::addnav("", "$link");
	if ($area==""){
		Commentary::talkform("X","says");
		Commentary::commentdisplay("", "' or '1'='1","X",100);
	}else{
		Commentary::commentdisplay("", $area,"X",100);
		Commentary::talkform($area,"says");
	}
}elseif ($op=="audit"){
	$subop = Http::httpget("subop");
	if ($subop=="undelete") {
		$unkeys = Http::httppost("mod");
		if ($unkeys && is_array($unkeys)) {
			$sql = "SELECT * FROM ".db_prefix("moderatedcomments")." WHERE modid IN ('".join("','",array_keys($unkeys))."')";
			$result = db_query($sql);
			while ($row = db_fetch_assoc($result)){
				$comment = unserialize($row['comment']);
				$id = addslashes($comment['commentid']);
				$postdate = addslashes($comment['postdate']);
				$section = addslashes($comment['section']);
				$author = addslashes($comment['author']);
				$comment = addslashes($comment['comment']);
				$sql = "INSERT LOW_PRIORITY INTO ".db_prefix("commentary")." (commentid,postdate,section,author,comment) VALUES ('$id','$postdate','$section','$author','$comment')";
				db_query($sql);
				DataCache::invalidatedatacache("comments-$section");
			}
			$sql = "DELETE FROM ".db_prefix("moderatedcomments")." WHERE modid IN ('".join("','",array_keys($unkeys))."')";
			db_query($sql);
		} else {
			OutputClass::output("No items selected to undelete -- Please try again`n`n");
		}
	}
	$sql = "SELECT DISTINCT acctid, name FROM ".db_prefix("accounts").
		" INNER JOIN ".db_prefix("moderatedcomments").
		" ON acctid=moderator ORDER BY name";
	$result = db_query($sql);
	OutputClass::addnav("Commentary");
	OutputClass::addnav("Sections");
	OutputClass::addnav("Modules");
	OutputClass::addnav("Clan Halls");
	OutputClass::addnav("Review by Moderator");
	Translator::tlschema("notranslate");
	while ($row = db_fetch_assoc($result)){
		OutputClass::addnav(" ?".$row['name'],"moderate.php?op=audit&moderator={$row['acctid']}");
	}
	Translator::tlschema();
	OutputClass::addnav("Commentary");
	OutputClass::output("`c`bComment Auditing`b`c");
	$ops = Translator::translate_inline("Ops");
	$mod = Translator::translate_inline("Moderator");
	$when = Translator::translate_inline("When");
	$com = Translator::translate_inline("Comment");
	$unmod = Translator::translate_inline("Unmoderate");
	OutputClass::rawoutput("<form action='moderate.php?op=audit&subop=undelete' method='POST'>");
	OutputClass::addnav("","moderate.php?op=audit&subop=undelete");
	OutputClass::rawoutput("<table border='0' cellpadding='2' cellspacing='0'>");
	OutputClass::rawoutput("<tr class='trhead'><td>$ops</td><td>$mod</td><td>$when</td><td>$com</td></tr>");
	$limit = "75";
	$where = "1=1 ";
	$moderator = Http::httpget("moderator");
	if ($moderator>"") $where.="AND moderator=$moderator ";
	$sql = "SELECT name, ".db_prefix("moderatedcomments").
		".* FROM ".db_prefix("moderatedcomments")." LEFT JOIN ".
		db_prefix("accounts").
		" ON acctid=moderator WHERE $where ORDER BY moddate DESC LIMIT $limit";
	$result = db_query($sql);
	$i=0;
	$clanrankcolors=array("`!","`#","`^","`&");
	while ($row = db_fetch_assoc($result)){
		$i++;
		OutputClass::rawoutput("<tr class='".($i%2?'trlight':'trdark')."'>");
		OutputClass::rawoutput("<td><input type='checkbox' name='mod[{$row['modid']}]' value='1'></td>");
		OutputClass::rawoutput("<td>");
		OutputClass::output_notl("%s", $row['name']);
		OutputClass::rawoutput("</td>");
		OutputClass::rawoutput("<td>");
		OutputClass::output_notl("%s", $row['moddate']);
		OutputClass::rawoutput("</td>");
		OutputClass::rawoutput("<td>");
		$comment = unserialize($row['comment']);
		OutputClass::output_notl("`0(%s)", $comment['section']);

		if ($comment['clanrank']>0)
			OutputClass::output_notl("%s<%s%s>`0", $clanrankcolors[ceil($comment['clanrank']/10)],
					$comment['clanshort'],
					$clanrankcolors[ceil($comment['clanrank']/10)]);
		OutputClass::output_notl("%s", $comment['name']);
		OutputClass::output_notl("-");
		OutputClass::output_notl("%s", SanitizeClass::comment_sanitize($comment['comment']));
		OutputClass::rawoutput("</td>");
		OutputClass::rawoutput("</tr>");
	}
	OutputClass::rawoutput("</table>");
	OutputClass::rawoutput("<input type='submit' class='button' value='$unmod'>");
	OutputClass::rawoutput("</form>");
}


OutputClass::addnav("Sections");
Translator::tlschema("commentary");
$vname = Settings::getsetting("villagename", LOCATION_FIELDS);
OutputClass::addnav(array("%s Square", $vname), "moderate.php?area=village");

if ($session['user']['superuser'] & ~SU_DOESNT_GIVE_GROTTO) {
	OutputClass::addnav("Grotto","moderate.php?area=superuser");
}

OutputClass::addnav("Land of the Shades","moderate.php?area=shade");
OutputClass::addnav("Grassy Field","moderate.php?area=grassyfield");

$iname = Settings::getsetting("innname", LOCATION_INN);
// the inn name is a proper name and shouldn't be translated.
Translator::tlschema("notranslate");
OutputClass::addnav($iname,"moderate.php?area=inn");
Translator::tlschema();

OutputClass::addnav("MotD","moderate.php?area=motd");
OutputClass::addnav("Veterans Club","moderate.php?area=veterans");
OutputClass::addnav("Hunter's Lodge","moderate.php?area=hunterlodge");
OutputClass::addnav("Gardens","moderate.php?area=gardens");
OutputClass::addnav("Clan Hall Waiting Area","moderate.php?area=waiting");

if (Settings::getsetting("betaperplayer", 1) == 1 && @file_exists("pavilion.php")) {
	OutputClass::addnav("Beta Pavilion","moderate.php?area=beta");
}
Translator::tlschema();

if ($session['user']['superuser'] & SU_MODERATE_CLANS){
	OutputClass::addnav("Clan Halls");
	$sql = "SELECT clanid,clanname,clanshort FROM " . db_prefix("clans") . " ORDER BY clanid";
	$result = db_query($sql);
	// these are proper names and shouldn't be translated.
	Translator::tlschema("notranslate");
	while ($row=db_fetch_assoc($result)){
		OutputClass::addnav(array("<%s> %s", $row['clanshort'], $row['clanname']),
				"moderate.php?area=clan-{$row['clanid']}");
	}
	Translator::tlschema();
} elseif ($session['user']['superuser'] & SU_EDIT_COMMENTS &&
		Settings::getsetting("officermoderate", 0)) {
	// the CLAN_OFFICER requirement was chosen so that moderators couldn't
	// just get accepted as a member to any random clan and then proceed to
	// wreak havoc.
	// although this isn't really a big deal on most servers, the choice was
	// made so that staff won't have to have another issue to take into
	// consideration when choosing moderators.  the issue is moot in most
	// cases, as players that are trusted with moderator powers are also
	// often trusted with at least the rank of officer in their respective
	// clans.
	if (($session['user']['clanid'] != 0) &&
			($session['user']['clanrank'] >= CLAN_OFFICER)) {
		OutputClass::addnav("Clan Halls");
		$sql = "SELECT clanid,clanname,clanshort FROM " . db_prefix("clans") . " WHERE clanid='" . $session['user']['clanid'] . "'";
		$result = db_query($sql);
		// these are proper names and shouldn't be translated.
		Translator::tlschema("notranslate");
		if ($row=db_fetch_assoc($result)){
			OutputClass::addnav(array("<%s> %s", $row['clanshort'], $row['clanname']),
					"moderate.php?area=clan-{$row['clanid']}");
		} else {
			OutputClass::debug ("There was an error while trying to access your clan.");
		}
		Translator::tlschema();
	}
}
OutputClass::addnav("Modules");
$mods = array();
$mods = Modules::modulehook("moderate", $mods);
reset($mods);

// These are already translated in the module.
Translator::tlschema("notranslate");
foreach ($mods as $area=>$name) {
	OutputClass::addnav($name, "moderate.php?area=$area");
}
Translator::tlschema();

PageParts::page_footer();
?>