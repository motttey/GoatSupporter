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

  $emailre = '/^(?!(?:(?:\x22?\x5C[\x00-\x7E]\x22?)|(?:\x22?[^\x5C\x22]\x22?)){255,})(?!(?:(?:\x22?\x5C[\x00-\x7E]\x22?)|(?:\x22?[^\x5C\x22]\x22?)){65,}@)(?:(?:[\x21\x23-\x27\x2A\x2B\x2D\x2F-\x39\x3D\x3F\x5E-\x7E]+)|(?:\x22(?:[\x01-\x08\x0B\x0C\x0E-\x1F\x21\x23-\x5B\x5D-\x7F]|(?:\x5C[\x00-\x7F]))*\x22))(?:\.(?:(?:[\x21\x23-\x27\x2A\x2B\x2D\x2F-\x39\x3D\x3F\x5E-\x7E]+)|(?:\x22(?:[\x01-\x08\x0B\x0C\x0E-\x1F\x21\x23-\x5B\x5D-\x7F]|(?:\x5C[\x00-\x7F]))*\x22)))*@(?:(?:(?!.*[^.]{64,})(?:(?:(?:xn--)?[a-z0-9]+(?:-[a-z0-9]+)*\.){1,126}){1,}(?:(?:[a-z][a-z0-9]*)|(?:(?:xn--)[a-z0-9]+))(?:-[a-z0-9]+)*)|(?:\[(?:(?:IPv6:(?:(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){7})|(?:(?!(?:.*[a-f0-9][:\]]){7,})(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,5})?::(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,5})?)))|(?:(?:IPv6:(?:(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){5}:)|(?:(?!(?:.*[a-f0-9]:){5,})(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,3})?::(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,3}:)?)))?(?:(?:25[0-5])|(?:2[0-4][0-9])|(?:1[0-9]{2})|(?:[1-9]?[0-9]))(?:\.(?:(?:25[0-5])|(?:2[0-4][0-9])|(?:1[0-9]{2})|(?:[1-9]?[0-9]))){3}))\]))$/iD';

  $passre = '/^[0-9a-zA-Z]{6,20}$/';

  $birthre = '/\d{4}\-\d{2}\-\d{2}/';

  $error = [];

  if (1 > strlen($_POST['name']) || strlen($_POST['name']) > 20) {
    $error[] = '名前は1文字以上20文字以内';
  }
  if (!preg_match($emailre, $_POST['email'])) {
    $error[] = '不正なメールアドレス';
  }
  else if (emailExists($_POST['email']) != 0) {
    $error[] = 'このメールアドレスは既に登録されています';
  }
  if (!preg_match($passre, $_POST['password'])) {
    $error[] = 'パスワードは英数字6文字以上20文字以内';
  }
  else if ($_POST['password'] != $_POST['repassword']) {
    $error[] = '二つのパスワードが異なっています';
  }
  else if (!preg_match($birthre, $_POST['birthyear'].'-'.$_POST['birthmonth'].'-'.$_POST['birthday'])) {
    $error[] = '誕生日の入力が不正です';
  }
  else if ($_POST['gender'] != '0' && $_POST['gender'] != '1') {
    $error[] = '性別の入力が不正です';
  }

  if (count($error) == 0) {
    $_POST['password'] = getPassword($_POST['repassword']);
    unset($_POST['repassword']);
    unset($_POST['token']);
    unset($_SESSION['token']);
    $_SESSION['signup'] = $_POST;
    unset($_POST);
    header('Location: '.SITE_URL.'registuser.php');
    exit;
  }
}

?>

<!DOCTYPE html>
<html>
<head><title>デートアプリ</title>

<meta http-equiv="Content-Script-Type" content="text/javascript">
<meta charset = "utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">

