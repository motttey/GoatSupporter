<?php
// Block directory traversal
// ファイル名を英数字に限定することでURLにディレクトリを指定することによる不正な呼び出しを防止
	define('TEMPLATE_DIR', 'PATH of Directory');
	tmp = $_GET['template'];
	//入力を英数字に限定
	if(!preg_match('/¥A[a-zA-Z0-9]+¥z/ui',$tmp)){
		die('不正なファイル呼び出し');
	}

?>
<html>
<body>
	<?php readfile(TEMPLATE_DIR .$tmp .'.html')?>
</body>
</html>