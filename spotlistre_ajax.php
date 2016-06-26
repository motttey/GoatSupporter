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

// DB接続
$pdo = connectDB();

// パラメータ
$planid = $_SESSION['planid'];

// スポットがプランに登録されているか
$sql = <<<EOS
SELECT
  COUNT(co.SpotID) AS cnt,
  pn.Budget AS budget,
  pn.StartDatetime AS start,
  pn.FinishDatetime AS finish
FROM Plans AS pn
LEFT JOIN Course AS co
  ON pn.PlanID = co.PlanID
WHERE pn.PlanID = :planid
EOS;
$stmt = $pdo->prepare($sql);
$stmt->execute(array(':planid' => $planid));
$fetch = $stmt->fetch(PDO::FETCH_ASSOC);

// 開始終了日時と予算
$_SESSION['startdatetime'] = $start = $fetch['start'];
$_SESSION['finishdatetime'] = $fetch['finish'];
$_SESSION['budget'] = $budget = (int)$fetch['budget'];

// 登録されていない場合
if ((int)$fetch['cnt'] == 0) {
  echo json_encode(
    array('valid' => 0),
    JSON_UNESCAPED_UNICODE
  );
  exit;
}

// 登録されているスポットのID等取得
$sql = <<<EOS
SELECT
  co.Schedule AS start,
  co.SpotID AS spotid,
  co.OpenID AS openid,
  co.PriceID AS priceid,
  pr.ExamplePrice AS price,
  ct.CategoryCode AS categorycode,
  ad.Latitude AS latitude,
  ad.Longitude AS longitude
FROM Course AS co
INNER JOIN Spots AS sp
  ON co.PlanID = :planid
  AND co.SpotID = sp.SpotID
INNER JOIN Prices AS pr
  ON co.PriceID = pr.PriceID
INNER JOIN Addresses AS ad
  ON sp.SpotID = ad.SpotID
INNER JOIN Categories AS ct
  ON sp.CategoryCode = ct.CategoryCode
ORDER BY co.Schedule ASC
EOS;
$stmt = $pdo->prepare($sql);
$stmt->execute(array(':planid' => $planid));
$spots = array();
while ($spot = $stmt->fetch(PDO::FETCH_ASSOC)) {
  foreach (array('spotid', 'openid', 'priceid', 'price', 'categorycode') as $k) {
    $spot[$k] = (int)$spot[$k];
  }
  foreach (array('latitude', 'longitude') as $k) {
    $spot[$k] = (double)$spot[$k];
  }
  $spots[] = $spot;
}

// 開始時間取得
$sql = <<<EOS
SELECT
  ADDTIME(co.Schedule, sp.RequiredTime) AS start
FROM Course AS co
INNER JOIN Spots AS sp
  ON co.SpotID = sp.SpotID
WHERE co.PlanID = :planid
ORDER BY co.Schedule DESC
LIMIT 1
EOS;
$stmt = $pdo->prepare($sql);
$stmt->execute(array(':planid' => $planid));
$spots[] = $stmt->fetch(PDO::FETCH_ASSOC);

// SELECT文テンプレート
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
  AND op.CloseTime >= TIMEDIFF(IF(op.OpenTime <= CAST(:start AS time), CAST(:start AS time), ADDTIME(CAST(:start AS time), '24:00:00')), sp.RequiredTime)
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

$results = array();
for ($i = 0, $len = count($spots)-1; $i <= $len; $i++) {
  $data = array(
    ':planid' => $planid,
    ':budget' => $budget,
    ':start' => $spots[$i]['start']
  );

  if ($i < $len) {
    // スポットが選択されているリスト
    $order = '+ IF(sp.SpotID = :spotid AND op.OpenID = :openid AND pr.PriceID = :priceid, 10000, 0)';
    $data = array_merge(
      $data,
      array(
        ':spotid' => $spots[$i]['spotid'],
        ':openid' => $spots[$i]['openid'],
        ':priceid' => $spots[$i]['priceid']
      )
    );
  }
  else {
    // スポットが選択されていないリスト
    $order = '';
  }

  if ($i == 0) {
    // 最初のスポットリスト
    $where = '';
    $data = array_merge(
      $data,
      array(
        ':latitude' => 0,
        ':longitude' => 0
      )
    );
  }
  else {
    // 2番目以降
    $where = 'AND NOT EXISTS (SELECT 1 FROM Course AS co WHERE co.planid = :planid AND co.spotid = sp.spotid %s)';
    if ($i < $len) {
      $where = sprintf($where, 'AND sp.SpotID <> :spotid');
    }
    else {
      $where = sprintf($where, '');
    }
    $order = $order.<<<EOS
      + IF(sp.CategoryCode = :beforecategory, 0, 2)
      + IF(distance <= 375, 2, 0)
      + IF(distance <= 750, 1, 0)
      + IF(distance <= 1500, 1, 0)
EOS;
    $data = array_merge(
      $data,
      array(
        ':latitude' => $spots[$i-1]['latitude'],
        ':longitude' => $spots[$i-1]['longitude'],
        ':beforecategory' => $spots[$i-1]['categorycode']
      )
    );
  }
  $sql = sprintf($selectsql, $where, $order);
  $stmt = $pdo->prepare($sql);
  $stmt->execute($data);
  $tmp = array();
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
    $tmp[] = $spot;
  }
  $results[] = array(
    'spotdata' => $tmp,
    'start' => convertDatetime($spots[$i]['start'], 0),
    'budget' => $budget
  );
  if ($i < $len) {
    $budget -= $spots[$i]['price'];
  }
}

$selected = array();
for ($i = 0, $len = count($spots)-1; $i < $len; $i++) {
  $tmp = $spots[$i];
  $selected[] = array(
    'spotid' => $tmp['spotid'],
    'openid' => $tmp['openid'],
    'priceid' => $tmp['priceid']
  );
}

// DB切断
$pdo = null;

// JSON表示
echo json_encode(
  array(
    'valid' => 1,
    'data' => $results,
    'selected' => $selected
  ),
  JSON_UNESCAPED_UNICODE
);

?>