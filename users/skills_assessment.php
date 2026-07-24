<?php
// Student self-assessment for active skills.

require_once __DIR__ . '/../includes/auth_check.php';
skillmap_require_auth(['student']);

$activePage = 'assessment';
$user = skillmap_current_user();
$userId = (int) ($user['id'] ?? 0);
$message = '';
$errors = [];

function assessment_touch_streak(PDO $pdo, int $userId): void
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

    $current = (int) $row['current_streak'];
    $best = (int) $row['best_streak'];
    $lastActivity = (string) ($row['last_activity'] ?? '');

    if ($lastActivity === $today) {
        return;
    }

    $current = $lastActivity === $yesterday ? $current + 1 : 1;
    $best = max($best, $current);

    $update = $pdo->prepare(
        'UPDATE learning_streaks
         SET current_streak = :current_streak, best_streak = :best_streak, last_activity = :last_activity
         WHERE user_id = :user_id'
    );
    $update->execute([
        'current_streak' => $current,
        'best_streak' => $best,
        'last_activity' => $today,
        'user_id' => $userId,
    ]);
}

$skillStmt = $pdo->prepare(
    'SELECT s.id, s.name, s.description, s.difficulty, c.name AS category, c.icon, COALESCE(r.rating, 0) AS rating, COALESCE(r.notes, "") AS notes
     FROM skills s
     INNER JOIN skill_categories c ON c.id = s.category_id
     LEFT JOIN user_skill_ratings r ON r.skill_id = s.id AND r.user_id = :user_id
     WHERE c.type = "Skill Category" AND s.status = "Active"
     ORDER BY c.id, s.name'
);
$skillStmt->execute(['user_id' => $userId]);
$skillRows = $skillStmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ratings = is_array($_POST['ratings'] ?? null) ? $_POST['ratings'] : [];
    $notes = is_array($_POST['notes'] ?? null) ? $_POST['notes'] : [];
    $activeSkillIds = array_map(static fn(array $skill): int => (int) $skill['id'], $skillRows);

    foreach ($activeSkillIds as $skillId) {
        $rating = isset($ratings[$skillId]) ? (int) $ratings[$skillId] : 0;
        if ($rating !== 0 && ($rating < 1 || $rating > 5)) {
            $errors[$skillId] = 'Please rate this skill from 1 to 5.';
        }
    }

    if ($errors === []) {
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO user_skill_ratings (user_id, skill_id, rating, notes)
                 VALUES (:user_id, :skill_id, :rating, :notes)
                 ON DUPLICATE KEY UPDATE rating = VALUES(rating), notes = VALUES(notes), updated_at = NOW()'
            );

            foreach ($activeSkillIds as $skillId) {
                $rating = isset($ratings[$skillId]) ? max(0, min(5, (int) $ratings[$skillId])) : 0;
                $stmt->execute([
                    'user_id' => $userId,
                    'skill_id' => $skillId,
                    'rating' => $rating,
                    'notes' => trim((string) ($notes[$skillId] ?? '')) ?: null,
                ]);
            }

            assessment_touch_streak($pdo, $userId);
            $pdo->commit();
            $message = 'Self-assessment saved successfully.';

            $skillStmt->execute(['user_id' => $userId]);
            $skillRows = $skillStmt->fetchAll();
        } catch (Throwable $exception) {
            $pdo->rollBack();
            $errors[0] = 'Unable to save your assessment. Please try again.';
        }
    }
}

