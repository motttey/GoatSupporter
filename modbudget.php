<?php

require_once('phpconf.php');
require_once('phpfunc.php');

session_cache_expire(0);
session_cache_limiter('private_no_expire');
session_start();

if (strcmp(@$_SERVER['HTTP_REFERER'], SITE_URL.'spotlist.php') != 0 || $_SESSION['token'] != $_POST['token']) {
  header('Location: '.SITE_URL.'spotlist.php');
  exit;
}

$_POST = arrayString($_POST);

$data = array(
  ':planid' => $_SESSION['planid'],
  ':addbudget' => (int)$_POST['addbudget']
);

if (!is_int($data[':addbudget'])) {
  // JSON表示
  echo json_encode(
    array('addbudget' => 0),
    JSON_UNESCAPED_UNICODE
  );
  exit;
}

// DB接続
$pdo = connectDB();

$sql = <<<EOS
SELECT
  Budget + :addbudget AS budget
FROM Plans
WHERE PlanID = :planid
LIMIT 1
EOS;
$stmt = $pdo->prepare($sql);
$stmt->execute($data);
$fetch = $stmt->fetch(PDO::FETCH_ASSOC);
$tmpbud = (int)$fetch['budget'];
if ($tmpbud < 0) {
  // JSON表示
  echo json_encode(
    array('addbudget' => 0),
    JSON_UNESCAPED_UNICODE
  );
  exit;
}
else {
  $_SESSION['budget'] = $tmpbud;
}

$sql = <<<EOS
UPDATE Plans
SET Budget = Budget + :addbudget
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
  // JSON表示
  echo json_encode(
    array('addbudget' => 0),
    JSON_UNESCAPED_UNICODE
  );
  exit;
}

// DB切断
$pdo = null;

// JSON表示
echo json_encode(
  array('addbudget' => (int)$data[':addbudget']),
  JSON_UNESCAPED_UNICODE
);

?>