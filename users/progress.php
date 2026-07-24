<?php
// User progress page showing real analysis history, roadmap completion, skill ratings, and badges.

require_once __DIR__ . '/../includes/auth_check.php';
skillmap_require_auth(['student']);

$activePage = 'progress';
$user = skillmap_current_user();
$userId = (int) ($user['id'] ?? 0);

$historyStmt = $pdo->prepare(
    'SELECT a.id, a.match_score, a.created_at, cr.name AS role_name
     FROM analyses a
     INNER JOIN career_roles cr ON cr.id = a.target_role_id
     WHERE a.user_id = :user_id
     ORDER BY a.created_at ASC'
);
$historyStmt->execute(['user_id' => $userId]);
$history = $historyStmt->fetchAll();

$latestAnalysis = $history ? $history[count($history) - 1] : null;
$previousAnalysis = count($history) > 1 ? $history[count($history) - 2] : null;
$scoreChange = $latestAnalysis && $previousAnalysis ? (int) round((float) $latestAnalysis['match_score'] - (float) $previousAnalysis['match_score']) : 0;

$roadmap = ['total_count' => 0, 'completed_count' => 0];
if ($latestAnalysis) {
    $roadmapStmt = $pdo->prepare(
        'SELECT COUNT(*) AS total_count,
                SUM(CASE WHEN COALESCE(urp.status, ar.status) = "Completed" THEN 1 ELSE 0 END) AS completed_count
         FROM analysis_results ar
         LEFT JOIN user_roadmap_progress urp ON urp.skill_id = ar.skill_id AND urp.user_id = :user_id
         WHERE ar.analysis_id = :analysis_id AND ar.status IN ("Missing", "Partial")'
    );
    $roadmapStmt->execute(['user_id' => $userId, 'analysis_id' => (int) $latestAnalysis['id']]);
    $roadmap = $roadmapStmt->fetch() ?: $roadmap;
}
$roadmapPct = (int) $roadmap['total_count'] > 0 ? (int) round(((int) $roadmap['completed_count'] / (int) $roadmap['total_count']) * 100) : 0;

$streakStmt = $pdo->prepare('SELECT current_streak, best_streak, last_activity FROM learning_streaks WHERE user_id = :user_id LIMIT 1');
$streakStmt->execute(['user_id' => $userId]);
$streak = $streakStmt->fetch() ?: ['current_streak' => 0, 'best_streak' => 0, 'last_activity' => null];

$ratingStmt = $pdo->prepare(
    'SELECT s.name AS skill_name, c.name AS category, r.rating, DATE_FORMAT(r.updated_at, "%e %b %Y") AS updated_at
     FROM user_skill_ratings r
     INNER JOIN skills s ON s.id = r.skill_id
     INNER JOIN skill_categories c ON c.id = s.category_id
     WHERE r.user_id = :user_id
     ORDER BY r.updated_at DESC, s.name
     LIMIT 8'
);
$ratingStmt->execute(['user_id' => $userId]);
$ratings = $ratingStmt->fetchAll();

$badgeStmt = $pdo->prepare(
    'SELECT b.name, b.tier, b.description, b.icon, ub.earned_at
     FROM badges b
     LEFT JOIN user_badges ub ON ub.badge_id = b.id AND ub.user_id = :user_id
     ORDER BY FIELD(b.tier, "gold", "silver", "bronze"), b.name'
);
$badgeStmt->execute(['user_id' => $userId]);
$badges = $badgeStmt->fetchAll();

