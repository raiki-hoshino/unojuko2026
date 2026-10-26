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
    <nav class="floor-nav" aria-label="階を選択">
      <button class="floor-button" type="button" data-floor="3" aria-pressed="false">3階</button>
      <button class="floor-button" type="button" data-floor="2" aria-pressed="false">2階</button>
      <button class="floor-button" type="button" data-floor="1" aria-pressed="true">1階</button>
    </nav>

    <section class="main-panel" aria-labelledby="floor-heading">
      <h2 class="panel-heading" id="floor-heading">1階</h2>

      <section class="current-section" aria-labelledby="current-heading">
        <h3 class="current-heading" id="current-heading">座席表</h3>
        <div class="image-preview" id="image-preview" aria-live="polite">
          座席画像が登録されていません
        </div>
      </section>
    </section>
  </main>

  <script>
    const floorButtons = [...document.querySelectorAll(".floor-button")];
    const floorHeading = document.querySelector("#floor-heading");
    const imagePreview = document.querySelector("#image-preview");
    let selectedFloor = "1";

    // 管理画面側で localStorage に保存した画像を階ごとに表示します。
    // 保存キー例: seatMapImage_1 / seatMapImage_2 / seatMapImage_3
    function renderFloor() {
      floorHeading.textContent = `${selectedFloor}階`;
      floorButtons.forEach((button) => {
        button.setAttribute("aria-pressed", String(button.dataset.floor === selectedFloor));
      });

      imagePreview.replaceChildren();
      const savedImage = localStorage.getItem(`seatMapImage_${selectedFloor}`);

      if (savedImage) {
        const image = document.createElement("img");
        image.src = savedImage;
        image.alt = `${selectedFloor}階の座席表`;
        imagePreview.appendChild(image);
        imagePreview.classList.add("has-image");
      } else {
        imagePreview.textContent = "座席画像が登録されていません";
        imagePreview.classList.remove("has-image");
      }
    }

    floorButtons.forEach((button) => {
      button.addEventListener("click", () => {
        selectedFloor = button.dataset.floor;
        renderFloor();
      });
    });

    document.querySelector("#search-form").addEventListener("submit", (event) => {
      event.preventDefault();
      const query = document.querySelector("#name-search").value.trim();
      if (!query) {
        document.querySelector("#name-search").focus();
        return;
      }
      alert(`「${query}」の検索機能は、登録者データとの接続後に利用できます。`);
    });

    renderFloor();
  </script>
</body>
</html>

