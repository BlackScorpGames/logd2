<?php
// translator ready
// addnews ready
// mail ready
require_once("common.php");
require_once("lib/http.php");
require_once("lib/buffs.php");
require_once("lib/sanitize.php");
require_once("lib/villagenav.php");

Translator::tlschema('stables');

$basetext=array(
	"title"=>"Merick's Stables",
	"desc"=>array(
		"`7Behind the inn, and a little to the left of Ye Olde Bank, is as fine a stable as one might expect to find in any village. ",
		"In it, Merick, a burly looking dwarf tends to various beasts.`n`n",
		array("You approach, and he whirls around, pointing a pitchfork in your general direction, \"`&Ach, sorry m'%s, I dinnae hear ya' comin' up on me, an' I thoht fer sure ye were %s`&; he what been tryin' to improve on his dwarf tossin' skills. ",Translator::translate_inline($session['user']['sex']?'lass':'lad'),Settings::getsetting('barkeep','`tCedrik')),
		"Naahw, wha' can oye do fer ya?`7\" he asks.",
	),
	"nosuchbeast"=>"`7\"`&Ach, thar dinnae be any such beestie here!`7\" shouts the dwarf!",
	"finebeast"=>array(
		"`7\"`&Aye, tha' be a foyne beastie indeed!`7\" comments the dwarf.`n`n",
		"`7\"`&Ye cert'nly have an oye fer quality!`7\" exclaims the dwarf.`n`n",
		"`7\"`&Och, this beastie will serve ye well indeed,`7\" says the dwarf.`n`n",
		"`7\"`&That beastie be one o' me finest!`7\" says the dwarf with pride.`n`n",
		"`7\"`&Ye couldnae hae made a foyner choice o' beasts!`7\" says the dwarf with pride.`n`n"
	),
	"toolittle"=>"`7Merick looks at you sorta sideways.  \"`&'Ere, whadday ya think yeer doin'?  Cannae ye see that %s`& costs `^%s`& gold an' `%%s`& gems?`7\"",
	"replacemount"=>"`7You hand over the reins to %s`7 and the purchase price of your new critter, and Merick leads out a fine new `&%s`7 for you!`n`n",
	"newmount"=>"`7You hand over the purchase price of your new critter, and Merick leads out a fine `&%s`7 for you!`n`n",
	"nofeed"=>"`7\"`&Ach, m'%s, what dae ye think this is, a hostelry?  I cannae feed yer critter here!`7\"`nMerick thumps you on the back good naturedly, and sends you on your way.",
	"nothungry"=>"%s`7 isn't hungry.  Merick hands your gold back.",
	"halfhungry"=>"%s`7 pinches a bit of the given food and leaves the rest alone. %s`7 is fully restored. Because there is still more than half of the food left, Merick gives you 50%% discount.`nYou only pay %s gold.",
	"hungry"=>"%s`7 eats all the food greedily.`n%s`7 is fully restored and you give your %s gold to Merick.",
	"mountfull"=>"`n`7\"`&Aye, there ye go %s, yer %s`& be full o' foyne grub. I willnae be able t' feed 'em again 'til the morrow though.  Well, enjoy ye day!`7\"`nMerick whistles a jaunty tune and heads back to work.",
	"nofeedgold"=>"`7You don't have enough gold with you to pay for the food. Merick refuses to feed your creature and advises you to look for somewhere else to let %s`7 graze for free, such as in the `@Forest`7.",
	"confirmsale"=>"`n`n`7Merick whistles.  \"`&Yer mount shure is a foyne one, %s. Are ye sure ye wish t' part wae it?`7\"`n`nHe waits for your answer.`0",
	"mountsold"=>"`7As sad as it is to do so, you give up your precious %s`7, and a lone tear escapes your eye.`n`nHowever, the moment you spot the %s, you find that you're feeling quite a bit better.",
	"offer"=>"`n`n`&Merick offers you `^%s`& gold and `%%s`& gems for %s`7.",
	"lass"=>"lass",
	"lad"=>"lad",
);
$schemas = array(
	'title'=>'stables',
	'desc'=>'stables',
	'nosuchbeast'=>'stables',
	'finebeast'=>'stables',
	'toolittle'=>'stables',
	'replacemount'=>'stables',
	'newmount'=>'stables',
	'nofeed'=>'stables',
	'nothungry'=>'stables',
	'halfhungry'=>'stables',
	'hungry'=>'stables',
	'mountfull'=>'stables',
	'nofeedgold'=>'stables',
	'confirmsale'=>'stables',
	'mountsold'=>'stables',
	'offer'=>'stables',
);
$basetext['schemas']=$schemas;
$texts = Modules::modulehook("stabletext", $basetext);
$schemas = $texts['schemas'];

