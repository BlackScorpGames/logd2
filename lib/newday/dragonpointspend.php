<?php
if ($dkills-$dp > 1) {
	PageParts::page_header("Dragon Points");
	OutputClass::output("`@You earn one dragon point each time you slay the dragon.");
	OutputClass::output("Advancements made by spending dragon points are permanent!");
	OutputClass::output("`n`nYou have `^%s`@ unspent dragon points.", $dkills-$dp);
	OutputClass::output("How do you wish to spend them?`n`n");
	OutputClass::output("Be sure that your allocations add up to your total unspent dragon points.");
	$text = "<script type='text/javascript' language='Javascript'>
	<!--
	function pointsLeft() {
			var form = document.getElementById(\"dkForm\");
	";
	reset($labels);
	foreach($labels as $type=>$label) {
		if (isset($canbuy[$type]) && $canbuy[$type]) {
			$text .= "var $type = parseInt(form.$type.value);
			";
		}
	}
	reset($labels);
	foreach($labels as $type=>$label) {
		if (isset($canbuy[$type]) && $canbuy[$type]) {
			$text .= "if (isNaN($type)) $type = 0;
			";
		}
	}
	$text .= "var val = $dkills - $dp ";
	foreach($labels as $type=>$label) {
		if (isset($canbuy[$type]) && $canbuy[$type]) {
			$text .= "- $type";
		}
	}
	$text .= ";
			var absval = Math.abs(val);
			var points = 'points';
			if (absval == 1) points = 'point';
				if (val >= 0)
				document.getElementById(\"amtLeft\").innerHTML = \"<span class='colLtWhite'>You have </span><span class='colLtYellow'>\"+absval+\"</span><span class='colLtWhite'> \"+points+\" left to spend.</span><br />\";
			else
				document.getElementById(\"amtLeft\").innerHTML = \"<span class='colLtWhite'>You have spent </span><span class='colLtRed'>\"+absval+\"</span><span class='colLtWhite'> \"+points+\" too many!</span><br />\";
		}
	// -->
	</script>\n";
	OutputClass::rawoutput($text);
	OutputClass::addnav("Reset", "newday.php?pdk=0$resline");
		$link = appendcount("newday.php?pdk=1$resline");
		OutputClass::rawoutput("<form id='dkForm' action='$link' method='POST'>");
	OutputClass::addnav("",$link);
	OutputClass::rawoutput("<table cellpadding='0' cellspacing='0' border='0' width='200'>");
	reset($labels);
	foreach($labels as $type=>$label) {
		if (isset($canbuy[$type]) && $canbuy[$type]) {
			OutputClass::rawoutput("<tr><td nowrap>");
			OutputClass::output($label);
			OutputClass::output_notl(":");
			OutputClass::rawoutput("</td><td>");
			OutputClass::rawoutput("<input id='$type' name='$type' size='4' maxlength='4' value='{$pdks[$type]}' onKeyUp='pointsLeft();' onBlur='pointsLeft();' onFocus='pointsLeft();'>");
			OutputClass::rawoutput("</td></tr>");
		}
	}
	OutputClass::rawoutput("<tr><td colspan='2'>&nbsp;");
	OutputClass::rawoutput("</td></tr><tr><td colspan='2' align='center'>");
	$click = Translator::translate_inline("Spend");
	OutputClass::rawoutput("<input id='dksub' type='submit' class='button' value='$click'>");
	OutputClass::rawoutput("</td></tr><tr><td colspan='2'>&nbsp;");
	OutputClass::rawoutput("</td></tr><tr><td colspan='2' align='center'>");
	OutputClass::rawoutput("<div id='amtLeft'></div>");
	OutputClass::rawoutput("</td></tr>");
	OutputClass::rawoutput("</table>");
	OutputClass::rawoutput("</form>");
	reset($labels);
	$count = 0;
	foreach($labels as $type=>$label) {
		if ($count > 0) break;
		if (isset($canbuy[$type]) && $canbuy[$type]) {
			OutputClass::rawoutput("<script language='JavaScript'>document.getElementById('$type').focus();</script>");
			$count++;
		}
	}
}else{
	PageParts::page_header("Dragon Points");
	reset ($labels);
	$dist = array();
	foreach ($labels as $type=>$label) {
		$dist[$type] = 0;  // Initialize the distribution
		if (isset($canbuy[$type]) && $canbuy[$type]) {
			OutputClass::addnav($label, "newday.php?dk=$type$resline");
		}
	}
		OutputClass::output("`@You have `&1`@ unspent dragon point.");
	OutputClass::output("How do you wish to spend it?`n`n");
	OutputClass::output("You earn one dragon point each time you slay the dragon.");
	OutputClass::output("Advancements made by spending dragon points are permanent!");
		for ($i=0; $i<count($session['user']['dragonpoints']); $i++) {
		if (isset($dist[$session['user']['dragonpoints'][$i]])) {
			$dist[$session['user']['dragonpoints'][$i]]++;
		} else {
			$dist['unknown']++;
		}
	}
		OutputClass::output("`n`nCurrently, the dragon points you have already spent are distributed in the following manner.");
	OutputClass::rawoutput("<blockquote>");
	OutputClass::rawoutput("<table>");
	reset ($labels);
	foreach ($labels as $type=>$label) {
		if ($type == 'unknown' && $dist[$type] == 0) continue;
		OutputClass::rawoutput("<tr><td nowrap>");
		OutputClass::output($label);
		OutputClass::output_notl(":");
		OutputClass::rawoutput("</td><td>&nbsp;&nbsp;</td><td>");
		OutputClass::output_notl("`@%s", $dist[$type]);
		OutputClass::rawoutput("</td></tr>");
	}
	OutputClass::rawoutput("</table>");
	OutputClass::rawoutput("</blockquote>");
}
?>