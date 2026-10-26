<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>座席管理</title>
<link rel="stylesheet" href="css/index.css">
</head>

<?php require "config/database.php" ?>
<?php require "header.php" ?>
<?php session_start(); ?>

<div class="box">
<h1>ログイン</h1>

<form method="post">
    <div class="form_text"><p>社員番号<br><input type="text" name="people_num"><br></p></div>
    <div class="form_text"><p>パスワード<br><input type="password" name="password"><br></p></div>
    <input type="submit" value="ログイン">
</form>

<div class="reset"><p><a href="reset_email.php">パスワードを忘れた方</a></p></div>
<div class="new"><p>初めて利用する方<a href="new.php">新規登録</a></p></div>

</div>

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