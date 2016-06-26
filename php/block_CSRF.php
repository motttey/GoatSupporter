<?php
//Block CSRF(Cross site request forgeries)
//罠サイトを閲覧させることによる攻撃対象サイトへのPOSTリクエスト送信,それによるパスワード改ざん,退会処理
//ログイン画面つけない場合必要なし
//他の対策手法としてRefererの確認,パスワードの再入力がある
//パスワード再入力はログインフォーム作るなら必須

 session_start();
 if(session_id() !== $_POST['token'] ){
 	//POSTメソッドによるトークンの送信元が正規のものであるか確認
 	die('正規の画面を経て入力してください');
 }
?>
<html>
<!-- HTML側でセッションにトークンを埋め込む-->
<body>
	<form action="hogehoge.php" name="token" value>
	<!-- 
	パスワードとかの入力フォームが配置されている
	-->
	
	<!-- 埋め込み(非表示) -->
	<input type="hidden" name="token" value="<?php echo htmlspecialchars(session_id(), ENT_CONPAT, 'UTF-8'); ?>">
	<input type="submit" value="submitval">
	</form>
</body>
</html>

