<?php

require_once('phpconf.php');
require_once('phpfunc.php');
require_once('phpsecurity.php');

session_cache_expire(0);
session_cache_limiter('private_no_expire');
session_start();


if (strcmp(@$_SERVER['HTTP_REFERER'], SITE_URL.'signup.php') != 0 || !isset($_SESSION['signup'])) {
  header('Location: '.SITE_URL.'signup.php');
  exit;
}

$tmp = $_SESSION['signup'];
unset($_SESSION['signup']);

$data = array(
  ':email' => $tmp['email'],
  ':password' => $tmp['password'],
  ':name' => $tmp['name'],
  ':gender' => (int)$tmp['gender'],
  ':birthday' => $tmp['birthyear'].'-'.$tmp['birthmonth'].'-'.$tmp['birthday']
);

// DB接続
$pdo = connectDB();

if (1) {
  $sql = <<<EOS
INSERT INTO Users (
  Email,
  Password,
  Name,
  Gender,
  Birthday,
  Created
)
VALUES (
  :email,
  :password,
  :name,
  :gender,
  :birthday,
  NOW()
)
EOS;
  try {
    $pdo->beginTransaction();
    $stmt = $pdo->prepare($sql);
    $stmt->execute($data);
    $_SESSION['userid'] = $pdo->lastInsertId();
    $pdo->commit();
  }
  catch (PDOException $e) {
    $pdo->rollBack();
    header('Location: '.SITE_URL.'signup.php');
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
