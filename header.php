
<header class="site-header">
  <link rel="stylesheet" href="css/header.css">
  <h1 class="brand">宇野重工</h1>

  <form class="search-form" id="name-search" role="search">
    <label class="search-field">
      <svg class="search-icon" viewBox="0 0 24 24" fill="none"
           aria-hidden="true">
        <circle cx="10.8" cy="10.8" r="6.8"
                stroke="currentColor" stroke-width="2.2"></circle>
        <path d="m16 16 5 5" stroke="currentColor"
              stroke-width="2.2" stroke-linecap="round"></path>
      </svg>

      <input
        id="name-query"
        name="name"
        type="search"
        placeholder="名前で検索"
        aria-label="名前で検索"
      >
    </label>

    <button class="search-button" type="submit">検索</button>
  </form>

  <div class="header-actions">
    <button class="zaseki-button" type="button">座席画面</button>
    <button class="admin-button" type="button">管理者画面</button>

    <div class="header-divider"></div>

    <span class="user-name">山田 太郎 さん</span>

    <button
      class="logout-button"
      type="button"
      onclick="logout()"
    >
      ログアウト
    </button>
  </div>
</header>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>座席登録</title>
  <style>
    :root {
      --navy: #142947;
      --blue: #0c6b53;
      --blue-hover: #355784;
      --border: #d9dee7;
      --muted: #858d99;
      --background: #f7f8fa;
      --surface: #ffffff;
    }

    * {
      box-sizing: border-box;
    }

    body {
      margin: 0;
      font-family: -apple-system, BlinkMacSystemFont, "Noto Sans JP",
        "Hiragino Kaku Gothic ProN", "Yu Gothic", Meiryo, sans-serif;
      color: var(--navy);
      background: var(--background);
    }

    .site-header {
      display: flex;
      align-items: center;
      gap: clamp(24px, 5vw, 72px);
      min-height: 112px;
      padding: 24px clamp(20px, 4vw, 64px);
      background: var(--surface);
      border: 1px solid #e5e7eb;
      box-shadow: 0 2px 8px rgb(20 41 71 / 7%);
    }

    .brand {
      flex: 0 0 auto;
      margin: 0;
      color: var(--navy);
      font-size: clamp(24px, 2.5vw, 36px);
      font-weight: 750;
      letter-spacing: 0.04em;
      white-space: nowrap;
    }

    .search-form {
      display: flex;
      align-items: stretch;
      gap: 12px;
      width: min(100%, 1070px);
      margin-left: auto;
      margin-right: auto;
    }


    .admin-button {
      margin-right: clamp(0px, 8vw, 150px);
      flex: 0 0 auto;
      min-height: 48px;
      padding: 0 18px;
      border: 1px solid #cbd5e2;
      border-radius: 8px;
      background: #fff;
      color: var(--navy);
      font: inherit;
      font-size: 15px;
      font-weight: 600;
      white-space: nowrap;
      cursor: pointer;
      transition: background 150ms ease, border-color 150ms ease;
    }

    .admin-button:hover {
      background: #f1f5fa;
      border-color: #aebdd1;
    }

    .search-field {
      display: flex;
      align-items: center;
      flex: 1;
      min-width: 0;
      height: 60px;
      gap: 16px;
      padding: 0 20px 0 24px;
      background: #fff;
      border: 1.5px solid var(--border);
      border-radius: 10px;
      transition: border-color 160ms ease, box-shadow 160ms ease;
    }

    .search-field:focus-within {
      border-color: var(--blue);
      box-shadow: 0 0 0 3px rgb(69 105 159 / 12%);
    }

    .search-icon {
      width: 23px;
      height: 23px;
      flex: 0 0 auto;
      color: #737b87;
    }

    .search-field input {
      width: 100%;
      min-width: 0;
      padding: 0;
      border: 0;
      outline: 0;
      color: var(--navy);
      background: transparent;
      font: inherit;
      font-size: 19px;
    }

    .search-field input::placeholder {
      color: var(--muted);
      opacity: 1;
    }

    .search-button {
      min-width: 112px;
      padding: 0 26px;
      border: 0;
      border-radius: 10px;
      color: #fff;
      background: var(--blue);
      font: inherit;
      font-size: 19px;
      font-weight: 600;
      cursor: pointer;
      transition: background 160ms ease, transform 160ms ease;
    }

    .search-button:hover {
      background: var(--blue-hover);
    }

    .search-button:active {
      transform: translateY(1px);
    }

    .search-button:focus-visible {
      outline: 3px solid #a9c4ed;
      outline-offset: 3px;
    }

    .page-content {
      padding: 28px clamp(20px, 4vw, 64px);
    }

    .search-status {
      margin: 0;
      color: #586477;
      font-size: 14px;
    }

    @media (max-width: 640px) {
      .site-header {
        align-items: stretch;
        flex-direction: column;
        gap: 18px;
        padding: 22px 18px;
      }

      .brand {
        font-size: 27px;
      }

      .search-form {
        gap: 8px;
        width: 100%;
        margin: 0;
      }

      .search-field {
        height: 50px;
        gap: 10px;
        padding: 0 12px;
      }

      .search-icon {
        width: 20px;
        height: 20px;
      }

      .search-field input {
        font-size: 16px;
      }

      .search-button {
        min-width: 76px;
        padding: 0 15px;
        font-size: 16px;
      }
    }
  </style>
</head>
<body>
  <header class="site-header">
    <h1 class="brand">宇野重工</h1>

    <form class="search-form" id="name-search" role="search">
      <label class="search-field">
        <svg class="search-icon" viewBox="0 0 24 24" fill="none"aria-hidden="true">
          <circle cx="10.8" cy="10.8" r="6.8" stroke="currentColor"
                  stroke-width="2.2"></circle>
          <path d="m16 16 5 5" stroke="currentColor" stroke-width="2.2"
                stroke-linecap="round"></path>
        </svg>
        <input id="name-query" name="name" type="search"
              placeholder="名前で検索" aria-label="名前で検索">
      </label>
      <button class="search-button" type="submit">検索</button>
    </form>
      <a href="admin.php"><button class="admin-button" id="admin-button" type="button">管理者画面</button></a>
  </header>
  <script>
    const searchForm = document.querySelector("#name-search");
    const searchInput = document.querySelector("#name-query");
    const searchStatus = document.querySelector("#search-status");

    searchForm.addEventListener("submit", (event) => {
      event.preventDefault();
      const name = searchInput.value.trim();

      if (!name) {
        searchStatus.textContent = "検索する名前を入力してください。";
        searchInput.focus();
        return;
      }

      // 実際の検索処理は、座席データに接続した後で実装してください。
      searchStatus.textContent = `「${name}」の検索を実行します。`;
    });
  </script>
</body>
</html>
