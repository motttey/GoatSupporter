<?php
require_once('phpconf.php');
require_once('phpfunc.php');
require_once('phpsecurity.php');

session_cache_expire(0);
session_cache_limiter('private_no_expire');
session_start();

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
  // CSRF対策
  setToken();

} else {
  $_POST = arrayString($_POST);

  checkToken();

  $pdo = connectDB();
  $sql = <<<EOS
SELECT
  UserID as userid
FROM Users
WHERE Email LIKE :email
  AND Password LIKE :password
LIMIT 1
EOS;
  $stmt = $pdo->prepare($sql);
  $stmt->execute(array(
    ':email' => $_POST['email'],
    ':password' => getPassword($_POST['password'])
  ));
  $fetch = $stmt->fetch(PDO::FETCH_ASSOC);
  if (isset($fetch['userid'])) {
    $_SESSION['userid'] = (int)$fetch['userid'];
    unset($_SESSION['token']);
    unset($_POST);
    header('Location: '.SITE_URL.'form.php');
    exit;
  }
}
?>

<!DOCTYPE html>
<html>
<head><title>Signin - GoatSupporter</title>

<meta http-equiv="Content-Script-Type" content="text/javascript">
<meta charset = "utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">

<link rel="stylesheet" type="text/css" href="css/bootstrap.min.css">
<!--<link rel="stylesheet" type="text/css" href="css/slick.css"> -->
<link rel="stylesheet" type="text/css" href="css/style.css">
<link rel="stylesheet" type="text/css" href="css/login.css">
<!-- <link rel="stylesheet" type="text/css" href="css/hover.css"> -->
<script type="text/javascript" src="js/jquery.min.js"></script>

<!-- Using Font Awesome-->
<link rel="stylesheet" href="css/font-awesome.min.css">

<!-- ナビゲーション用の スタイル-->
<link rel="stylesheet" type="text/css" href="css/navigation.css">

<!-- View Tooltip (Using tooltip.js in Bootstrap)-->
<script type="text/javascript" src="js/tooltip.js"></script>
<!--画面サイズが500px以上のメディア(すまほ)ではツールチップ表示-->
<script type="text/javascript">
$(document).ready(function(){
  if (window.matchMedia('screen and (min-width:800px)').matches) {
    //500px以上の処理
  }else{
      $('[data-toggle="tooltip"]').tooltip();
  }
});
</script>

<!-- ここまでナビゲーション用の スタイル-->

<!-- for iCheck -->
<script type="text/javascript" src="js/icheck.js"></script>
<link rel="stylesheet" type="text/css" href="css/skins/flat/red.css">

<script>
$(document).ready(function(){
  $('input').iCheck({
    checkboxClass: 'icheckbox_flat',
    radioClass: 'iradio_flat',
    //increase checkable area (max:200%)
    increaseArea: '100%'
  });
});
</script>

<script>
$(document).ready(function(){
  $('input').iCheck({
    checkboxClass: 'icheckbox_flat-red',
    radioClass: 'iradio_flat-red',
    increaseArea: '100%'
  });
});
</script>

<!-- 年齢が正当な値かチェック -->
<script type="text/javascript">
$(function(){
  $("input[value=submit]").click(function(){
  if($("input[type=number][id=age]").val() <= 0){
    res = "負の値です" ;
    $("div.error").text(res);
  }
  })
});
</script>

<!-- Feedbackは任意なので入力内容チェックしなくていいかも... -->

<script type="text/javascript">
$(function(){
  var res = "";
  /*
  $("input[value=submit]").click(function(){
  if($("input[type=text][id=name]").val() == ""){
    res += "あなたの名前が入力されていません<br>" ;
    $("div.error").html(res);
  }
  if($("input[type=text][id=partnername]").val() == "" ){
    res += "パートナーの名前が入力されていません" ;
    $("div.error").html(res);
  }
  */
  /*
  if(($("input[type=text][id=name]").val() == false ) && ($("input[type=text][id=name]").val() == false )){
    res = "性別が入力されていません" ;
    $("div.error").text(res);
  }
  */
  /*
  else if($("input[type=number][id=age]").val() == null){
    res += "年齢が入力されていません" ;
    $("div.error").html(res);
  }
  else if($("input[type=datetime][id=datetime]").val() == null){
    res += "日程が入力されていません" ;
    $("div.error").html(res);
  }
  */
  })
});


</script>

<!-- For modal.js-->
<script type="text/javascript" src="js/modal.js"></script>
<script type="text/javascript">
$('#myModal').on('shown.bs.modal', function () {
  $('#myInput').focus()
})
</script>

</head>

<body>
  <!--
    you can substitue the span of reauth email for a input with the email and
    include the remember me checkbox
  -->
  <div class="bgimage">
    <div class="formcontainer">
      <div class="wrapform">
        <div class="loginform">
          <div class="signup-message">
            <a href="signup.php" >
                Sign Up
            </a>
          </div>
          <!-- ロゴとかキャッチコピーが入る -->
          <div class="headerlogo">
            <img src="img/goatlogo.png" class="list-image img-circle" alt="example">
          </div>
          <!-- ロゴとかキャッチコピーが入る -->
          <div class="formtitle">Sign in</div>
          <p id="profile-name" class="profile-name-card"></p>
          <form class="form-signin" method="POST" action="">
            <!--<span id="reauth-email" class="reauth-email"></span>-->
            <input type="email" name="email" id="inputEmail" class="form-control" placeholder="Email address" value="<?=h($_POST['email'] ?: '')?>" required autofocus>
            <input type="password" name="password" id="inputPassword" class="form-control" placeholder="Password" required>
            <!--
            <div id="remember" class="checkbox">
                <label>
                    <input type="checkbox" value="remember-me"> Remember me
                </label>
            </div>
            -->
            <input type="hidden" name="token" value="<?=h($_SESSION['token'])?>">
            <button class="btn btn-lg btn-salmon btn-block" type="submit">Sign in</button>
          </form><!-- /form -->
          <a href="#" class="forgot-password">
              Forgot the password?
          </a>
          <br>
        </div>
      </div>
    </div>
  </div>
</body>

</html>
