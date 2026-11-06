<?php require "config/database.php" ?>
<?php ob_start(); // header.php の出力を一旦ためておく（着席の処理では JSON だけを返すため） ?>
<?php require "header.php" ?>
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config/auth.php';   // currentUserId() など

if ($pdo === null) {
    echo "データベースに接続できません。";
    exit;
}
/*
 * ===== テーブル・セッションの前提（違う場合はここと SQL を直してください）=====
 *   seat  : seat_id, seat_num, floor, seat_x, seat_y
 *   sit   : sit_id, seat_id, people_id, history_num, date, leave_date
 *           （着席の履歴。leave_date が空の行 = いま座っている）
 *   people: people_id, name
 *   セッション: ログイン中の people_id（config/auth.php の currentUserId() で取り出す）
 */

$floors = [1 => '1階', 2 => '2階', 3 => '3階'];

/** seat_x / seat_y のテーブル初期値 */
function seatDefaults(PDO $pdo): array
{
    $def = ['seat_x' => null, 'seat_y' => null];
    try {
        $st = $pdo->query(
            "SELECT COLUMN_NAME, COLUMN_DEFAULT FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'seat'
               AND COLUMN_NAME IN ('seat_x', 'seat_y')"
        );
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $col) {
            $d = $col['COLUMN_DEFAULT'];
            if ($d !== null) {
                $d = trim((string)$d, "'\"");
            }
            $def[$col['COLUMN_NAME']] = ($d === null || !is_numeric($d)) ? null : (float)$d;
        }
    } catch (PDOException $e) {
    }
    return $def;
}

/** 階の画像（URL と元サイズ） */
function floorImage(int $floor): array
{
    foreach (['jpg', 'png', 'gif', 'webp'] as $ext) {
        $path = __DIR__ . '/uploads/floor' . $floor . '-image.' . $ext;
        if (is_file($path)) {
            $size = @getimagesize($path);
            return [
                'uploads/floor' . $floor . '-image.' . $ext . '?v=' . filemtime($path),
                (int)($size[0] ?? 1000),
                (int)($size[1] ?? 600),
            ];
        }
    }
    return ['', 1000, 600];
}

/** 自分がいま座っている席（なければ null） */
function loadMySeat(PDO $pdo, ?int $me): ?array
{
    if ($me === null) {
        return null;
    }
    $st = $pdo->prepare(
        'SELECT se.seat_num, se.floor
         FROM sit si JOIN seat se ON se.seat_id = si.seat_id
         WHERE si.people_id = ? AND si.leave_date IS NULL
         ORDER BY si.sit_id DESC LIMIT 1'
    );
    $st->execute([$me]);
    $r = $st->fetch(PDO::FETCH_ASSOC);
    return $r ? ['num' => (string)$r['seat_num'], 'floor' => (int)$r['floor']] : null;
}

/** その階の着席状況：seat_num => ['name' => 名前, 'mine' => 自分か] */
function loadState(PDO $pdo, int $floor, ?int $me): array
{
    // leave_date が空 = まだ離席していない = いま座っている
    $st = $pdo->prepare(
        'SELECT se.seat_num, si.people_id, p.name
         FROM sit si
         JOIN seat se ON se.seat_id = si.seat_id
         LEFT JOIN people p ON p.people_id = si.people_id
         WHERE se.floor = ? AND si.leave_date IS NULL'
    );
    $st->execute([$floor]);
    $state = [];
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $state[(string)$r['seat_num']] = [
            'name' => (string)($r['name'] ?? ''),
            'mine' => $me !== null && (int)$r['people_id'] === $me,
        ];
    }
    return $state;
}

