<?php
// Admin benchmark editor for target role skill requirements.

require_once __DIR__ . '/../includes/auth_check.php';
skillmap_require_auth(['admin']);
$activePage = 'benchmarks';
$data = skillmap_data()['admin']['benchmarks'];
$selectedRole = trim((string) ($_GET['role'] ?? $data['selected']));
$roleNames = array_column($data['roles'], 'name');
if (!in_array($selectedRole, $roleNames, true)) {
  $selectedRole = $data['selected'];
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Skill Map - Benchmarks</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="/fyp_skillmapsystem/assets/css/style.css" rel="stylesheet">
</head>
<body>
  <?php require __DIR__ . '/includes/navbar.php'; ?>
  <main class="container-fluid py-4 py-lg-5">
    <div class="row g-4">
      <div class="col-lg-3">
        <div class="card rounded-4"><div class="card-body p-3 p-lg-4"><div class="fw-bold mb-3">Roles</div><div class="d-grid gap-2"><?php foreach ($data['roles'] as $role): ?><a class="btn text-start <?= $role['name'] === $selectedRole ? 'btn-primary' : 'btn-outline-secondary' ?>" href="/fyp_skillmapsystem/admin/benchmarks.php?role=<?= urlencode($role['name']) ?>"><div class="d-flex justify-content-between"><span><?= htmlspecialchars($role['name'], ENT_QUOTES, 'UTF-8') ?></span><span class="badge text-bg-light border"><?= $role['type'] ?></span></div><div class="small opacity-75"><?= $role['skills'] ?> skills</div></a><?php endforeach; ?></div><div class="card mt-4"><div class="card-body text-center"><h2 class="h6 fw-bold">Role Summary</h2><?= skillmap_progress_ring(78) ?><div class="small text-muted mt-2">Avg required rating 4.1 · 5 critical skills</div></div></div></div></div>
      </div>
      <div class="col-lg-9">
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4"><div><h1 class="fw-bold mb-1">Benchmark Manager</h1><div class="text-muted">Edit required ratings for selected role benchmarks</div></div><div class="d-flex gap-2"><button class="btn btn-outline-secondary" type="button" onclick="window.print()">Export</button><button class="btn btn-success" type="button" data-href="/fyp_skillmapsystem/admin/permissions.php">Save All Changes</button></div></div>
        <div class="card"><div class="card-body p-4"><div class="d-flex justify-content-between align-items-center mb-3"><h2 class="h5 fw-bold mb-0"><?= htmlspecialchars($selectedRole, ENT_QUOTES, 'UTF-8') ?></h2><a class="btn btn-primary btn-sm" href="/fyp_skillmapsystem/admin/reviews.php">Add Skill</a></div><div class="table-responsive"><table class="table align-middle"><thead><tr><th>Skill Name</th><th>Category</th><th>Required Rating</th><th>Priority</th><th>Actions</th></tr></thead><tbody><?php foreach ($data['selected_skills'] as $skill): ?><tr><td class="fw-semibold"><?= htmlspecialchars($skill['name'], ENT_QUOTES, 'UTF-8') ?></td><td><span class="badge badge-soft rounded-pill"><?= htmlspecialchars($skill['category'], ENT_QUOTES, 'UTF-8') ?></span></td><td><div class="btn-group btn-group-sm"><button type="button" class="btn btn-outline-secondary">-</button><?php for ($i = 1; $i <= 5; $i++): ?><button type="button" class="btn <?= $i <= $skill['required'] ? 'btn-primary' : 'btn-outline-secondary' ?>"><?= $i ?></button><?php endfor; ?><button type="button" class="btn btn-outline-secondary">+</button></div></td><td><span class="badge <?= $skill['priority'] === 'Critical' ? 'text-bg-danger' : 'text-bg-warning' ?>"><?= htmlspecialchars($skill['priority'], ENT_QUOTES, 'UTF-8') ?></span></td><td><a class="btn btn-sm btn-outline-danger" href="/fyp_skillmapsystem/admin/permissions.php"><i class="bi bi-trash"></i></a></td></tr><?php endforeach; ?></tbody></table></div><div class="d-flex justify-content-between align-items-center"><div class="text-muted small">5 skills · Avg required 4.1</div><button class="btn btn-primary" type="button" onclick="window.location.reload()">Save Benchmarks</button></div></div></div><div class="alert skillmap-info-banner rounded-4 mt-4 mb-0"><strong>Required Rating</strong> indicates the expected mastery level students should reach before the role is considered a strong match.</div>
      </div>
    </div>
  </main>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="/fyp_skillmapsystem/assets/js/app.js"></script>
</body>
</html>
