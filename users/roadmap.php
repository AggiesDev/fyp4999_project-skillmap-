<?php
// Learning roadmap based on the user's latest gap analysis.

require_once __DIR__ . '/../includes/auth_check.php';
skillmap_require_auth(['student']);

$activePage = 'roadmap';
$user = skillmap_current_user();
$userId = (int) ($user['id'] ?? 0);
$message = '';
$error = '';

function roadmap_touch_streak(PDO $pdo, int $userId): void
{
    $stmt = $pdo->prepare('SELECT current_streak, best_streak, last_activity FROM learning_streaks WHERE user_id = :user_id LIMIT 1');
    $stmt->execute(['user_id' => $userId]);
    $row = $stmt->fetch();
    $today = date('Y-m-d');
    $yesterday = date('Y-m-d', strtotime('-1 day'));

    if (!$row) {
        $insert = $pdo->prepare('INSERT INTO learning_streaks (user_id, current_streak, best_streak, last_activity) VALUES (:user_id, 1, 1, :today)');
        $insert->execute(['user_id' => $userId, 'today' => $today]);
        return;
    }

    if ((string) ($row['last_activity'] ?? '') === $today) {
        return;
    }

    $current = (string) ($row['last_activity'] ?? '') === $yesterday ? (int) $row['current_streak'] + 1 : 1;
    $best = max((int) $row['best_streak'], $current);
    $update = $pdo->prepare('UPDATE learning_streaks SET current_streak = :current, best_streak = :best, last_activity = :today WHERE user_id = :user_id');
    $update->execute(['current' => $current, 'best' => $best, 'today' => $today, 'user_id' => $userId]);
}

