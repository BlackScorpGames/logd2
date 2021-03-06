<?php
// addnews ready
// translator ready
// mail ready
require_once("common.php");
require_once("lib/sanitize.php");

Translator::tlschema("bio");

GameDateTime::checkday();

$ret = Http::httpget('ret');
if ($ret==""){
	$return = "/list.php";
}else{
	$return = SanitizeClass::cmd_sanitize($ret);
}

$char = Http::httpget('char');
//Legacy support
if (is_numeric($char)){
	$where = "acctid = $char";
} else {
	$where = "login = '$char'";
}
$sql = "SELECT login, name, level, sex, title, specialty, hashorse, acctid, resurrections, bio, dragonkills, race, clanname, clanshort, clanrank, ".db_prefix("accounts").".clanid, laston, loggedin FROM " . db_prefix("accounts") . " LEFT JOIN " . db_prefix("clans") . " ON " . db_prefix("accounts") . ".clanid = " . db_prefix("clans") . ".clanid WHERE $where";
$result = db_query($sql);
if ($target = db_fetch_assoc($result)) {
  $target['login'] = rawurlencode($target['login']);
  $id = $target['acctid'];
  $target['return_link']=$return;

  PageParts::page_header("Character Biography: %s", SanitizeClass::full_sanitize($target['name']));

  Translator::tlschema("nav");
  OutputClass::addnav("Return");
  Translator::tlschema();

  if ($session['user']['superuser'] & SU_EDIT_USERS){
	  OutputClass::addnav("Superuser");
	  OutputClass::addnav("Edit User","user.php?op=edit&userid=$id");
  }

  Modules::modulehook("biotop", $target);

  OutputClass::output("`^Biography for %s`^.",$target['name']);
  $write = Translator::translate_inline("Write Mail");
  if ($session['user']['loggedin'])
	  OutputClass::rawoutput("<a href=\"mail.php?op=write&to={$target['login']}\" target=\"_blank\" onClick=\"".popup("mail.php?op=write&to={$target['login']}").";return false;\"><img src='images/newscroll.GIF' width='16' height='16' alt='$write' border='0'></a>");
  OutputClass::output_notl("`n`n");

  if ($target['clanname']>"" && Settings::getsetting("allowclans",false)){
	  $ranks = array(CLAN_APPLICANT=>"`!Applicant`0",CLAN_MEMBER=>"`#Member`0",CLAN_OFFICER=>"`^Officer`0",CLAN_LEADER=>"`&Leader`0", CLAN_FOUNDER=>"`\$Founder");
	  $ranks = Modules::modulehook("clanranks", array("ranks"=>$ranks, "clanid"=>$target['clanid']));
	  Translator::tlschema("clans"); //just to be in the right schema
	  array_push($ranks['ranks'],"`\$Founder");
	  $ranks = Translator::translate_inline($ranks['ranks']);
	  Translator::tlschema();
	  OutputClass::output("`@%s`2 is a %s`2 to `%%s`2`n", $target['name'], $ranks[$target['clanrank']], $target['clanname']);
  }

  OutputClass::output("`^Title: `@%s`n",$target['title']);
  OutputClass::output("`^Level: `@%s`n",$target['level']);
  $loggedin = false;
  if ($target['loggedin'] &&
		  (date("U") - strtotime($target['laston']) <
			Settings::getsetting("LOGINTIMEOUT", 900))) {
	  $loggedin = true;
  }
  $status = Translator::translate_inline($loggedin?"`#Online`0":"`\$Offline`0");
  OutputClass::output("`^Status: %s`n",$status);

  OutputClass::output("`^Resurrections: `@%s`n",$target['resurrections']);

  $race = $target['race'];
  if (!$race) $race = RACE_UNKNOWN;
  Translator::tlschema("race");
  $race = Translator::translate_inline($race);
  Translator::tlschema();
  OutputClass::output("`^Race: `@%s`n",$race);

  $genders = array("Male","Female");
  $genders = Translator::translate_inline($genders);
  OutputClass::output("`^Gender: `@%s`n",$genders[$target['sex']]);

  $specialties = Modules::modulehook("specialtynames",
		  array(""=>Translator::translate_inline("Unspecified")));
  if (isset($specialties[$target['specialty']])) {
		OutputClass::output("`^Specialty: `@%s`n",$specialties[$target['specialty']]);
  }
  $sql = "SELECT * FROM " . db_prefix("mounts") . " WHERE mountid='{$target['hashorse']}'";
  $result = db_query_cached($sql, "mountdata-{$target['hashorse']}", 3600);
  $mount = db_fetch_assoc($result);

  $mount['acctid']=$target['acctid'];
  $mount = Modules::modulehook("bio-mount",$mount);
  $none = Translator::translate_inline("`iNone`i");
  if (!isset($mount['mountname']) || $mount['mountname']=="")
		  $mount['mountname'] = $none;
  OutputClass::output("`^Creature: `@%s`0`n",$mount['mountname']);

  Modules::modulehook("biostat", $target);

  if ($target['dragonkills']>0)
	  OutputClass::output("`^Dragon Kills: `@%s`n",$target['dragonkills']);

  if ($target['bio']>"")
	  OutputClass::output("`^Bio: `@`n%s`n",Censor::soap($target['bio']));

  Modules::modulehook("bioinfo", $target);

  OutputClass::output("`n`^Recent accomplishments (and defeats) of %s`^",$target['name']);
  $result = db_query("SELECT * FROM " . db_prefix("news") . " WHERE accountid={$target['acctid']} ORDER BY newsdate DESC,newsid ASC LIMIT 100");

  $odate="";
  Translator::tlschema("news");
  while ($row = db_fetch_assoc($result)) {
	  Translator::tlschema($row['Translator::tlschema']);
	  if ($row['arguments'] > "") {
		  $arguments = array();
		  $base_arguments = unserialize($row['arguments']);
		  array_push($arguments, $row['newstext']);
		  while(list($key, $val) = each($base_arguments)) {
			  array_push($arguments, $val);
		  }
		  $news = call_user_func_array("Translator::sprintf_translate", $arguments);
		  OutputClass::rawoutput(Translator::tlbutton_clear());
	  } else {
		  $news = Translator::translate_inline($row['newstext']);
		  OutputClass::rawoutput(Translator::tlbutton_clear());
	  }
	  Translator::tlschema();
	  if ($odate!=$row['newsdate']){
		  OutputClass::output_notl("`n`b`@%s`0`b`n",
				  date("D, M d", strtotime($row['newsdate'])));
		  $odate=$row['newsdate'];
	  }
	  OutputClass::output_notl("`@$news`0`n");
  }
  Translator::tlschema();

  if ($ret==""){
	  $return = substr($return,strrpos($return,"/")+1);
	  Translator::tlschema("nav");
	  OutputClass::addnav("Return");
	  OutputClass::addnav("Return to the warrior list",$return);
	  Translator::tlschema();
  }else{
	  $return = substr($return,strrpos($return,"/")+1);
	  Translator::tlschema("nav");
	  OutputClass::addnav("Return");
	  if ($return=="list.php") {
		  OutputClass::addnav("Return to the warrior list",$return);
	  } else {
		  OutputClass::addnav("Return whence you came",$return);
	  }
	  Translator::tlschema();
  }

  Modules::modulehook("bioend", $target);
  PageParts::page_footer();
} else {
	PageParts::page_header("Character has been deleted");
	OutputClass::output("This character is already deleted.");
  if ($ret==""){
	  $return = substr($return,strrpos($return,"/")+1);
	  Translator::tlschema("nav");
	  OutputClass::addnav("Return");
	  OutputClass::addnav("Return to the warrior list",$return);
	  Translator::tlschema();
  }else{
	  $return = substr($return,strrpos($return,"/")+1);
	  Translator::tlschema("nav");
	  OutputClass::addnav("Return");
	  if ($return=="list.php") {
		  OutputClass::addnav("Return to the warrior list",$return);
	  } else {
		  OutputClass::addnav("Return whence you came",$return);
	  }
	  Translator::tlschema();
  }
	PageParts::page_footer();
}
?>