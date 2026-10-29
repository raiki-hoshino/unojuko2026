<?php require "config/database.php" ?>
<?php require "header.php" ?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <link rel="stylesheet" href="css/main.css">
  <title>座席登録 - 座席画像管理</title>

</head>
<body>

  <main class="layout">
  <aside class="side-column">
    <nav class="floor-nav" aria-label="階を選択">
      <button class="floor-button" type="button" data-floor="3" aria-pressed="false">3階</button>
      <button class="floor-button" type="button" data-floor="2" aria-pressed="false">2階</button>
      <button class="floor-button" type="button" data-floor="1" aria-pressed="true">1階</button>
    </nav>

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

