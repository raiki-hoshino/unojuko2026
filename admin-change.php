<?php
//データベース
require "config/database.php"; // ← $pdo（PDO）が使える前提

if ($pdo === null) {
    echo "データベースに接続できません。";
    exit;
}

$uploadDir = __DIR__ . '/uploads';
$allowed = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/gif'  => 'gif',
    'image/webp' => 'webp',
];
$floors = [1 => '1階', 2 => '2階', 3 => '3階'];
$message = '';

/* ===== 座席配置の保存（fetch から呼ばれる） ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_seats') {
    header('Content-Type: application/json; charset=UTF-8');

    $saveFloor = (int)($_POST['floor'] ?? 0);
    $posted = json_decode($_POST['seats'] ?? '[]', true);

    // 受け取ったデータを検証
    $clean = [];
    $valid = isset($floors[$saveFloor]) && is_array($posted);
    if ($valid) {
        foreach ($posted as $s) {
            $num = trim((string)($s['seat_num'] ?? ''));
            if ($num === '' || mb_strlen($num) > 20 || isset($clean[$num])) {
                $valid = false;
                break;
            }
            $clean[$num] = [
                max(0, (int)round($s['x'] ?? 0)),
                max(0, (int)round($s['y'] ?? 0)),
            ];
        }
    }
    if (!$valid) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => '座席データが正しくありません。（番号の重複・空欄など）'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    try {
        $pdo->beginTransaction();

        $st = $pdo->prepare('SELECT seat_num FROM seat WHERE floor = ?');
        $st->execute([$saveFloor]);
        $existing = array_map('strval', $st->fetchAll(PDO::FETCH_COLUMN));

        $upd = $pdo->prepare('UPDATE seat SET seat_x = ?, seat_y = ? WHERE floor = ? AND seat_num = ?');
        $ins = $pdo->prepare('INSERT INTO seat (seat_num, floor, seat_x, seat_y) VALUES (?, ?, ?, ?)');
        foreach ($clean as $num => [$x, $y]) {
            if (in_array((string)$num, $existing, true)) {
                $upd->execute([$x, $y, $saveFloor, (string)$num]);   // 移動
            } else {
                $ins->execute([(string)$num, $saveFloor, $x, $y]);   // 追加
            }
        }

        // 画面から消された座席を削除
        $del = $pdo->prepare('DELETE FROM seat WHERE floor = ? AND seat_num = ?');
        foreach ($existing as $num) {
            if (!isset($clean[$num])) {
                $del->execute([$saveFloor, $num]);
            }
        }

        $pdo->commit();
        echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => '保存に失敗しました。（使用中の座席は削除できない場合があります）'], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

/* ===== 画像の差し替え ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') !== 'save_seats') {
    $f = $_FILES['image'] ?? null;
    $floor = (int)($_POST['floor'] ?? 0);

    if (!isset($floors[$floor])) {
        $message = '階を選択してください。';
    } elseif (!$f || $f['error'] !== UPLOAD_ERR_OK) {
        $message = 'アップロードに失敗しました。';
    } else {
        $info = @getimagesize($f['tmp_name']);
        $mime = $info['mime'] ?? '';
        if (!isset($allowed[$mime])) {
            $message = 'JPEG / PNG / GIF / WebP の画像を選んでください。';
        } else {
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $prefix  = $uploadDir . '/floor' . $floor . '-image';
            $newPath = $prefix . '.' . $allowed[$mime];
            $tmpPath = $prefix . '.new.' . $allowed[$mime];

            // 先に保存し、成功したらその階の古い画像だけを削除
            if (move_uploaded_file($f['tmp_name'], $tmpPath)) {
                foreach (glob($prefix . '.*') as $old) {
                    if ($old !== $tmpPath) {
                        unlink($old);
                    }
                }
                rename($tmpPath, $newPath);
                $message = $floors[$floor] . 'の画像を差し替えました。';
            } else {
                $message = '画像の保存に失敗しました。';
            }
        }
    }
}

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

// 表示する階：画像を差し替えた直後はその階、通常は ?floor=
$floor = (int)($_POST['floor'] ?? $_GET['floor'] ?? 1);
if (!in_array($floor, [1, 2, 3], true)) {
    $floor = 1;
}
$imageUrl = floorImageUrl($floor);

// この階の座席
$st = $pdo->prepare('SELECT seat_num, seat_x, seat_y FROM seat WHERE floor = ? ORDER BY seat_num');
$st->execute([$floor]);
$seats = $st->fetchAll(PDO::FETCH_ASSOC);

// seat_x / seat_y の「初期値（DEFAULT）」を取得：初期値のままの座席は未配置として扱う
$seatDefault = ['seat_x' => null, 'seat_y' => null];
try {
    $st = $pdo->query(
        "SELECT COLUMN_NAME, COLUMN_DEFAULT FROM INFORMATION_SCHEMA.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'seat'
           AND COLUMN_NAME IN ('seat_x', 'seat_y')"
    );
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $col) {
        $d = $col['COLUMN_DEFAULT'];
        if ($d !== null) {
            $d = trim((string)$d, "'\"");   // MariaDB は '0' のように引用符付きで返す
        }
        $seatDefault[$col['COLUMN_NAME']] = ($d === null || !is_numeric($d)) ? null : (float)$d;
    }
} catch (PDOException $e) {
    // 取得できなくても、NULL と 0,0 は未配置として扱うので動作は続行
}

require "header.php";
?>

<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>画像の差し替え</title>
<link rel="stylesheet" href="css/admin-change.css">
</head>
<body>
<div class="layout">
<nav class="side" aria-label="管理者メニュー">
    <small>管理者メニュー</small>
    <a href="admin.php">社員管理</a>
    <a href="admin-change.php" class="on">座席変更</a>
    <a href="admin-data.php">座席データ閲覧</a>
    <hr>
    <a href="main.php" class="back">座席図に戻る</a>
</nav>
<main>
<?php if ($message): ?>
<p><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p>
<?php endif; ?>

<form method="post" enctype="multipart/form-data">
    <label>
        階:
        <select name="floor" required>
            <?php foreach ($floors as $n => $label): ?>
            <option value="<?= $n ?>"<?= $n === $floor ? ' selected' : '' ?>><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <input type="file" name="image" accept="image/*" required>
    <button type="submit">差し替える</button>
</form>

<!-- 上部: 設定バー（階の切り替え・選択中・追加/削除・保存） -->
<div class="seat-toolbar" aria-label="座席の設定">
    <nav class="floor-nav" aria-label="階を選択">
        <?php foreach ([3, 2, 1] as $n): ?>
        <a class="floor-button" href="admin-change.php?floor=<?= $n ?>"
            aria-pressed="<?= $n === $floor ? 'true' : 'false' ?>"><?= $n ?>階</a>
        <?php endforeach; ?>
    </nav>

    <div class="current">
        <span>選択中</span>
        <strong id="selected-seat">―</strong>
    </div>

    <input type="text" id="new-seat-num" maxlength="20" placeholder="追加する座席番号"
        aria-label="追加する座席番号" autocomplete="off">
    <button type="button" class="btn" id="add-seat">＋ 追加</button>
    <button type="button" class="btn" id="delete-seat" disabled>削除</button>

    <button type="button" class="btn primary" id="save-seats">変更を保存</button>
    <p class="save-msg" id="save-msg" role="status"></p>
</div>
<!-- 下部: 未配置の座席置き場（読み込み直後・追加直後の座席がここに並ぶ） -->
<section class="seat-tray" id="seat-tray-wrap" aria-label="未配置の座席">
    <div class="tray-head">
        <h2>未配置の座席</h2>
        <span class="tray-count"><span id="tray-count">0</span>件</span>
    </div>
    <p class="tray-hint">座席カードを上の座席図へドラッグして配置します。配置済みの座席をここへドラッグして戻すこともできます。</p>
    <div class="tray-list" id="seat-tray"></div>
</section>
<!-- 座席表（座席をドラッグして移動） -->
<section class="main-panel" aria-label="<?= $floor ?>階の座席表">
    <div class="image-preview<?= $imageUrl ? ' has-image' : '' ?>">
        <div class="stage<?= $imageUrl ? '' : ' empty' ?>" id="stage">
            <?php if ($imageUrl): ?>
            <img src="<?= htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8') ?>"
                alt="<?= $floor ?>階の座席図" draggable="false">
            <?php else: ?>
            <p><?= $floor ?>階の画像が登録されていません。</p>
            <?php endif; ?>
            <div class="seat-layer" id="seat-layer"></div>
        </div>
    </div>
</section>


</main>
</div>

<script>
(() => {
  const FLOOR = <?= (int)$floor ?>;
  const DATA  = <?= json_encode($seats, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>;
  const DEFAULT_X = <?= json_encode($seatDefault['seat_x']) ?>;   // seat_x の初期値（なければ null）
  const DEFAULT_Y = <?= json_encode($seatDefault['seat_y']) ?>;   // seat_y の初期値（なければ null）

  const stage     = document.getElementById('stage');
  const layer     = document.getElementById('seat-layer');
  const img       = stage.querySelector('img');
  const trayWrap  = document.getElementById('seat-tray-wrap');
  const tray      = document.getElementById('seat-tray');
  const trayCount = document.getElementById('tray-count');
  const selLabel  = document.getElementById('selected-seat');
  const numInput  = document.getElementById('new-seat-num');
  const addBtn    = document.getElementById('add-seat');
  const delBtn    = document.getElementById('delete-seat');
  const saveBtn   = document.getElementById('save-seats');
  const msg       = document.getElementById('save-msg');

  // CSS が読み込まれていなくても動くよう、配置に必要なスタイルはここでも指定する
  stage.style.position = 'relative';
  Object.assign(layer.style, {
    position: 'absolute', left: '0', top: '0', right: '0', bottom: '0', pointerEvents: 'none'
  });

  // 座標は「画像の元サイズ（px）」基準で保存する（表示サイズが変わっても位置がずれない）
  let W = 1000, H = 600;

  // 座席カードの大きさは「座席図の表示幅」に比例して拡縮する（画像との位置関係を保つ）
  //   表示幅が BASE_WIDTH のとき等倍。MIN_SCALE より小さくはしない（読めなくなるため）
  const BASE_WIDTH = 1000;
  const MIN_SCALE  = window.matchMedia('(pointer: coarse)').matches ? 0.7 : 0.5;
  let scale = 1;
  let seats = [];      // { num, x, y, placed, el }  placed=false は「未配置」
  let selected = null;

  const clamp = (v, min, max) => Math.min(max, Math.max(min, v));
  const showMsg = (text, isError = false) => {
    msg.textContent = text;
    msg.classList.toggle('error', isError);
  };
  const inside = (box, e) => {
    const r = box.getBoundingClientRect();
    return e.clientX >= r.left && e.clientX <= r.right && e.clientY >= r.top && e.clientY <= r.bottom;
  };
  const rawStage = e => {            // 範囲で丸めない座標（つかんだ位置の補正用）
    const r = stage.getBoundingClientRect();
    return { x: (e.clientX - r.left) / r.width * W, y: (e.clientY - r.top) / r.height * H };
  };
  const toStage = e => {
    const r = stage.getBoundingClientRect();
    return {
      x: clamp(Math.round((e.clientX - r.left) / r.width  * W), 0, W),
      y: clamp(Math.round((e.clientY - r.top)  / r.height * H), 0, H)
    };
  };

  function select(seat) {
    selected = seat;
    seats.forEach(s => s.el && s.el.classList.toggle('selected', s === seat));
    selLabel.textContent = seat ? seat.num : '―';
    delBtn.disabled = !seat;
  }

  // 座席図の上のカードだけ、表示幅に合わせた大きさにする（未配置エリアは通常サイズ）
  function sizeChip(el, placed) {
    const st = el.style;
    if (!placed) {
      st.minWidth = st.height = st.padding = st.fontSize = st.borderRadius = '';
      return;
    }
    st.minWidth     = (34 * scale) + 'px';
    st.height       = (26 * scale) + 'px';
    st.padding      = '0 ' + (8 * scale) + 'px';
    st.fontSize     = (11 * scale) + 'px';
    st.borderRadius = (8 * scale) + 'px';
  }
  function updateScale() {
    const w = stage.getBoundingClientRect().width || BASE_WIDTH;
    scale = clamp(w / BASE_WIDTH, MIN_SCALE, 2);
    seats.forEach(s => s.el && sizeChip(s.el, s.placed));
  }

  // 座席を「座席図の上」か「未配置エリア」のどちらかに置く
  function applyMode(seat) {
    const el = seat.el;
    if (seat.placed) {
      Object.assign(el.style, {
        position: 'absolute', transform: 'translate(-50%, -50%)',
        left: (seat.x / W * 100) + '%', top: (seat.y / H * 100) + '%'
      });
      if (el.parentNode !== layer) layer.appendChild(el);
      sizeChip(el, true);
    } else {
      Object.assign(el.style, { position: 'static', transform: 'none', left: '', top: '' });
      if (el.parentNode !== tray) tray.appendChild(el);
      sizeChip(el, false);
    }
    trayCount.textContent = seats.filter(s => !s.placed).length;
  }

  function createEl(seat) {
    const el = document.createElement('button');
    el.type = 'button';
    el.className = 'seat';
    el.textContent = seat.num;
    Object.assign(el.style, { pointerEvents: 'auto', touchAction: 'none', userSelect: 'none', cursor: 'grab' });
    seat.el = el;
    applyMode(seat);

    let drag = null;

    el.addEventListener('pointerdown', e => {
      if (e.button > 0) return;
      el.setPointerCapture(e.pointerId);
      select(seat);
      drag = { ghost: null, dx: 0, dy: 0 };
      if (seat.placed) {                       // カードの中心とつかんだ位置の差を覚えておく
        const q = rawStage(e);
        drag.dx = seat.x - q.x;
        drag.dy = seat.y - q.y;
      }
      e.preventDefault();
    });

    el.addEventListener('pointermove', e => {
      if (!drag) return;
      if (seat.placed) {
        // 配置済み：座席図の上でそのまま動かす（未配置エリアの上なら枠を強調）
        const q = rawStage(e);
        seat.x = clamp(Math.round(q.x + drag.dx), 0, W);
        seat.y = clamp(Math.round(q.y + drag.dy), 0, H);
        applyMode(seat);
        trayWrap.classList.toggle('drop', inside(trayWrap, e));
      } else {
        // 未配置：カードのコピーをポインタに追従させる
        if (!drag.ghost) {
          const g = el.cloneNode(true);
          g.classList.add('selected');
          Object.assign(g.style, {
            position: 'fixed', transform: 'translate(-50%, -50%)',
            pointerEvents: 'none', zIndex: '1000', opacity: '.9'
          });
          sizeChip(g, true);
          document.body.appendChild(g);
          drag.ghost = g;
          el.style.opacity = '.4';
        }
        drag.ghost.style.left = e.clientX + 'px';
        drag.ghost.style.top  = e.clientY + 'px';
      }
    });

    const finish = (e, cancelled) => {
      if (!drag) return;
      if (!cancelled) {
        if (seat.placed && inside(trayWrap, e)) {            // 座席図 → 未配置エリアへ戻す
          seat.placed = false; seat.x = seat.y = null;
          applyMode(seat);
        } else if (!seat.placed && drag.ghost && inside(stage, e)) {   // 未配置 → 座席図へ配置
          const p = toStage(e);
          seat.placed = true; seat.x = p.x; seat.y = p.y;
          applyMode(seat);
        }
      }
      if (drag.ghost) drag.ghost.remove();
      el.style.opacity = '';
      trayWrap.classList.remove('drop');
      drag = null;
    };
    el.addEventListener('pointerup', e => finish(e, false));
    el.addEventListener('pointercancel', e => finish(e, true));
  }

  function init() {
    seats = DATA.map(d => {
      const x = d.seat_x === null || d.seat_x === '' ? null : Number(d.seat_x);
      const y = d.seat_y === null || d.seat_y === '' ? null : Number(d.seat_y);
      // 初期値のまま（NULL・0,0・テーブルの初期値と同じ）の座席は「未配置」として下のエリアに並べる
      const isDefault =
        x === null || y === null ||
        (x === 0 && y === 0) ||
        (DEFAULT_X !== null && DEFAULT_Y !== null && x === DEFAULT_X && y === DEFAULT_Y);
      const placed = !isDefault;
      return { num: String(d.seat_num), x: placed ? x : null, y: placed ? y : null, placed, el: null };
    });
    updateScale();
    seats.forEach(createEl);
  }

  // 画像の読み込み後に元サイズを取得してから座席を描画
  const ready = () => {
    if (img && img.naturalWidth) { W = img.naturalWidth; H = img.naturalHeight; }
    init();
  };
  if (!img) ready();
  else if (img.complete) ready();
  else {
    img.addEventListener('load', ready, { once: true });
    img.addEventListener('error', ready, { once: true });
  }

  // 画面幅・向きが変わったら、カードの大きさを追従させる
  if ('ResizeObserver' in window) new ResizeObserver(updateScale).observe(stage);
  else window.addEventListener('resize', updateScale);

  // 空いている場所をクリックしたら選択解除
  stage.addEventListener('pointerdown', e => {
    if (!e.target.closest('.seat')) select(null);
  });

  /* ---------- 追加（未配置エリアに置かれる） ---------- */
  function addSeat() {
    const num = numInput.value.trim();
    if (!num)            return showMsg('座席番号を入力してください。', true);
    if (num.length > 20) return showMsg('座席番号は20文字以内にしてください。', true);
    if (seats.some(s => s.num === num)) return showMsg('その座席番号はこの階にすでにあります。', true);

    const seat = { num, x: null, y: null, placed: false, el: null };
    seats.push(seat);
    createEl(seat);
    select(seat);
    numInput.value = '';
    showMsg('下の「未配置の座席」に追加しました。座席図へドラッグして配置してください。');
  }
  addBtn.addEventListener('click', addSeat);
  numInput.addEventListener('keydown', e => {
    if (e.key === 'Enter') { e.preventDefault(); addSeat(); }
  });

  /* ---------- 削除 ---------- */
  delBtn.addEventListener('click', () => {
    if (!selected) return;
    selected.el.remove();
    seats = seats.filter(s => s !== selected);
    select(null);
    trayCount.textContent = seats.filter(s => !s.placed).length;
    showMsg('削除しました。「変更を保存」を押すと確定します。');
  });

  /* ---------- 保存（未配置の座席は座標 0,0 で保存） ---------- */
  saveBtn.addEventListener('click', async () => {
    const body = new FormData();
    body.append('action', 'save_seats');
    body.append('floor', FLOOR);
    body.append('seats', JSON.stringify(
      seats.map(s => ({ seat_num: s.num, x: s.placed ? s.x : null, y: s.placed ? s.y : null }))
    ));

    saveBtn.disabled = true;
    try {
      const res  = await fetch('admin-change.php', { method: 'POST', body });
      const json = await res.json();
      showMsg(json.ok ? '保存しました。' : (json.error || '保存に失敗しました。'), !json.ok);
    } catch (e) {
      showMsg('通信に失敗しました。', true);
    } finally {
      saveBtn.disabled = false;
    }
  });
})();
</script>
</body>
</html>