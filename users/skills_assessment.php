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
        if ($rating < 1 || $rating > 5) {
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
                $stmt->execute([
                    'user_id' => $userId,
                    'skill_id' => $skillId,
                    'rating' => (int) $ratings[$skillId],
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
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
      <div>
        <h1 class="fw-bold mb-1">Skill Assessment</h1>
        <div class="text-muted">Rate every active skill to unlock accurate gap analysis</div>
      </div>
      <button form="assessmentForm" type="submit" class="btn btn-primary">
        <i class="bi bi-check2 me-1"></i>Save Assessment
      </button>
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
          <div class="card">
            <div class="card-body p-4">
              <ul class="nav nav-pills flex-wrap gap-2 mb-4">
                <?php foreach ($skillsByCategory as $category => $group): ?>
                  <li class="nav-item">
                    <button type="button" class="nav-link <?= $category === $activeCategory ? 'active' : 'bg-light text-dark' ?>" data-skillmap-tab="<?= htmlspecialchars($category, ENT_QUOTES, 'UTF-8') ?>">
                      <i class="bi <?= htmlspecialchars($group['icon'], ENT_QUOTES, 'UTF-8') ?> me-1"></i><?= htmlspecialchars($category, ENT_QUOTES, 'UTF-8') ?>
                    </button>
                  </li>
                <?php endforeach; ?>
              </ul>

              <?php foreach ($skillsByCategory as $category => $group): ?>
                <div data-skillmap-tab-panel="<?= htmlspecialchars($category, ENT_QUOTES, 'UTF-8') ?>" class="<?= $category === $activeCategory ? '' : 'd-none' ?>">
                  <div class="d-grid gap-3">
                    <?php foreach ($group['skills'] as $skill): ?>
                      <?php $ratingValue = isset($_POST['ratings'][(int) $skill['id']]) ? (int) $_POST['ratings'][(int) $skill['id']] : (int) $skill['rating']; ?>
                      <div class="border rounded-4 p-3 p-md-4 <?= isset($errors[(int) $skill['id']]) ? 'border-danger' : '' ?>">
                        <div class="d-flex justify-content-between align-items-start gap-3">
                          <div>
                            <div class="fw-bold"><?= htmlspecialchars($skill['name'], ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="text-muted small"><?= htmlspecialchars($skill['description'], ENT_QUOTES, 'UTF-8') ?></div>
                          </div>
                          <?= skillmap_rating_badge($ratingValue) ?>
                        </div>
                        <div class="d-flex flex-wrap align-items-center gap-3 mt-3">
                          <?= skillmap_star_rating($ratingValue) ?>
                          <input type="hidden" class="skill-rating-value" name="ratings[<?= (int) $skill['id'] ?>]" value="<?= $ratingValue ?>">
                          <span class="small text-muted">Difficulty <?= (int) $skill['difficulty'] ?>/5</span>
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
            </div>
          </div>
        </div>

        <div class="col-lg-4">
          <div class="card">
            <div class="card-body p-4">
              <h2 class="h5 fw-bold mb-3">Assessment Status</h2>
              <?php
                $ratedCount = count(array_filter($skillRows, static fn(array $skill): bool => (int) $skill['rating'] > 0));
                $totalCount = count($skillRows);
                $completion = $totalCount > 0 ? (int) round(($ratedCount / $totalCount) * 100) : 0;
              ?>
              <?= skillmap_progress_ring($completion) ?>
              <div class="small text-muted text-center mt-3"><?= $ratedCount ?> of <?= $totalCount ?> active skills rated</div>
              <hr>
              <div class="d-grid gap-2">
                <a class="btn btn-outline-secondary" href="/fyp_skillmapsystem/users/profile.php">Back to Profile</a>
                <a class="btn btn-outline-primary" href="/fyp_skillmapsystem/users/analyse.php">Choose Target Role</a>
              </div>
            </div>
          </div>
        </div>
      </form>
    <?php endif; ?>
  </main>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="/fyp_skillmapsystem/assets/js/app.js"></script>
</body>
</html>
