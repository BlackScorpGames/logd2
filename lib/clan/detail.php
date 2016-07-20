<?php
	if ($session['user']['superuser'] & SU_EDIT_COMMENTS){
		$clanname = Http::httppost('clanname');
		if ($clanname) $clanname = SanitizeClass::full_sanitize($clanname);
		$clanshort = Http::httppost('clanshort');
		if ($clanshort) $clanshort = SanitizeClass::full_sanitize($clanshort);
		if ($clanname>"" && $clanshort>""){
			$sql = "UPDATE " . db_prefix("clans") . " SET clanname='$clanname',clanshort='$clanshort' WHERE clanid='$detail'";
			OutputClass::output("Updating clan names`n");
			db_query($sql);
			DataCache::invalidatedatacache("clandata-$detail");
		}
		if (Http::httppost('block')>""){
			$blockdesc = Translator::translate_inline("Description blocked for inappropriate usage.");
			$sql = "UPDATE " . db_prefix("clans") . " SET descauthor=4294967295, clandesc='$blockdesc' where clanid='$detail'";
			OutputClass::output("Blocking public description`n");
			db_query($sql);
			DataCache::invalidatedatacache("clandata-$detail");
		}elseif (Http::httppost('unblock')>""){
			$sql = "UPDATE " . db_prefix("clans") . " SET descauthor=0, clandesc='' where clanid='$detail'";
			OutputClass::output("UNblocking public description`n");
			db_query($sql);
			DataCache::invalidatedatacache("clandata-$detail");
		}
	}
	$sql = "SELECT * FROM " . db_prefix("clans") . " WHERE clanid='$detail'";
	$result1 = db_query_cached($sql, "clandata-$detail", 3600);
	$row1 = db_fetch_assoc($result1);
	if ($session['user']['superuser'] & SU_EDIT_COMMENTS){
		OutputClass::rawoutput("<div id='hidearea'>");
		OutputClass::rawoutput("<form action='clan.php?detail=$detail' method='POST'>");
		OutputClass::addnav("","clan.php?detail=$detail");
		OutputClass::output("Superuser / Moderator renaming:`n");
		OutputClass::output("Long Name: ");
		OutputClass::rawoutput("<input name='clanname' value=\"".htmlentities($row1['clanname'], ENT_COMPAT, Settings::getsetting("charset", "ISO-8859-1"))."\" maxlength=50 size=50>");
		OutputClass::output("`nShort Name: ");
		OutputClass::rawoutput("<input name='clanshort' value=\"".htmlentities($row1['clanshort'], ENT_COMPAT, Settings::getsetting("charset", "ISO-8859-1"))."\" maxlength=5 size=5>");
		OutputClass::output_notl("`n");
		$save = Translator::translate_inline("Save");
		OutputClass::rawoutput("<input type='submit' class='button' value=\"$save\">");
		$snu = htmlentities(Translator::translate_inline("Save & UNblock public description"), ENT_COMPAT, Settings::getsetting("charset", "ISO-8859-1"));
		$snb = htmlentities(Translator::translate_inline("Save & Block public description"), ENT_COMPAT, Settings::getsetting("charset", "ISO-8859-1"));
		if ($row1['descauthor']=="4294967295")
			OutputClass::rawoutput("<input type='submit' name='unblock' value=\"$snu\" class='button'>");
		else
			OutputClass::rawoutput("<input type='submit' name='block' value=\"$snb\" class='button'>");
		OutputClass::rawoutput("</form>");
		OutputClass::rawoutput("</div>");
		OutputClass::rawoutput("<script language='JavaScript'>var hidearea = document.getElementById('hidearea');hidearea.style.visibility='hidden';hidearea.style.display='none';</script>",true);
		$e = Translator::translate_inline("Edit Clan Info");
		OutputClass::rawoutput("<a href='#' onClick='hidearea.style.visibility=\"visible\"; hidearea.style.display=\"inline\"; return false;'>$e</a>",true);
		OutputClass::output_notl("`n");
	}

	OutputClass::output_notl(nltoappon($row1['clandesc']));
	if ( nltoappon($row1['clandesc']) != "" ) OutputClass::output ("`n`n");
	OutputClass::output("`0This is the current clan membership of %s < %s >:`n",$row1['clanname'],$row1['clanshort']);
	PageParts::page_header("Clan Membership for %s &lt;%s&gt;", SanitizeClass::full_sanitize($row1['clanname']), SanitizeClass::full_sanitize($row1['clanshort']));
	OutputClass::addnav("Clan Options");
	$rank = Translator::translate_inline("Rank");
	$name = Translator::translate_inline("Name");
	$dk = Translator::translate_inline("Dragon Kills");
	$jd = Translator::translate_inline("Join Date");
	OutputClass::rawoutput("<table border='0' cellpadding='2' cellspacing='0'>");
	OutputClass::rawoutput("<tr class='trhead'><td>$rank</td><td>$name</td><td>$dk</td><td>$jd</td></tr>");
	$i=0;
	$sql = "SELECT acctid,name,login,clanrank,clanjoindate,dragonkills FROM " . db_prefix("accounts") . " WHERE clanid=$detail ORDER BY clanrank DESC,clanjoindate";
	$result = db_query($sql);
	$tot = 0;
	//little hack with the hook...can't think of any other way
	$ranks = array(CLAN_APPLICANT=>"`!Applicant`0",CLAN_MEMBER=>"`#Member`0",CLAN_OFFICER=>"`^Officer`0",CLAN_LEADER=>"`&Leader`0", CLAN_FOUNDER=>"`\$Founder");
	$args = Modules::modulehook("clanranks", array("ranks"=>$ranks, "clanid"=>$detail));
	$ranks = Translator::translate_inline($args['ranks']);
	//end
	while ($row=db_fetch_assoc($result)){
		$i++;
		$tot += $row['dragonkills'];
		OutputClass::rawoutput("<tr class='".($i%2?"trlight":"trdark")."'>");
		OutputClass::rawoutput("<td>");
		OutputClass::output_notl($ranks[$row['clanrank']]); //translated earlier
		OutputClass::rawoutput("</td><td>");
		$link = "bio.php?char=".$row['acctid']."&ret=".urlencode($_SERVER['REQUEST_URI']);
		OutputClass::rawoutput("<a href='$link'>");
		OutputClass::addnav("", $link);
		OutputClass::output_notl("`&%s`0", $row['name']);
		OutputClass::rawoutput("</a>");
		OutputClass::rawoutput("</td><td align='center'>");
		OutputClass::output_notl("`\$%s`0", $row['dragonkills']);
		OutputClass::rawoutput("</td><td>");
		OutputClass::output_notl("`3%s`0", $row['clanjoindate']);
		OutputClass::rawoutput("</td></tr>");
	}
	OutputClass::rawoutput("</table>");
	OutputClass::output("`n`n`^This clan has a total of `\$%s`^ dragon kills.",$tot);
?>