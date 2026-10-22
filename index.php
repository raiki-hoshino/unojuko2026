<?php require "config/database.php" ?>
<?php session_start(); ?>

<form method="post">
    社員番号<input type="text" name="people_num">
    パスワード<input type="password" name="password">
    <input type="submit" value="ログイン">
</form>

<?php 
unset($_SESSION['people']);

$sql=$pdo->prepare('select * from people where people_num and password=?');
$sql->execute([$_REQUEST['people_num'], $_REQUEST['password']]);
foreach ($sql as $row) {
    $_SESSION['people']=[
        'people_id'=>$row['people_id'], 'people_num'=>$row['people_num'],
        'name'=>$row['name'], 'password'=>$row['password'],
        'mail'=>$row['mail']
    ];
}
if (isset($_SESSION['customer'])) {
    header('Location:main.php');
    exit;
} else{
    echo '失敗';
}

?>