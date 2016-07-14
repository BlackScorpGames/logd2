<?php
// translator ready
// addnews ready
// mail ready

require_once("common.php");
require_once("lib/commentary.php");
require_once("lib/http.php");

Translator::tlschema("petition");

check_su_access(SU_EDIT_PETITIONS);

Commentary::addcommentary();

//WHEN 0 THEN 2 WHEN 1 THEN 3 WHEN 2 THEN 7 WHEN 3 THEN 5 WHEN 4 THEN 1 WHEN 5 THEN 0 WHEN 6 THEN 4 WHEN 7 THEN 6
$statuses=array(
	5=>"`\$Top Level`0",
	4=>"`^Escalated`0",
	0=>"`bUnhandled`b",
	1=>"In-Progress",
	6=>"`%Bug`0",
	7=>"`#Awaiting Points`0",
	3=>"`!Informational`0",
	2=>"`iClosed`i",
	);

//$statuses = Modules::modulehook("petition-status", $status);
$statuses=Translator::translate_inline($statuses);

$op = Http::httpget("op");
$id = Http::httpget("id");

if (trim(httppost('insertcommentary'))!="") {
	/* Update the bug if someone adds comments as well */
	$sql = "UPDATE " . db_prefix("petitions") . " SET closeuserid='{$session['user']['acctid']}',closedate='".date("Y-m-d H:i:s")."' WHERE petitionid='$id'";
	db_query($sql);
}

