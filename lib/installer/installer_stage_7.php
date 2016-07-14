<?php
require("lib/installer/installer_sqlstatements.php");
if (httppost("type")>""){
	if (httppost("type")=="install") {
		$session['fromversion']="-1";
		$session['dbinfo']['upgrade']=false;
	}else{
		$session['fromversion']=httppost("version");
		$session['dbinfo']['upgrade']=true;
	}
}

if (!isset($session['fromversion']) || $session['fromversion']==""){
	OutputClass::output("`@`c`bConfirmation`b`c");
	OutputClass::output("`2Please confirm the following:`0`n");
	OutputClass::rawoutput("<form action='installer.php?stage=7' method='POST'>");
	OutputClass::rawoutput("<table border='0' cellpadding='0' cellspacing='0'><tr><td valign='top'>");
	OutputClass::output("`2I should:`0");
	OutputClass::rawoutput("</td><td>");
	$version = Settings::getsetting("installer_version","-1");
	if ($version != "-1") $session['dbinfo']['upgrade']=true;
	OutputClass::rawoutput("<input type='radio' value='upgrade' name='type'".($session['dbinfo']['upgrade']?" checked":"").">");
	OutputClass::output(" `2Perform an upgrade from ");
	if ($version=="-1") $version="0.9.7";
	reset($sql_upgrade_statements);
	OutputClass::rawoutput("<select name='version'>");
	while(list($key,$val)=each($sql_upgrade_statements)){
		if ($key!="-1"){
			OutputClass::rawoutput("<option value='$key'".($version==$key?" selected":"").">$key</option>");
		}
	}
	OutputClass::rawoutput("</select>");
	OutputClass::rawoutput("<br><input type='radio' value='install' name='type'".($session['dbinfo']['upgrade']?"":" checked").">");
	OutputClass::output(" `2Perform a clean install.");
	OutputClass::rawoutput("</td></tr></table>");
	$submit=Translator::translate_inline("Submit");
	OutputClass::rawoutput("<input type='submit' value='$submit' class='button'>");
	OutputClass::rawoutput("</form>");
	$session['stagecompleted']=$stage - 1;
}else{
	$session['stagecompleted']=$stage;
	header("Location: installer.php?stage=".($stage+1));
	exit();
}
?>
