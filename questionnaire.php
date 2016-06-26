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

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
  // CSRF対策
  setToken();
  $pdo = connectDB();
  $sql = <<<EOS
SELECT
  AreaCode AS area,
  Mode AS mode,
  TargetCategory AS category
FROM Plans
WHERE PlanID = :planid
LIMIT 1
EOS;
  $stmt = $pdo->prepare($sql);
  $stmt->execute(array(':planid' => $_SESSION['planid']));
  $fetch = $stmt->fetch(PDO::FETCH_ASSOC);
  $area = $fetch['area'] ?: 0;
  $mode = $fetch['mode'] ?: 0;
  $category = $fetch['category'] ?: 0;
} else {
  $_POST = arrayString($_POST);

  checkToken();

  if (!isset($_POST['category'])) {
    $_POST['category'] = '0';
  }
  unset($_POST['token']);
  $_SESSION['quest'] = $_POST;
  unset($_POST);
  header('Location: '.SITE_URL.'registquest.php');
  exit;
}

?>

<!DOCTYPE html>

<html lang="en">
<head>
<meta charset="utf-8">
<title>Questionnaire - GoatSupporter</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="">
<meta name="author" content="">
<link id="callCss" rel="stylesheet" href="css/bootstrap.min.css" type="text/css" media="screen" charset="utf-8" />
<link id="callCss" rel="stylesheet" href=="//blueimp.github.io/Gallery/css/blueimp-gallery.min.css" type="text/css" media="screen" charset="utf-8" />
<link id="callCss" rel="stylesheet" href="css/bootstrap-image-gallery.min.css" type="text/css" media="screen" charset="utf-8" />
<link id="callCss"rel="stylesheet" href="css/style.css" type="text/css" media="screen" charset="utf-8" />
<link id="callCss"rel="stylesheet" href="css/questionnaire.css" type="text/css" media="screen" charset="utf-8" />

<!-- Using Font Awesome-->
<link rel="stylesheet" href="css/font-awesome.min.css">

<!-- ナビゲーション用の スタイル-->
<link rel="stylesheet" type="text/css" href="css/navigation.css">

</head>

<script src="//blueimp.github.io/Gallery/js/jquery.blueimp-gallery.min.js"></script>
<script src="js/bootstrap-image-gallery.min.js"></script>
<script src="js/lightbox.js"></script>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>
<script src="js/jquery-1.9.1.min.js"></script>
<script src="js/bootstrap.min.js" type="text/javascript"></script>
<script src="js/jquery.scrollTo-1.4.3.1-min.js" type="text/javascript"></script>
<script src="js/jquery.easing-1.3.min.js"></script>
<script src="js/default.js"></script>

<script type="text/javascript">

  $(document).ready(function() {
    $('#QCarousel').carousel({
      //interval: 7000
    });
  });

</script>


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

<!-- キーボードによるカルーセルコントロール(jwerty.js)-->
<script type="text/javascript" src="js/jwerty.js"></script>

<script>
jwerty.key('←', function(){
  $('#QCarousel').carousel('prev');
});
jwerty.key('→', function(){
  $('#QCarousel').carousel('next');
});
</script>

<!-- スワイプによるカルーセルコントロール(hammer.js)-->
<script type="text/javascript" src="js/hammer.min.js"></script>

<script>
$(function(){
  var targetcarousel = $('#QCarousel');
  var hammer = new Hammer(targetcarousel[0]);
  hammer.on('swipeleft', function(){
    targetcarousel.carousel('next');
  });
  hammer.on('swiperight', function(){
    targetcarousel.carousel('prev');
  });
});
</script>

<script type="text/javascript">
//breadcrumbのクリック
/*
$('a.breadcrumb').on('click',function(e){
  e.preventDefault();
  //上位ノードへのイベントの 伝播 を止めずにそのイベントをキャンセル
});
*/
/*
$(function(){
  $('a').click(function(){
    return false;
  });

});
*/
</script>