// Eric decide he didn't want petitions to be manually deleted
//
//if ($op=="del"){
//	$sql = "DELETE FROM " . db_prefix("petitions") . " WHERE petitionid='$id'";
//	db_query($sql);
//	$sql = "DELETE FROM " . db_prefix("commentary") . " WHERE section='pet-$id'";
//	db_query($sql);
//	invalidatedatacache("petition_counts");
//	$op="";
//}
PageParts::page_header("Petition Viewer");
require_once("lib/superusernav.php");
superusernav();
if ($op==""){
	$sql = "DELETE FROM " . db_prefix("petitions") . " WHERE status=2 AND closedate<'".date("Y-m-d H:i:s",strtotime("-7 days"))."'";
	db_query($sql);
	if(db_affected_rows()) {
		invalidatedatacache("petition_counts");
	}
	$setstat = Http::httpget("setstat");
	if ($setstat!=""){
		$sql = "SELECT status FROM " . db_prefix("petitions") . " WHERE petitionid='$id'";
		$result = db_query($sql);
		$row = db_fetch_assoc($result);
		if ($row['status']!=$setstat){
			$sql = "UPDATE " . db_prefix("petitions") . " SET status='$setstat',closeuserid='{$session['user']['acctid']}',closedate='".date("Y-m-d H:i:s")."' WHERE petitionid='$id'";
			db_query($sql);
			invalidatedatacache("petition_counts");
		}
	}
	reset($statuses);
	$sort = "";
	$pos = 0;
	while (list($key,$val)=each($statuses)){
		$sort.=" WHEN $key THEN $pos";
		$pos++;
	}

	$petitionsperpage = 50;
	$sql = "SELECT count(petitionid) AS c from ".db_prefix("petitions");
	$result = db_query($sql);
	$row = db_fetch_assoc($result);
	$totalpages = ceil($row['c']/$petitionsperpage);

	$page = Http::httpget("page");
	if ($page == "") {
		if (isset($session['petitionPage'])){
			$page = (int)$session['petitionPage'];
		}else{
			$page = 1;
		}
	}
	if ($page < 1) $page = 1;
	if ($page > $totalpages) $page = $totalpages;
	$session['petitionPage'] = $page;

	// No need to show the pages if there is only one.
	if ($totalpages != 1)  {
		OutputClass::addnav("Page");
		for ($x=1; $x <= $totalpages; $x++){
			if ($page == $x){
				OutputClass::addnav(array("`b`#Page %s`0`b", $x),"viewpetition.php?page=$x");
			}else{
				OutputClass::addnav(array("Page %s", $x),"viewpetition.php?page=$x");
			}
		}
	}
	if ($page > 1){
		$limit = (($page-1) * $petitionsperpage) . "," . $petitionsperpage;
	}else{
		$limit = "$petitionsperpage";
	}

	$sql =
	"SELECT
		petitionid,
		".db_prefix("accounts").".name,
		".db_prefix("petitions").".date,
		".db_prefix("petitions").".status,
		".db_prefix("petitions").".body,
		".db_prefix("petitions").".closedate,
		accts.name AS closer,
		CASE status $sort END AS sortorder
	FROM
		".db_prefix("petitions")."
	LEFT JOIN
		".db_prefix("accounts")."
	ON	".db_prefix("accounts").".acctid=".db_prefix("petitions").".author
	LEFT JOIN
		".db_prefix("accounts")." AS accts
	ON	accts.acctid=".db_prefix("petitions").".closeuserid
	ORDER BY
		sortorder ASC,
		date ASC
	LIMIT $limit";
	$result = db_query($sql);
	OutputClass::addnav("Petitions");
	OutputClass::addnav("Refresh","viewpetition.php");
	$num = Translator::translate_inline("Num");
	$ops = Translator::translate_inline("Ops");
	$from = Translator::translate_inline("From");
	$sent = Translator::translate_inline("Sent");
	$com = Translator::translate_inline("Com");
	$last = Translator::translate_inline("Last Updater");
	$when = Translator::translate_inline("Updated");
	$view = Translator::translate_inline("View");
	$close = Translator::translate_inline("Close");
	$mark = Translator::translate_inline("Mark");

	OutputClass::rawoutput("<table border='0'><tr class='trhead'><td>$num</td><td>$ops</td><td>$from</td><td>$sent</td><td>$com</td><td>$last</td><td>$when</td></tr>");
	$i=0;
	$laststatus=-1;
	while($row = db_fetch_assoc($result)){
		$i++;
		$sql = "SELECT count(commentid) AS c FROM ". db_prefix("commentary") .  " WHERE section='pet-{$row['petitionid']}'";
		$res = db_query($sql);
		$counter = db_fetch_assoc($res);
		if (array_key_exists('status', $row) && $row['status']!=$laststatus){
			OutputClass::rawoutput("<tr class='".($i%2?"trlight":"trdark")."'>");
			OutputClass::rawoutput("<td colspan='7'>");
			OutputClass::output_notl("%s", $statuses[$row['status']]);
			OutputClass::rawoutput("</td></tr>");
			$i++;
			$laststatus=$row['status'];
		}
		OutputClass::rawoutput("<tr class='".($i%2?"trlight":"trdark")."'>");
		OutputClass::rawoutput("<td>");
		OutputClass::output_notl("%s", $row['petitionid']);
		OutputClass::rawoutput("</td>");
		OutputClass::rawoutput("<td nowrap>[ ");
		OutputClass::rawoutput("<a href='viewpetition.php?op=view&id={$row['petitionid']}'>$view</a>",true);
		OutputClass::rawoutput(" | <a href='viewpetition.php?setstat=2&id={$row['petitionid']}'>$close</a>");
		OutputClass::output_notl(" | %s: ", $mark);
		OutputClass::output_notl("<a href='viewpetition.php?setstat=0&id={$row['petitionid']}'>`b`&U`0`b</a>/",true);
		OutputClass::output_notl("<a href='viewpetition.php?setstat=1&id={$row['petitionid']}'>`7P`0</a>/",true);
		//OutputClass::output_notl("<a href='viewpetition.php?setstat=3&id={$row['petitionid']}'>`!I`0</a>/",true);
		OutputClass::output_notl("<a href='viewpetition.php?setstat=4&id={$row['petitionid']}'>`^E`0</a>",true);
		//OutputClass::output_notl("<a href='viewpetition.php?setstat=5&id={$row['petitionid']}'>`\$T`0</a>/",true);
		//OutputClass::output_notl("<a href='viewpetition.php?setstat=6&id={$row['petitionid']}'>`%B`0</a>/",true);
		//OutputClass::output_notl("<a href='viewpetition.php?setstat=7&id={$row['petitionid']}'>`#A`0</a>",true);
		OutputClass::rawoutput(" ]</td>");
		OutputClass::addnav("","viewpetition.php?op=view&id={$row['petitionid']}");
		OutputClass::addnav("","viewpetition.php?setstat=2&id={$row['petitionid']}");
		OutputClass::addnav("","viewpetition.php?setstat=0&id={$row['petitionid']}");
		OutputClass::addnav("","viewpetition.php?setstat=1&id={$row['petitionid']}");
		//OutputClass::addnav("","viewpetition.php?setstat=3&id={$row['petitionid']}");
		OutputClass::addnav("","viewpetition.php?setstat=4&id={$row['petitionid']}");
		//OutputClass::addnav("","viewpetition.php?setstat=5&id={$row['petitionid']}");
		//OutputClass::addnav("","viewpetition.php?setstat=6&id={$row['petitionid']}");
		//OutputClass::addnav("","viewpetition.php?setstat=7&id={$row['petitionid']}");
		OutputClass::rawoutput("<td>");
		if ($row['name']==""){
			$v = substr($row['body'],0,strpos($row['body'],"[email"));
			$v = preg_replace("'\\[PHPSESSID\\] = .*'", "", $v);
			$v = preg_replace("'[^a-zA-Z0-91234567890\\[\\]= @.!,?-]'","", $v);
			// Make sure we don't get something too large.. 50 chars max
			$v = substr($v, 0, 50);
			OutputClass::output_notl("`\$%s`0", $v);
		}else{
			OutputClass::output_notl("`&%s`0", $row['name']);
		}
		OutputClass::rawoutput("</td>");
		OutputClass::rawoutput("<td>");
		OutputClass::output_notl("`7%s`0", reltime(strtotime($row['date'])));
		OutputClass::rawoutput("</td>");
		OutputClass::rawoutput("<td>");
		OutputClass::output_notl("`#%s`0", $counter['c']);
		OutputClass::rawoutput("</td>");
		OutputClass::rawoutput("<td>");
		OutputClass::output_notl("`^%s`0", $row['closer']);
		OutputClass::rawoutput("</td>");
		OutputClass::rawoutput("<td>");
		if ($row['closedate'] != 0) OutputClass::output_notl("`7%s`0", reltime(strtotime($row['closedate'])));
		OutputClass::rawoutput("</td>");
		OutputClass::rawoutput("</tr>");
	}
	OutputClass::rawoutput("</table>");
	OutputClass::output("`i(Closed petitions will automatically delete themselves when they have been closed for 7 days)`i");
	OutputClass::output("`n`bKey:`b`n");
	OutputClass::rawoutput("<ul><li>");
	OutputClass::output("`\$T = Top Level`0 petitions are for petitions that only server operators can take care of.");
	OutputClass::rawoutput("</li><li>");
	OutputClass::output("`^E = Escalated`0 petitions deal with an issue you can't handle for yourself.");
	OutputClass::output("Mark it escalated so someone with more permissions than you can deal with it.");
	OutputClass::rawoutput("</li><li>");
	OutputClass::output("`b`&U = Unhandled`0`b: No one is currently working on this problem, and it has not been dealt with yet.");
	OutputClass::rawoutput("</li><li>");
	OutputClass::output("P = In-Progress petitions are probably being worked on by someone else, so please leave them be unless they have been around for some time.");
	OutputClass::rawoutput("</li><li>");
	OutputClass::output("`%B = Bug/Suggestion`0 petitions are petitions that detail mistakes, bugs, misspellings, or suggestions for the game.");
	OutputClass::rawoutput("</li><li>");
	OutputClass::output("`#A = Awaiting Points`0 stuff wot is dun and needz teh points added (this is mostly for lotgd.net).");
	OutputClass::rawoutput("</li><li>");
	OutputClass::output("`!I = Informational`0 petitions are just around for others to view, either nothing needed to be done with them, or their issue has been dealt with, but you feel other admins could benefit from reading it.");
	OutputClass::rawoutput("</li><li>");
	OutputClass::output("`iClosed`i petitions are for you have dealt with an issue, these will auto delete when they have been closed for 7 days.");
	Modules::modulehook("petitions-descriptions", array());
	OutputClass::rawoutput("</li></ul>");
}elseif($op=="view"){
	$viewpageinfo = (int)Http::httpget("viewpageinfo");
	if ($viewpageinfo==1){
		OutputClass::addnav("Hide Details","viewpetition.php?op=view&id=$id}");
	}else{
		OutputClass::addnav("D?Show Details","viewpetition.php?op=view&id=$id&viewpageinfo=1");
	}
	OutputClass::addnav("V?Petition Viewer","viewpetition.php");

	OutputClass::addnav("User Ops");

	OutputClass::addnav("Petition Ops");
	reset($statuses);
	while (list($key,$val)=each($statuses)){
		$plain = full_sanitize($val);
		OutputClass::addnav(array("%s?Mark %s", substr($plain,0,1), $val),
				"viewpetition.php?setstat=$key&id=$id");
	}

	$sql = "SELECT " . db_prefix("accounts") . ".name," .  db_prefix("accounts") . ".login," .  db_prefix("accounts") . ".acctid," .  "date,closedate,status,petitionid,ip,body,pageinfo," .  "accts.name AS closer FROM " .  db_prefix("petitions") . " LEFT JOIN " .  db_prefix("accounts ") . "ON " .  db_prefix("accounts") . ".acctid=author LEFT JOIN " .  db_prefix("accounts") . " AS accts ON accts.acctid=".  "closeuserid WHERE petitionid='$id' ORDER BY date ASC";
	$result = db_query($sql);
	$row = db_fetch_assoc($result);
	OutputClass::addnav("User Ops");
	if (isset($row['login'])) {
		OutputClass::addnav("View User Biography","bio.php?char=" . $row['acctid']
						. "&ret=%2Fviewpetition.php%3Fop%3Dview%26id=" . $id);
	}
	if ($row['acctid']>0 && $session['user']['superuser'] & SU_EDIT_USERS){
		OutputClass::addnav("User Ops");
		OutputClass::addnav("R?Edit User Record","user.php?op=edit&userid={$row['acctid']}&returnpetition=$id");
	}
	if ($row['acctid']>0 && $session['user']['superuser'] & SU_EDIT_DONATIONS){
		OutputClass::addnav("User Ops");
		OutputClass::addnav("Edit User Donations","donators.php?op=add1&name=".rawurlencode($row['login'])."&ret=".urlencode($_SERVER['REQUEST_URI']));
	}
	$write = Translator::translate_inline("Write Mail");
	// We assume that petitions are handled in default language
	$yourpeti = translate_mail("Your Petition",0);
	$peti = translate_mail("Petition",0);
	$row['body'] = str_replace("[charname]",translate_mail("[charname]",0),$row['body']);
	$row['body'] = str_replace("[email]",translate_mail("[email]",0),$row['body']);
	$row['body'] = str_replace("[description]",translate_mail("[description]",0),$row['body']);
	// For email replies, make sure we don't overflow the URI buffer.
	$reppet = substr(stripslashes($row['body']), 0, 2000);
	OutputClass::output("`@From: ");
	if ($row['login']>"") {
		OutputClass::rawoutput("<a href=\"mail.php?op=write&to=".rawurlencode($row['login'])."&body=".rawurlencode("\n\n----- $yourpeti -----\n$reppet")."&subject=RE:+$peti\" target=\"_blank\" onClick=\"".popup("mail.php?op=write&to=".rawurlencode($row['login'])."&body=".rawurlencode("\n\n----- $yourpeti -----\n$reppet")."&subject=RE:+$peti").";return false;\"><img src='images/newscroll.GIF' width='16' height='16' alt='$write' border='0'></a>");
	}
	OutputClass::output_notl("`^`b%s`b`n", $row['name']);
	OutputClass::output("`@Date: `^`b%s`b (%s)`n", $row['date'], reltime(strtotime($row['date'])));
	OutputClass::output("`@Status: %s`n", $statuses[$row['status']]);
	if($row['closedate']) OutputClass::output("`@Last Update: `^%s`@ on `^%s (%s)`n", $row['closer'], $row['closedate'],  reltime(strtotime($row['closedate'])));
	OutputClass::output("`@Body:`^`n");
	OutputClass::output("`\$[ipaddress] `^= `#%s`^`n", $row['ip']);
	$body = htmlentities(stripslashes($row['body']), ENT_COMPAT, Settings::getsetting("charset", "ISO-8859-1"));
	$body = preg_replace("'([[:alnum:]_.-]+[@][[:alnum:]_.-]{2,}([.][[:alnum:]_.-]{2,})+)'i","<a href='mailto:\\1?subject=RE: $peti&body=".str_replace("+"," ",URLEncode("\n\n----- $yourpeti -----\n".$row['body']))."'>\\1</a>",$body);
	$body = preg_replace("'([\\[][[:alnum:]_.-]+[\\]])'i","<span class='colLtRed'>\\1</span>",$body);
	OutputClass::rawoutput("<span style='font-family: fixed-width'>".nl2br($body)."</span>");
	commentdisplay("`n`@Commentary:`0`n", "pet-$id","Add information",200);
	if ($viewpageinfo){
		OutputClass::output("`n`n`@Page Info:`&`n");
		$row['pageinfo']=stripslashes($row['pageinfo']);
		$body = HTMLEntities($row['pageinfo'], ENT_COMPAT, Settings::getsetting("charset", "ISO-8859-1"));
		$body = preg_replace("'([[:alnum:]_.-]+[@][[:alnum:]_.-]{2,}([.][[:alnum:]_.-]{2,})+)'i","<a href='mailto:\\1?subject=RE: $peti&body=".str_replace("+"," ",URLEncode("\n\n----- $yourpeti -----\n".$row['body']))."'>\\1</a>",$body);
		$body = preg_replace("'([\\[][[:alnum:]_.-]+[\\]])'i","<span class='colLtRed'>\\1</span>",$body);
		OutputClass::rawoutput("<span style='font-family: fixed-width'>".nl2br($body)."</span>");
	}
}

