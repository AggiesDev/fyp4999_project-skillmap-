<?php
// Student dashboard backed by the user's real assessment and analysis data.

require_once __DIR__ . '/../includes/auth_check.php';
skillmap_require_auth(['student']);

$activePage = 'dashboard';
$user = skillmap_current_user();
$userId = (int) ($user['id'] ?? 0);

$profileStmt = $pdo->prepare(
    'SELECT u.name, u.email, u.programme, u.year_level, u.avatar_initials,
            COALESCE((SELECT COUNT(*) FROM skills s INNER JOIN skill_categories c ON c.id = s.category_id WHERE s.status = "Active" AND c.type = "Skill Category"), 0) AS total_skills,
            COALESCE((SELECT COUNT(*) FROM user_skill_ratings r WHERE r.user_id = u.id), 0) AS rated_skills,
            COALESCE((SELECT COUNT(*) FROM analyses a WHERE a.user_id = u.id), 0) AS analyses_done,
            COALESCE((SELECT COUNT(*) FROM user_badges ub WHERE ub.user_id = u.id), 0) AS badges_earned,
            COALESCE((SELECT current_streak FROM learning_streaks ls WHERE ls.user_id = u.id), 0) AS current_streak
     FROM users u
     WHERE u.id = :user_id
     LIMIT 1'
);
$profileStmt->execute(['user_id' => $userId]);
$profile = $profileStmt->fetch() ?: [];

$latestStmt = $pdo->prepare(
    'SELECT a.id, a.match_score, a.ai_summary, a.created_at, cr.name AS role_name
     FROM analyses a
     INNER JOIN career_roles cr ON cr.id = a.target_role_id
     WHERE a.user_id = :user_id
     ORDER BY a.created_at DESC
     LIMIT 1'
);
$latestStmt->execute(['user_id' => $userId]);
$latestAnalysis = $latestStmt->fetch() ?: null;

$summary = ['Have' => 0, 'Partial' => 0, 'Missing' => 0];
$focusItems = [];
$roadmapCompletePct = 0;
if ($latestAnalysis) {
    $summaryStmt = $pdo->prepare('SELECT status, COUNT(*) AS total FROM analysis_results WHERE analysis_id = :analysis_id GROUP BY status');
    $summaryStmt->execute(['analysis_id' => (int) $latestAnalysis['id']]);
    foreach ($summaryStmt->fetchAll() as $row) {
        $summary[(string) $row['status']] = (int) $row['total'];
    }

    $focusStmt = $pdo->prepare(
        'SELECT ar.skill_id, ar.status, ar.gap_value, s.name AS skill_name, lr.title, lr.platform, lr.duration_hours
         FROM analysis_results ar
         INNER JOIN skills s ON s.id = ar.skill_id
         LEFT JOIN learning_resources lr ON lr.skill_id = ar.skill_id
         WHERE ar.analysis_id = :analysis_id AND ar.status IN ("Missing", "Partial")
         ORDER BY FIELD(ar.status, "Missing", "Partial"), ar.gap_value DESC, s.name
         LIMIT 5'
    );
    $focusStmt->execute(['analysis_id' => (int) $latestAnalysis['id']]);
    $focusItems = $focusStmt->fetchAll();

    $roadmapStmt = $pdo->prepare(
        'SELECT COUNT(*) AS total_count,
                SUM(CASE WHEN COALESCE(urp.status, ar.status) = "Completed" THEN 1 ELSE 0 END) AS completed_count
         FROM analysis_results ar
         LEFT JOIN user_roadmap_progress urp ON urp.skill_id = ar.skill_id AND urp.user_id = :user_id
         WHERE ar.analysis_id = :analysis_id AND ar.status IN ("Missing", "Partial")'
    );
    $roadmapStmt->execute(['user_id' => $userId, 'analysis_id' => (int) $latestAnalysis['id']]);
    $roadmapCounts = $roadmapStmt->fetch() ?: ['total_count' => 0, 'completed_count' => 0];
    $roadmapTotal = (int) $roadmapCounts['total_count'];
    $roadmapCompletePct = $roadmapTotal > 0 ? (int) round(((int) $roadmapCounts['completed_count'] / $roadmapTotal) * 100) : 0;
}

$historyStmt = $pdo->prepare(
    'SELECT cr.name AS role_name, ROUND(a.match_score) AS match_score, DATE_FORMAT(a.created_at, "%e %b %Y") AS created_at
     FROM analyses a
     INNER JOIN career_roles cr ON cr.id = a.target_role_id
     WHERE a.user_id = :user_id
     ORDER BY a.created_at DESC
     LIMIT 5'
);
$historyStmt->execute(['user_id' => $userId]);
$recentAnalyses = $historyStmt->fetchAll();

$badgeStmt = $pdo->prepare(
    'SELECT b.name, b.tier, b.icon, DATE_FORMAT(ub.earned_at, "%e %b %Y") AS earned_at
     FROM user_badges ub
     INNER JOIN badges b ON b.id = ub.badge_id
     WHERE ub.user_id = :user_id
     ORDER BY ub.earned_at DESC
     LIMIT 4'
);
$badgeStmt->execute(['user_id' => $userId]);
$badges = $badgeStmt->fetchAll();

