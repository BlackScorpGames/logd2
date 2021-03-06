<?php
OutputClass::output("`@`c`bSuperuser Accounts`b`c");
OutputClass::debug($logd_version, true);
$sql = "SELECT login, password FROM ".db_prefix("accounts")." WHERE superuser & ".SU_MEGAUSER;
$result = db_query($sql);
if (db_num_rows($result)==0){
	if (Http::httppost("name")>""){
		$showform=false;
		if (Http::httppost("pass1")!=Http::httppost("pass2")){
			OutputClass::output("`\$Oops, your passwords don't match.`2`n");
			$showform=true;
		}elseif (strlen(Http::httppost("pass1"))<6){
			OutputClass::output("`\$Whoa, that's a short password, you really should make it longer.`2`n");
			$showform=true;
		}else{
			// Give the superuser a decent set of privs so they can
			// do everything needed without having to first go into
			// the user editor and give themselves privs.
			$su = SU_MEGAUSER | SU_EDIT_MOUNTS | SU_EDIT_CREATURES |
			SU_EDIT_PETITIONS | SU_EDIT_COMMENTS | SU_EDIT_DONATIONS |
			SU_EDIT_USERS | SU_EDIT_CONFIG | SU_INFINITE_DAYS |
			SU_EDIT_EQUIPMENT | SU_EDIT_PAYLOG | SU_DEVELOPER |
			SU_POST_MOTD | SU_MODERATE_CLANS | SU_EDIT_RIDDLES |
			SU_MANAGE_MODULES | SU_AUDIT_MODERATION | SU_RAW_SQL |
			SU_VIEW_SOURCE | SU_NEVER_EXPIRE;
			$name = Http::httppost("name");
			$pass = md5(md5(stripslashes(Http::httppost("pass1"))));
			$sql = "DELETE FROM ".db_prefix("accounts")." WHERE login='$name'";
			db_query($sql);
			$sql = "INSERT IGNORE INTO " .db_prefix("accounts") ." (login,password,superuser,name,ctitle,regdate) VALUES('$name','$pass',$su,'`%Admin `&$name`0','`%Admin', NOW())";

            db_query($sql);
			OutputClass::output("`^Your superuser account has been created as `%Admin `&$name`^!");
			Settings::savesetting("installer_version",$logd_version);
		}
	}else{
		$showform=true;
		Settings::savesetting("installer_version",$logd_version);
	}
	if ($showform){
		OutputClass::rawoutput("<form action='installer.php?stage=$stage' method='POST'>");
		OutputClass::output("Enter a name for your superuser account:");
		OutputClass::rawoutput("<input name='name' value=\"".htmlentities(Http::httppost("name"), ENT_COMPAT, Settings::getsetting("charset", "ISO-8859-1"))."\">");
		OutputClass::output("`nEnter a password: ");
		OutputClass::rawoutput("<input name='pass1' type='password'>");
		OutputClass::output("`nConfirm your password: ");
		OutputClass::rawoutput("<input name='pass2' type='password'>");
		$submit = Translator::translate_inline("Create");
		OutputClass::rawoutput("<br><input type='submit' value='$submit' class='button'>");
		OutputClass::rawoutput("</form>");
	}
}else{
	OutputClass::output("`#You already have a superuser account set up on this server.");
	Settings::savesetting("installer_version",$logd_version);
}
?>