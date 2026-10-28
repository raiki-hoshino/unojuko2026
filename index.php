<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>座席管理</title>
<link rel="stylesheet" href="css/index.css">
</head>

<?php require "config/database.php" ?>
<?php session_start(); ?>

<div class="login-card">
  <h1>ログイン</h1>
  <p class="login-lead">社員番号とパスワードを入力してください。</p>

  <form method="post">
    <div class="form-group">
      <label for="employee">社員番号</label>
      <input type="text" id="employee" name="employee" placeholder="例：1001">
    </div>

    <div class="form-group">
      <label for="password">パスワード</label>
      <div class="password-wrap">
        <input type="password" id="password" name="password" placeholder="パスワードを入力">
        <img class="eye-close" src="image/icons8-invisible-48.png">
        <img class="eye-open" src="image/icons8-目に見える-48.png">
      </div>
    </div>

    <input type="submit" class="login-button" value="ログイン">
  </form>

  <p class="forgot-link"><a href="reset-email.php">パスワードを忘れた方</a></p>
  <p class="register-link">初めて利用する方<a href="new.php">新規登録</a></p>
</div>
<p class="login-footer">社内専用 座席管理システム</p>

<?php 
unset($_SESSION['people']);
$sql=$pdo->prepare('select * from people where people_num=? and password=?');
$sql->execute([$_REQUEST['people_num'], $_REQUEST['password']]);
foreach ($sql as $row) {
    $_SESSION['people']=[
        'people_id'=>$row['people_id'], 'people_num'=>$row['people_num'],
        'name'=>$row['name'], 'password'=>$row['password'],
        'mail'=>$row['mail']
    ];
}

// ハッシュ化されたパスワードのままログインができてしまってる

if (isset($_SESSION['people'])) {
    header('Location:main.php');
    exit;
} else{
    echo '';
}
?>

</body>
</html>