<?php

// php用のコンフィグファイル

/*
GRANT SELECT, UPDATE, INSERT, DELETE ON [DB名].[テーブル名] TO '[ユーザ名]'@'[ホスト名]';
程度の権限を持ったユーザとパスワード
*/
define('DSN', 'mysql:host=localhost;dbname=Sheep;charset=utf8;');
define('DB_USER', 'root');
define('DB_PASSWORD', 'pass');

define('SITE_URL', 'http://localhost:8080/public/sheep-files/');
// define('SITE_URL', 'http://11planner.hacked.jp/');

error_reporting(E_ALL & ~E_NOTICE);

date_default_timezone_set('Asia/Tokyo');

?>