<link rel="stylesheet" type="text/css" href="css/bootstrap.min.css">
<!--<link rel="stylesheet" type="text/css" href="css/slick.css"> -->
<link rel="stylesheet" type="text/css" href="css/style.css">
<link rel="stylesheet" type="text/css" href="css/login.css">
<!-- <link rel="stylesheet" type="text/css" href="css/hover.css"> -->
<script type="text/javascript" src="js/jquery.min.js"></script>
<script type="text/javascript" src="js/jquery.valid8.js"></script>
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

<script>
  function passwordMatch(values) {
    if (values.password == values.reinput) {
      return {valid:true}
    }
    else {
      return {valid:false, message:'二つのパスワードが異なっています'}
    }
  }

  function emailCheck(values) {
    var re = /^[-a-z0-9~!$%^&*_=+}{\'?]+(\.[-a-z0-9~!$%^&*_=+}{\'?]+)*@([a-z0-9_][-a-z0-9_]*(\.[-a-z0-9_]+)*\.(aero|arpa|biz|com|coop|edu|gov|info|int|mil|museum|name|net|org|pro|travel|mobi|[a-z][a-z])|([0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}))(:[0-9]{1,5})?$/i;
    if (!values.email.match(re)) {
      return {valid:false, message:'不正なメールアドレス'};
    }
    $.ajax({
      type: 'POST',
      url: 'emailoverlap.php',
      async: false,
      data: {
        email: values.email,
        token: "<?=$_SESSION['token']?>"
      },
      dataType: 'json'
    })
    .done(function(json){
      if (json['invalid'] == 1) {
        result = {valid:false, message:'既に登録されています'};
      }
      else {
        result = {valid:true};
      }
    })
    .fail(function(){
      result = {vaild:false, message:'通信エラー'};
    });
    return result;
  }

  $(document).ready(function(){
    $('#inputName').valid8({
      'regularExpressions': [
        {expression:/^.{1,20}$/, errormessage:'1文字以上20文字以内'}
      ]
    });

    $('#inputEmail').valid8({
      'jsFunctions': [
        { function: emailCheck,
          values: function(){
            return {email:$('#inputEmail').val()}
          }
        }
      ]
    });

    $('#inputPassword').valid8({
      'regularExpressions': [
        {expression:/^[0-9a-zA-Z]{6,20}$/, errormessage:'英数字6文字以上20文字以内'}
      ]
    });

    $('#reinputPassword').valid8({
      'jsFunctions': [
        { function: passwordMatch,
          values: function(){
            return {password:$('#inputPassword').val(), reinput:$('#reinputPassword').val()}
          }
        }
      ]
    });
  });

  function reCheck(values) {
    if (!values.name.match(/^.{1,20}$/) || !values.password.match(/^[0-9a-zA-Z]{6,20}$/)) {
      return {valid:false}
    }
    return {valid:true}
  }

  function formCheck() {
    tmp = [
      emailCheck({email:$('#inputEmail').val()}),
      passwordMatch({password:$('#inputPassword').val(), reinput:$('#reinputPassword').val()}),
      reCheck({name:$('#inputName').val(), password:$('#inputPassword').val()})
    ];
    for (i = 0; i < tmp.length; i++) {
      if (!tmp[i]['valid']) {
        return false;
      }
    }
    return true;
  }
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
        <!-- エラーメッセージを表示するクラス -->
        <div class="error">
          <?php
            if(isset($error)) {
              // エラー出力
              for ($i = 0; $i < count($error); $i++) {
                echo $error[$i].'<br>';
              }
            }
          ?>
        </div>
        <div class="signupform">
          <!-- ロゴとかキャッチコピーが入る -->
          <div class="headerlogo">
            <img src="img/goatlogo.png" class="list-image img-circle" alt="example">
          </div>
          <!-- ロゴとかキャッチコピーが入る -->
          <div class="formtitle">Sign up</div>
            <p id="profile-name" class="profile-name-card"></p>
            <form name="form" class="form-signin" method="POST" action="">
              <!--<span id="reauth-email" class="reauth-email"></span>-->
              <span>
              <input type="text" name="name" id="inputName" class="form-control" value="<?=h($_POST['name'] ?: '')?>" placeholder="Name" required>
              <span id="inputNameValidationMessage" class="validationMessage"></span>
              </span>
              <span>
              <input type="email" name="email" id="inputEmail" class="form-control" value="<?=h($_POST['email'] ?: '')?>" placeholder="Email address" required>
              <span id="inputEmailValidationMessage" class="validationMessage"></span>
              </span>
              <span>
              <input type="password" name="password" id="inputPassword" class="form-control" placeholder="Password" required>
              <span id="inputPasswordValidationMessage" class="validationMessage"></span>
              </span>
              <span>
              <input type="password" name="repassword" id="reinputPassword" class="form-control" placeholder="Confirm Password" required>
              <span id="reinputPasswordValidationMessage" class="validationMessage"></span>
              </span>
              <!--
              -->
              <p>
                生年月日
                <p>
                <select name="birthyear" id="inputBirthyear" required>
                  <option value="">年</option>
                  <?php $year = (int)date("Y"); ?>
                  <?php for($i = $year; $i > $year-120; $i--): ?>
                    <option value="<?=$i?>"><?=$i?>年</option>
                  <?php endfor; ?>
                </select>
                <select name="birthmonth" id="inputBirthmonth" required>
                  <option value="">月</option>
                  <option value="01">1月</option>
                  <option value="02">2月</option>
                  <option value="03">3月</option>
                  <option value="04">4月</option>
                  <option value="05">5月</option>
                  <option value="06">6月</option>
                  <option value="07">7月</option>
                  <option value="08">8月</option>
                  <option value="09">9月</option>
                  <option value="10">10月</option>
                  <option value="11">11月</option>
                  <option value="12">12月</option>
                </select>
                <select name="birthday" id="inputBirthday" required>
                  <option value="">日</option>
                  <option value="01">1日</option>
                  <option value="02">2日</option>
                  <option value="03">3日</option>
                  <option value="04">4日</option>
                  <option value="05">5日</option>
                  <option value="06">6日</option>
                  <option value="07">7日</option>
                  <option value="08">8日</option>
                  <option value="09">9日</option>
                  <option value="10">10日</option>
                  <option value="11">11日</option>
                  <option value="12">12日</option>
                  <option value="13">13日</option>
                  <option value="14">14日</option>
                  <option value="15">15日</option>
                  <option value="16">16日</option>
                  <option value="17">17日</option>
                  <option value="18">18日</option>
                  <option value="19">19日</option>
                  <option value="20">20日</option>
                  <option value="21">21日</option>
                  <option value="22">22日</option>
                  <option value="23">23日</option>
                  <option value="24">24日</option>
                  <option value="25">25日</option>
                  <option value="26">26日</option>
                  <option value="27">27日</option>
                  <option value="28">28日</option>
                  <option value="29">29日</option>
                  <option value="30">30日</option>
                  <option value="31">31日</option>
                </select>
                </p>
              </p>
              <p>
                <label for "Female"><input name="gender" id="inputGender" type="radio" value="1" required>Female</label>
                &nbsp;&nbsp;&nbsp;
                <label for "Male"><input name="gender" id="inputGender" type="radio" value="0" required>Male</label>
              </p>
              <!--
              <label for "Female"><input type="radio" name="gender" id="Female" value="1" class="form-control" required></label>
              <label for "Male"><input type="radio" name="gender" id="Male" value="0" class="form-control" required></label>
              -->
              <!--
              <div id="remember" class="checkbox">
              </div>
              -->
              <input type="hidden" name="token" value="<?=h($_SESSION['token'])?>">
              <button class="btn btn-lg btn-salmon btn-block" type="submit" onclick="return formCheck();">Sign Up</button>
            </form><!-- /form -->
          </div>
        </div>
      </div>
    </div><!-- /container -->
  </div>
</body>

</html>
