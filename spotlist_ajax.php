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

$selectsql = <<<EOS
SELECT
  sp.SpotID AS spotid,
  sp.Name AS name,
  sp.Kana AS kana,
  sp.Description AS description,
  sp.ImagePath AS imgpath,
  sp.TEL AS tel,
  sp.URL AS url,
  pr.PriceID AS priceid,
  pr.MinAge AS minage,
  pr.MaxAge AS maxage,
  pr.ExamplePrice AS price,
  aa.AreaName AS area,
  ct1.CategoryName AS category,
  ct1.CategoryCode AS categorycode,
  sp.RequiredTime AS reqtime,
  op.OpenID AS openid,
  op.OpenTime AS open,
  IF(
    TIMEDIFF(op.CloseTime, '24:00:00')>=0,
    CONCAT('翌', CAST(SUBTIME(op.CloseTime, '24:00:00') AS char)),
    op.CloseTime
  ) AS close,
  CONCAT(
    IFNULL(ad.Prefecture, ''),
    IFNULL(ad.Ward, ''),
    IFNULL(ad.City, ''),
    IFNULL(ad.Address, ''),
    IFNULL(ad.Remarks, '')
  ) AS address,
  ad.Latitude AS latitude,
  ad.Longitude AS longitude,
  6378137 * ACOS(
    SIN(RADIANS(ad.Latitude)) * SIN(RADIANS(:latitude))
    + COS(RADIANS(ad.Latitude)) * COS(RADIANS(:latitude)) * COS(RADIANS(ad.Longitude - :longitude))
  ) AS distance
FROM Plans AS pn
INNER JOIN Spots AS sp
  ON pn.PlanID = :planid
  AND sp.Valid = 1
INNER JOIN Addresses AS ad
  ON ad.Valid = 1
  AND sp.SpotID = ad.SpotID
  AND pn.AreaCode = ad.AreaCode
INNER JOIN Area AS aa
  ON ad.AreaCode = aa.AreaCode
INNER JOIN OpeningHours AS op
  ON op.Valid = 1
  AND sp.SpotID = op.SpotID
  AND FIND_IN_SET(
    CASE DAYOFWEEK(:start)
      WHEN 1 THEN 'Sun'
      WHEN 2 THEN 'Mon'
      WHEN 3 THEN 'Tue'
      WHEN 4 THEN 'Wed'
      WHEN 5 THEN 'Thu'
      WHEN 6 THEN 'Fri'
      WHEN 7 THEN 'Sat'
    END,
    op.IsOpen
  )
  AND op.CloseTime >= TIMEDIFF(IF(op.OpenTime<=CAST(:start AS time), CAST(:start AS time), ADDTIME(CAST(:start AS time), '24:00:00')), sp.RequiredTime)
INNER JOIN Prices AS pr
  ON pr.Valid = 1
  AND sp.SpotID = pr.SpotID
  AND pn.Age BETWEEN pr.MinAge AND pr.MaxAge
  AND pr.ExamplePrice <= :budget + IF(:budget * 0.2 > 500, :budget * 0.2, 500)
INNER JOIN Categories AS ct1
  ON sp.CategoryCode = ct1.CategoryCode
INNER JOIN Categories AS ct2
  ON ct1.ParentCode = ct2.CategoryCode
  %s
GROUP BY sp.SpotID
ORDER BY
  IF(price <= :budget * 2/3 , 1, 0)
  + IF(price <= :budget, 1, 0)
  + IF(pn.TargetCategory = ct2.CategoryCode, 2, 0)
  + IF(ct2.CategoryCode = 3, IF((CAST(:start AS time) BETWEEN '07:00:00' AND '10:00:00') OR (CAST(:start AS time) BETWEEN '11:30:00' AND '13:00:00') OR (CAST(:start AS time) BETWEEN '17:30:00' AND '19:00:00'), 1, -1), 0)
  + IF((ct1.CategoryCode IN (300, 301, 302, 303, 304, 305, 306, 354, 399) AND (pn.mode = 1) AND (CAST(:start AS time) BETWEEN '11:00:00' AND '15:00:00')), 2, 0)
  + IF((ct1.CategoryCode IN (352, 358, 359) AND (pn.mode = 2) AND (CAST(:start AS time) BETWEEN '09:00:00' AND '18:00:00')), 2, 0)
  + IF((ct2.CategoryCode = 4) AND (pn.mode = 3), 0, -1000)
  + IF((ct2.CategoryCode IN (1, 2, 5)) AND (pn.People >= 4), IF(pn.People >= 8, 4, 2), 0)
  %s
  DESC
LIMIT 10
EOS;

// DB接続
$pdo = connectDB();

// パラメータ
$spotno = (int)$_POST['spotno'];
$planid = $_SESSION['planid'];

