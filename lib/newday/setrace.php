<?php
$setrace = Http::httpget("setrace");
if ($setrace!=""){
	$vname = Settings::getsetting("villagename", LOCATION_FIELDS);
	//in case the module wants to reference it this way.
	$session['user']['race']=$setrace;
	// Set the person to the main village/capital by default
	$session['user']['location'] = $vname;
	modulehook("setrace");
	addnav("Continue","newday.php?continue=1$resline");
}else{
	OutputClass::output("Where do you recall growing up?`n`n");
	modulehook("chooserace");
}
if (navcount()==0){
	clearoutput();
	PageParts::page_header("No Races Installed");
	OutputClass::output("No races were installed in this game.");
	OutputClass::output("So we'll call you a 'human' and get on with it.");
	if ($session['user']['superuser'] & (SU_MEGAUSER|SU_MANAGE_MODULES)) {
		OutputClass::output("You should go into the module manager off of the super user grotto, install and activate some races.");
	} else {
		OutputClass::output("You might want to ask your admin to install some races, they're really quite fun.");
	}
	$session['user']['race']="Human";
	addnav("Continue","newday.php?continue=1$resline");
	page_footer();
}else{
	PageParts::page_header("A little history about yourself");
	page_footer();
}
?>