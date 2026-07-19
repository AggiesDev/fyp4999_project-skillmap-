<?php
// Admin category and target role management page.

require_once __DIR__ . '/../includes/auth_check.php';
skillmap_require_auth(['admin']);
$activePage = 'categories';
$data = skillmap_data()['admin']['categories'];
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Skill Map - Categories</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="/fyp_skillmapsystem/assets/css/style.css" rel="stylesheet">
</head>
<body>
  <?php require __DIR__ . '/includes/navbar.php'; ?>
  <main class="container-fluid py-4 py-lg-5">
    <div class="row g-4">
      <div class="col-12">
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4"><div><h1 class="fw-bold mb-1">Manage Categories &amp; Roles</h1><div class="text-muted">Maintain skill taxonomies and benchmarked target roles</div></div><div><a class="btn btn-success" href="/fyp_skillmapsystem/admin/reviews.php">Add New Category</a></div></div>
        <ul class="nav nav-tabs mb-3"><li class="nav-item"><a class="nav-link active" href="/fyp_skillmapsystem/admin/categories.php">Skill Categories</a></li><li class="nav-item"><a class="nav-link" href="/fyp_skillmapsystem/admin/benchmarks.php">Target Roles &amp; Benchmarks</a></li></ul>
        <div class="input-group mb-3" style="max-width: 360px;"><span class="input-group-text"><i class="bi bi-search"></i></span><input class="form-control" placeholder="Search categories..."></div>
        <div class="card"><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>#</th><th>Category Name</th><th>Type</th><th>Subcategories</th><th>Last Updated</th><th>Actions</th></tr></thead><tbody><?php foreach ($data as $index => $category): ?><tr><td><?= $index + 1 ?></td><td><i class="bi <?= htmlspecialchars($category['icon'], ENT_QUOTES, 'UTF-8') ?> me-2 text-primary"></i><?= htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8') ?></td><td><span class="badge text-bg-primary-subtle text-primary"><?= htmlspecialchars($category['type'], ENT_QUOTES, 'UTF-8') ?></span></td><td><?= $category['subcategories'] ?></td><td><?= htmlspecialchars($category['updated'], ENT_QUOTES, 'UTF-8') ?></td><td><button class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></button> <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></td></tr><?php endforeach; ?></tbody></table></div></div>
        <nav class="mt-3"><ul class="pagination justify-content-end"><li class="page-item disabled"><span class="page-link">Previous</span></li><li class="page-item active"><span class="page-link">1</span></li><li class="page-item"><span class="page-link">2</span></li><li class="page-item"><span class="page-link">Next</span></li></ul></nav>
      </div>
    </div>
  </main>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="/fyp_skillmapsystem/assets/js/app.js"></script>
</body>
</html>
