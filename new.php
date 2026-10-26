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

<h1>新規登録</h1>

<?php
$people_num=$name=$mail=$password='';
if (isset($_SESSION['people'])) {
    $people_num=$_SESSION['people']['people_num'];
    $name=$_SESSION['people']['name'];
    $mail=$_SESSION['people']['mail'];
    $password=$_SESSION['people']['password'];
}

echo '<form method="post">';
echo '<p>社員番号<br><input type="text" name="new_employee" value="', $people_num,'"></p>';
echo '<p>姓・名<br><input type="text" name="new_name" value="', $name,'"></p>';
echo '<p>メールアドレス<br><input type="email" name="new_mail" value="', $mail,'"></p>';
echo '<p>パスワード<br><input type="password" name="new_password" value="', $password,'"></p>';
echo '<p>パスワード（確認用）<br><input type="password" name="password_conf"></p>';
echo '<input type="submit" value="登録">';
echo '</form>';

echo '<p><a href="index.php">← ログイン画面に戻る</a></p>';

// エラー
$err = [];

if(isset($_POST['people'])){
    $password = filter_input(INPUT_POST, 'new_employee');
    if($people_num == ''){
        $err[] = '社員番号を入力してください';
    }

    $password = filter_input(INPUT_POST, 'new_name');
    if($name == ''){
        $err[] = 'ユーザ名を入力してください';
    }

    $password = filter_input(INPUT_POST, 'new_mail');
    if($mail == ''){
        $err[] = 'メールアドレスを入力してください';
    }

    $password = filter_input(INPUT_POST, 'new_password');
    if(!preg_match("/\A[a-z\d]{8,50}+\z/i", $password)){
        $err[] = 'パスワードは英数字８文字以上１００文字以下にしてください。';
    }

    $password = filter_input(INPUT_POST, 'password_conf');
    if("new_password" != "password_conf"){
        $err[] = 'パスワードが異なっています';
    }
}

if (!empty($err)) {
    var_dump($err);
    foreach ($err as $msg) {
        echo '<p>', $msg,'</p><br>';
    }
    echo '</div>';
}else if(count($err) === 0){
    //ユーザを登録する処理
    if (isset($_SESSION['people'])) {
        $people_id=$_SESSION['people']['people_id'];
        $sql=$pdo->prepare('select * from people where people_num=?');
        $sql->execute([$id, $_REQUEST['login']]);
    } else {
        $sql=$pdo->prepare('select * from people where people_num=?');
        $sql->execute([$_REQUEST['people_num']]);
    }
    }

?>


</body>
</html>