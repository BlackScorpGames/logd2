<?php
// addnews ready
// mail ready
// translator ready
function game_dice_getmoduleinfo(){
	$info = array(
		"name"=>"Dice Game for DarkHorse",
		"author"=>"Eric Stevens",
		"version"=>"1.1",
		"category"=>"Darkhorse Game",
		"download"=>"core_module",
	);
	return $info;
}

function game_dice_install(){
	global $session;
	debug("Adding Hooks");
	module_addhook("darkhorsegame");
	return true;
}

function game_dice_uninstall(){
	OutputClass::output("Uninstalling this module.`n");
	return true;
}

function game_dice_dohook($hookname, $args){
	if ($hookname=="darkhorsegame"){
		$ret = urlencode($args['return']);
		OutputClass::addnav("D?Play Dice Game",
				"runmodule.php?module=game_dice&ret=$ret");
	}
	return $args;
}

function game_dice_run(){
	global $session;
	$ret = urlencode(Http::httpget("ret"));
	PageParts::page_header("A Game of Dice");

	if ($session['user']['gold']>0){
		$bet = abs((int)Http::httpget('bet') + (int)Http::httppost('bet'));
		if ($bet<=0){
			OutputClass::addnav("Never mind", appendlink(urldecode($ret), "op=oldman"));
			OutputClass::output("`3\"`!You get to roll a die, and choose to keep or pass on the roll.  If you pass, you get up to two more chances to roll, for a total of three rolls.  Once you keep your roll (or on the third roll), I will do the same.  In the end, if my die is higher than yours, I win, if yours is higher, you win, and if they are a tie, neither of us wins, and we each keep our bet.`3\"`n`n");
			OutputClass::output("`3\"`!How much would you bet young %s?`3\"", Translator::translate_inline($session['user']['sex']?"lady":"man"));
			OutputClass::rawoutput("<form action='runmodule.php?module=game_dice&ret=$ret' method='POST'>");
			OutputClass::rawoutput("<input name='bet' id='bet'>");
			$b = Translator::translate_inline("Bet");
			OutputClass::rawoutput("<input type='submit' class='button' value='$b'>");
			OutputClass::rawoutput("</form>");
			OutputClass::rawoutput("<script language='JavaScript'>document.getElementById('bet').focus();</script>");
			OutputClass::addnav("","runmodule.php?module=game_dice&ret=$ret");
		}else if($bet>$session['user']['gold']){
			OutputClass::output("`3The old man reaches out with his stick and pokes your coin purse.");
			OutputClass::output("\"`!I don't believe you have `^%s`! gold!`3\" he declares.`n`n", $bet);
			OutputClass::output("Desperate to really show him good, you open up your purse and spill out its contents: `^%s`3 gold.`n`n", $session['user']['gold']);
			OutputClass::output("Embarrassed, you think you'll head back to the tavern.");
			OutputClass::addnav("Return to the Main Room",appendlink(urldecode($ret), "op=tavern"));
		} else {
			$what = Http::httpget('what');
			if ($what!="keep"){
				$session['user']['specialmisc']=Erand::e_rand(1,6);
				$try=(int)Http::httpget('try');
				$try++;
				switch ($try) {
				case 1: $die = "first";  break;
				case 2: $die = "second";  break;
				default: $die = "third";  break;
				}
				$die = Translator::translate_inline($die);
				OutputClass::output("You roll your %s die, and it comes up as `b%s`b`n`n", $die, $session['user']['specialmisc']);
				OutputClass::output("`3You have bet `^%s`3.", $bet);
				OutputClass::output("What do you do?");
				OutputClass::addnav("Keep","runmodule.php?module=game_dice&what=keep&bet=$bet&ret=$ret");
				if ($try<3)
					OutputClass::addnav("Pass","runmodule.php?module=game_dice&what=pass&try=$try&bet=$bet&ret=$ret");
			}else{
				OutputClass::output("Your final roll was `b%s`b, the old man will now try to beat it:`n`n", $session['user']['specialmisc']);
				$r = Erand::e_rand(1,6);
				OutputClass::output("The old man rolls a %s...`n", $r);
				if ($r>$session['user']['specialmisc'] || $r==6){
					OutputClass::output("\"`7I think I'll stick with that roll!`0\" he says.`n");
				}else{
					$r = Erand::e_rand(1,6);
					OutputClass::output("The old man rolls again and gets a %s...`n", $r);
					if ($r>=$session['user']['specialmisc']){
						OutputClass::output("\"`7I think I'll stick with that roll!`0\" he says.`n");
					}else{
						$r = Erand::e_rand(1,6);
						OutputClass::output("The old man rolls his final roll and gets a %s...`n", $r);
					}
				}
				if ($r>$session['user']['specialmisc']){
					OutputClass::output("`n\"`7Yeehaw, I knew the likes of you would never stand up to the likes of me!`0\" exclaims the old man as you hand him your `^%s`0 gold.", $bet);
					$session['user']['gold']-=$bet;
					debuglog("lost $bet gold at dice");
				}elseif ($r==$session['user']['specialmisc']){
					OutputClass::output("`n\"`7Yah... well, looks as though we tied.`0\" he says.");
				}else{
					OutputClass::output("`n\"`7Aaarrgh!!!  How could the likes of you beat me?!?!?`0\" shouts the old man as he gives you the gold he owes.");
					$session['user']['gold']+=$bet;
					debuglog("won $bet gold at dice");
				}
				OutputClass::addnav("Play again?","runmodule.php?module=game_dice&ret=$ret");
				OutputClass::addnav("Other Games",appendlink(urldecode($ret), "op=oldman"));
				OutputClass::addnav("Return to the Main Room", appendlink(urldecode($ret), "op=tavern"));
			}
		}
	}else{
		OutputClass::output("`3The old man reaches out with his stick and pokes your coin purse.  \"`!Empty?!?!  How can you bet with no money??`3\" he shouts.");
		OutputClass::output("With that, he turns back to his dice, apparently having already forgotten his anger.");
		OutputClass::addnav("Return to the Main Room", appendlink(urldecode($ret), "op=tavern"));
	}
	PageParts::page_footer();
}
?>
