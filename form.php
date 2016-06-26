<?php

require_once('phpconf.php');
require_once('phpfunc.php');
require_once('phpsecurity.php');

session_cache_expire(0);
session_cache_limiter('private_no_expire');
session_start();

if (!isset($_SESSION['userid'])) {
  header('Location: '.SITE_URL.'signin.php');
  exit;
}

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
  // CSRF対策
  setToken();

} else {
  $_POST = arrayString($_POST);

  checkToken();

  $now = new Datetime();
  $start = new DateTime(convertDatetime($_POST['start'], 1));
  $finish = new DateTime(convertDatetime($_POST['finish'], 1));
  $minfin = (new DateTime(convertDatetime($_POST['start'], 1)))->modify('+2 hour');
  $maxfin = (new DateTime(convertDatetime($_POST['start'], 1)))->modify('+3 day');

  $error = [];

  if ($now > $start) {
    $error[] = '開始日時は現在日時より後';
  }
  if ($start >= $finish) {
    $error[] = '終了日時は開始日時より後';
  }
  if ($minfin >= $finish) {
    $error[] = '開始から終了までが2時間以上';
  }
  if ($maxfin < $finish) {
    $error[] = '開始から終了までが72時間以内';
  }
  if ($_POST['people'] < 1 || 20 < $_POST['people'] ) {
    $error[] = '人数は1人以上20人以下';
  }
  if ($_POST['budget'] < 0) {
    $error[] = '予算は0円以上';
  }

  if (count($error) == 0) {
    unset($_POST['token']);
    unset($_SESSION['token']);
    $_POST['start'] = convertDatetime($_POST['start']);
    $_POST['finish'] = convertDatetime($_POST['finish']);
    $_SESSION['form'] = $_POST;
    unset($_POST);
    header('Location: '.SITE_URL.'registplan.php');
    exit;
  }
}

?>

<!DOCTYPE html>
<html>
<head><title>Form - GoatSupporter</title>

<meta http-equiv="Content-Script-Type" content="text/javascript">
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">

<link rel="stylesheet" type="text/css" href="css/bootstrap.min.css">
<!--<link rel="stylesheet" type="text/css" href="css/slick.css"> -->
<link rel="stylesheet" type="text/css" href="css/style.css">
<link rel="stylesheet" type="text/css" href="css/form.css">
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

<!-- 年齢が正当な値かチェック -->
<!--
<script type="text/javascript">
$(function(){
  $("input[value=submit]").click(function(){
  if($("input[type=number][id=age]").val() <= 0){
    res="負の値です" ;
    $("div.error").text(res);
  }
  })
});
</script>
-->

