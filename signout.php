<?php
require_once('phpconf.php');

session_cache_expire(0);
session_cache_limiter('private_no_expire');
session_start();

session_destroy();

header('Location: '.SITE_URL.'signin.php');
exit;

?>