$chartLabels = array_map(static fn(array $row): string => date('j M', strtotime((string) $row['created_at'])), $history);
$chartScores = array_map(static fn(array $row): int => (int) round((float) $row['match_score']), $history);
$historyRows = array_reverse($history);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Skill Map - Progress</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="/fyp_skillmapsystem/assets/css/style.css" rel="stylesheet">
</head>
<body>
  <?php require __DIR__ . '/includes/navbar.php'; ?>
  <main class="container py-4 py-lg-5">
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
      <div>
        <h1 class="fw-bold mb-1">My Progress</h1>
        <div class="text-muted">Track analysis results, learning activity, and earned achievements.</div>
      </div>
      <a class="btn btn-primary" href="/fyp_skillmapsystem/users/analyse.php"><i class="bi bi-search me-1"></i>Run New Analysis</a>
    </div>

    <div class="row g-4 mb-4">
      <div class="col-md-4">
        <div class="card h-100"><div class="card-body p-4">
          <div class="text-muted small mb-2">Latest Match Score</div>
          <div class="display-6 fw-bold"><?= $latestAnalysis ? (int) round((float) $latestAnalysis['match_score']) : 0 ?>%</div>
          <span class="badge <?= $scoreChange >= 0 ? 'text-bg-success' : 'text-bg-danger' ?>"><?= $scoreChange >= 0 ? '+' : '' ?><?= $scoreChange ?> since previous</span>
        </div></div>
      </div>
      <div class="col-md-4">
        <div class="card h-100"><div class="card-body p-4">
          <div class="text-muted small mb-2">Roadmap Completion</div>
          <div class="display-6 fw-bold"><?= $roadmapPct ?>%</div>
          <div class="small text-muted"><?= (int) $roadmap['completed_count'] ?> of <?= (int) $roadmap['total_count'] ?> learning items completed</div>
        </div></div>
      </div>
      <div class="col-md-4">
        <div class="card h-100"><div class="card-body p-4">
          <div class="text-muted small mb-2">Learning Streak</div>
          <div class="display-6 fw-bold"><?= (int) $streak['current_streak'] ?> days</div>
          <div class="small text-muted">Best streak: <?= (int) $streak['best_streak'] ?> days</div>
        </div></div>
      </div>
    </div>

    <div class="row g-4 mb-4">
      <div class="col-lg-8">
        <div class="card h-100"><div class="card-body p-4">
          <h2 class="h5 fw-bold mb-3">Match Score Trend</h2>
          <?php if ($history === []): ?>
            <div class="text-center text-muted py-5">Run your first gap analysis to build a progress trend.</div>
          <?php else: ?>
            <div style="height:280px"><canvas id="progressHistoryChart"></canvas></div>
          <?php endif; ?>
        </div></div>
      </div>
      <div class="col-lg-4">
        <div class="card h-100"><div class="card-body p-4">
          <h2 class="h5 fw-bold mb-3">Recent Skill Ratings</h2>
          <?php if ($ratings === []): ?>
            <div class="text-muted small">No skills have been rated yet.</div>
          <?php else: ?>
            <div class="d-grid gap-3">
              <?php foreach ($ratings as $rating): ?>
                <div>
                  <div class="d-flex justify-content-between gap-3">
                    <span class="fw-semibold"><?= htmlspecialchars((string) $rating['skill_name'], ENT_QUOTES, 'UTF-8') ?></span>
                    <span><?= (int) $rating['rating'] ?>/5</span>
                  </div>
                  <div class="small text-muted"><?= htmlspecialchars((string) $rating['category'], ENT_QUOTES, 'UTF-8') ?> - <?= htmlspecialchars((string) $rating['updated_at'], ENT_QUOTES, 'UTF-8') ?></div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div></div>
      </div>
    </div>

    <div class="row g-4">
      <div class="col-lg-8">
        <div class="card"><div class="card-body p-4">
          <h2 class="h5 fw-bold mb-3">Analysis History</h2>
          <?php if ($historyRows === []): ?>
            <div class="text-muted small">No analysis history yet.</div>
          <?php else: ?>
            <div class="table-responsive"><table class="table align-middle mb-0">
              <thead><tr><th>Date</th><th>Target Role</th><th>Score</th></tr></thead>
              <tbody>
                <?php foreach ($historyRows as $row): ?>
                  <tr>
                    <td><?= htmlspecialchars(date('j M Y', strtotime((string) $row['created_at'])), ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="fw-semibold"><?= htmlspecialchars((string) $row['role_name'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= (int) round((float) $row['match_score']) ?>%</td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table></div>
          <?php endif; ?>
        </div></div>
      </div>
      <div class="col-lg-4">
        <div class="card"><div class="card-body p-4">
          <h2 class="h5 fw-bold mb-3">Badges</h2>
          <div class="d-grid gap-3">
            <?php foreach ($badges as $badge): ?>
              <?php $earned = $badge['earned_at'] !== null; ?>
              <div class="d-flex gap-3 align-items-start <?= $earned ? '' : 'opacity-50' ?>">
                <div class="skillmap-stats-icon"><i class="bi <?= htmlspecialchars((string) ($badge['icon'] ?: 'bi-award'), ENT_QUOTES, 'UTF-8') ?>"></i></div>
                <div>
                  <div class="fw-semibold"><?= htmlspecialchars((string) $badge['name'], ENT_QUOTES, 'UTF-8') ?></div>
                  <div class="small text-muted"><?= htmlspecialchars((string) ($badge['description'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                  <span class="badge <?= $earned ? skillmap_badge_tier_class((string) $badge['tier']) : 'text-bg-light border' ?> mt-1"><?= $earned ? 'Earned' : 'Locked' ?></span>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div></div>
      </div>
    </div>
    <?php require __DIR__ . '/../includes/footer.php'; ?>
  </main>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
  <script src="/fyp_skillmapsystem/assets/js/app.js"></script>
  <script>
    const progressCanvas = document.getElementById('progressHistoryChart');
    if (progressCanvas && typeof Chart !== 'undefined') {
      new Chart(progressCanvas, {
        type: 'line',
        data: {
          labels: <?= json_encode($chartLabels, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>,
          datasets: [{
            label: 'Match Score',
            data: <?= json_encode($chartScores, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>,
            borderColor: '#2563eb',
            backgroundColor: 'rgba(37, 99, 235, 0.12)',
            fill: true,
            tension: 0.35
          }]
        },
        options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true, max: 100 } } }
      });
    }
  </script>
</body>
</html>
