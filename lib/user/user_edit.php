<?php
$result = db_query("SELECT * FROM " . db_prefix("accounts") . " WHERE acctid='$userid'");
$row = db_fetch_assoc($result);
$petition=Http::httpget("returnpetition");
if ($petition != "")
	$returnpetition = "&returnpetition=$petition";
	if ($petition !=""){
	OutputClass::addnav("Navigation");
	OutputClass::addnav("Return to the petition","viewpetition.php?op=view&id=$petition");
}
	OutputClass::addnav("Operations");
OutputClass::addnav("View last page hit","user.php?op=lasthit&userid=$userid",false,true);
OutputClass::addnav("Display debug log","user.php?op=debuglog&userid=$userid$returnpetition");
OutputClass::addnav("View user bio","bio.php?char=".$row['acctid']."&ret=".urlencode($_SERVER['REQUEST_URI']));
if ($session['user']['superuser'] & SU_EDIT_DONATIONS) {
	OutputClass::addnav("Add donation points","donators.php?op=add1&name=".rawurlencode($row['login'])."&ret=".urlencode($_SERVER['REQUEST_URI']));
}
	OutputClass::addnav("","user.php?op=edit&userid=$userid$returnpetition");
OutputClass::addnav("Bans");
OutputClass::addnav("Set up ban","user.php?op=setupban&userid={$row['acctid']}");
if (Http::httpget("subop")==""){
	rawoutput("<form action='user.php?op=special&userid=$userid$returnpetition' method='POST'>");
	OutputClass::addnav("","user.php?op=special&userid=$userid$returnpetition");
	$grant = Translator::translate_inline("Grant New Day");
	rawoutput("<input type='submit' class='button' name='newday' value='$grant'>");
	$fix = Translator::translate_inline("Fix Broken Navs");
	rawoutput("<input type='submit' class='button' name='fixnavs' value='$fix'>");
	$mark = Translator::translate_inline("Mark Email As Valid");
	rawoutput("<input type='submit' class='button' name='clearvalidation' value='$mark'>");
	rawoutput("</form>");
		//Show a user's usertable
	rawoutput("<form action='user.php?op=save&userid=$userid$returnpetition' method='POST'>");
	OutputClass::addnav("","user.php?op=save&userid=$userid$returnpetition");
	$save = Translator::translate_inline("Save");
	rawoutput("<input type='submit' class='button' value='$save'>");
	if ($row['loggedin']==1 && $row['laston']>date("Y-m-d H:i:s",strtotime("-".Settings::getsetting("LOGINTIMEOUT",900)." seconds"))){
		OutputClass::output_notl("`\$");
		rawoutput("<span style='font-size: 20px'>");
		OutputClass::output("`\$Warning:`0");
		rawoutput("</span>");
		OutputClass::output("`\$This user is probably logged in at the moment!`0");
	}
	// Okay, munge the display name down to just the players name sans
	// title
	$row['name'] = get_player_basename($row);
	/*careful using this hook! add only things with 'viewonly' in there, nothing will be saved if do otherwise! Example:
	do_hook of your module:
	array_push($args['userinfo'], "Some Stuff to have a look at,title");
	$args['userinfo']['test'] = "The truth!!!,viewonly";
	$args['user']['test'] = "Is out there???";
	*/
	$showformargs = Modules::modulehook("modifyuserview", array("userinfo"=>$userinfo, "user"=>$row));
	$info = showform($showformargs['userinfo'],$showformargs['user']);
	rawoutput("<input type='hidden' value=\"".htmlentities(serialize($info), ENT_COMPAT, Settings::getsetting("charset", "ISO-8859-1"))."\" name='oldvalues'>");
	rawoutput("</form>");
		OutputClass::output("`n`nLast Page Viewed:`n");
	rawoutput("<iframe src='user.php?op=lasthit&userid=$userid' width='100%' height='400'>");
	OutputClass::output("You need iframes to view the user's last hit here.");
	OutputClass::output("Use the link in the nav instead.");
	rawoutput("</iframe>");
}elseif(Http::httpget("subop")=="module"){
	//Show a user's prefs for a given module.
	OutputClass::addnav("Operations");
	OutputClass::addnav("Edit user","user.php?op=edit&userid=$userid$returnpetition");
	$module = Http::httpget('module');
	$info = get_module_info($module);
	if (count($info['prefs']) > 0) {
		$data = array();
		$msettings = array();
		while (list($key,$val)=each($info['prefs'])){
			// Handle vals which are arrays.
			if (is_array($val)) {
				$v = $val[0];
				$x = explode("|", $v);
				$val[0] = $x[0];
				$x[0] = $val;
			} else {
				$x = explode("|",$val);
			}
			$msettings[$key] = $x[0];
			// Set up the defaults as well.
			if (isset($x[1])) $data[$key] = $x[1];
		}
		$sql = "SELECT * FROM " . db_prefix("module_userprefs") ." WHERE modulename='$module' AND userid='$userid'";
		$result = db_query($sql);
		while ($row = db_fetch_assoc($result)){
			$data[$row['setting']] = $row['value'];
		}
		rawoutput("<form action='user.php?op=savemodule&module=$module&userid=$userid$returnpetition' method='POST'>");
		OutputClass::addnav("","user.php?op=savemodule&module=$module&userid=$userid$returnpetition");
		Translator::tlschema("module-$module");
		showform($msettings,$data);
		Translator::tlschema();
		rawoutput("</form>");
	}else{
		OutputClass::output("The $module module doesn't appear to define any user preferences.");
	}
}
module_editor_navs('prefs', "user.php?op=edit&subop=module&userid=$userid$returnpetition&module=");
OutputClass::addnav("","user.php?op=lasthit&userid=$userid");
?>