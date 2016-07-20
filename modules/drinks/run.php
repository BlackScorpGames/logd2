<?php
function drinks_run_private(){
	require_once("modules/drinks/misc_functions.php");
	require_once("lib/partner.php");

	global $session;
	$partner = Partner::get_partner();
	$act = Http::httpget('act');
	if ($act=="editor"){
		drinks_editor();
	}elseif ($act=="buy"){
		$texts = drinks_gettexts();
		$drinktext = Modules::modulehook("drinks-text",$texts);

		Translator::tlschema($drinktext['schemas']['title']);
		PageParts::page_header($drinktext['title']);
		OutputClass::rawoutput("<span style='color: #9900FF'>");
		OutputClass::output_notl("`c`b");
		OutputClass::output($drinktext['title']);
		OutputClass::output_notl("`b`c");
		Translator::tlschema();
		$drunk = get_module_pref("drunkeness");
		$end = ".";
		if ($drunk > get_module_setting("maxdrunk"))
			$end = ",";
		Translator::tlschema($drinktext['schemas']['demand']);
		$remark = Translator::translate_inline($drinktext['demand']);
		$remark = str_replace("{lover}",$partner."`0", $remark);
		$remark = str_replace("{barkeep}", $drinktext['barkeep']."`0", $remark);
		Translator::tlschema();
		OutputClass::output_notl("%s$end", $remark);
		$drunk = get_module_pref("drunkeness");
		if ($drunk > get_module_setting("maxdrunk")) {
			Translator::tlschema($drinktext['schemas']['toodrunk']);
			$remark = Translator::translate_inline($drinktext['toodrunk']);
 			Translator::tlschema();
			$remark = str_replace("{lover}",$partner."`0", $remark);
			$remark = str_replace("{barkeep}", $drinktext['barkeep']."`0", $remark);
			OutputClass::output($remark);
			Translator::tlschema();
		} else {
			$sql = "SELECT * FROM " . db_prefix("drinks") . " WHERE drinkid='".Http::httpget('id')."'";
			$result = db_query($sql);
			$row = db_fetch_assoc($result);
			$drinkcost = $session['user']['level'] * $row['costperlevel'];
			if ($session['user']['gold'] >= $drinkcost) {
				$drunk = get_module_pref("drunkeness");
				$drunk += $row['drunkeness'];
				set_module_pref("drunkeness", $drunk);
				$session['user']['gold'] -= $drinkcost;
				DebugLogClass::debuglog("spent $drinkcost on {$row['name']}");
				$remark = str_replace("{lover}",$partner."`0", $row['remarks']);
				$remark = str_replace("{barkeep}", $drinktext['barkeep']."`0", $remark);
				if (count($drinktext['drinksubs']) > 0) {
					$keys = array_keys($drinktext['drinksubs']);
					$vals = array_values($drinktext['drinksubs']);
					$remark = preg_replace($keys, $vals, $remark);
				}
				OutputClass::output($remark);
				OutputClass::output_notl("`n`n");
				if ($row['harddrink']) {
					$drinks = get_module_pref("harddrinks");
					set_module_pref("harddrinks", $drinks+1);
				}
				$givehp = 0;
				$giveturn = 0;
				if ($row['hpchance']>0 || $row['turnchance']>0) {
					$tot = $row['hpchance'] + $row['turnchance'];
					$c = Erand::e_rand(1, $tot);
					if ($c <= $row['hpchance'] && $row['hpchance']>0)
						$givehp = 1;
					else
						$giveturn = 1;
				}
				if ($row['alwayshp']) $givehp = 1;
				if ($row['alwaysturn'])  $giveturn = 1;
				if ($giveturn) {
					$turns = Erand::e_rand($row['turnmin'], $row['turnmax']);
					$oldturns = $session['user']['turns'];
					$session['user']['turns'] += $turns;
					// sanity check
					if ($session['user']['turns'] < 0)
						$session['user']['turns'] = 0;

					if ($oldturns < $session['user']['turns']) {
						OutputClass::output("`&You feel vigorous!`n");
					} else if ($oldturns > $session['user']['turns']) {
						OutputClass::output("`&You feel lethargic!`n");
					}
				}
				if ($givehp) {
					$oldhp = $session['user']['hitpoints'];

					// Check for percent increase first
					if ($row['hppercent'] != 0.0) {
						$hp = round($session['user']['maxhitpoints'] *
								($row['hppercent']/100), 0);
					} else {
						$hp = Erand::e_rand($row['hpmin'], $row['hpmax']);
					}
					$session['user']['hitpoints'] += $hp;
					// Sanity check
					if ($session['user']['hitpoints'] < 1)
						$session['user']['hitpoints'] = 1;

					if ($oldhp < $session['user']['hitpoints']) {
						OutputClass::output("`&You feel healthy!`n");
					} else if ($oldhp > $session['user']['hitpoints']) {
						OutputClass::output("`&You feel sick!`n");
					}
				}
				$buff = array();
				$buff['name'] = $row['buffname'];
				$buff['rounds'] = $row['buffrounds'];
				if ($row['buffwearoff'])
					$buff['wearoff'] = $row['buffwearoff'];
				if ($row['buffatkmod'])
					$buff['atkmod'] = $row['buffatkmod'];
				if ($row['buffdefmod'])
					$buff['defmod'] = $row['buffdefmod'];
				if ($row['buffdmgmod'])
					$buff['dmgmod'] = $row['buffdmgmod'];
				if ($row['buffdmgshield'])
					$buff['damageshield'] = $row['buffdmgshield'];
				if ($row['buffroundmsg'])
					$buff['roundmsg'] = $row['buffroundmsg'];
				if ($row['buffeffectmsg'])
					$buff['effectmsg'] = $row['buffeffectmsg'];
				if ($row['buffeffectnodmgmsg'])
					$buff['effectnodmgmsg'] = $row['buffeffectnodmgmsg'];
				if ($row['buffeffectfailmsg'])
					$buff['effectfailmsg'] = $row['buffeffectfailmsg'];
				$buff['schema'] = "module-drinks";
				apply_buff('buzz',$buff);
			} else {
				OutputClass::output("You don't have enough money.  How can you buy %s if you don't have any money!?!", $row['name']);
			}
		}
		OutputClass::rawoutput("</span>");
		if ($drinktext['return']>""){
			Translator::tlschema($drinktext['schemas']['return']);
			OutputClass::addnav($drinktext['return'],$drinktext['returnlink']);
			Translator::tlschema();
		}else{
			Translator::tlschema($drinktext['schemas']['return']);
			OutputClass::addnav("I?Return to the Inn","inn.php");
			OutputClass::addnav(array("Go back to talking to %s`0", Settings::getsetting("barkeep", "`tCedrik")),"inn.php?op=bartender");
			Translator::tlschema();
		}
		require_once("lib/villagenav.php");
		VillageNavClass::villagenav();
		PageParts::page_footer();
	}
}
?>
