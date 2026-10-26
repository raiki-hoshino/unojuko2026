<?php

require "config/database.php";

// 接続に失敗すると $pdo は null のままなので、ここで止める
if ($pdo === null) {
    echo "データベースに接続できません。";
    exit;
}
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// 画面に出す文字は必ずこの関数を通す（XSS対策）
function h($s)
{
    return htmlspecialchars((string)$s, ENT_QUOTES, "UTF-8");
}

// 検索条件をURLのクエリ文字列にする（ページ送り用）
function make_qs(array $filters, $page)
{
    $p = array_filter($filters, function ($v) { return $v !== ""; });
    if ($page > 1) { $p["page"] = $page; }
    return http_build_query($p);
}

// ---- 検索条件（GET） ----
$from = trim($_GET["from"] ?? "");
$to   = trim($_GET["to"] ?? "");
$seat = trim($_GET["seat"] ?? "");
$num  = trim($_GET["num"] ?? "");
$name = trim($_GET["name"] ?? "");
$page = max(1, (int)($_GET["page"] ?? 1));
$per  = 20;

// 日付は YYYY-MM-DD の形だけ受け付ける
if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $from)) { $from = ""; }
if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $to))   { $to = ""; }

$filters = ["from" => $from, "to" => $to, "seat" => $seat, "num" => $num, "name" => $name];

$where  = [];
$params = [];
if ($from !== "") {
    $where[] = "DATE(sit.date) >= :from";
    $params[":from"] = $from;
}
if ($to !== "") {
    $where[] = "DATE(sit.date) <= :to";
    $params[":to"] = $to;
}
if ($seat !== "") {
    $where[] = "sit.history_num = :seat";
    $params[":seat"] = $seat;
}
if ($num !== "") {
    $where[] = "people.people_num LIKE :num";
    $params[":num"] = "%" . addcslashes($num, "%_\\") . "%";
}
if ($name !== "") {
    // 空白（半角・全角）を除いて比較する
    $kw = preg_replace("/[\\s\\x{3000}]+/u", "", $name);
    $where[] = "REPLACE(REPLACE(people.name, ' ', ''), '　', '') LIKE :name";
    $params[":name"] = "%" . addcslashes($kw, "%_\\") . "%";
}
$where_sql = $where ? "WHERE " . implode(" AND ", $where) : "";

$base   = "FROM sit JOIN people ON sit.people_id = people.people_id {$where_sql}";
$select = "SELECT sit.date, sit.history_num, people.people_num, people.name";
$order  = "ORDER BY sit.date DESC, sit.sit_id DESC";

// ---- CSV出力（?csv=1 のとき） ----
if (($_GET["csv"] ?? "") === "1") {
    $st = $pdo->prepare("{$select} {$base} {$order}");
    $st->execute($params);
    header("Content-Type: text/csv; charset=UTF-8");
    header("Content-Disposition: attachment; filename=\"seat_data.csv\"");
    echo "\xEF\xBB\xBF"; // Excelで文字化けしないためのBOM
    $out = fopen("php://output", "w");
    fputcsv($out, ["登録日", "社員番号", "名前", "座席番号", "登録時刻"]);
    while ($r = $st->fetch(PDO::FETCH_ASSOC)) {
        $ts = strtotime($r["date"]);
        fputcsv($out, [date("Y/m/d", $ts), $r["people_num"], $r["name"], $r["history_num"], date("H:i", $ts)]);
    }
    fclose($out);
    exit;
}

// ---- 件数・ページ ----
$st = $pdo->prepare("SELECT COUNT(*) {$base}");
$st->execute($params);
$total = (int)$st->fetchColumn();

$pages = max(1, (int)ceil($total / $per));
if ($page > $pages) { $page = $pages; }
$offset = ($page - 1) * $per;

// ---- 一覧の取得 ----
$st = $pdo->prepare("{$select} {$base} {$order} LIMIT :lim OFFSET :off");
foreach ($params as $k => $v) {
    $st->bindValue($k, $v);
}
$st->bindValue(":lim", $per, PDO::PARAM_INT);
$st->bindValue(":off", $offset, PDO::PARAM_INT);
$st->execute();
$rows = $st->fetchAll(PDO::FETCH_ASSOC);

// ---- 座席番号の選択肢 ----
$seat_options = "<option value=''>すべて</option>";
foreach ($pdo->query("SELECT seat_num FROM seat ORDER BY seat_id") as $s) {
    $sn  = h($s["seat_num"]);
    $sel = $s["seat_num"] === $seat ? " selected" : "";
    $seat_options .= "<option value='{$sn}'{$sel}>{$sn}</option>";
}

