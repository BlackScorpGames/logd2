<?php
// translator ready
// addnews ready
// mail ready
require_once("common.php");
require_once("lib/commentary.php");
require_once("lib/http.php");
require_once("lib/events.php");
require_once("lib/experience.php");

Translator::tlschema('village');
//mass_module_prepare(array("village","validlocation","villagetext","village-desc"));
// See if the user is in a valid location and if not, put them back to
// a place which is valid
$valid_loc = array();
$vname = Settings::getsetting("villagename", LOCATION_FIELDS);
$iname = Settings::getsetting("innname", LOCATION_INN);
$valid_loc[$vname]="village";
$valid_loc = Modules::modulehook("validlocation", $valid_loc);
if (!isset($valid_loc[$session['user']['location']])) {
	$session['user']['location']=$vname;
}

$newestname = "";
$newestplayer = Settings::getsetting("newestplayer", "");
if ($newestplayer == $session['user']['acctid']) {
	$newtext = "`nYou're the newest member of the village.  As such, you wander around, gaping at the sights, and generally looking lost.";
	$newestname = $session['user']['name'];
} else {
	$newtext = "`n`2Wandering near the inn is `&%s`2, looking completely lost.";
	if ((int)$newestplayer != 0) {
		$sql = "SELECT name FROM " . db_prefix("accounts") . " WHERE acctid='$newestplayer'";
		$result = db_query_cached($sql, "newest");
		if (db_num_rows($result) == 1) {
			$row = db_fetch_assoc($result);
			$newestname = $row['name'];
		} else {
			$newestplayer = "";
		}
	} else {
		if ($newestplayer > "") {
			$newestname = $newestplayer;
		} else {
			$newestname = "";
		}
	}
}

$basetext = array(
	"`@`c`b%s Square`b`cThe village of %s hustles and bustles.  No one really notices that you're standing there.  ".
	"You see various shops and businesses along main street.  There is a curious looking rock to one side.  ".
	"On every side the village is surrounded by deep dark forest.`n`n",$vname,$vname
	);
$origtexts = array(
	"text"=>$basetext,
	"clock"=>"The clock on the inn reads `^%s`@.`n",
	"title"=>array("%s Square", $vname),
	"talk"=>"`n`%`@Nearby some villagers talk:`n",
	"sayline"=>"says",
	"newest"=>$newtext,
	"newestplayer"=>$newestname,
	"newestid"=>$newestplayer,
	"gatenav"=>"City Gates",
	"fightnav"=>"Blades Boulevard",
	"marketnav"=>"Market Street",
	"tavernnav"=>"Tavern Street",
	"infonav"=>"Info",
	"othernav"=>"Other",
	"section"=>"village",
	"innname"=>$iname,
	"stablename"=>"Merick's Stables",
	"mercenarycamp"=>"Mercenary Camp",
	"armorshop"=>"Pegasus Armor",
	"weaponshop"=>"MightyE's Weaponry"
	);
$schemas = array(
	"text"=>"village",
	"clock"=>"village",
	"title"=>"village",
	"talk"=>"village",
	"sayline"=>"village",
	"newest"=>"village",
	"newestplayer"=>"village",
	"newestid"=>"village",
	"gatenav"=>"village",
	"fightnav"=>"village",
	"marketnav"=>"village",
	"tavernnav"=>"village",
	"infonav"=>"village",
	"othernav"=>"village",
	"section"=>"village",
	"innname"=>"village",
	"stablename"=>"village",
	"mercenarycamp"=>"village",
	"armorshop"=>"village",
	"weaponshop"=>"village"
	);
// Now store the schemas
$origtexts['schemas'] = $schemas;

// don't hook on to this text for your standard modules please, use "village"
// instead.
// This hook is specifically to allow modules that do other villages to create
// ambience.
$texts = Modules::modulehook("villagetext",$origtexts);
//and now a special hook for the village
$texts = Modules::modulehook("villagetext-{$session['user']['location']}",$texts);
$schemas = $texts['schemas'];

Translator::tlschema($schemas['title']);
PageParts::page_header($texts['title']);
Translator::tlschema();

