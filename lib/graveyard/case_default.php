<?php
if (!$skipgraveyardtext) {
	OutputClass::output("`)`c`bThe Graveyard`b`c");
	OutputClass::output("Your spirit wanders into a lonely graveyard, overgrown with sickly weeds which seem to grab at your spirit as you float past them.");
	OutputClass::output("Around you are the remains of many broken tombstones, some lying on their faces, some shattered to pieces.");
	OutputClass::output("You can almost hear the wails of the souls trapped within each plot lamenting their fates.`n`n");
	OutputClass::output("In the center of the graveyard is an ancient looking mausoleum which has been worn by the effects of untold years.");
	OutputClass::output("A sinister looking gargoyle adorns the apex of its roof; its eyes seem to follow  you, and its mouth gapes with sharp stone teeth.");
	OutputClass::output("The plaque above the door reads `\$%s`), Overlord of Death`).",$deathoverlord);
	Modules::modulehook("graveyard-desc");
}
Modules::modulehook("graveyard");
	if ($session['user']['gravefights']) {
	OutputClass::addnav("Look for Something to Torment","graveyard.php?op=search");
}
OutputClass::addnav("Places");
OutputClass::addnav("W?List Warriors","list.php");
OutputClass::addnav("S?Return to the Shades","shades.php");
OutputClass::addnav("M?Enter the Mausoleum","graveyard.php?op=enter");
module_display_events("graveyard", "graveyard.php");
?>