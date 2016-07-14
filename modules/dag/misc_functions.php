<?php
function dag_sortbounties($x, $y) {
	if ($x['Amount'] == $y['Amount']) {
		return 0;
	} elseif ($x['Amount'] > $y['Amount']) {
		return -1;
	} elseif ($x['Amount'] < $y['Amount']) {
		return 1;
	}
}

function dag_sortbountieslevel($x, $y) {
	if ($x['Level'] == $y['Level']) {
		return dag_sortbounties($x, $y);
	} elseif ($x['Level'] > $y['Level']) {
		return -1;
	} elseif ($x['Level'] < $y['Level']) {
		return 1;
	}
}

function dag_manage(){
	PageParts::page_header("Dag's Bounty Lists");
	require_once("lib/superusernav.php");
	superusernav();

	// Add some bounty expiration for closed bounties
	$sql = "DELETE FROM " . db_prefix("bounty") . " WHERE status=1 AND windate <'".date("Y-m-d H:i:s",strtotime("-".(Settings::getsetting("expirecontent",180)/10)." days"))."'";
	db_query($sql);

	OutputClass::addnav("Actions");
	OutputClass::addnav("A?View All Bounties","runmodule.php?module=dag&manage=true&op=viewbounties&type=1&sort=1&dir=1&admin=true");
	OutputClass::addnav("O?View Open Bounties","runmodule.php?module=dag&manage=true&op=viewbounties&type=2&sort=1&dir=1&admin=true");
	OutputClass::addnav("C?View Closed Bounties","runmodule.php?module=dag&manage=true&op=viewbounties&type=3&sort=1&dir=1&admin=true");
	OutputClass::addnav("R?Refresh List","runmodule.php?module=dag&manage=true&admin=true");

	OutputClass::rawoutput("<form action='runmodule.php?module=dag&manage=true&op=viewbounties&type=search&admin=true' method='POST'>");
	OutputClass::addnav("","runmodule.php?module=dag&manage=true&op=viewbounties&type=search&admin=true");
	OutputClass::output("Setter: ");
	OutputClass::rawoutput("<input name='setter' value=\"".htmlentities(stripslashes(httppost('setter')))."\">");
	OutputClass::output(" Winner: ");
	OutputClass::rawoutput("<input name='getter' value=\"".htmlentities(stripslashes(httppost('getter')))."\">");
	OutputClass::output(" Target: ");
	OutputClass::rawoutput("<input name='target' value=\"".htmlentities(stripslashes(httppost('target')))."\">");
	OutputClass::output_notl("`n");
	OutputClass::output("Order by: ");
	$id = Translator::translate_inline("ID");
	$amt = Translator::translate_inline("Amount");
	$targ = Translator::translate_inline("Target");
	$set = Translator::translate_inline("Setter");
	$sdate = Translator::translate_inline("Set Date");
	$stat = Translator::translate_inline("Status");
	$win = Translator::translate_inline("Winner");
	$wdate = Translator::translate_inline("Win Date");
	$desc = Translator::translate_inline("Descending");
	$asc = Translator::translate_inline("Ascending");
	$search = Translator::translate_inline("Search");
	OutputClass::rawoutput("<select name='s'>
		<option value='1'".(httppost('s')=='1'?" selected":"").">$id</option>
		<option value='2'".(httppost('s')=='2'?" selected":"").">$amt</option>
		<option value='3'".(httppost('s')=='3'?" selected":"").">$targ</option>
		<option value='4'".(httppost('s')=='4'?" selected":"").">$set</option>
		<option value='5'".(httppost('s')=='5'?" selected":"").">$sdate</option>
		<option value='6'".(httppost('s')=='6'?" selected":"").">$stat</option>
		<option value='7'".(httppost('s')=='7'?" selected":"").">$win</option>
		<option value='8'".(httppost('s')=='8'?" selected":"").">$wdate</option>
		</select>");
	OutputClass::rawoutput("<input type='radio' name='d' value='1'".(httppost('d')==1?" checked":"")."> $desc");
	OutputClass::rawoutput("<input type='radio' name='d' value='2'".(httppost('d')==1?"":" checked")."> $asc");
	OutputClass::output_notl("`n");
	OutputClass::rawoutput("<input type='submit' class='button' value='$search'>");
	OutputClass::rawoutput("</form>");

	$op = Http::httpget('op');
	if ($op == "") {
		// ***ADDED***
		// By Andrew Senger
		// Adding for new Bounty Code
		OutputClass::output_notl("`n`n");
		OutputClass::output("`c`bThe Bounty List`b`c`n");
		$sql = "SELECT bountyid,amount,target,setter,setdate FROM " . db_prefix("bounty") . " WHERE status=0 ORDER BY bountyid ASC";
		$result = db_query($sql);
		OutputClass::rawoutput("<table border=0 cellpadding=2 cellspacing=1 bgcolor='#999999'>");
		$amt = Translator::translate_inline("Amount");
		$lev = Translator::translate_inline("Level");
		$name = Translator::translate_inline("Name");
		$loc = Translator::translate_inline("Location");
		$sex = Translator::translate_inline("Sex");
		$alive = Translator::translate_inline("Alive");
		$last = Translator::translate_inline("Last On");
		OutputClass::rawoutput("<tr class='trhead'><td><b>$amt</b></td><td><b>$lev</b></td><td><b>$name</b></td><td><b>$loc</b></td><td><b>$sex</b></td><td><b>$alive</b></td><td><b>$last</b></td>");
		$listing = array();
		$totlist = 0;
		for($i=0;$i<db_num_rows($result);$i++){
			$row = db_fetch_assoc($result);
				$amount = (int)$row['amount'];
				$sql = "SELECT name,alive,sex,level,laston,loggedin,lastip,uniqueid FROM " . db_prefix("accounts") . " WHERE acctid={$row['target']}";
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
					if($listing[$j]['Name'] == $row2['name']) {
						$listing[$j]['Amount'] = $listing[$j]['Amount'] + $amount;
						$yesno = 1;
					}
				}
				if ($yesno==0) {
					$listing[] = array('Amount'=>$amount,'Level'=>$row2['level'],'Name'=>$row2['name'],'Location'=>$row2['location'],'Sex'=>$row2['sex'],'Alive'=>$row2['alive'],'LastOn'=>$row2['laston']);
					$totlist = $totlist + 1;
				}
		}
		usort($listing, 'dag_sortbounties');
		for($i=0;$i<$totlist;$i++) {
			OutputClass::rawoutput("<tr class='".($i%2?"trdark":"trlight")."'><td>");
			OutputClass::output_notl("`^%s`0", $listing[$i]['Amount']);
			OutputClass::rawoutput("</td><td>");
			OutputClass::output_notl("`^%s`0", $listing[$i]['Level']);
			OutputClass::rawoutput("</td><td>");
			OutputClass::output_notl("`^%s`0", $listing[$i]['Name']);
			OutputClass::rawoutput("</td><td>");
			OutputClass::output($loggedin ? "`#Online`0" : $listing[$i]['Location']);
			OutputClass::rawoutput("</td><td>");
			OutputClass::output($listing[$i]['Sex']?"`!Female`0":"`!Male`0");
			OutputClass::rawoutput("</td><td>");
			OutputClass::output($listing[$i]['Alive']?"`1Yes`0":"`4No`0");
			OutputClass::rawoutput("</td><td>");
			$laston= relativedate($listing[$i]['LastOn']);
			if ($loggedin) $laston=Translator::translate_inline("Now");
			OutputClass::output_notl("%s", $laston);
			OutputClass::rawoutput("</td></tr>");
		}
		OutputClass::rawoutput("</table>");
		OutputClass::output("`n`n`c`bAdd Bounty`b`c`n");
		OutputClass::rawoutput("<form action='runmodule.php?module=dag&manage=true&op=addbounty&admin=true' method='POST'>");
		OutputClass::output("`2Target: ");
		OutputClass::rawoutput("<input name='contractname'>");
		OutputClass::output_notl("`n");
		OutputClass::output("`2Amount to Place: ");
		OutputClass::rawoutput("<input name='amount' id='amount' width='5'>");
		OutputClass::output_notl("`n`n");
		$final = Translator::translate_inline("Finalize Contract");
		OutputClass::rawoutput("<input type='submit' class='button' value='$final'>");
		OutputClass::rawoutput("</form>");
		OutputClass::addnav("","runmodule.php?module=dag&manage=true&op=addbounty&admin=true");
	}else if ($op == "addbounty") {
		if (Http::httpget('subfinal')==1){
			$sql = "SELECT acctid,name,login,level,locked,age,dragonkills,pk,experience FROM " . db_prefix("accounts") . " WHERE name='".addslashes(rawurldecode(stripslashes(httppost('contractname'))))."' AND locked=0";
		}else{
			$contractname = stripslashes(rawurldecode(httppost('contractname')));
			$name="%";
			for ($x=0;$x<strlen($contractname);$x++){
				$name.=substr($contractname,$x,1)."%";
			}
			$sql = "SELECT acctid,name,login,level,locked,age,dragonkills,pk,experience FROM " . db_prefix("accounts") . " WHERE name LIKE '".addslashes($name)."' AND locked=0";
		}
		$result = db_query($sql);
		if (db_num_rows($result) == 0) {
			OutputClass::output("No one by that name!");
		} elseif(db_num_rows($result) > 100) {
			OutputClass::output("Too many names!");
		} elseif(db_num_rows($result) > 1) {
			OutputClass::output("Select the correct name:`n");
			OutputClass::rawoutput("<form action='runmodule.php?module=dag&manage=true&op=addbounty&subfinal=1&admin=true' method='POST'>");
			OutputClass::output("`2Target: ");
			OutputClass::rawoutput("<select name='contractname'>");
			for ($i=0;$i<db_num_rows($result);$i++){
				$row = db_fetch_assoc($result);
				OutputClass::rawoutput("<option value=\"".rawurlencode($row['name'])."\">".full_sanitize($row['name'])."</option>");
			}
			OutputClass::rawoutput("</select>");
			OutputClass::output_notl("`n`n");
			$amount = httppost('amount');
			OutputClass::output("`2Amount to Place: ");
			OutputClass::rawoutput("<input name='amount' id='amount' width='5' value='$amount'>");
			OutputClass::output_notl("`n`n");
			$final = Translator::translate_inline("Finalize Contract");
			OutputClass::rawoutput("<input type='submit' class='button' value='$final'>");
			OutputClass::rawoutput("</form>");
			OutputClass::addnav("","runmodule.php?module=dag&manage=true&op=addbounty&subfinal=1");
		} else {
			// Now, we have just the one, so check it.
			$row  = db_fetch_assoc($result);
			if ($row['locked']) {
				OutputClass::output("Target is a locked user.");
			}
			$amt = (int)httppost('amount');
			if ($amt <= 0) {
				OutputClass::output("That bounty value make no sense.");
			} else {
				// All good!
				$sql = "INSERT INTO " . db_prefix("bounty") . " (amount, target, setter, setdate) VALUES ($amt, ".$row['acctid'].", 0, '".date("Y-m-d H:i:s")."')";
				db_query($sql);
				OutputClass::output("Bounty added!");
			}
		}
	} else if ($op == "viewbounties") {
		$type = Http::httpget('type');
		$sort = Http::httpget('sort');
		$dir = Http::httpget('dir');
		OutputClass::output("`c`bThe Bounty List`b`c`n");
		if ($type == 1) {
			OutputClass::output("`c`bViewing: `3All Bounties`b`c");
		}elseif ($type == 2) {
			OutputClass::output("`c`bViewing: `3Open Bounties`b`c");
		}elseif ($type == 3) {
			OutputClass::output("`c`bViewing: `3Closed Bounties`b`c");
		}
		OutputClass::addnav("Sorting");

		if (($sort == 1) && ($dir == 1)) {
			OutputClass::addnav("1?By BountyID - Asc","runmodule.php?module=dag&manage=true&op=viewbounties&type=".$type."&sort=1&dir=2&admin=true");
			OutputClass::output("`c`bSorting By: `3BountyID - Desc`b`c`n`n");
		}elseif (($sort == 1) && ($dir == 2)) {
			OutputClass::addnav("1?By BountyID - Desc","runmodule.php?module=dag&manage=true&op=viewbounties&type=".$type."&sort=1&dir=1&admin=true");
			OutputClass::output("`c`bSorting By: `3BountyID - Asc`b`c`n`n");
		}else {
			OutputClass::addnav("1?By BountyID - Desc","runmodule.php?module=dag&manage=true&op=viewbounties&type=".$type."&sort=1&dir=1&admin=true");
		}
		if (($sort == 2) && ($dir == 1)) {
			OutputClass::addnav("2?By Amount - Asc","runmodule.php?module=dag&manage=true&op=viewbounties&type=".$type."&sort=2&dir=2&admin=true");
			OutputClass::output("`c`bSorting By: `3Amount - Desc`b`c`n`n");
		}elseif (($sort == 2) && ($dir == 2)) {
			OutputClass::addnav("2?By Amount - Desc","runmodule.php?module=dag&manage=true&op=viewbounties&type=".$type."&sort=2&dir=1&admin=true");
			OutputClass::output("`c`bSorting By: `3Amount - Asc`b`c`n`n");
		}else {
			OutputClass::addnav("2?By Amount - Desc","runmodule.php?module=dag&manage=true&op=viewbounties&type=".$type."&sort=2&dir=1&admin=true");
		}
		if (($sort == 3) && ($dir == 1)) {
			OutputClass::addnav("3?By Target - Asc","runmodule.php?module=dag&manage=true&op=viewbounties&type=".$type."&sort=3&dir=2&admin=true");
			OutputClass::output("`c`bSorting By: `3Target - Desc`b`c`n`n");
		}elseif (($sort == 3) && ($dir == 2)) {
			OutputClass::addnav("3?By Target - Desc","runmodule.php?module=dag&manage=true&op=viewbounties&type=".$type."&sort=3&dir=1&admin=true");
			OutputClass::output("`c`bSorting By: `3Target - Asc`b`c`n`n");
		}else {
			OutputClass::addnav("3?By Target - Desc","runmodule.php?module=dag&manage=true&op=viewbounties&type=".$type."&sort=3&dir=1&admin=true");
		}
		if (($sort == 4) && ($dir == 1)) {
			OutputClass::addnav("4?By Setter - Asc","runmodule.php?module=dag&manage=true&op=viewbounties&type=".$type."&sort=4&dir=2&admin=true");
			OutputClass::output("`c`bSorting By: `3Setter - Desc`b`c`n`n");
		}elseif (($sort == 4) && ($dir == 2)) {
			OutputClass::addnav("4?By Setter - Desc","runmodule.php?module=dag&manage=true&op=viewbounties&type=".$type."&sort=4&dir=1&admin=true");
			OutputClass::output("`c`bSorting By: `3Setter - Asc`b`c`n`n");
		}else {
			OutputClass::addnav("4?By Setter - Desc","runmodule.php?module=dag&manage=true&op=viewbounties&type=".$type."&sort=4&dir=1&admin=true");
		}
		if (($sort == 5) && ($dir == 1)) {
			OutputClass::addnav("5?By Set Date - Asc","runmodule.php?module=dag&manage=true&op=viewbounties&type=".$type."&sort=5&dir=2&admin=true");
			OutputClass::output("`c`bSorting By: `3Set Date - Desc`b`c`n`n");
		}elseif (($sort == 5) && ($dir == 2)) {
			OutputClass::addnav("5?By Set Date - Desc","runmodule.php?module=dag&manage=true&op=viewbounties&type=".$type."&sort=5&dir=1&admin=true");
			OutputClass::output("`c`bSorting By: `3Set Date - Asc`b`c`n`n");
		}else {
			OutputClass::addnav("5?By Set Date - Desc","runmodule.php?module=dag&manage=true&op=viewbounties&type=".$type."&sort=5&dir=1&admin=true");
		}
		if ($type == 1) {
			if (($sort == 6) && ($dir == 1)) {
				OutputClass::addnav("6?By Status - Asc","runmodule.php?module=dag&manage=true&op=viewbounties&type=".$type."&sort=6&dir=2&admin=true");
				OutputClass::output("`c`bSorting By: `3Status - Desc`b`c`n`n");
			}elseif (($sort == 6) && ($dir == 2)) {
				OutputClass::addnav("6?By Status - Desc","runmodule.php?module=dag&manage=true&op=viewbounties&type=".$type."&sort=6&dir=1&admin=true");
				OutputClass::output("`c`bSorting By: `3Status - Asc`b`c`n`n");
			}else {
				OutputClass::addnav("6?By Status - Desc","runmodule.php?module=dag&manage=true&op=viewbounties&type=".$type."&sort=6&dir=1&admin=true");
			}
		}
		if (($type == 1) || ($type == 3)) {
			if (($sort == 7) && ($dir == 1)) {
				OutputClass::addnav("7?By Winner - Asc","runmodule.php?module=dag&manage=true&op=viewbounties&type=".$type."&sort=7&dir=2&admin=true");
				OutputClass::output("`c`bSorting By: `3Winner - Desc`b`c`n`n");
			}elseif (($sort == 7) && ($dir == 2)) {
				OutputClass::addnav("7?By Winner - Desc","runmodule.php?module=dag&manage=true&op=viewbounties&type=".$type."&sort=7&dir=1&admin=true");
				OutputClass::output("`c`bSorting By: `3Winner - Asc`b`c`n`n");
			}else {
				OutputClass::addnav("7?By Winner - Desc","runmodule.php?module=dag&manage=true&op=viewbounties&type=".$type."&sort=7&dir=1&admin=true");
			}

			if (($sort == 8) && ($dir == 1)) {
				OutputClass::addnav("8?By Win Date - Asc","runmodule.php?module=dag&manage=true&op=viewbounties&type=".$type."&sort=8&dir=2&admin=true");
				OutputClass::output("`c`bSorting By: `3Win Date - Desc`b`c`n`n");
			}elseif (($sort == 8) && ($dir == 2)) {
				OutputClass::addnav("8?By Win Date - Desc","runmodule.php?module=dag&manage=true&op=viewbounties&type=".$type."&sort=8&dir=1&admin=true");
				OutputClass::output("`c`bSorting By: `3Win Date - Asc`b`c`n`n");
			}else {
				OutputClass::addnav("8?By Win Date - Desc","runmodule.php?module=dag&manage=true&op=viewbounties&type=".$type."&sort=8&dir=1&admin=true");
			}
		}
		OutputClass::addnav("Return to Bounty Home","runmodule.php?module=dag&manage=true&op=bounties&admin=true");
		switch ($type) {
			case 1:
				$t = "";
				break;
			case 2:
				$t = " WHERE status=0";
				break;
			case 3:
				$t = " WHERE status=1";
				break;
		}
		switch  ($sort) {
			case 1:
				$s = " ORDER BY bountyid";
				break;
			case 2:
				$s = " ORDER BY amount";
				break;
			case 3:
				$s = " ORDER BY target";
				break;
			case 4:
				$s = " ORDER BY setter";
				break;
			case 5:
				$s = " ORDER BY setdate";
				break;
			case 6:
				$s = " ORDER BY status";
				break;
			case 7:
				$s = " ORDER BY winner";
				break;
			case 8:
				$s = " ORDER BY windate";
				break;
		}
		switch ($dir) {
			case 1:
				$d = " DESC";
				break;
			case 2:
				$d = " ASC";
				break;
		}
		//override those options in favor of the search form if it exists
		if ($type=='search'){
			switch(httppost('s')){
				case 1: $s = " ORDER BY bountyid"; break;
				case 2: $s = " ORDER BY amount"; break;
				case 3: $s = " ORDER BY target"; break;
				case 4: $s = " ORDER BY setter"; break;
				case 5: $s = " ORDER BY setdate"; break;
				case 6: $s = " ORDER BY status"; break;
				case 7: $s = " ORDER BY winner"; break;
				case 8: $s = " ORDER BY windate"; break;
			}
			switch(httppost('d')){
				case 1: $d = " DESC"; break;
				case 2: $d = " ASC"; break;
			}
			$t = "";
			if (httppost('setter')>'') {
				if ($t>"") $t.=" AND";
				$a = httppost('setter');
				$setter = "%";
				for ($i=0;$i<strlen($a);$i++){
					$setter.=$a[$i]."%";
				}
				$sql = "SELECT acctid FROM " . db_prefix("accounts") . " WHERE name LIKE '$setter'";
				$result = db_query($sql);
				$ids = array();
				while ($row = db_fetch_assoc($result)){
					array_push($ids,$row['acctid']);
				}
				if (count($ids)==0) $ids[0]=0;
				$t .= " setter IN (".join(",",$ids).")";
			}
			if (httppost('getter')>'') {
				if ($t>"") $t.=" AND";
				$a = httppost('getter');
				$getter = "%";
				for ($i=0;$i<strlen($a);$i++){
					$getter.=$a[$i]."%";
				}
				$sql = "SELECT acctid FROM " . db_prefix("accounts") . " WHERE name LIKE '$getter'";
				$result = db_query($sql);
				$ids = array();
				while ($row = db_fetch_assoc($result)){
					array_push($ids,$row['acctid']);
				}
				if (count($ids)==0) $ids[0]=0;
				$t .= " winner IN (".join(",",$ids).")";
			}
			if (httppost('target')>'') {
				if ($t>"") $t.=" AND";
				$a = httppost('target');
				$target = "%";
				for ($i=0;$i<strlen($a);$i++){
					$target.=$a[$i]."%";
				}
				$sql = "SELECT acctid FROM " . db_prefix("accounts") . " WHERE name LIKE '$target'";
				$result = db_query($sql);
				$ids = array();
				while ($row = db_fetch_assoc($result)){
					array_push($ids,$row['acctid']);
				}
				if (count($ids)==0) $ids[0]=0;
				$t .= " target IN (".join(",",$ids).")";
			}
			if ($t>"") $t = " WHERE".$t;
		}
		$sql = "SELECT bountyid,amount,target,setter,setdate,status,winner,windate FROM " . db_prefix("bounty").$t.$s.$d;
		$result = db_query($sql);
		OutputClass::rawoutput("<table border=0 cellpadding=2 cellspacing=1 bgcolor='#999999'>");
		$id = Translator::translate_inline("ID");
		$amt = Translator::translate_inline("Amt");
		$targ = Translator::translate_inline("Target");
		$set = Translator::translate_inline("Setter");
		$sdate = Translator::translate_inline("Set Date/Time");
		$stat = Translator::translate_inline("Status");
		$win = Translator::translate_inline("Winner");
		$wdate = Translator::translate_inline("Win Date/Time");
		$ops = Translator::translate_inline("Ops");

		OutputClass::rawoutput("<tr class='trhead'><td><b>$id</b></td><td><b>$amt</b></td><td><b>$targ</b></td><td><b>$set</b></td><td><b>$sdate</b></td><td><b>$stat</b></td><td><b>$win</b></td><td><b>$wdate</b></td><td>$ops</td></tr>");
		for($i=0;$i<db_num_rows($result);$i++){
			$row = db_fetch_assoc($result);
			if ($row['target']==0) {
				$target['name'] = Translator::translate_inline("`2Green Dragon");
			} else {
				$sql = "SELECT name FROM " . db_prefix("accounts") . " WHERE acctid=".(int)$row['target'];
				$result2 = db_query($sql);
				if (db_num_rows($result2) == 0) {
					$target['name'] = Translator::translate_inline("`4Deleted Character");
				} else {
					$target = db_fetch_assoc($result2);
				}
			}
			if ($row['setter']==0) {
				$setter['name'] = Translator::translate_inline("`2Green Dragon");
			}else {
				$sql = "SELECT name FROM " . db_prefix("accounts") . " WHERE acctid=".(int)$row['setter'];
				$result3 = db_query($sql);
				if (db_num_rows($result3) == 0) {
					$setter['name'] = Translator::translate_inline("`4Deleted Character");
				} else {
					$setter = db_fetch_assoc($result3);
				}
			}
			$winner['name'] = "";
			if (($row['winner']==0) && $row['status'] == 1) {
				$winner['name'] = Translator::translate_inline("`2Green Dragon");
			} elseif ($row['status'] == 1) {
				$sql = "SELECT name FROM " . db_prefix("accounts") . " WHERE acctid=".(int)$row['winner'];
					$result4 = db_query($sql);
				if (db_num_rows($result4) == 0) {
					$winner['name'] = Translator::translate_inline("`2Deleted Character");
				} else {
					$winner = db_fetch_assoc($result4);
				}
			}
			OutputClass::rawoutput("<tr class='".($i%2?"trdark":"trlight")."'><td>");
			OutputClass::output_notl("`^%s`0", $row['bountyid']);
			OutputClass::rawoutput("</td><td>");
			OutputClass::output_notl("`^%s`0", $row['amount']);
			OutputClass::rawoutput("</td><td>");
			OutputClass::output_notl("`&%s`0", $target['name']);
			OutputClass::rawoutput("</td><td>");
			OutputClass::output_notl("`^%s`0", $setter['name']);
			OutputClass::rawoutput("</td><td>");
			OutputClass::output_notl("`^%s`0", $row['setdate']);
			OutputClass::rawoutput("</td><td>");
			OutputClass::output($row['status']==0?"`^Open`0":"`^Closed`0");
			OutputClass::rawoutput("</td><td>");
			OutputClass::output_notl("`^%s`0", $winner['name']);
			OutputClass::rawoutput("</td><td>");
			OutputClass::output_notl("`^%s`0", $row['status']?$row['windate']:"");
			OutputClass::rawoutput("</td><td>");
			if ($row['status'] == 0) {
				$link = "runmodule.php?module=dag&manage=true&op=closebounty&id={$row['bountyid']}&admin=true";
				$close = Translator::translate_inline("Close");
				OutputClass::rawoutput("<a href=\"$link\">$close</a>");
				OutputClass::addnav("",$link);
			} else {
				OutputClass::rawoutput("&nbsp;");
			}
			OutputClass::rawoutput("</td></tr>");
		}
		OutputClass::rawoutput("</table>");
	} else if ($op == "closebounty") {
		$windate = date("Y-m-d H:i:s");
		$bountyid = (int)Http::httpget('id');
		$sql = "UPDATE " . db_prefix("bounty") . " SET status=1,winner=0,windate=\"$windate\" WHERE bountyid=$bountyid";
		db_query($sql);
		OutputClass::output("Bounty closed.");
	// ***END ADD***
	}
	page_footer();
}

