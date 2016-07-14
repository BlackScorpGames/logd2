<?php
// translator ready
// addnews ready
// mail ready
require_once("common.php");
require_once("lib/commentary.php");


Translator::tlschema("shades");

PageParts::page_header("Land of the Shades");
Commentary::addcommentary();
GameDateTime::checkday();

if ($session['user']['alive']) RedirectClass::redirect("village.php");
OutputClass::output("`\$You walk among the dead now, you are a shade. ");
OutputClass::output("Everywhere around you are the souls of those who have fallen in battle, in old age, and in grievous accidents. ");
OutputClass::output("Each bears telltale signs of the means by which they met their end.`n`n");
OutputClass::output("Their souls whisper their torments, haunting your mind with their despair:`n");

OutputClass::output("`nA sepulchral voice intones, \"`QIt is now %s in the world above.`\$\"`n`n",GameDateTime::getgametime());
Modules::modulehook("shades", array());
Commentary::commentdisplay("`n`QNearby, some lost souls lament:`n", "shade","Despair",25,"despairs");

OutputClass::addnav("Log out","login.php?op=logout");
OutputClass::addnav("Places");
OutputClass::addnav("The Graveyard","graveyard.php");

OutputClass::addnav("Return to the news","news.php");

Translator::tlschema("nav");

// the mute module blocks players from speaking until they
// read the FAQs, and if they first try to speak when dead
// there is no way for them to unmute themselves without this link.
OutputClass::addnav("Other");
OutputClass::addnav("??F.A.Q. (Frequently Asked Questions)", "petition.php?op=faq",false,true);

if ($session['user']['superuser'] & SU_EDIT_COMMENTS){
	OutputClass::addnav("Superuser");
	OutputClass::addnav(",?Comment Moderation","moderate.php");
}
if ($session['user']['superuser']&~SU_DOESNT_GIVE_GROTTO){
	OutputClass::addnav("Superuser");
  OutputClass::addnav("X?Superuser Grotto","superuser.php");
}
if ($session['user']['superuser'] & SU_INFINITE_DAYS){
	OutputClass::addnav("Superuser");
  OutputClass::addnav("/?New Day","newday.php");
}

Translator::tlschema();

PageParts::page_footer();
?>