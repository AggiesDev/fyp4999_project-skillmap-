<?php
// Admin user management page for student tracking and access administration.

require_once __DIR__ . '/../includes/auth_check.php';
skillmap_require_auth(['admin']);
$activePage = 'users';
$data = skillmap_data()['admin']['users'];
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Skill Map - Users</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="/fyp_skillmapsystem/assets/css/style.css" rel="stylesheet">
</head>
<body>
  <?php require __DIR__ . '/includes/navbar.php'; ?>
  <main class="container-fluid py-4 py-lg-5">
    <div class="row g-4">
      <div class="col-12">
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4"><div><h1 class="fw-bold mb-1">User Management</h1><div class="text-muted">Monitor student participation and current readiness</div></div><a class="btn btn-outline-secondary" href="/fyp_skillmapsystem/admin/permissions.php">Export CSV</a></div>
        <div class="row g-3 mb-4"><div class="col-md-6 col-xl-3"><div class="card"><div class="card-body"><div class="text-muted small">Total Students</div><div class="fs-3 fw-bold"><?= count($data) ?></div><div class="text-success small">+<?= count(array_filter($data, fn($user) => $user['status'] === 'Active')) ?> active count</div></div></div></div><div class="col-md-6 col-xl-3"><div class="card"><div class="card-body"><div class="text-muted small">Avg Match Score</div><div class="fs-3 fw-bold">76%</div></div></div></div><div class="col-md-6 col-xl-3"><div class="card"><div class="card-body"><div class="text-muted small">Avg Analyses/Student</div><div class="fs-3 fw-bold">4.0</div></div></div></div><div class="col-md-6 col-xl-3"><div class="card"><div class="card-body"><div class="text-muted small">Top Streak</div><div class="fs-3 fw-bold">12 days</div><div class="small text-muted">Chong Pei</div></div></div></div></div>
        <div class="row g-3 mb-3"><div class="col-lg-5"><div class="input-group"><span class="input-group-text"><i class="bi bi-search"></i></span><input class="form-control" placeholder="Search users..."></div></div><div class="col-lg-3"><select class="form-select"><option>All Programmes</option><option>Information Systems</option><option>Software Engineering</option><option>Computer Science</option></select></div><div class="col-lg-4"><div class="btn-group w-100" data-table-filter-group><button type="button" class="btn btn-outline-secondary active" data-table-filter-target="#userTable" data-table-filter="all">All</button><button type="button" class="btn btn-outline-success" data-table-filter-target="#userTable" data-table-filter="active">Active</button><button type="button" class="btn btn-outline-secondary" data-table-filter-target="#userTable" data-table-filter="inactive">Inactive</button></div></div></div>
        <div class="card"><div class="table-responsive"><table id="userTable" class="table align-middle mb-0"><thead><tr><th>#</th><th>Student</th><th>Programme</th><th>Analyses</th><th>Best Match</th><th>Top Role</th><th>Streak</th><th>Last Active</th><th>Status</th><th>Actions</th></tr></thead><tbody><?php foreach ($data as $index => $user): ?><tr data-filter-value="<?= htmlspecialchars(strtolower($user['status']), ENT_QUOTES, 'UTF-8') ?>"><td><?= $index + 1 ?></td><td><div class="d-flex align-items-center gap-3"><div class="avatar-circle bg-primary"><?= strtoupper(substr($user['name'], 0, 2)) ?></div><div><div class="fw-semibold"><?= htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8') ?></div><div class="text-muted small"><?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8') ?></div></div></div></td><td><span class="badge badge-soft rounded-pill"><?= htmlspecialchars($user['programme'], ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars($user['year'], ENT_QUOTES, 'UTF-8') ?></span></td><td><?= $user['analyses'] ?></td><td><div class="d-flex align-items-center gap-2"><div class="progress flex-grow-1" style="min-width:120px;"><div class="progress-bar bg-primary" style="width: <?= $user['best_match'] ?>%"></div></div><span class="small"><?= $user['best_match'] ?>%</span></div></td><td><?= htmlspecialchars($user['top_role'], ENT_QUOTES, 'UTF-8') ?></td><td><i class="bi bi-fire text-danger me-1"></i><?= $user['streak'] ?> days</td><td><?= htmlspecialchars($user['last_active'], ENT_QUOTES, 'UTF-8') ?></td><td><?= skillmap_status_badge($user['status']) ?></td><td><a class="btn btn-sm btn-outline-primary" href="/fyp_skillmapsystem/admin/reviews.php?student=<?= urlencode($user['email']) ?>"><i class="bi bi-person-gear"></i></a> <a class="btn btn-sm btn-outline-danger" href="/fyp_skillmapsystem/admin/permissions.php"><i class="bi bi-shield-lock"></i></a></td></tr><?php endforeach; ?></tbody></table></div></div>
        <nav class="mt-3"><ul class="pagination justify-content-end"><li class="page-item disabled"><span class="page-link">Previous</span></li><li class="page-item active"><span class="page-link">1</span></li><li class="page-item"><span class="page-link">2</span></li><li class="page-item"><span class="page-link">Next</span></li></ul></nav>
      </div>
    </div>
  </main>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="/fyp_skillmapsystem/assets/js/app.js"></script>
</body>
</html>
