<?php
$sql = "DELETE FROM " . db_prefix("bans") . " WHERE ipfilter = '".Http::httpget("ipfilter"). "' AND uniqueid = '".Http::httpget("uniqueid")."'";
db_query($sql);
RedirectClass::redirect("user.php?op=removeban");
?>