if ($spotno == 0) {
  // 一つ目のスポットリスト提示
  /*
  $sql = <<<EOS
SELECT
  Budget AS budget,
  StartDatetime AS start,
  FinishDatetime AS finish
FROM Plans
WHERE PlanID = :planid
LIMIT 1
EOS;
  $stmt = $pdo->prepare($sql);
  $stmt->execute(array(':planid' => $planid));
  $fetch = $stmt->fetch(PDO::FETCH_ASSOC);
  $_SESSION['startdatetime'] = $start = $fetch['start'];
  $_SESSION['finishdatetime'] = $fetch['finish'];
  $_SESSION['budget'] = (int)$fetch['budget'];
  $budget = $_SESSION['budget'];
  */

  // 開始時間と予算
  $start = $_SESSION['startdatetime'];
  $budget = $_SESSION['budget'];

  $sql = sprintf($selectsql, '', '');
  $data = array(
    ':planid' => $planid,
    ':latitude' => 0,
    ':longitude' => 0,
    ':budget' => $budget,
    ':start' => $start
  );
}
else {
  // 二つ目以降
  $spotid = (int)$_POST['spotid'];
  $openid = (int)$_POST['openid'];
  $priceid = (int)$_POST['priceid'];
  $schedule = convertDatetime($_POST['schedule'], 1);

  // プランに登録されているスポット数の取得
  $sql = <<<EOS
SELECT
  COUNT(*) - :spotno + 1 AS cnt
FROM Course
WHERE PlanID = :planid
EOS;
  $stmt = $pdo->prepare($sql);
  $stmt->execute(array(
    ':spotno' => $spotno,
    ':planid' => $planid
  ));
  $fetch = $stmt->fetch(PDO::FETCH_ASSOC);
  $limit = (int)$fetch['cnt'];
  try {
    $pdo->beginTransaction();

    // ADDされたスポット以降のスポット削除
    if ($limit > 0) {
      $sql = <<<EOS
DELETE
FROM Course
WHERE PlanID = :planid
ORDER BY Schedule DESC
LIMIT {$limit}
EOS;
      $stmt = $pdo->prepare($sql);
      $stmt->execute(array(':planid' => $planid));
    }

    // 開始日時取得
    $sql = <<<EOS
SELECT
  IFNULL(
    (
      SELECT
        IF(:schedule BETWEEN co.Schedule AND :finish, :schedule, ADDTIME(co.Schedule, sp.RequiredTime))
      FROM Course AS co
      INNER JOIN Spots AS sp
        ON co.PlanID = :planid
        AND co.SpotID = sp.SpotID
      ORDER BY co.Schedule DESC
      LIMIT 1
    ),
    IF(:schedule BETWEEN :start AND :finish, :schedule, :start)
  ) AS schedule
FROM DUAL
EOS;
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array(
      ':planid' => $planid,
      ':schedule' => $schedule,
      ':start' => $_SESSION['startdatetime'],
      ':finish' => $_SESSION['finishdatetime']
    ));
    $fetch = $stmt->fetch(PDO::FETCH_ASSOC);
    $schedule = $fetch['schedule'];

    // スポットをプランに登録
    $sql = <<<EOS
INSERT INTO Course
  (PlanID, Schedule, SpotID, OpenID, PriceID)
VALUES
  (:planid, :schedule, :spotid, :openid, :priceid)
EOS;
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array(
      ':planid' => $planid,
      ':schedule' => $schedule,
      ':spotid' => $spotid,
      ':openid' => $openid,
      ':priceid' => $priceid
    ));

    $pdo->commit();
  }
  catch (PDOException $e) {
    $pdo->rollBack();
    exit;
  }

  // 登録したスポット情報の確認
  $sql = <<<EOS
SELECT
  ADDTIME(co.Schedule, sp.RequiredTime) AS start,
  sp.CategoryCode AS beforecategory,
  ad.Latitude AS latitude,
  ad.Longitude AS longitude,
  T.budget AS budget
FROM Course AS co
INNER JOIN Spots AS sp
  ON co.SpotID = sp.SpotID
INNER JOIN Addresses AS ad
  ON sp.SpotID = ad.SpotID
INNER JOIN (
  SELECT
    :budget - IFNULL(sum(pr.ExamplePrice), 0) AS budget
  FROM Course AS co
  INNER JOIN Prices AS pr
    ON co.PlanID = :planid
    AND co.PriceID = pr.PriceID
) AS T
WHERE co.PlanID = :planid
ORDER BY co.Schedule DESC
LIMIT 1
EOS;
  $stmt = $pdo->prepare($sql);
  $stmt->execute(array(
    ':planid' => $planid,
    ':budget' => $_SESSION['budget']
  ));
  $fetch = $stmt->fetch(PDO::FETCH_ASSOC);
  $start = $fetch['start'];
  $beforecategory = (int)$fetch['beforecategory'];
  $latitude = (double)$fetch['latitude'];
  $longitude = (double)$fetch['longitude'];
  $budget = (int)$fetch['budget'];

  $sql = sprintf(
    $selectsql,
    'AND NOT EXISTS (SELECT 1 FROM Course AS co WHERE co.planid=:planid AND co.spotid=sp.spotid)',
<<<EOS
    + IF(sp.CategoryCode = :beforecategory, 0, 2)
    + IF(distance <= 375, 2, 0)
    + IF(distance <= 750, 1, 0)
    + IF(distance <= 1500, 1, 0)
EOS
  );
  $data = array(
    ':latitude' => $latitude,
    ':longitude' => $longitude,
    ':budget' => $budget,
    ':start' => $start,
    ':planid' => $planid,
    ':beforecategory' => $beforecategory
  );
}

$stmt = $pdo->prepare($sql);
$stmt->execute($data);

$results = array();
while ($spot = $stmt->fetch(PDO::FETCH_ASSOC)) {
  foreach (array('spotid', 'openid', 'priceid', 'minage', 'maxage', 'price', 'categorycode') as $k) {
    $spot[$k] = (int)$spot[$k];
  }
  foreach (array('latitude', 'longitude', 'distance') as $k) {
    $spot[$k] = (double)$spot[$k];
  }
  foreach (array('name', 'kana', 'description', 'imgpath', 'tel', 'url', 'area', 'category', 'address') as $k) {
    $spot[$k] = h($spot[$k]);
  }
  $results[] = $spot;
}

// DB切断
$pdo = null;

// JSON表示
echo json_encode(
  array(
    'spotdata' => $results,
    'start' => convertDatetime($start, 0),
    'budget' => (int)$budget
  ),
  JSON_UNESCAPED_UNICODE
);

?>