<script type="text/javascript">
//confirmに入力結果を表示
$(function(){
  $("input[value=confirm]").click(function(){
    var ans1 = $("input[type=radio][name=mode]:checked").parents('label').text();
    var ans2 = $("input[type=radio][name=area]:checked").parents('label').text();
    var ans3 = $("input[type=radio][name=category]:checked").parents('label').text();

    ans1 = "目的: " + ans1 ;
    ans2 = "場所: " + ans2 ;
    ans3 = "種類: " + (ans3!='' ? ans3 : '特になし') ;

    $("div.answer1").text(ans1);
    $("div.answer2").text(ans2);
    $("div.answer3").text(ans3);

  })
});
</script>

<script type="text/javascript">
//confirmに入力結果を表示
$(function(){
  $("input[value=confirm]").click(function(){
    $("#1").css("color", "blue");

  })
});
</script>



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
      <li><a href="form.php" class="nav-href" data-toggle="tooltip" data-placement="bottom" title="Form">
             <i class="fa fa-pencil-square"></i>
             <span class="navname">Form</span>
             <div class="tooltip"><span>Form</span></div>
      </a></li>
      <li class="active"><a href="questionnaire.php" class="nav-href" data-toggle="tooltip" data-placement="bottom"  title="Questionnaire">
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
<!-- ここまでナビゲーション部分 -->
<div class="header">
    <!--
    <div class="line"></div>
    アンケート画面
    <div class="line"></div>
    -->
    <!--
    <div class="breadcrumb">
          <a href= "#q1" class="breadcrumb" target = "_top" data-toggle="bread" class="active" >Budget</a>
        -><a href= "#q2" class="breadcrumb" target = "_top" data-toggle="bread" >Meal</a>
        -><a href= "#q3" class="breadcrumb" target = "_top" data-toggle="bread" >Area</a>
        -><a href= "#q4" class="breadcrumb" target = "_top" data-toggle="bread" >Spot Type</a>
        -><a href= "#confirm" class="breadcrumb" target = "_top" data-toggle="bread" >Confirm</a>
        <br>
    </div>
  -->
</div>

      <!-- ターゲットのディレクトリはDBの部分できたら追加してください -->

