<?php
// Gap analysis engine and results page.

require_once __DIR__ . '/../includes/auth_check.php';
skillmap_require_auth(['student']);

$activePage = 'analyse';
$user = skillmap_current_user();
$userId = (int) ($user['id'] ?? 0);
$targetRoleId = (int) ($_GET['target_role_id'] ?? $_SESSION['target_role_id'] ?? 0);
$error = '';
$analysis = null;
$results = [];
$summary = ['Have' => 0, 'Partial' => 0, 'Missing' => 0];

if ($targetRoleId > 0) {
    $_SESSION['target_role_id'] = $targetRoleId;
}

if ($targetRoleId <= 0) {
    $latest = $pdo->prepare('SELECT target_role_id FROM analyses WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 1');
    $latest->execute(['user_id' => $userId]);
    $targetRoleId = (int) ($latest->fetchColumn() ?: 0);
}

$role = null;
if ($targetRoleId > 0) {
    $stmt = $pdo->prepare('SELECT id, name, type, description FROM career_roles WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $targetRoleId]);
    $role = $stmt->fetch() ?: null;
}

if (!$role) {
    $error = 'Please choose a target role before running gap analysis.';
} else {
    $ratedStmt = $pdo->prepare('SELECT COUNT(*) FROM user_skill_ratings WHERE user_id = :user_id');
    $ratedStmt->execute(['user_id' => $userId]);
    $ratedCount = (int) $ratedStmt->fetchColumn();

    if ($ratedCount === 0) {
        $error = 'Complete your skill assessment before running gap analysis.';
    } else {
        $benchmarkStmt = $pdo->prepare(
            'SELECT rb.skill_id, rb.required_rating, rb.priority, s.name AS skill_name, c.name AS category,
                    COALESCE(r.rating, 0) AS your_rating
             FROM role_skill_benchmarks rb
             INNER JOIN skills s ON s.id = rb.skill_id
             INNER JOIN skill_categories c ON c.id = s.category_id
             LEFT JOIN user_skill_ratings r ON r.skill_id = rb.skill_id AND r.user_id = :user_id
             WHERE rb.role_id = :role_id
             ORDER BY FIELD(rb.priority, "Critical", "Important", "Optional"), c.name, s.name'
        );
        $benchmarkStmt->execute(['user_id' => $userId, 'role_id' => (int) $role['id']]);
        $benchmarks = $benchmarkStmt->fetchAll();

        if ($benchmarks === []) {
            $error = 'No skill benchmarks are mapped for this target role yet.';
        } else {
            $scoreEarned = 0;
            $scoreRequired = 0;
            $topMissing = [];
            $results = [];

            foreach ($benchmarks as $row) {
                $yourRating = (int) $row['your_rating'];
                $requiredRating = (int) $row['required_rating'];
                $gapValue = max($requiredRating - $yourRating, 0);

                if ($yourRating >= $requiredRating) {
                    $status = 'Have';
                } elseif ($gapValue <= 1) {
                    $status = 'Partial';
                } else {
                    $status = 'Missing';
                }

                $summary[$status]++;
                $scoreEarned += min($yourRating, $requiredRating);
                $scoreRequired += $requiredRating;

                if ($status === 'Missing') {
                    $topMissing[] = $row['skill_name'];
                }

                $results[] = [
                    'skill_id' => (int) $row['skill_id'],
                    'skill_name' => $row['skill_name'],
                    'category' => $row['category'],
                    'your_rating' => $yourRating,
                    'required_rating' => $requiredRating,
                    'priority' => $row['priority'],
                    'status' => $status,
                    'gap_value' => $gapValue,
                ];
            }

            $matchScore = $scoreRequired > 0 ? round(($scoreEarned / $scoreRequired) * 100, 2) : 0.0;
            $focusSkills = array_slice($topMissing, 0, 3);
            $aiSummary = 'You match ' . number_format($matchScore, 2) . '% of the ' . $role['name'] . ' role requirements.';
            if ($focusSkills !== []) {
                $aiSummary .= ' Focus on: ' . implode(', ', $focusSkills) . '.';
            }

            $pdo->beginTransaction();
            try {
                $insertAnalysis = $pdo->prepare(
                    'INSERT INTO analyses (user_id, target_role_id, match_score, ai_summary)
                     VALUES (:user_id, :target_role_id, :match_score, :ai_summary)'
                );
                $insertAnalysis->execute([
                    'user_id' => $userId,
                    'target_role_id' => (int) $role['id'],
                    'match_score' => $matchScore,
                    'ai_summary' => $aiSummary,
                ]);
                $analysisId = (int) $pdo->lastInsertId();

                $insertResult = $pdo->prepare(
                    'INSERT INTO analysis_results (analysis_id, skill_id, status, your_rating, required_rating, gap_value)
                     VALUES (:analysis_id, :skill_id, :status, :your_rating, :required_rating, :gap_value)'
                );
                foreach ($results as $result) {
                    $insertResult->execute([
                        'analysis_id' => $analysisId,
                        'skill_id' => $result['skill_id'],
                        'status' => $result['status'],
                        'your_rating' => $result['your_rating'],
                        'required_rating' => $result['required_rating'],
                        'gap_value' => $result['gap_value'],
                    ]);
                }

                $badgeStmt = $pdo->prepare('SELECT id FROM badges WHERE name = "Bronze Explorer" LIMIT 1');
                $badgeStmt->execute();
                $badgeId = (int) ($badgeStmt->fetchColumn() ?: 0);
                if ($badgeId > 0) {
                    $exists = $pdo->prepare('SELECT id FROM user_badges WHERE user_id = :user_id AND badge_id = :badge_id LIMIT 1');
                    $exists->execute(['user_id' => $userId, 'badge_id' => $badgeId]);
                    if (!$exists->fetch()) {
                        $award = $pdo->prepare('INSERT INTO user_badges (user_id, badge_id, earned_at) VALUES (:user_id, :badge_id, CURDATE())');
                        $award->execute(['user_id' => $userId, 'badge_id' => $badgeId]);
                    }
                }

                $pdo->commit();
                $analysis = [
                    'id' => $analysisId,
                    'role' => $role,
                    'match_score' => $matchScore,
                    'ai_summary' => $aiSummary,
                ];
            } catch (Throwable $exception) {
                $pdo->rollBack();
                $error = 'Unable to save the gap analysis. Please try again.';
                $analysis = null;
                $results = [];
            }
        }
    }
}

function gap_status_badge(string $status): string
{
    if ($status === 'Have') {
        return '<span class="badge text-bg-success">Have</span>';
    }

    if ($status === 'Partial') {
        return '<span class="badge text-bg-warning">Partially Have</span>';
    }

    return '<span class="badge text-bg-danger">Missing</span>';
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Skill Map - Gap Analysis Results</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="/fyp_skillmapsystem/assets/css/style.css" rel="stylesheet">
</head>
<body>
  <?php require __DIR__ . '/includes/navbar.php'; ?>
  <main class="container py-4 py-lg-5">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
      <div>
        <h1 class="fw-bold mb-1">Gap Analysis Results</h1>
        <div class="text-muted"><?= $role ? htmlspecialchars($role['name'], ENT_QUOTES, 'UTF-8') : 'No target role selected' ?></div>
      </div>
      <a class="btn btn-outline-secondary" href="/fyp_skillmapsystem/users/analyse.php">
        <i class="bi bi-arrow-left me-1"></i>Change Role
      </a>
    </div>

    <?php if ($error !== ''): ?>
      <div class="alert alert-warning">
        <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        <?php if (str_contains($error, 'assessment')): ?>
          <a class="alert-link" href="/fyp_skillmapsystem/users/skills_assessment.php">Go to skill assessment</a>.
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <?php if ($analysis): ?>
      <div class="row g-4 mb-4">
        <div class="col-lg-4">
          <div class="card h-100">
            <div class="card-body text-center p-4">
              <h2 class="h5 fw-bold mb-3">Overall Match</h2>
              <?= skillmap_progress_ring((int) round($analysis['match_score']), $analysis['role']['name']) ?>
            </div>
          </div>
        </div>
        <div class="col-lg-4">
          <div class="card h-100">
            <div class="card-body p-4">
              <h2 class="h5 fw-bold mb-3">Skill Breakdown</h2>
              <?php $total = max(count($results), 1); ?>
              <div class="mb-3"><div class="d-flex justify-content-between small mb-1"><span class="text-success fw-semibold">Have</span><span><?= $summary['Have'] ?></span></div><?= skillmap_percent_bar((int) round(($summary['Have'] / $total) * 100), 'success') ?></div>
              <div class="mb-3"><div class="d-flex justify-content-between small mb-1"><span class="text-warning fw-semibold">Partially Have</span><span><?= $summary['Partial'] ?></span></div><?= skillmap_percent_bar((int) round(($summary['Partial'] / $total) * 100), 'warning') ?></div>
              <div><div class="d-flex justify-content-between small mb-1"><span class="text-danger fw-semibold">Missing</span><span><?= $summary['Missing'] ?></span></div><?= skillmap_percent_bar((int) round(($summary['Missing'] / $total) * 100), 'danger') ?></div>
            </div>
          </div>
        </div>
        <div class="col-lg-4">
          <div class="card h-100">
            <div class="card-body p-4">
              <h2 class="h5 fw-bold mb-3">AI Recommendation</h2>
              <p class="text-muted mb-0"><?= htmlspecialchars($analysis['ai_summary'], ENT_QUOTES, 'UTF-8') ?></p>
            </div>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="card-body p-4">
          <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
            <h2 class="h5 fw-bold mb-0">Skill Breakdown</h2>
            <div class="btn-group btn-group-sm">
              <button type="button" class="btn btn-outline-secondary active" data-skill-status-filter="all">All</button>
              <button type="button" class="btn btn-outline-success" data-skill-status-filter="Have">Have</button>
              <button type="button" class="btn btn-outline-warning" data-skill-status-filter="Partial">Partially Have</button>
              <button type="button" class="btn btn-outline-danger" data-skill-status-filter="Missing">Missing</button>
            </div>
          </div>
          <div class="table-responsive">
            <table class="table align-middle">
              <thead><tr><th>Skill</th><th>Category</th><th>Your Rating</th><th>Required</th><th>Priority</th><th>Status</th><th>Gap</th></tr></thead>
              <tbody>
                <?php foreach ($results as $result): ?>
                  <tr data-skill-status="<?= htmlspecialchars($result['status'], ENT_QUOTES, 'UTF-8') ?>">
                    <td class="fw-semibold"><?= htmlspecialchars($result['skill_name'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><span class="badge badge-soft rounded-pill"><?= htmlspecialchars($result['category'], ENT_QUOTES, 'UTF-8') ?></span></td>
                    <td><?= skillmap_rating_badge((int) $result['your_rating']) ?></td>
                    <td><?= skillmap_rating_badge((int) $result['required_rating']) ?></td>
                    <td><?= htmlspecialchars($result['priority'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= gap_status_badge($result['status']) ?></td>
                    <td class="fw-semibold <?= (int) $result['gap_value'] > 0 ? 'text-danger' : 'text-success' ?>">+<?= (int) $result['gap_value'] ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <div class="d-flex flex-wrap gap-3 mt-2">
            <a class="btn btn-success" href="/fyp_skillmapsystem/users/roadmap.php">View Learning Roadmap</a>
            <a class="btn btn-danger" href="/fyp_skillmapsystem/users/report.php">Download PDF</a>
          </div>
        </div>
      </div>
    <?php endif; ?>
  </main>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="/fyp_skillmapsystem/assets/js/app.js"></script>
</body>
</html>
