<?php
	PageParts::page_header("Clan Halls");
	$registrar=Settings::getsetting('clanregistrar','`%Karissa');
	OutputClass::addnav("Clan Options");
	OutputClass::output("`b`c`&Clan Halls`c`b");
	if ($op=="apply"){
		require_once("lib/clan/applicant_apply.php");
	}elseif ($op=="new"){
		require_once("lib/clan/applicant_new.php");
	}else{
		OutputClass::output("`7You stand in the center of a great marble lobby filled with pillars.");
		OutputClass::output("All around the walls of the lobby are various doors which lead to various clan halls.");
		OutputClass::output("The doors each possess a variety of intricate mechanisms which are obviously elaborate locks designed to be opened only by those who have been educated on how to operate them.");
		OutputClass::output("Nearby, you watch another warrior glance about nervously to make sure no one is watching before touching various levers and knobs on the door.");
		OutputClass::output("With a large metallic \"Chunk\" the lock on the door disengages, and the door swings silently open, admitting the warrior before slamming shut.`n`n");
		OutputClass::output("In the center of the lobby sits a highly polished desk, behind which sits `%%s`7, the clan registrar.",$registrar);
		OutputClass::output("She can take your filing for a new clan, or accept your application to an existing clan.`n`n");
/*//*/	Modules::modulehook("clan-enter");
		if ($op=="withdraw"){
			$session['user']['clanid']=0;
			$session['user']['clanrank']=CLAN_APPLICANT;
			$session['user']['clanjoindate']='0000-00-00 00:00:00';
			OutputClass::output("`7You tell `%%s`7 that you're no longer interested in joining %s.",$registrar, $claninfo['clanname']);
			OutputClass::output("She reaches into her desk, withdraws your application, and tears it up.  \"`5You wouldn't have been happy there anyhow, I don't think,`7\" as she tosses the shreds in her trash can.");
			$claninfo = array();
			$sql = "DELETE FROM " . db_prefix("mail") . " WHERE msgfrom=0 AND seen=0 AND subject='".serialize($apply_subj)."'";
			db_query($sql);
			OutputClass::output("You are not a member of any clan.");
			OutputClass::addnav("Apply for Membership to a Clan","clan.php?op=apply");
			OutputClass::addnav("Apply for a New Clan","clan.php?op=new");
		}else{
			if (isset($claninfo['clanid']) && $claninfo["clanid"]>0){
				//applied for membership to a clan
				OutputClass::output("`7You approach `%%s`7 who smiles at you, but lets you know that your application to %s hasn't yet been accepted.",$registrar,$claninfo['clanname']);
				OutputClass::output("Perhaps you'd like to take a seat in the waiting area, she suggests.");
				OutputClass::addnav("Waiting Area","clan.php?op=waiting");
				OutputClass::addnav("Withdraw Application","clan.php?op=withdraw");
			}else{
				//hasn't applied for membership to any clan.
				OutputClass::output("You are not a member of any clan.");
				OutputClass::addnav("Apply for Membership to a Clan","clan.php?op=apply");
				OutputClass::addnav("Apply for a New Clan","clan.php?op=new");
			}
		}
	}
?>