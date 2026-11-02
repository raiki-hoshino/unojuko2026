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
  <aside class="side-column">
    <nav class="floor-nav" aria-label="階を選択">
      <?php foreach ([3, 2, 1] as $n): ?>
        <a class="floor-button" href="main.php?floor=<?= $n ?>"
          aria-pressed="<?= $n === $floor ? 'true' : 'false' ?>"><?= $n ?>階</a>
      <?php endforeach; ?>
    </nav>

    <section class="main-panel" aria-labelledby="floor-heading">

      <section class="current-section" aria-labelledby="current-heading">
  <div class="image-preview<?= $imageUrl ? ' has-image' : '' ?>">
    <?php if ($imageUrl): ?>
      <img src="<?= htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8') ?>"
          alt="<?= $floor ?>階の座席図">
    <?php else: ?>
      <p><?= $floor ?>階の画像が登録されていません。</p>
    <?php endif; ?>
  </div>
</section>
    </section>
  </main>
    <section class="away-panel" aria-labelledby="away-heading">
      <h3 class="away-heading" id="away-heading">外出者一覧</h3>
      <ul class="away-list" id="away-list">
        <li class="away-empty">外出中の人はいません</li>
      </ul>
    </section>
  </aside>
  <section class="main-panel" aria-labelledby="floor-heading">
    <!-- <h2 class="panel-heading" id="floor-heading">1階</h2> -->

<div class="image-preview" id="image-preview">
  座席画像が登録されていません
</div>

  </section>
</main>

<script>
document.addEventListener("DOMContentLoaded", () => {
  const floorButtons = document.querySelectorAll(".floor-button");
  const imagePreview = document.getElementById("image-preview");

  let selectedFloor = "1";

  function renderFloor() {
    floorButtons.forEach((button) => {
      button.setAttribute(
        "aria-pressed",
        String(button.dataset.floor === selectedFloor)
      );
    });

    imagePreview.replaceChildren();

    const savedImage = localStorage.getItem(`seatMapImage_${selectedFloor}`);

    if (savedImage) {
      const image = document.createElement("img");
      image.src = savedImage;
      image.alt = `${selectedFloor}階の座席画像`;
      imagePreview.appendChild(image);
    } else {
      imagePreview.textContent = `${selectedFloor}階の座席画像が登録されていません`;
    }
  }

  floorButtons.forEach((button) => {
    button.addEventListener("click", () => {
      selectedFloor = button.dataset.floor;
      renderFloor();
    });
  });

  renderFloor();
});
</script>

</body>
</html>