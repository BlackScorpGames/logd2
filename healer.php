<?php
// addnews ready
// translator ready
// mail ready
require_once("common.php");
require_once("lib/forest.php");
require_once("lib/http.php");
require_once("lib/villagenav.php");

Translator::tlschema("healer");

$config = unserialize($session['user']['donationconfig']);

$return = Http::httpget("return");
$returnline = $return>""?"&return=$return":"";

PageParts::page_header("Healer's Hut");
OutputClass::output("`#`b`cHealer's Hut`c`b`n");

$cost = log($session['user']['level']) * (($session['user']['maxhitpoints']-$session['user']['hitpoints']) + 10);
$result=Modules::modulehook("healmultiply",array("alterpct"=>1.0));
$cost*=$result['alterpct'];
$cost = round($cost,0);

$op = Http::httpget('op');
if ($op==""){
  	checkday();
	OutputClass::output("`3You duck into the small smoke-filled grass hut.");
	OutputClass::output("The pungent aroma makes you cough, attracting the attention of a grizzled old person that does a remarkable job of reminding you of a rock, which probably explains why you didn't notice them until now.");
	OutputClass::output("Couldn't be your failure as a warrior.");
	OutputClass::output("Nope, definitely not.`n`n");
	if ($session['user']['hitpoints'] < $session['user']['maxhitpoints']){
		OutputClass::output("\"`6See you, I do.  Before you did see me, I think, hmm?`3\" the old thing remarks.");
		OutputClass::output("\"`6Know you, I do; healing you seek.  Willing to heal am I, but only if willing to pay are you.`3\"`n`n");
		OutputClass::output("\"`5Uh, um.  How much?`3\" you ask, ready to be rid of the smelly old thing.`n`n");
		OutputClass::output("The old being thumps your ribs with a gnarly staff.  \"`6For you... `$`b%s`b`6 gold pieces for a complete heal!!`3\" it says as it bends over and pulls a clay vial from behind a pile of skulls sitting in the corner.", $cost);
		OutputClass::output("The view of the thing bending over to remove the vial almost does enough mental damage to require a larger potion.");
		OutputClass::output("\"`6I also have some, erm... 'bargain' potions available,`3\" it says as it gestures at a pile of dusty, cracked vials.");
		OutputClass::output("\"`6They'll heal a certain percent of your `idamage`i.`3\"");
	}elseif($session['user']['hitpoints'] == $session['user']['maxhitpoints']){
		OutputClass::output("`3The old creature grunts as it looks your way. \"`6Need a potion, you do not.  Wonder why you bother me, I do.`3\" says the hideous thing.");
		OutputClass::output("The aroma of its breath makes you wish you hadn't come in here in the first place.  You think you had best leave.");
	}else{
		OutputClass::output("`3The old creature glances at you, then in a `^whirlwind of movement`3 that catches you completely off guard, brings its gnarled staff squarely in contact with the back of your head.");
		OutputClass::output("You gasp as you collapse to the ground.`n`n");
		OutputClass::output("Slowly you open your eyes and realize the beast is emptying the last drops of a clay vial down your throat.`n`n");
		OutputClass::output("\"`6No charge for that potion.`3\" is all it has to say.");
		OutputClass::output("You feel a strong urge to leave as quickly as you can.");
		$session['user']['hitpoints'] = $session['user']['maxhitpoints'];
	}
}elseif ($op=="buy"){
	$pct = Http::httpget('pct');
	$newcost=round($pct*$cost/100,0);
	if ($session['user']['gold']>=$newcost){
		$session['user']['gold']-=$newcost;
		debuglog("spent gold on healing",false,false,"healing",$newcost);
		$diff = round(($session['user']['maxhitpoints']-$session['user']['hitpoints'])*$pct/100,0);
		$session['user']['hitpoints'] += $diff;
		OutputClass::output("`3With a grimace, you up-end the potion the creature hands you, and despite the foul flavor, you feel a warmth spreading through your veins as your muscles knit back together.");
		if($newcost){
			OutputClass::output("`3With a grimace, you up-end the potion the creature hands you, and despite the foul flavor, you feel a warmth spreading through your veins as your muscles knit back together.");
			OutputClass::output("Staggering some, you hand it your gold and are ready to be out of here.");
		}else{
			OutputClass::output("`3With a grimace, you up-end the potion the creature hands you, and despite the foul flavor, you feel a warmth spreading through your veins.");
			OutputClass::output("Staggering some you are ready to be out of here.");
		}
		if($diff == 1) {
			OutputClass::output("`n`n`#You have been healed for one point!", $diff);
		} else {
			OutputClass::output("`n`n`#You have been healed for %s points!", $diff);
		}
	}else{
		OutputClass::output("`3The old creature pierces you with a gaze hard and cruel.");
		OutputClass::output("Your lightning quick reflexes enable you to dodge the blow from its gnarled staff.");
		OutputClass::output("Perhaps you should get some more money before you attempt to engage in local commerce.`n`n");
		OutputClass::output("You recall that the creature had asked for `b`\$%s`3`b gold.", $newcost);
	}
}elseif ($op=="companion"){
	$compcost = Http::httpget('compcost');
	
	if($session['user']['gold'] < $compcost){
		OutputClass::output("`3The old creature pierces you with a gaze hard and cruel.`n");
		OutputClass::output("Your lightning quick reflexes enable you to dodge the blow from its gnarled staff.`n");
		OutputClass::output("Perhaps you should get some more money before you attempt to engage in local commerce.`n`n");
		OutputClass::output("You recall that the creature had asked for `b`\$%s`3`b gold.", $compcost);
	}else{
		$name = stripslashes(rawurldecode(Http::httpget('name')));
		$session['user']['gold'] -= $compcost;
		$companions[$name]['hitpoints'] = $companions[$name]['maxhitpoints'];
		OutputClass::output("`3With a grimace, %s`3 up-ends the potion from the creature.`n", $companions[$name]['name']);
		OutputClass::output("Muscles knit back together, cuts close and bruises fade. %s`3 is ready to battle once again!`n", $companions[$name]['name']);
		OutputClass::output("You hand the creature your gold and are ready to be out of here.");
	}
}
$playerheal = false;
if($session['user']['hitpoints'] < $session['user']['maxhitpoints']){
	$playerheal = true;
	OutputClass::addnav("Potions");
	OutputClass::addnav("`^Complete Healing`0","healer.php?op=buy&pct=100$returnline");
	for ($i=90;$i>0;$i-=10){
		OutputClass::addnav(array("%s%% - %s gold", $i, round($cost*$i/100,0)),"healer.php?op=buy&pct=$i$returnline");
	}
	Modules::modulehook('potion');
}
OutputClass::addnav("`bHeal Companions`b");
$compheal = false;
foreach($companions as $name => $companion){
	if(isset($companion['cannotbehealed']) && $companion['cannotbehealed'] == true){
	}else{
		$points = $companion['maxhitpoints'] - $companion['hitpoints'];
		if($points > 0){
			$compcost = round(log($session['user']['level']+1) * ($points + 10)*1.33);
			OutputClass::addnav(array("%s`0 (`^%s Gold`0)", $companion['name'], $compcost), "healer.php?op=companion&name=".rawurlencode($name)."&compcost=$compcost$returnline");
			$compheal = true;
		}
	}
}
Translator::tlschema("nav");
OutputClass::addnav("`bReturn`b");
if ($return==""){
	if($playerheal || $compheal){
		OutputClass::addnav("F?Back to the Forest", "forest.php");
		villagenav();
	}else{
		forest(true);
	}
}elseif ($return=="village.php"){
	villagenav();
}else{
	OutputClass::addnav("R?Return whence you came",$return);
}
Translator::tlschema();
output_notl("`0");
page_footer();
?>