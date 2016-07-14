<?php
// translator ready
// addnews ready
// mail ready
define("ALLOW_ANONYMOUS",true);
require_once("common.php");
require_once("lib/http.php");
require_once("lib/villagenav.php");

Translator::tlschema("news");

Modules::modulehook("news-intercept",array());

if ((int)Settings::getsetting("expirecontent",180)>0){
	$sql = "DELETE FROM " . db_prefix("news") . " WHERE newsdate<'".date("Y-m-d H:i:s",strtotime("-".Settings::getsetting("expirecontent",180)." days"))."'";
	//echo $sql;
	db_query($sql);
}

if ($session['user']['loggedin']) GameDateTime::checkday();
$newsperpage=50;

$offset = (int)Http::httpget('offset');
$timestamp=strtotime((0-$offset)." days");
$sql = "SELECT count(newsid) AS c FROM " . db_prefix("news") . " WHERE newsdate='".date("Y-m-d",$timestamp)."'";
$result = db_query($sql);
$row = db_fetch_assoc($result);
$totaltoday=$row['c'];
$page = (int)Http::httpget('page');
if (!$page) $page=1;
$pageoffset = $page;
if ($pageoffset>0) $pageoffset--;
$pageoffset*=$newsperpage;
$sql = "SELECT * FROM " . db_prefix("news") . " WHERE newsdate='".date("Y-m-d",$timestamp)."' ORDER BY newsid DESC LIMIT $pageoffset,$newsperpage";
$result = db_query($sql);
PageParts::page_header("LoGD News");
$date=date("D, M j, Y",$timestamp);

$pagestr = "";
if ($totaltoday > $newsperpage) {
	$pagestr = sprintf_translate("(Items %s - %s of %s)", $pageoffset+1,
			min($pageoffset+$newsperpage,$totaltoday), $totaltoday);
}

$sql2 = "SELECT " . db_prefix("motd") . ".*,name AS motdauthorname FROM " . db_prefix("motd") . " LEFT JOIN " . db_prefix("accounts") . " ON " . db_prefix("accounts") . ".acctid = " . db_prefix("motd") . ".motdauthor ORDER BY motddate DESC LIMIT 1";
$result2 = db_query_cached($sql2, "lastmotd");
while ($row = db_fetch_assoc($result2)) {
	require_once("lib/motd.php");
	require_once("lib/nltoappon.php");
	if ($row['motdauthorname']=="")
		$row['motdauthorname']="`@Green Dragon Staff`0";
	if ($row['motdtype']==0){
		motditem($row['motdtitle'], $row['motdbody'],$row['motdauthorname'], $row['motddate'], "");
	}else{
		pollitem($row['motditem'], $row['motdtitle'], $row['motdbody'],$row['motdauthorname'],$row['motddate'], false);
	}
}
OutputClass::output_notl("`n");
OutputClass::output("`c`b`!News for %s %s`0`b`c", $date, $pagestr);

while ($row = db_fetch_assoc($result)) {
	OutputClass::output_notl("`c`2-=-`@=-=`2-=-`@=-=`2-=-`@=-=`2-=-`0`c");
	if ($session['user']['superuser'] & SU_EDIT_COMMENTS){
		$del = Translator::translate_inline("Del");
		OutputClass::rawoutput("[ <a href='superuser.php?op=newsdelete&newsid=".$row['newsid']."&return=".URLEncode($_SERVER['REQUEST_URI'])."'>$del</a> ]&nbsp;");
		OutputClass::addnav("","superuser.php?op=newsdelete&newsid={$row['newsid']}&return=".URLEncode($_SERVER['REQUEST_URI']));
	}
	Translator::tlschema($row['Translator::tlschema']);
	if ($row['arguments']>""){
		$arguments = array();
		$base_arguments = unserialize($row['arguments']);
		array_push($arguments,$row['newstext']);
		while (list($key,$val)=each($base_arguments)){
			array_push($arguments,$val);
		}
		$news = call_user_func_array("sprintf_translate",$arguments);
		OutputClass::rawoutput(tlbutton_clear());
	}else{
		$news = Translator::translate_inline($row['newstext']);
	}
	Translator::tlschema();
	OutputClass::output_notl($news."`n");
}
if (db_num_rows($result)==0){
	OutputClass::output_notl("`c`2-=-`@=-=`2-=-`@=-=`2-=-`@=-=`2-=-`0`c");
	OutputClass::output("`1`b`c Nothing of note happened this day.  All in all a boring day. `c`b`0");
}
OutputClass::output_notl("`c`2-=-`@=-=`2-=-`@=-=`2-=-`@=-=`2-=-`0`c");
if (!$session['user']['loggedin']) {
	OutputClass::addnav("Login Screen", "index.php");
} else if ($session['user']['alive']){
	villagenav();
}else{
	Translator::tlschema("nav");
	if($session['user']['sex'] == 1) {
		OutputClass::addnav("`!`bYou're dead, Jane!`b`0");
	} else {
		OutputClass::addnav("`!`bYou're dead, Jim!`b`0");
	}
	OutputClass::addnav("S?Land of Shades","shades.php");
	OutputClass::addnav("G?The Graveyard","graveyard.php");
	require_once("lib/extended-battle.php");
	suspend_companions("allowinshades", true);
	OutputClass::addnav("Log out","login.php?op=logout");
	Translator::tlschema();
}
OutputClass::addnav("News");
OutputClass::addnav("Previous News","news.php?offset=".($offset+1));
if ($offset>0){
	OutputClass::addnav("Next News","news.php?offset=".($offset-1));
}
if ($session['user']['loggedin'])
	OutputClass::addnav("Preferences","prefs.php");
OutputClass::addnav("About this game","about.php");

Translator::tlschema("nav");
if ($session['user']['superuser'] & SU_EDIT_COMMENTS){
	OutputClass::addnav("Superuser");
	OutputClass::addnav(",?Comment Moderation","moderate.php");
}
if ($session['user']['superuser']&~SU_DOESNT_GIVE_GROTTO){
	OutputClass::addnav("Superuser");
	OutputClass::addnav("X?Superuser Grotto","superuser.php");
}
if ($session['user']['superuser'] & SU_INFINITE_DAYS){
	OutputClass::addnav("Superuser");
	OutputClass::addnav("/?New Day","newday.php");
}
Translator::tlschema();

OutputClass::addnav("","news.php");
if ($totaltoday>$newsperpage){
	OutputClass::addnav("Today's news");
	for ($i=0;$i<$totaltoday;$i+=$newsperpage){
		$pnum = $i/$newsperpage+1;
		if ($pnum == $page) {
			OutputClass::addnav(array("`b`#Page %s`0`b", $pnum),"news.php?offset=$offset&page=$pnum");
		} else {
			OutputClass::addnav(array("Page %s", $pnum),"news.php?offset=$offset&page=$pnum");
		}
	}
}

PageParts::page_footer();
?>