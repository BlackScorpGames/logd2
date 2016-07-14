<?php
function get_partner($player=false)
{
	global $session;
	if ($player === false) {
		$partner = Settings::getsetting("barmaid", "`%Violet");
		if ($session['user']['sex'] != SEX_MALE) {
			$partner = Settings::getsetting("bard", "`^Seth");
		}
	} else {
		if ($session['user']['marriedto'] == INT_MAX) {
			$partner = Settings::getsetting("barmaid", "`%Violet");
			if ($session['user']['sex'] != SEX_MALE) {
				$partner = Settings::getsetting("bard", "`^Seth");
			}
		} else {
			$sql = "SELECT name FROM ".db_prefix("accounts")." WHERE acctid = {$session['user']['marriedto']}";
			$result = db_query($sql);
			if ($row = db_fetch_assoc($result)) {
				$partner = $row['name'];
			} else {
				$session['user']['marriedto'] = 0;
				$partner = Settings::getsetting("barmaid", "`%Violet");
				if ($session['user']['sex'] != SEX_MALE) {
					$partner = Settings::getsetting("bard", "`^Seth");
				}
			}
		}
	}
//	No need to translate names...
//	Translator::tlschema("partner");
//	$partner = translate_inline($partner);
//	Translator::tlschema();
	return $partner;
}

?>