function h(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

$me = currentUserId();

/* ===== 座席カードを押したとき（着席 / 席の移動 / 離席） ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'sit') {
    while (ob_get_level() > 0) {   // header.php の HTML は捨てて、JSON だけ返す
        ob_end_clean();
    }
    header('Content-Type: application/json; charset=UTF-8');

    $reqFloor = (int)($_POST['floor'] ?? 0);
    $num      = trim((string)($_POST['seat_num'] ?? ''));

    $reply = function (bool $ok, string $message) use ($pdo, $reqFloor, $me, $floors) {
        $seats = new stdClass();
        $mySeat = null;
        if (isset($floors[$reqFloor])) {
            try {
                $mySeat = loadMySeat($pdo, $me);
                $seats = loadState($pdo, $reqFloor, $me);
            } catch (PDOException $e) {
                $message .= '（着席状況を読み込めません：' . $e->getMessage() . '）';
            }
        }
        echo json_encode([
            'ok'      => $ok,
            'message' => $message,
            'seats'   => $seats,
            'mySeat'  => $mySeat,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    };

    if ($me === null)                              $reply(false, 'ログインしてください。');
    if (!isset($floors[$reqFloor]) || $num === '') $reply(false, '座席が正しくありません。');

    try {
        $pdo->beginTransaction();

        $chk = $pdo->prepare('SELECT seat_id FROM seat WHERE floor = ? AND seat_num = ?');
        $chk->execute([$reqFloor, $num]);
        $seatId = $chk->fetchColumn();
        if ($seatId === false) {
            $pdo->rollBack();
            $reply(false, 'その座席は存在しません。');
        }

        // いまこの席に座っている人（leave_date が空の行）
        $cur = $pdo->prepare('SELECT sit_id, people_id FROM sit WHERE seat_id = ? AND leave_date IS NULL FOR UPDATE');
        $cur->execute([$seatId]);
        $row = $cur->fetch(PDO::FETCH_ASSOC);

        if ($row && (int)$row['people_id'] === $me) {
            // 自分の席をもう一度押した → 離席（leave_date を入れる）
            $pdo->prepare('UPDATE sit SET leave_date = NOW() WHERE sit_id = ?')
                ->execute([$row['sit_id']]);
            $pdo->commit();
            $reply(true, $num . ' から離席しました。');
        } elseif ($row) {
            $pdo->rollBack();
            $reply(false, 'この席は他の人が使用中です。');
        } else {
            // 別の席に座っていれば、そちらを離席扱いにしてから着席（席の移動）
            $pdo->prepare('UPDATE sit SET leave_date = NOW() WHERE people_id = ? AND leave_date IS NULL')
                ->execute([$me]);
            $pdo->prepare('INSERT INTO sit (seat_id, people_id, `date`) VALUES (?, ?, NOW())')
                ->execute([$seatId, $me]);
            $pdo->commit();
            $reply(true, $num . ' に着席しました。');
        }
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        // ※ 原因が分かるよう DB のエラー内容も表示（公開前に外してください）
        $reply(false, '操作に失敗しました。（' . $e->getMessage() . '）');
    }
    exit;
}

/* ===== 画面表示 ===== */
$floor = (int)($_GET['floor'] ?? 1);
if (!isset($floors[$floor])) {
    $floor = 1;
}
[$imageUrl, $imgW, $imgH] = floorImage($floor);

$dbError = '';
$state = [];
$mySeat = null;
$rows = [];
try {
    $st = $pdo->prepare('SELECT seat_num, seat_x, seat_y FROM seat WHERE floor = ? ORDER BY seat_num');
    $st->execute([$floor]);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);
    $state = loadState($pdo, $floor, $me);
    $mySeat = loadMySeat($pdo, $me);
} catch (PDOException $e) {
    // ※ 原因が分かるよう DB のエラー内容を表示（公開前に外してください）
    $dbError = 'データを読み込めません：' . $e->getMessage();
}

