<?php
require_once("lib/installer/installer_functions.php");
if (array_key_exists('modules',$_POST)){
	$session['moduleoperations'] = $_POST['modules'];
	$session['stagecompleted'] = $stage;
	header("Location: installer.php?stage=".($stage+1));
	exit();
}elseif (array_key_exists('moduleoperations',$session) && is_array($session['moduleoperations'])){
	$session['stagecompleted'] = $stage;
}else{
	$session['stagecompleted'] = $stage - 1;
}
OutputClass::output("`@`c`bManage Modules`b`c");
OutputClass::output("Legend of the Green Dragon supports an extensive module system.");
OutputClass::output("Modules are small self-contained files that perform a specific function or event within the game.");
OutputClass::output("For the most part, modules are independant of each other, meaning that one module can be installed, uninstalled, activated, and deactivated without negative impact on the rest of the game.");
OutputClass::output("Not all modules are ideal for all sites, for example, there's a module called 'Multiple Cities,' which is intended only for large sites with many users online at the same time.");
OutputClass::output("`n`n`^If you are not familiar with Legend of the Green Dragon, and how the game is played, it is probably wisest to choose the default set of modules to be installed.");
OutputClass::output("`n`n`@There is an extensive community of users who write modules for LoGD at <a href='http://dragonprime.net/'>http://dragonprime.net/</a>.",true);
$phpram = ini_get("memory_limit");
if (return_bytes($phpram) < 12582912 && $phpram!=-1 && !$session['overridememorylimit'] && !$session['dbinfo']['upgrade']) {// 12 MBytes
	// enter this ONLY if it's not an upgrade and if the limit is really too low
	OutputClass::output("`n`n`\$Warning: Your PHP memory limit is set to a very low level.");
	OutputClass::output("Smaller servers should not be affected by this during normal gameplay but for this installation step you should assign at least 12 Megabytes of RAM for your PHP process.");
	OutputClass::output("For now we will skip this step, but before installing any module, make sure to increase you memory limit.");
	OutputClass::output("`nYou can proceed at your own risk. Be aware that a blank screen indicates you *must* increase the memory limit.");
	OutputClass::output("`n`nTo override click again on \"Set Up Modules\".");
	$session['stagecompleted'] = "8";
	$session['overridememorylimit'] = true;
	$session['skipmodules'] = true;
} else {
	if (isset($session['overridememorylimit']) && $session['overridememorylimit']) {
		OutputClass::output("`4`n`nYou have been warned... you are now working on your own risk.`n`n");
		$session['skipmodules'] = false;
	}
	$submit = Translator::translate_inline("Save Module Settings");
	$install = Translator::translate_inline("Select Recommended Modules");
	$reset = Translator::translate_inline("Reset Values");
	$all_modules = array();
	$sql = "SELECT * FROM ".db_prefix("modules")." ORDER BY category,active DESC,formalname";
	$result = @db_query($sql);
	if ($result!==false){
		while ($row = db_fetch_assoc($result)){
			if (!array_key_exists($row['category'],$all_modules)){
				$all_modules[$row['category']] = array();
			}
			$row['installed']=true;
			$all_modules[$row['category']][$row['modulename']] = $row;
		}
	}
	$install_status = Modules::get_module_install_status();
		$uninstalled = $install_status['uninstalledmodules'];
	reset($uninstalled);
	$invalidmodule = array(
				"version"=>"",
				"author"=>"",
				"category"=>"Invalid Modules",
				"download"=>"",
				"description"=>"",
				"invalid"=>true,
			);
	while (list($key,$modulename) = each($uninstalled)){
		$row = array();
		//test if the file is a valid module or a lib file/whatever that got in, maybe even malcode that does not have module form
		$modulenamelower = strtolower($modulename);
		$file = strtolower(file_get_contents("modules/$modulename.php"));
		if (strpos($file,$modulenamelower."_getmoduleinfo")===false ||
			//strpos($file,$shortname."_dohook")===false ||
			//do_hook is not a necessity
			strpos($file,$modulenamelower."_install")===false ||
			strpos($file,$modulenamelower."_uninstall")===false) {
			//here the files has neither do_hook nor getinfo, which means it won't execute as a module here --> block it + notify the admin who is the manage modules section
			$moduleinfo=array_merge($invalidmodule,array("name"=>$modulename.".php ".OutputClass::appoencode(Translator::translate_inline("(`\$Invalid Module! Contact Author or check file!`0)"))));
		} else {
			$moduleinfo= Modules::get_module_info($modulename);
		}
		//end of testing
		$row['installed'] = false;
		$row['active'] = false;
		$row['category'] = $moduleinfo['category'];
		$row['modulename'] = $modulename;
		$row['formalname'] = $moduleinfo['name'];
		$row['description'] = $moduleinfo['description'];
		$row['moduleauthor'] = $moduleinfo['author'];
		$row['invalid'] = (isset($moduleinfo['invalid']))?$moduleinfo['invalid']:false;
		if (!array_key_exists($row['category'],$all_modules)){
			$all_modules[$row['category']] = array();
		}
		$all_modules[$row['category']][$row['modulename']] = $row;
	}
	if (count($all_modules) == 0) {
		$session['skipmodules'] = true;
		$session['stagecompleted'] = $stage;
		header("Location: installer.php?stage=".($stage+1));
		exit();
	}
	OutputClass::output_notl("`0");
	OutputClass::rawoutput("<form action='installer.php?stage=".$stage."' method='POST'>");
	OutputClass::rawoutput("<input type='submit' value='$submit' class='button'>");
	OutputClass::rawoutput("<input type='button' onClick='chooseRecommendedModules();' class='button' value='$install' class='button'>");
	OutputClass::rawoutput("<input type='reset' value='$reset' class='button'><br>");
	OutputClass::rawoutput("<table cellpadding='1' cellspacing='1'>");
	ksort($all_modules);
	reset($all_modules);
	$x=0;
	while (list($categoryName,$categoryItems)=each($all_modules)){
		OutputClass::rawoutput("<tr class='trhead'><td colspan='6'>".Translator::tl($categoryName)."</td></tr>");
		OutputClass::rawoutput("<tr class='trhead'><td>".Translator::tl("Uninstalled")."</td><td>".Translator::tl("Installed")."</td><td>".Translator::tl("Activated")."</td><td>".Translator::tl("Recommended")."</td><td>".Translator::tl("Module Name")."</td><td>".Translator::tl("Author")."</td></tr>");
		reset($categoryItems);
		while (list($modulename,$moduleinfo)=each($categoryItems)){
			$x++;
			//if we specified things in a previous hit on this page, let's update the modules array here as we go along.
			$moduleinfo['realactive'] = $moduleinfo['active'];
			$moduleinfo['realinstalled'] = $moduleinfo['installed'];
			if (array_key_exists('moduleoperations',$session) && is_array($session['moduleoperations']) && array_key_exists($modulename,$session['moduleoperations'])){
				$ops = explode(",",$session['moduleoperations'][$modulename]);
				reset($ops);
				while (list($trash,$op) = each($ops)){
					switch($op){
						case "uninstall":
						$moduleinfo['installed'] = false;
						$moduleinfo['active'] = false;
						break;
						case "install":
						$moduleinfo['installed'] = true;
						$moduleinfo['active'] = false;
						break;
						case "activate":
						$moduleinfo['installed'] = true;
						$moduleinfo['active'] = true;
						break;
						case "deactivate":
						$moduleinfo['installed'] = true;
						$moduleinfo['active'] = false;
						break;
						case "donothing":
						break;
					}
				}
			}
			OutputClass::rawoutput("<tr class='".($x%2?"trlight":"trdark")."'>");
			if ($moduleinfo['realactive']){
				$uninstallop = "uninstall";
				$installop = "deactivate";
				$activateop = "donothing";
			}elseif ($moduleinfo['realinstalled']){
				$uninstallop = "uninstall";
				$installop = "donothing";
				$activateop = "activate";
			}else{
				$uninstallop = "donothing";
				$installop = "install";
				$activateop = "install,activate";
			}
			$uninstallcheck = false;
			$installcheck = false;
			$activatecheck = false;
			if ($moduleinfo['active']){
				$activatecheck = true;
			}elseif ($moduleinfo['installed']){
				//echo "<font color='red'>$modulename is installed but not active.</font><br>";
				$installcheck = true;
			}else{
				//echo "$modulename is uninstalled.<br>";
				$uninstallcheck = true;
			}
			if (isset($moduleinfo['invalid']) && $moduleinfo['invalid'] == true) {
				OutputClass::rawoutput("<td><input type='radio' name='modules[$modulename]' id='uninstall-$modulename' value='$uninstallop' checked disabled></td>");
				OutputClass::rawoutput("<td><input type='radio' name='modules[$modulename]' id='install-$modulename' value='$installop' disabled></td>");
				OutputClass::rawoutput("<td><input type='radio' name='modules[$modulename]' id='activate-$modulename' value='$activateop' disabled></td>");
			} else {
				OutputClass::rawoutput("<td><input type='radio' name='modules[$modulename]' id='uninstall-$modulename' value='$uninstallop'".($uninstallcheck?" checked":"")."></td>");
				OutputClass::rawoutput("<td><input type='radio' name='modules[$modulename]' id='install-$modulename' value='$installop'".($installcheck?" checked":"")."></td>");
				OutputClass::rawoutput("<td><input type='radio' name='modules[$modulename]' id='activate-$modulename' value='$activateop'".($activatecheck?" checked":"")."></td>");
			}
			OutputClass::output_notl("<td>".(in_array($modulename,$recommended_modules)?Translator::tl("`^Yes`0"):Translator::tl("`\$No`0"))."</td>",true);
			require_once("lib/sanitize.php");
			OutputClass::rawoutput("<td><span title=\"" .
			(isset($moduleinfo['description']) &&
			$moduleinfo['description'] ?
			$moduleinfo['description'] :
			SanitizeClass::sanitize($moduleinfo['formalname'])). "\">");
			OutputClass::output_notl("`@");
			if (isset($moduleinfo['invalid']) && $moduleinfo['invalid'] == true) {
				OutputClass::rawoutput($moduleinfo['formalname']);
			} else {
				OutputClass::output($moduleinfo['formalname']);
			}
			OutputClass::output_notl(" [`%$modulename`@]`0");
			OutputClass::rawoutput("</span></td><td>");
			OutputClass::output_notl("`#{$moduleinfo['moduleauthor']}`0", true);
			OutputClass::rawoutput("</td>");
			OutputClass::rawoutput("</tr>");
		}
	}
	OutputClass::rawoutput("</table>");
	OutputClass::rawoutput("<br><input type='submit' value='$submit' class='button'>");
	OutputClass::rawoutput("<input type='button' onClick='chooseRecommendedModules();' class='button' value='$install' class='button'>");
	OutputClass::rawoutput("<input type='reset' value='$reset' class='button'>");
	OutputClass::rawoutput("</form>");
	OutputClass::rawoutput("<script language='JavaScript'>
function chooseRecommendedModules(){
	var thisItem;
	var selectedCount = 0;
");
	reset($recommended_modules);
	while (list($key,$val)=each($recommended_modules)){
		OutputClass::rawoutput("thisItem = document.getElementById('activate-$val'); ");
		OutputClass::rawoutput("if (!thisItem.checked) { selectedCount++; thisItem.checked=true; }\n");
	}
	OutputClass::rawoutput("
	alert('I selected '+selectedCount+' modules that I recommend, but which were not already selected.');
}");
	if (!$session['dbinfo']['upgrade']){
		OutputClass::rawoutput("
	chooseRecommendedModules();");
	}
	OutputClass::rawoutput("
</script>");
}
?>
