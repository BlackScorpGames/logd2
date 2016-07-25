<?php
// addnews ready
// translator ready
// mail ready
require_once("common.php");
require_once("lib/http.php");
require_once("lib/sanitize.php");
SuAccess::check_su_access(SU_MANAGE_MODULES);
Translator::tlschema("modulemanage");

PageParts::page_header("Module Manager");

require_once("lib/superusernav.php");
SuperUserNavClass::superusernav();

OutputClass::addnav("Module Categories");

OutputClass::addnav("",$REQUEST_URI);
$op = Http::httpget('op');
$module = Http::httpget('module');

if ($op == 'mass'){
	if (Http::httppost("activate")) $op = "activate";
	if (Http::httppost("deactivate")) $op = "deactivate";
	if (Http::httppost("uninstall")) $op = "uninstall";
	if (Http::httppost("reinstall")) $op = "reinstall";
	if (Http::httppost("install")) $op = "install";
	$module = Http::httppost("module");
}
$theOp = $op;
if (is_array($module)){
	$modules = $module;
}else{
	if ($module) $modules = array($module);
	else $modules = array();
}
reset($modules);
while (list($key,$module)=each($modules)){
	$op = $theOp;
	OutputClass::output("`2Performing `^%s`2 on `%%s`0`n", Translator::translate_inline($op), $module);
	if($op=="install"){
		if (Modules::install_module($module)){

		}else{
			Http::httpset('cat','');
		}
		$op="";
		Http::httpset('op', "");
	}elseif($op=="uninstall"){
		if (Modules::uninstall_module($module)) {
		} else {
			OutputClass::output("Unable to inject module.  Module not uninstalled.`n");
		}
		$op="";
		Http::httpset('op', "");
	}elseif($op=="activate"){
		activate_module($module);
		$op="";
		Http::httpset('op', "");
		DataCache::invalidatedatacache("inject-$module");
	}elseif($op=="deactivate"){
		deactivate_module($module);
		$op="";
		Http::httpset('op', "");
		DataCache::invalidatedatacache("inject-$module");
	}elseif($op=="reinstall"){
		$sql = "UPDATE " . db_prefix("modules") . " SET filemoddate='0000-00-00 00:00:00' WHERE modulename='$module'";
		db_query($sql);
		// We don't care about the return value here at all.
		Modules::injectmodule($module, true);
		$op="";
		Http::httpset('op', "");
		DataCache::invalidatedatacache("inject-$module");
	}
}

$install_status = get_module_install_status();
$uninstmodules = $install_status['uninstalledmodules'];
$seencats = $install_status['installedcategories'];
$ucount = $install_status['uninstcount'];

ksort($seencats);
OutputClass::addnav(array(" ?Uninstalled - (%s modules)", $ucount), "modules.php");
reset($seencats);
foreach ($seencats as $cat=>$count) {
	OutputClass::addnav(array(" ?%s - (%s modules)", $cat, $count), "modules.php?cat=$cat");
}

$cat = Http::httpget('cat');

