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

$analysisTrend = $pdo->query(
    'SELECT DATE_FORMAT(created_at, "%b %Y") AS period_label,
            DATE_FORMAT(created_at, "%Y-%m") AS period_key,
            COUNT(*) AS total,
            COALESCE(ROUND(AVG(match_score)), 0) AS avg_match
     FROM analyses
     WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
     GROUP BY period_key, period_label
     ORDER BY period_key'
)->fetchAll();

$programmeReadiness = $pdo->query(
    'SELECT u.programme,
            COUNT(DISTINCT u.id) AS students,
            COUNT(a.id) AS analyses,
            COALESCE(ROUND(AVG(a.match_score)), 0) AS avg_match
     FROM users u
     LEFT JOIN analyses a ON a.user_id = u.id
     WHERE u.role = "student"
     GROUP BY u.programme
     ORDER BY avg_match DESC, students DESC, u.programme
     LIMIT 8'
)->fetchAll();

$latestStudentAnalyses = $pdo->query(
    'SELECT u.id, u.name, u.programme, cr.name AS role_name, a.match_score, a.created_at
     FROM users u
     INNER JOIN analyses a ON a.user_id = u.id
     INNER JOIN career_roles cr ON cr.id = a.target_role_id
     WHERE u.role = "student"
       AND a.created_at = (
         SELECT MAX(a2.created_at)
         FROM analyses a2
         WHERE a2.user_id = u.id
       )
     ORDER BY a.match_score ASC, a.created_at DESC
     LIMIT 6'
)->fetchAll();

