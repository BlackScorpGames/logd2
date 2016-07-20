<?php
// translator ready
// addnews ready
// mail ready
require_once("common.php");
require_once("lib/dhms.php");
require_once("lib/http.php");

Translator::tlschema("referers");

SuAccess::check_su_access(SU_EDIT_CONFIG);

$expire = Settings::getsetting("expirecontent",180);
if($expire > 0) $sql = "DELETE FROM " . db_prefix("referers") . " WHERE last<'".date("Y-m-d H:i:s",strtotime("-".$expire." days"))."'";
db_query($sql);
$op = Http::httpget('op');

if ($op=="rebuild"){
	$sql = "SELECT * FROM " . db_prefix("referers");
	$result = db_query($sql);
	$number=db_num_rows($result);
	for ($i=0;$i<$number;$i++){
		$row = db_fetch_assoc($result);
		$site = str_replace("http://","",$row['uri']);
		if (strpos($site,"/")) $site = substr($site,0,strpos($site,"/"));
		$sql = "UPDATE " . db_prefix("referers") . " SET site='".addslashes($site)."' WHERE refererid='{$row['refererid']}'";
		db_query($sql);
	}
}
require_once("lib/superusernav.php");
superusernav();
OutputClass::addnav("Referer Options");
OutputClass::addnav("",$_SERVER['REQUEST_URI']);
$sort = Http::httpget('sort');
OutputClass::addnav("Refresh","referers.php?sort=".URLEncode($sort)."");
OutputClass::addnav("C?Sort by Count","referers.php?sort=count".($sort=="count DESC"?"":"+DESC"));
OutputClass::addnav("U?Sort by URL","referers.php?sort=uri".($sort=="uri"?"+DESC":""));
OutputClass::addnav("T?Sort by Time","referers.php?sort=last".($sort=="last DESC"?"":"+DESC"));

OutputClass::addnav("Rebuild Sites","referers.php?op=rebuild");

PageParts::page_header("Referers");
$order = "count DESC";
if ($sort!="") $order=$sort;
$sql = "SELECT SUM(count) AS count, MAX(last) AS last,site FROM " . db_prefix("referers") . " GROUP BY site ORDER BY $order LIMIT 100";
$count = Translator::translate_inline("Count");
$last = Translator::translate_inline("Last");
$dest = Translator::translate_inline("Destination");
$none = Translator::translate_inline("`iNone`i");
$notset = Translator::translate_inline("`iNot set`i");
$skipped = Translator::translate_inline("`i%s records skipped (over a week old)`i");
OutputClass::rawoutput("<table border=0 cellpadding=2 cellspacing=1><tr class='trhead'><td>$count</td><td>$last</td><td>URL</td><td>$dest</td><td>IP</td></tr>");
$result = db_query($sql);
$number=db_num_rows($result);
for ($i=0;$i<$number;$i++){
	$row = db_fetch_assoc($result);

	OutputClass::rawoutput("<tr class='trdark'><td valign='top'>");
	OutputClass::output_notl("`b".$row['count']."`b");
	OutputClass::rawoutput("</td><td valign='top'>");
	$diffsecs = strtotime("now")-strtotime($row['last']);
	//OutputClass::output((int)($diffsecs/86400)."d ".(int)($diffsecs/3600%3600)."h ".(int)($diffsecs/60%60)."m ".(int)($diffsecs%60)."s");
	OutputClass::output_notl("`b".dhms($diffsecs)."`b");
	OutputClass::rawoutput("</td><td valign='top' colspan='3'>");
	OutputClass::output_notl("`b".($row['site']==""?$none:$row['site'])."`b");
	OutputClass::rawoutput("</td></tr>");

	$sql = "SELECT count,last,uri,dest,ip FROM " . db_prefix("referers") . " WHERE site='".addslashes($row['site'])."' ORDER BY {$order} LIMIT 25";
	$result1 = db_query($sql);
	$skippedcount=0;
	$skippedtotal=0;
	$number=db_num_rows($result1);
	for ($k=0;$k<$number;$k++){
		$row1=db_fetch_assoc($result1);
		$diffsecs = strtotime("now")-strtotime($row1['last']);
		if ($diffsecs<=604800){
			OutputClass::rawoutput("<tr class='trlight'><td>");
			OutputClass::output_notl($row1['count']);
			OutputClass::rawoutput("</td><td valign='top'>");
			//OutputClass::output((int)($diffsecs/86400)."d".(int)($diffsecs/3600%3600)."h".(int)($diffsecs/60%60)."m".(int)($diffsecs%60)."s");
			OutputClass::output_notl(dhms($diffsecs));
			OutputClass::rawoutput("</td><td valign='top'>");
			if ($row1['uri']>"")
				OutputClass::rawoutput("<a href='".HTMLEntities($row1['uri'], ENT_COMPAT, Settings::getsetting("charset", "ISO-8859-1"))."' target='_blank'>".HTMLEntities(substr($row1['uri'],0,100))."</a>");
			else
				OutputClass::output_notl($none);
			OutputClass::output_notl("`n");
			OutputClass::rawoutput("</td><td valign='top'>");
			OutputClass::output_notl($row1['dest']==''?$notset:$row1['dest']);
			OutputClass::rawoutput("</td><td valign='top'>");
			OutputClass::output_notl($row1['ip']==''?$notset:$row1['ip']);
			OutputClass::rawoutput("</td></tr>");
		}else{
			$skippedcount++;
			$skippedtotal+=$row1['count'];
		}
	}
	if ($skippedcount>0){
		OutputClass::rawoutput("<tr class='trlight'><td>$skippedtotal</td><td valign='top' colspan='4'>");
		OutputClass::output_notl(sprintf($skipped,$skippedcount));
		OutputClass::rawoutput("</td></tr>");
	}
	//OutputClass::output("</td></tr>",true);
}
OutputClass::rawoutput("</table>");
PageParts::page_footer();
?>