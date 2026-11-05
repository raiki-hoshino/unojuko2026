<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>座席管理</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="css/index.css">
<script src="js/index.js" defer></script>
</head>
<body>

<?php require "config/database.php" ?>
<?php session_start(); ?>

<?php
if ($pdo === null) {
    echo "データベースに接続できません。";
    exit;
}
if (!empty($_POST)) {
unset($_SESSION['people']);
$sql=$pdo->prepare('select * from people where people_num=?');
$sql->execute([$_REQUEST['people_num']]);

foreach ($sql as $row) {
  if (password_verify($_POST['password'], $row['password'])){
    $_SESSION['people']=[
        'people_id'=>$row['people_id'], 
        'people_num'=>$row['people_num'],
        'name'=>$row['name'],
        'mail'=>$row['mail']
    ];
  session_regenerate_id(true);
  }
}
// ハッシュ化されたパスワードのままログインができてしまってる

if (isset($_SESSION['people'])) {
    header('Location:main.php');
    exit;
} else{
    echo '<div class="err-login">';
    echo '<p>社員番号またはパスワードが間違っています</p>';
    echo '</div>';
}
}
?>

<div class="login-card">
  <h1>ログイン</h1>
  <p class="login-lead">社員番号とパスワードを入力してください。</p>

  <form class="form-wrap" method="post">
    <div class="form-group">
      <label for="employee">社員番号</label>
      <input type="text" id="employee" name="people_num">
    </div>

    <div class="form-group">
      <label for="password">パスワード</label>
      <div class="password-wrap">
        <input type="password" class="password-input" id="password" name="password">
        <!-- jsで表示 -->
        <img class="eye-close" src="image/icons8-invisible-48.png">
        <img class="eye-open" src="image/icons8-目に見える-48.png" style="display: none;">
      </div>
    </div>

    <input type="submit" class="login-button" value="ログイン">
  </form>

  <p class="forgot-link"><a href="reset-email.php">パスワードを忘れた方</a></p>
  <p class="register-link">初めて利用する方<a href="new.php">新規登録</a></p>
</div>
<p class="login-footer">社内専用 座席管理システム</p>


</body>
</html>