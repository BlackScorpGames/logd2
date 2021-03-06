<?php
// addnews ready
// translator ready
// mail ready

function motd_admin($id, $poll=false) {
	global $session;
	if ($session['user']['superuser'] & SU_POST_MOTD) {
		$ed = Translator::translate_inline("Edit");
		$del = Translator::translate_inline("Del");
		$confirm = Translator::translate_inline("Are you sure you want to delete this item?");
		OutputClass::output_notl("[ ");
		if (!$poll) {
			OutputClass::rawoutput("<a href='motd.php?op=add".($poll?"poll":"")."&id=$id'>$ed</a> | ");
		}
		OutputClass::rawoutput("<a href='motd.php?op=del&id=$id' onClick=\"return confirm('$confirm');\">$del</a> ]");
	}
}
class Motd{
    public static function motd_poll_form() {
        global $session;
        $subject = Http::httppost('subject');
        $body = Http::httppost('body');
        if ($subject=="" || $body==""){
            OutputClass::output("`\$NOTE:`^ Polls cannot be edited after they are begun in order to ensure fairness and accuracy of results.`0`n`n");
            OutputClass::rawoutput("<form action='motd.php?op=addpoll' method='POST'>");
            OutputClass::addnav("","motd.php?op=add");
            OutputClass::output("Subject: ");
            OutputClass::rawoutput("<input type='text' size='50' name='subject' value=\"".HTMLEntities(stripslashes($subject), ENT_COMPAT, Settings::getsetting("charset", "ISO-8859-1"))."\"><br/>");
            OutputClass::output("Body:`n");
            OutputClass::rawoutput("<textarea class='input' name='body' cols='37' rows='5'>".HTMLEntities(stripslashes($body), ENT_COMPAT, Settings::getsetting("charset", "ISO-8859-1"))."</textarea><br/>");
            $option = Translator::translate_inline("Option");
            OutputClass::output("Choices:`n");
            $pollitem = "$option <input name='opt[]'><br/>";
            OutputClass::rawoutput($pollitem);
            OutputClass::rawoutput($pollitem);
            OutputClass::rawoutput($pollitem);
            OutputClass::rawoutput($pollitem);
            OutputClass::rawoutput($pollitem);
            OutputClass::rawoutput("<div id='hidepolls'>");
            OutputClass::rawoutput("</div>");
            OutputClass::rawoutput("<script language='JavaScript'>document.getElementById('hidepolls').innerHTML = '';</script>",true);
            $addi = Translator::translate_inline("Add Poll Item");
            $add = Translator::translate_inline("Add");
            OutputClass::rawoutput("<a href=\"#\" onClick=\"javascript:document.getElementById('hidepolls').innerHTML += '".addslashes($pollitem)."'; return false;\">$addi</a><br>");
            OutputClass::rawoutput("<input type='submit' class='button' value='$add'></form>");
        }else{
            $opt = Http::httppost("opt");
            $body = array("body"=>$body,"opt"=>$opt);
            $sql = "INSERT INTO " . db_prefix("motd") . " (motdtitle,motdbody,motddate,motdtype,motdauthor) VALUES (\"$subject\",\"".addslashes(serialize($body))."\",'".date("Y-m-d H:i:s")."',1,'{$session['user']['acctid']}')";
            db_query($sql);
            DataCache::invalidatedatacache("motd");
            DataCache::invalidatedatacache("lastmotd");
            DataCache::invalidatedatacache("motddate");
            header("Location: motd.php");
            exit();
        }
    }
    public static function motd_form($id)
    {
        global $session;
        $subject = Http::httppost('subject');
        $body = Http::httppost('body');
        $preview = Http::httppost('preview');
        if ($subject == "" || $body == "" || $preview > "") {
            $edit = Translator::translate_inline("Edit a MoTD");
            $add = Translator::translate_inline("Add a MoTD");
            $ret = Translator::translate_inline("Return");

            $row = array(
                "motditem" => 0,
                "motdauthorname" => "",
                "motdtitle" => "",
                "motdbody" => "",
            );
            if ($id > "") {
                $sql = "SELECT " . db_prefix("motd") . ".*,name AS motdauthorname FROM " . db_prefix("motd") . " LEFT JOIN " . db_prefix("accounts") . " ON " . db_prefix("accounts") . ".acctid = " . db_prefix("motd") . ".motdauthor WHERE motditem='$id'";
                $result = db_query($sql);
                if (db_num_rows($result) > 0) {
                    $row = db_fetch_assoc($result);
                    $msg = $edit;
                } else {
                    $msg = $add;
                }
            } else {
                $msg = $add;
            }
            OutputClass::output_notl("`b%s`b", $msg);
            OutputClass::rawoutput("[ <a href='motd.php'>$ret</a> ]<br>");

            OutputClass::rawoutput("<form action='motd.php?op=add&id={$row['motditem']}' method='POST'>");
            OutputClass::addnav("", "motd.php?op=add&id={$row['motditem']}");
            if ($row['motdauthorname'] > "") {
                OutputClass::output("Originally by `@%s`0 on %s`n", $row['motdauthorname'],
                    $row['motddate']);
            }
            if ($subject > "") {
                $row['motdtitle'] = stripslashes($subject);
            }
            if ($body > "") {
                $row['motdbody'] = stripslashes($body);
            }
            if ($preview > "") {
                if (Http::httppost('changeauthor') || $row['motdauthorname'] == "") {
                    $row['motdauthorname'] = $session['user']['name'];
                }
                if (Http::httppost('changedate') || !isset($row['motddate']) || $row['motddate'] == "") {
                    $row['motddate'] = date("Y-m-d H:i:s");
                }
                Motd::motditem($row['motdtitle'], $row['motdbody'],
                    $row['motdauthorname'], $row['motddate'], "");
            }
            OutputClass::output("Subject: ");
            OutputClass::rawoutput("<input type='text' size='50' name='subject' value=\"" . HTMLEntities(stripslashes($row['motdtitle']),
                    ENT_COMPAT, Settings::getsetting("charset", "ISO-8859-1")) . "\"><br/>");
            OutputClass::output("Body:`n");
            OutputClass::rawoutput("<textarea align='right' class='input' name='body' cols='37' rows='5'>" . HTMLEntities(stripslashes($row['motdbody']),
                    ENT_COMPAT, Settings::getsetting("charset", "ISO-8859-1")) . "</textarea><br/>");
            if ($row['motditem'] > 0) {
                OutputClass::output("Options:`n");
                OutputClass::rawoutput("<input type='checkbox' value='1' name='changeauthor'" . (Http::httppost('changeauthor') ? " checked" : "") . ">");
                OutputClass::output("Change Author`n");
                OutputClass::rawoutput("<input type='checkbox' value='1' name='changedate'" . (Http::httppost('changedate') ? " checked" : "") . ">");
                OutputClass::output("Change Date (force popup again)`n");
            }
            $prev = Translator::translate_inline("Preview");
            $sub = Translator::translate_inline("Submit");
            OutputClass::rawoutput("<input type='submit' class='button' name='preview' value='$prev'> <input type='submit' class='button' value='$sub'></form>");
        } else {
            if ($id > "") {
                $sql = " SET motdtitle='$subject', motdbody='$body'";
                if (Http::httppost('changeauthor')) {
                    $sql .= ", motdauthor={$session['user']['acctid']}";
                }
                if (Http::httppost('changedate')) {
                    $sql .= ", motddate='" . date("Y-m-d H:i:s") . "'";
                }
                $sql = "UPDATE " . db_prefix("motd") . $sql . " WHERE motditem='$id'";
                db_query($sql);
                DataCache::invalidatedatacache("motd");
                DataCache::invalidatedatacache("lastmotd");
                DataCache::invalidatedatacache("motddate");
            }
            if ($id == "" || db_affected_rows() == 0) {
                if ($id > "") {
                    $sql = "SELECT * FROM " . db_prefix("motd") . " WHERE motditem='$id'";
                    $result = db_query($sql);
                    if (db_num_rows($result) > 0) {
                        $doinsert = false;
                    } else {
                        $doinsert = true;
                    }
                } else {
                    $doinsert = true;
                }
                if ($doinsert) {
                    $sql = "INSERT INTO " . db_prefix("motd") . " (motdtitle,motdbody,motddate,motdauthor) VALUES (\"$subject\",\"$body\",'" . date("Y-m-d H:i:s") . "','{$session['user']['acctid']}')";
                    db_query($sql);
                    DataCache::invalidatedatacache("motd");
                    DataCache::invalidatedatacache("lastmotd");
                    DataCache::invalidatedatacache("motddate");
                }
            }
            header("Location: motd.php");
            exit();
        }
    }
public static function motditem($subject,$body,$author,$date,$id){
	if ($date)
		OutputClass::rawoutput("<a name='motd".date("YmdHis",strtotime($date))."'>");
	OutputClass::output_notl("`b`^%s`0`b", $subject);
	if ($id > "") {
		motd_admin($id);
	}
	if ($date || $author) OutputClass::output_notl("`n");
	if ($author > "") {
		OutputClass::output_notl("`3%s`0", $author);
	}
	if ($date>"")
		OutputClass::output_notl("`0 &#150; `#%s`0", $date, true);
	if ($date || $author) OutputClass::output_notl("`n");

	OutputClass::output_notl("`2%s`0", nltoappon($body), true);
	if ($date) OutputClass::rawoutput("</a>");
	OutputClass::rawoutput("<hr>");
}
    public static function pollitem($id,$subject,$body,$author,$date,$showpoll=true){
        global $session;
        $sql = "SELECT count(resultid) AS c, MAX(choice) AS choice FROM " . db_prefix("pollresults") . " WHERE motditem='$id' AND account='{$session['user']['acctid']}'";
        $result = db_query($sql);
        $row = db_fetch_assoc($result);
        $choice = $row['choice'];
        $body = unserialize($body);

        $poll = Translator::translate_inline("Poll:");
        if ($session['user']['loggedin'] && $showpoll) {
            OutputClass::rawoutput("<form action='motd.php?op=vote' method='POST'>");
            OutputClass::rawoutput("<input type='hidden' name='motditem' value='$id'>",true);
        }
        OutputClass::output_notl("`b`&%s `^%s`0`b", $poll, $subject);
        if ($showpoll) motd_admin($id, true);
        OutputClass::output_notl("`n`3%s`0 &#150; `#%s`0`n", $author, $date, true);
        OutputClass::output_notl("`2%s`0`n", stripslashes($body['body']));
        $sql = "SELECT count(resultid) AS c, choice FROM " . db_prefix("pollresults") . " WHERE motditem='$id' GROUP BY choice ORDER BY choice";
        $result = db_query_cached($sql,"poll-$id");
        $choices=array();
        $totalanswers=0;
        $maxitem = 0;
        while ($row = db_fetch_assoc($result)) {
            $choices[$row['choice']]=$row['c'];
            $totalanswers+=$row['c'];
            if ($row['c']>$maxitem) $maxitem = $row['c'];
        }
        while (list($key,$val)=each($body['opt'])){
            if (trim($val)!=""){
                if ($totalanswers<=0) $totalanswers=1;
                $percent = 0;
                if(isset($choices[$key])) {
                    $percent = round($choices[$key] / $totalanswers * 100,1);
                }
                if ($session['user']['loggedin'] && $showpoll) {
                    OutputClass::rawoutput("<input type='radio' name='choice' value='$key'".($choice==$key?" checked":"").">");
                }
                OutputClass::output_notl("%s (%s - %s%%)`n", stripslashes($val),
                    (isset($choices[$key])?(int)$choices[$key]:0), $percent);
                if ($maxitem==0 || !isset($choices[$key])){
                    $width=1;
                } else {
                    $width = round(($choices[$key]/$maxitem) * 400,0);
                }
                $width = max($width,1);
                OutputClass::rawoutput("<img src='images/rule.gif' width='$width' height='2' alt='$percent'><br>");
            }
        }
        if ($session['user']['loggedin'] && $showpoll) {
            $vote = Translator::translate_inline("Vote");
            OutputClass::rawoutput("<input type='submit' class='button' value='$vote'></form>");
        }
        OutputClass::rawoutput("<hr>",true);
    }
    public static function motd_del($id) {
        $sql = "DELETE FROM " . db_prefix("motd") . " WHERE motditem=\"$id\"";
        db_query($sql);
        DataCache::invalidatedatacache("motd");
        DataCache::invalidatedatacache("lastmotd");
        DataCache::invalidatedatacache("motddate");
        header("Location: motd.php");
        exit();
    }

}







?>