Translator::tlschema($schemas['title']);
PageParts::page_header($texts['title']);
Translator::tlschema();

OutputClass::addnav("Other");
VillageNavClass::villagenav();
Modules::modulehook("stables-nav");

require_once("lib/mountname.php");
list($name, $lcname) = MountName::getmountname();

$repaygold = 0;
$repaygems = 0;
$grubprice = 0;

if ($playermount) {
	$repaygold = round($playermount['mountcostgold']*2/3,0);
	$repaygems = round($playermount['mountcostgems']*2/3,0);
	$grubprice = round($session['user']['level']*$playermount['mountfeedcost'], 0);
}
$confirm = 0;

$op = Http::httpget('op');
$id = Http::httpget('id');

global $playermount;

if ($op==""){
	GameDateTime::checkday();
	Translator::tlschema($schemas['desc']);
  	if (is_array($texts['desc'])) {
  		foreach ($texts['desc'] as $description) {
  			OutputClass::output_notl(Translator::sprintf_translate($description));
  		}
  	} else {
  		OutputClass::output($texts['desc']);
  	}
	Translator::tlschema();
	Modules::modulehook("stables-desc");
}elseif($op=="examine"){
	$sql = "SELECT * FROM " . db_prefix("mounts") . " WHERE mountid='$id'";
	$result = db_query_cached($sql, "mountdata-$id", 3600);
	if (db_num_rows($result)<=0){
		Translator::tlschema($schemas['nosuchbeast']);
		OutputClass::output($texts['nosuchbeast']);
		Translator::tlschema();
	}else{
		// Idea taken from Robert of dragonprime.cawsquad.net
		$t = Erand::e_rand(0,count($texts['finebeast'])-1);
		Translator::tlschema($schemas['finebeast']);
		OutputClass::output($texts['finebeast'][$t]);
		Translator::tlschema();
		$mount = db_fetch_assoc($result);
		OutputClass::output("`7Creature: `&%s`0`n", $mount['mountname']);
		OutputClass::output("`7Description: `&%s`0`n", $mount['mountdesc']);
		OutputClass::output("`7Cost: `^%s`& gold, `%%s`& gems`n`n", $mount['mountcostgold'], $mount['mountcostgems']);
		OutputClass::addnav(array("New %s", $mount['mountname']));
		OutputClass::addnav("Buy this creature","stables.php?op=buymount&id={$mount['mountid']}");
	}
}elseif($op=='buymount'){
	if ($session['user']['hashorse']) {
		Translator::tlschema($schemas['confirmsale']);
		OutputClass::output($texts['confirmsale'],
				($session['user']['sex']?$texts["lass"]:$texts["lad"]));
		Translator::tlschema();
		OutputClass::addnav("Confirm trade");
		OutputClass::addnav("Yes", "stables.php?op=confirmbuy&id=$id");
		OutputClass::addnav("No","stables.php");
		$confirm = 1;
	} else {
		$op="confirmbuy";
		Http::httpset("op",$op);
	}
}
if ($op == 'confirmbuy') {
	$sql = "SELECT * FROM " . db_prefix("mounts") . " WHERE mountid='$id'";
	$result = db_query_cached($sql, "mountdata-$id", 3600);
	if (db_num_rows($result)<=0){
		Translator::tlschema($schemas['nosuchbeast']);
		OutputClass::output($texts['nosuchbeast']);
		Translator::tlschema();
	}else{
		$mount = db_fetch_assoc($result);
		if (($session['user']['gold']+$repaygold) < $mount['mountcostgold'] ||
			($session['user']['gems']+$repaygems) < $mount['mountcostgems']){
			Translator::tlschema($schemas['toolittle']);
			OutputClass::output($texts['toolittle'], $mount['mountname'], $mount['mountcostgold'], $mount['mountcostgems']);
			Translator::tlschema();
		}else{
			if ($session['user']['hashorse']>0){
				Translator::tlschema($schemas['replacemount']);
				OutputClass::output($texts['replacemount'], $lcname, $mount['mountname']);
				Translator::tlschema();
			}else{
				Translator::tlschema($schemas['newmount']);
				OutputClass::output($texts['newmount'], $mount['mountname']);
				Translator::tlschema();
			}
			$debugmount1=isset($playermount['mountname'])?$playermount['mountname']:false;
			if ($debugmount1) $debugmount1="a ".$debugmount1;
			$session['user']['hashorse']=$mount['mountid'];
			$debugmount2=$mount['mountname'];
			$goldcost = $repaygold-$mount['mountcostgold'];
			$session['user']['gold']+=$goldcost;
			$gemcost = $repaygems-$mount['mountcostgems'];
			$session['user']['gems']+=$gemcost;
			DebugLogClass::debuglog(($goldcost <= 0?"spent ":"gained ") . abs($goldcost) . " gold and " . ($gemcost <= 0?"spent ":"gained ") . abs($gemcost) . " gems trading $debugmount1 for a new mount, a $debugmount2");
			$buff = unserialize($mount['mountbuff']);
			if ($buff['schema'] == "") $buff['schema'] = "mounts";
			Buffs::apply_buff('mount',unserialize($mount['mountbuff']));
			// Recalculate so the selling stuff works right
			$playermount = Mounts::getmount($mount['mountid']);
			$repaygold = round($playermount['mountcostgold']*2/3,0);
			$repaygems = round($playermount['mountcostgems']*2/3,0);
			// Recalculate the special name as well.
			Modules::modulehook("stable-mount", array());
			Modules::modulehook("boughtmount");
			require_once("lib/mountname.php");
			list($name, $lcname) = MountName::getmountname();
			$grubprice = round($session['user']['level']*$playermount['mountfeedcost'], 0);
		}
	}
}elseif($op=='feed'){
	if (Settings::getsetting("allowfeed", 0) == 0) {
		Translator::tlschema($schemas['nofeed']);
		OutputClass::output($texts['nofeed'],
				($session['user']['sex']?$texts["lass"]:$texts["lad"]));
		Translator::tlschema();
	} elseif($session['user']['gold']>=$grubprice) {
		$buff = unserialize($playermount['mountbuff']);
		if (!isset($buff['schema']) || $buff['schema'] == "") $buff['schema'] = "mounts";
		if (isset($session['bufflist']['mount']) && $session['bufflist']['mount']['rounds'] == $buff['rounds']) {
			Translator::tlschema($schemas['nothungry']);
			OutputClass::output($texts['nothungry'],$name);
			Translator::tlschema();
		} else {
			if (isset($session['bufflist']['mount']) && $session['bufflist']['mount']['rounds'] > $buff['rounds']*.5) {
				$grubprice=round($grubprice/2,0);
				Translator::tlschema($schemas['halfhungry']);
				OutputClass::output($texts['halfhungry'], $name, $name, $grubprice);
				Translator::tlschema();
				$session['user']['gold']-=$grubprice;
			}else{
				$session['user']['gold']-=$grubprice;
				Translator::tlschema($schemas['hungry']);
				OutputClass::output($texts['hungry'], $name, $name, $grubprice);
				Translator::tlschema();
			}
			DebugLogClass::debuglog("spent $grubprice feeding their mount");
			Buffs::apply_buff('mount',$buff);
			$session['user']['fedmount'] = 1;
			Translator::tlschema($schemas['mountfull']);
			OutputClass::output($texts['mountfull'],
				($session['user']['sex']?$texts["lass"]:$texts["lad"]),
				($playermount['basename']?
				 $playermount['basename']:$playermount['mountname']));
			Translator::tlschema();
		}
	} else {
		Translator::tlschema($schemas['nofeedgold']);
		OutputClass::output($texts['nofeedgold'], $lcname);
		Translator::tlschema();
	}
}elseif($op=='sellmount'){
	Translator::tlschema($schemas['confirmsale']);
	OutputClass::output($texts['confirmsale'],
			($session['user']['sex']?$texts["lass"]:$texts["lad"]));
	Translator::tlschema();
	OutputClass::addnav("Confirm sale");
	OutputClass::addnav("Yes", "stables.php?op=confirmsell");
	OutputClass::addnav("No","stables.php");
	$confirm = 1;
}elseif($op=='confirmsell'){
	$session['user']['gold']+=$repaygold;
	$session['user']['gems']+=$repaygems;
	$debugmount=$playermount['mountname'];
	DebugLogClass::debuglog("gained $repaygold gold and $repaygems gems selling their mount, a $debugmount");
	Buffs::strip_buff('mount');
	$session['user']['hashorse']=0;
	Modules::modulehook("soldmount");

	$amtstr = "";
	if ($repaygold > 0) {
		$amtstr .= "%s gold";
	}
	if ($repaygems > 0) {
		if ($repaygold) $amtstr .= " and ";
		$amtstr .= "%s gems";
	}
	if ($repaygold > 0 && $repaygems > 0) {
		$amtstr = Translator::sprintf_translate($amtstr, $repaygold, $repaygems);
	} elseif ($repaygold > 0) {
		$amtstr = Translator::sprintf_translate($amtstr, $repaygold);
	} else {
		$amtstr = Translator::sprintf_translate($amtstr, $repaygems);
	}

	Translator::tlschema($schemas['mountsold']);
	OutputClass::output($texts['mountsold'],
			($playermount['newname']?
			   $playermount['newname']:$playermount['mountname']),
			$amtstr);
	Translator::tlschema();
}

