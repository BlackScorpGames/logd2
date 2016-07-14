<?php
require_once("lib/installer/installer_sqlstatements.php");
require_once("lib/installer/installer_functions.php");
require_once("lib/installer/installer_default_settings.php");
OutputClass::output("`@`c`bBuilding the Tables`b`c");
OutputClass::output("`2I'm now going to build the tables.");
OutputClass::output("If this is an upgrade, your current tables will be brought in line with the current version.");
OutputClass::output("If it's an install, the necessary tables will be placed in your database.`n");
OutputClass::output("`n`@Table Synchronization Logs:`n");
rawoutput("<div style='width: 100%; height: 150px; max-height: 150px; overflow: auto;'>");
$descriptors = descriptors($DB_PREFIX);
require_once("lib/tabledescriptor.php");
reset($descriptors);
while (list($tablename,$descriptor)=each($descriptors)){
	OutputClass::output("`3Synchronizing table `#$tablename`3..`n");
	synctable($tablename,$descriptor,true);
	if ($session['dbinfo']['upgrade']==false){
		//on a clean install, destroy all old data.
		db_query("TRUNCATE TABLE $tablename");
	}
}
rawoutput("</div>");
OutputClass::output("`n`2The tables now have new fields and columns added, I'm going to begin importing data now.`n");
rawoutput("<div style='width: 100%; height: 150px; max-height: 150px; overflow: auto;'>");
$dosql = false;
reset($sql_upgrade_statements);
while (list($key,$val)=each($sql_upgrade_statements)){
	if ($dosql){
		OutputClass::output("`3Version `#%s`3: %s SQL statements...`n",$key,count($val));
		if (count($val)>0){
			OutputClass::output("`^Doing: `6");
			reset($val);
			$count=0;
			while (list($id,$sql)=each($val)){
				$onlyupgrade = 0;
				if (substr($sql, 0, 2) == "1|") {
					$sql = substr($sql, 2);
					$onlyupgrade = 1;
				}
				// Skip any statements that should only be run during
				// upgrades from previous versions.
				if (!$session['dbinfo']['upgrade'] && $onlyupgrade) {
					continue;
				}
				$count++;
				if ($count%10==0 && $count!=count($val))
				output_notl("`6$count...");
				if (!db_query($sql)) {
					OutputClass::output("`n`\$Error: `^'%s'`7 executing `#'%s'`7.`n",
					db_error(), $sql);
				}
			}
			OutputClass::output("$count.`n");
		}
	}
	if ($key == $session['fromversion'] ||
	$session['dbinfo']['upgrade'] == false) $dosql=true;
}
rawoutput("</div>");
OutputClass::output("Now I'm going to insert default settings that you don't have.");
rawoutput("<div style='width: 100%; height: 150px; max-height: 150px; overflow: auto;'>");
foreach ($default_settings as $setting_name=>$setting_value) {
	if(!isset($settings[$setting_name]) && getsetting($setting_name, $setting_value) == $setting_value) {
		if ($setting_value === true) {
			$setting_value = "true";
		}elseif ($setting_value === false) {
			$setting_value = "false";
		}
		output_notl("Setting $setting_name to default value of $setting_value`n");
	}
}
rawoutput("</div>");
	/*
OutputClass::output("`n`2Now I'll install the recommended modules.");
OutputClass::output("Please note that these modules will be installed, but not activated.");
OutputClass::output("Once installation is complete, you should use the Module Manager found in the superuser grotto to activate those modules you wish to use.");
reset($recommended_modules);
rawoutput("<div style='width: 100%; height: 150px; max-height: 150px; overflow: auto;'>");
while (list($key,$modulename)=each($recommended_modules)){
OutputClass::output("`3Installing `#$modulename`\$`n");
install_module($modulename, false);
}
rawoutput("</div>");
*/
if (!$session['skipmodules']) {
  OutputClass::output("`n`2Now I'll install and configure your modules.");
  rawoutput("<div style='width: 100%; height: 150px; max-height: 150px; overflow: auto;'>");
  foreach($session['moduleoperations'] as $modulename=>$val){
	  $ops = explode(",",$val);
	  reset($ops);
	  while (list($trash,$op) = each($ops)){
		  switch($op){
			  case "uninstall":
			  OutputClass::output("`3Uninstalling `#$modulename`3: ");
			  if (uninstall_module($modulename)){
				  OutputClass::output("`@OK!`0`n");
			  }else{
				  OutputClass::output("`\$Failed!`0`n");
			  }
			  break;
			  case "install":
			  OutputClass::output("`3Installing `#$modulename`3: ");
			  if (install_module($modulename)){
				  OutputClass::output("`@OK!`0`n");
			  }else{
				  OutputClass::output("`\$Failed!`0`n");
			  }
			  install_module($modulename);
			  break;
			  case "activate":
			  OutputClass::output("`3Activating `#$modulename`3: ");
			  if (activate_module($modulename)){
				  OutputClass::output("`@OK!`0`n");
			  }else{
				  OutputClass::output("`\$Failed!`0`n");
			  }
			  break;
			  case "deactivate":
			  OutputClass::output("`3Deactivating `#$modulename`3: ");
			  if (deactivate_module($modulename)){
				  OutputClass::output("`@OK!`0`n");
			  }else{
				  OutputClass::output("`\$Failed!`0`n");
			  }
			  break;
			  case "donothing":
			  break;
		  }
	  }
	  $session['moduleoperations'][$modulename] = "donothing";
  }
  rawoutput("</div>");
}
OutputClass::output("`n`2Finally, I'll clean up old data.`n");
rawoutput("<div style='width: 100%; height: 150px; max-height: 150px; overflow: auto;'>");
reset($descriptors);
while (list($tablename,$descriptor)=each($descriptors)){
	OutputClass::output("`3Cleaning up `#$tablename`3...`n");
	synctable($tablename,$descriptor);
}
rawoutput("</div>");
OutputClass::output("`n`n`^You're ready for the next step.");
?>
