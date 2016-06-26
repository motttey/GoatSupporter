<?php
	//$nameについて入力値のチェック
	$name = isset($_GET['name']) ? $_GET['name'] : '';
	//文字エンコーディングチェック
	if(! mb_check_encording($name, 'UTF-8')){
		die('不正な文字エンコーディング');
	}

	//文字エンコーディングの変更はmv_convert_encording関数で行う
	//$name = mb_convert_encording($name, ''UTF-8, 'Shift_JIS');
?>
<body>
	<!-- 入力値の表示時にhtmlspecialchars を通してエンコーティングをチェックする -->
	Name is <?php echo htmlspecialchars($name, ENT_NOQUOTES, 'Shift_JIS' )?>
</doby>