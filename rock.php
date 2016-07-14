<?php
// translator ready
// addnews ready
// mail ready
require_once("common.php");
require_once("lib/villagenav.php");
require_once("lib/commentary.php");

Translator::tlschema("rock");

// This idea is Imusade's from lotgd.net
if ($session['user']['dragonkills']>0 ||
		$session['user']['superuser'] & SU_EDIT_COMMENTS){
	addcommentary();
}

checkday();
if ($session['user']['dragonkills']>0 ||
		$session['user']['superuser'] & SU_EDIT_COMMENTS){
	PageParts::page_header("The Veteran's Club");

	OutputClass::output("`b`c`2The Veteran's Club`0`c`b");

	OutputClass::output("`n`n`4Something in you compels you to examine the curious rock.  Some dark magic, locked up in age old horrors.`n`n");
	OutputClass::output("When you arrive at the rock, an old scar on your arm begins to throb in succession with a mysterious light that now seems to come from the rock.  ");
	OutputClass::output("As you stare at it, the rock shimmers, shaking off an illusion.  You realize that this is more than a rock.  ");
	OutputClass::output("It is, in fact, a doorway, and over the threshold you see others bearing an identical scar to yours.  ");
	OutputClass::output("It somehow reminds you of the head of one of the great serpents from legend.`n`n");
	OutputClass::output("You have discovered The Veteran's Club.`n`n");

	Modules::modulehook("rock");

	commentdisplay("", "veterans","Boast here",30,"boasts");
}else{
	PageParts::page_header("Curious looking rock");
	OutputClass::output("You approach the curious looking rock.  ");
	OutputClass::output("After staring and looking at it for a little while, it continues to look just like a curious looking rock.`n`n");
	OutputClass::output("Bored, you decide to leave the rock alone.");
}
villagenav();

page_footer();
?>