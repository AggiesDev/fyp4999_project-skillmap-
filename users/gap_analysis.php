<?php
// Gap analysis results page showing match score, skill breakdown, and recommendations.

require_once __DIR__ . '/../includes/auth_check.php';
skillmap_require_auth(['student']);
$activePage = 'analyse';
$data = skillmap_data()['student']['gap_analysis'];
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Skill Map - Gap Analysis Results</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="/fyp_skillmapsystem/assets/css/style.css" rel="stylesheet">
</head>
<body>
  <?php require __DIR__ . '/includes/navbar.php'; ?>
  <main class="container py-4 py-lg-5">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
      <div>
        <h1 class="fw-bold mb-1">Gap Analysis Results</h1>
        <span class="badge rounded-pill text-bg-primary">💻 <?= htmlspecialchars($data['role'], ENT_QUOTES, 'UTF-8') ?></span>
      </div>
      <a class="btn btn-outline-danger" href="/fyp_skillmapsystem/users/report.php"><i class="bi bi-filetype-pdf me-1"></i>Download PDF</a>
    </div>
    <div class="row g-4 mb-4">
      <div class="col-lg-4"><div class="card h-100"><div class="card-body text-center p-4"><h2 class="h5 fw-bold mb-3">Overall Match</h2><?= skillmap_progress_ring($data['match'], $data['role']) ?><div class="mt-3"><span class="badge text-bg-success-subtle text-success"><?= htmlspecialchars($data['status'], ENT_QUOTES, 'UTF-8') ?></span><div class="small text-muted mt-2">for <?= htmlspecialchars($data['role'], ENT_QUOTES, 'UTF-8') ?></div></div></div></div></div>
      <div class="col-lg-4"><div class="card h-100"><div class="card-body p-4"><h2 class="h5 fw-bold mb-3">Skill Breakdown</h2><div class="mb-3"><div class="d-flex justify-content-between small mb-1"><span class="text-success fw-semibold">Have</span><span><?= $data['summary']['have'] ?></span></div><?= skillmap_percent_bar(57, 'success') ?></div><div class="mb-3"><div class="d-flex justify-content-between small mb-1"><span class="text-warning fw-semibold">Partial</span><span><?= $data['summary']['partial'] ?></span></div><?= skillmap_percent_bar(29, 'warning') ?></div><div><div class="d-flex justify-content-between small mb-1"><span class="text-danger fw-semibold">Missing</span><span><?= $data['summary']['missing'] ?></span></div><?= skillmap_percent_bar(14, 'danger') ?></div></div></div></div>
      <div class="col-lg-4"><div class="card h-100"><div class="card-body p-4"><h2 class="h5 fw-bold mb-3">AI Recommendation</h2><div class="d-flex gap-3"><i class="bi bi-check-circle-fill text-success fs-4"></i><div><div class="fw-semibold"><?= htmlspecialchars($data['verdict'], ENT_QUOTES, 'UTF-8') ?></div><div class="text-muted small mt-2">Focus area: <?= htmlspecialchars($data['focus'], ENT_QUOTES, 'UTF-8') ?></div><div class="text-muted small">Estimated time: <?= htmlspecialchars($data['eta'], ENT_QUOTES, 'UTF-8') ?></div></div></div></div></div></div>
    </div>
    <div class="card"><div class="card-body p-4">
      <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
        <h2 class="h5 fw-bold mb-0">Skill Breakdown</h2>
        <div class="btn-group btn-group-sm"><button type="button" class="btn btn-outline-secondary active" data-skill-status-filter="all">All</button><button type="button" class="btn btn-outline-success" data-skill-status-filter="Have">Have</button><button type="button" class="btn btn-outline-warning" data-skill-status-filter="Partial">Partial</button><button type="button" class="btn btn-outline-danger" data-skill-status-filter="Missing">Missing</button></div>
      </div>
      <div class="table-responsive">
        <table class="table align-middle">
          <thead><tr><th>Skill</th><th>Category</th><th>Your Rating</th><th>Required</th><th>Status</th><th>Gap</th><th>Action</th></tr></thead>
          <tbody>
            <?php foreach ($data['skills'] as $skill): ?>
              <tr data-skill-status="<?= htmlspecialchars($skill['status'], ENT_QUOTES, 'UTF-8') ?>">
                <td class="fw-semibold"><?= htmlspecialchars($skill['skill'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><span class="badge badge-soft rounded-pill"><?= htmlspecialchars($skill['category'], ENT_QUOTES, 'UTF-8') ?></span></td>
                <td><?= skillmap_star_rating($skill['rating']) ?></td>
                <td><?= skillmap_rating_badge($skill['required']) ?></td>
                <td><?= skillmap_status_badge($skill['status']) ?></td>
                <td class="text-danger fw-semibold">+<?= $skill['gap'] ?></td>
                <td><a class="btn btn-sm btn-outline-primary" href="/fyp_skillmapsystem/users/report.php">View Resource</a></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <div class="d-flex flex-wrap gap-3 mt-2">
        <a class="btn btn-success" href="/fyp_skillmapsystem/users/roadmap.php">View Learning Roadmap</a>
        <a class="btn btn-danger" href="/fyp_skillmapsystem/users/report.php">Download PDF</a>
      </div>
    </div></div>
  </main>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="/fyp_skillmapsystem/assets/js/app.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
  <script src="/fyp_skillmapsystem/assets/js/charts.js"></script>
</body>
</html>