if ($confirm == 0) {
	if ($session['user']['hashorse']>0){
		OutputClass::addnav(array("%s", SanitizeClass::color_sanitize($name)));
		Translator::tlschema($schemas['offer']);
		OutputClass::output($texts['offer'], $repaygold, $repaygems, $lcname);
		Translator::tlschema();
		OutputClass::addnav(array("Sell %s`0", $lcname),"stables.php?op=sellmount");
		if (Settings::getsetting("allowfeed", 0) && $session['user']['fedmount']==0) {
			OutputClass::addnav(array("Feed %s`0 (`^%s`0 gold)", $lcname, $grubprice),
					"stables.php?op=feed");
		}
	}

	$sql = "SELECT mountname,mountid,mountcategory,mountdkcost FROM " . db_prefix("mounts") .  " WHERE mountactive=1 AND mountlocation IN ('all','{$session['user']['location']}') ORDER BY mountcategory,mountcostgems,mountcostgold";
	$result = db_query($sql);
	$category="";
	$number=db_num_rows($result);
	for ($i=0;$i<$number;$i++){
		$row = db_fetch_assoc($result);
		if ($category!=$row['mountcategory']){
			OutputClass::addnav(array("%s", $row['mountcategory']));
			$category = $row['mountcategory'];
		}
		if ($row['mountdkcost'] <= $session['user']['dragonkills'])
			OutputClass::addnav(array("Examine %s`0", $row['mountname']),"stables.php?op=examine&id={$row['mountid']}");
	}
}

PageParts::page_footer();
?>