<?php
// addnews ready
// translator ready
// mail ready





function is_new_day($now=0){
	global $session;

	if ($session['user']['lasthit'] == "0000-00-00 00:00:00") {
		return true;
	}
	$t1 = gametime();
	$t2 = GameDateTime::convertgametime(strtotime($session['user']['lasthit']." +0000"));
	$d1 = gmdate("Y-m-d",$t1);
	$d2 = gmdate("Y-m-d",$t2);

	if ($d1!=$d2){
		return true;
	}
	return false;
}

class GameDateTime
{

	public static function convertgametime($intime,$debug=false){

		//adjust the requested time by the game offset
		$intime -= Settings::getsetting("gameoffsetseconds",0);

		// we know that strtotime gives us an identical timestamp for
		// everywhere in the world at the same time, if it is provided with
		// the GMT offset:
		$epoch = strtotime(Settings::getsetting("game_epoch",gmdate("Y-m-d 00:00:00 O",strtotime("-30 days"))));
		$now = strtotime(gmdate("Y-m-d H:i:s O",$intime));
		$logd_timestamp = ($now - $epoch) * Settings::getsetting("daysperday",4);
		if ($debug){
			echo "Game Timestamp: ".$logd_timestamp.", which makes it ".gmdate("Y-m-d H:i:s",$logd_timestamp)."<br>";
		}
		return $logd_timestamp;
	}

	public static function reltime($date,$short=true){
		$now = strtotime("now");
		$x = abs($now - $date);
		$d = (int)($x/86400);
		$x = $x % 86400;
		$h = (int)($x/3600);
		$x = $x % 3600;
		$m = (int)($x/60);
		$x = $x % 60;
		$s = (int)($x);
		if ($short){
			$array=array("d"=>"d","h"=>"h","m"=>"m","s"=>"s");
			$array=Translator::translate_inline($array,"datetime");
			if ($d > 0)
				$o = $d.$array['d'].($h>0?$h.$array['h']:"");
			elseif ($h > 0)
				$o = $h.$array['h'].($m>0?$m.$array['m']:"");
			elseif ($m > 0)
				$o = $m.$array['m'].($s>0?$s.$array['s']:"");
			else
				$o = $s.$array['s'];

			/*		if ($d > 0)
                        $o = sprintf("%3s%2s",$d.$array['d'],($h>0?$h.$array['h']:""));
                    elseif ($h > 0)
                        $o = sprintf("%3s%2s",$h.$array['h'],($m>0?$m.$array['m']:""));
                    elseif ($m > 0)
                        $o = sprintf("%3s%2s",$m.$array['m'],($s>0?$s.$array['s']:""));
                    else
                        $o = sprintf("%5s", $s.$array['s']);
                    $o = str_replace(" ", "&nbsp;", $o);*/
		}else{
			$array=array("day"=>"day","days"=>"days","hour"=>"hour","hours"=>"hours","minute"=>"minute","minutes"=>"minutes","second"=>"second","seconds"=>"second");
			$array=Translator::translate_inline($array,"datetime"); //translate it... tl-ready now
			if ($d > 0)
				$o = "$d ".($d>1?$array['days']:$array['day']).($h>0?", $h ".($h>1?$array['hours']:$array['hour']):"");
			elseif ($h > 0)
				$o = "$h ".($h>1?$array['hours']:$array['hour']).($m>0?", $m ".($m>1?$array['minutes']:$array['minute']):"");
			elseif ($m > 0)
				$o = "$m ".($m>1?$array['minutes']:$array['minute']).($s>0?", $s ".($s>1?$array['seconds']:$array['second']):"");
			else
				$o = "$s ".($s>0?$array['seconds']:$array['second']);
		}
		return $o;
	}
    public static function gametimedetails(){
        $ret = array();
        $ret['now'] = date("Y-m-d 00:00:00");
        $ret['gametime'] = gametime();
        $ret['daysperday'] = Settings::getsetting("daysperday", 4);
        $ret['secsperday'] = 86400/$ret['daysperday'];
        $ret['today'] = strtotime(gmdate("Y-m-d 00:00:00 O", $ret['gametime']));
        $ret['tomorrow'] =
            strtotime(gmdate("Y-m-d H:i:s O",$ret['gametime'])." + 1 day");
        $ret['tomorrow'] = strtotime(gmdate("Y-m-d 00:00:00 O",$ret['tomorrow']));
        // Why isn't this
        // $ret['tomorrow'] =
        //	strtotime(gmdate("Y-m-d 00:00:00 O",$ret['gametime'])." + 1 day");
        $ret['secssofartoday'] = $ret['gametime'] - $ret['today'];
        $ret['secstotomorrow'] = $ret['tomorrow']-$ret['gametime'];
        $ret['realsecssofartoday'] = $ret['secssofartoday'] / $ret['daysperday'];
        $ret['realsecstotomorrow'] = $ret['secstotomorrow'] / $ret['daysperday'];
        $ret['dayduration'] = ($ret['tomorrow']-$ret['today'])/$ret['daysperday'];
        return $ret;
    }
	public static function relativedate($indate){
		$laston = round((strtotime("now")-strtotime($indate)) / 86400,0) . " days";
		Translator::tlschema("datetime");
		if (substr($laston,0,2)=="1 ")
			$laston=Translator::translate_inline("1 day");
		elseif (date("Y-m-d",strtotime($laston)) == date("Y-m-d"))
			$laston=Translator::translate_inline("Today");
		elseif (date("Y-m-d",strtotime($laston)) == date("Y-m-d",strtotime("-1 day")))
			$laston=Translator::translate_inline("Yesterday");
		elseif (strpos($indate,"0000-00-00")!==false)
			$laston = Translator::translate_inline("Never");
		else {
			$laston= Translator::sprintf_translate("%s days", round((strtotime("now")-strtotime($indate)) / 86400,0));
			OutputClass::rawoutput(Translator::tlbutton_clear());
		}
		Translator::tlschema();
		return $laston;
	}
	public static function getgametime()
	{
		return gmdate("g:i a", gametime());
	}

	public static function secondstonextgameday($details = false)
	{
		if ($details === false) {
			$details = GameDateTime::gametimedetails();
		}
		return strtotime("{$details['now']} + {$details['realsecstotomorrow']} seconds");
	}

    public static function checkday()
    {
        global $session, $revertsession, $REQUEST_URI;
        if ($session['user']['loggedin']) {
            OutputClass::output_notl("<!--CheckNewDay()-->", true);
            if (is_new_day()) {
                $session = $revertsession;
                $session['user']['restorepage'] = $REQUEST_URI;
                $session['allowednavs'] = array();
                OutputClass::addnav("", "newday.php");
                RedirectClass::redirect("newday.php");
            }
        }
}

}
function gametime(){
	$time = GameDateTime::convertgametime(strtotime("now"));
	return $time;
}






function getmicrotime(){
	list($usec, $sec) = explode(" ",microtime());
	return ((float)$usec + (float)$sec);
}


?>
