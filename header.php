
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