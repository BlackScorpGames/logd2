<?php
// addnews ready
// translator ready
// mail ready
define("OVERRIDE_FORCED_NAV",true);
require_once("common.php");
Translator::tlschema("translatortool");

check_su_access(SU_IS_TRANSLATOR);
$op=Http::httpget("op");
if ($op==""){
	popup_header("Translator Tool");
	$uri = rawurldecode(Http::httpget('u'));
	$text = stripslashes(rawurldecode(Http::httpget('t')));
	
	$translation = translate_loadnamespace($uri);
	if (isset($translation[$text]))
		$trans = $translation[$text];
	else
		$trans = "";
	$namespace = Translator::translate_inline("Namespace:");
	$texta = Translator::translate_inline("Text:");
	$translation = Translator::translate_inline("Translation:");
	$saveclose = htmlentities(Translator::translate_inline("Save & Close"), ENT_COMPAT, Settings::getsetting("charset", "ISO-8859-1"));
	$savenotclose = htmlentities(Translator::translate_inline("Save No Close"), ENT_COMPAT, Settings::getsetting("charset", "ISO-8859-1"));
	OutputClass::rawoutput("<form action='translatortool.php?op=save' method='POST'>");
	OutputClass::rawoutput("$namespace <input name='uri' value=\"".htmlentities(stripslashes($uri), ENT_COMPAT, Settings::getsetting("charset", "ISO-8859-1"))."\" readonly><br/>");
	OutputClass::rawoutput("$texta<br>");
	OutputClass::rawoutput("<textarea name='text' cols='60' rows='5' readonly>".htmlentities($text, ENT_COMPAT, Settings::getsetting("charset", "ISO-8859-1"))."</textarea><br/>");
	OutputClass::rawoutput("$translation<br>");
	OutputClass::rawoutput("<textarea name='trans' cols='60' rows='5'>".htmlentities(stripslashes($trans), ENT_COMPAT, Settings::getsetting("charset", "ISO-8859-1"))."</textarea><br/>");
	OutputClass::rawoutput("<input type='submit' value=\"$saveclose\" class='button'>");
	OutputClass::rawoutput("<input type='submit' value=\"$savenotclose\" class='button' name='savenotclose'>");
	OutputClass::rawoutput("</form>");
	popup_footer();
}elseif ($_GET['op']=='save'){
	$uri = Http::httppost('uri');
	$text = Http::httppost('text');
	$trans = Http::httppost('trans');

	$page = $uri;
	if (strpos($page,"?")!==false) $page = substr($page,0,strpos($page,"?"));

	if ($trans==""){
		$sql = "DELETE ";
	}else{
		$sql = "SELECT * ";
	}
	$sql .= "
		FROM ".db_prefix("translations")."
		WHERE language='".LANGUAGE."'
			AND intext='$text'
			AND (uri='$page' OR uri='$uri')";
	if ($trans>""){
		$result = db_query($sql);
		invalidatedatacache("translations-".$uri."-".$language);
		//invalidatedatacache("translations-".$namespace."-".$language);
		if (db_num_rows($result)==0){
			$sql = "INSERT INTO ".db_prefix("translations")." (language,uri,intext,outtext,author,version) VALUES ('".LANGUAGE."','$uri','$text','$trans','{$session['user']['login']}','$logd_version ')";
			$sql1 = "DELETE FROM " . db_prefix("untranslated") .
				" WHERE intext='$text' AND language='" . LANGUAGE .
				"' AND namespace='$url'";
			db_query($sql1);
		}elseif(db_num_rows($result)==1){
			$row = db_fetch_assoc($result);
			// MySQL is case insensitive so we need to do it here.
			if ($row['intext'] == $text){
				$sql = "UPDATE ".db_prefix("translations")." SET author='{$session['user']['login']}', version='$logd_version', uri='$uri', outtext='$trans' WHERE tid={$row['tid']}";
			}else{
				$sql = "INSERT INTO " . db_prefix("translations") . " (language,uri,intext,outtext,author,version) VALUES ('" . LANGUAGE . "','$uri','$text','$trans','{$session['user']['login']}','$logd_version ')";
				$sql1 = "DELETE FROM " . db_prefix("untranslated") . " WHERE intext='$text' AND language='" . LANGUAGE . "' AND namespace='$url'";
				db_query($sql1);
			}
		}elseif(db_num_rows($result)>1){
			$rows = array();
			while ($row = db_fetch_assoc($result)){
				// MySQL is case insensitive so we need to do it here.
				if ($row['intext'] == $text){
					$rows['tid']=$row['tid'];
				}
			}
			$sql = "UPDATE ".db_prefix("translations")." SET author='{$session['user']['login']}', version='$logd_version', uri='$page', outtext='$trans' WHERE tid IN (".join(",",$rows).")";
		}
	}
	db_query($sql);
	if (Http::httppost("savenotclose")>""){
		header("Location: translatortool.php?op=list&u=$page");
		exit();
	}else{
		popup_header("Updated");
		OutputClass::rawoutput("<script language='javascript'>window.close();</script>");
		popup_footer();
	}
}elseif($op=="list"){
	popup_header("Translation List");
	$sql = "SELECT uri,count(*) AS c FROM " . db_prefix("translations") . " WHERE language='".LANGUAGE."' GROUP BY uri ORDER BY uri ASC";
	$result = db_query($sql);
	OutputClass::rawoutput("<form action='translatortool.php' method='GET'>");
	OutputClass::rawoutput("<input type='hidden' name='op' value='list'>");
	OutputClass::output("Known Namespaces:");
	OutputClass::rawoutput("<select name='u'>");
	while ($row = db_fetch_assoc($result)){
		OutputClass::rawoutput("<option value=\"".rawurlencode(htmlentities($row['uri'], ENT_COMPAT, Settings::getsetting("charset", "ISO-8859-1")))."\">".htmlentities($row['uri'], ENT_COMPAT, Settings::getsetting("charset", "ISO-8859-1"))." ({$row['c']})</option>",true);
	}
	OutputClass::rawoutput("</select>");
	$show = Translator::translate_inline("Show");
	OutputClass::rawoutput("<input type='submit' class='button' value=\"$show\">");
	OutputClass::rawoutput("</form>");
	$ops = Translator::translate_inline("Ops");
	$from = Translator::translate_inline("From");
	$to = Translator::translate_inline("To");
	$version = Translator::translate_inline("Version");
	$author = Translator::translate_inline("Author");
	$norows = Translator::translate_inline("No rows found");
	OutputClass::rawoutput("<table border='0' cellpadding='2' cellspacing='0'>");
	OutputClass::rawoutput("<tr class='trhead'><td>$ops</td><td>$from</td><td>$to</td><td>$version</td><td>$author</td></tr>");
	$sql = "SELECT * FROM " . db_prefix("translations") . " WHERE language='".LANGUAGE."' AND uri='".Http::httpget("u")."'";
	$result = db_query($sql);
	if (db_num_rows($result)>0){
		$i=0;
		while ($row = db_fetch_assoc($result)){
			$i++;
			OutputClass::rawoutput("<tr class='".($i%2?"trlight":"trdark")."'><td>");
			$edit = Translator::translate_inline("Edit");
			OutputClass::rawoutput("<a href='translatortool.php?u=".rawurlencode(htmlentities($row['uri'], ENT_COMPAT, Settings::getsetting("charset", "ISO-8859-1")))."&t=".rawurlencode(htmlentities($row['intext']))."'>$edit</a>");
			OutputClass::rawoutput("</td><td>");
			OutputClass::rawoutput(htmlentities($row['intext'], ENT_COMPAT, Settings::getsetting("charset", "ISO-8859-1")));
			OutputClass::rawoutput("</td><td>");
			OutputClass::rawoutput(htmlentities($row['outtext'], ENT_COMPAT, Settings::getsetting("charset", "ISO-8859-1")));
			OutputClass::rawoutput("</td><td>");
			OutputClass::rawoutput($row['version']);
			OutputClass::rawoutput("</td><td>");
			OutputClass::rawoutput($row['author']);
			OutputClass::rawoutput("</td></tr>");
		}
	}else{
		OutputClass::rawoutput("<tr><td colspan='5'>$norows</td></tr>");
	}
	OutputClass::rawoutput("</table>");
	popup_footer();
}
?>