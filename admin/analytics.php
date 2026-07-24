<?php
// Aggregate analytics dashboard for admins and permitted reviewers.

require_once __DIR__ . '/../includes/auth_check.php';
skillmap_require_permission('view_admin_dashboard');

$activePage = 'analytics';
$monthLabel = date('F Y');
$canManageUsers = skillmap_user_can('manage_users');
$canReviewSkills = skillmap_user_can('review_student_skills');
$canManageSkills = skillmap_user_can('manage_skills');
$canManageRoles = skillmap_user_can('manage_roles');
$canViewGapAnalytics = $canReviewSkills || $canManageSkills;

$totalStudents = (int) $pdo->query('SELECT COUNT(*) FROM users WHERE role = "student"')->fetchColumn();
$newStudentsStmt = $pdo->query('SELECT COUNT(*) FROM users WHERE role = "student" AND created_at >= CURDATE() - INTERVAL 30 DAY');
$newStudents = (int) $newStudentsStmt->fetchColumn();
$avgMatch = (int) $pdo->query('SELECT COALESCE(ROUND(AVG(match_score)), 0) FROM analyses')->fetchColumn();
$analysisCount = (int) $pdo->query('SELECT COUNT(*) FROM analyses')->fetchColumn();

$popularRole = $pdo->query(
    'SELECT cr.name, COUNT(*) AS total
     FROM analyses a
     INNER JOIN career_roles cr ON cr.id = a.target_role_id
     GROUP BY cr.id
     ORDER BY total DESC
     LIMIT 1'
)->fetch();

$missingSkill = $pdo->query(
    'SELECT s.name, COUNT(*) AS total
     FROM analysis_results ar
     INNER JOIN skills s ON s.id = ar.skill_id
     WHERE ar.status = "Missing"
     GROUP BY ar.skill_id
     ORDER BY total DESC
     LIMIT 1'
)->fetch();

$missingSkills = $pdo->query(
    'SELECT s.name, COUNT(*) AS total
     FROM analysis_results ar
     INNER JOIN skills s ON s.id = ar.skill_id
     WHERE ar.status = "Missing"
     GROUP BY ar.skill_id
     ORDER BY total DESC, s.name
     LIMIT 8'
)->fetchAll();

$newGapSkills = $pdo->query(
    'SELECT s.name, COUNT(*) AS total
     FROM analysis_results ar
     INNER JOIN analyses a ON a.id = ar.analysis_id
     INNER JOIN skills s ON s.id = ar.skill_id
     WHERE ar.status IN ("Missing", "Partial")
       AND a.created_at >= CURDATE() - INTERVAL 30 DAY
     GROUP BY ar.skill_id
     ORDER BY total DESC, s.name
     LIMIT 8'
)->fetchAll();

$gapSeverity = $pdo->query(
    'SELECT s.name,
            SUM(ar.status = "Missing") AS missing_total,
            SUM(ar.status = "Partial") AS partial_total
     FROM analysis_results ar
     INNER JOIN skills s ON s.id = ar.skill_id
     WHERE ar.status IN ("Missing", "Partial")
     GROUP BY ar.skill_id
     ORDER BY (SUM(ar.status = "Missing") * 2 + SUM(ar.status = "Partial")) DESC, s.name
     LIMIT 8'
)->fetchAll();

$rolePopularity = $pdo->query(
    'SELECT cr.name, COUNT(a.id) AS total, COALESCE(ROUND(AVG(a.match_score)), 0) AS avg_match
     FROM career_roles cr
     LEFT JOIN analyses a ON a.target_role_id = cr.id
     GROUP BY cr.id
     ORDER BY total DESC, cr.name'
)->fetchAll();

$categoryReadiness = $pdo->query(
    'SELECT c.name,
            COALESCE(ROUND(AVG(CASE WHEN ar.required_rating > 0 THEN LEAST(ar.your_rating, ar.required_rating) / ar.required_rating * 100 ELSE NULL END)), 0) AS readiness
     FROM skill_categories c
     INNER JOIN skills s ON s.category_id = c.id
     LEFT JOIN analysis_results ar ON ar.skill_id = s.id
     WHERE c.type = "Skill Category"
     GROUP BY c.id
     ORDER BY c.name'
)->fetchAll();

