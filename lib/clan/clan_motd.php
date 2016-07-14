<?php
		PageParts::page_header("Update Clan Description / MoTD");
		OutputClass::addnav("Clan Options");
		if ($session['user']['clanrank']>=CLAN_OFFICER){
			$clanmotd = substr(Http::httppost('clanmotd'),0,4096);
			if (httppostisset('clanmotd') &&
					stripslashes($clanmotd)!=$claninfo['clanmotd']){
				$sql = "UPDATE " . db_prefix("clans") . " SET clanmotd='$clanmotd',motdauthor={$session['user']['acctid']} WHERE clanid={$claninfo['clanid']}";
				db_query($sql);
				invalidatedatacache("clandata-{$claninfo['clanid']}");
				$claninfo['clanmotd']=stripslashes($clanmotd);
				OutputClass::output("Updating MoTD`n");
				$claninfo['motdauthor']=$session['user']['acctid'];
			}
			$clandesc = Http::httppost('clandesc');
			if (httppostisset('clandesc') &&
					stripslashes($clandesc)!=$claninfo['clandesc'] &&
					$claninfo['descauthor']!=4294967295){
				$sql = "UPDATE " . db_prefix("clans") . " SET clandesc='".addslashes(substr(stripslashes($clandesc),0,4096))."',descauthor={$session['user']['acctid']} WHERE clanid={$claninfo['clanid']}";
				db_query($sql);
				invalidatedatacache("clandata-{$claninfo['clanid']}");
				OutputClass::output("Updating description`n");
				$claninfo['clandesc']=stripslashes($clandesc);
				$claninfo['descauthor']=$session['user']['acctid'];
			}
			$customsay = Http::httppost('customsay');
			if (httppostisset('customsay') && $customsay!=$claninfo['customsay'] && $session['user']['clanrank']>=CLAN_LEADER){
				$sql = "UPDATE " . db_prefix("clans") . " SET customsay='$customsay' WHERE clanid={$claninfo['clanid']}";
				db_query($sql);
				invalidatedatacache("clandata-{$claninfo['clanid']}");
				OutputClass::output("Updating custom say line`n");
				$claninfo['customsay']=stripslashes($customsay);
			}
			$sql = "SELECT name FROM " . db_prefix("accounts") . " WHERE acctid={$claninfo['motdauthor']}";
			$result = db_query($sql);
			$row = db_fetch_assoc($result);
			$motdauthname = $row['name'];

			$sql = "SELECT name FROM " . db_prefix("accounts") . " WHERE acctid={$claninfo['descauthor']}";
			$result = db_query($sql);
			$row = db_fetch_assoc($result);
			$descauthname = $row['name'];

			OutputClass::output("`&`bCurrent MoTD:`b `#by %s`2`n",$motdauthname);
			OutputClass::output_notl(nltoappon($claninfo['clanmotd'])."`n");
			OutputClass::output("`&`bCurrent Description:`b `#by %s`2`n",$descauthname);
			OutputClass::output_notl(nltoappon($claninfo['clandesc'])."`n");

			OutputClass::rawoutput("<form action='clan.php?op=motd' method='POST'>");
			OutputClass::addnav("","clan.php?op=motd");
			OutputClass::output("`&`bMoTD:`b `7(4096 chars)`n");
			OutputClass::rawoutput("<textarea name='clanmotd' cols='50' rows='10' class='input' style='width: 66%'>".htmlentities($claninfo['clanmotd'], ENT_COMPAT, Settings::getsetting("charset", "ISO-8859-1"))."</textarea><br>");
			OutputClass::output("`n`&`bDescription:`b `7(4096 chars)`n");
			$blocked = Translator::translate_inline("Your clan has been blocked from posting a description.`n");
			if ($claninfo['descauthor']==INT_MAX){
				OutputClass::output_notl($blocked);
			}else{
				OutputClass::rawoutput("<textarea name='clandesc' cols='50' rows='10' class='input' style='width: 66%'>".htmlentities($claninfo['clandesc'], ENT_COMPAT, Settings::getsetting("charset", "ISO-8859-1"))."</textarea><br>");
			}
			if ($session['user']['clanrank']>=CLAN_LEADER){
				OutputClass::output("`n`&`bCustom Talk Line`b `7(blank means \"says\" -- 15 chars max)`n");
				OutputClass::rawoutput("<input name='customsay' value=\"".htmlentities($claninfo['customsay'], ENT_COMPAT, Settings::getsetting("charset", "ISO-8859-1"))."\" class='input' maxlength=\"15\"><br/>");
			}
			$save = Translator::translate_inline("Save");
			OutputClass::rawoutput("<input type='submit' class='button' value='$save'>");
			OutputClass::rawoutput("</form>");
		}else{
			OutputClass::output("You do not have authority to change your clan's motd or description.");
		}
		OutputClass::addnav("Return to your clan hall","clan.php");
?>