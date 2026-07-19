<?php
// Progress dashboard showing trends, badges, radar comparison, and history.

require_once __DIR__ . '/../includes/auth_check.php';
skillmap_require_auth(['student']);
$activePage = 'progress';
$data = skillmap_data()['student']['progress'];
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Skill Map - Progress</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="/fyp_skillmapsystem/assets/css/style.css" rel="stylesheet">
</head>
<body>
  <?php require __DIR__ . '/includes/navbar.php'; ?>
  <main class="container py-4 py-lg-5">
    <div class="row g-4 mb-4">
      <div class="col-lg-4"><div class="card h-100"><div class="card-body p-4"><div class="d-flex justify-content-between align-items-center mb-3"><h2 class="h5 fw-bold mb-0">Match Score Trend</h2><span class="badge text-bg-success-subtle text-success">+<?= $data['delta_month'] ?>% this month</span></div><div style="height:240px"><canvas id="progressTrendChart"></canvas></div></div></div></div>
      <div class="col-lg-4"><div class="card h-100"><div class="card-body p-4"><h2 class="h5 fw-bold mb-3">Skills Improved</h2><div class="display-6 fw-bold mb-2"><?= count($data['skills_improved']) ?></div><ul class="list-group list-group-flush"><?php foreach ($data['skills_improved'] as $item): ?><li class="list-group-item d-flex justify-content-between px-0"><span><?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?></span><span class="text-success fw-semibold"><?= htmlspecialchars($item['delta'], ENT_QUOTES, 'UTF-8') ?></span></li><?php endforeach; ?></ul></div></div></div>
      <div class="col-lg-4"><div class="card h-100"><div class="card-body p-4"><div class="d-flex align-items-start gap-3"><i class="bi bi-fire fs-2 text-danger"></i><div><h2 class="h5 fw-bold mb-1">Learning Streak</h2><div class="display-6 fw-bold"><?= $data['streak'] ?> days</div></div></div><div class="d-flex gap-1 mt-4"><?php for ($i = 1; $i <= 7; $i++): ?><span class="timeline-dot <?= $i <= 5 ? 'bg-success' : 'bg-secondary-subtle' ?>"></span><?php endfor; ?></div></div></div></div>
    </div>
    <div class="row g-4 mb-4">
      <div class="col-lg-8"><div class="card h-100"><div class="card-body p-4"><h2 class="h5 fw-bold mb-3">Achievements &amp; Badges</h2><div class="row g-3"><?php foreach ($data['badges'] as $badge): ?><div class="col-md-3"><div class="card h-100 border-0 <?= $badge['unlocked'] ? 'bg-light' : 'bg-light opacity-50' ?>"><div class="card-body text-center"><div class="skillmap-stats-icon mx-auto mb-3"><i class="bi bi-award"></i></div><div class="fw-semibold"><?= htmlspecialchars($badge['name'], ENT_QUOTES, 'UTF-8') ?></div><div class="small text-muted"><?= $badge['progress'] ?>% progress</div></div></div></div><?php endforeach; ?></div></div></div></div>
      <div class="col-lg-4"><div class="card h-100"><div class="card-body p-4"><h2 class="h5 fw-bold mb-3">Skill Radar vs Target Benchmark</h2><div style="height:300px"><canvas id="skillRadarChart"></canvas></div></div></div></div>
    </div>
    <div class="card"><div class="card-body p-4"><h2 class="h5 fw-bold mb-3">Analysis History</h2><div class="table-responsive"><table class="table align-middle"><thead><tr><th>Date</th><th>Target Role</th><th>Score</th><th>Change</th></tr></thead><tbody><?php foreach ($data['history'] as $row): ?><tr><td><?= htmlspecialchars($row['date'], ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($row['role'], ENT_QUOTES, 'UTF-8') ?></td><td><?= $row['score'] ?>%</td><td class="<?= str_starts_with($row['change'], '+') ? 'text-success' : 'text-danger' ?> fw-semibold"><?= htmlspecialchars($row['change'], ENT_QUOTES, 'UTF-8') ?></td></tr><?php endforeach; ?></tbody></table></div></div></div>
  </main>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
  <script src="/fyp_skillmapsystem/assets/js/app.js"></script>
  <script src="/fyp_skillmapsystem/assets/js/charts.js"></script>
</body>
</html>
