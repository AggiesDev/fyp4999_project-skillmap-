<?php
// Learning roadmap page with prioritized skills and progress tracking.

require_once __DIR__ . '/../includes/auth_check.php';
skillmap_require_auth(['student']);
$activePage = 'dashboard';
$data = skillmap_data()['student']['roadmap'];
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Skill Map - Roadmap</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="/fyp_skillmapsystem/assets/css/style.css" rel="stylesheet">
</head>
<body>
  <?php require __DIR__ . '/includes/navbar.php'; ?>
  <main class="container py-4 py-lg-5">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
      <div>
        <h1 class="fw-bold mb-1">My Learning Roadmap · <?= htmlspecialchars($data['role'], ENT_QUOTES, 'UTF-8') ?> · <?= $data['match'] ?>% Match</h1>
        <div class="text-muted"><?= $data['complete'] ?>% Complete</div>
      </div>
      <div style="min-width:260px;" class="w-100 w-md-auto"><?= skillmap_percent_bar($data['complete']) ?></div>
    </div>
    <div class="row g-4">
      <div class="col-lg-8">
        <div class="d-grid gap-4">
          <div class="card left-accent-red"><div class="card-body p-4"><div class="d-flex justify-content-between align-items-center mb-3"><h2 class="h5 fw-bold mb-0">Priority 1 — Missing Skills</h2><span class="badge text-bg-danger">MISSING</span></div><div class="d-grid gap-3"><?php foreach ($data['missing'] as $item): ?><div class="border rounded-4 p-3"><div class="d-flex justify-content-between gap-3"><div><div class="fw-semibold"><?= htmlspecialchars($item['skill'], ENT_QUOTES, 'UTF-8') ?></div><div class="small text-muted"><?= htmlspecialchars($item['resource'], ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars($item['platform'], ENT_QUOTES, 'UTF-8') ?></div></div><div class="text-end"><div class="small text-muted"><?= htmlspecialchars($item['duration'], ENT_QUOTES, 'UTF-8') ?></div><?= $item['free'] ? '<span class="badge text-bg-success">Free</span>' : '<span class="badge text-bg-light border">Paid</span>' ?></div></div><div class="progress mt-3"><div class="progress-bar bg-danger" style="width: <?= (int) $item['progress'] ?>%"></div></div><div class="d-flex gap-2 mt-3"><a class="btn btn-primary btn-sm" href="/fyp_skillmapsystem/users/report.php">Start Learning</a><a class="btn btn-outline-success btn-sm" href="/fyp_skillmapsystem/users/progress.php">Mark Complete</a></div></div><?php endforeach; ?></div></div></div>
          <div class="card left-accent-yellow"><div class="card-body p-4"><div class="d-flex justify-content-between align-items-center mb-3"><h2 class="h5 fw-bold mb-0">Priority 2 — Partially Have</h2><span class="badge text-bg-warning">PARTIAL</span></div><div class="d-grid gap-3"><?php foreach ($data['partial'] as $item): ?><div class="border rounded-4 p-3"><div class="d-flex justify-content-between gap-3"><div><div class="fw-semibold"><?= htmlspecialchars($item['skill'], ENT_QUOTES, 'UTF-8') ?></div><div class="small text-muted"><?= htmlspecialchars($item['resource'], ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars($item['platform'], ENT_QUOTES, 'UTF-8') ?></div></div><div class="text-end"><div class="small text-muted"><?= htmlspecialchars($item['duration'], ENT_QUOTES, 'UTF-8') ?></div><?= $item['free'] ? '<span class="badge text-bg-success">Free</span>' : '<span class="badge text-bg-light border">Paid</span>' ?></div></div><div class="progress mt-3"><div class="progress-bar bg-warning" style="width: <?= (int) $item['progress'] ?>%"></div></div><div class="d-flex gap-2 mt-3"><a class="btn btn-primary btn-sm" href="/fyp_skillmapsystem/users/report.php">Start Learning</a><a class="btn btn-outline-success btn-sm" href="/fyp_skillmapsystem/users/progress.php">Mark Complete</a></div></div><?php endforeach; ?></div></div></div>
          <div class="card left-accent-green"><div class="card-body p-4"><div class="d-flex justify-content-between align-items-center mb-3"><h2 class="h5 fw-bold mb-0">Section 3 — Completed Skills</h2><button type="button" class="btn btn-sm btn-outline-secondary" data-toggle-panel="completedAccordion">Collapse</button></div><div class="accordion" id="completedAccordion"><?php foreach ($data['completed'] as $index => $item): ?><div class="accordion-item"><h2 class="accordion-header"><button class="accordion-button <?= $index === 0 ? '' : 'collapsed' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#done<?= $index ?>"><?= htmlspecialchars($item['skill'], ENT_QUOTES, 'UTF-8') ?></button></h2><div id="done<?= $index ?>" class="accordion-collapse collapse <?= $index === 0 ? 'show' : '' ?>" data-bs-parent="#completedAccordion"><div class="accordion-body"><div class="small text-muted"><?= htmlspecialchars($item['resource'], ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars($item['platform'], ENT_QUOTES, 'UTF-8') ?></div><div class="progress mt-3"><div class="progress-bar bg-success" style="width: 100%"></div></div></div></div></div><?php endforeach; ?></div></div></div>
        </div>
      </div>
      <div class="col-lg-4">
        <div class="skillmap-right-rail d-grid gap-4">
          <div class="card"><div class="card-body p-4 text-center"><h2 class="h5 fw-bold mb-3">Roadmap Summary</h2><?= skillmap_progress_ring($data['complete']) ?><div class="row g-3 mt-3 text-start"><div class="col-4"><div class="small text-danger">Missing</div><div class="fw-bold"><?= $data['summary']['missing'] ?></div></div><div class="col-4"><div class="small text-warning">Partial</div><div class="fw-bold"><?= $data['summary']['partial'] ?></div></div><div class="col-4"><div class="small text-success">Completed</div><div class="fw-bold"><?= $data['summary']['completed'] ?></div></div></div><hr><div class="small text-muted"><?= $data['hours'] ?> hours estimated · <?= $data['days'] ?> days at a steady pace</div><a href="/fyp_skillmapsystem/users/analyse.php" class="link-primary text-decoration-none d-inline-block mt-2">Switch Target Role</a></div></div>
        </div>
      </div>
    </div>
  </main>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="/fyp_skillmapsystem/assets/js/app.js"></script>
</body>
</html>
