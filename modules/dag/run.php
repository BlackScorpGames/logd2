<?php
function dag_run_private(){
	require_once("modules/dag/misc_functions.php");
	global $session;
	if (Http::httpget('manage')!="true"){
		PageParts::page_header("Dag Durnick's Table");
		OutputClass::output("<span style='color: #9900FF'>",true);
		OutputClass::output("`c`bDag Durnick's Table`b`c");
	}else{
		dag_manage();
	}

	$op = Http::httpget('op');

	OutputClass::addnav("Navigation");
	OutputClass::addnav("I?Return to the Inn","inn.php");
	if ($op != '')
		OutputClass::addnav("Talk to Dag Durnick", "runmodule.php?module=dag");

	if ($op=="list"){
		OutputClass::output("Dag fishes a small leather bound book out from under his cloak, flips through it to a certain page and holds it up for you to see.");
		OutputClass::output("\"`7Deese ain't the most recent figgers, I ain't just had time to get th' other numbers put in.`0\"`n`n");
		// ***ADDED***
		// By Andrew Senger
		// Added for new Bounty Code
		OutputClass::output("`c`bThe Bounty List`b`c`n");
		$sql = "SELECT bountyid,amount,target,setter,setdate FROM " . db_prefix("bounty") . " WHERE status=0 AND setdate<='".date("Y-m-d H:i:s")."' ORDER BY bountyid ASC";
		$result = db_query($sql);
		OutputClass::rawoutput("<table border=0 cellpadding=2 cellspacing=1 bgcolor='#999999'>");
		$amount = Translator::translate_inline("Amount");
		$level = Translator::translate_inline("Level");
		$name = Translator::translate_inline("Name");
		$loc = Translator::translate_inline("Location");
		$sex = Translator::translate_inline("Sex");
		$alive = Translator::translate_inline("Alive");
		$last = Translator::translate_inline("Last On");
		OutputClass::rawoutput("<tr class='trhead'><td><b>$amount</b></td><td><b>$level</b></td><td><b>$name</b></td><td><b>$loc</b></td><td><b>$sex</b></td><td><b>$alive</b></td><td><b>$last</b></td>");
		$listing = array();
		$totlist = 0;
		for($i=0;$i<db_num_rows($result);$i++){
			$row = db_fetch_assoc($result);
			$amount = (int)$row['amount'];
			$sql = "SELECT name,alive,sex,level,laston,loggedin,lastip,location FROM " . db_prefix("accounts") . " WHERE acctid={$row['target']}";
			$result2 = db_query($sql);
			if (db_num_rows($result2) == 0) {
				/* this person has been deleted, clear bounties */
				$sql = "UPDATE " . db_prefix("bounty") . " SET status=1 WHERE target={$row['target']}";
				db_query($sql);
				continue;
			}
			$row2 = db_fetch_assoc($result2);
			$yesno = 0;
			for($j=0;$j<=$i;$j++){
				if(isset($listing[$j]) &&
						$listing[$j]['Name'] == $row2['name']) {
					$listing[$j]['Amount'] = $listing[$j]['Amount'] + $amount;
					$yesno = 1;
				}
			}

			if ($yesno==0) {
				$loggedin = (date("U")-strtotime($row2['laston'])<Settings::getsetting("LOGINTIMEOUT",900) && $row2['loggedin']);
				$listing[] = array('Amount'=>$amount,'Level'=>$row2['level'],'Name'=>$row2['name'],'Location'=>$row2['location'],'Sex'=>$row2['sex'],'Alive'=>$row2['alive'],'LastOn'=>$row2['laston'], 'LoggedIn'=>$loggedin);
				$totlist = $totlist + 1;
			}
		}
		$sort = Http::httpget("sort");
		if ($sort=="level")
			usort($listing, 'dag_sortbountieslevel');
		elseif ($sort != "")
			usort($listing, 'dag_sortbounties');
		else
			usort($listing, 'dag_sortbountieslevel');
		for($i=0;$i<$totlist;$i++) {
			OutputClass::rawoutput("<tr class='".($i%2?"trdark":"trlight")."'><td>");
			OutputClass::output_notl("`^%s`0", $listing[$i]['Amount']);
			OutputClass::rawoutput("</td><td>");
			OutputClass::output_notl("`^%s`0", $listing[$i]['Level']);
			OutputClass::rawoutput("</td><td>");
			OutputClass::output_notl("`^%s`0", $listing[$i]['Name']);
			OutputClass::rawoutput("</td><td>");
			OutputClass::output($listing[$i]['LoggedIn']?"`#Online`0":$listing[$i]['Location']);
			OutputClass::rawoutput("</td><td>");
			OutputClass::output($listing[$i]['Sex']?"`!Female`0":"`!Male`0");
			OutputClass::rawoutput("</td><td>");
			OutputClass::output($listing[$i]['Alive']?"`1Yes`0":"`4No`0");
			OutputClass::rawoutput("</td><td>");
			$laston = GameDateTime::relativedate($listing[$i]['LastOn']);
			OutputClass::output_notl("%s", $laston);
			OutputClass::rawoutput("</td></tr>");
		}
		OutputClass::rawoutput("</table>");
		// ***END ADDING***
	}else if ($op=="addbounty"){
		if (get_module_pref("bounties") >= get_module_setting("maxbounties")) {
			OutputClass::output("Dag gives you a piercing look.");
			OutputClass::output("`7\"Ye be thinkin' I be an assassin or somewhat?  Ye already be placin' more than 'nuff bounties for t'day.  Now, be ye gone before I stick a bounty on yer head fer annoyin' me.\"`n`n");
		} else {
			$fee = get_module_setting("bountyfee");
			if ($fee < 0 || $fee > 100) {
				$fee = 10;
				set_module_setting("bountyfee",$fee);
			}
			$min = get_module_setting("bountymin");
			$max = get_module_setting("bountymax");
			OutputClass::output("Dag Durnick glances up at you and adjusts the pipe in his mouth with his teeth.`n");
			OutputClass::output("`7\"So, who ye be wantin' to place a hit on? Just so ye be knowing, they got to be legal to be killin', they got to be at least level %s, and they can't be having too much outstandin' bounty nor be getting hit too frequent like, so if they ain't be listed, they can't be contracted on!  We don't run no slaughterhouse here, we run a.....business.  Also, there be a %s%% listin' fee fer any hit ye be placin'.\"`n`n", get_module_setting("bountylevel"), get_module_setting("bountyfee"));
			OutputClass::rawoutput("<form action='runmodule.php?module=dag&op=finalize' method='POST'>");
			OutputClass::output("`2Target: ");
			OutputClass::rawoutput("<input name='contractname'>");
			OutputClass::output_notl("`n");
			OutputClass::output("`2Amount to Place: ");
			OutputClass::rawoutput("<input name='amount' id='amount' width='5'>");
			OutputClass::output_notl("`n`n");
			$final = Translator::translate_inline("Finalize Contract");
			OutputClass::rawoutput("<input type='submit' class='button' value='$final'>");
			OutputClass::rawoutput("</form>");
			OutputClass::addnav("","runmodule.php?module=dag&op=finalize");
		}
	}elseif ($op=="finalize") {
		if (Http::httpget('subfinal')==1){
			$sql = "SELECT acctid,name,login,level,locked,age,dragonkills,pk,experience FROM " . db_prefix("accounts") . " WHERE name='".addslashes(rawurldecode(stripslashes(Http::httppost('contractname'))))."' AND locked=0";
		}else{
			$contractname = stripslashes(rawurldecode(Http::httppost('contractname')));
			$name="%";
			for ($x=0;$x<strlen($contractname);$x++){
				$name.=substr($contractname,$x,1)."%";
			}
			$sql = "SELECT acctid,name,login,level,locked,age,dragonkills,pk,experience FROM " . db_prefix("accounts") . " WHERE name LIKE '".addslashes($name)."' AND locked=0";
		}
		$result = db_query($sql);
		if (db_num_rows($result) == 0) {
			OutputClass::output("Dag Durnick sneers at you, `7\"There not be anyone I be knowin' of by that name.  Maybe ye should come back when ye got a real target in mind?\"");
		} elseif(db_num_rows($result) > 100) {
			OutputClass::output("Dag Durnick scratches his head in puzzlement, `7\"Ye be describing near half th' town, ye fool?  Why don't ye be giving me a better name now?\"");
		} elseif(db_num_rows($result) > 1) {
			OutputClass::output("Dag Durnick searches through his list for a moment, `7\"There be a couple of 'em that ye could be talkin' about.  Which one ye be meaning?\"`n");
			OutputClass::rawoutput("<form action='runmodule.php?module=dag&op=finalize&subfinal=1' method='POST'>");
			OutputClass::output("`2Target: ");
			OutputClass::rawoutput("<select name='contractname'>");
			for ($i=0;$i<db_num_rows($result);$i++){
				$row = db_fetch_assoc($result);
				OutputClass::rawoutput("<option value=\"".rawurlencode($row['name'])."\">".SanitizeClass::full_sanitize($row['name'])."</option>");
			}
			OutputClass::rawoutput("</select>");
			OutputClass::output_notl("`n`n");
			$amount = Http::httppost('amount');
			OutputClass::output("`2Amount to Place: ");
			OutputClass::rawoutput("<input name='amount' id='amount' width='5' value='$amount'>");
			OutputClass::output_notl("`n`n");
			$final = Translator::translate_inline("Finalize Contract");
			OutputClass::rawoutput("<input type='submit' class='button' value='$final'>");
			OutputClass::rawoutput("</form>");
			OutputClass::addnav("","runmodule.php?module=dag&op=finalize&subfinal=1");
		} else {
			// Now, we have just the one, so check it.
			$row  = db_fetch_assoc($result);
			if ($row['locked']) {
				OutputClass::output("Dag Durnick sneers at you, `7\"There not be anyone I be knowin' of by that name.  Maybe ye should come back when ye got a real target in mind?\"");
			} elseif ($row['login'] == $session['user']['login']) {
				OutputClass::output("Dag Durnick slaps his knee laughing uproariously, `7\"Ye be wanting to take out a contract on yerself?  I ain't be helping no suicider, now!\"");
			} elseif ($row['level'] < get_module_setting("bountylevel") ||
						($row['age'] < Settings::getsetting("pvpimmunity",5) &&
						 $row['dragonkills'] == 0 && $row['pk'] == 0 &&
						 $row['experience'] < Settings::getsetting("pvpminexp",1500))) {
				OutputClass::output("Dag Durnick stares at you angrily, `7\"I told ye that I not be an assassin.  That ain't a target worthy of a bounty.  Now get outta me sight!\"");
			} else {
				// All good!
				$amt = abs((int)Http::httppost('amount'));
				$min = get_module_setting("bountymin") * $row['level'];
				$max = get_module_setting("bountymax") * $row['level'];
				$fee = get_module_setting("bountyfee");
				$cost = round($amt*((100+$fee)/100), 0);
				$curbounty = 0;
				$sql = "SELECT sum(amount) AS total FROM " . db_prefix("bounty") . " WHERE status=0 AND target={$row['acctid']}";
				$result = db_query($sql);
				if (db_num_rows($result) > 0) {
					$nrow = db_fetch_assoc($result);
					$curbounty = $nrow['total'];
				}
				if ($amt < $min) {
					OutputClass::output("Dag Durnick scowls, `7\"Ye think I be workin' for that pittance?  Be thinkin' again an come back when ye willing to spend some real coin.  That mark be needin' at least %s gold to be worth me time.\"", $min);
				} elseif ($session['user']['gold'] < $cost) {
					OutputClass::output("Dag Durnick scowls, `7\"Ye don't be havin enough gold to be settin' that contract.  Wastin' my time like this, I aught to be puttin' a contract on YE instead!");
				} elseif ($amt + $curbounty > $max) {
					if ($curbounty) {
						OutputClass::output("Dag looks down at the pile of coin and just leaves them there.");
						OutputClass::output("`7\"I'll just be passin' on that contract.  That's way more'n `^%s`7 be worth and ye know it.  I ain't no durned assassin. A bounty o' %s already be on their head, what with the bounties I ain't figgered in to th' book already.  I might be willin' t'up it to %s, after me %s%% listin' fee of course\"`n`n",$row['name'], $curbounty, $max, $fee);
					} else {
						OutputClass::output("Dag looks down at the pile of coin and just leaves them there.");
						OutputClass::output("`7\"I'll just be passin' on that contract.  That's way more'n `^%s`7 be worth and ye know it.  I ain't no durned assassin.  I might be willin' t'let y' set one of %s, after me %s%% listin' fee of course\"`n`n", $row['name'], $max, $fee);
					}
				} else {
					OutputClass::output("You slide the coins towards Dag Durnick, who deftly palms them from the table.");
					OutputClass::output("`7\"I'll just be takin' me %s%% listin' fee offa the top.  The word be put out that ye be wantin' `^%s`7 taken care of. Be patient, and keep yer eyes on the news.\"`n`n", $fee, $row['name']);
					Modules::set_module_pref("bounties",get_module_pref("bounties")+1);
					$session['user']['gold']-=$cost;
					// ***ADDED***
					// By Andrew Senger
					// Adding for new Bounty Code
					$setdate = time();
					// random set date up to 4 hours in the future.
					$setdate += Erand::e_rand(0,14400);
					$sql = "INSERT INTO ". db_prefix("bounty") . " (amount, target, setter, setdate) VALUES ($amt, ".$row['acctid'].", ".(int)$session['user']['acctid'].", '".date("Y-m-d H:i:s",$setdate)."')";
					db_query($sql);
					// ***END ADD***
					DebugLogClass::debuglog("spent $cost to place a $amt bounty on {$row['name']}");
				}
			}
		}
	}else{
		OutputClass::output("You stroll over to Dag Durnick, who doesn't even bother to look up at you.");
		OutputClass::output("He takes a long pull on his pipe.`n");
		OutputClass::output("`7\"Ye probably be wantin' to know if there's a price on yer head, ain't ye.\"`n`n");
		// ***ADDED***
		// By Andrew Senger
		// Adding for new Bounty Code
		$sql = "SELECT sum(amount) as total FROM " . db_prefix("bounty") . " WHERE status=0 AND setdate<='".date("Y-m-d H:i:s")."' AND target=".$session['user']['acctid'];
		$result = db_query($sql);
		$curbounty = 0;
		if (db_num_rows($result) != 0) {
			$row = db_fetch_assoc($result);
			$curbounty = $row['total'];
		}
		if ($curbounty == 0) {
			OutputClass::output("\"`3Ye don't have no bounty on ya.  I suggest ye be keepin' it that way.\"");
		} else {
		 OutputClass::output("\"`3Well, it be lookin like ye have `^%s gold`3 on yer head currently. Ye might wanna be watchin yourself.\"", $curbounty);
		}
		// ***END ADD***
		OutputClass::addnav("Bounties");
		OutputClass::addnav("Check the Wanted List","runmodule.php?module=dag&op=list");
		OutputClass::addnav("Set a Bounty","runmodule.php?module=dag&op=addbounty");
	}
	Modules::modulehook('dagnav');
	if ($op == "list") {
		OutputClass::addnav("Sort List");
		OutputClass::addnav("View by Bounty",
				"runmodule.php?module=dag&op=list&sort=bounty");
		OutputClass::addnav("View by Level", "runmodule.php?module=dag&op=list&sort=level");
	}
	OutputClass::rawoutput("</span>");
	PageParts::page_footer();
}
?>
