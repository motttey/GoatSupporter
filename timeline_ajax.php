<?php

require_once('phpconf.php');
require_once('phpfunc.php');

session_cache_expire(0);
session_cache_limiter('private_no_expire');
session_start();

if (strcmp(@$_SERVER['HTTP_REFERER'], SITE_URL.'timeline.php') != 0 || $_SESSION['token'] != $_POST['token']) {
  header('Location: '.SITE_URL.'timeline.php');
  exit;
}

$planid = $_SESSION['planid'];

// データベース接続
$pdo = connectDB();

$_POST = arrayString($_POST);

if (!empty($_POST['schedule'])) {
  $schedule = convertDatetime($_POST['schedule'], 1);
  $data = array(
    ':planid' => $planid,
    ':schedule' => $schedule
  );
  if (!empty($_POST['newschedule'])) {
    $data[':newschedule'] = convertDatetime($_POST['newschedule'], 1);
    $sql = <<<EOS
UPDATE Course
  SET Schedule = CAST(:newschedule AS datetime)
WHERE PlanID = :planid
  AND Schedule = CAST(:schedule AS datetime)
LIMIT 1
EOS;
  }
  else {
    $sql = <<<EOS
DELETE
FROM Course
WHERE PlanID = :planid
  AND Schedule = CAST(:schedule AS datetime)
LIMIT 1
EOS;
  }
  try {
    $pdo->beginTransaction();
    $stmt = $pdo->prepare($sql);
    $stmt->execute($data);
    $pdo->commit();
  }
  catch (PDOException $e) {
    $pdo->rollBack();
  }
}

$sql = <<<EOS
SELECT
  co.Schedule AS schedule,
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
  CONCAT(
    IF(TIMEDIFF('24:00:00', op.CloseTime)>=0, '翌', ''),
    CAST(SUBTIME('24:00:00', op.CloseTime) AS char)
  ) AS close,
  CONCAT(
    IFNULL(ad.Prefecture, ''),
    IFNULL(ad.Ward, ''),
    IFNULL(ad.City, ''),
    IFNULL(ad.Address, ''),
    IFNULL(ad.Remarks, '')
  ) AS address,
  ad.Latitude AS latitude,
  ad.Longitude AS longitude
FROM Course AS co
INNER JOIN Spots AS sp
  ON co.PlanID = :planid
  AND co.SpotID = sp.SpotID
INNER JOIN Addresses AS ad
  ON sp.SpotID = ad.SpotID
INNER JOIN Area AS aa
  ON ad.AreaCode = aa.AreaCode
INNER JOIN OpeningHours AS op
  ON co.OpenID = op.OpenID
INNER JOIN Prices AS pr
  ON co.PriceID = pr.PriceID
INNER JOIN Categories AS ct1
  ON sp.CategoryCode = ct1.CategoryCode
INNER JOIN Categories AS ct2
  ON ct1.ParentCode = ct2.CategoryCode
ORDER BY Schedule ASC
EOS;

$stmt = $pdo->prepare($sql);
$stmt->execute(array(':planid' => $planid));
$results = array();
while ($spot = $stmt->fetch(PDO::FETCH_ASSOC)) {
  foreach (array('spotid', 'openid', 'priceid', 'minage', 'maxage', 'price', 'categorycode') as $k) {
    $spot[$k] = (int)$spot[$k];
  }
  foreach (array('latitude', 'longitude') as $k) {
    $spot[$k] = (double)$spot[$k];
  }
  foreach (array('name', 'kana', 'description', 'imgpath', 'tel', 'url', 'area', 'category', 'address') as $k) {
    $spot[$k] = h($spot[$k]);
  }
  $spot['schedule'] = convertDatetime($spot['schedule'], 0);
  $results[] = $spot;
}

$sql = <<<EOS
SELECT
  pn.Budget as budget,
  SUM(pr.ExamplePrice) as sumprice
FROM Plans AS pn
INNER JOIN Course AS co
  ON pn.PlanID = :planid
  AND pn.PlanID = co.PlanID
INNER JOIN Prices AS pr
  ON co.PriceID = pr.PriceID
EOS;

$stmt = $pdo->prepare($sql);
$stmt->execute(array(':planid' => $planid));
$fetch = $stmt->fetch(PDO::FETCH_ASSOC);
$budget = (int)$fetch['budget'];
$sumprice = (int)$fetch['sumprice'];

// DB切断
$pdo = null;

// JSON表示
echo json_encode(
  array(
    'spotdata' => $results,
    'budget' => $budget,
    'sumprice' => $sumprice
  ),
  JSON_UNESCAPED_UNICODE
);

?>
