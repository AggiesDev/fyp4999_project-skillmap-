<?php
// Admin skill library page for skill records and usage auditing.

require_once __DIR__ . '/../includes/auth_check.php';
skillmap_require_auth(['admin']);
$activePage = 'skill_library';
$data = skillmap_data()['admin']['skills'];
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Skill Map - Skill Library</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="/fyp_skillmapsystem/assets/css/style.css" rel="stylesheet">
</head>
<body>
  <?php require __DIR__ . '/includes/navbar.php'; ?>
  <main class="container-fluid py-4 py-lg-5">
    <div class="row g-4">
      <div class="col-12">
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4"><div><h1 class="fw-bold mb-1">Skill Library</h1><div class="text-muted">Central catalog of all mapped capabilities</div></div><div class="d-flex gap-2"><a class="btn btn-outline-secondary" href="/fyp_skillmapsystem/admin/permissions.php">Export CSV</a><a class="btn btn-success" href="/fyp_skillmapsystem/admin/reviews.php">Add New Skill</a></div></div>
        <div class="row g-3 mb-4"><div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted small">Total Skills</div><div class="fs-3 fw-bold"><?= count($data) ?></div></div></div></div><div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted small">Technical</div><div class="fs-3 fw-bold"><?= count($data) ?></div></div></div></div><div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted small">Leadership</div><div class="fs-3 fw-bold">12</div></div></div></div><div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted small">Active Skills</div><div class="fs-3 fw-bold">5</div></div></div></div></div>
        <div class="d-flex flex-wrap gap-2 mb-3" data-table-filter-group><?php foreach (['All', 'Technical', 'Leadership', 'Interpersonal', 'Academic'] as $filter): ?><button type="button" class="btn btn-sm btn-outline-primary <?= $filter === 'All' ? 'active' : '' ?>" data-table-filter-target="#skillLibraryTable" data-table-filter="<?= htmlspecialchars(strtolower($filter), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($filter, ENT_QUOTES, 'UTF-8') ?></button><?php endforeach; ?></div>
        <div class="input-group mb-3" style="max-width: 360px;"><span class="input-group-text"><i class="bi bi-search"></i></span><input class="form-control" placeholder="Search skills..."></div>
        <div class="card"><div class="table-responsive"><table id="skillLibraryTable" class="table align-middle mb-0"><thead><tr><th>#</th><th>Skill Name</th><th>Category</th><th>Description</th><th>Difficulty</th><th>Used In Roles</th><th>Status</th><th>Actions</th></tr></thead><tbody><?php foreach ($data as $index => $skill): ?><tr data-filter-value="<?= htmlspecialchars($skill['category'], ENT_QUOTES, 'UTF-8') ?>"><td><?= $index + 1 ?></td><td class="fw-semibold"><?= htmlspecialchars($skill['name'], ENT_QUOTES, 'UTF-8') ?></td><td><span class="badge badge-soft rounded-pill"><?= htmlspecialchars($skill['category'], ENT_QUOTES, 'UTF-8') ?></span></td><td><?= htmlspecialchars($skill['description'], ENT_QUOTES, 'UTF-8') ?></td><td><?= str_repeat('<span class="text-warning"><i class="bi bi-dot"></i></span>', $skill['difficulty']) ?></td><td><?php foreach ($skill['roles'] as $role): ?><span class="badge text-bg-light border me-1 mb-1"><?= htmlspecialchars($role, ENT_QUOTES, 'UTF-8') ?></span><?php endforeach; ?></td><td><?= skillmap_status_badge($skill['status']) ?></td><td><a class="btn btn-sm btn-outline-primary" href="/fyp_skillmapsystem/admin/reviews.php"><i class="bi bi-pencil"></i></a> <a class="btn btn-sm btn-outline-danger" href="/fyp_skillmapsystem/admin/permissions.php"><i class="bi bi-trash"></i></a></td></tr><?php endforeach; ?></tbody></table></div></div>
      </div>
    </div>
  </main>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="/fyp_skillmapsystem/assets/js/app.js"></script>
</body>
</html>
