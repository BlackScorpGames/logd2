<?php
$output="";
$sql = "SELECT OutputClass::output FROM " . db_prefix("accounts_output") . " WHERE acctid='$userid'";
$result = db_query($sql);
$row = db_fetch_assoc($result);
echo str_replace(".focus();",".blur();",str_replace("<iframe src=","<iframe Xsrc=",$row['OutputClass::output']));
exit();
?>