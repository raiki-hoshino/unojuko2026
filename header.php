
<header class="site-header">
  <link rel="stylesheet" href="css/header.css">
  <h1 class="brand">宇野重工</h1>

  <div class="header-date" id="header-date">
  <span class="date-label">本日の日付</span>
  <span class="date-value" id="date-value"></span>
</div>

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
    <a href="main.php" ><button class="zaseki-button" type="button" >座席画面</button></a>
    <a href="admin.php"><button class="admin-button" type="button">管理画面</button></a>

    <div class="header-divider"></div>

    <span class="user-name">山田 太郎 さん</span>

    <a href="index.php">
    <button
      class="logout-button"
      type="button"
      onclick="logout()"
    >
      ログアウト
    </button>
    </a>
  </div>
</header>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>座席登録</title>

</head>
<body>

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

  
  function updateHeaderDate() {
    const dateElement = document.getElementById("date-value");

    const today = new Date();

    const formattedDate = new Intl.DateTimeFormat("ja-JP", {
      year: "numeric",
      month: "2-digit",
      day: "2-digit",
      weekday: "short"
    }).format(today);

    dateElement.textContent = formattedDate;
  }

  updateHeaderDate();
</script>

  </script>
</body>
</html>
