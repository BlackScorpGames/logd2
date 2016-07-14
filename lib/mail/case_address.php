<?php
output_notl("<form action='mail.php?op=write' method='post'>",true);
OutputClass::output("`b`2Address:`b`n");
$to = translate_inline("To: ");
$search = htmlentities(translate_inline("Search"), ENT_COMPAT, getsetting("charset", "ISO-8859-1"));
output_notl("`2$to <input name='to' id='to' value=\"".htmlentities(stripslashes(Http::httpget('prepop')), ENT_COMPAT, getsetting("charset", "ISO-8859-1"))."\">",true);
output_notl("<input type='submit' class='button' value=\"$search\">", true);
if ($session['user']['superuser'] & SU_IS_GAMEMASTER) {
	$from = translate_inline("From: ");
	output_notl("`n`2$from <input name='from' id='from'>`n", true);
	OutputClass::output("`7`iLeave empty to send from your account!`i");
}
rawoutput("</form>");
rawoutput("<script type='text/javascript'>document.getElementById(\"to\").focus();</script>");
?>