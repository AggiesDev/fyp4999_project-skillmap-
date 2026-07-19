<?php
// Admin analytics dashboard showing institution-wide adoption and skill gaps.

require_once __DIR__ . '/../includes/auth_check.php';
skillmap_require_auth(['admin']);
$activePage = 'analytics';
$data = skillmap_data()['admin']['analytics'];
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Skill Map - Admin Analytics</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="/fyp_skillmapsystem/assets/css/style.css" rel="stylesheet">
</head>
<body>
  <?php require __DIR__ . '/includes/navbar.php'; ?>
  <div class="container-fluid py-4 py-lg-5">
    <div class="row g-4">
      <div class="col-12">
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4"><div><h1 class="fw-bold mb-1">Analytics Dashboard</h1><div class="text-muted">FDSIT · <?= htmlspecialchars($data['month'], ENT_QUOTES, 'UTF-8') ?></div></div><div class="d-flex gap-2"><a class="btn btn-outline-secondary" href="/fyp_skillmapsystem/admin/permissions.php">Export Report</a><button class="btn btn-primary" type="button" onclick="window.location.reload()">Refresh</button></div></div>
        <div class="row g-3 mb-4">
          <div class="col-md-6 col-xl-3"><div class="card h-100"><div class="card-body"><div class="text-muted small">Total Students</div><div class="fs-3 fw-bold"><?= $data['total_students'] ?></div><div class="text-success small">+<?= $data['new_students'] ?> new this month</div></div></div></div>
          <div class="col-md-6 col-xl-3"><div class="card h-100"><div class="card-body"><div class="text-muted small">Avg Match Score</div><div class="fs-3 fw-bold"><?= $data['avg_match'] ?>%</div><div class="text-success small"><?= htmlspecialchars($data['avg_change'], ENT_QUOTES, 'UTF-8') ?> vs last month</div></div></div></div>
          <div class="col-md-6 col-xl-3"><div class="card h-100"><div class="card-body"><div class="text-muted small">Most Popular Role</div><div class="fs-5 fw-bold"><?= htmlspecialchars($data['popular_role'], ENT_QUOTES, 'UTF-8') ?></div></div></div></div>
          <div class="col-md-6 col-xl-3"><div class="card h-100"><div class="card-body"><div class="text-muted small">Most Missing Skill</div><div class="fs-5 fw-bold"><?= htmlspecialchars($data['missing_skill'], ENT_QUOTES, 'UTF-8') ?></div><div class="text-danger small"><?= $data['missing_skill_pct'] ?>% of students</div></div></div></div>
        </div>
        <div class="row g-4 mb-4">
          <div class="col-lg-6"><div class="card h-100"><div class="card-body p-4"><h2 class="h5 fw-bold mb-3">Top 8 Missing Skills Across Students</h2><div class="d-grid gap-3"><?php foreach ($data['missing_skills'] as $skill): ?><div><div class="d-flex justify-content-between small mb-1"><span><?= htmlspecialchars($skill['name'], ENT_QUOTES, 'UTF-8') ?></span><span><?= $skill['pct'] ?>%</span></div><?= skillmap_percent_bar($skill['pct'], $skill['pct'] > 30 ? 'danger' : 'warning') ?></div><?php endforeach; ?></div></div></div></div>
          <div class="col-lg-6"><div class="card h-100"><div class="card-body p-4"><h2 class="h5 fw-bold mb-3">Category Popularity</h2><div style="height:280px"><canvas id="categoryPopularityChart"></canvas></div></div></div></div>
        </div>
        <div class="card"><div class="card-body p-4"><h2 class="h5 fw-bold mb-3">Programme-wise Skill Readiness</h2><div style="height:320px"><canvas id="programmeReadinessChart"></canvas></div></div></div>
      </div>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
  <script src="/fyp_skillmapsystem/assets/js/app.js"></script>
  <script src="/fyp_skillmapsystem/assets/js/charts.js"></script>
</body>
</html>
