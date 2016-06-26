<?php
	//$nameについて入力値のチェック
	$name = isset($_GET['name']) ? $_GET['name'] : '';

	//文字列 or 英数字のみ許可 (最大文字数は20)
	//許可する記号とかあれば適宜追加する
	if(preg_match('/¥A[a-z0-9]{1,20}¥z/ui', $name) == 0 ){
		die('不正な入力です');
	}
	//文字エンコーディングの変更はmv_convert_encording関数で行う
	//$name = mb_convert_encording($name, ''UTF-8, 'Shift_JIS');
?>
<body>
	<!-- 入力値の表示時にhtmlspecialchars を通してエンコーティングをチェックする -->
	Name is <?php echo htmlspecialchars($name ,ENT_NOQUOTES, 'Shift_JIS' )?>
</doby>