<?php
// Target role selection page for running a new gap analysis.

require_once __DIR__ . '/../includes/auth_check.php';
skillmap_require_auth(['student']);

$activePage = 'analyse';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $targetRoleId = (int) ($_POST['target_role_id'] ?? 0);
    $stmt = $pdo->prepare('SELECT id FROM career_roles WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $targetRoleId]);

    if ($stmt->fetch()) {
        $_SESSION['target_role_id'] = $targetRoleId;
        header('Location: /fyp_skillmapsystem/users/gap_analysis.php');
        exit;
    }

    $error = 'Please choose a valid target role.';
}

$stmt = $pdo->query(
    'SELECT cr.id, cr.name, cr.type, cr.description, COALESCE(sc.icon, CASE WHEN cr.type = "Lead" THEN "bi-people-fill" ELSE "bi-briefcase-fill" END) AS icon,
            COUNT(rb.id) AS mapped_count
     FROM career_roles cr
     LEFT JOIN skill_categories sc ON sc.name = cr.name AND sc.type = "Target Role"
     LEFT JOIN role_skill_benchmarks rb ON rb.role_id = cr.id
     GROUP BY cr.id, cr.name, cr.type, cr.description, sc.icon
     ORDER BY cr.type, cr.name'
);
$roles = $stmt->fetchAll();
$rolesByType = ['Career' => [], 'Lead' => []];
foreach ($roles as $role) {
    $rolesByType[$role['type']][] = $role;
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Skill Map - Select Target Role</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="/fyp_skillmapsystem/assets/css/style.css" rel="stylesheet">
</head>
<body>
  <?php require __DIR__ . '/includes/navbar.php'; ?>
  <main class="container py-4 py-lg-5">
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
      <div>
        <h1 class="fw-bold mb-1">Choose Your Target Role</h1>
        <div class="text-muted">Select a career or leadership target for comparison</div>
      </div>
      <a class="btn btn-outline-secondary" href="/fyp_skillmapsystem/users/skills_assessment.php">
        <i class="bi bi-stars me-1"></i>Update Assessment
      </a>
    </div>

    <?php if ($error !== ''): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <?php if ($roles === []): ?>
      <div class="alert alert-light border">No target roles are available yet.</div>
    <?php else: ?>
      <div class="skillmap-search mb-4">
        <i class="bi bi-search"></i>
        <input class="form-control" type="search" placeholder="Search roles, type, description, or mapped skills" data-search-input data-search-target="#targetRoleSearch">
      </div>
      <ul class="nav nav-tabs mb-4" role="tablist">
        <li class="nav-item" role="presentation"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#careerRoles" type="button">Career Roles</button></li>
        <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#leadRoles" type="button">Leadership Roles</button></li>
      </ul>

      <div class="tab-content" id="targetRoleSearch">
        <?php foreach (['Career' => 'careerRoles', 'Lead' => 'leadRoles'] as $type => $paneId): ?>
          <div class="tab-pane fade <?= $type === 'Career' ? 'show active' : '' ?>" id="<?= $paneId ?>">
            <?php if ($rolesByType[$type] === []): ?>
              <div class="alert alert-light border">No <?= strtolower($type) ?> roles are available yet.</div>
            <?php else: ?>
              <div class="row g-3">
                <?php foreach ($rolesByType[$type] as $role): ?>
                  <div class="col-md-6 col-xl-4" data-search-item data-search-text="<?= htmlspecialchars($role['name'] . ' ' . $role['type'] . ' ' . $role['description'] . ' ' . $role['mapped_count'] . ' skills mapped', ENT_QUOTES, 'UTF-8') ?>">
                    <form method="post" class="card role-card h-100">
                      <input type="hidden" name="target_role_id" value="<?= (int) $role['id'] ?>">
                      <div class="card-body d-flex flex-column">
                        <div class="d-flex align-items-start gap-3 mb-3">
                          <div class="skillmap-stats-icon">
                            <i class="bi <?= htmlspecialchars($role['icon'], ENT_QUOTES, 'UTF-8') ?>"></i>
                          </div>
                          <div>
                            <div class="fw-bold fs-5"><?= htmlspecialchars($role['name'], ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="text-muted small"><?= (int) $role['mapped_count'] ?> skills mapped</div>
                          </div>
                        </div>
                        <p class="text-muted small flex-grow-1"><?= htmlspecialchars((string) ($role['description'] ?? 'No description available.'), ENT_QUOTES, 'UTF-8') ?></p>
                        <button class="btn <?= $type === 'Career' ? 'btn-outline-primary' : 'btn-primary' ?> w-100" type="submit">
                          Analyse <i class="bi bi-arrow-right ms-1"></i>
                        </button>
                      </div>
                    </form>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
        <div class="alert alert-light border d-none" data-search-empty>No matching target roles found.</div>
      </div>
    <?php endif; ?>
  </main>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="/fyp_skillmapsystem/assets/js/app.js"></script>
</body>
</html>
