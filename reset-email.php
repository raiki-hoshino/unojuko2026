<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>座席管理</title>
<link rel="stylesheet" href="css/reset-email-code.css">
</head>
<body>

<?php require "config/database.php" ?>
<?php session_start(); ?>

<div class="login-card">
  <h1>パスワード再設定</h1>
  <p class="login-lead">メールアドレスを入力してください。</p>

  <form method="post">
    <div class="form-group">
      <label for="mail">メールアドレス</label>
      <input type="email" id="mail" name="mail">
    </div>

    <input type="submit" class="login-button" value="送信">
  </form>

  <p class="back-link"><a href="index.php">← ログイン画面に戻る</a></p>
</div>
<p class="login-footer">社内専用 座席管理システム</p>

<?php 

?>

</body>
</html>