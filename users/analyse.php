<?php
// Category and role selection page for running a new gap analysis.

require_once __DIR__ . '/../includes/auth_check.php';
skillmap_require_auth(['student']);
$activePage = 'analyse';
$data = skillmap_data()['student'];
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Skill Map - Analyse</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="/fyp_skillmapsystem/assets/css/style.css" rel="stylesheet">
</head>
<body>
  <?php require __DIR__ . '/includes/navbar.php'; ?>
  <main class="container py-4 py-lg-5">
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
      <div><h1 class="fw-bold mb-1">Choose Your Target Category</h1><div class="text-muted">Select a role or leadership target for comparison</div></div>
      <div class="input-group" style="max-width: 360px;"><span class="input-group-text"><i class="bi bi-search"></i></span><input type="search" class="form-control" placeholder="Search roles or skills..."></div>
    </div>
    <h2 class="h5 fw-bold mb-3">💼 Career Job Roles</h2>
    <div class="row g-3 mb-4">
      <?php foreach ($data['analyse_roles'] as $role): ?>
        <div class="col-md-6 col-xl-4"><div class="card role-card h-100"><div class="card-body"><div class="d-flex align-items-start gap-3 mb-3"><div class="skillmap-stats-icon"><i class="bi <?= htmlspecialchars($role['icon'], ENT_QUOTES, 'UTF-8') ?>"></i></div><div><div class="fw-bold fs-5"><?= htmlspecialchars($role['name'], ENT_QUOTES, 'UTF-8') ?></div><div class="text-muted small"><?= $role['mapped'] ?> skills mapped</div></div></div><div class="d-flex flex-wrap gap-2 mb-3"><?php foreach ($role['tags'] as $tag): ?><span class="skill-tag"><?= htmlspecialchars($tag, ENT_QUOTES, 'UTF-8') ?></span><?php endforeach; ?></div><a href="/fyp_skillmapsystem/users/gap_analysis.php" class="btn btn-outline-primary w-100">Analyse →</a></div></div></div>
      <?php endforeach; ?>
    </div>
    <div class="section-divider my-4"></div>
    <div class="d-flex align-items-center justify-content-between mb-3"><h2 class="h5 fw-bold mb-0">🔥 Leadership Categories</h2><span class="badge rounded-pill bg-soft-purple text-purple">Exclusive to Skill Map</span></div>
    <div class="row g-3 mb-4">
      <?php foreach ($data['leadership_roles'] as $role): ?>
        <div class="col-md-6 col-xl-4"><div class="card role-card h-100 analysis-card-border purple"><div class="card-body"><div class="d-flex align-items-start gap-3 mb-3"><div class="skillmap-stats-icon bg-soft-purple"><i class="bi <?= htmlspecialchars($role['icon'], ENT_QUOTES, 'UTF-8') ?> text-purple"></i></div><div><div class="fw-bold fs-5"><?= htmlspecialchars($role['name'], ENT_QUOTES, 'UTF-8') ?></div><div class="text-muted small"><?= $role['mapped'] ?> skills mapped</div></div></div><div class="d-flex flex-wrap gap-2 mb-3"><?php foreach ($role['tags'] as $tag): ?><span class="skill-tag bg-soft-purple text-purple"><?= htmlspecialchars($tag, ENT_QUOTES, 'UTF-8') ?></span><?php endforeach; ?></div><a href="/fyp_skillmapsystem/users/gap_analysis.php" class="btn btn-primary w-100">Analyse →</a></div></div></div>
      <?php endforeach; ?>
    </div>
    <div class="alert skillmap-yellow-banner rounded-4 border-0 mb-0"><i class="bi bi-info-circle-fill me-2"></i>You can analyse multiple categories and compare results side by side</div>
  </main>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="/fyp_skillmapsystem/assets/js/app.js"></script>
</body>
</html>
