<?php
// translator ready
// addnews ready
// mail ready
require_once("common.php");
require_once("lib/http.php");
require_once("lib/systemmail.php");

check_su_access(SU_EDIT_DONATIONS);

Translator::tlschema("donation");

PageParts::page_header("Donator's Page");
require_once("lib/superusernav.php");
superusernav();


$ret=Http::httpget('ret');
$return = cmd_sanitize($ret);
$return = substr($return,strrpos($return,"/")+1);
Translator::tlschema("nav");
addnav("Return whence you came",$return);
Translator::tlschema();

$add = Translator::translate_inline("Add Donation");
rawoutput("<form action='donators.php?op=add1&ret=".rawurlencode($ret)."' method='POST'>");
addnav("","donators.php?op=add1&ret=".rawurlencode($ret)."");
$name = httppost("name");
if ($name=="") $name = Http::httpget("name");
$amt = httppost("amt");
if ($amt=="") $amt = Http::httpget("amt");
$reason = httppost("reason");
if ($reason=="") $reason = Http::httpget("reason");
$txnid = httppost("txnid");
if ($txnid=="") $txnid = Http::httpget("txnid");
if ($reason == "") $reason = Translator::translate_inline("manual donation entry");


OutputClass::output("`bAdd Donation Points:`b`n");
OutputClass::output("Character: ");
rawoutput("<input name='name' value=\"".htmlentities($name, ENT_COMPAT, Settings::getsetting("charset", "ISO-8859-1"))."\">");
OutputClass::output("`nPoints: ");
rawoutput("<input name='amt' size='3' value=\"".htmlentities($amt, ENT_COMPAT, Settings::getsetting("charset", "ISO-8859-1"))."\">");
OutputClass::output("`nReason: ");
rawoutput("<input name='reason' size='30' value=\"".htmlentities($reason, ENT_COMPAT, Settings::getsetting("charset", "ISO-8859-1"))."\">");
rawoutput("<input type='hidden' name='txnid' value=\"".htmlentities($txnid, ENT_COMPAT, Settings::getsetting("charset", "ISO-8859-1"))."\">");
output_notl("`n");
if ($txnid>"") OutputClass::output("For transaction: %s`n",$txnid);
rawoutput("<input type='submit' class='button' value='$add'>");
rawoutput("</form>");

addnav("Donations");
if (($session['user']['superuser'] & SU_EDIT_PAYLOG) &&
		file_exists("paylog.php")){
	addnav("Payment Log","paylog.php");
}
$op = Http::httpget('op');
if ($op=="add2"){
	$id = Http::httpget('id');
	$amt = Http::httpget('amt');
	$reason = Http::httpget('reason');

	$sql="SELECT name FROM ".db_prefix("accounts")." WHERE acctid=$id;";
	$result=db_query($sql);
	$row=db_fetch_assoc($result);
	OutputClass::output("%s donation points added to %s`0, reason: `^%s`0",$amt,$row['name'],$reason);

	$txnid = Http::httpget("txnid");
	$ret = Http::httpget('ret');
	if ($id==$session['user']['acctid']){
		$session['user']['donation']+=$amt;
	}
	if ($txnid > ""){
		$result = modulehook("donation_adjustments",array("points"=>$amt,"amount"=>$amt/100,"acctid"=>$id,"messages"=>array()));
		$points = $result['points'];
		if (!is_array($result['messages'])){
			$result['messages'] = array($result['messages']);
		}
		foreach($result['messages'] as $messageid=>$message){
			debuglog($message,false,$id,"donation",0,false);
		}
	}else{
		$points = $amt;
	}
	// ok to execute when this is the current user, they'll overwrite the
	// value at the end of their page hit, and this will allow the display
	// table to update in real time.
	$sql = "UPDATE " . db_prefix("accounts") . " SET donation=donation+'$points' WHERE acctid='$id'";
	db_query($sql);
	modulehook("donation", array("id"=>$id, "amt"=>$points, "manual"=>($txnid>""?false:true)));

	if ($txnid>""){
		$sql = "UPDATE ".db_prefix("paylog")." SET acctid='$id', processed=1 WHERE txnid='$txnid'";
		db_query($sql);
		debuglog("Received donator points for donating -- Credited manually [$reason]",false,$id,"donation",$points,false);
		redirect("paylog.php");
	}else{
		debuglog("Received donator points -- Manually assigned, not based on a known dollar donation [$reason]",false,$id,"donation",$amt,false);
	}
	if ($points == 1) {
		systemmail($id,array("Donation Point Added"),array("`2You have received a donation point for %s.",$reason));
	}else {
		systemmail($id,array("Donation Points Added"),array("`2You have received %d donation points for %s.",$points,$reason));
	}
	httpset('op', "");
	$op = "";
}

