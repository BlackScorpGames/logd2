<?php
// translator ready
// addnews ready
// mail ready

// Okay, someone wants to use this outside of normal game flow.. no real harm
define("OVERRIDE_FORCED_NAV",true);

// Translate Untranslated Strings
// Originally Written by Christian Rutsch
// Slightly modified by JT Traub
require_once("common.php");
require_once("lib/http.php");

SuAccess::check_su_access(SU_IS_TRANSLATOR);

Translator::tlschema("untranslated");

$op = Http::httpget('op');
PageParts::page_header("Untranslated Texts");

if ($op == "list") {
	$mode = Http::httpget('mode');
	$namespace = Http::httpget('ns');

	if ($mode == "save") {
		$intext = Http::httppost('intext');
		$outtext = Http::httppost('outtext');
		if ($outtext <> "") {
			$login = $session['user']['login'];
			$language = $session['user']['prefs']['language'];
			$sql = "INSERT INTO " . db_prefix("translations") . " (language,uri,intext,outtext,author,version) VALUES" . " ('$language','$namespace','$intext','$outtext','$login','$logd_version')";
			db_query($sql);
			$sql = "DELETE FROM " . db_prefix("untranslated") . " WHERE intext = '$intext' AND language = '$language' AND namespace = '$namespace'";
			db_query($sql);
		}
	}

	if ($mode == "edit") {
		OutputClass::rawoutput("<form action='untranslated.php?op=list&mode=save&ns=".rawurlencode($namespace)."' method='post'>");
		OutputClass::addnav("", "untranslated.php?op=list&mode=save&ns=".rawurlencode($namespace));
	} else {
		OutputClass::rawoutput("<form action='untranslated.php?op=list' method='get'>");
		OutputClass::addnav("", "untranslated.php?op=list");
	}

	$sql = "SELECT namespace,count(*) AS c FROM " . db_prefix("untranslated") . " WHERE language='".$session['user']['prefs']['language']."' GROUP BY namespace ORDER BY namespace ASC";
	$result = db_query($sql);
	OutputClass::rawoutput("<input type='hidden' name='op' value='list'>");
	OutputClass::output("Known Namespaces:");
	OutputClass::rawoutput("<select name='ns'>");
	while ($row = db_fetch_assoc($result)){
		OutputClass::rawoutput("<option value=\"".htmlentities($row['namespace'], ENT_COMPAT, Settings::getsetting("charset", "ISO-8859-1"))."\"".((htmlentities($row['namespace'], ENT_COMPAT, Settings::getsetting("charset", "ISO-8859-1")) == $namespace) ? "selected" : "").">".htmlentities($row['namespace'], ENT_COMPAT, Settings::getsetting("charset", "ISO-8859-1"))." ({$row['c']})</option>");
	}
	OutputClass::rawoutput("</select>");
	OutputClass::rawoutput("<input type='submit' class='button' value='". Translator::translate_inline("Show") ."'>");
	OutputClass::rawoutput("<br>");

	if ($mode == "edit") {
		OutputClass::rawoutput(Translator::translate_inline("Text:"). "<br>");
		OutputClass::rawoutput("<textarea name='intext' cols='60' rows='5' readonly>".htmlentities(stripslashes(Http::httpget('intext')), ENT_COMPAT, Settings::getsetting("charset", "ISO-8859-1"))."</textarea><br/>");
		OutputClass::rawoutput(Translator::translate_inline("Translation:"). "<br>");
		OutputClass::rawoutput("<textarea name='outtext' cols='60' rows='5'></textarea><br/>");
		OutputClass::rawoutput("<input type='submit' value='". Translator::translate_inline("Save") ."' class='button'>");
	} else {
		OutputClass::rawoutput("<table border='0' cellpadding='2' cellspacing='0'>");
		OutputClass::rawoutput("<tr class='trhead'><td>". Translator::translate_inline("Ops") ."</td><td>". Translator::translate_inline("Text") ."</td></tr>");
		$sql = "SELECT * FROM " . db_prefix("untranslated") . " WHERE language='".$session['user']['prefs']['language']."' AND namespace='".$namespace."'";
		$result = db_query($sql);
		if (db_num_rows($result)>0){
			$i = 0;
			while ($row = db_fetch_assoc($result)){
				$i++;
				OutputClass::rawoutput("<tr class='".($i%2?"trlight":"trdark")."'><td>");
				OutputClass::rawoutput("<a href='untranslated.php?op=list&mode=edit&ns=". rawurlencode($row['namespace']) ."&intext=". rawurlencode($row['intext']) ."'>". Translator::translate_inline("Edit") ."</a>");
				OutputClass::addnav("", "untranslated.php?op=list&mode=edit&ns=". rawurlencode($row['namespace']) ."&intext=". rawurlencode($row['intext']));
				OutputClass::rawoutput("</td><td>");
				OutputClass::rawoutput(htmlentities($row['intext'], ENT_COMPAT, Settings::getsetting("charset", "ISO-8859-1")));
				OutputClass::rawoutput("</td></tr>");
			}
		}else{
			OutputClass::rawoutput("<tr><td colspan='2'>". Translator::translate_inline("No rows found") ."</td></tr>");
		}
		OutputClass::rawoutput("</table>");
	}

	OutputClass::rawoutput("</form>");

} else {
	if ($op == "step2") {
		$intext = Http::httppost('intext');
		$outtext = Http::httppost('outtext');
		$namespace = Http::httppost('namespace');
		$language = Http::httppost('language');
		if ($outtext <> "") {
			$login = $session['user']['login'];
			$sql = "INSERT INTO " . db_prefix("translations") . " (language,uri,intext,outtext,author,version) VALUES" . " ('$language','$namespace','$intext','$outtext','$login','$logd_version')";
			db_query($sql);
			$sql = "DELETE FROM " . db_prefix("untranslated") . " WHERE intext = '$intext' AND language = '$language' AND namespace = '$namespace'";
			db_query($sql);
			DataCache::invalidatedatacache("translations-".$namespace."-".$language);
		}
	}

	$sql = "SELECT count(intext) AS count FROM " . db_prefix("untranslated");
	$count = db_fetch_assoc(db_query($sql));
	if ($count['count'] > 0) {
		$sql = "SELECT * FROM " . db_prefix("untranslated") . " WHERE language = '" . $session['user']['prefs']['language'] . "' ORDER BY rand(".Erand::e_rand().") LIMIT 1";
		$result = db_query($sql);
		if (db_num_rows($result) == 1) {
			$row = db_fetch_assoc($result);
			$row['intext'] = stripslashes($row['intext']);
			$submit = Translator::translate_inline("Save Translation");
			$skip = Translator::translate_inline("Skip Translation");
			OutputClass::rawoutput("<form action='untranslated.php?op=step2' method='post'>");
			OutputClass::output("`^`cThere are `&%s`^ untranslated texts in the database.`c`n`n", $count['count']);
			OutputClass::rawoutput("<table width='80%'>");
			OutputClass::rawoutput("<tr><td width='30%'>");
			OutputClass::output("Target Language: %s", $row['language']);
			OutputClass::rawoutput("</td><td></td></tr>");
			OutputClass::rawoutput("<tr><td width='30%'>");
			OutputClass::output("Namespace: %s", $row['namespace']);
			OutputClass::rawoutput("</td><td></td></tr>");
			OutputClass::rawoutput("<tr><td width='30%'><textarea cols='35' rows='4' name='intext'>".$row['intext']."</textarea></td>");
			OutputClass::rawoutput("<td width='30%'><textarea cols='25' rows='4' name='outtext'></textarea></td></tr></table>");
			OutputClass::rawoutput("<input type='hidden' name='id' value='{$row['id']}'>");
			OutputClass::rawoutput("<input type='hidden' name='language' value='{$row['language']}'>");
			OutputClass::rawoutput("<input type='hidden' name='namespace' value='{$row['namespace']}'>");
			OutputClass::rawoutput("<input type='submit' value='$submit' class='button'>");
			OutputClass::rawoutput("</form>");
			OutputClass::rawoutput("<form action='untranslated.php' method='post'>");
			OutputClass::rawoutput("<input type='submit' value='$skip' class='button'>");
			OutputClass::rawoutput("</form>");
			OutputClass::addnav("", "untranslated.php?op=step2");
			OutputClass::addnav("", "untranslated.php");
		} else {
			OutputClass::output("There are `&%s`^ untranslated texts in the database, but none for your selected language.", $count['count']);
			OutputClass::output("Please change your language to translate these texts.");
		}
	} else {
		OutputClass::output("There are no untranslated texts in the database!");
		OutputClass::output("Congratulations!!!");
	} // end if
} // end list if
OutputClass::addnav("R?Restart Translator", "untranslated.php");
OutputClass::addnav("N?Translate by Namespace", "untranslated.php?op=list");
require_once("lib/superusernav.php");
superusernav();
PageParts::page_footer();

?>