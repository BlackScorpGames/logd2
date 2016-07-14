<?php
OutputClass::output("`)`b`cThe Mausoleum`c`b");
$max = $session['user']['level'] * 5 + 50;
$favortoheal = round(10 * ($max-$session['user']['soulpoints'])/$max);
if ($session['user']['soulpoints']<$max){
	if ($session['user']['deathpower']>=$favortoheal){
		OutputClass::output("`\$%s`) calls you weak for needing restoration, but as you have enough favor with him, he grants your request at the cost of `4%s`) favor.",$deathoverlord, $favortoheal);
		$session['user']['deathpower']-=$favortoheal;
		$session['user']['soulpoints']=$max;
	}else{
		OutputClass::output("`\$%s`) curses you and throws you from the Mausoleum, you must gain more favor with him before he will grant restoration.",$deathoverlord);
	}
}else{
	OutputClass::output("`\$%s`) sighs and mumbles something about, \"`7just 'cause they're dead, does that mean they don't have to think?`)\"`n`n",$deathoverlord);
	OutputClass::output("Perhaps you'd like to actually `ineed`i restoration before you ask for it.");
}
OutputClass::addnav(array("Question `\$%s`0 about the worth of your soul",$deathoverlord),"graveyard.php?op=question");
OutputClass::addnav("Places");
OutputClass::addnav("S?Land of the Shades","shades.php");
OutputClass::addnav("G?Return to the Graveyard","graveyard.php");
?>