<?php
// translator ready
// addnews ready
// mail ready
require_once("common.php");
require_once("lib/http.php");

check_su_access(SU_EDIT_COMMENTS);

Translator::tlschema("badword");

$op = Http::httpget('op');
//yuck, this page is a mess, but it gets the job done.
PageParts::page_header("Bad word editor");

require_once("lib/superusernav.php");
superusernav();
OutputClass::addnav("Bad Word Editor");

OutputClass::addnav("Refresh the list","badword.php");
OutputClass::output("`7Here you can edit the words that the game filters.  Using * at the start or end of a word will be a wildcard matching anything else attached to the word.  These words are only filtered if bad word filtering is turned on in the game settings page.`n`n`0");

$test = Translator::translate_inline("Test");
OutputClass::rawoutput("<form action='badword.php?op=test' method='POST'>");
OutputClass::addnav("","badword.php?op=test");
OutputClass::output("`7Test a word:`0");
OutputClass::rawoutput("<input name='word'><input type='submit' class='button' value='$test'></form>");
if ($op=="test"){
	$word = Http::httppost("word");
	$return = soap($word,true);
	if ($return == $word)
		OutputClass::output("`7\"%s\" does not trip any filters.`0`n`n", $word);
	else
		OutputClass::output("`7%s`0`n`n", $return);
}

OutputClass::output_notl("<font size='+1'>", true);
OutputClass::output("`7`bGood Words`b`0");
OutputClass::rawoutput("</font>");
OutputClass::output("`7 (bad word exceptions)`0`n");

$add = Translator::translate_inline("Add");
$remove = Translator::translate_inline("Remove");
OutputClass::rawoutput("<form action='badword.php?op=addgood' method='POST'>");
OutputClass::addnav("","badword.php?op=addgood");
OutputClass::output("`7Add a word:`0");
OutputClass::rawoutput("<input name='word'><input type='submit' class='button' value='$add'></form>");
OutputClass::rawoutput("<form action='badword.php?op=removegood' method='POST'>");
OutputClass::addnav("","badword.php?op=removegood");
OutputClass::output("`7Remove a word:`0");
OutputClass::rawoutput("<input name='word'><input type='submit' class='button' value='$remove'></form>");


$sql = "SELECT * FROM ".db_prefix("nastywords")." WHERE type='good'";
$result = db_query($sql);
$row = db_fetch_assoc($result);
$words = explode(" ",$row['words']);
if ($op=="addgood"){

	$newregexp = stripslashes(Http::httppost('word'));

	// not sure if the line below should appear, as the strings in the good
	// word list have different behaviour than those in the nasty word list,
	// and strings with single quotes in them currently have odd and
	// unreliable behaviour, both under the good word list and the nasty
	// word list
	//	$newregexp = preg_replace('/(?<!\\\\)\'/', '\\\'', $newregexp);

	// $newregexp = str_replace("\n", '', $newregexp);
	// appears to only remove the line feed character, chr(10),
	// but leaves the carriage return character, chr(13), intact
	$newregexp = str_replace("\n", '', $newregexp);
	$newregexp = str_replace("\r", '', $newregexp);

	if ( $newregexp !== '' )
		array_push($words,$newregexp);

	//array_push($words,stripslashes(httppost('word')));
}
if ($op=="removegood"){

	// false if not found
	$removekey = array_search(stripslashes(Http::httppost('word')),$words);
	// $removekey can be 0
	if ( $removekey !== false ) unset($words[$removekey]);

	//unset($words[array_search(stripslashes(httppost('word')),$words)]);
}

show_word_list($words);
if ($op=="addgood" || $op=="removegood"){
	$sql = "DELETE FROM " . db_prefix("nastywords") . " WHERE type='good'";
	db_query($sql);
	$sql = "INSERT INTO " . db_prefix("nastywords") . " (words,type) VALUES ('" . addslashes(join(" ",$words)) . "','good')";
	db_query($sql);
	invalidatedatacache("goodwordlist");
}

OutputClass::output_notl("`0`n`n");
OutputClass::rawoutput("<font size='+1'>");
OutputClass::output("`7`bNasty Words`b`0");
OutputClass::rawoutput("</font>");
OutputClass::output_notl("`n");

OutputClass::rawoutput("<form action='badword.php?op=add' method='POST'>");
OutputClass::addnav("","badword.php?op=add");
OutputClass::output("`7Add a word:`0");
OutputClass::rawoutput("<input name='word'><input type='submit' class='button' value='$add'></form>");
OutputClass::rawoutput("<form action='badword.php?op=remove' method='POST'>");
OutputClass::addnav("","badword.php?op=remove");
OutputClass::output("`7Remove a word:`0");
OutputClass::rawoutput("<input name='word'><input type='submit' class='button' value='$remove'></form>");

$sql = "SELECT * FROM " . db_prefix("nastywords") . " WHERE type='nasty'";
$result = db_query($sql);
$row = db_fetch_assoc($result);
$words = explode(" ",$row['words']);
reset($words);

if ($op=="add"){

	$newregexp = stripslashes(Http::httppost('word'));

	// automagically escapes all unescaped single quote characters
	$newregexp = preg_replace('/(?<!\\\\)\'/', '\\\'', $newregexp);

	// $newregexp = str_replace("\n", '', $newregexp);
	// appears to only remove the line feed character, chr(10),
	// but leaves the carriage return character, chr(13), intact
	$newregexp = str_replace("\n", '', $newregexp);
	$newregexp = str_replace("\r", '', $newregexp);

	if ( $newregexp !== '' ) array_push($words,$newregexp);

	//array_push($words,stripslashes(httppost('word')));
}
if ($op=="remove"){
	// false if not found
	$removekey = array_search(stripslashes(Http::httppost('word')),$words);
	// $removekey can be 0
	if ( $removekey !== false ) unset($words[$removekey]);

	//unset($words[array_search(stripslashes(httppost('word')),$words)]);
}
show_word_list($words);
OutputClass::output_notl("`0");

if ($op=="add" || $op=="remove"){
	$sql = "DELETE FROM " . db_prefix("nastywords") . " WHERE type='nasty'";
	db_query($sql);
	$sql = "INSERT INTO " . db_prefix("nastywords") . " (words,type) VALUES ('" . addslashes(join(" ",$words)) . "','nasty')";
	db_query($sql);
	invalidatedatacache("nastywordlist");
}
PageParts::page_footer();

function show_word_list($words){
	sort($words);
	$lastletter="";
	while (list($key,$val)=each($words)){
		if (trim($val)==""){
			unset($words[$key]);
		}else{
			if (substr($val,0,1)!=$lastletter){
				$lastletter = substr($val,0,1);
				OutputClass::output_notl("`n`n`^`b%s`b`@`n", strtoupper($lastletter));
			}
			OutputClass::output_notl("%s ", $val);
		}
	}
}
?>