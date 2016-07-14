<?php
// addnews ready
// translator ready
// mail ready
define("ALLOW_ANONYMOUS",true);
require_once("common.php");
require_once("lib/http.php");
require_once("lib/villagenav.php");

Translator::tlschema("list");

PageParts::page_header("List Warriors");
if ($session['user']['loggedin']) {
	GameDateTime::checkday();
	if ($session['user']['alive']) {
		villagenav();
	} else {
		OutputClass::addnav("Return to the Graveyard", "graveyard.php");
	}
	OutputClass::addnav("Currently Online","list.php");
	if ($session['user']['clanid']>0){
		OutputClass::addnav("Online Clan Members","list.php?op=clan");
		if ($session['user']['alive']) {
			OutputClass::addnav("Clan Hall","clan.php");
		}
	}
}else{
	OutputClass::addnav("Login Screen","index.php");
	OutputClass::addnav("Currently Online","list.php");
}

$playersperpage=50;

$sql = "SELECT count(acctid) AS c FROM " . db_prefix("accounts") . " WHERE locked=0";
$result = db_query($sql);
$row = db_fetch_assoc($result);
$totalplayers = $row['c'];

$op = Http::httpget('op');
$page = Http::httpget('page');
$search = "";
$limit = "";

if ($op=="search"){
	$search="%";
	$n = httppost('name');
	for ($x=0;$x<strlen($n);$x++){
		$search .= substr($n,$x,1)."%";
	}
	$search=" AND name LIKE '".addslashes($search)."' ";
}else{
	$pageoffset = (int)$page;
	if ($pageoffset>0) $pageoffset--;
	$pageoffset*=$playersperpage;
	$from = $pageoffset+1;
	$to = min($pageoffset+$playersperpage,$totalplayers);

	$limit=" LIMIT $pageoffset,$playersperpage ";
}
OutputClass::addnav("Pages");
for ($i=0;$i<$totalplayers;$i+=$playersperpage){
	$pnum = $i/$playersperpage+1;
	if ($page == $pnum) {
		OutputClass::addnav(array(" ?`b`#Page %s`0 (%s-%s)`b", $pnum, $i+1, min($i+$playersperpage,$totalplayers)), "list.php?page=$pnum");
	} else {
		OutputClass::addnav(array(" ?Page %s (%s-%s)", $pnum, $i+1, min($i+$playersperpage,$totalplayers)), "list.php?page=$pnum");
	}
}

// Order the list by level, dragonkills, name so that the ordering is total!
// Without this, some users would show up on multiple pages and some users
// wouldn't show up
if ($page=="" && $op==""){
	$title = Translator::translate_inline("Warriors Currently Online");
	$sql = "SELECT acctid,name,login,alive,location,race,sex,level,laston,loggedin,lastip,uniqueid FROM " . db_prefix("accounts") . " WHERE locked=0 AND loggedin=1 AND laston>'".date("Y-m-d H:i:s",strtotime("-".Settings::getsetting("LOGINTIMEOUT",900)." seconds"))."' ORDER BY level DESC, dragonkills DESC, login ASC";
	$result = db_query_cached($sql,"list.php-warsonline");
}elseif($op=='clan'){
	$title = Translator::translate_inline("Clan Members Online");
	$sql = "SELECT acctid,name,login,alive,location,race,sex,level,laston,loggedin,lastip,uniqueid FROM " . db_prefix("accounts") . " WHERE locked=0 AND loggedin=1 AND laston>'".date("Y-m-d H:i:s",strtotime("-".Settings::getsetting("LOGINTIMEOUT",900)." seconds"))."' AND clanid='{$session['user']['clanid']}' ORDER BY level DESC, dragonkills DESC, login ASC";
	$result = db_query($sql);
}else{
	if ($totalplayers > $playersperpage && $op != "search") {
		$title = sprintf_translate("Warriors of the realm (Page %s: %s-%s of %s)", ($pageoffset/$playersperpage+1), $from, $to, $totalplayers);
	} else {
		$title = sprintf_translate("Warriors of the realm");
	}
	OutputClass::rawoutput(tlbutton_clear());
	$sql = "SELECT acctid,name,login,alive,hitpoints,location,race,sex,level,laston,loggedin,lastip,uniqueid FROM " . db_prefix("accounts") . " WHERE locked=0 $search ORDER BY level DESC, dragonkills DESC, login ASC $limit";
	$result = db_query($sql);
}
if ($session['user']['loggedin']){
	$search = Translator::translate_inline("Search by name: ");
	$search2 = Translator::translate_inline("Search");

	OutputClass::rawoutput("<form action='list.php?op=search' method='POST'>$search<input name='name'><input type='submit' class='button' value='$search2'></form>");
	OutputClass::addnav("","list.php?op=search");
}

