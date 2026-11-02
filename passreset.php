<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>座席管理</title>
<link rel="stylesheet" href="css/index.css">
</head>
<body>

<?php require "config/database.php" ?>
<?php session_start(); ?>

<div class="login-card">
  <h1>パスワード再設定</h1>
  <p class="login-lead">新しいパスワードを入力してください。</p>

  <form method="post">
    <div class="form-group">
      <label for="password">パスワード</label>
      <div class="password-wrap">
      <input type="password" id="password" name="password">
      <img class="eye-close" src="image/icons8-invisible-48.png">
      <img class="eye-open" src="image/icons8-目に見える-48.png">
    </div>
    </div>
    <div class="form-group">
      <label for="password">パスワード（確認用）</label>
      <div class="password-wrap">
      <input type="password" id="password-conf" name="password-conf">
      <img class="eye-close" src="image/icons8-invisible-48.png">
      <img class="eye-open" src="image/icons8-目に見える-48.png">
    </div>
    </div>

    <input type="submit" class="login-button" value="送信">
  </form>

  <p class="back-link"><a href="reset-code.php">← 前画面に戻る</a></p>
</div>
<p class="login-footer">社内専用 座席管理システム</p>

<?php 

?>

</body>
</html>