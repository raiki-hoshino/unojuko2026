<?php
session_start();
require "header.php";
// ---- DB接続（config/database.php で $pdo を作っている） ----
require "config/database.php";

// 接続に失敗すると $pdo は null のままなので、ここで止める
if ($pdo === null) {
    echo "データベースに接続できません。";
    exit;
}
// エラーを例外として受け取る（削除のトランザクション用）
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// 画面に出す文字は必ずこの関数を通す（XSS対策）
function h($s)
{
    return htmlspecialchars((string)$s, ENT_QUOTES, "UTF-8");
}

// CSRFトークン（削除ボタン用）
if (empty($_SESSION["csrf"])) {
    $_SESSION["csrf"] = bin2hex(random_bytes(16));
}
$csrf = $_SESSION["csrf"];

// ---- 検索条件・ページ ----
$q_num  = trim($_GET["num"] ?? "");
$q_name = trim($_GET["name"] ?? "");
$page   = max(1, (int)($_GET["page"] ?? 1));
$per    = 10;

// 検索条件を引き継ぐためのクエリ文字列
function make_qs($num, $name, $page)
{
    $p = [];
    if ($num !== "")  { $p["num"] = $num; }
    if ($name !== "") { $p["name"] = $name; }
    if ($page > 1)    { $p["page"] = $page; }
    return http_build_query($p);
}

// ---- 削除処理（POST） ----
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $token = $_POST["csrf"] ?? "";
    $id    = (int)($_POST["people_id"] ?? 0);
    if (hash_equals($csrf, $token) && $id > 0) {
        $pdo->beginTransaction();
        // sit が people_id を参照しているので先に消す
        $st = $pdo->prepare("DELETE FROM sit WHERE people_id = :id");
        $st->execute([":id" => $id]);
        $st = $pdo->prepare("DELETE FROM people WHERE people_id = :id");
        $st->execute([":id" => $id]);
        $pdo->commit();
    }
    $qs = make_qs($q_num, $q_name, $page);
    header("Location: admin.php" . ($qs !== "" ? "?{$qs}" : ""));
    exit;
}

// ---- 検索 ----
$where  = [];
$params = [];
if ($q_num !== "") {
    $where[] = "p.people_num LIKE :num";
    $params[":num"] = "%" . addcslashes($q_num, "%_\\") . "%";
}
if ($q_name !== "") {
    // 空白（半角・全角）を除いて比較する
    $kw = preg_replace("/[\\s\\x{3000}]+/u", "", $q_name);
    $where[] = "REPLACE(REPLACE(p.name, ' ', ''), '　', '') LIKE :name";
    $params[":name"] = "%" . addcslashes($kw, "%_\\") . "%";
}
$where_sql = $where ? "WHERE " . implode(" AND ", $where) : "";

$st = $pdo->prepare("SELECT COUNT(*) FROM people p {$where_sql}");
$st->execute($params);
$total = (int)$st->fetchColumn();

$pages = max(1, (int)ceil($total / $per));
if ($page > $pages) { $page = $pages; }
$offset = ($page - 1) * $per;

// 本日の座席 = sit の今日の最新の history_num
$sql = "SELECT p.people_id, p.people_num, p.name,
            (SELECT s.history_num FROM sit s
                WHERE s.people_id = p.people_id AND DATE(s.date) = CURDATE()
                ORDER BY s.date DESC LIMIT 1) AS today_seat
        FROM people p
        {$where_sql}
        ORDER BY p.people_num
        LIMIT :lim OFFSET :off";
$st = $pdo->prepare($sql);
foreach ($params as $k => $v) {
    $st->bindValue($k, $v);
}
$st->bindValue(":lim", $per, PDO::PARAM_INT);
$st->bindValue(":off", $offset, PDO::PARAM_INT);
$st->execute();
$rows = $st->fetchAll(PDO::FETCH_ASSOC);

$from = $total ? $offset + 1 : 0;
$to   = $offset + count($rows);
$qs_now = make_qs($q_num, $q_name, $page);
$v_num  = h($q_num);
$v_name = h($q_name);

