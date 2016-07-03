# GoatSupporter
おでかけプラン生成のためのWebアプリケーション

http://opi.innovations-i.com/feature/idea/
にて発表

GoatSupporter は、以下のような画面から構成され る。以降に、各画面の機能および UI の概略を示す。詳 細な UI 設計および画面遷移については後の章で示す。
###登録画面
新規ユーザの登録を行う。登録済みの場合には表示しない。
###ログイン画面
GoatSupporter へのログイン処理を行う。ユーザIDとパスワードを必要とする。
###ダッシュボード画面
各画面へと移動するためのナビゲーションの役割を成す。
###フォーム画面
ユーザ情報を入力する。入力情報として以下がある。
+プラン開始時刻
+プラン終了時刻
+プラン利用人数
+一人当たりの予算額

###アンケート画面
カルーセルを用いてスワイプやキー操作によりプランの詳細を入力する。入力項目として以下がある。
+目的
+希望エリア
+希望する(メイン)スポットのタイプ

###スポットリスト画面
入力したユーザプロファイルに基づいてスポットの一覧が提示される。ユーザはリストよりスポットを洗濯してスポットリストに登録する。

###タイムライン画面
スポットリストが時系列純にタイムラインとして表示される。詳細情報の表示や時刻の修正が行える。スポットの削除もタイムラインより行う。

###フィードバック画面
GoatSupporterへのフィードバックを返す。
