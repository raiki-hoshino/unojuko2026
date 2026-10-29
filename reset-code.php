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
  <p class="login-lead">メールに送信された認証コードを入力してください。</p>

  <form method="post">
    <div class="form-group">
      <label for="code">認証コード</label>
      <div class="password-wrap">
        <input type="password" id="code" name="code" placeholder="○○○○">
        <button type="button" class="toggle-password" aria-label="認証コードを表示"></button>
      </div>
    </div>

    <input type="submit" class="login-button" value="送信">
  </form>

  <p class="back-link"><a href="reset-email.php">← 前画面に戻る</a></p>
</div>
<p class="login-footer">社内専用 座席管理システム</p>

<?php 

?>

</body>
</html>