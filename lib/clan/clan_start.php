<?php
	PageParts::page_header("Clan Hall for %s",  SanitizeClass::full_sanitize($claninfo['clanname']));
	OutputClass::addnav("Clan Options");
	if ($op==""){
		require_once("lib/clan/clan_default.php");
	}elseif ($op=="motd"){
		require_once("lib/clan/clan_motd.php");
	}elseif ($op=="membership"){
		require_once("lib/clan/clan_membership.php");
	}elseif ($op=="withdrawconfirm"){
		OutputClass::output("Are you sure you want to withdraw from your clan?");
		OutputClass::addnav("Withdraw?");
		OutputClass::addnav("No","clan.php");
		OutputClass::addnav("!?Yes","clan.php?op=withdraw");
	}elseif ($op=="withdraw"){
		require_once("lib/clan/clan_withdraw.php");
	}

?>