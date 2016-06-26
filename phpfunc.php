<?php

// phpファイル用の関数まとめファイル

// DB接続
function connectDB() {
  try {
    $pdo = new PDO(DSN, DB_USER, DB_PASSWORD);
    // デバッグ用
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $pdo;
  } catch (PDOException $e) {
    echo $e->getMessage();
    exit;
  }
}

function h($s) {
  return htmlspecialchars($s, ENT_QUOTES, "UTF-8");
}

function emailExists($email) {
  $pdo = connectDB();
  $stmt = $pdo->prepare('SELECT EXISTS(SELECT 1 FROM Users WHERE Email LIKE :email)');
  $stmt->execute(array(':email' => $email));
  $fetch = $stmt->fetch();
  return (int)$fetch[0];
}

function arrayString($arr) {
  if (!isset($arr)) {
    return false;
  }
  foreach ($arr as $k => $v) {
    $_arr[$k] = (string)$v;
  }
  return $arr;
}

function convertDatetime($s, $f) {
  return $f ? str_replace('T', ' ', $s).':00' : (string)date("Y-m-d\TH:i", strtotime($s));
}

?>
