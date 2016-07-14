<?php
$sql = "DELETE FROM " . db_prefix("bans") . " WHERE ipfilter = '".Http::httpget("ipfilter"). "' AND uniqueid = '".Http::httpget("uniqueid")."'";
db_query($sql);
redirect("user.php?op=removeban");
?>