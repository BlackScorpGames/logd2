<?php
// translator ready
// addnews ready
// mail ready

define("ALLOW_ANONYMOUS",true);
define("OVERRIDE_FORCED_NAV",true);

require_once("lib/http.php");

require_once("common.php");
require_once("lib/dump_item.php");
require_once("lib/modules.php");
require_once("lib/villagenav.php");

if (injectmodule(Http::httpget('module'), (Http::httpget('admin')?true:false))){
	$info = get_module_info(Http::httpget('module'));
	if (!isset($info['allowanonymous'])){
		$allowanonymous=false;
	}else{
		$allowanonymous = $info['allowanonymous'];
	}
	if (!isset($info['override_forced_nav'])){
		$override_forced_nav=false;
	}else{
		$override_forced_nav=$info['override_forced_nav'];
	}
	do_forced_nav($allowanonymous,$override_forced_nav);

	$starttime = getmicrotime();
	$fname = $mostrecentmodule."_run";
	Translator::tlschema("module-$mostrecentmodule");
	$fname();
	$endtime = getmicrotime();
	if (($endtime - $starttime >= 1.00 && ($session['user']['superuser'] & SU_DEBUG_OUTPUT))){
		OutputClass::debug("Slow Module (".round($endtime-$starttime,2)."s): $mostrecentmodule`n");
	}
	Translator::tlschema();
}else{
	do_forced_nav(false,false);

	Translator::tlschema("badnav");

	PageParts::page_header("Error");
	if ($session['user']['loggedin']){
		VillageNavClass::villagenav();
	}else{
		OutputClass::addnav("L?Return to the Login","index.php");
	}
	OutputClass::output("You are attempting to use a module which is no longer active, or has been uninstalled.");
	PageParts::page_footer();
}
?>