if ($op==""){
	$sql = "SELECT name,donation,donationspent FROM " . db_prefix("accounts") . " WHERE donation>0 ORDER BY donation DESC LIMIT 25";
	$result = db_query($sql);

	$name = Translator::translate_inline("Name");
	$points = Translator::translate_inline("Points");
	$spent = Translator::translate_inline("Spent");

	rawoutput("<table border='0' cellpadding='3' cellspacing='1' bgcolor='#999999'>");
	rawoutput("<tr class='trhead'><td>$name</td><td>$points</td><td>$spent</td></tr>");
	$number=db_num_rows($result);
	for ($i=0;$i<$number;$i++){
		$row = db_fetch_assoc($result);
		rawoutput("<tr class='".($i%2?"trlight":"trdark")."'>");
		rawoutput("<td>");
		output_notl("`^%s`0",$row['name']);
		rawoutput("</td><td>");
		output_notl("`@%s`0", number_format($row['donation']));
		rawoutput("</td><td>");
		output_notl("`%%s`0", number_format($row['donationspent']));
		rawoutput("</td>");
		rawoutput("</tr>");
	}
	rawoutput("</table>",true);
}else if ($op=="add1"){
	$search="%";
	$name = httppost('name');
	if ($name=='') $name = Http::httpget('name');
	for ($i=0;$i<strlen($name);$i++){
		$z = substr($name, $i, 1);
		if ($z == "'") $z = "\\'";
		$search.=$z."%";
	}
	$sql = "SELECT name,acctid,donation,donationspent FROM " . db_prefix("accounts") . " WHERE login LIKE '$search' or name LIKE '$search' LIMIT 100";
	$result = db_query($sql);
	$ret = Http::httpget('ret');
	$amt = httppost('amt');
	if ($amt=='') $amt = Http::httpget("amt");
	$reason = httppost("reason");
	if ($reason=="") $reason = Http::httpget("reason");
	$txnid = httppost('txnid');
	if ($txnid=='') $txnid = Http::httpget("txnid");
	OutputClass::output("Confirm the addition of %s points to:`n",$amt);
	if ($reason) OutputClass::output("(Reason: `^`b`i%s`i`b`0)`n`n",$reason);
	$number=db_num_rows($result);
	for ($i=0;$i<$number;$i++){
		$row = db_fetch_assoc($result);
		if ($ret!=""){
			rawoutput("<a href='donators.php?op=add2&id={$row['acctid']}&amt=$amt&ret=".rawurlencode($ret)."&reason=".rawurlencode($reason)."'>");
		}else{
			rawoutput("<a href='donators.php?op=add2&id={$row['acctid']}&amt=$amt&reason=".rawurlencode($reason)."&txnid=$txnid'>");
		}
		output_notl("%s (%s/%s)", $row['name'], $row['donation'], $row['donationspent']);
		rawoutput("</a>");
		output_notl("`n");
		if ($ret!=""){
			addnav("","donators.php?op=add2&id={$row['acctid']}&amt=$amt&ret=".rawurlencode($ret)."&reason=".rawurlencode($reason));
		}else{
			addnav("","donators.php?op=add2&id={$row['acctid']}&amt=$amt&reason=".rawurlencode($reason)."&txnid=$txnid");
		}
	}
}
page_footer();
?>