<?php
// addnews ready
// translator ready
// mail ready
class PvpWarningClass
{
    public static function pvpwarning($dokill = false)
    {
        global $session;
        $days = Settings::getsetting("pvpimmunity", 5);
        $exp = Settings::getsetting("pvpminexp", 1500);
        if ($session['user']['age'] <= $days &&
            $session['user']['dragonkills'] == 0 &&
            $session['user']['pk'] == 0 &&
            $session['user']['experience'] <= $exp
        ) {
            if ($dokill) {
                OutputClass::output("`\$Warning!`^ Since you were still under PvP immunity, but have chosen to attack another player, you have lost this immunity!!`n`n");
                $session['user']['pk'] = 1;
            } else {
                OutputClass::output("`\$Warning!`^ Players are immune from Player vs Player (PvP) combat for their first %s days in the game or until they have earned %s experience, or until they attack another player.  If you choose to attack another player, you will lose this immunity!`n`n",
                    $days, $exp);
            }
        }
        Modules::modulehook("pvpwarning", array("dokill" => $dokill));
    }
}