Commentary::addcommentary();
$skipvillagedesc = Events::handle_event("village");
GameDateTime::checkday();

if ($session['user']['slaydragon'] == 1) {
	$session['user']['slaydragon'] = 0;
}


if ($session['user']['alive']){ }else{
	RedirectClass::redirect("shades.php");
}

if (Settings::getsetting("automaster",1) && $session['user']['seenmaster']!=1){
	//masters hunt down truant students
	$level = $session['user']['level']+1;
	$dks = $session['user']['dragonkills'];
	$expreqd = Experience::exp_for_next_level($level, $dks);
	if ($session['user']['experience']>$expreqd &&
			$session['user']['level']<15){
		RedirectClass::redirect("train.php?op=autochallenge");
	}
}

$op = Http::httpget('op');
$com = Http::httpget('comscroll');
$refresh = Http::httpget("refresh");
$commenting = Http::httpget("commenting");
$comment = Http::httppost('insertcommentary');
// Don't give people a chance at a special event if they are just browsing
// the commentary (or talking) or dealing with any of the hooks in the village.
if (!$op && $com=="" && !$comment && !$refresh && !$commenting) {
	// The '1' should really be sysadmin customizable.
	if (module_events("village", Settings::getsetting("villagechance", 0)) != 0) {
		if (checknavs()) {
			PageParts::page_footer();
		} else {
			// Reset the special for good.
			$session['user']['specialinc'] = "";
			$session['user']['specialmisc'] = "";
			$skipvillagedesc=true;
			$op = "";
			httpset("op", "");
		}
	}
}

Translator::tlschema($schemas['gatenav']);
OutputClass::addnav($texts['gatenav']);
Translator::tlschema();

OutputClass::addnav("F?Forest","forest.php");
if (Settings::getsetting("pvp",1)){
	OutputClass::addnav("S?Slay Other Players","pvp.php");
}
OutputClass::addnav("Q?`%Quit`0 to the fields","login.php?op=logout",true);
if (Settings::getsetting("enablecompanions",true)) {
	Translator::tlschema($schemas['mercenarycamp']);
	OutputClass::addnav($texts['mercenarycamp'], "mercenarycamp.php");
	Translator::tlschema();
}

Translator::tlschema($schemas['fightnav']);
OutputClass::addnav($texts['fightnav']);
Translator::tlschema();
OutputClass::addnav("u?Bluspring's Warrior Training","train.php");
if (@file_exists("lodge.php")) {
	OutputClass::addnav("J?JCP's Hunter Lodge","lodge.php");
}

Translator::tlschema($schemas['marketnav']);
OutputClass::addnav($texts['marketnav']);
Translator::tlschema();
Translator::tlschema($schemas['weaponshop']);
OutputClass::addnav("W?".$texts['weaponshop'],"weapons.php");
Translator::tlschema();
Translator::tlschema($schemas['armorshop']);
OutputClass::addnav("A?".$texts['armorshop'],"armor.php");
Translator::tlschema();
OutputClass::addnav("B?Ye Olde Bank","bank.php");
OutputClass::addnav("Z?Ze Gypsy Tent","gypsy.php");
if (Settings::getsetting("betaperplayer", 1) == 1 && @file_exists("pavilion.php")) {
	OutputClass::addnav("E?Eye-catching Pavilion","pavilion.php");
}

Translator::tlschema($schemas['tavernnav']);
OutputClass::addnav($texts['tavernnav']);
Translator::tlschema();
Translator::tlschema($schemas['innname']);
OutputClass::addnav("I?".$texts['innname']."`0","inn.php",true);
Translator::tlschema();
Translator::tlschema($schemas['stablename']);
OutputClass::addnav("M?".$texts['stablename']."`0","stables.php");
Translator::tlschema();

OutputClass::addnav("G?The Gardens", "gardens.php");
OutputClass::addnav("R?Curious Looking Rock", "rock.php");
if (Settings::getsetting("allowclans",1)) OutputClass::addnav("C?Clan Halls","clan.php");

