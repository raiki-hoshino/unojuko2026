<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>座席管理</title>
<link rel="stylesheet" href="css/index.css">
<script src="js/index.js" defer></script>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>

<?php require "config/database.php" ?>
<?php session_start(); ?>

<?php
if ($pdo === null) {
    echo "データベースに接続できません。";
    exit;
}
$people_num=$name=$mail=$password='';
if (isset($_SESSION['people'])) {
    $people_num=$_SESSION['people']['people_num'];
    $name=$_SESSION['people']['name'];
    $mail=$_SESSION['people']['mail'];
    $password=$_SESSION['people']['password'];
}

$err = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $people_num    = trim(filter_input(INPUT_POST, 'new_people_num') ?? '');
    $name          = trim(filter_input(INPUT_POST, 'new_name') ?? '');
    $mail          = trim(filter_input(INPUT_POST, 'new_mail') ?? '');
    $password      = filter_input(INPUT_POST, 'new_password') ?? '';
    $password_conf = filter_input(INPUT_POST, 'new_password_conf') ?? '';
    
        if($people_num === ''){
            $err[] = '社員番号を入力してください';
        }

        if($name === ''){
            $err[] = 'ユーザ名を入力してください';
        }

        if($mail === ''){
            $err[] = 'メールアドレスを入力してください';
        } elseif (!filter_var($mail, FILTER_VALIDATE_EMAIL)) {
        $err[] = 'メールアドレスの形式が正しくありません';
        }

        if(!preg_match('/\A(?=.*?[a-z])(?=.*?[A-Z])(?=.*?\d)[a-zA-Z\d]{8,50}+\z/', $password)){
            $err[] = 'パスワードは半角数字８～５０文字にしてください';
        }

        if($password !== $password_conf){
            $err[] = 'パスワードが異なっています';
        }

    // エラーがなければ登録処理（後で追加）
    if (!empty($err)) {
        echo '<div class="err-login" id="err-new-login">';
        foreach ($err as $msg) {
            echo '<p>', htmlspecialchars($msg, ENT_QUOTES, 'UTF-8'), '</p>';
        }
        echo '</div>';
    }else if(empty($err)) {
        $sql = $pdo->prepare('select * from people where people_num=? or mail=?');
        $sql->execute([$people_num, $mail]);

        if($sql->fetch()) {
            echo '<div class="err-login" id="err-login">';
            echo '<p>このユーザー情報はすでに登録されています。</p>';
            echo '</div>';
        } else {
            // people_idを連番させる
            $next_id = (int)$pdo->query('SELECT COALESCE(MAX(people_id), 0) + 1 FROM people')->fetchColumn();
            // パスワードはハッシュ化して保存
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $sql = $pdo->prepare('insert into people values(?,?,?,?,?)');
            $sql->execute([$next_id, $people_num, $name, $hash, $mail]);

            echo '<div class="new-login"><p>お客様情報を登録しました。</p></div>';
            $people_num = $name = $mail = $password = $password_conf = ''; // 登録後は入力欄を空に
        }
    }
}


echo '<div class="login-card">';
echo '<h1>新規登録</h1>';
echo '<p class="login-lead">社員情報を入力してください。</p>';

echo '<form method="post">';
echo '<div class="form-group">';
echo '<label for="new_employee">社員番号</label>';
echo '<input type="text" name="new_people_num" placeholder="例：P0001" value="', $people_num, '">';
echo '</div>';

echo '<div class="form-group">';
echo '<label for="new_name">姓・名</label>';
echo '<input type="text" id="new_name" name="new_name" placeholder="例：宇野重太" value="', $name, '">';
echo '</div>';

echo '<div class="form-group">';
echo '<label for="new_mail">メールアドレス</label>';
echo '<input type="email" id="new_mail" name="new_mail" value="', $mail, '">';
echo '</div>';

echo '<div class="form-group">';
echo '<label for="new_password">パスワード</label>';
echo '<div class="password-wrap">';
echo '<input type="password" class="password-input" id="new_password" name="new_password" value="', $password, '">';
echo '<img class="eye-close" src="image/icons8-invisible-48.png">';
echo '<img class="eye-open" src="image/icons8-目に見える-48.png" style="display: none;">';
echo '</div>';
echo '<p class="form-hint">・半角の大文字・小文字・数字を含めてください<br>・8文字以上50文字以下で入力してください</p>';
echo '</div>';

echo '<div class="form-group">';
echo '<label for="password_conf">パスワード（確認用）</label>';
echo '<div class="password-wrap">';
echo '<input type="password" class="password-input" id="password_conf" name="new_password_conf">';
echo '<img class="eye-close" src="image/icons8-invisible-48.png">';
echo '<img class="eye-open" src="image/icons8-目に見える-48.png" style="display: none;">';
echo '</div>';
echo '</div>';

echo '<input type="submit" class="login-button" value="登録">';
echo '</form>';

echo '<p class="back-link"><a href="index.php">← ログイン画面に戻る</a></p>';
echo '</div>';
echo '<p class="login-footer">社内専用 座席管理システム</p>';
echo '</div>';

?>


</body>
</html>