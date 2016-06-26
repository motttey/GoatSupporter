<?php
	//JavascriptによるXSS対策,URLが正当な入力値であるかチェック
	function checkurl($url){
		if(preg_match('/¥Ahttp:/', $url) || preg_match('/¥Ahttps:/' ){
			return true;
		}else{
			return false;
		}
		//falseのときは入力を受け付けない
	}
?>
