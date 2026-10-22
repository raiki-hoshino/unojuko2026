<?php
 
require 'config/database.php';
 
if ($pdo) {
    echo 'データベース接続成功<br>';
    echo '接続先：' . $dbname;
} else {
    echo 'データベース接続失敗<br>';
    echo 'エラー内容：' . $errorMessage;
}