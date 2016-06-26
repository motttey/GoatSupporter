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
<head><title>Spot List - GoatSupporter</title>

<meta http-equiv="Content-Script-Type" content="text/javascript">
<meta charset = "utf-8">
<!--
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
-->

<!--<link rel="stylesheet" type="text/css" href="css/modal.css">-->
<link rel="stylesheet" type="text/css" href="css/bootstrap.min.css">
<link rel="stylesheet" type="text/css" href="css/style.css">
<link rel="stylesheet" type="text/css" href="css/spotlist.css">
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
<script type="text/javascript">
  $('#myModal').on('shown.bs.modal', function () {
    $('#myInput').focus()
  })
</script>

<!-- スポットリスト表示 -->
<script>
  // ページロード時
  $(window).load(function() {
    $.get("spotlist_template.html", function(tmpl) {
      $template = tmpl;
      reloadText();
    });
    // loadText(0, 0);
  });

  function timeFormat(timestr) {
    return timestr.split(':', 2).join(':');
  }

  function time2hour(timestr) {
    var tmp = timestr.split(':', 2);
    return parseInt(tmp[0], 10) + (Math.floor(tmp.length==2 ? parseInt(tmp[1], 10) / 6 : 0) / 10) + 'h';
  }

  function reloadText() {
    // PHPとの通信
    $.ajax({
      type: 'POST',
      url: 'spotlistre_ajax.php',
      data: {
        token:  "<?=$_SESSION['token']?>"
      },
      dataType: 'json'
    })
    .done(function(json){
      // スポットデータの取得
      if (json['valid'] == 0) {
        listdata = [];
        selecteddata = [[0, 0, 0].toString()];
        loadText(0, 0);
      }
      else {
        listdata = json['data'];
        var sel = json['selected'];
        selecteddata = [];
        for (var i = 0; i < sel.length; i++) {
          selecteddata.push([sel[i]['spotid'], sel[i]['openid'], sel[i]['priceid']].toString());
        }
        // 表示の更新
        $('#spotlists').html($.tmpl($template, {selected:selecteddata, data:listdata, budget:listdata[listdata.length-1]['budget']}));
        $('#budget').css({'color':(listdata[listdata.length-1]['budget'] < 0 ? '#FF0000' : '#FFFFFF')});
        $('#budget').html(listdata[listdata.length-1]['budget']);
      }
    });
  }

  function loadText(spotno, index) {
    var select = spotno-1;
    if (spotno <= 0) {
      var spotid = null;
      var openid = null;
      var priceid = null;
      var schedule = null;
    }
    else {
      // フォーム値およびパラメータの取得
      var spotid = listdata[select]['spotdata'][index]['spotid'];
      var openid = listdata[select]['spotdata'][index]['openid'];
      var priceid = listdata[select]['spotdata'][index]['priceid'];
      var schedule = document.getElementById("datetime_"+select+"_"+index).value;

      // 選択されたスポットリスト以降のスポットリストを削除
      if (listdata.length > spotno) {
        listdata = listdata.slice(0, spotno);
        selecteddata = selecteddata.slice(0, select);
      }

      selecteddata[select] = [spotid, openid, priceid].toString();

      // 選択されたスポットデータのソート
      var tmp = listdata[select]['spotdata'][index];
      listdata[select]['spotdata'].splice(index, 1);
      listdata[select]['spotdata'].unshift(tmp);
    }
    // PHPとの通信
    $.ajax({
      type: 'POST',
      url: 'spotlist_ajax.php',
      data: {
        spotno:   spotno,
        spotid:   spotid,
        openid:   openid,
        priceid:  priceid,
        schedule: schedule,
        token:    "<?=$_SESSION['token']?>"
      },
      dataType: 'json'
    })
    .done(function(json){
      // スポットデータの取得
      listdata[spotno] = json;

      // ポップアップの非表示
      $('body').removeClass('modal-open');
      $('div.modal-backdrop.in').remove();

      // 表示の更新
      $('#spotlists').html($.tmpl($template, {selected:selecteddata, data:listdata, budget:listdata[listdata.length-1]['budget']}));
      $('#budget').css({'color':(listdata[listdata.length-1]['budget'] < 0 ? '#FF0000' : '#FFFFFF')});
      $('#budget').html(listdata[listdata.length-1]['budget']);
      listScroll(select);
    });
  }

  function modifyBudget() {
    // PHPとの通信
    $.ajax({
      type: 'POST',
      url: 'modbudget.php',
      data: {
        addbudget:   $('#addbudget').val(),
        token:       "<?=$_SESSION['token']?>"
      },
      async: false,
      dataType: 'json'
    })
    .done(function(json){
      // 予算の増加値取得
      var ab = json['addbudget'];

      // リスト更新
      for (var i = 0; i < listdata.length; i++) {
        listdata[i]['budget'] += ab;
      }

      // 表示の更新
      $('#spotlists').html($.tmpl($template, {selected:selecteddata, data:listdata, budget:listdata[listdata.length-1]['budget']}));
      $('#budget').css({'color':(listdata[listdata.length-1]['budget'] < 0 ? '#FF0000' : '#FFFFFF')});
      $('#budget').html(listdata[listdata.length-1]['budget']);
    });
  }