<div class="container">
  <div class="row">
    <div class="col-xs-12"><h1 class="cntr">Questionnaire</h1>
      <div class="line"></div>
      <form id="questionnaire" method="post" ACTION="">

        <div id="carouselQSection" class="cntr">
          <div id="QCarousel" class="carousel slide" data-ride="carousel">

            <ol class="carousel-indicators">
              <li data-target="#QCarousel" data-slide-to="0" class="active"></li>
              <li data-target="#QCarousel" data-slide-to="1"></li>
              <li data-target="#QCarousel" data-slide-to="2"></li>
              <li data-target="#QCarousel" data-slide-to="3"></li>
            </ol>

            <div class="carousel-inner">
              <div class="item active" id="q1">
                <div class="question">
                  お出かけの目的は何ですか?<br>
                </div>
                <ul class="nav nav-pills nav-stacked">
                  <li role = "presantation" class="choices">
                    <label for "a1-1"><input name="mode" type="radio" id="a1-1" value="1" <?=$mode==1 ? 'checked':''?>>
                    デート</label></li>
                  <li role = "presantation" class="choices">
                    <label for "a1-2"><input name="mode" type="radio" id="a1-2" value="2" <?=$mode==2 ? 'checked':''?>>
                    女子会</label></li>
                  <li role = "presantation" class="choices">
                    <label for "a1-3"><input name="mode" type="radio" id="a1-3" value="3" <?=$mode==3 ? 'checked':''?>>
                    家族旅行</label></li>
                  <li role = "presantation" class="choices">
                    <label for "a1-3"><input name="mode" type="radio" id="a1-4" value="4" <?=$mode==4 ? 'checked':''?>>
                    散歩</label></li>
                </ul>
              </div>

              <div class="item" id = "q3">
                <div class="question">
                  特に行きたいエリアはどこですか?<br>
                </div>
                <ul class="nav nav-pills nav-stacked">
                  <li role = "presantation" class="choices">
                    <label for "a2-1"><input name="area" type="radio" id="a2-1" value="1" <?=$area==1 ? 'checked':''?>>
                    渋谷・恵比寿・代官山エリア</label></li>
                  <li role = "presantation" class="choices">
                    <label for "a2-2"><input name="area" type="radio" id="a2-2" value="2" <?=$area==2 ? 'checked':''?>>
                    お台場・銀座エリア</label></li>
                  <li role = "presantation" class="choices">
                    <label for "a2-3"><input name="area" type="radio" id="a2-3" value="3" <?=$area==3 ? 'checked':''?>>
                    新宿・池袋エリア</label></li>
                  <li role = "presantation" class="choices">
                    <label for "a2-4"><input name="area" type="radio" id="a2-4" value="4" <?=$area==4 ? 'checked':''?>>
                    品川・六本木エリア</label></li>
                  <li role = "presantation" class="choices">
                    <label for "a2-5"><input name="area" type="radio" id="a2-5" value="5" <?=$area==5 ? 'checked':''?>>
                    下町エリア</label></li>
                </ul>
              </div>

              <div class="item" id = "q4">
                <div class="question">
                  特に行きたい場所のタイプは?<br>
                </div>
                <ul class="nav nav-pills nav-stacked" >
                  <li role = "presantation" class="choices">
                    <label for "a3-1"><input name="category" type="radio" id="a3-1" value="1" <?=$category==1 ? 'checked':''?>>
                    アミューズメント</label></li>
                  <li role = "presantation" class="choices">
                    <label for "a3-2"><input name="category" type="radio" id="a3-2" value="2" <?=$category==2 ? 'checked':''?>>
                    イベント</label></li>
                  <li role = "presantation" class="choices">
                    <label for "a3-3"><input name="category" type="radio" id="a3-3" value="5" <?=$category==5 ? 'checked':''?>>
                    アウトドア&amp;レクリエーション</label></li>
                  <li role = "presantation" class="choices">
                    <label for "a3-4"><input name="category" type="radio" id="a3-4" value="6" <?=$category==6 ? 'checked':''?>>
                    ショッピング</label></li>
                </ul>
              </div>

              <div class="item" id = "confirm">
                <ul class="nav nav-pills nav-stacked" id="confirm" >
                  <li class = "confirm">
                    <div class = "answer1"></div>
                    <div class = "answer2"></div>
                    <div class = "answer3"></div>
                  </li>
                  <li class = "confirm">
                    <!-- Submit Data to Database-->
                    <button type="submit" class="btn btn-salmon">Submit</button>
                    <!-- Confirm Input Data-->
                    <input type="button"  class="btn btn-salmon" value="confirm" />
                    <br>
                    <br>
                  </li>
                </ul>
              </div>
            </div>

            <!--
            <ol class="carousel-indicators">
                <li data-target="#QCarousel" data-slide-to="0" class="active"></li>
                <li data-target="#QCarousel" data-slide-to="1"></li>
                <li data-target="#QCarousel" data-slide-to="2"></li>
                <li data-target="#QCarousel" data-slide-to="3"></li>
                <li data-target="#QCarousel" data-slide-to="4"></li>
            </ol>
            -->
            <!--
              <a class="left carousel-control" href="#carouselScSection" role="button" data-slide="prev">
                <span class="glyphicon glyphicon-chevron-left" aria-hidden="true"></span>
                <span class="sr-only">Previous</span>
              </a>
              <a class="right carousel-control" href="#carouselScSection" role="button" data-slide="next">
                <span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span>
                <span class="sr-only">Next</span>
              </a>
              -->
          </div>

        <a class="carousel-control left" href="#QCarousel" data-slide="prev">&lsaquo;</a>
        <a class="carousel-control right" href="#QCarousel" data-slide="next">&rsaquo;</a>

        <input type="hidden" name="token" value="<?=h($_SESSION['token'])?>">
      </form>
    </div>
  </div>
</div>

</body>
</html>
