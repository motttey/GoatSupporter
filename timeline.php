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

if (!isset($_SESSION['planid'])) {
  header('Location: '.SITE_URL.'form.php');
  exit;
}

setToken();

?>

<!DOCTYPE html>
<html>
<head><title>Timeline - GoatSupporter</title>

<meta http-equiv="Content-Script-Type" content="text/javascript">
<meta charset = "utf-8">
<!--
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
-->

<!-- Using Codepen CSS3 Timeline:http://codepen.io/P233/pen/lGewF -->
<link rel="stylesheet" type="text/css" href="css/timeline.css">

<!--<link rel="stylesheet" type="text/css" href="css/modal.css">-->
<link rel="stylesheet" type="text/css" href="css/bootstrap.min.css">
<link rel="stylesheet" type="text/css" href="css/style.css">
<link rel="stylesheet" type="text/css" href="css/inlineliststyle.css">

<script type="text/javascript" src="js/jquery.min.js"></script>
<script type="text/javascript" src="js/jquery.tmpl.min.js"></script>
<script type="text/javascript" src="js/slick.min.js"></script>

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

<!-- Using Font Awesome-->
<link rel="stylesheet" href="css/font-awesome.min.css">

<!-- ナビゲーションの動的表示 -->
<!--
<script>
  $(
    function() {
    $("#naviwrapper").hover(function() {
      $("#navigation").show();
      // $(this).fadeTo("normal", 1);
    },
    function() {
      $("#navigation").hide();
      // $(this).fadeTo("normal", 0);
    });
  });
</script>
-->

<!-- For modal.js-->
<script type="text/javascript" src="js/modal.js"></script>
<script>
  $('#myModal').on('shown.bs.modal', function () {
    $('#myInput').focus()
  })
</script>

<!-- スポットリスト表示 -->
<script>
  // ページロード時
  $(window).load(function() {
    $.get("timeline_template.html", function(tmpl) { $template = tmpl; });
    loadText(null, -1);
  });

  function timeFormat(timestr) {
    return timestr.split(':', 2).join(':');
  }

  function time2hour(timestr) {
    var tmp = timestr.split(':', 2);
    return parseInt(tmp[0], 10) + (Math.floor(tmp.length==2 ? parseInt(tmp[1], 10) / 6 : 0) / 10) + 'h';
  }

  function datetimeDecomp(datetimestr) {
    var tmp = datetimestr.split('T', 2);
    var date = tmp[0].split('-').join('/');
    var time = timeFormat(tmp[1]);
    return [date, time];
  }

  function loadText(schedule, modflag) {
    // PHPとの通信
    $.ajax({
      type: 'POST',
      url: 'timeline_ajax.php',
      data: {
        schedule: schedule,
        newschedule: (modflag>=0 ? $("#datetime_"+modflag).val() : null),
        token: "<?=$_SESSION['token']?>"
      },
      dataType: 'json'
    })
    .done(function(json){
      // スポットデータの取得
      listdata = json;

      if (modflag >= 0) {
        // ポップアップの非表示
        $('body').removeClass('modal-open');
        $('div.modal-backdrop.in').remove();
      }

      // 表示の更新
      $("#timeline").html($.tmpl($template, {data:listdata['spotdata'], budget:listdata['budget']}));
      $("#sumprice").html(listdata['sumprice']);
      $("#userbudget").html(listdata['budget']);
      $("#sumprice").css({'color' : listdata['sumprice'] > listdata['budget'] ? '#FF0000' : '#FFFFFF'});
    });
  }
</script>

</head>

<body>
<!-- ナビゲーション部分 -->
<!-- TODO:選択されているページ部分の色を変更する-->
<!-- Static navbar -->

<span id="naviwrapper">
  <div id="navigation" class="navbar navbar-default" role="navigation">
    <div class="navbar-header">
      <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
      <span class="sr-only">Toggle navigation</span>
      <span class="icon-bar"></span>
      <span class="icon-bar"></span>
      </button>
    </div>
    <!-- ナビゲーションバー -->
    <div class="navbar-collapse collapse">
      <ul class="nav navbar-nav">
        <li>
          <a href="dashboard.html" class="nav-href" data-toggle="tooltip" data-placement="bottom"  title="Dashboard">
            <i class="fa fa-home"></i>
            <span class="navname">Dashboard</span>
            <div class="tooltip">Dashboard</div>
          </a>
        </li>
        <li>
          <a href="form.php" class="nav-href" data-toggle="tooltip" data-placement="bottom" title="Form">
            <i class="fa fa-pencil-square"></i>
            <span class="navname">Form</span>
            <div class="tooltip"><span>Form</span></div>
          </a>
        </li>
        <li>
          <a href="questionnaire.php" class="nav-href" data-toggle="tooltip" data-placement="bottom" title="Questionnaire">
            <i class="fa fa-dot-circle-o"></i>
            <span class="navname">Questionnaire</span>
            <div class="tooltip"><span>Questionnaire</span></div>
          </a>
        </li>
        <li>
          <a href="spotlist.php" class="nav-href" data-toggle="tooltip" data-placement="bottom" title="Spot List">
            <i class="fa fa-list-alt"></i>
            <span class="navname">Spot List</span>
            <div class="tooltip"><span>Spot List</span></div>
          </a>
        </li>
        <li class="active">
          <a href="spotlist.php" class="nav-href" data-toggle="tooltip" data-placement="bottom" title="Timeline">
            <i class="fa fa-calendar"></i>
            <span class="navname">Timeline</span>
            <div class="tooltip"><span>Timeline</span></div>
          </a>
        </li>
      </ul>
    </div><!--/.nav-collapse -->
  </div>
  <!-- ここまでナビゲーション部分 -->

  <!-- ヘッダ -->
  <div class="header">
    <!--
    リスト画面
    <div class="line"></div>
    -->
    <!-- 残り予算額が入る -->
    <div class="budget">
      <span class="budgetval"><i class="fa fa-jpy"></i>予算額: <span id="sumprice"></span> / <span id="userbudget"></span></span>
    </div>

    <div class="line">
    </div>

    <!--フィードバック機能へ遷移するボタン-->
    <div class="feedback">
      <button class="btn btm-sm btn-search" onclick="location.href='feedback.html'" >Feedback</button>
    </div>

    <!-- 検索ボックス -->
    <!--
    <div class="searchbox">
    <form class="form-search">
    <div class="navbar-search">
      <div class="input-append">
    -->
        <!-- 必要なクエリ分だけフォームを追加 -->
        <!--
        <span class="add-on"><i class="fa fa-search"></i></span>
        <input type="text" name ="search_query" class="input-medium search-query" placeholder="Search Query">
        <button type="submit" class="btn btm-sm btn-search" >Go </button>
      </div>
    </div>
    </form>
    </div>
    -->
  </div>
  <!-- ここまでヘッダ -->
</span>

<div class="container" id="timeline">
</div>

</body>
</html>