$max = db_num_rows($result);
if ($max>Settings::getsetting("maxlistsize", 100)) {
	OutputClass::output("`\$Too many names match that search.  Showing only the first %s.`0`n", Settings::getsetting("maxlistsize", 100));
	$max = Settings::getsetting("maxlistsize", 100);
}

if ($page=="" && $op==""){
	$title .= sprintf_translate(" (%s warriors)", $max);
}
OutputClass::output_notl("`c`b".$title."`b");

$alive = Translator::translate_inline("Alive");
$level = Translator::translate_inline("Level");
$name = Translator::translate_inline("Name");
$loc = Translator::translate_inline("Location");
$race = Translator::translate_inline("Race");
$sex = Translator::translate_inline("Sex");
$last = Translator::translate_inline("Last On");

OutputClass::rawoutput("<table border=0 cellpadding=2 cellspacing=1 bgcolor='#999999'>",true);
OutputClass::rawoutput("<tr class='trhead'><td>$alive</td><td>$level</td><td>$name</td><td>$loc</td><td>$race</td><td>$sex</td><td>$last</tr>");
$writemail = Translator::translate_inline("Write Mail");
$alive = Translator::translate_inline("`1Yes`0");
$dead = Translator::translate_inline("`4No`0");
$unconscious = Translator::translate_inline("`6Unconscious`0");
for($i=0;$i<$max;$i++){
	$row = db_fetch_assoc($result);
	OutputClass::rawoutput("<tr class='".($i%2?"trdark":"trlight")."'><td>",true);
	if ($row['alive'] == true) {
		$a = $alive;
	} else if ($row['hitpoints'] > 0) {
		$a = $unconscious;
	} else {
		$a = $dead;
	}
	//$a = Translator::translate_inline($row['alive']?"`1Yes`0":"`4No`0");
	OutputClass::output_notl("%s", $a);
	OutputClass::rawoutput("</td><td>");
	OutputClass::output_notl("`^%s`0", $row['level']);
	OutputClass::rawoutput("</td><td>");
	if ($session['user']['loggedin']) {
		OutputClass::rawoutput("<a href=\"mail.php?op=write&to=".rawurlencode($row['login'])."\" target=\"_blank\" onClick=\"".popup("mail.php?op=write&to=".rawurlencode($row['login'])."").";return false;\">");
		OutputClass::rawoutput("<img src='images/newscroll.GIF' width='16' height='16' alt='$writemail' border='0'></a>");
		OutputClass::rawoutput("<a href='bio.php?char=".$row['acctid']."'>");
		OutputClass::addnav("","bio.php?char=".$row['acctid']."");
	}
	OutputClass::output_notl("`&%s`0", $row['name']);
	if ($session['user']['loggedin'])
		OutputClass::rawoutput("</a>");
	OutputClass::rawoutput("</td><td>");
	$loggedin=(date("U") - strtotime($row['laston']) < Settings::getsetting("LOGINTIMEOUT",900) && $row['loggedin']);
	OutputClass::output_notl("`&%s`0", $row['location']);
	if ($loggedin) {
		$online = Translator::translate_inline("`#(Online)");
		OutputClass::output_notl("%s", $online);
	}
	OutputClass::rawoutput("</td><td>");
	if (!$row['race']) $row['race'] = RACE_UNKNOWN;
	Translator::tlschema("race");
	OutputClass::output($row['race']);
	Translator::tlschema();
	OutputClass::rawoutput("</td><td>");
	$sex = Translator::translate_inline($row['sex']?"`%Female`0":"`!Male`0");
	OutputClass::output_notl("%s", $sex);
	OutputClass::rawoutput("</td><td>");
	$laston = relativedate($row['laston']);
	OutputClass::output_notl("%s", $laston);
	OutputClass::rawoutput("</td></tr>");
}
OutputClass::rawoutput("</table>");
OutputClass::output_notl("`c");
PageParts::page_footer();
?>