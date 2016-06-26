<?php

require_once('phpconf.php');
require_once('phpfunc.php');
require_once('phpsecurity.php');

session_cache_expire(0);
session_cache_limiter('private_no_expire');
session_start();

if (!isset($_SESSION['userid'])) {
  header('Location: '.SITE_URL.'signin.php');
  exit;
}

if (!isset($_SESSION['planid'])) {
  header('Location: '.SITE_URL.'form.php');
  exit;
}

if (strcmp(@$_SERVER['HTTP_REFERER'], SITE_URL.'questionnaire.php') != 0 || !isset($_SESSION['quest'])) {
  header('Location: '.SITE_URL.'questionnaire.php');
  exit;
}

$tmp = $_SESSION['quest'];
unset($_SESSION['quest']);

$data = array(
  ':planid' => $_SESSION['planid'],
  ':area' => (int)$tmp['area'],
  ':category' => (int)$tmp['category'],
  ':mode' => (int)$tmp['mode']
);

// DB接続
$pdo = connectDB();

$sql = <<<EOS
UPDATE Plans
SET
  AreaCode = :area,
  Mode = :mode,
  TargetCategory = :category
WHERE PlanID = :planid
LIMIT 1
EOS;

try {
  $pdo->beginTransaction();
  $stmt = $pdo->prepare($sql);
  $stmt->execute($data);
  $pdo->commit();
}
catch (PDOException $e) {
  $pdo->rollBack();
  header('Location: '.SITE_URL.'questionnaire.php');
  exit;
}

// DB切断
$pdo = null;

// スポットリストへ遷移
header('Location: '.SITE_URL.'spotlist.php');
exit;

?>
