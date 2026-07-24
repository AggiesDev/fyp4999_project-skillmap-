<?php
// Student benchmark reference page.

require_once __DIR__ . '/../includes/auth_check.php';
skillmap_require_auth(['student']);

$activePage = 'benchmarks';

$categoryRows = $pdo->query(
    'SELECT c.name, c.icon, COUNT(s.id) AS skill_count
     FROM skill_categories c
     LEFT JOIN skills s ON s.category_id = c.id AND s.status = "Active"
     WHERE c.type = "Skill Category"
     GROUP BY c.id
     ORDER BY c.name'
)->fetchAll();

$benchmarkRows = $pdo->query(
    'SELECT cr.id AS role_id, cr.name AS role_name, cr.type, cr.description,
            s.name AS skill_name, s.description AS skill_description, c.name AS category, c.icon,
            rb.required_rating, rb.priority
     FROM career_roles cr
     LEFT JOIN role_skill_benchmarks rb ON rb.role_id = cr.id
     LEFT JOIN skills s ON s.id = rb.skill_id
     LEFT JOIN skill_categories c ON c.id = s.category_id
     ORDER BY cr.type, cr.name, FIELD(rb.priority, "Critical", "Important", "Optional"), rb.required_rating DESC, s.name'
)->fetchAll();

$roles = [];
foreach ($benchmarkRows as $row) {
    $roleId = (int) $row['role_id'];
    if (!isset($roles[$roleId])) {
        $roles[$roleId] = [
            'name' => $row['role_name'],
            'type' => $row['type'],
            'description' => $row['description'],
            'benchmarks' => [],
        ];
    }
    if (!empty($row['skill_name'])) {
        $roles[$roleId]['benchmarks'][] = $row;
    }
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
  <main class="container py-4 py-lg-5">
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
      <div>
        <h1 class="fw-bold mb-1">Skill Benchmarks</h1>
        <div class="text-muted">Review the required skills used by your gap analysis</div>
      </div>
      <a class="btn btn-outline-primary" href="/fyp_skillmapsystem/users/analyse.php">
        <i class="bi bi-search me-1"></i>Choose Target Role
      </a>
    </div>

    <div class="row g-3 mb-4">
      <?php foreach ($categoryRows as $category): ?>
        <div class="col-6 col-lg-3">
          <div class="card h-100">
            <div class="card-body">
              <div class="d-flex align-items-center gap-3">
                <div class="skillmap-stats-icon"><i class="bi <?= htmlspecialchars($category['icon'], ENT_QUOTES, 'UTF-8') ?>"></i></div>
                <div>
                  <div class="fw-semibold"><?= htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8') ?></div>
                  <div class="small text-muted"><?= (int) $category['skill_count'] ?> active skills</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <div data-search-scope>
      <div class="skillmap-search mb-4">
        <i class="bi bi-search"></i>
        <input class="form-control" type="search" placeholder="Search roles, skills, categories, priority, or descriptions" data-search-input>
      </div>

      <?php if ($roles === []): ?>
        <div class="alert alert-light border">No benchmark data is available yet.</div>
      <?php else: ?>
        <div class="row g-4">
          <?php foreach ($roles as $role): ?>
            <?php
              $benchmarkTextParts = array_map(
                  static fn(array $benchmark): string => $benchmark['skill_name'] . ' ' . $benchmark['category'] . ' ' . $benchmark['priority'] . ' ' . $benchmark['required_rating'] . '/5 ' . $benchmark['skill_description'],
                  $role['benchmarks']
              );
            ?>
            <div class="col-lg-6" data-search-item data-search-text="<?= htmlspecialchars($role['name'] . ' ' . $role['type'] . ' ' . $role['description'] . ' ' . implode(' ', $benchmarkTextParts), ENT_QUOTES, 'UTF-8') ?>">
              <div class="card h-100">
                <div class="card-body p-4">
                  <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
                    <div>
                      <h2 class="h5 fw-bold mb-1"><?= htmlspecialchars($role['name'], ENT_QUOTES, 'UTF-8') ?></h2>
                      <div class="text-muted small"><?= htmlspecialchars((string) ($role['description'] ?? 'No description available.'), ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                    <span class="badge text-bg-light border"><?= htmlspecialchars($role['type'], ENT_QUOTES, 'UTF-8') ?></span>
                  </div>

                  <?php if ($role['benchmarks'] === []): ?>
                    <div class="alert alert-light border mb-0">No skills mapped yet.</div>
                  <?php else: ?>
                    <div class="table-responsive">
                      <table class="table align-middle mb-0">
                        <thead><tr><th>Skill</th><th>Category</th><th>Required</th><th>Priority</th></tr></thead>
                        <tbody>
                          <?php foreach ($role['benchmarks'] as $benchmark): ?>
                            <tr>
                              <td>
                                <div class="fw-semibold"><?= htmlspecialchars($benchmark['skill_name'], ENT_QUOTES, 'UTF-8') ?></div>
                                <div class="small text-muted"><?= htmlspecialchars((string) $benchmark['skill_description'], ENT_QUOTES, 'UTF-8') ?></div>
                              </td>
                              <td><span class="badge badge-soft rounded-pill"><i class="bi <?= htmlspecialchars($benchmark['icon'], ENT_QUOTES, 'UTF-8') ?> me-1"></i><?= htmlspecialchars($benchmark['category'], ENT_QUOTES, 'UTF-8') ?></span></td>
                              <td><?= (int) $benchmark['required_rating'] ?>/5</td>
                              <td><span class="badge text-bg-light border"><?= htmlspecialchars($benchmark['priority'], ENT_QUOTES, 'UTF-8') ?></span></td>
                            </tr>
                          <?php endforeach; ?>
                        </tbody>
                      </table>
                    </div>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
        <div class="alert alert-light border mt-4 d-none" data-search-empty>No matching benchmarks found.</div>
      <?php endif; ?>
    </div>
    <?php require __DIR__ . '/../includes/footer.php'; ?>
  </main>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="/fyp_skillmapsystem/assets/js/app.js"></script>
</body>
</html>
