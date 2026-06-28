<?php
$dataFile = 'records.json';
$records = file_exists($dataFile) ? json_decode(file_get_contents($dataFile), true) : [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // POST: save record
    $record = [
        'id' => time(),
        'date' => $_POST['date'],
        'folder' => $_POST['folder'],
        'color' => $_POST['color'],
        'checks' => $_POST['checks'] ?? [],
        'memo' => $_POST['memo'],
        'tags' => $_POST['tags'],
    ];
    $records[] = $record;
    file_put_contents($dataFile, json_encode($records, JSON_UNESCAPED_UNICODE));
    header('Location: index.php');
    exit;
}

if (isset($_GET['delete'])) {
    $records = array_filter($records, fn($r) => $r['id'] != $_GET['delete']);
    file_put_contents($dataFile, json_encode(array_values($records), JSON_UNESCAPED_UNICODE));
    header('Location: index.php');
    exit;
}

$folders = array_unique(array_column($records, 'folder'));
$selectedFolder = $_GET['folder'] ?? '';
$selectedTag = $_GET['tag'] ?? '';
$selectedMonth = $_GET['month'] ?? date('Y-m');

$filtered = array_filter($records, function($r) use ($selectedFolder, $selectedTag, $selectedMonth) {
    $monthMatch = str_starts_with($r['date'], $selectedMonth);
    $folderMatch = $selectedFolder === '' || $r['folder'] === $selectedFolder;
    $tagMatch = $selectedTag === '' || str_contains($r['tags'], '#' . $selectedTag);
    return $monthMatch && $folderMatch && $tagMatch;
});
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>記録アプリ</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: 'Helvetica Neue', sans-serif; background: #f5f5f5; color: #333; }
header { background: #4A90D9; color: white; padding: 16px 24px; font-size: 1.4rem; font-weight: bold; }
.container { max-width: 900px; margin: 24px auto; padding: 0 16px; }
.card { background: white; border-radius: 12px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
h2 { font-size: 1.1rem; margin-bottom: 16px; color: #4A90D9; }
label { display: block; margin-bottom: 6px; font-size: 0.9rem; color: #666; }
input[type=text], input[type=date], textarea, select {
    width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px; font-size: 0.95rem; margin-bottom: 12px;
}
textarea { height: 80px; resize: vertical; }
.checks { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 12px; }
.checks label { display: flex; align-items: center; gap: 6px; font-size: 0.95rem; color: #333; cursor: pointer; }
.color-options { display: flex; gap: 10px; margin-bottom: 12px; }
.color-options input[type=radio] { display: none; }
.color-options label { width: 32px; height: 32px; border-radius: 50%; cursor: pointer; border: 3px solid transparent; }
.color-options input[type=radio]:checked + label { border-color: #333; }
button[type=submit] {
    background: #4A90D9; color: white; border: none; padding: 10px 24px;
    border-radius: 8px; font-size: 1rem; cursor: pointer; width: 100%;
}
button[type=submit]:hover { background: #357ABD; }
.filters { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 16px; }
.filters a {
    padding: 6px 14px; border-radius: 20px; background: #e0e0e0;
    text-decoration: none; color: #333; font-size: 0.85rem;
}
.filters a.active { background: #4A90D9; color: white; }
.calendar { display: grid; grid-template-columns: repeat(7, 1fr); gap: 4px; }
.cal-header { text-align: center; font-size: 0.8rem; color: #666; padding: 4px; }
.cal-day {
    min-height: 60px; background: #f9f9f9; border-radius: 8px;
    padding: 4px; font-size: 0.8rem; border: 1px solid #eee;
}
.cal-day.today { border-color: #4A90D9; }
.cal-day .day-num { font-weight: bold; color: #333; margin-bottom: 2px; }
.cal-day .dot {
    width: 10px; height: 10px; border-radius: 50%; display: inline-block; margin: 1px;
}
.cal-day.empty { background: transparent; border: none; }
.record-list { display: flex; flex-direction: column; gap: 12px; }
.record-item {
    border-left: 6px solid #ccc; padding: 12px 16px;
    background: #fafafa; border-radius: 8px;
}
.record-item .record-date { font-size: 0.8rem; color: #999; }
.record-item .record-folder { font-size: 0.8rem; background: #e8f0fe; color: #4A90D9; padding: 2px 8px; border-radius: 10px; display: inline-block; margin-bottom: 6px; }
.record-item .record-checks { font-size: 0.9rem; color: #333; margin-bottom: 4px; }
.record-item .record-memo { font-size: 0.9rem; color: #555; }
.record-item .record-tags { font-size: 0.85rem; color: #4A90D9; margin-top: 4px; }
.delete-btn { float: right; background: none; border: none; color: #ccc; cursor: pointer; font-size: 1.1rem; }
.delete-btn:hover { color: #e55; }
.month-nav { display: flex; align-items: center; gap: 12px; margin-bottom: 16px; }
.month-nav a { text-decoration: none; color: #4A90D9; font-size: 1.2rem; }
.month-nav span { font-weight: bold; font-size: 1rem; }
</style>
</head>
<body>
<header>📋 記録アプリ</header>
<div class="container">

  <!-- 入力フォーム -->
  <div class="card">
    <h2>➕ 新しい記録</h2>
    <form method="POST">
      <label>📅 日付</label>
      <input type="date" name="date" value="<?= date('Y-m-d') ?>" required>

      <label>📁 フォルダ</label>
      <input type="text" name="folder" placeholder="例：勉強、運動、仕事" required>

      <label>🎨 色</label>
      <div class="color-options">
        <?php foreach(['#4A90D9','#E74C3C','#2ECC71','#F39C12','#9B59B6','#1ABC9C'] as $c): ?>
        <input type="radio" name="color" id="c<?= $c ?>" value="<?= $c ?>">
        <label for="c<?= $c ?>" style="background:<?= $c ?>"></label>
        <?php endforeach; ?>
      </div>

      <label>✅ チェック項目</label>
      <div class="checks">
        <?php foreach(['完了','確認済み','重要','要復習','継続中'] as $item): ?>
        <label><input type="checkbox" name="checks[]" value="<?= $item ?>"> <?= $item ?></label>
        <?php endforeach; ?>
      </div>

      <label>🏷️ タグ（#で区切る）</label>
      <input type="text" name="tags" placeholder="例：#勉強 #PHP #毎日">

      <label>📝 メモ</label>
      <textarea name="memo" placeholder="メモを入力..."></textarea>

      <button type="submit">💾 保存する</button>
    </form>
  </div>

  <!-- フィルター -->
  <div class="card">
    <h2>🔍 絞り込み</h2>
    <div class="filters">
      <a href="?month=<?= $selectedMonth ?>" class="<?= $selectedFolder===''?'active':'' ?>">すべて</a>
      <?php foreach($folders as $f): ?>
      <a href="?folder=<?= urlencode($f) ?>&month=<?= $selectedMonth ?>" class="<?= $selectedFolder===$f?'active':'' ?>"><?= htmlspecialchars($f) ?></a>
      <?php endforeach; ?>
    </div>

    <!-- タグ一覧 -->
    <?php
    $allTags = [];
    foreach($records as $r) {
        preg_match_all('/#(\S+)/', $r['tags'], $m);
        $allTags = array_merge($allTags, $m[1]);
    }
    $allTags = array_unique($allTags);
    ?>
    <?php if($allTags): ?>
    <div class="filters">
      <a href="?month=<?= $selectedMonth ?>&folder=<?= urlencode($selectedFolder) ?>" class="<?= $selectedTag===''?'active':'' ?>">#すべて</a>
      <?php foreach($allTags as $t): ?>
      <a href="?tag=<?= urlencode($t) ?>&month=<?= $selectedMonth ?>" class="<?= $selectedTag===$t?'active':'' ?>">#<?= htmlspecialchars($t) ?></a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>

  <!-- カレンダー -->
  <div class="card">
    <?php
    [$year, $month] = explode('-', $selectedMonth);
    $prevMonth = date('Y-m', mktime(0,0,0,$month-1,1,$year));
    $nextMonth = date('Y-m', mktime(0,0,0,$month+1,1,$year));
    $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
    $firstDay = date('w', mktime(0,0,0,$month,1,$year));
    ?>
    <div class="month-nav">
      <a href="?month=<?= $prevMonth ?>&folder=<?= urlencode($selectedFolder) ?>">◀</a>
      <span><?= $year ?>年<?= (int)$month ?>月</span>
      <a href="?month=<?= $nextMonth ?>&folder=<?= urlencode($selectedFolder) ?>">▶</a>
    </div>
    <h2>📅 カレンダー</h2>
    <div class="calendar">
      <?php foreach(['日','月','火','水','木','金','土'] as $d): ?>
      <div class="cal-header"><?= $d ?></div>
      <?php endforeach; ?>
      <?php for($i=0; $i<$firstDay; $i++): ?>
      <div class="cal-day empty"></div>
      <?php endfor; ?>
      <?php for($d=1; $d<=$daysInMonth; $d++):
        $dateStr = sprintf('%s-%02d-%02d', $year, $month, $d);
        $dayRecords = array_filter($filtered, fn($r) => $r['date'] === $dateStr);
        $isToday = $dateStr === date('Y-m-d');
      ?>
      <div class="cal-day <?= $isToday?'today':'' ?>">
        <div class="day-num"><?= $d ?></div>
        <?php foreach($dayRecords as $r): ?>
        <span class="dot" style="background:<?= $r['color'] ?: '#ccc' ?>"></span>
        <?php endforeach; ?>
      </div>
      <?php endfor; ?>
    </div>
  </div>

  <!-- 記録一覧 -->
  <div class="card">
    <h2>📋 記録一覧</h2>
    <div class="record-list">
      <?php if(empty($filtered)): ?>
      <p style="color:#999;text-align:center;">記録がありません</p>
      <?php endif; ?>
      <?php foreach(array_reverse(array_values($filtered)) as $r): ?>
      <div class="record-item" style="border-left-color:<?= $r['color'] ?: '#ccc' ?>">
        <a href="?delete=<?= $r['id'] ?>&month=<?= $selectedMonth ?>" class="delete-btn" onclick="return confirm('削除しますか？')">🗑</a>
        <div class="record-date"><?= htmlspecialchars($r['date']) ?></div>
        <div class="record-folder">📁 <?= htmlspecialchars($r['folder']) ?></div>
        <?php if($r['checks']): ?>
        <div class="record-checks">✅ <?= implode('　', $r['checks']) ?></div>
        <?php endif; ?>
        <?php if($r['memo']): ?>
        <div class="record-memo">📝 <?= nl2br(htmlspecialchars($r['memo'])) ?></div>
        <?php endif; ?>
        <?php if($r['tags']): ?>
        <div class="record-tags"><?= htmlspecialchars($r['tags']) ?></div>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

</div>
</body>
</html>