$totalSkills = (int) ($profile['total_skills'] ?? 0);
$ratedSkills = (int) ($profile['rated_skills'] ?? 0);
$assessmentPct = $totalSkills > 0 ? (int) round(($ratedSkills / $totalSkills) * 100) : 0;
$matchScore = $latestAnalysis ? (int) round((float) $latestAnalysis['match_score']) : 0;
$lastAnalysisDate = $latestAnalysis ? date('j M Y', strtotime((string) $latestAnalysis['created_at'])) : '-';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Skill Map - Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="/fyp_skillmapsystem/assets/css/style.css" rel="stylesheet">
</head>
<body>
  <?php require __DIR__ . '/includes/navbar.php'; ?>
  <main class="container py-4 py-lg-5">
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
      <div>
        <h1 class="fw-bold mb-1">Welcome back, <?= htmlspecialchars((string) ($profile['name'] ?? $user['name'] ?? 'Student'), ENT_QUOTES, 'UTF-8') ?></h1>
        <div class="text-muted">
          <?= htmlspecialchars((string) ($profile['programme'] ?? '-'), ENT_QUOTES, 'UTF-8') ?> -
          <?= htmlspecialchars((string) ($profile['year_level'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>
        </div>
      </div>
      <div class="d-flex flex-wrap gap-2">
        <a class="btn btn-outline-primary" href="/fyp_skillmapsystem/users/skills_assessment.php"><i class="bi bi-stars me-1"></i>Update Assessment</a>
        <a class="btn btn-primary" href="/fyp_skillmapsystem/users/analyse.php"><i class="bi bi-search me-1"></i>New Analysis</a>
      </div>
    </div>

    <?php if (!$latestAnalysis): ?>
      <div class="alert alert-light border d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
          <div class="fw-semibold">No gap analysis yet</div>
          <div class="small text-muted">Complete your assessment, then choose a target role to generate your roadmap and report.</div>
        </div>
        <a class="btn btn-primary btn-sm" href="/fyp_skillmapsystem/users/analyse.php">Start Analysis</a>
      </div>
    <?php endif; ?>

    <div class="row g-4 mb-4">
      <div class="col-md-6 col-xl-3">
        <div class="card h-100"><div class="card-body p-4">
          <div class="d-flex justify-content-between align-items-start mb-3"><span class="text-muted small">Latest Match</span><i class="bi bi-bullseye text-primary"></i></div>
          <div class="display-6 fw-bold"><?= $matchScore ?>%</div>
          <div class="small text-muted"><?= $latestAnalysis ? htmlspecialchars((string) $latestAnalysis['role_name'], ENT_QUOTES, 'UTF-8') . ' - ' . $lastAnalysisDate : 'Run your first analysis' ?></div>
        </div></div>
      </div>
      <div class="col-md-6 col-xl-3">
        <div class="card h-100"><div class="card-body p-4">
          <div class="d-flex justify-content-between align-items-start mb-3"><span class="text-muted small">Assessment</span><i class="bi bi-check2-circle text-success"></i></div>
          <div class="display-6 fw-bold"><?= $assessmentPct ?>%</div>
          <div class="small text-muted"><?= $ratedSkills ?> of <?= $totalSkills ?> active skills rated</div>
        </div></div>
      </div>
      <div class="col-md-6 col-xl-3">
        <div class="card h-100"><div class="card-body p-4">
          <div class="d-flex justify-content-between align-items-start mb-3"><span class="text-muted small">Roadmap</span><i class="bi bi-map text-warning"></i></div>
          <div class="display-6 fw-bold"><?= $roadmapCompletePct ?>%</div>
          <div class="small text-muted">Latest roadmap completed</div>
        </div></div>
      </div>
      <div class="col-md-6 col-xl-3">
        <div class="card h-100"><div class="card-body p-4">
          <div class="d-flex justify-content-between align-items-start mb-3"><span class="text-muted small">Streak</span><i class="bi bi-lightning-charge text-danger"></i></div>
          <div class="display-6 fw-bold"><?= (int) ($profile['current_streak'] ?? 0) ?></div>
          <div class="small text-muted"><?= (int) ($profile['badges_earned'] ?? 0) ?> badges earned</div>
        </div></div>
      </div>
    </div>

    <div class="row g-4 mb-4">
      <div class="col-lg-8">
        <div class="card h-100">
          <div class="card-body p-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
              <h2 class="h5 fw-bold mb-0">Current Skill Breakdown</h2>
              <a class="link-primary text-decoration-none" href="/fyp_skillmapsystem/users/gap_analysis.php">View Results</a>
            </div>
            <?php if (!$latestAnalysis): ?>
              <div class="text-center text-muted py-5">Your skill breakdown will appear after your first analysis.</div>
            <?php else: ?>
              <?php $resultTotal = max(array_sum($summary), 1); ?>
              <div class="mb-3"><div class="d-flex justify-content-between small mb-1"><span class="text-success fw-semibold">Have</span><span><?= $summary['Have'] ?></span></div><?= skillmap_percent_bar((int) round(($summary['Have'] / $resultTotal) * 100), 'success') ?></div>
              <div class="mb-3"><div class="d-flex justify-content-between small mb-1"><span class="text-warning fw-semibold">Partial</span><span><?= $summary['Partial'] ?></span></div><?= skillmap_percent_bar((int) round(($summary['Partial'] / $resultTotal) * 100), 'warning') ?></div>
              <div class="mb-4"><div class="d-flex justify-content-between small mb-1"><span class="text-danger fw-semibold">Missing</span><span><?= $summary['Missing'] ?></span></div><?= skillmap_percent_bar((int) round(($summary['Missing'] / $resultTotal) * 100), 'danger') ?></div>
              <div style="height:220px"><canvas id="dashboardMatchChart"></canvas></div>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <div class="col-lg-4">
        <div class="card h-100">
          <div class="card-body p-4">
            <h2 class="h5 fw-bold mb-3">Next Learning Focus</h2>
            <?php if ($focusItems === []): ?>
              <div class="text-muted small">No missing or partial skills found yet.</div>
            <?php else: ?>
              <div class="d-grid gap-3">
                <?php foreach ($focusItems as $item): ?>
                  <div class="border rounded-4 p-3">
                    <div class="d-flex justify-content-between gap-3">
                      <div class="fw-semibold"><?= htmlspecialchars((string) $item['skill_name'], ENT_QUOTES, 'UTF-8') ?></div>
                      <span class="badge <?= $item['status'] === 'Missing' ? 'text-bg-danger' : 'text-bg-warning' ?>"><?= htmlspecialchars((string) $item['status'], ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <div class="small text-muted mt-1">
                      <?= $item['title'] ? htmlspecialchars((string) $item['title'], ENT_QUOTES, 'UTF-8') . ' - ' . htmlspecialchars((string) $item['platform'], ENT_QUOTES, 'UTF-8') : 'Resource pending' ?>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
            <a class="btn btn-outline-primary w-100 mt-3" href="/fyp_skillmapsystem/users/roadmap.php">Open Roadmap</a>
          </div>
        </div>
      </div>
    </div>

    <div class="row g-4">
      <div class="col-lg-7">
        <div class="card h-100"><div class="card-body p-4">
          <h2 class="h5 fw-bold mb-3">Recent Analyses</h2>
          <?php if ($recentAnalyses === []): ?>
            <div class="text-muted small">No analysis history yet.</div>
          <?php else: ?>
            <div class="table-responsive"><table class="table align-middle mb-0">
              <thead><tr><th>Date</th><th>Target Role</th><th>Match</th></tr></thead>
              <tbody>
                <?php foreach ($recentAnalyses as $row): ?>
                  <tr><td><?= htmlspecialchars((string) $row['created_at'], ENT_QUOTES, 'UTF-8') ?></td><td class="fw-semibold"><?= htmlspecialchars((string) $row['role_name'], ENT_QUOTES, 'UTF-8') ?></td><td><?= (int) $row['match_score'] ?>%</td></tr>
                <?php endforeach; ?>
              </tbody>
            </table></div>
          <?php endif; ?>
        </div></div>
      </div>
      <div class="col-lg-5">
        <div class="card h-100"><div class="card-body p-4">
          <h2 class="h5 fw-bold mb-3">Achievements</h2>
          <?php if ($badges === []): ?>
            <div class="text-muted small">Badges appear here after completing analyses and roadmap milestones.</div>
          <?php else: ?>
            <div class="row g-3">
              <?php foreach ($badges as $badge): ?>
                <div class="col-sm-6">
                  <div class="border rounded-4 p-3 h-100">
                    <i class="bi <?= htmlspecialchars((string) ($badge['icon'] ?: 'bi-award'), ENT_QUOTES, 'UTF-8') ?> text-warning fs-4"></i>
                    <div class="fw-semibold mt-2"><?= htmlspecialchars((string) $badge['name'], ENT_QUOTES, 'UTF-8') ?></div>
                    <span class="badge <?= skillmap_badge_tier_class((string) $badge['tier']) ?> mt-2"><?= ucfirst((string) $badge['tier']) ?></span>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div></div>
      </div>
    </div>
    <?php require __DIR__ . '/../includes/footer.php'; ?>
  </main>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
  <script src="/fyp_skillmapsystem/assets/js/app.js"></script>
  <script>
    const dashboardCanvas = document.getElementById('dashboardMatchChart');
    if (dashboardCanvas && typeof Chart !== 'undefined') {
      new Chart(dashboardCanvas, {
        type: 'doughnut',
        data: {
          labels: ['Have', 'Partial', 'Missing'],
          datasets: [{ data: [<?= $summary['Have'] ?>, <?= $summary['Partial'] ?>, <?= $summary['Missing'] ?>], backgroundColor: ['#16a34a', '#eab308', '#dc2626'], borderWidth: 0 }]
        },
        options: { responsive: true, maintainAspectRatio: false, cutout: '70%', plugins: { legend: { position: 'bottom' } } }
      });
    }
  </script>
</body>
</html>
