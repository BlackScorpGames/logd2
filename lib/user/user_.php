<?php
if ($display == 1){
	$q = "";
	if ($query) {
		$q = "&q=$query";
	}
	$ops=Translator::translate_inline("Ops");
	$acid =Translator::translate_inline("AcctID");
	$login =Translator::translate_inline("Login");
	$nm =Translator::translate_inline("Name");
	$lev =Translator::translate_inline("Level");
	$lon =Translator::translate_inline("Last On");
	$hits =Translator::translate_inline("Hits");
	$lip =Translator::translate_inline("Last IP");
	$lid =Translator::translate_inline("Last ID");
	$email =Translator::translate_inline("Email");
	$ed = Translator::translate_inline("Edit");
	$del = Translator::translate_inline("Del");
	$conf = Translator::translate_inline("Are you sure you wish to delete this user?");
	$ban = Translator::translate_inline("Ban");
	$log = Translator::translate_inline("Log");
		OutputClass::rawoutput("<table>");
	OutputClass::rawoutput("<tr class='trhead'><td>$ops</td><td><a href='user.php?sort=acctid$q'>$acid</a></td><td><a href='user.php?sort=login$q'>$login</a></td><td><a href='user.php?sort=name$q'>$nm</a></td><td><a href='user.php?sort=level$q'>$lev</a></td><td><a href='user.php?sort=laston$q'>$lon</a></td><td><a href='user.php?sort=gentimecount$q'>$hits</a></td><td><a href='user.php?sort=lastip$q'>$lip</a></td><td><a href='user.php?sort=uniqueid$q'>$lid</a></td><td><a href='user.php?sort=emailaddress$q'>$email</a></td></tr>");
	OutputClass::addnav("","user.php?sort=acctid$q");
	OutputClass::addnav("","user.php?sort=login$q");
	OutputClass::addnav("","user.php?sort=name$q");
	OutputClass::addnav("","user.php?sort=level$q");
	OutputClass::addnav("","user.php?sort=laston$q");
	OutputClass::addnav("","user.php?sort=gentimecount$q");
	OutputClass::addnav("","user.php?sort=lastip$q");
	OutputClass::addnav("","user.php?sort=uniqueid$q");
	$rn=0;
	$oorder = "";
	$number3=db_num_rows($searchresult);
	for ($i=0;$i<$number3;$i++){
		$row=db_fetch_assoc($searchresult);
		$laston = GameDateTime::relativedate($row['laston']);
		$loggedin =
			(date("U") - strtotime($row['laston']) <
			 Settings::getsetting("LOGINTIMEOUT",900) && $row['loggedin']);
		if ($loggedin)
			$laston=Translator::translate_inline("`#Online`0");
		$row['laston']=$laston;
		if ($row[$order]!=$oorder) $rn++;
		$oorder = $row[$order];
		OutputClass::rawoutput("<tr class='".($rn%2?"trlight":"trdark")."'>");
		OutputClass::rawoutput("<td nowrap>");
		OutputClass::rawoutput("[ <a href='user.php?op=edit&userid={$row['acctid']}$m'>$ed</a> | <a href='user.php?op=del&userid={$row['acctid']}' onClick=\"return confirm('$conf');\">$del</a> | <a href='user.php?op=setupban&userid={$row['acctid']}'>$ban</a> | <a href='user.php?op=debuglog&userid={$row['acctid']}'>$log</a> ]");
		OutputClass::addnav("","user.php?op=edit&userid={$row['acctid']}$m");
		OutputClass::addnav("","user.php?op=del&userid={$row['acctid']}");
		OutputClass::addnav("","user.php?op=setupban&userid={$row['acctid']}");
		OutputClass::addnav("","user.php?op=debuglog&userid={$row['acctid']}");
		OutputClass::rawoutput("</td><td>");
		OutputClass::output_notl("%s", $row['acctid']);
		OutputClass::rawoutput("</td><td>");
		OutputClass::output_notl("%s", $row['login']);
		OutputClass::rawoutput("</td><td>");
		OutputClass::output_notl("`&%s`0", $row['name']);
		OutputClass::rawoutput("</td><td>");
		OutputClass::output_notl("`^%s`0", $row['level']);
		OutputClass::rawoutput("</td><td>");
		OutputClass::output_notl("%s", $row['laston']);
		OutputClass::rawoutput("</td><td>");
		OutputClass::output_notl("%s", $row['gentimecount']);
		OutputClass::rawoutput("</td><td>");
		OutputClass::output_notl("%s", $row['lastip']);
		OutputClass::rawoutput("</td><td>");
		OutputClass::output_notl("%s", $row['uniqueid']);
		OutputClass::rawoutput("</td><td>");
		OutputClass::output_notl("%s", $row['emailaddress']);
		OutputClass::rawoutput("</td></tr>");
		$gentimecount+=$row['gentimecount'];
		$gentime+=$row['gentime'];
	}
	OutputClass::rawoutput("</table>");
	OutputClass::output("Total hits: %s`n", $gentimecount);
	OutputClass::output("Total CPU time: %s seconds`n", round($gentime,3));
	OutputClass::output("Average page gen time is %s seconds`n", round($gentime/max($gentimecount,1),4));
}
?>