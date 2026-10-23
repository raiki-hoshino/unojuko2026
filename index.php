<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>座席管理</title>
<link rel="stylesheet" href="css/style.css">
</head>
<body>

<?php require "config/database.php" ?>
<?php session_start(); ?>

<h1>ログイン画面</h1>

<form method="post">
    社員番号<br><input type="text" name="people_num"><p></p>
    パスワード<br><input type="password" name="password"><p></p><br><p></p>
    <input type="submit" value="ログイン">
</form>

<p></p><br><a href="reset_email.php">パスワードを忘れた方</a>
<p></p><br>
<p>初めて利用する方<a href="new.php">新規登録</a></p>

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
    echo '失敗';
}

?>
</body>
</html>