// ページ送りのリンク
$prev_url = "admin.php?" . make_qs($q_num, $q_name, $page - 1);
$next_url = "admin.php?" . make_qs($q_num, $q_name, $page + 1);

$prev_cls = $page <= 1 ? "dis" : "";
$next_cls = $page >= $pages ? "dis" : "";

// ---- 一覧の行を作る ----
$list = "";
if (count($rows) === 0) {
    $list = "<tr><td colspan='4' class='empty'>条件に合う社員がいません。検索条件を変えてください。</td></tr>";
}
foreach ($rows as $r) {
    $id   = (int)$r["people_id"];
    $num  = h($r["people_num"]);
    $name = h($r["name"]);
    $seat = $r["today_seat"] !== null ? h($r["today_seat"]) : "—";
    $msg  = h(addslashes($r["name"])) . "さん（" . h(addslashes($r["people_num"])) . "）を削除します。この操作は元に戻せません。";

    $list .= "<tr>
        <td>{$num}</td>
        <td class='n'>{$name}</td>
        <td class='seat'>{$seat}</td>
        <td>
            <form method='post' action='admin.php?{$qs_now}' class='delform' onsubmit=\"return confirm('{$msg}');\">
                <input type='hidden' name='csrf' value='{$csrf}'>
                <input type='hidden' name='people_id' value='{$id}'>
                <button class='del' type='submit'><svg viewBox='0 0 24 24'><path d='M4 7h16M9 7V4.5h6V7M6.5 7l1 12.5h9l1-12.5M10 11v5M14 11v5'/></svg>削除</button>
            </form>
        </td>
    </tr>";
}
$range = $total ? "{$from}–{$to} / {$total}件" : "0件";

// ---- 画面 ----
echo "<!DOCTYPE html>
<html lang='ja'>
<head>
<meta charset='utf-8'>
<meta name='viewport' content='width=device-width, initial-scale=1, viewport-fit=cover'>
<title>社員管理</title>
<link rel='preconnect' href='https://fonts.googleapis.com'>
<link rel='preconnect' href='https://fonts.gstatic.com' crossorigin>
<link href='https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@400;500;700&display=swap' rel='stylesheet'>
<link rel='stylesheet' href='css/admin.css'>
</head>
<body>
<div class='layout'>

<nav class='side' aria-label='管理者メニュー'>
    <small>管理者メニュー</small>
    <a href='admin.php'>社員管理</a>
    <a href='admin-change.php'>座席変更</a>
    <a href='admin-data.php'aria-current='page'>座席データ閲覧</a>
    <hr>
    <a href='main.php' class='back'>座席図に戻る</a>
</nav>

<main>
    <div class='crumb'>管理者画面</div>
    <h1>社員管理</h1>
    <p class='lead'>社員の検索、登録情報の確認・削除を行います。</p>

    <section class='card search'>
        <h2>社員を検索</h2>
        <form method='get' action='admin.php' class='row'>
            <div class='field'><label for='q-num'>社員番号</label><input id='q-num' name='num' value='{$v_num}' placeholder='例：1001' autocomplete='off'></div>
            <div class='field'><label for='q-name'>名前</label><input id='q-name' name='name' value='{$v_name}' placeholder='姓・名で検索' autocomplete='off'></div>
            <button class='btn primary' type='submit'>検索</button>
            <a class='btn' href='admin.php'>クリア</a>
        </form>
    </section>

    <section class='card'>
        <div class='listhead'><h2>社員一覧</h2><span class='count'>全{$total}名</span></div>
        <div class='scroll'>
            <table>
                <thead><tr><th>社員番号</th><th>名前</th><th>本日の座席</th><th>操作</th></tr></thead>
                <tbody>{$list}</tbody>
            </table>
        </div>
        <div class='foot'>
            <span>{$range}</span>
            <div class='pager'>
                <a class='{$prev_cls}' href='{$prev_url}' aria-label='前のページ'><</a>
                <span class='cur'>{$page}</span>
                <a class='{$next_cls}' href='{$next_url}' aria-label='次のページ'>></a>
            </div>
        </div>
    </section>

    <div class='note'>社内専用</div>
</main>

</div>
</body>
</html>";