if ($op==""){
	if ($cat) {
		$sortby=Http::httpget('sortby');
		if (!$sortby) $sortby="installdate";
		$order=Http::httpget('order');
		$tcat = Translator::translate_inline($cat);
		OutputClass::output("`n`b%s Modules`b`n", $tcat);
		$deactivate = Translator::translate_inline("Deactivate");
		$activate = Translator::translate_inline("Activate");
		$uninstall = Translator::translate_inline("Uninstall");
		$reinstall = Translator::translate_inline("Reinstall");
		$strsettings = Translator::translate_inline("Settings");
		$strnosettings = Translator::translate_inline("`\$No Settings`0");
		$uninstallconfirm = Translator::translate_inline("Are you sure you wish to uninstall this module?  All user preferences and module settings will be lost.  If you wish to temporarily remove access to the module, you may simply deactivate it.");
		$status = Translator::translate_inline("Status");
		$mname = Translator::translate_inline("Module Name");
		$ops = Translator::translate_inline("Ops");
		$mauth = Translator::translate_inline("Module Author");
		$inon = Translator::translate_inline("Installed On");
		$installstr = Translator::translate_inline("by %s");
		$active = Translator::translate_inline("`@Active`0");
		$inactive = Translator::translate_inline("`\$Inactive`0");
		OutputClass::rawoutput("<form action='modules.php?op=mass&cat=$cat' method='POST'>");
		OutputClass::addnav("","modules.php?op=mass&cat=$cat");
		OutputClass::rawoutput("<table border='0' cellpadding='2' cellspacing='1' bgcolor='#999999'>",true);
		OutputClass::rawoutput("<tr class='trhead'><td>&nbsp;</td><td>$ops</td><td><a href='modules.php?cat=$cat&sortby=active&order=".($sortby=="active"?!$order:1)."'>$status</a></td><td><a href='modules.php?cat=$cat&sortby=formalname&order=".($sortby=="formalname"?!$order:1)."'>$mname</a></td><td><a href='modules.php?cat=$cat&sortby=moduleauthor&order=".($sortby=="moduleauthor"?!$order:1)."'>$mauth</a></td><td><a href='modules.php?cat=$cat&sortby=installdate&order=".($sortby=="installdate"?!$order:0)."'>$inon</a></td></tr>");
		OutputClass::addnav("","modules.php?cat=$cat&sortby=active&order=".($sortby=="active"?!$order:1));
		OutputClass::addnav("","modules.php?cat=$cat&sortby=formalname&order=".($sortby=="formalname"?!$order:1));
		OutputClass::addnav("","modules.php?cat=$cat&sortby=moduleauthor&order=".($sortby=="moduleauthor"?!$order:1));
		OutputClass::addnav("","modules.php?cat=$cat&sortby=installdate&order=".($sortby=="installdate"?$order:0));
		$sql = "SELECT * FROM " . db_prefix("modules") . " WHERE category='$cat' ORDER BY ".$sortby." ".($order?"ASC":"DESC");
		$result = db_query($sql);
		if (db_num_rows($result)==0){
			OutputClass::rawoutput("<tr class='trlight'><td colspan='6' align='center'>");
			OutputClass::output("`i-- No Modules Installed--`i");
			OutputClass::rawoutput("</td></tr>");
		}
		$number=db_num_rows($result);
		for ($i=0;$i<$number;$i++){
			$row = db_fetch_assoc($result);
			OutputClass::rawoutput("<tr class='".($i%2?"trlight":"trdark")."'>",true);
			OutputClass::rawoutput("<td nowrap valign='top'>");
			OutputClass::rawoutput("<input type='checkbox' name='module[]' value=\"{$row['modulename']}\">");
			OutputClass::rawoutput("</td><td valign='top' nowrap>[ ");
			if ($row['active']){
				OutputClass::rawoutput("<a href='modules.php?op=deactivate&module={$row['modulename']}&cat=$cat'>");
				OutputClass::output_notl($deactivate);
				OutputClass::rawoutput("</a>");
				OutputClass::addnav("","modules.php?op=deactivate&module={$row['modulename']}&cat=$cat");
			}else{
				OutputClass::rawoutput("<a href='modules.php?op=activate&module={$row['modulename']}&cat=$cat'>");
				OutputClass::output_notl($activate);
				OutputClass::rawoutput("</a>");
				OutputClass::addnav("","modules.php?op=activate&module={$row['modulename']}&cat=$cat");
			}
			OutputClass::rawoutput(" |<a href='modules.php?op=uninstall&module={$row['modulename']}&cat=$cat' onClick='return confirm(\"$uninstallconfirm\");'>");
			OutputClass::output_notl($uninstall);
			OutputClass::rawoutput("</a>");
			OutputClass::addnav("","modules.php?op=uninstall&module={$row['modulename']}&cat=$cat");
			OutputClass::rawoutput(" | <a href='modules.php?op=reinstall&module={$row['modulename']}&cat=$cat'>");
			OutputClass::output_notl($reinstall);
			OutputClass::rawoutput("</a>");
			OutputClass::addnav("","modules.php?op=reinstall&module={$row['modulename']}&cat=$cat");

			if ($session['user']['superuser'] & SU_EDIT_CONFIG) {
				if (strstr($row['infokeys'], "|settings|")) {
					OutputClass::rawoutput(" | <a href='configuration.php?op=modulesettings&module={$row['modulename']}'>");
					OutputClass::output_notl($strsettings);
					OutputClass::rawoutput("</a>");
					OutputClass::addnav("","configuration.php?op=modulesettings&module={$row['modulename']}");
				} else {
					OutputClass::output_notl(" | %s", $strnosettings);
				}
			}

			OutputClass::rawoutput(" ]</td><td valign='top'>");
			OutputClass::output_notl($row['active']?$active:$inactive);
			require_once("lib/sanitize.php");
			OutputClass::rawoutput("</td><td nowrap valign='top'><span title=\"".
					(isset($row['description'])&&$row['description']?
					 $row['description']:SanitizeClass::sanitize($row['formalname']))."\">");
			OutputClass::output_notl("%s %s", $row['formalname'], $row['version']);
			OutputClass::rawoutput("<br>");
			OutputClass::output_notl("(%s) ", $row['modulename'], $row['version']);
			OutputClass::rawoutput("</span></td><td valign='top'>");
			OutputClass::output_notl("`#%s`0", $row['moduleauthor'], true);
			OutputClass::rawoutput("</td><td nowrap valign='top'>");
			$line = sprintf($installstr, $row['installedby']);
			OutputClass::output_notl("%s", $row['installdate']);
			OutputClass::rawoutput("<br>");
			OutputClass::output_notl("%s", $line);
			OutputClass::rawoutput("</td></tr>");
		}
		OutputClass::rawoutput("</table><br />");
		$activate = Translator::translate_inline("Activate");
		$deactivate = Translator::translate_inline("Deactivate");
		$reinstall = Translator::translate_inline("Reinstall");
		$uninstall = Translator::translate_inline("Uninstall");
		OutputClass::rawoutput("<input type='submit' name='activate' class='button' value='$activate'>");
		OutputClass::rawoutput("<input type='submit' name='deactivate' class='button' value='$deactivate'>");
		OutputClass::rawoutput("<input type='submit' name='reinstall' class='button' value='$reinstall'>");
		OutputClass::rawoutput("<input type='submit' name='uninstall' class='button' value='$uninstall'>");
		OutputClass::rawoutput("</form>");
	} else {
		$sorting=Http::httpget('sorting');
		if (!$sorting) $sorting="shortname";
		$order=Http::httpget('order');
		OutputClass::output("`bUninstalled Modules`b`n");
		$install = Translator::translate_inline("Install");
		$mname = Translator::translate_inline("Module Name");
		$ops = Translator::translate_inline("Ops");
		$mauth = Translator::translate_inline("Module Author");
		$categ = Translator::translate_inline("Category");
		$fname = Translator::translate_inline("Filename");
		OutputClass::rawoutput("<form action='modules.php?op=mass&cat=$cat' method='POST'>");
		OutputClass::addnav("","modules.php?op=mass&cat=$cat");
		OutputClass::rawoutput("<table border='0' cellpadding='2' cellspacing='1' bgcolor='#999999'>",true);
		OutputClass::rawoutput("<tr class='trhead'><td>&nbsp;</td><td>$ops</td><td><a href='modules.php?sorting=name&order=".($sorting=="name"?!$order:0)."'>$mname</a></td><td><a href='modules.php?sorting=author&order=".($sorting=="author"?!$order:0)."'>$mauth</a></td><td><a href='modules.php?sorting=category&order=".($sorting=="category"?!$order:0)."'>$categ</a></td><td><a href='modules.php?sorting=shortname&order=".($sorting=="shortname"?!$order:0)."'>$fname</a></td></tr>");
		OutputClass::addnav("","modules.php?sorting=name&order=".($sorting=="name"?!$order:0));
		OutputClass::addnav("","modules.php?sorting=author&order=".($sorting=="author"?!$order:0));
		OutputClass::addnav("","modules.php?sorting=category&order=".($sorting=="category"?!$order:0));
		OutputClass::addnav("","modules.php?sorting=shortname&order=".($sorting=="shortname"?!$order:0));
		if (count($uninstmodules) > 0) {
			$count = 0;
			$moduleinfo=array();
			$sortby=array();
			$numberarray=array();
			$invalidmodule = array(
				"version"=>"",
				"author"=>"",
				"category"=>"",
				"download"=>"",
				"invalid"=>true,
			);
			foreach($uninstmodules as $key=>$shortname) {
				//test if the file is a valid module or a lib file/whatever that got in, maybe even malcode that does not have module form
				$shortnamelower = strtolower($shortname);
				$file = strtolower(file_get_contents("modules/$shortname.php"));
				if (strpos($file,$shortnamelower."_getmoduleinfo")===false ||
					//strpos($file,$shortname."_dohook")===false ||
					//do_hook is not a necessity
					strpos($file,$shortnamelower."_install")===false ||
					strpos($file,$shortnamelower."_uninstall")===false) {
						//here the files has neither do_hook nor getinfo, which means it won't execute as a module here --> block it + notify the admin who is the manage modules section
						$temp=array_merge($invalidmodule,array("name"=>$shortname.".php ".OutputClass::appoencode(Translator::translate_inline("(`\$Invalid Module! Contact Author or check file!`0)"))));
				} else {
					$temp= Modules::get_module_info($shortname);
				}
				//end of testing
				if (!$temp || empty($temp)) continue;
				$temp['shortname']=$shortname;
				array_push($moduleinfo,$temp);
				array_push($sortby,SanitizeClass::full_sanitize($temp[$sorting]));
				array_push($numberarray,$count);
				$count++;
			}
			array_multisort($sortby,($order?SORT_DESC:SORT_ASC),$numberarray,($order?SORT_DESC:SORT_ASC));
			for ($a=0;$a<count($moduleinfo);$a++) {
				$i=$numberarray[$a];
				OutputClass::rawoutput("<tr class='".($i%2?"trlight":"trdark")."'>");
				if (isset($moduleinfo[$i]['invalid']) && $moduleinfo[$i]['invalid']===true) {
					OutputClass::rawoutput("<td></td><td nowrap valign='top'>");
						OutputClass::output("Not installable");
						OutputClass::rawoutput("</td>");
				} else {
					OutputClass::rawoutput("<td><input type='checkbox' name='module[]' value='{$moduleinfo[$i]['shortname']}'></td>");
					OutputClass::rawoutput("<td nowrap valign='top'>");
					OutputClass::rawoutput("[ <a href='modules.php?op=install&module={$moduleinfo[$i]['shortname']}&cat={$moduleinfo[$i]['category']}'>");
					OutputClass::output_notl($install);
					OutputClass::rawoutput("</a>]</td>");
					OutputClass::addnav("","modules.php?op=install&module={$moduleinfo[$i]['shortname']}&cat={$moduleinfo[$i]['category']}");
				}
			    OutputClass::rawoutput("<td nowrap valign='top'><span title=\"".
					(isset($moduleinfo[$i]['description'])&&
					     $moduleinfo[$i]['description'] ?
					 $moduleinfo[$i]['description'] :
					 SanitizeClass::sanitize($moduleinfo[$i]['name']))."\">");
				OutputClass::rawoutput($moduleinfo[$i]['name']." ".$moduleinfo[$i]['version']);
				OutputClass::rawoutput("</span></td><td valign='top'>");
				OutputClass::output_notl("`#%s`0", $moduleinfo[$i]['author'], true);
				OutputClass::rawoutput("</td><td valign='top'>");
				OutputClass::rawoutput($moduleinfo[$i]['category']);
				OutputClass::rawoutput("</td><td valign='top'>");
				OutputClass::rawoutput($moduleinfo[$i]['shortname'] . ".php");
				OutputClass::rawoutput("</td>");
				OutputClass::rawoutput("</tr>");
				if (isset($moduleinfo[$i]['requires']) && count($moduleinfo[$i]['requires'])){
					OutputClass::rawoutput("<tr class='".($i%2?"trlight":"trdark")."'>");
					OutputClass::rawoutput("<td>&nbsp;</td>");
					OutputClass::rawoutput("<td colspan='6'>");
					OutputClass::output("`bRequires:`b`n");
					reset($moduleinfo[$i]['requires']);
					while (list($key,$val)=each($moduleinfo[$i]['requires'])){
						$info = explode("|",$val);
						if (module_check_requirements(array($key=>$val))){
							OutputClass::output_notl("`@");
						}else{
							OutputClass::output_notl("`\$");
						}
						if(isset($info[1])) OutputClass::output_notl("$key {$info[0]} -- {$info[1]}`n");
						else OutputClass::output_notl("$key {$info[0]}`n");
					}
					OutputClass::rawoutput("</td>");
					OutputClass::rawoutput("</tr>");
				}
				$count++;
			}
		} else {
			OutputClass::rawoutput("<tr class='trlight'><td colspan='6' align='center'>");
			OutputClass::output("`i--No uninstalled modules were found--`i");
			OutputClass::rawoutput("</td></tr>");
		}
		OutputClass::rawoutput("</table><br />");
		$install = Translator::translate_inline("Install");
		OutputClass::rawoutput("<input type='submit' name='install' class='button' value='$install'>");
	}
}

PageParts::page_footer();
?>