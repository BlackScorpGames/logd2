<?php
$setspecialty=Http::httpget('setspecialty');
if ($setspecialty != "") {
	$session['user']['specialty']=$setspecialty;
	Modules::modulehook("set-specialty");
	OutputClass::addnav("Continue","newday.php?continue=1$resline");
} else {
	PageParts::page_header("A little history about yourself");
	OutputClass::output("What do you recall doing as a child?`n`n");
	Modules::modulehook("choose-specialty");
}
if (navcount() == 0) {
	clearoutput();
	PageParts::page_header("No Specialties Installed");
	OutputClass::output("Since there are no suitable specialties available, we'll make you a student of the mystical powers and get on with it.");
	// This is someone who will definately have the rights to install
	// modules.
	if ($session['user']['superuser'] & (SU_MEGAUSER|SU_MANAGE_MODULES)) {
		OutputClass::output("You should go into the module manager off of the super user grotto, install and activate some specialties.");
	} else {
		OutputClass::output("You might want to ask your admin to install some specialties, as they are quite fun (and helpful).");
	}
	$session['user']['specialty'] = "MP";
	OutputClass::addnav("Continue","newday.php?continue=1$resline");
	page_footer();
}else{
	page_footer();
}
?>