function roadmap_award_runner_if_complete(PDO $pdo, int $userId): void
{
    $counts = $pdo->prepare(
        'SELECT COUNT(*) AS total_count, SUM(status = "Completed") AS completed_count
         FROM user_roadmap_progress
         WHERE user_id = :user_id'
    );
    $counts->execute(['user_id' => $userId]);
    $row = $counts->fetch();

    if (!$row || (int) $row['total_count'] === 0 || (int) $row['total_count'] !== (int) $row['completed_count']) {
        return;
    }

    $badge = $pdo->prepare('SELECT id FROM badges WHERE name = "Roadmap Runner" LIMIT 1');
    $badge->execute();
    $badgeId = (int) ($badge->fetchColumn() ?: 0);
    if ($badgeId <= 0) {
        return;
    }

    $exists = $pdo->prepare('SELECT id FROM user_badges WHERE user_id = :user_id AND badge_id = :badge_id LIMIT 1');
    $exists->execute(['user_id' => $userId, 'badge_id' => $badgeId]);
    if ($exists->fetch()) {
        return;
    }

    $award = $pdo->prepare('INSERT INTO user_badges (user_id, badge_id, earned_at) VALUES (:user_id, :badge_id, CURDATE())');
    $award->execute(['user_id' => $userId, 'badge_id' => $badgeId]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $skillId = (int) ($_POST['skill_id'] ?? 0);
    $action = (string) ($_POST['roadmap_action'] ?? '');

    if ($skillId <= 0 || !in_array($action, ['Started', 'Completed'], true)) {
        $error = 'Unable to update roadmap progress.';
    } else {
        $status = $action === 'Completed' ? 'Completed' : 'Partial';
        $progress = $action === 'Completed' ? 100 : 25;
        $stmt = $pdo->prepare(
            'INSERT INTO user_roadmap_progress (user_id, skill_id, status, progress_pct, started_at, completed_at)
             VALUES (:user_id, :skill_id, :status, :progress_pct, CURDATE(), :completed_at)
             ON DUPLICATE KEY UPDATE
                status = VALUES(status),
                progress_pct = VALUES(progress_pct),
                started_at = COALESCE(started_at, CURDATE()),
                completed_at = VALUES(completed_at),
                updated_at = NOW()'
        );
        $stmt->execute([
            'user_id' => $userId,
            'skill_id' => $skillId,
            'status' => $status,
            'progress_pct' => $progress,
            'completed_at' => $action === 'Completed' ? date('Y-m-d') : null,
        ]);

        roadmap_touch_streak($pdo, $userId);
        roadmap_award_runner_if_complete($pdo, $userId);
        $message = $action === 'Completed' ? 'Skill marked as completed.' : 'Skill marked as started.';
    }
}

$latestStmt = $pdo->prepare(
    'SELECT a.id, a.match_score, cr.name AS role_name
     FROM analyses a
     INNER JOIN career_roles cr ON cr.id = a.target_role_id
     WHERE a.user_id = :user_id
     ORDER BY a.created_at DESC
     LIMIT 1'
);
$latestStmt->execute(['user_id' => $userId]);
$analysis = $latestStmt->fetch();

$items = [];
if ($analysis) {
    $itemStmt = $pdo->prepare(
        'SELECT ar.skill_id, ar.status AS gap_status, ar.gap_value, s.name AS skill_name,
                lr.title, lr.platform, lr.url, lr.duration_hours, lr.is_free,
                COALESCE(urp.status, ar.status) AS roadmap_status,
                COALESCE(urp.progress_pct, 0) AS progress_pct
         FROM analysis_results ar
         INNER JOIN skills s ON s.id = ar.skill_id
         LEFT JOIN learning_resources lr ON lr.skill_id = ar.skill_id
         LEFT JOIN user_roadmap_progress urp ON urp.skill_id = ar.skill_id AND urp.user_id = :user_id
         WHERE ar.analysis_id = :analysis_id AND ar.status IN ("Missing", "Partial")
         ORDER BY FIELD(ar.status, "Missing", "Partial"), ar.gap_value DESC, s.name'
    );
    $itemStmt->execute(['user_id' => $userId, 'analysis_id' => (int) $analysis['id']]);
    $items = $itemStmt->fetchAll();
}

$missing = [];
$partial = [];
$completed = [];
$totalHours = 0.0;

foreach ($items as $item) {
    $totalHours += (float) ($item['duration_hours'] ?? 0);
    if ($item['roadmap_status'] === 'Completed') {
        $completed[] = $item;
    } elseif ($item['gap_status'] === 'Missing') {
        $missing[] = $item;
    } else {
        $partial[] = $item;
    }
}

$totalItems = count($items);
$completePct = $totalItems > 0 ? (int) round((count($completed) / $totalItems) * 100) : 0;
$roadmapStatusItems = [
    ['label' => 'Missing', 'count' => count($missing), 'class' => 'danger', 'icon' => 'bi-exclamation-circle'],
    ['label' => 'Partial', 'count' => count($partial), 'class' => 'warning', 'icon' => 'bi-hourglass-split'],
    ['label' => 'Done', 'count' => count($completed), 'class' => 'success', 'icon' => 'bi-check2-circle'],
];
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Skill Map - Roadmap</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="/fyp_skillmapsystem/assets/css/style.css" rel="stylesheet">
</head>
<body>
  <?php require __DIR__ . '/includes/navbar.php'; ?>
  <main class="container py-4 py-lg-5">
    <div class="skillmap-tool-header mb-4">
      <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div>
          <div class="skillmap-kicker">Learning plan</div>
          <h1 class="fw-bold mb-1">My Learning Roadmap</h1>
          <div class="text-muted">
            <?= $analysis ? htmlspecialchars($analysis['role_name'], ENT_QUOTES, 'UTF-8') . ' - ' . (int) round((float) $analysis['match_score']) . '% match' : 'No analysis available yet' ?>
          </div>
        </div>
        <?php if ($analysis): ?>
          <div class="skillmap-roadmap-metrics">
            <?php foreach ($roadmapStatusItems as $statusItem): ?>
              <div class="skillmap-mini-metric">
                <i class="bi <?= htmlspecialchars($statusItem['icon'], ENT_QUOTES, 'UTF-8') ?> text-<?= htmlspecialchars($statusItem['class'], ENT_QUOTES, 'UTF-8') ?>"></i>
                <span class="fw-bold"><?= (int) $statusItem['count'] ?></span>
                <span class="text-muted"><?= htmlspecialchars($statusItem['label'], ENT_QUOTES, 'UTF-8') ?></span>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
      <?php if ($analysis): ?>
        <div class="mt-4"><?= skillmap_percent_bar($completePct) ?></div>
      <?php endif; ?>
    </div>

    <?php if ($message !== ''): ?>
      <div class="alert alert-success"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <?php if (!$analysis): ?>
      <div class="alert alert-light border">
        Run a gap analysis first to generate your learning roadmap.
        <a class="alert-link" href="/fyp_skillmapsystem/users/analyse.php">Choose a target role</a>.
      </div>
    <?php elseif ($items === []): ?>
      <div class="alert alert-success">Your latest analysis has no missing or partial skills.</div>
    <?php else: ?>
      <div class="row g-4" id="roadmapSearch">
        <div class="col-lg-8">
          <div class="skillmap-work-panel">
            <div class="skillmap-panel-toolbar">
              <div class="skillmap-search">
                <i class="bi bi-search"></i>
                <input class="form-control" type="search" placeholder="Search roadmap skills, resources, platform, or status" data-search-input data-search-target="#roadmapSearch">
              </div>
            </div>
            <div class="skillmap-panel-body d-grid gap-4">
            <?php
              $sections = [
                ['title' => 'Priority 1 - Missing Skills', 'badge' => 'Missing', 'items' => $missing, 'class' => 'danger', 'bar' => 'danger', 'icon' => 'bi-exclamation-circle'],
                ['title' => 'Priority 2 - Partially Have', 'badge' => 'Partial', 'items' => $partial, 'class' => 'warning', 'bar' => 'warning', 'icon' => 'bi-hourglass-split'],
                ['title' => 'Completed Skills', 'badge' => 'Completed', 'items' => $completed, 'class' => 'success', 'bar' => 'success', 'icon' => 'bi-check2-circle'],
              ];
            ?>
            <?php foreach ($sections as $section): ?>
              <section class="skillmap-roadmap-section skillmap-roadmap-section-<?= htmlspecialchars($section['class'], ENT_QUOTES, 'UTF-8') ?>">
                <div class="skillmap-section-heading mb-3">
                  <div>
                    <h2 class="h5 fw-bold mb-1"><i class="bi <?= htmlspecialchars($section['icon'], ENT_QUOTES, 'UTF-8') ?> me-2"></i><?= htmlspecialchars($section['title'], ENT_QUOTES, 'UTF-8') ?></h2>
                    <div class="text-muted small">Sorted by gap size and priority.</div>
                  </div>
                  <span class="badge text-bg-light border"><?= count($section['items']) ?> <?= htmlspecialchars($section['badge'], ENT_QUOTES, 'UTF-8') ?></span>
                </div>

                  <?php if ($section['items'] === []): ?>
                    <div class="alert alert-light border mb-0">No skills in this section.</div>
                  <?php else: ?>
                    <div class="d-grid gap-3">
                      <?php foreach ($section['items'] as $item): ?>
                        <div class="skillmap-roadmap-item" data-search-item data-search-text="<?= htmlspecialchars($item['skill_name'] . ' ' . $section['badge'] . ' ' . $item['title'] . ' ' . $item['platform'] . ' ' . $item['roadmap_status'], ENT_QUOTES, 'UTF-8') ?>">
                          <div class="skillmap-roadmap-item-top">
                            <div class="min-w-0">
                              <div class="fw-semibold"><?= htmlspecialchars($item['skill_name'], ENT_QUOTES, 'UTF-8') ?></div>
                              <?php if ($item['title']): ?>
                                <div class="small text-muted">
                                  <?= htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8') ?> -
                                  <?= htmlspecialchars((string) $item['platform'], ENT_QUOTES, 'UTF-8') ?>
                                </div>
                              <?php else: ?>
                                <div class="small text-muted">No resource available yet</div>
                              <?php endif; ?>
                            </div>
                            <div class="skillmap-resource-meta">
                              <div class="small text-muted"><?= $item['duration_hours'] !== null ? htmlspecialchars((string) $item['duration_hours'], ENT_QUOTES, 'UTF-8') . ' hours' : '-' ?></div>
                              <?php if ($item['title']): ?>
                                <?= (int) $item['is_free'] === 1 ? '<span class="badge text-bg-success">Free</span>' : '<span class="badge text-bg-light border">Paid</span>' ?>
                              <?php endif; ?>
                            </div>
                          </div>
                          <div class="progress mt-3"><div class="progress-bar bg-<?= htmlspecialchars($section['bar'], ENT_QUOTES, 'UTF-8') ?>" style="width: <?= (int) $item['progress_pct'] ?>%"></div></div>
                          <div class="skillmap-action-row mt-3">
                            <?php if ($item['url']): ?>
                              <a class="btn btn-primary btn-sm" href="<?= htmlspecialchars($item['url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener"><i class="bi bi-box-arrow-up-right me-1"></i>Open Resource</a>
                            <?php endif; ?>
                            <form method="post">
                              <input type="hidden" name="skill_id" value="<?= (int) $item['skill_id'] ?>">
                              <button class="btn btn-outline-primary btn-sm" type="submit" name="roadmap_action" value="Started"><i class="bi bi-play-circle me-1"></i>Started</button>
                            </form>
                            <form method="post">
                              <input type="hidden" name="skill_id" value="<?= (int) $item['skill_id'] ?>">
                              <button class="btn btn-outline-success btn-sm" type="submit" name="roadmap_action" value="Completed"><i class="bi bi-check2-circle me-1"></i>Completed</button>
                            </form>
                          </div>
                        </div>
                      <?php endforeach; ?>
                    </div>
                  <?php endif; ?>
              </section>
            <?php endforeach; ?>
            <div class="alert alert-light border mb-0 d-none" data-search-empty>No matching roadmap items found.</div>
            </div>
          </div>
        </div>

        <div class="col-lg-4">
          <div class="skillmap-right-rail d-grid gap-4">
            <div class="card">
              <div class="card-body p-4 text-center">
                <h2 class="h5 fw-bold mb-3">Roadmap Summary</h2>
                <?= skillmap_progress_ring($completePct) ?>
                <div class="d-grid gap-2 mt-4 text-start">
                  <?php foreach ($roadmapStatusItems as $statusItem): ?>
                    <div class="skillmap-summary-line">
                      <span><i class="bi <?= htmlspecialchars($statusItem['icon'], ENT_QUOTES, 'UTF-8') ?> text-<?= htmlspecialchars($statusItem['class'], ENT_QUOTES, 'UTF-8') ?> me-2"></i><?= htmlspecialchars($statusItem['label'], ENT_QUOTES, 'UTF-8') ?></span>
                      <strong><?= (int) $statusItem['count'] ?></strong>
                    </div>
                  <?php endforeach; ?>
                </div>
                <hr>
                <div class="small text-muted"><?= number_format($totalHours, 1) ?> hours estimated</div>
                <a href="/fyp_skillmapsystem/users/analyse.php" class="btn btn-outline-primary btn-sm mt-3"><i class="bi bi-arrow-repeat me-1"></i>Switch Target Role</a>
              </div>
            </div>
          </div>
        </div>
      </div>
    <?php endif; ?>
    <?php require __DIR__ . '/../includes/footer.php'; ?>
  </main>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="/fyp_skillmapsystem/assets/js/app.js"></script>
</body>
</html>