function dag_pvpwin($args){
	global $badguy,$session;
	// ***ADDED***
	// By Andrew Senger
	// Added for Bounty Code
	// Bounty Check - Andrew Senger
	// Check for Bounty
	$sql = "SELECT bountyid,amount,setter FROM " . db_prefix("bounty") . " WHERE status=0 AND setdate<='".date("Y-m-d H:i:s")."' AND target=".$badguy['acctid'];
	$result = db_query($sql);
	if (db_num_rows($result) > 0) {
		$totgoodamt = 0;
		$totbadamt = 0;
		for($i=0;$i<db_num_rows($result);$i++){
			$row = db_fetch_assoc($result);
			if ($row['setter'] == $session['user']['acctid']) {
				$totbadamt += $row['amount'];
			} else {
				$totgoodamt += $row['amount'];
				$windate = date("Y-m-d H:i:s");
				$sql = "UPDATE " . db_prefix("bounty") . " SET status=1,winner=".$session['user']['acctid'].",windate=\"$windate\" WHERE bountyid=".$row['bountyid'];
				db_query($sql);
			}
		}
		if ($totgoodamt > 0) {
			OutputClass::output("`@When you turn around, Dag Durnick is standing there.");
			OutputClass::output("\"%s`# had a bounty of `^%s`# on th' head`@\", he says as he tosses you a leather purse which clinks with the sounds of your new fortune.`n`n", $badguy['creaturename'], $totgoodamt);
		}
		if ($totbadamt > 0) {
			OutputClass::output("\"`#I'm keeping `^%s`# of the total bounty on this soul's head, as ye' be the one that set it.`@\"", $totbadamt);
		}
	}
	// End Check for Bounty
	// Add Bounty Gold
	if ($totgoodamt > 0) {
		$session['user']['gold']+=$totgoodamt;
		debuglog("gained ".$totgoodamt." gold bounty for killing ", $badguy['acctid']);
	}
	// End Add Bounty Gold
	// Add Bounty Kill to News
	if ($totgoodamt > 0) {
		addnews("`4%s`3 collected `4%s`3 gold bounty by turning in `4%s`3's head!",$session['user']['name'],$totgoodamt,$badguy['creaturename']);
	}
	// End Add Bounty Kill to News
	// End Bounty Check - Andrew
	// ***END ADD***
	if ($totgoodamt > 0) {
		$args['pvpmessageadd'] .= sprintf_translate("`nThey also received `^%s`2 in bounty gold.`n", $totgoodamt);
		OutputClass::rawoutput(tlbutton_clear());
	}
	return $args;
}
?>