</script>

<!-- スクロール -->
<script>
  // ページリロード時
  $(window).load(function(){
    scroll = $("html, body");
    scroll.animate({scrollTop: 0, scrollLeft: 0}, '1');
  });

  function listScroll(select){
    var newlist = select + 1;
    var winwidth = $(window).width();
    var unitwidth = $("#list_"+newlist).width();
    var scrbarwidth = window.innerWidth - $(window).outerWidth(true);
    var nowX = (document.documentElement).scrollLeft || (document.body).scrollLeft;
    var newlistX = $("#list_"+newlist).offset().left;
    if (nowX + winwidth <= newlistX + unitwidth + scrbarwidth) {
      scroll.animate({ scrollTop: 0, scrollLeft: (newlistX + unitwidth + scrbarwidth*2 - winwidth) || 0 }, '1');
    }
    else if (nowX >= newlistX - unitwidth*2) {
      scroll.animate({ scrollTop: 0, scrollLeft: (newlistX + unitwidth + scrbarwidth*2 - winwidth) || 0 }, '1');
    }
    else {
      scroll.animate({ scrollTop: 0 }, '1');
    }
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
        <li class="active">
          <a href="spotlist.php" class="nav-href" data-toggle="tooltip" data-placement="bottom" title="Spot List">
            <i class="fa fa-list-alt"></i>
            <span class="navname">Spot List</span>
            <div class="tooltip"><span>Spot List</span></div>
          </a>
        </li>
        <li>
          <a href="timeline.php" class="nav-href" data-toggle="tooltip" data-placement="bottom" title="Timeline">
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
    <!--
    <div class="budget">
      <span class="budgetval"><i class="fa fa-jpy"></i>残り予算額: <span id="budget"></span></span>
    </div>
    -->

    <div class="budget">
      <span class="budgetlabel" style="text-align:left;"><i class="fa fa-jpy"></i>残り予算額: </span>
      <!-- ここに残り予算額が入る -->
      <span class="budgetval" id="budget" style="color:#FA8072;"></span>
      <form class="form-budgetpm" method="post" style="display:inline;text-align:left;" action="javascript: modifyBudget();">
        <!--
        <li class="pm" requied>
        + <input name="puls" type= "radio" id="puls" value = "0" checked>
        - <input name="minus" type= "radio" id="minus" value = "1">
        </li>
        -->
        <!--このフォームに正の値が入ったとき,DB内の予算属性がその分プラスされる
            負の値であればマイナス
        -->
        <label class="form-budgetpm" for="add-budget">＋</label>
        <input type="text" id="addbudget" name="add-budget" class="input-medium add-budget" placeholder="add budget">
        <button type="submit" class="btn btm-sm btn-search">Modify</button>
      </form>
    </div>

    <!--
    <div class="line">
    </div>
    -->
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

<!-- スポットリスト表示箇所 -->
<div class="wrapper" id="spotlists"></div>

</body>
</html>