if ($id && $op != ""){
	$prevsql="SELECT p1.petitionid, p1.status FROM ".db_prefix("petitions")." AS p1, ".db_prefix("petitions")." AS p2
			WHERE p1.petitionid<'$id' AND p2.petitionid='$id' AND p1.status=p2.status ORDER BY p1.petitionid DESC LIMIT 1";
	$prevresult=db_query($prevsql);
	$prevrow=db_fetch_assoc($prevresult);
	if ($prevrow){
		$previd=$prevrow['petitionid'];
		$s=$prevrow['status'];
		$status=$statuses[$s];
		OutputClass::addnav("Navigation");
		OutputClass::addnav(array("Previous %s",$status),"viewpetition.php?op=view&id=$previd");
	}
	$nextsql="SELECT p1.petitionid, p1.status FROM ".db_prefix("petitions")." AS p1, ".db_prefix("petitions")." AS p2
			WHERE p1.petitionid>'$id' AND p2.petitionid='$id' AND p1.status=p2.status ORDER BY p1.petitionid ASC LIMIT 1";
	$nextresult=db_query($nextsql);
	$nextrow=db_fetch_assoc($nextresult);
	if ($nextrow){
		$nextid=$nextrow['petitionid'];
		$s=$nextrow['status'];
		$status=$statuses[$s];
		OutputClass::addnav("Navigation");
		OutputClass::addnav(array("Next %s",$status),"viewpetition.php?op=view&id=$nextid");
	}
}
PageParts::page_footer();
?>