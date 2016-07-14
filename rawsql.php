<?php
// translator ready
// addnews ready
// mail ready
require_once("common.php");
require_once("lib/http.php");

Translator::tlschema("rawsql");

check_su_access(SU_RAW_SQL);

PageParts::page_header("Raw SQL/PHP execution");
require_once("lib/superusernav.php");
superusernav();
OutputClass::addnav("Execution");
OutputClass::addnav("SQL","rawsql.php");
OutputClass::addnav("PHP","rawsql.php?op=php");

$op = Http::httpget("op");
if ($op=="" || $op=="sql"){
	$sql = httppost('sql');
	if ($sql != "") {
		$sql = stripslashes($sql);
		Modules::modulehook("rawsql-execsql",array("sql"=>$sql));
		$r = db_query($sql, false);
		debuglog('Ran Raw SQL: ' . $sql);
		if (!$r) {
			OutputClass::output("`\$SQL Error:`& %s`0`n`n",db_error($r));
		} else {
			if (db_affected_rows() > 0) {
				OutputClass::output("`&%s rows affected.`n`n",db_affected_rows());
			}
			OutputClass::rawoutput("<table cellspacing='1' cellpadding='2' border='0' bgcolor='#999999'>");
			$number = db_num_rows($r);
			for ($i = 0; $i < $number; $i++) {
				$row = db_fetch_assoc($r);
				if ($i == 0) {
					OutputClass::rawoutput("<tr class='trhead'>");
					$keys = array_keys($row);
					foreach ($keys as $value) {
						OutputClass::rawoutput("<td>$value</td>");
					}
					OutputClass::rawoutput("</tr>");
				}
				OutputClass::rawoutput("<tr class='".($i%2==0?"trlight":"trdark")."'>");
				foreach ($keys as $value) {
					OutputClass::rawoutput("<td valign='top'>{$row[$value]}</td>");
				}
				OutputClass::rawoutput("</tr>");
			}
			OutputClass::rawoutput("</table>");
		}
	}

	OutputClass::output("Type your query");
	$execute = Translator::translate_inline("Execute");
	$ret = Modules::modulehook("rawsql-modsql",array("sql"=>$sql));
	$sql = $ret['sql'];
	OutputClass::rawoutput("<form action='rawsql.php' method='post'>");
	OutputClass::rawoutput("<textarea name='sql' class='input' cols='60' rows='10'>".htmlentities($sql, ENT_COMPAT, Settings::getsetting("charset", "ISO-8859-1"))."</textarea><br>");
	OutputClass::rawoutput("<input type='submit' class='button' value='$execute'>");
	OutputClass::rawoutput("</form>");
	OutputClass::addnav("", "rawsql.php");
}else{
	$php = stripslashes(httppost("php"));
	$source = Translator::translate_inline("Source:");
	$execute = Translator::translate_inline("Execute");
	if ($php>""){
		OutputClass::rawoutput("<div style='background-color: #FFFFFF; color: #000000; width: 100%'><b>$source</b><br>");
		OutputClass::rawoutput(highlight_string("<?php\n$php\n?>",true));
		OutputClass::rawoutput("</div>");
		OutputClass::output("`bResults:`b`n");
		Modules::modulehook("rawsql-execphp",array("php"=>$php));
		ob_start();
		eval($php);
		OutputClass::output_notl(ob_get_contents(),true);
		ob_end_clean();
		debuglog('Ran Raw PHP: ' . $php);
	}
	OutputClass::output("`n`nType your code:");
	$ret = Modules::modulehook("rawsql-modphp",array("php"=>$php));
	$php = $ret['php'];
	OutputClass::rawoutput("<form action='rawsql.php?op=php' method='post'>");
	OutputClass::rawoutput("&lt;?php<br><textarea name='php' class='input' cols='60' rows='10'>".htmlentities($php, ENT_COMPAT, Settings::getsetting("charset", "ISO-8859-1"))."</textarea><br>?&gt;<br>");
	OutputClass::rawoutput("<input type='submit' class='button' value='$execute'>");
	OutputClass::rawoutput("</form>");
	OutputClass::addnav("", "rawsql.php?op=php");
}
PageParts::page_footer();
?>