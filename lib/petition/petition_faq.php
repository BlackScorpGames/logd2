<?php
Translator::tlschema("faq");
popup_header("Frequently Asked Questions (FAQ)");
OutputClass::output("`^Welcome to Legend of the Green Dragon.`n`n");
OutputClass::output("`@You wake up one day, and you're in a village for some reason.");
OutputClass::output("You wander around, bemused, until you stumble upon the main village square.");
OutputClass::output("Once there you start asking lots of stupid questions.");
OutputClass::output("People (who are mostly naked for some reason) throw things at you.");
OutputClass::output("You escape by ducking into a nearby building and find a rack of pamphlets by the door.");
OutputClass::output("The title of the pamphlet reads: `&\"Everything You Wanted to Know About the LotGD, but Were Afraid to Ask.\"");
OutputClass::output("`@Looking furtively around to make sure nobody's watching, you open one and read:`n`n");
OutputClass::output("\"`#So, you're a Newbie.  Welcome to the club.");
OutputClass::output("Here you will find answers to the questions that plague you.");
OutputClass::output("Well, actually you will find answers to the questions that plagued US.");
OutputClass::output("So, here, read and learn, and leave us alone!`@\"`n`n");
OutputClass::output("`^`bContents:`b`0`n");

modulehook("faq-pretoc");
OutputClass::output("`^`bNew Player & FAQ`b`0`n");
$t = Translator::translate_inline("`@New Player Primer`0");
output_notl("&#149;<a href='petition.php?op=primer'>%s</a><br/>", $t, true);
$t = Translator::translate_inline("`@Frequently Asked Questions on Game Play (General)`0");
output_notl("&#149;<a href='petition.php?op=faq1'>%s</a><br/>", $t, true);
$t = Translator::translate_inline("`@Frequently Asked Questions on Game Play (with spoilers)`0");
output_notl("&#149;<a href='petition.php?op=faq2'>%s</a><br/>", $t, true);
$t = Translator::translate_inline("`@Frequently Asked Questions on Technical Issues`0");
output_notl("&#149;<a href='petition.php?op=faq3'>%s</a><br/>", $t, true);
modulehook("faq-toc");
modulehook("faq-posttoc");
OutputClass::output("`nThank you,`nthe Management.`n");
?>