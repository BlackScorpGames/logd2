<?php
$string="%";
$name = httppost('name');
for ($x=0;$x<strlen($name);$x++){
	$string .= substr($name,$x,1)."%";
}
$sql = "SELECT login,name,level FROM " . db_prefix("accounts") . " WHERE name LIKE '".addslashes($string)."' AND locked=0 ORDER BY level,login";
$result = db_query($sql);
if (db_num_rows($result)<=0){
	OutputClass::output("`\$%s`) could find no one who matched the name you gave him.",$deathoverlord);
}elseif(db_num_rows($result)>100){
	OutputClass::output("`\$%s`) thinks you should narrow down the number of people you wish to haunt.",$deathoverlord);
	$search = Translator::translate_inline("Search");
	OutputClass::rawoutput("<form action='graveyard.php?op=haunt2' method='POST'>");
	OutputClass::addnav("","graveyard.php?op=haunt2");
	OutputClass::output("Who would you like to haunt? ");
	OutputClass::rawoutput("<input name='name' id='name'>");
	OutputClass::rawoutput("<input type='submit' class='button' value='$search'>");
	OutputClass::rawoutput("</form>");
	OutputClass::rawoutput("<script language='JavaScript'>document.getElementById('name').focus()</script>",true);
}else{
	OutputClass::output("`\$%s`) will allow you to try to haunt these people:`n",$deathoverlord);
	$name = Translator::translate_inline("Name");
	$lev = Translator::translate_inline("Level");
	OutputClass::rawoutput("<table cellpadding='3' cellspacing='0' border='0'>");
	OutputClass::rawoutput("<tr class='trhead'><td>$name</td><td>$lev</td></tr>");
	for ($i=0;$i<db_num_rows($result);$i++){
		$row = db_fetch_assoc($result);
		OutputClass::rawoutput("<tr class='".($i%2?"trlight":"trdark")."'><td><a href='graveyard.php?op=haunt3&name=".HTMLEntities($row['login'], ENT_COMPAT, Settings::getsetting("charset", "ISO-8859-1"))."'>");
		OutputClass::output_notl("%s", $row['name']);
		OutputClass::rawoutput("</a></td><td>");
		OutputClass::output_notl("%s", $row['level']);
		OutputClass::rawoutput("</td></tr>",true);
		OutputClass::addnav("","graveyard.php?op=haunt3&name=".HTMLEntities($row['login'], ENT_COMPAT, Settings::getsetting("charset", "ISO-8859-1")));
	}
	OutputClass::rawoutput("</table>",true);
}
OutputClass::addnav(array("Question `\$%s`0 about the worth of your soul",$deathoverlord),"graveyard.php?op=question");
$max = $session['user']['level'] * 5 + 50;
$favortoheal = round(10 * ($max-$session['user']['soulpoints'])/$max);
OutputClass::addnav(array("Restore Your Soul (%s favor)",$favortoheal),"graveyard.php?op=restore");
OutputClass::addnav("Places");
OutputClass::addnav("S?Land of the Shades","shades.php");
OutputClass::addnav("G?The Graveyard","graveyard.php");
OutputClass::addnav("M?Return to the Mausoleum","graveyard.php?op=enter");
?>