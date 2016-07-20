<?php
$sql = "INSERT INTO " . db_prefix("bans") . " (banner,";
$type = Http::httppost("type");
if ($type=="ip"){
	$sql.="ipfilter";
}else{
	$sql.="uniqueid";
}
$sql.=",banexpire,banreason) VALUES ('" . addslashes($session['user']['name']) . "',";
if ($type=="ip"){
	$sql.="\"".Http::httppost("ip")."\"";
}else{
	$sql.="\"".Http::httppost("id")."\"";
}
$duration = (int)Http::httppost("duration");
if ($duration == 0) $duration="0000-00-00";
else $duration = date("Y-m-d", strtotime("+$duration days"));
	$sql.=",\"$duration\",";
$sql.="\"".Http::httppost("reason")."\")";
if ($type=="ip"){
	if (substr($_SERVER['REMOTE_ADDR'],0,strlen(Http::httppost("ip"))) ==
			Http::httppost("ip")){
		$sql = "";
		OutputClass::output("You don't really want to ban yourself now do you??");
		OutputClass::output("That's your own IP address!");
	}
}else{
	if ($_COOKIE['lgi']==Http::httppost("id")){
		$sql = "";
		OutputClass::output("You don't really want to ban yourself now do you??");
		OutputClass::output("That's your own ID!");
	}
}
if ($sql!=""){
	db_query($sql);
	OutputClass::output("%s ban rows entered.`n`n", db_affected_rows());
	OutputClass::output_notl("%s", db_error(LINK));
	DebugLogClass::debuglog("entered a ban: " .  ($type=="ip"?  "IP: ".Http::httppost("ip"): "ID: ".Http::httppost("id")) . " Ends after: $duration  Reason: \"" .  Http::httppost("reason")."\"");
}
?>