<?php require "config/database.php" ?>
<?php require "header.php" ?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>座席登録 - 座席画像管理</title>
  <style>
    :root {
      --navy: #172b49;
      --blue: #45699f;
      --blue-hover: #355784;
      --border: #e0e5ec;
      --muted: #7b8492;
      --page: #f7f8fa;
      --surface: #ffffff;
      --selected: #edf3fb;
    }

    * { box-sizing: border-box; }

    body {
      margin: 0;
      background: var(--page);
      color: var(--navy);
      font-family: -apple-system, BlinkMacSystemFont, "Noto Sans JP",
        "Hiragino Kaku Gothic ProN", "Yu Gothic", Meiryo, sans-serif;
    }

    .header {
      display: flex;
      align-items: center;
      gap: clamp(20px, 4vw, 64px);
      min-height: 108px;
      margin: 16px 18px 0;
      padding: 22px clamp(20px, 3.5vw, 48px);
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 9px;
      box-shadow: 0 2px 8px rgb(23 43 73 / 4%);
    }

    .brand {
      flex: 0 0 auto;
      margin: 0;
      font-size: clamp(25px, 2.3vw, 34px);
      font-weight: 750;
      letter-spacing: .04em;
      white-space: nowrap;
    }

    .search-form {
      display: flex;
      gap: 12px;
      width: min(100%, 730px);
      margin: 0 auto;
    }

    .search-field {
      display: flex;
      align-items: center;
      flex: 1;
      min-width: 0;
      height: 58px;
      gap: 14px;
      padding: 0 20px;
      border: 1.5px solid #d9dfe8;
      border-radius: 8px;
      background: #fff;
    }

    .search-field:focus-within {
      border-color: var(--blue);
      box-shadow: 0 0 0 3px rgb(69 105 159 / 10%);
    }

    .search-field svg {
      width: 23px;
      height: 23px;
      flex: 0 0 auto;
      color: #737d8b;
    }

    .search-field input {
      width: 100%;
      min-width: 0;
      border: 0;
      outline: 0;
      background: transparent;
      color: var(--navy);
      font: inherit;
      font-size: 18px;
    }

    .search-field input::placeholder { color: #8b93a0; }

    .search-button {
      min-width: 104px;
      padding: 0 22px;
      border: 0;
      border-radius: 8px;
      background: var(--blue);
      color: white;
      font: inherit;
      font-weight: 600;
      font-size: 18px;
      cursor: pointer;
    }

    .search-button:hover { background: var(--blue-hover); }

    .layout {
      display: grid;
      grid-template-columns: 180px minmax(0, 1fr);
      gap: 18px;
      min-height: calc(100vh - 150px);
      margin: 28px 18px 18px;
    }

    .floor-nav, .main-panel {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 10px;
    }

    .floor-nav {
      align-self: stretch;
      padding: 6px;
    }

    .floor-button {
      display: block;
      width: 100%;
      min-height: 68px;
      border: 1px solid var(--border);
      background: #fff;
      color: var(--navy);
      font: inherit;
      font-size: 18px;
      cursor: pointer;
      transition: background 140ms ease, color 140ms ease;
    }

    .floor-button:first-child { border-radius: 8px 8px 0 0; }
    .floor-button:last-child { border-radius: 0 0 8px 8px; }
    .floor-button + .floor-button { margin-top: -1px; }

    .floor-button:hover { background: #f5f8fc; }

    .floor-button[aria-pressed="true"] {
      position: relative;
      z-index: 1;
      background: var(--selected);
      color: var(--blue);
      border-color: #c8d6e9;
      font-weight: 700;
    }

    .main-panel {
      min-width: 0;
      padding: clamp(22px, 3vw, 36px);
    }

    .panel-heading {
      margin: 0 0 26px;
      font-size: 25px;
      font-weight: 750;
    }

    .panel-content {
      display: grid;
      grid-template-columns: minmax(0, 4fr) minmax(190px, 1fr);
      gap: 24px;
      align-items: start;
    }

    .upload-area {
      grid-column: 2;
      grid-row: 1;
      display: flex;
      min-height: 260px;
      align-items: center;
      justify-content: center;
      flex-direction: column;
      gap: 18px;
      padding: 28px;
      border: 2px dashed #c7ced9;
      border-radius: 10px;
      background: #fbfcfe;
      text-align: center;
      cursor: pointer;
      transition: border-color 150ms ease, background 150ms ease;
    }

    .upload-area:hover, .upload-area.dragging {
      border-color: var(--blue);
      background: #f3f7fd;
    }

    .upload-icon {
      width: 68px;
      height: 68px;
      color: #929aa6;
    }

    .upload-title {
      margin: 0;
      font-size: 18px;
      font-weight: 650;
    }

    .upload-help {
      margin: 0;
      color: var(--muted);
      font-size: 15px;
    }

    .choose-button {
      margin-top: 8px;
      padding: 11px 20px;
      border: 1px solid #cbd2dd;
      border-radius: 7px;
      background: #fff;
      color: var(--navy);
      font: inherit;
      cursor: pointer;
    }

    .choose-button:hover { background: #f1f5fa; }

    .current-section {
      min-width: 0;
      margin-top: 0;
      text-align: center;
    }

    .current-heading {
      margin: 0 0 12px;
      font-size: 18px;
      font-weight: 700;
    }

    .image-preview {
      display: flex;
      min-height: min(68vh, 720px);
      align-items: center;
      justify-content: center;
      overflow: hidden;
      padding: 16px;
      border: 1px solid var(--border);
      border-radius: 9px;
      background: #fff;
      color: var(--muted);
      text-align: center;
    }

    .image-preview img {
      display: block;
      width: 100%;
      max-height: 72vh;
      object-fit: contain;
    }

    .image-preview.has-image {
      display: block;
      padding: 12px;
    }

    .image-actions {
      display: flex;
      justify-content: flex-end;
      margin-top: 10px;
    }

    .remove-button {
      padding: 8px 12px;
      border: 1px solid #d9dee7;
      border-radius: 6px;
      background: white;
      color: #596579;
      font: inherit;
      cursor: pointer;
    }

    .remove-button:hover { background: #f7f8fa; }

    .visually-hidden {
      position: absolute;
      width: 1px;
      height: 1px;
      padding: 0;
      margin: -1px;
      overflow: hidden;
      clip: rect(0, 0, 0, 0);
      white-space: nowrap;
      border: 0;
    }

    @media (max-width: 720px) {
      .header {
        align-items: stretch;
        flex-direction: column;
        gap: 18px;
        margin: 10px 10px 0;
        padding: 20px;
      }

      .search-form { width: 100%; gap: 8px; }
      .search-field { height: 50px; padding: 0 12px; }
      .search-field input, .search-button { font-size: 16px; }
      .search-button { min-width: 74px; padding: 0 14px; }

      .layout {
        grid-template-columns: 1fr;
        gap: 12px;
        margin: 16px 10px 10px;
      }

      .floor-nav {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
      }

      .floor-button {
        min-height: 48px;
        border-radius: 7px !important;
        margin: 0 !important;
      }

      .floor-button + .floor-button { margin-left: -1px !important; }
      .main-panel { padding: 20px 14px; }
      .panel-heading { font-size: 22px; margin-bottom: 18px; }
      .upload-area { min-height: 270px; padding: 20px 12px; }
      .upload-title { font-size: 16px; }
      .upload-help { font-size: 13px; }
    }
  </style>
</head>
<body>
  <!-- <header class="header">
    <h1 class="brand">座席登録</h1>
    <form class="search-form" id="search-form" role="search">
      <label class="search-field">
        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
          <circle cx="10.8" cy="10.8" r="6.8" stroke="currentColor" stroke-width="2.2"></circle>
          <path d="m16 16 5 5" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"></path>
        </svg>
        <input id="name-search" type="search" placeholder="名前で検索" aria-label="名前で検索">
      </label>
      <button class="search-button" type="submit">検索</button>
    </form>
  </header> -->

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