Translator::tlschema($schemas['infonav']);
OutputClass::addnav($texts['infonav']);
Translator::tlschema();
OutputClass::addnav("??F.A.Q. (newbies start here)", "petition.php?op=faq",false,true);
OutputClass::addnav("N?Daily News","news.php");
OutputClass::addnav("L?List Warriors","list.php");
OutputClass::addnav("o?Hall o' Fame","hof.php");

Translator::tlschema($schemas['othernav']);
OutputClass::addnav($texts['othernav']);
Translator::tlschema();
OutputClass::addnav("P?Preferences","prefs.php");
if (!file_exists("lodge.php")) {
	OutputClass::addnav("Refer a Friend", "referral.php");
}

Translator::tlschema('nav');
OutputClass::addnav("Superuser");
if ($session['user']['superuser'] & SU_EDIT_COMMENTS){
	OutputClass::addnav(",?Comment Moderation","moderate.php");
}
if ($session['user']['superuser']&~SU_DOESNT_GIVE_GROTTO){
  OutputClass::addnav("X?`bSuperuser Grotto`b","superuser.php");
}
if ($session['user']['superuser'] & SU_INFINITE_DAYS){
  OutputClass::addnav("/?New Day","newday.php");
}
Translator::tlschema();
//let users try to cheat, we protect against this and will know if they try.
OutputClass::addnav("","superuser.php");
OutputClass::addnav("","user.php");
OutputClass::addnav("","taunt.php");
OutputClass::addnav("","creatures.php");
OutputClass::addnav("","configuration.php");
OutputClass::addnav("","badword.php");
OutputClass::addnav("","armoreditor.php");
OutputClass::addnav("","bios.php");
OutputClass::addnav("","badword.php");
OutputClass::addnav("","donators.php");
OutputClass::addnav("","referers.php");
OutputClass::addnav("","retitle.php");
OutputClass::addnav("","stats.php");
OutputClass::addnav("","viewpetition.php");
OutputClass::addnav("","weaponeditor.php");

if (!$skipvillagedesc) {
	Modules::modulehook("collapse{", array("name"=>"villagedesc-".$session['user']['location']));
	Translator::tlschema($schemas['text']);
	OutputClass::output($texts['text']);
	Translator::tlschema();
	Modules::modulehook("}collapse");
	Modules::modulehook("collapse{", array("name"=>"villageclock-".$session['user']['location']));
	Translator::tlschema($schemas['clock']);
	OutputClass::output($texts['clock'],GameDateTime::getgametime());
	Translator::tlschema();
	Modules::modulehook("}collapse");
	Modules::modulehook("village-desc",$texts);
	//support for a special village-only hook
	Modules::modulehook("village-desc-{$session['user']['location']}",$texts);
	if ($texts['newestplayer'] > "" && $texts['newest']) {
		Modules::modulehook("collapse{", array("name"=>"villagenewest-".$session['user']['location']));
		Translator::tlschema($schemas['newest']);
		OutputClass::output($texts['newest'], $texts['newestplayer']);
		Translator::tlschema();
		$id = $texts['newestid'];
		if ($session['user']['superuser'] & SU_EDIT_USERS && $id) {
			$edit = Translator::translate_inline("Edit");
			OutputClass::rawoutput(" [<a href='user.php?op=edit&userid=$id'>$edit</a>]");
			OutputClass::addnav("","user.php?op=edit&userid=$id");
		}
		OutputClass::output_notl("`n");
		Modules::modulehook("}collapse");
	}
}
Modules::modulehook("village",$texts);
//special hook for all villages... saves queries...
Modules::modulehook("village-{$session['user']['location']}",$texts);

if ($skipvillagedesc) OutputClass::output("`n");

$args = Modules::modulehook("blockcommentarea", array("section"=>$texts['section']));
if (!isset($args['block']) || $args['block'] != 'yes') {
		Translator::tlschema($schemas['talk']);
		OutputClass::output($texts['talk']);
		Translator::tlschema();
		commentdisplay("",$texts['section'],"Speak",25,$texts['sayline'], $schemas['sayline']);
}

module_display_events("village", "village.php");
PageParts::page_footer();
?>