// ---- 一覧の行を作る ----
$list = "";
if (count($rows) === 0) {
    $list = "<tr><td colspan='5' class='empty'>条件に合うデータがありません。検索条件を変えてください。</td></tr>";
}
foreach ($rows as $r) {
    $ts          = strtotime($r["date"]);
    $day         = h(date("Y/m/d", $ts));
    $time        = h(date("H:i", $ts));
    $people_num  = h($r["people_num"]);
    $name_view   = h($r["name"]);
    $history_num = h($r["history_num"]);

    $list .= "<tr>
        <td>{$day}</td>
        <td class='num'>{$people_num}</td>
        <td class='n'>{$name_view}</td>
        <td class='seatno'>{$history_num}</td>
        <td class='time'>{$time}</td>
    </tr>";
}

// ---- 画面用の値 ----
$v_from = h($from);
$v_to   = h($to);
$v_num  = h($num);
$v_name = h($name);

$from_no = $total ? $offset + 1 : 0;
$to_no   = $offset + count($rows);
$range   = $total ? "{$from_no}–{$to_no} / {$total}件" : "0件";

$prev_url = "admin-data.php?" . make_qs($filters, $page - 1);
$next_url = "admin-data.php?" . make_qs($filters, $page + 1);
$prev_cls = $page <= 1 ? "dis" : "";
$next_cls = $page >= $pages ? "dis" : "";
$csv_url  = "admin-data.php?" . http_build_query(array_merge(array_filter($filters, function ($v) { return $v !== ""; }), ["csv" => 1]));

// ---- 画面（echoは1回だけ） ----
echo "<!DOCTYPE html>
<html lang='ja'>
<head>
<meta charset='utf-8'>
<meta name='viewport' content='width=device-width, initial-scale=1, viewport-fit=cover'>
<title>座席データ閲覧</title>
<link rel='preconnect' href='https://fonts.googleapis.com'>
<link rel='preconnect' href='https://fonts.gstatic.com' crossorigin>
<link href='https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@400;500;700&display=swap' rel='stylesheet'>
<link rel='stylesheet' href='css/admin.css'>
<link rel='stylesheet' href='css/admin-data.css'>
</head>
<body>
<div class='layout'>

<nav class='side' aria-label='管理者メニュー'>
    <small>管理者メニュー</small>
    <a href='admin.php'>社員管理</a>
    <a href='admin-change.php'>座席変更</a>
    <a href='admin-data.php' class='on' aria-current='page'>座席データ閲覧</a>
    <hr>
    <a href='main.php' class='back'>座席図に戻る</a>
</nav>

<main>
    <div class='crumb'>管理者画面</div>
    <h1>座席データ閲覧</h1>
    <p class='lead'>日付や社員情報から、座席の登録履歴を確認できます。</p>

    <section class='card search'>
        <h2>検索条件</h2>
        <form method='get' action='admin-data.php'>
            <div class='row'>
                <div class='field'><label for='q-from'>開始日</label><input type='date' id='q-from' name='from' value='{$v_from}'></div>
                <span class='tilde'>～</span>
                <div class='field'><label for='q-to'>終了日</label><input type='date' id='q-to' name='to' value='{$v_to}'></div>
                <div class='field'><label for='q-seat'>座席番号</label><select id='q-seat' name='seat'>{$seat_options}</select></div>
            </div>
            <div class='row'>
                <div class='field'><label for='q-num'>社員番号</label><input id='q-num' name='num' value='{$v_num}' placeholder='例：1001' autocomplete='off'></div>
                <div class='field'><label for='q-name'>名前</label><input id='q-name' name='name' value='{$v_name}' placeholder='姓・名で検索' autocomplete='off'></div>
                <button class='btn primary' type='submit'><svg viewBox='0 0 24 24'><circle cx='11' cy='11' r='6.5'/><path d='m20 20-4.2-4.2'/></svg>検索</button>
                <a class='btn' href='admin-data.php'>クリア</a>
            </div>
        </form>
    </section>

    <section class='card'>
        <div class='listhead'>
            <div class='ttl'><h2>座席登録データ</h2><span class='count'>{$total}件</span></div>
            <a class='btn csv' href='{$csv_url}'><svg viewBox='0 0 24 24'><path d='M12 4v11M7 11l5 5 5-5M5 20h14'/></svg>CSV出力</a>
        </div>
        <div class='scroll'>
            <table class='data'>
                <thead><tr><th>登録日</th><th>社員番号</th><th>名前</th><th>座席番号</th><th>登録時刻</th></tr></thead>
                <tbody>{$list}</tbody>
            </table>
        </div>
        <div class='foot'>
            <span>{$range}</span>
            <div class='pager'>
                <a class='{$prev_cls}' href='{$prev_url}' aria-label='前のページ'><svg viewBox='0 0 24 24'><path d='m15 6-6 6 6 6'/></svg></a>
                <span class='cur'>{$page}</span>
                <a class='{$next_cls}' href='{$next_url}' aria-label='次のページ'><svg viewBox='0 0 24 24'><path d='m9 6 6 6-6 6'/></svg></a>
            </div>
        </div>
    </section>

    <div class='note'>社内専用</div>
</main>

</div>
</body>
</html>";