$skillsByCategory = [];
foreach ($skillRows as $skill) {
    $skillsByCategory[$skill['category']]['icon'] = $skill['icon'];
    $skillsByCategory[$skill['category']]['skills'][] = $skill;
}
$activeCategory = array_key_first($skillsByCategory);
$ratedCount = count(array_filter($skillRows, static fn(array $skill): bool => (int) $skill['rating'] > 0));
$totalCount = count($skillRows);
$completion = $totalCount > 0 ? (int) round(($ratedCount / $totalCount) * 100) : 0;
$categoryProgress = [];
foreach ($skillsByCategory as $category => $group) {
    $categoryTotal = count($group['skills']);
    $categoryRated = count(array_filter($group['skills'], static fn(array $skill): bool => (int) $skill['rating'] > 0));
    $categoryProgress[$category] = [
        'icon' => $group['icon'],
        'total' => $categoryTotal,
        'rated' => $categoryRated,
        'percent' => $categoryTotal > 0 ? (int) round(($categoryRated / $categoryTotal) * 100) : 0,
    ];
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Skill Map - Skill Assessment</title>
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
          <div class="skillmap-kicker">Self assessment</div>
          <h1 class="fw-bold mb-1">Skill Assessment</h1>
          <div class="text-muted">Rate active skills so your gap analysis and roadmap stay accurate.</div>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2">
          <span class="skillmap-pill"><i class="bi bi-check2-circle"></i><?= $ratedCount ?> / <?= $totalCount ?> rated</span>
          <button form="assessmentForm" type="submit" class="btn btn-primary">
            <i class="bi bi-check2 me-1"></i>Save Assessment
          </button>
        </div>
      </div>
    </div>

    <?php if ($message !== ''): ?>
      <div class="alert alert-success"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
    <?php if (isset($errors[0])): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($errors[0], ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <?php if ($skillsByCategory === []): ?>
      <div class="alert alert-light border">No active skills are available for assessment yet.</div>
    <?php else: ?>
      <form id="assessmentForm" method="post" class="row g-4">
        <div class="col-lg-8">
          <div class="skillmap-work-panel" data-search-scope>
            <div class="skillmap-panel-toolbar">
              <div class="skillmap-search mb-4">
                <i class="bi bi-search"></i>
                <input class="form-control" type="search" placeholder="Search skills, categories, or descriptions" data-search-input>
              </div>
              <ul class="nav nav-pills skillmap-scroll-tabs gap-2 mb-4">
                <?php foreach ($skillsByCategory as $category => $group): ?>
                  <li class="nav-item">
                    <button type="button" class="nav-link <?= $category === $activeCategory ? 'active' : 'bg-light text-dark' ?>" data-skillmap-tab="<?= htmlspecialchars($category, ENT_QUOTES, 'UTF-8') ?>">
                      <i class="bi <?= htmlspecialchars($group['icon'], ENT_QUOTES, 'UTF-8') ?> me-1"></i><?= htmlspecialchars($category, ENT_QUOTES, 'UTF-8') ?>
                    </button>
                  </li>
                <?php endforeach; ?>
              </ul>
            </div>

            <div class="skillmap-panel-body">
              <?php foreach ($skillsByCategory as $category => $group): ?>
                <div data-skillmap-tab-panel="<?= htmlspecialchars($category, ENT_QUOTES, 'UTF-8') ?>" class="<?= $category === $activeCategory ? '' : 'd-none' ?>">
                  <div class="skillmap-section-heading mb-3">
                    <div>
                      <h2 class="h5 fw-bold mb-1"><i class="bi <?= htmlspecialchars($group['icon'], ENT_QUOTES, 'UTF-8') ?> me-2 text-primary"></i><?= htmlspecialchars($category, ENT_QUOTES, 'UTF-8') ?></h2>
                      <div class="text-muted small"><?= count($group['skills']) ?> skills in this category</div>
                    </div>
                  </div>
                  <div class="d-grid gap-3">
                    <?php foreach ($group['skills'] as $skill): ?>
                      <?php $ratingValue = isset($_POST['ratings'][(int) $skill['id']]) ? (int) $_POST['ratings'][(int) $skill['id']] : (int) $skill['rating']; ?>
                      <div class="skillmap-skill-card <?= isset($errors[(int) $skill['id']]) ? 'border-danger' : '' ?>" data-search-item data-search-text="<?= htmlspecialchars($skill['name'] . ' ' . $skill['category'] . ' ' . $skill['description'] . ' difficulty ' . $skill['difficulty'], ENT_QUOTES, 'UTF-8') ?>">
                        <div class="skillmap-skill-card-top">
                          <div class="min-w-0">
                            <div class="fw-bold"><?= htmlspecialchars($skill['name'], ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="text-muted small"><?= htmlspecialchars($skill['description'], ENT_QUOTES, 'UTF-8') ?></div>
                          </div>
                          <?= skillmap_rating_badge($ratingValue) ?>
                        </div>
                        <div class="skillmap-rating-row mt-3">
                          <?= skillmap_star_rating($ratingValue) ?>
                          <input type="hidden" class="skill-rating-value" name="ratings[<?= (int) $skill['id'] ?>]" value="<?= $ratingValue ?>">
                          <span class="skillmap-difficulty">Difficulty <?= (int) $skill['difficulty'] ?>/5</span>
                        </div>
                        <label class="form-label small text-muted mt-3">Notes</label>
                        <textarea name="notes[<?= (int) $skill['id'] ?>]" class="form-control" rows="2"><?= htmlspecialchars((string) ($_POST['notes'][(int) $skill['id']] ?? $skill['notes']), ENT_QUOTES, 'UTF-8') ?></textarea>
                        <?php if (isset($errors[(int) $skill['id']])): ?>
                          <div class="text-danger small mt-2"><?= htmlspecialchars($errors[(int) $skill['id']], ENT_QUOTES, 'UTF-8') ?></div>
                        <?php endif; ?>
                      </div>
                    <?php endforeach; ?>
                  </div>
                </div>
              <?php endforeach; ?>
              <div class="alert alert-light border mb-0 d-none" data-search-empty>No matching skills found.</div>
            </div>
          </div>
        </div>

        <div class="col-lg-4">
          <div class="skillmap-right-rail d-grid gap-4">
            <div class="card">
              <div class="card-body p-4">
                <h2 class="h5 fw-bold mb-3">Assessment Status</h2>
              <?= skillmap_progress_ring($completion) ?>
              <div class="small text-muted text-center mt-3"><?= $ratedCount ?> of <?= $totalCount ?> active skills rated</div>
                <div class="d-grid gap-2 mt-4">
                  <a class="btn btn-outline-secondary" href="/fyp_skillmapsystem/users/profile.php"><i class="bi bi-person me-1"></i>Profile</a>
                  <a class="btn btn-outline-primary" href="/fyp_skillmapsystem/users/analyse.php"><i class="bi bi-search me-1"></i>Choose Target Role</a>
                </div>
              </div>
            </div>

            <div class="card">
              <div class="card-body p-4">
                <h2 class="h6 fw-bold mb-3">Category Progress</h2>
                <div class="d-grid gap-3">
                  <?php foreach ($categoryProgress as $category => $progress): ?>
                    <div>
                      <div class="d-flex justify-content-between align-items-center small mb-1">
                        <span><i class="bi <?= htmlspecialchars($progress['icon'], ENT_QUOTES, 'UTF-8') ?> me-1 text-primary"></i><?= htmlspecialchars($category, ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="text-muted"><?= (int) $progress['rated'] ?>/<?= (int) $progress['total'] ?></span>
                      </div>
                      <div class="progress"><div class="progress-bar" style="width: <?= (int) $progress['percent'] ?>%"></div></div>
                    </div>
                  <?php endforeach; ?>
                </div>
              </div>
            </div>
          </div>
        </div>
      </form>
    <?php endif; ?>
    <?php require __DIR__ . '/../includes/footer.php'; ?>
  </main>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="/fyp_skillmapsystem/assets/js/app.js"></script>
</body>
</html>