<script>
  function datetimeCheck(values) {
    re = /\d{4}-\d{2}-\d{2}T\d{2}:\d{2}/
    if (values.datetime.match(re)) {
      return {valid:true}
    }
    else {
      return {valid:false, message:'不正な日時です'}
    }
  }

  function peopleCheck(values) {
    if (1 <= values.people && values.people <= 20) {
      return {valid:true}
    }
    else {
      return {valid:false, message:'1人以上20人以内'}
    }
  }

  function budgetCheck(values) {
    if (values.budget >= 0) {
      return {valid:true}
    }
    else {
      return {valid:false, message:'0円以上'}
    }
  }

  $(document).ready(function(){
    $('#start').valid8({
      'jsFunctions': [
        { function: datetimeCheck,
          values: function(){
            return {datetime:$('#start').val()}
          }
        }
      ]
    });

    $('#finish').valid8({
      'jsFunctions': [
        { function: datetimeCheck,
          values: function(){
            return {datetime:$('#finish').val()}
          }
        }
      ]
    });

    $('#numofpeople').valid8({
      'jsFunctions': [
        { function: peopleCheck,
          values: function(){
            return {people:$('#numofpeople').val()}
          }
        }
      ]
    });

    $('#userbudget').valid8({
      'jsFunctions': [
        { function: budgetCheck,
          values: function(){
            return {budget:$('#userbudget').val()}
          }
        }
      ]
    });
  });

  function formCheck() {
    tmp = [
      datetimeCheck({datetime:$('#start').val()}),
      datetimeCheck({datetime:$('#finish').val()}),
      peopleCheck({people:$('#numofpeople').val()}),
      budgetCheck({budget:$('#userbudget').val()})
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

<!-- ナビゲーション部分 -->
<!-- TODO:選択されているページ部分の色を変更する-->
<!-- Static navbar -->
<div class="navbar navbar-default" role="navigation">
    <div class="navbar-header">
      <button type="button" class="navbar-toggle"
      data-toggle="collapse" data-target=".navbar-collapse">
      <span class="sr-only">Toggle navigation</span>
      <span class="icon-bar"></span>
      <span class="icon-bar"></span>
      </button>
    </div>
    <!-- ナビゲーションバー -->
    <div class="navbar-collapse collapse">
      <ul class="nav navbar-nav">
      <li ><a href="dashboard.html" class="nav-href" data-toggle="tooltip" data-placement="bottom"  title="Dashboard">
             <i class="fa fa-home"></i>
             <span class="navname">Dashboard</span>
             <div class="tooltip">Dashboard</div>
      </a></li>
      <li class="active"><a href="form.php" class="nav-href" data-toggle="tooltip" data-placement="bottom"  title="Form">
             <i class="fa fa-pencil-square"></i>
             <span class="navname">Form</span>
             <div class="tooltip"><span>Form</span></div>
      </a></li>
      <li><a href="questionnaire.php" class="nav-href" data-toggle="tooltip" data-placement="bottom"  title="Questionnaire">
             <i class="fa fa-dot-circle-o"></i>
             <span class="navname">Questionnaire</span>
             <div class="tooltip"><span>Questionnaire</span></div>
      </a></li>
      <li><a href="spotlist.php" class="nav-href" data-toggle="tooltip" data-placement="bottom"  title="Spot List">
             <i class="fa fa-list-alt"></i>
             <span class="navname">Spot List</span>
             <div class="tooltip"><span>Spot List</span></div>
      </a></li>
      <li><a href="timeline.php" class="nav-href" data-toggle="tooltip" data-placement="bottom"  title="Timeline">
             <i class="fa fa-calendar"></i>
             <span class="navname">Timeline</span>
             <div class="tooltip"><span>Timeline</span></div>
      </a></li>
      </ul>
    </div><!--/.nav-collapse -->
  </div>
</div>
<!-- ここまでナビゲーション部分 -->

  <!--　フォーム群  -->
  <div class="header">
    <!--
    フォーム画面
    <div class="line"></div>
    -->
    <span>Please fill forms</span>
  </div>

  <div class="container">
  <!-- アクション先phpファイルを入れる-->
  <!-- requied属性およびpattern属性で入力値チェック -->
  <form class="forms" action="" method="post">
  <fieldset>
    <ul>
      <br>

      <li class="date">
        <label class="forms" for="datetime" style="text-decoration:underline;">StartDatetime</label>
        <input type="datetime-local" name="start" id="start" value="<?=h($_POST['start'] ?: (string)date("Y-m-d\TH:i", strtotime("+1 hour")))?>" required>
        <span id="startValidationMessage" class="validationMessage"></span>
      </li>
      <li class="date">
        <label class="forms" for="datetime" style="text-decoration:underline;">FinishDatetime</label>
        <input type="datetime-local" name="finish" id="finish" value="<?=h($_POST['finish'] ?: (string)date("Y-m-d\TH:i", strtotime("+5 hour")))?>" required>
        <span id="finishValidationMessage" class="validationMessage"></span>
      </li>
      <br>

      <li class="numofpeople">
        <label class="forms" for="NumofPeople" max="15" min="0" style="text-decoration:underline;" >Number of People</label>
        <input type="number" name="people" id="numofpeople" placeholder="NumofPeople" value="<?=h($_POST['people'] ?: 1)?>" requied>
        <span id="numberofpeopleValidationMessage" class="validationMessage"></span>
      </li>
      <li class="userbudget">
        <label class="forms" for="UserBudget" max="100000" min="0" style="text-decoration:underline;">UserBudget
          <i style="text-decoration:none;font-size:80%;">(per person)</i></label>
        <input type="number" name="budget" id="userbudget" placeholder="UserBudget" value="<?=h($_POST['budget'] ?: 5000)?>" requied>
        <span id="userbudgetValidationMessage" class="validationMessage"></span>
      </li>
      <!--
      <li class="budget">
        <label class="forms" for="budget" max="100000" min="0" style="text-decoration:underline;" >Budget</label>
        <input type="number" id="budget" placeholder="Budget">
      </li>
      -->
      <!--
      <li class="date">
        <label class="forms"  for="date">Date</label>
        <input type="date" id="date" placeholder="Date">
      </li>
      -->
      <!--
          Datetime型の入力ボックスはOpera以外は未対応っぽい
          Dateだけでもよいかもしれない
          Ex:YYYY-MM-DDThh:mm:ssTZD
          YYYY:Year
          MM:Month
          DD:Day
          hh:Hour
          mm:min
          ss:sec
          TZD:TimeZone
       -->
    </ul>
    <br>
    <input type="hidden" name="token" value="<?=h($_SESSION['token'])?>">
    <input type="submit" class="btn btn-salmon" value="submit" onclick="return formCheck();" />

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

  </fieldset>
  </form>
  </div>

</body>

</html>
