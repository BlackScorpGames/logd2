<?php
Translator::tlschema("petition");
PageParts::popup_header("Petition for Help");
$post = Http::httpallpost();
if (count($post)>0){
	$ip = explode(".",$_SERVER['REMOTE_ADDR']);
	array_pop($ip);
	$ip = join($ip,".").".";
	$sql = "SELECT count(petitionid) AS c FROM ".db_prefix("petitions")." WHERE (ip LIKE '$ip%' OR id = '".addslashes($_COOKIE['lgi'])."') AND date > '".date("Y-m-d H:i:s",strtotime("-1 day"))."'";
	$result = db_query($sql);
	$row = db_fetch_assoc($result);
	if ($row['c'] < 5 || (isset($session['user']['superuser']) && $session['user']['superuser']&~SU_DOESNT_GIVE_GROTTO)){
		if (!isset($session['user']['acctid']))
			$session['user']['acctid']=0;
		if (!isset($session['user']['password']))
			$session['user']['password']="";
		$p = $session['user']['password'];
		unset($session['user']['password']);
		$date = date("Y-m-d H:i:s");
		$post['cancelpetition'] = false;
		$post['cancelreason'] = 'The admins here decided they didn\'t like something about how you submitted your petition.  They were also too lazy to give a real reason.';
		$post = Modules::modulehook("addpetition",$post);
		if (!$post['cancelpetition']){
			unset($post['cancelpetition'], $post['cancelreason']);
			$sql = "INSERT INTO " . db_prefix("petitions") . " (author,date,body,pageinfo,ip,id) VALUES (".(int)$session['user']['acctid'].",'$date',\"".addslashes(output_array($post))."\",\"".addslashes(output_array($session,"Session:"))."\",'{$_SERVER['REMOTE_ADDR']}','".addslashes($_COOKIE['lgi'])."')";
			db_query($sql);
			// Fix the counter
			DataCache::invalidatedatacache("petitioncounts");
			// If the admin wants it, email the petitions to them.
			if (Settings::getsetting("emailpetitions", 0)) {
				// Yeah, the format of this is ugly.
				require_once("lib/sanitize.php");
				$name = SanitizeClass::color_sanitize($session['user']['name']);
				$url = Settings::getsetting("serverurl",
					"http://".$_SERVER['SERVER_NAME'] .
					($_SERVER['SERVER_PORT']==80?"":":".$_SERVER['SERVER_PORT']) .
					dirname($_SERVER['REQUEST_URI']));
				if (!preg_match("/\\/$/", $url)) {
					$url = $url . "/";
					Settings::savesetting("serverurl", $url);
				}
				$tl_server = Translator::translate_inline("Server");
				$tl_author = Translator::translate_inline("Author");
				$tl_date = Translator::translate_inline("Date");
				$tl_body = Translator::translate_inline("Body");
				$tl_subject = Translator::sprintf_translate("New LoGD Petition at %s", $url);

				$msg  = "$tl_server: $url\n";
				$msg .= "$tl_author: $name\n";
				$msg .= "$tl_date : $date\n";
				$msg .= "$tl_body :\n".output_array($post)."\n";
				mail(Settings::getsetting("gameadminemail","postmaster@localhost.com"),$tl_subject, $msg);
			}
			$session['user']['password']=$p;
			OutputClass::output("Your petition has been sent to the server admin.");
			OutputClass::output("Please be patient, most server admins have jobs and obligations beyond their game, so sometimes responses will take a while to be received.");
		} else {
			OutputClass::output("`\$There was a problem with your petition!`n");
			OutputClass::output("`@Please read the information below carefully; there was a problem with your petition, and it was not submitted.\n");
			OutputClass::rawoutput("<blockquote>");
			OutputClass::output($post['cancelreason']);
			OutputClass::rawoutput("</blockquote>");
		}
	}else{
		OutputClass::output("`\$`bError:`b There have already been %s petitions filed from your network in the last day; to prevent abuse of the petition system, you must wait until there have been 5 or fewer within the last 24 hours.",$row['c']);
		OutputClass::output("If you have multiple issues to bring up with the staff of this server, you might think about consolidating those issues to reduce the overall number of petitions you file.");
	}
}else{
	OutputClass::output("`c`b`\$Before sending a petition, please make sure you have read the motd.`n");
	OutputClass::output("Petitions about problems we already know about just take up time we could be using to fix those problems.`b`c`n");
	OutputClass::rawoutput("<form action='petition.php?op=submit' method='POST'>");
	if ($session['user']['loggedin']) {
		OutputClass::output("Your Character's Name: ");
		OutputClass::output_notl("%s", $session['user']['name']);
		OutputClass::rawoutput("<input type='hidden' name='charname' value=\"".htmlentities($session['user']['name'], ENT_COMPAT, Settings::getsetting("charset", "ISO-8859-1"))."\">");
		OutputClass::output("`nYour email address: ");
		OutputClass::output_notl("%s", htmlentities($session['user']['emailaddress']));
		OutputClass::rawoutput("<input type='hidden' name='email' value=\"".htmlentities($session['user']['emailaddress'], ENT_COMPAT, Settings::getsetting("charset", "ISO-8859-1"))."\">");
	} else {
		OutputClass::output("Your Character's Name: ");
		OutputClass::rawoutput("<input name='charname' value=\"".htmlentities($session['user']['name'], ENT_COMPAT, Settings::getsetting("charset", "ISO-8859-1"))."\" size='46'>");
		OutputClass::output("`nYour email address: ");
		OutputClass::rawoutput("<input name='email' value=\"".htmlentities($session['user']['emailaddress'], ENT_COMPAT, Settings::getsetting("charset", "ISO-8859-1"))."\" size='50'>");
		$nolog = Translator::translate_inline("Character is not logged in!!");
		OutputClass::rawoutput("<input name='unverified' type='hidden' value='$nolog'>");
	}
	OutputClass::output("`nDescription of the problem:`n");
	$abuse = Http::httpget("abuse");
	if ($abuse == "yes") {
		OutputClass::rawoutput("<textarea name='description' cols='55' rows='7' class='input'></textarea>");
		OutputClass::rawoutput("<input type='hidden' name='abuse' value=\"".stripslashes_deep(htmlentities(Http::httpget("problem"), ENT_COMPAT, Settings::getsetting("charset", "ISO-8859-1")))."\"><br><hr><pre>".stripslashes(htmlentities(Http::httpget("problem")))."</pre><hr><br>");
	} else {
		OutputClass::rawoutput("<textarea name='description' cols='55' rows='7' class='input'>".stripslashes_deep(htmlentities(Http::httpget("problem"), ENT_COMPAT, Settings::getsetting("charset", "ISO-8859-1")))."</textarea>");
	}
	Modules::modulehook("petitionform",array());
	$submit = Translator::translate_inline("Submit");
	OutputClass::rawoutput("<br/><input type='submit' class='button' value='$submit'><br/>");
	OutputClass::output("Please be as descriptive as possible in your petition.");
	OutputClass::output("If you have questions about how the game works, please check out the <a href='petition.php?op=faq'>FAQ</a>.", true);
	OutputClass::output("Petitions about game mechanics will more than likely not be answered unless they have something to do with a bug.");
	OutputClass::output("Remember, if you are not signed in, and do not provide an email address, we have no way to contact you.");
	OutputClass::rawoutput("</form>");
}
?>
