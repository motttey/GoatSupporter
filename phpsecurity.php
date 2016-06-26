<?php

define('PASSWORD_KEY', 'k5|G-L$q');
define('STRETCH_COUNT', 1000);

function getSha256($s) {
  return hash('sha256', PASSWORD_KEY.$s);
}

function getPassword($s) {
  $hash = '';
  for ($i = 0; $i < STRETCH_COUNT; $i++) {
    $hash = getSha256($hash.$s);
  }
  return $hash;
}

function setToken() {
  $token = hash('sha256', uniqid(mt_rand(), true));
  $_SESSION['token'] = $token;
}

function checkToken() {
  if (empty($_SESSION['token']) || $_SESSION['token'] != $_POST['token']) {
    echo '不正なPOSTが行われました！';
    exit;
  }
}

?>
