<?php
// translator ready
// addnews ready
// mail ready
define("ALLOW_ANONYMOUS",true);
require_once("common.php");
require_once("lib/http.php");

Translator::tlschema("referral");

if ($session['user']['loggedin']){
	PageParts::page_header("Referral Page");
	if (file_exists("lodge.php")) {
		OutputClass::addnav("L?Return to the Lodge","lodge.php");
	} else {
		require_once("lib/villagenav.php");
		villagenav();
	}
	OutputClass::output("You will automatically receive %s points for each person that you refer to this website who makes it to level %s.`n`n", Settings::getsetting("refereraward", 25), Settings::getsetting("referminlevel", 4));

	$url = Settings::getsetting("serverurl",
			"http://".$_SERVER['SERVER_NAME'] .
			($_SERVER['SERVER_PORT']==80?"":":".$_SERVER['SERVER_PORT']) .
			dirname($_SERVER['REQUEST_URI']));
	if (!preg_match("/\\/$/", $url)) {
		$url = $url . "/";
		Settings::savesetting("serverurl", $url);
	}

	OutputClass::output("How does the site know that I referred a person?`n");
	OutputClass::output("Easy!  When you tell your friends about this site, give out the following link:`n`n");
	OutputClass::output_notl("%sreferral.php?r=%s`n`n",$url,rawurlencode($session['user']['login']));
	OutputClass::output("If you do, the site will know that you were the one who sent them here.");
	OutputClass::output("When they reach level %s for the first time, you'll get your points!", Settings::getsetting("referminlevel", 4));

	$sql = "SELECT name,level,refererawarded FROM " . db_prefix("accounts") . " WHERE referer={$session['user']['acctid']} ORDER BY dragonkills,level";
	$result = db_query($sql);
	$name=Translator::translate_inline("Name");
	$level=Translator::translate_inline("Level");
	$awarded=Translator::translate_inline("Awarded?");
	$yes=Translator::translate_inline("`@Yes!`0");
	$no=Translator::translate_inline("`\$No!`0");
	$none=Translator::translate_inline("`iNone`i");
	OutputClass::output("`n`nAccounts which you referred:`n");
	OutputClass::rawoutput("<table border='0' cellpadding='3' cellspacing='0'><tr><td>$name</td><td>$level</td><td>$awarded</td></tr>");
	$number=db_num_rows($result);
	for ($i=0;$i<$number;$i++){
		$row = db_fetch_assoc($result);
		OutputClass::rawoutput("<tr class='".($i%2?"trlight":"trdark")."'><td>");
		OutputClass::output_notl($row['name']);
		OutputClass::rawoutput("</td><td>");
		OutputClass::output_notl($row['level']);
		OutputClass::rawoutput("</td><td>");
		OutputClass::output_notl($row['refererawarded']?$yes:$no);
		OutputClass::rawoutput("</td></tr>");
	}
	if (db_num_rows($result)==0){
		OutputClass::rawoutput("<tr><td colspan='3' align='center'>");
		OutputClass::output_notl($none);
		OutputClass::rawoutput("</td></tr>");
	}
	OutputClass::rawoutput("</table>",true);
	PageParts::page_footer();
}else{
	PageParts::page_header("Welcome to Legend of the Green Dragon");
	OutputClass::output("`@Legend of the Green Dragon is a remake of the classic BBS Door Game Legend of the Red Dragon.");
	OutputClass::output("Adventure into the classic realm that was one of the world's very first multiplayer roleplaying games!");
	OutputClass::addnav("Create a character","create.php?r=".HTMLEntities(Http::httpget('r'), ENT_COMPAT, Settings::getsetting("charset", "ISO-8859-1")));
	OutputClass::addnav("Login Page","index.php");
	PageParts::page_footer();
}
?>