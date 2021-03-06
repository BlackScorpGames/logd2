<?php
// translator ready
// addnews ready
// mail ready

if (isset($_POST['template'])){
	$skin = $_POST['template'];
	if ($skin > "") {
		setcookie("template",$skin ,strtotime("+45 days"));
		$_COOKIE['template']=$skin;
	}
}

define("ALLOW_ANONYMOUS",true);
require_once("common.php");
require_once("lib/http.php");


if (!isset($session['loggedin'])) $session['loggedin']=false;
if ($session['loggedin']){
	RedirectClass::redirect("badnav.php");
}

Translator::tlschema("home");

$op = Http::httpget('op');

PageParts::page_header();
OutputClass::output("`cWelcome to Legend of the Green Dragon, a browser based role playing game, based on Seth Able's Legend of the Red Dragon.`n");

if (Settings::getsetting("homecurtime", 1)) {
	OutputClass::output("`@The current time in %s is `%%s`@.`0`n", Settings::getsetting("villagename", LOCATION_FIELDS), GameDateTime::getgametime());
}

if (Settings::getsetting("homenewdaytime", 1)) {
	$secstonewday = GameDateTime::secondstonextgameday();
	OutputClass::output("`@Next new game day in: `\$%s (real time)`0`n`n",
			date("G\\".Translator::translate_inline("h","datetime").", i\\".Translator::translate_inline("m","datetime").", s\\".Translator::translate_inline("s","datetime"),
				$secstonewday));
}

if (Settings::getsetting("homenewestplayer", 1)) {
	$name = "";
	$newplayer = Settings::getsetting("newestplayer", "");
	if ($newplayer != 0) {
		$sql = "SELECT name FROM " . db_prefix("accounts") . " WHERE acctid='$newplayer'";
		$result = db_query_cached($sql, "newest");
		$row = db_fetch_assoc($result);
		$name = $row['name'];
	} else {
		$name = $newplayer;
	}
	if ($name != "") {
		OutputClass::output("`QThe newest resident of the realm is: `&%s`0`n`n", $name);
	}
}

OutputClass::clearnav();
OutputClass::addnav("New to LoGD?");
OutputClass::addnav("Create a character","create.php");
OutputClass::addnav("Game Functions");
OutputClass::addnav("Forgotten Password","create.php?op=forgot");
OutputClass::addnav("List Warriors","list.php");
OutputClass::addnav("Daily News", "news.php");
OutputClass::addnav("Other Info");
OutputClass::addnav("About LoGD","about.php");
OutputClass::addnav("Game Setup Info", "about.php?op=setup");
OutputClass::addnav("LoGD Net","logdnet.php?op=list");

Modules::modulehook("index", array());

if (abs(Settings::getsetting("OnlineCountLast",0) - strtotime("now")) > 60){
	$sql="SELECT count(acctid) as onlinecount FROM " . db_prefix("accounts") . " WHERE locked=0 AND loggedin=1 AND laston>'".date("Y-m-d H:i:s",strtotime("-".Settings::getsetting("LOGINTIMEOUT",900)." seconds"))."'";
	$result = db_query($sql);
	$onlinecount = db_fetch_assoc($result);
	$onlinecount = $onlinecount ['onlinecount'];
	Settings::savesetting("OnlineCount",$onlinecount);
	Settings::savesetting("OnlineCountLast",strtotime("now"));
}else{
	$onlinecount = Settings::getsetting("OnlineCount",0);
}
if ($onlinecount<Settings::getsetting("maxonline",0) || Settings::getsetting("maxonline",0)==0){
	OutputClass::output("Enter your name and password to enter the realm.`n");
	if ($op=="timeout"){
		$session['message'].= Translator::translate_inline(" Your session has timed out, you must log in again.`n");
	}
	if (!isset($_COOKIE['lgi'])){
		$session['message'].=Translator::translate_inline("It appears that you may be blocking cookies from this site.  At least session cookies must be enabled in order to use this site.`n");
		$session['message'].=Translator::translate_inline("`b`#If you are not sure what cookies are, please <a href='http://en.wikipedia.org/wiki/WWW_browser_cookie'>read this article</a> about them, and how to enable them.`b`n");
	}
	if (isset($session['message']) && $session['message']>"")
		OutputClass::output_notl("`b`\$%s`b`n", $session['message'],true);
	OutputClass::rawoutput("<script language='JavaScript' src='lib/md5.js'></script>");
	OutputClass::rawoutput("<script language='JavaScript'>
	<!--
	function md5pass(){
		//encode passwords before submission to protect them even from network sniffing attacks.
		var passbox = document.getElementById('password');
		if (passbox.value.substring(0, 5) != '!md5!') {
			passbox.value = '!md5!' + hex_md5(passbox.value);
		}
	}
	//-->
	</script>");
	$uname = Translator::translate_inline("<u>U</u>sername");
	$pass = Translator::translate_inline("<u>P</u>assword");
	$butt = Translator::translate_inline("Log in");
	OutputClass::rawoutput("<form action='login.php' method='POST' onSubmit=\"md5pass();\">".Template::templatereplace("login",array("username"=>$uname,"password"=>$pass,"button"=>$butt))."</form>");
	OutputClass::output_notl("`c");
	OutputClass::addnav("","login.php");
} else {
	OutputClass::output("`\$`bServer full!`b`n`^Please wait until some users have logged out.`n`n`0");
	if ($op=="timeout"){
		$session['message'].= Translator::translate_inline(" Your session has timed out, you must log in again.`n");
	}
	if (!isset($_COOKIE['lgi'])){
		$session['message'].=Translator::translate_inline("It appears that you may be blocking cookies from this site. At least session cookies must be enabled in order to use this site.`n");
		$session['message'].=Translator::translate_inline("`b`#If you are not sure what cookies are, please <a href='http://en.wikipedia.org/wiki/WWW_browser_cookie'>read this article</a> about them, and how to enable them.`b`n");
	}
	if ($session['message']>"") OutputClass::output("`b`\$%s`b`n", $session['message'],true);
	OutputClass::rawoutput(Template::templatereplace("loginfull",array()));
	OutputClass::output_notl("`c");
}

$msg = Settings::getsetting("loginbanner","*BETA* This is a BETA of this website, things are likely to change now and again, as it is under active development *BETA*");
OutputClass::output_notl("`n`c`b`&%s`0`b`c`n", $msg);
$session['message']="";
OutputClass::output("`c`2Game server running version: `@%s`0`c", $logd_version);

if (Settings::getsetting("homeskinselect", 1)) {
	OutputClass::rawoutput("<form action='home.php' method='POST'>");
	OutputClass::rawoutput("<table align='center'><tr><td>");
	$form = array("template"=>"Choose a different display skin:,theme");
	$prefs['template'] = $_COOKIE['template'];
	if ($prefs['template'] == "")
		$prefs['template'] = Settings::getsetting("defaultskin", "jade.htm");
	require_once("lib/showform.php");
	ShowFormClass::showform($form, $prefs, true);
	$submit = Translator::translate_inline("Choose");
	OutputClass::rawoutput("</td><td><br>&nbsp;<input type='submit' class='button' value='$submit'></td>");
	OutputClass::rawoutput("</tr></table></form>");
}

PageParts::page_footer();
?>