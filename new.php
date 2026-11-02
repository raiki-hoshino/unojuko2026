<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>座席管理</title>
<link rel="stylesheet" href="css/new.css">
</head>
<body>

<?php require "config/database.php" ?>
<?php session_start(); ?>

<?php

if (!empty($_POST)) {
    $err = array();
    if(isset($_POST['people'])){
        $people_num = filter_input(INPUT_POST, 'people_num');
        if($people_num === ''){
            $err[] = '社員番号を入力してください';
        }

        $name = filter_input(INPUT_POST, 'name');
        if($name === ''){
            $err[] = 'ユーザ名を入力してください';
        }

        $mail = filter_input(INPUT_POST, 'mail');
        if($mail === ''){
            $err[] = 'メールアドレスを入力してください';
        }

        $password = filter_input(INPUT_POST, 'password');
        if(!preg_match("/\A[a-z\d]{8,50}+\z/i", $password)){
            $err[] = 'パスワードは英数字８文字以上５０文字以下にしてください。';
        }

        if("password" != "password_conf"){
            $err[] = 'パスワードが異なっています';
        }
    }

    if (!empty($err)) {
        foreach ($err as $msg) {
            echo '<div class="err_new">';
            echo '<p>', $msg,'</p><br>';
            echo '</div>';
        }
    }else if(count($err) === 0){
        $sql=$pdo->prepare('select * from people where people_num=?');
        $sql->execute([$_REQUEST['people_num']]);
        
        if (empty($sql->fetchAll())){ 
            if (isset($_SESSION['people'])) {
                $sql=$pdo->prepare('insert into people values(null,?,?,?,?)');
                $sql->execute([
                    $_REQUEST['people_id'], 
                    $_REQUEST['people_num'],$_REQUEST['name'], 
                    $_REQUEST['mail'], $_REQUEST['password']
                ]);
                // 2. データベースが自動で割り振った最新の数字（1, 2, 3... 11）をそのまま取得します
                // $people_id = $pdo->lastInsertId();
                echo '<div class="new-login">';
                echo '<p>お客様情報を登録しました。</p>';
                echo '</div>';
            }
        } else {
            echo '<div class="new-login" id="err-new-login">';
            echo '<p>このユーザー情報はすでに登録されています。</p>';
            echo '</div>';
        }
    }
}

$people_num=$name=$mail=$password='';

if (isset($_SESSION['people'])) {
    $people_num=$_SESSION['people']['people_num'];
    $name=$_SESSION['people']['name'];
    $mail=$_SESSION['people']['mail'];
    $password=$_SESSION['people']['password'];
}


echo '<div class="login-card">';
echo '<h1>新規登録</h1>';
echo '<p class="login-lead">社員情報を入力して、利用を開始しましょう。</p>';

echo '<div class="err-area">';
echo '</div>';

echo '<form method="post">';
echo '<div class="form-group">';
echo '<label for="new_employee">社員番号</label>';
echo '<input type="text" id="new_employee" name="people_num" placeholder="例：0001" value="', $people_num, '">';
echo '</div>';

echo '<div class="form-group">';
echo '<label for="new_name">姓・名</label>';
echo '<input type="text" id="new_name" name="name" placeholder="例：宇野　重太" value="', $name, '">';
echo '</div>';

echo '<div class="form-group">';
echo '<label for="new_mail">メールアドレス</label>';
echo '<input type="email" id="new_mail" name="mail" value="', $mail, '">';
echo '</div>';

echo '<div class="form-group">';
echo '<label for="new_password">パスワード</label>';
echo '<div class="password-wrap">';
echo '<input type="password" id="new_password" name="password" value="', $password, '">';
echo '<button type="button" class="toggle-password" aria-label="パスワードを表示"></button>';
echo '</div>';
echo '<p class="form-hint">半角英数字8文字以上で設定してください</p>';
echo '</div>';

echo '<div class="form-group">';
echo '<label for="password_conf">パスワード（確認用）</label>';
echo '<div class="password-wrap">';
echo '<input type="password" id="password_conf" name="password_conf">';
echo '<button type="button" class="toggle-password" aria-label="パスワードを表示"></button>';
echo '</div>';
echo '</div>';

echo '<input type="submit" class="new-button" value="登録">';
echo '</form>';

echo '<p class="back-link"><a href="index.php">← ログイン画面に戻る</a></p>';
echo '</div>';
echo '<p class="login-footer">社内専用 座席管理システム</p>';
echo '</div>';

?>


</body>
</html>