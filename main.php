<?php require "config/database.php" ?>
<?php require "header.php" ?>
<?php
function floorImageUrl(int $floor): string
{
    foreach (['jpg', 'png', 'gif', 'webp'] as $ext) {
        $path = __DIR__ . '/uploads/floor' . $floor . '-image.' . $ext;
        if (is_file($path)) {
            return 'uploads/floor' . $floor . '-image.' . $ext . '?v=' . filemtime($path);
        }
    }
    return '';
}

$floor = (int)($_GET['floor'] ?? 1);
if (!in_array($floor, [1, 2, 3], true)) {
    $floor = 1;
}
$imageUrl = floorImageUrl($floor);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="css/main.css">
  <title>座席表</title>
</head>
<body>
  <main class="layout">
    <!-- 左: 階の選択 -->
    <nav class="floor-nav" aria-label="階を選択">
      <?php foreach ([3, 2, 1] as $n): ?>
        <a class="floor-button" href="main.php?floor=<?= $n ?>"
          aria-pressed="<?= $n === $floor ? 'true' : 'false' ?>"><?= $n ?>階</a>
      <?php endforeach; ?>
    </nav>

    <!-- 中央: 座席表 -->
    <section class="main-panel" aria-label="<?= $floor ?>階の座席表">
      <div class="image-preview<?= $imageUrl ? ' has-image' : '' ?>">
        <?php if ($imageUrl): ?>
          <img src="<?= htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8') ?>"
              alt="<?= $floor ?>階の座席図">
        <?php else: ?>
          <p><?= $floor ?>階の画像が登録されていません。</p>
        <?php endif; ?>
      </div>
    </section>

    <!-- 右: 外出者一覧 -->
    <aside class="away-panel" aria-labelledby="away-heading">
      <h3 class="away-heading" id="away-heading">外出者一覧</h3>
      <ul class="away-list" id="away-list">
        <li class="away-empty">外出中の人はいません</li>
      </ul>
    </aside>
  </main>
</body>
</html>