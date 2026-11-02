<?php
//データベース
require "config/database.php";

$uploadDir = __DIR__ . '/uploads';
$allowed = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/gif'  => 'gif',
    'image/webp' => 'webp',
];
$floors = [1 => '1階', 2 => '2階', 3 => '3階'];
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $f = $_FILES['image'] ?? null;
    $floor = (int)($_POST['floor'] ?? 0);

    if (!isset($floors[$floor])) {
        $message = '階を選択してください。';
    } elseif (!$f || $f['error'] !== UPLOAD_ERR_OK) {
        $message = 'アップロードに失敗しました。';
    } else {
        $info = @getimagesize($f['tmp_name']);
        $mime = $info['mime'] ?? '';
        if (!isset($allowed[$mime])) {
            $message = 'JPEG / PNG / GIF / WebP の画像を選んでください。';
        } else {
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $prefix  = $uploadDir . '/floor' . $floor . '-image';
            $newPath = $prefix . '.' . $allowed[$mime];
            $tmpPath = $prefix . '.new.' . $allowed[$mime];

            // 先に保存し、成功したらその階の古い画像だけを削除
            if (move_uploaded_file($f['tmp_name'], $tmpPath)) {
                foreach (glob($prefix . '.*') as $old) {
                    if ($old !== $tmpPath) {
                        unlink($old);
                    }
                }
                rename($tmpPath, $newPath);
                $message = $floors[$floor] . 'の画像を差し替えました。';
            } else {
                $message = '画像の保存に失敗しました。';
            }
        }
    }
}

require "header.php";
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>画像の差し替え</title>
<link rel="stylesheet" href="css/admin.css">
<link rel="stylesheet" href="css/admin-data.css">
</head>
<body>
<div class="layout">
<nav class="side" aria-label="管理者メニュー">
    <small>管理者メニュー</small>
    <a href="admin.php">社員管理</a>
    <a href="admin-change.php">座席変更</a>
    <a href="admin-data.php">座席データ閲覧</a>
    <hr>
    <a href="main.php" class="back">座席図に戻る</a>
</nav>
<main>
<h1>画像の差し替え</h1>
<?php if ($message): ?>
<p><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p>
<?php endif; ?>
<form method="post" enctype="multipart/form-data">
    <label>
        階:
        <select name="floor" required>
            <?php foreach ($floors as $n => $label): ?>
            <option value="<?= $n ?>"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <input type="file" name="image" accept="image/*" required>
    <button type="submit">差し替える</button>
</form>
</main>
</div>
</body>
</html>