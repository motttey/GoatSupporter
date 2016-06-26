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

if (strcmp(@$_SERVER['HTTP_REFERER'], SITE_URL.'form.php') != 0 || !isset($_SESSION['form'])) {
  header('Location: '.SITE_URL.'form.php');
  exit;
}

$tmp = $_SESSION['form'];
unset($_SESSION['form']);

$data = array(
  ':userid' => $_SESSION['userid'],
  ':start' => $tmp['start'],
  ':finish' => $tmp['finish'],
  ':budget' => (int)$tmp['budget'],
  ':people' => (int)$tmp['people']
);

// DB接続
$pdo = connectDB();

// if (empty($_SESSION['profid']))
if (1) {
  $sql = <<<EOS
INSERT INTO Plans (
  UserID,
  Gender,
  Age,
  StartDatetime,
  FinishDateTime,
  Budget,
  People,
  AreaCode,
  Mode,
  TargetCategory,
  Created
)
SELECT
  UserID,
  Gender,
  (YEAR(:start) - YEAR(Birthday)) - (RIGHT(DATE(:start), 5) < RIGHT(Birthday, 5)),
  :start,
  :finish,
  :budget,
  :people,
  1,
  1,
  0,
  NOW()
FROM Users
WHERE UserID = :userid
EOS;
  try {
    $pdo->beginTransaction();
    $stmt = $pdo->prepare($sql);
    $stmt->execute($data);
    $_SESSION['planid'] = (int)$pdo->lastInsertId();
    $pdo->commit();
  }
  catch (PDOException $e) {
    $pdo->rollBack();
    header('Location: '.SITE_URL.'form.php');
    exit;
  }
}
/*
else {
  $user[':userid'] = $_SESSION['userid'];
  $sql = "UPDATE Users
          SET
            Name = :name,
            PartnerName = :partnername,
            Gender = :gender,
            Age = :age,
            PartnerAge = :partnerage
          WHERE UserID = :userid";
  $stmt = $pdo->prepare($sql);
  $stmt->execute($user);
}

if (empty($_SESSION['dateid'])) {
  $sql = "INSERT INTO DatePlan
          (UserID, StartDateTime, FinishDateTime)
          VALUES
          (:userid, CAST(:start as datetime), CAST(:finish as datetime))";
  $stmt = $pdo->prepare($sql);
  $datetime[':userid'] = $_SESSION['userid'];
  $stmt->execute($datetime);
  $_SESSION['dateid'] = getInsertedID($pdo);
}
else {
  $datetime[':dateid'] = $_SESSION['dateid'];
  $sql = "UPDATE DatePlan
          SET
            StartDateTime = :start,
            FinishDateTime = :finish
          WHERE DateID = :dateid";
  $stmt = $pdo->prepare($sql);
  $stmt->execute($datetime);
}
*/
// DB切断
$pdo = null;

// アンケートページへ遷移
header('Location: '.SITE_URL.'questionnaire.php');
exit;

?>
