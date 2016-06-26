<?php
	//プレースホルダを使用したSQLインジェクション対策
	//PDO使ってるならPDO向けに対策した方がよいかも
	/*
		DBへの接続処理は既に完了していて、セッションは$dbに保持されてるとする
	*/
	//アンケートフォームからエリアを取得した上でのSELECT文を想定
 	$Area = $_GET['area']

	$sql = "SELECT * FROM Spot WHERE Area = ? ORDER BY SpotID"
	//SQLの呼び出し,第二引数の配列にてプレースホルダのデータ型を指定
	$stmt = $db->prepare($sql,array['text']);
	//SQLを実行
	$rs = $stmt->execute(array($Area));

?>
<body>

</doby>