$statusSummary = $pdo->query(
    'SELECT status, COUNT(*) AS total
     FROM analysis_results
     GROUP BY status'
)->fetchAll();
$statusCounts = ['Have' => 0, 'Partial' => 0, 'Missing' => 0];
foreach ($statusSummary as $row) {
    $statusCounts[$row['status']] = (int) $row['total'];
}
$resultTotal = array_sum($statusCounts);
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
  <main class="container-fluid py-4 py-lg-5">
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
      <div>
        <h1 class="fw-bold mb-1">Analytics Dashboard</h1>
        <div class="text-muted">Aggregate readiness overview · <?= htmlspecialchars($monthLabel, ENT_QUOTES, 'UTF-8') ?></div>
      </div>
      <button class="btn btn-primary" type="button" onclick="window.location.reload()"><i class="bi bi-arrow-clockwise me-1"></i>Refresh</button>
    </div>

    <div class="row g-3 mb-4">
      <?php if ($canManageUsers || $canReviewSkills): ?>
        <div class="col-md-6 col-xl-3"><div class="card h-100"><div class="card-body"><div class="text-muted small">Total Students</div><div class="fs-3 fw-bold"><?= $totalStudents ?></div><div class="text-success small">+<?= $newStudents ?> new in 30 days</div></div></div></div>
      <?php endif; ?>
      <div class="col-md-6 col-xl-3"><div class="card h-100"><div class="card-body"><div class="text-muted small">Avg Match Score</div><div class="fs-3 fw-bold"><?= $avgMatch ?>%</div><div class="small text-muted"><?= $analysisCount ?> analyses completed</div></div></div></div>
      <?php if ($canManageRoles): ?>
        <div class="col-md-6 col-xl-3"><div class="card h-100"><div class="card-body"><div class="text-muted small">Most Popular Role</div><div class="fs-5 fw-bold"><?= htmlspecialchars((string) ($popularRole['name'] ?? 'No analyses yet'), ENT_QUOTES, 'UTF-8') ?></div></div></div></div>
      <?php endif; ?>
      <?php if ($canViewGapAnalytics): ?>
        <div class="col-md-6 col-xl-3"><div class="card h-100"><div class="card-body"><div class="text-muted small">Most Missing Skill</div><div class="fs-5 fw-bold"><?= htmlspecialchars((string) ($missingSkill['name'] ?? 'No missing skills yet'), ENT_QUOTES, 'UTF-8') ?></div><div class="text-danger small"><?= (int) ($missingSkill['total'] ?? 0) ?> missing results</div></div></div></div>
      <?php endif; ?>
    </div>

    <?php if ($canViewGapAnalytics): ?>
      <div class="row g-4 mb-4">
        <div class="col-xl-4">
          <div class="card h-100">
            <div class="card-body p-4">
              <h2 class="h5 fw-bold mb-3">Top Missing Skills</h2>
              <?php if ($missingSkills === []): ?>
                <div class="alert alert-light border mb-0">No missing skill results are available yet.</div>
              <?php else: ?>
                <div class="d-grid gap-3">
                  <?php $maxMissing = max(array_map(static fn(array $row): int => (int) $row['total'], $missingSkills)); ?>
                  <?php foreach ($missingSkills as $skill): ?>
                    <?php $pct = $maxMissing > 0 ? (int) round(((int) $skill['total'] / $maxMissing) * 100) : 0; ?>
                    <div>
                      <div class="d-flex justify-content-between small mb-1"><span><?= htmlspecialchars($skill['name'], ENT_QUOTES, 'UTF-8') ?></span><span><?= (int) $skill['total'] ?></span></div>
                      <?= skillmap_percent_bar($pct, 'danger') ?>
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <div class="col-xl-4">
          <div class="card h-100">
            <div class="card-body p-4">
              <h2 class="h5 fw-bold mb-3">New Gaps This Month</h2>
              <?php if ($newGapSkills === []): ?>
                <div class="alert alert-light border mb-0">No new gap results in the last 30 days.</div>
              <?php else: ?>
                <div class="d-grid gap-3">
                  <?php $maxNewGap = max(array_map(static fn(array $row): int => (int) $row['total'], $newGapSkills)); ?>
                  <?php foreach ($newGapSkills as $skill): ?>
                    <?php $pct = $maxNewGap > 0 ? (int) round(((int) $skill['total'] / $maxNewGap) * 100) : 0; ?>
                    <div>
                      <div class="d-flex justify-content-between small mb-1"><span><?= htmlspecialchars($skill['name'], ENT_QUOTES, 'UTF-8') ?></span><span><?= (int) $skill['total'] ?></span></div>
                      <?= skillmap_percent_bar($pct, 'warning') ?>
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <div class="col-xl-4">
          <div class="card h-100">
            <div class="card-body p-4">
              <h2 class="h5 fw-bold mb-3">Gap Status Summary</h2>
              <?php if ($resultTotal === 0): ?>
                <div class="alert alert-light border mb-0">No analysis result data yet.</div>
              <?php else: ?>
                <?php foreach ([['Have', 'success'], ['Partial', 'warning'], ['Missing', 'danger']] as [$label, $color]): ?>
                  <?php $pct = (int) round(($statusCounts[$label] / $resultTotal) * 100); ?>
                  <div class="mb-3">
                    <div class="d-flex justify-content-between small mb-1"><span><?= $label === 'Partial' ? 'Partially Have' : $label ?></span><span><?= $statusCounts[$label] ?> · <?= $pct ?>%</span></div>
                    <?= skillmap_percent_bar($pct, $color) ?>
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>

      <div class="row g-4 mb-4">
        <div class="col-12">
          <div class="card">
            <div class="card-body p-4">
              <h2 class="h5 fw-bold mb-3">Gap Severity Bar Chart</h2>
              <?php if ($gapSeverity === []): ?>
                <div class="alert alert-light border mb-0">No gap chart data yet.</div>
              <?php else: ?>
                <div class="skillmap-chart-box"><canvas id="gapSeverityChart"></canvas></div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <div class="row g-4">
      <?php if ($canManageRoles): ?>
        <div class="col-xl-6">
          <div class="card h-100">
            <div class="card-body p-4">
              <h2 class="h5 fw-bold mb-3">Target Role Popularity</h2>
              <div class="table-responsive">
                <table class="table align-middle mb-0">
                  <thead><tr><th>Role</th><th>Analyses</th><th>Avg Match</th></tr></thead>
                  <tbody>
                    <?php foreach ($rolePopularity as $role): ?>
                      <tr><td class="fw-semibold"><?= htmlspecialchars($role['name'], ENT_QUOTES, 'UTF-8') ?></td><td><?= (int) $role['total'] ?></td><td><?= (int) $role['avg_match'] ?>%</td></tr>
                    <?php endforeach; ?>
                    <?php if ($rolePopularity === []): ?><tr><td colspan="3" class="text-center text-muted py-4">No roles available.</td></tr><?php endif; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      <?php endif; ?>

      <?php if ($canViewGapAnalytics): ?>
        <div class="col-xl-6">
          <div class="card h-100">
            <div class="card-body p-4">
              <h2 class="h5 fw-bold mb-3">Category-Level Readiness</h2>
              <div class="d-grid gap-3">
                <?php foreach ($categoryReadiness as $category): ?>
                  <div>
                    <div class="d-flex justify-content-between small mb-1"><span><?= htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8') ?></span><span><?= (int) $category['readiness'] ?>%</span></div>
                    <?= skillmap_percent_bar((int) $category['readiness'], (int) $category['readiness'] >= 70 ? 'success' : 'warning') ?>
                  </div>
                <?php endforeach; ?>
                <?php if ($categoryReadiness === []): ?><div class="alert alert-light border mb-0">No category readiness data yet.</div><?php endif; ?>
              </div>
            </div>
          </div>
        </div>
      <?php endif; ?>
    </div>
    <?php require __DIR__ . '/../includes/footer.php'; ?>
  </main>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <?php if ($canViewGapAnalytics && $gapSeverity !== []): ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
  <?php endif; ?>
  <script src="/fyp_skillmapsystem/assets/js/app.js"></script>
  <?php if ($canViewGapAnalytics && $gapSeverity !== []): ?>
    <script>
      const gapSeverityCanvas = document.getElementById('gapSeverityChart');
      if (gapSeverityCanvas && typeof Chart !== 'undefined') {
        new Chart(gapSeverityCanvas, {
          type: 'bar',
          data: {
            labels: <?= json_encode(array_column($gapSeverity, 'name'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>,
            datasets: [
              { label: 'Missing', data: <?= json_encode(array_map('intval', array_column($gapSeverity, 'missing_total')), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>, backgroundColor: '#dc2626', borderRadius: 6 },
              { label: 'Partial', data: <?= json_encode(array_map('intval', array_column($gapSeverity, 'partial_total')), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>, backgroundColor: '#f59e0b', borderRadius: 6 }
            ]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: { x: { stacked: true }, y: { beginAtZero: true, stacked: true, ticks: { precision: 0 } } },
            plugins: { legend: { position: 'bottom' } }
          }
        });
      }
    </script>
  <?php endif; ?>
</body>
</html>
