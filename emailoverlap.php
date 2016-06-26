<?php

require_once('phpconf.php');
require_once('phpfunc.php');

session_cache_expire(0);
session_cache_limiter('private_no_expire');
session_start();

$_POST = arrayString($_POST);

if (strcmp(@$_SERVER['HTTP_REFERER'], SITE_URL.'signup.php') != 0 || empty($_SESSION['token']) || $_SESSION['token'] != $_POST['token'] || empty($_POST['email'])) {
  echo json_encode(array('invalid' => 1));
  exit;
}

echo json_encode(array('invalid' => emailExists($_POST['email'])));
$pdo = null;

?>