// 配置済みの座席だけ表示（NULL・0,0・テーブル初期値のままの座席は表示しない）
$def = seatDefaults($pdo);
$placed = [];
foreach ($rows as $s) {
    if ($s['seat_x'] === null || $s['seat_y'] === null) continue;
    $x = (float)$s['seat_x'];
    $y = (float)$s['seat_y'];
    if ($x == 0 && $y == 0) continue;
    if ($def['seat_x'] !== null && $def['seat_y'] !== null && $x == $def['seat_x'] && $y == $def['seat_y']) continue;
    $placed[] = [
        'num'  => (string)$s['seat_num'],
        'left' => round($x / $imgW * 100, 4),   // 画像に対する割合(%)で配置 → 画面サイズが変わってもずれない
        'top'  => round($y / $imgH * 100, 4),
    ];
}
?>
<?php ob_end_flush(); // ためておいた header.php の出力を、元と同じ位置（DOCTYPE の前）で出す ?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="css/main.css">
  <style>
    /* ===== 座席カード・確認ダイアログ・通知（main.php 専用。css/main.css は変更しません） ===== */
    :root{
      --seat-surface:#fff;--seat-soft:#f6f8f7;--seat-border:#cfd8d4;
      --seat-text:#1c2a25;--seat-muted:#6f7b77;
      --seat-accent:#00736D;--seat-accent-ink:#fff;--seat-danger:#c0392b;
    }
    @media (prefers-color-scheme:dark){
      :root{
        --seat-surface:#1c2421;--seat-soft:#222b28;--seat-border:#2e3935;
        --seat-text:#e6eeea;--seat-muted:#93a19b;
        --seat-accent:#5ab5a4;--seat-accent-ink:#0f1a17;--seat-danger:#ef8a7d;
      }
    }

    /* 座席図の枠：高さ固定・中央寄せ・はみ出し隠しがあると、画像と座席の位置がずれるため解除 */
    .main-panel .image-preview{
      height:auto;min-height:0;max-height:none;overflow:visible;
      align-items:flex-start;align-content:flex-start;justify-content:flex-start;
    }
    .stage{position:relative;width:100%;line-height:0}
    .stage img{user-select:none;-webkit-user-drag:none}
    .stage.empty{aspect-ratio:5/3;display:flex;align-items:center;justify-content:center;line-height:1.6}
    .stage.empty p{margin:0}

    /* 座席カード（寸法は em：JS が表示幅に合わせて font-size を設定） */
    .seat{
      display:flex;flex-direction:column;align-items:center;justify-content:center;gap:.1em;
      min-width:3em;padding:.35em .7em;margin:0;
      border:.12em solid var(--seat-accent);border-radius:.7em;
      background:var(--seat-surface);color:var(--seat-accent);
      font:inherit;line-height:1.25;white-space:nowrap;
      box-shadow:0 1px 3px rgba(0,0,0,.25);cursor:pointer;
    }
    .seat .no{font-size:.85em;font-weight:700}
    .seat .who{max-width:8em;overflow:hidden;text-overflow:ellipsis;font-size:1em;font-weight:700;color:var(--seat-text)}
    .seat:hover:not(.taken){filter:brightness(.96)}
    .seat:focus-visible{outline:2px solid var(--seat-accent);outline-offset:2px}
    .seat.taken{background:var(--seat-soft);border-color:var(--seat-border);color:var(--seat-muted);cursor:not-allowed}
    .seat.mine{background:var(--seat-accent);border-color:var(--seat-accent);color:var(--seat-accent-ink);cursor:pointer;z-index:2}
    .seat.mine .who{color:var(--seat-accent-ink)}

    /* 操作結果の通知（レイアウトに影響しないよう画面下に浮かせる） */
    .sit-toast{
      position:fixed;left:50%;bottom:24px;transform:translate(-50%,16px);
      max-width:min(92vw,520px);padding:10px 18px;border-radius:999px;
      background:var(--seat-accent);color:var(--seat-accent-ink);
      font-size:13px;line-height:1.5;box-shadow:0 4px 16px rgba(0,0,0,.25);
      opacity:0;pointer-events:none;transition:opacity .2s,transform .2s;z-index:3000;
    }
    .sit-toast.error{background:var(--seat-danger);color:#fff}
    .sit-toast.show{opacity:1;transform:translate(-50%,0)}

    /* 最終確認ダイアログ */
    dialog.sit-dialog{
      width:min(92vw,360px);padding:0;border:0;border-radius:14px;
      background:var(--seat-surface);color:var(--seat-text);
      box-shadow:0 12px 40px rgba(0,0,0,.35);
    }
    dialog.sit-dialog::backdrop{background:rgba(0,0,0,.45)}
    .sit-dialog-body{padding:22px 20px 18px}
    .sit-dialog-title{margin:0;font-size:17px;font-weight:700;line-height:1.5}
    .sit-dialog-text{margin:8px 0 0;font-size:13px;color:var(--seat-muted);line-height:1.6}
    .sit-dialog-actions{display:flex;gap:10px;margin-top:20px}
    .sit-btn{
      flex:1;min-height:42px;padding:0 12px;
      border:1px solid var(--seat-border);border-radius:10px;
      background:var(--seat-surface);color:var(--seat-text);
      font:inherit;font-size:14px;font-weight:600;cursor:pointer;
    }
    .sit-btn.primary{background:var(--seat-accent);border-color:var(--seat-accent);color:var(--seat-accent-ink)}
    .sit-btn:focus-visible{outline:2px solid var(--seat-accent);outline-offset:2px}
  </style>
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
        <div class="stage<?= $imageUrl ? '' : ' empty' ?>" id="stage" style="position:relative;width:100%;flex:none;align-self:flex-start;margin:0<?= $imageUrl ? ';aspect-ratio:' . $imgW . '/' . $imgH : '' ?>">
          <?php if ($imageUrl): ?>
            <img src="<?= h($imageUrl) ?>" alt="<?= $floor ?>階の座席図" draggable="false"
                style="position:absolute!important;left:0!important;top:0!important;display:block!important;width:100%!important;height:100%!important;max-width:none!important;max-height:none!important;object-fit:fill!important;margin:0!important">
          <?php else: ?>
            <p><?= $floor ?>階の画像が登録されていません。</p>
          <?php endif; ?>

          <!-- 座席カード（admin-change.php で保存した位置に表示） -->
          <div class="seat-layer" id="seat-layer" style="position:absolute;left:0;top:0;right:0;bottom:0;pointer-events:none;font-size:12px">
            <?php foreach ($placed as $p): ?>
              <button type="button" class="seat" data-seat="<?= h($p['num']) ?>"
                  style="position:absolute;transform:translate(-50%,-50%);pointer-events:auto;left:<?= $p['left'] ?>%;top:<?= $p['top'] ?>%">
                <span class="no"><?= h($p['num']) ?></span>
              </button>
            <?php endforeach; ?>
          </div>
        </div>
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

  <!-- 操作結果の通知 -->
  <div class="sit-toast" id="sit-toast" role="status" aria-live="polite"></div>

  <!-- 着席・離席の最終確認 -->
  <dialog class="sit-dialog" id="sit-dialog" aria-labelledby="sit-dialog-title">
    <form method="dialog" class="sit-dialog-body">
      <p class="sit-dialog-title" id="sit-dialog-title"></p>
      <p class="sit-dialog-text" id="sit-dialog-text"></p>
      <div class="sit-dialog-actions">
        <button type="submit" value="cancel" class="sit-btn">キャンセル</button>
        <button type="submit" value="ok" class="sit-btn primary" id="sit-dialog-ok">OK</button>
      </div>
    </form>
  </dialog>

<script>
(() => {
  const FLOOR        = <?= (int)$floor ?>;
  const FLOOR_LABELS = <?= json_encode($floors, JSON_UNESCAPED_UNICODE) ?>;
  const LOGGED_IN    = <?= $me !== null ? 'true' : 'false' ?>;
  const STATE0       = <?= json_encode($state ?: new stdClass(), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>;
  const INIT_ERROR   = <?= json_encode($dbError, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>;
  let   mySeat       = <?= json_encode($mySeat, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>;  // 自分が今座っている席

  const stage = document.getElementById('stage');
  const layer = document.getElementById('seat-layer');
  const toast = document.getElementById('sit-toast');
  const dlg   = document.getElementById('sit-dialog');
  const cards = [...layer.querySelectorAll('.seat')];

  const clamp = (v, min, max) => Math.min(max, Math.max(min, v));

  /* ---------- 通知（画面下に数秒だけ表示） ---------- */
  let toastTimer = null;
  const showMsg = (text, isError = false) => {
    clearTimeout(toastTimer);
    if (!text) { toast.classList.remove('show'); return; }
    toast.textContent = text;
    toast.classList.toggle('error', isError);
    toast.classList.add('show');
    toastTimer = setTimeout(() => toast.classList.remove('show'), isError ? 6000 : 3500);
  };
  if (INIT_ERROR) showMsg(INIT_ERROR, true);

  /* ---------- 最終確認ダイアログ（OK → true / キャンセル → false） ---------- */
  function ask({ title, text = '', ok = 'OK' }) {
    return new Promise(resolve => {
      if (typeof dlg.showModal !== 'function') {          // 古いブラウザ用
        resolve(window.confirm(title + (text ? '\n' + text : '')));
        return;
      }
      document.getElementById('sit-dialog-title').textContent = title;
      const t = document.getElementById('sit-dialog-text');
      t.textContent = text;
      t.hidden = !text;
      document.getElementById('sit-dialog-ok').textContent = ok;
      dlg.returnValue = '';
      dlg.addEventListener('close', () => resolve(dlg.returnValue === 'ok'), { once: true });
      dlg.showModal();
    });
  }
  // 暗い背景をタップしたらキャンセル
  dlg.addEventListener('click', e => { if (e.target === dlg) dlg.close('cancel'); });

  /* ---------- 着席状況をカードに反映（名前を表示） ---------- */
  function applyState(state) {
    cards.forEach(card => {
      const o = state[card.dataset.seat];
      card.classList.toggle('taken', !!o);
      card.classList.toggle('mine', !!(o && o.mine));

      let who = card.querySelector('.who');
      if (o) {
        if (!who) {
          who = document.createElement('span');
          who.className = 'who';
          card.appendChild(who);
        }
        who.textContent = o.name || '使用中';
        card.title = (o.name || '使用中') + (o.mine ? '（あなた）' : '');
      } else {
        if (who) who.remove();
        card.removeAttribute('title');
      }
    });
  }
  applyState(STATE0);

  /* ---------- カードの大きさを画像の表示幅に合わせる ---------- */
  const BASE_WIDTH = 1000;
  const MIN_SCALE  = window.matchMedia('(pointer: coarse)').matches ? 0.75 : 0.55;
  const updateScale = () => {
    const w = stage.getBoundingClientRect().width || BASE_WIDTH;
    layer.style.fontSize = (12 * clamp(w / BASE_WIDTH, MIN_SCALE, 2)) + 'px';  // カードの寸法は em 指定
  };
  updateScale();
  if ('ResizeObserver' in window) new ResizeObserver(updateScale).observe(stage);
  else window.addEventListener('resize', updateScale);

  /* ---------- カードを押す → 確認 → 着席 / 離席 ---------- */
  let busy = false;
  layer.addEventListener('click', async e => {
    const card = e.target.closest('.seat');
    if (!card || busy) return;

    if (!LOGGED_IN) return showMsg('ログインしてください。', true);
    if (card.classList.contains('taken') && !card.classList.contains('mine')) {
      return showMsg('この席は他の人が使用中です。', true);
    }

    const num    = card.dataset.seat;
    const isMine = card.classList.contains('mine');

    busy = true;
    let confirmed;
    if (isMine) {
      confirmed = await ask({ title: num + ' から離席しますか？', ok: '離席する' });
    } else {
      const moving = mySeat && !(mySeat.floor === FLOOR && mySeat.num === num);
      confirmed = await ask({
        title: num + ' に着席しますか？',
        text: moving ? '現在の席（' + (FLOOR_LABELS[mySeat.floor] || '') + ' ' + mySeat.num + '）は離席になります。' : '',
        ok: '着席する'
      });
    }
    if (!confirmed) { busy = false; return; }

    const body = new FormData();
    body.append('action', 'sit');
    body.append('floor', FLOOR);
    body.append('seat_num', num);

    try {
      const res  = await fetch('main.php', { method: 'POST', body });
      const json = await res.json();
      if (json.seats) applyState(json.seats);       // 成功・失敗どちらでも最新の状況に更新
      if ('mySeat' in json) mySeat = json.mySeat;
      showMsg(json.message || '', !json.ok);
    } catch (err) {
      showMsg('通信に失敗しました。', true);
    } finally {
      busy = false;
    }
  });
})();
</script>
</body>
</html>