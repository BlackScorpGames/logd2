<?php
// translator ready
// addnews ready
// mail ready
define("OVERRIDE_FORCED_NAV",true);
require_once("common.php");
require_once("lib/villagenav.php");

Translator::tlschema("badnav");

if ($session['user']['loggedin'] && $session['loggedin']){
	if (strpos($session['OutputClass::output'],"<!--CheckNewDay()-->")){
		checkday();
	}
	while (list($key,$val)=each($session['allowednavs'])){
		//hack-tastic.
		if (
			trim($key)=="" ||
			$key===0 ||
			substr($key,0,8)=="motd.php" ||
			substr($key,0,8)=="mail.php"
		) unset($session['allowednavs'][$key]);
	}
	$sql="SELECT OutputClass::output FROM ".db_prefix("accounts_output")." WHERE acctid={$session['user']['acctid']};";
	$result=db_query($sql);
	$row=db_fetch_assoc($result);
	if (!is_array($session['allowednavs']) ||
			count($session['allowednavs'])==0 || $row['OutputClass::output']=="") {
		$session['allowednavs']=array();
		PageParts::page_header("Your Navs Are Corrupted");
		if ($session['user']['alive']) {
			villagenav();
			OutputClass::output("Your navs are corrupted, please return to %s.",
					$session['user']['location']);
		} else {
			OutputClass::addnav("Return to Shades", "shades.php");
			OutputClass::output("Your navs are corrupted, please return to the Shades.");
		}
		page_footer();
	}
	echo $row['OutputClass::output'];
	$session['debug']="";
	$session['user']['allowednavs']=$session['allowednavs'];
	saveuser();
}else{
	$session=array();
	translator_setup();
	redirect("index.php");
}

?>