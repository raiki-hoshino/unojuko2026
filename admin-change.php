<?php

require "config/database.php";
require "header.php";

echo "<!DOCTYPE html>
<html lang='ja'>
<head>
<meta charset='utf-8'>
<meta name='viewport' content='width=device-width, initial-scale=1, viewport-fit=cover'>
<title>座席データ閲覧</title>
<link rel='preconnect' href='https://fonts.googleapis.com'>
<link rel='preconnect' href='https://fonts.gstatic.com' crossorigin>
<link href='https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@400;500;700&display=swap' rel='stylesheet'>
<link rel='stylesheet' href='css/admin.css'>
<link rel='stylesheet' href='css/admin-data.css'>
</head>
<body>
<div class='layout'>

<nav class='side' aria-label='管理者メニュー'>
    <small>管理者メニュー</small>
    <a href='admin.php'>社員管理</a>
    <a href='admin-change.php'>座席変更</a>
    <a href='admin-data.php' aria-current='page'>座席データ閲覧</a>
    <hr>
    <a href='main.php' class='back'>座席図に戻る</a>
</nav>";

?>