$activeStudents = (int) $pdo->query(
    'SELECT COUNT(DISTINCT user_id)
     FROM user_skill_ratings
     WHERE updated_at >= CURDATE() - INTERVAL 30 DAY'
)->fetchColumn();
$assessmentCoverage = $totalStudents > 0 ? (int) round(($activeStudents / $totalStudents) * 100) : 0;
$missingPct = $resultTotal > 0 ? (int) round(($statusCounts['Missing'] / $resultTotal) * 100) : 0;
$trendLatest = $analysisTrend !== [] ? $analysisTrend[count($analysisTrend) - 1] : null;
$trendPrevious = count($analysisTrend) > 1 ? $analysisTrend[count($analysisTrend) - 2] : null;
$trendChange = $trendLatest && $trendPrevious ? (int) $trendLatest['avg_match'] - (int) $trendPrevious['avg_match'] : 0;
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

    <div class="row g-3 mb-4">
      <div class="col-md-6 col-xl-3">
        <div class="skillmap-insight-card h-100">
          <div class="skillmap-insight-icon text-bg-primary"><i class="bi bi-activity"></i></div>
          <div>
            <div class="small text-muted">Assessment Activity</div>
            <div class="fs-4 fw-bold"><?= $assessmentCoverage ?>%</div>
            <div class="small text-muted"><?= $activeStudents ?> active students in 30 days</div>
          </div>
        </div>
      </div>
      <div class="col-md-6 col-xl-3">
        <div class="skillmap-insight-card h-100">
          <div class="skillmap-insight-icon text-bg-success"><i class="bi bi-graph-up-arrow"></i></div>
          <div>
            <div class="small text-muted">Readiness Trend</div>
            <div class="fs-4 fw-bold"><?= $trendLatest ? (int) $trendLatest['avg_match'] . '%' : '0%' ?></div>
            <div class="small <?= $trendChange >= 0 ? 'text-success' : 'text-danger' ?>"><?= $trendChange >= 0 ? '+' : '' ?><?= $trendChange ?> from previous month</div>
          </div>
        </div>
      </div>
      <?php if ($canViewGapAnalytics): ?>
        <div class="col-md-6 col-xl-3">
          <div class="skillmap-insight-card h-100">
            <div class="skillmap-insight-icon text-bg-danger"><i class="bi bi-exclamation-triangle"></i></div>
            <div>
              <div class="small text-muted">Missing Skill Risk</div>
              <div class="fs-4 fw-bold"><?= $missingPct ?>%</div>
              <div class="small text-muted"><?= $statusCounts['Missing'] ?> missing of <?= $resultTotal ?> results</div>
            </div>
          </div>
        </div>
      <?php endif; ?>
      <div class="col-md-6 col-xl-3">
        <div class="skillmap-insight-card h-100">
          <div class="skillmap-insight-icon text-bg-warning"><i class="bi bi-lightbulb"></i></div>
          <div>
            <div class="small text-muted">Recommended Focus</div>
            <div class="fs-6 fw-bold"><?= htmlspecialchars((string) ($missingSkill['name'] ?? 'Collect more data'), ENT_QUOTES, 'UTF-8') ?></div>
            <div class="small text-muted"><?= $analysisCount ?> analyses available</div>
          </div>
        </div>
      </div>
    </div>

    <div class="row g-4 mb-4">
      <div class="col-xl-7">
        <div class="card h-100">
          <div class="card-body p-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
              <h2 class="h5 fw-bold mb-0">Six-Month Readiness Trend</h2>
              <span class="badge text-bg-light border"><?= count($analysisTrend) ?> active month<?= count($analysisTrend) === 1 ? '' : 's' ?></span>
            </div>
            <?php if ($analysisTrend === []): ?>
              <div class="alert alert-light border mb-0">No analysis trend data yet.</div>
            <?php else: ?>
              <div class="skillmap-chart-box"><canvas id="analysisTrendChart"></canvas></div>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <div class="col-xl-5">
        <div class="card h-100">
          <div class="card-body p-4">
            <h2 class="h5 fw-bold mb-3">Programme Readiness Comparison</h2>
            <?php if ($programmeReadiness === []): ?>
              <div class="alert alert-light border mb-0">No programme readiness data yet.</div>
            <?php else: ?>
              <div class="skillmap-chart-box skillmap-chart-box-sm"><canvas id="programmeReadinessChart"></canvas></div>
            <?php endif; ?>
          </div>
        </div>
      </div>
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
        <div class="col-xl-8">
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
        <div class="col-xl-4">
          <div class="card h-100">
            <div class="card-body p-4">
              <h2 class="h5 fw-bold mb-3">Skill Status Mix</h2>
              <?php if ($resultTotal === 0): ?>
                <div class="alert alert-light border mb-0">No status mix data yet.</div>
              <?php else: ?>
                <div class="skillmap-chart-box skillmap-chart-box-sm"><canvas id="statusMixChart"></canvas></div>
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

    <?php if ($latestStudentAnalyses !== [] && ($canManageUsers || $canReviewSkills)): ?>
      <div class="row g-4 mt-1">
        <div class="col-12">
          <div class="card">
            <div class="card-body p-4">
              <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <h2 class="h5 fw-bold mb-0">Students Needing Support</h2>
                <span class="badge text-bg-light border">Latest analysis per student</span>
              </div>
              <div class="table-responsive">
                <table class="table align-middle mb-0">
                  <thead><tr><th>Student</th><th>Programme</th><th>Target Role</th><th>Latest Score</th><th>Date</th></tr></thead>
                  <tbody>
                    <?php foreach ($latestStudentAnalyses as $studentAnalysis): ?>
                      <tr>
                        <td class="fw-semibold"><?= htmlspecialchars((string) $studentAnalysis['name'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) $studentAnalysis['programme'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) $studentAnalysis['role_name'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><span class="badge <?= (int) round((float) $studentAnalysis['match_score']) >= 70 ? 'text-bg-success' : 'text-bg-warning' ?>"><?= (int) round((float) $studentAnalysis['match_score']) ?>%</span></td>
                        <td><?= htmlspecialchars(date('j M Y', strtotime((string) $studentAnalysis['created_at'])), ENT_QUOTES, 'UTF-8') ?></td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
    <?php endif; ?>
    <?php require __DIR__ . '/../includes/footer.php'; ?>
  </main>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
  <script src="/fyp_skillmapsystem/assets/js/app.js"></script>
    <script>
      const skillmapChartDefaults = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { labels: { usePointStyle: true, boxWidth: 10 } }
        }
      };

      const analysisTrendCanvas = document.getElementById('analysisTrendChart');
      if (analysisTrendCanvas && typeof Chart !== 'undefined') {
        new Chart(analysisTrendCanvas, {
          type: 'line',
          data: {
            labels: <?= json_encode(array_column($analysisTrend, 'period_label'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>,
            datasets: [
              {
                label: 'Avg Match Score',
                data: <?= json_encode(array_map('intval', array_column($analysisTrend, 'avg_match')), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>,
                borderColor: '#2563eb',
                backgroundColor: 'rgba(37, 99, 235, 0.12)',
                tension: 0.35,
                fill: true,
                yAxisID: 'y'
              },
              {
                label: 'Analyses',
                data: <?= json_encode(array_map('intval', array_column($analysisTrend, 'total')), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>,
                borderColor: '#14b8a6',
                backgroundColor: 'rgba(20, 184, 166, 0.12)',
                tension: 0.35,
                fill: false,
                yAxisID: 'y1'
              }
            ]
          },
          options: {
            ...skillmapChartDefaults,
            interaction: { mode: 'index', intersect: false },
            scales: {
              y: { beginAtZero: true, max: 100, title: { display: true, text: 'Score %' } },
              y1: { beginAtZero: true, position: 'right', grid: { drawOnChartArea: false }, ticks: { precision: 0 }, title: { display: true, text: 'Analyses' } }
            }
          }
        });
      }

      const programmeReadinessCanvas = document.getElementById('programmeReadinessChart');
      if (programmeReadinessCanvas && typeof Chart !== 'undefined') {
        new Chart(programmeReadinessCanvas, {
          type: 'bar',
          data: {
            labels: <?= json_encode(array_column($programmeReadiness, 'programme'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>,
            datasets: [{
              label: 'Avg Match Score',
              data: <?= json_encode(array_map('intval', array_column($programmeReadiness, 'avg_match')), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>,
              backgroundColor: '#2563eb',
              borderRadius: 6
            }]
          },
          options: { ...skillmapChartDefaults, indexAxis: 'y', scales: { x: { beginAtZero: true, max: 100 } }, plugins: { legend: { display: false } } }
        });
      }

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
            ...skillmapChartDefaults,
            scales: { x: { stacked: true }, y: { beginAtZero: true, stacked: true, ticks: { precision: 0 } } },
            plugins: { legend: { position: 'bottom' } }
          }
        });
      }

      const statusMixCanvas = document.getElementById('statusMixChart');
      if (statusMixCanvas && typeof Chart !== 'undefined') {
        new Chart(statusMixCanvas, {
          type: 'doughnut',
          data: {
            labels: ['Have', 'Partial', 'Missing'],
            datasets: [{
              data: [<?= $statusCounts['Have'] ?>, <?= $statusCounts['Partial'] ?>, <?= $statusCounts['Missing'] ?>],
              backgroundColor: ['#16a34a', '#f59e0b', '#dc2626'],
              borderWidth: 0
            }]
          },
          options: { ...skillmapChartDefaults, cutout: '68%', plugins: { legend: { position: 'bottom' } } }
        });
      }
    </script>
</body>
</html>
