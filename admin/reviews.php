<?php
// Lecturer and admin page for reviewing and editing student skill ratings.

require_once __DIR__ . '/../includes/auth_check.php';
skillmap_require_permission('review_student_skills');

$activePage = 'reviews';
$message = '';

$students = skillmap_fetch_all(
    'SELECT u.id, u.name, u.email, u.programme, u.year_level, u.avatar_initials, u.profile_icon, u.status,
            COALESCE((SELECT COUNT(*) FROM user_skill_ratings r WHERE r.user_id = u.id), 0) AS rated_skills,
            COALESCE((SELECT COUNT(*) FROM analyses a WHERE a.user_id = u.id), 0) AS analyses_done,
            COALESCE((SELECT ROUND(MAX(a.match_score)) FROM analyses a WHERE a.user_id = u.id), 0) AS best_match
     FROM users u
     WHERE u.role = "student"
     ORDER BY u.created_at DESC'
);
$selectedStudentId = (int) ($_POST['student_id'] ?? $_GET['student_id'] ?? ($students[0]['id'] ?? 0));

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['save_review'])) {
    $ratings = is_array($_POST['ratings'] ?? null) ? $_POST['ratings'] : [];
    if ($selectedStudentId > 0 && skillmap_save_profile($selectedStudentId, $ratings)) {
        $message = 'Student review saved successfully.';
    } else {
        $message = 'Unable to save the review.';
    }
}

$selectedStudent = null;
foreach ($students as $student) {
    if ((int) $student['id'] === $selectedStudentId) {
        $selectedStudent = $student;
        break;
    }
}

if (!$selectedStudent && $students !== []) {
    $selectedStudent = $students[0];
    $selectedStudentId = (int) $selectedStudent['id'];
}

$skills = [];
$categoryStats = [];
$ratingCounts = [0 => 0, 1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
$averageRating = 0;
$ratedCount = 0;
$totalSkillCount = 0;

if ($selectedStudentId > 0) {
    $skillRows = skillmap_fetch_all(
        'SELECT s.id AS skill_id, c.name AS category, c.icon, s.name, s.description, COALESCE(r.rating, 0) AS score
         FROM skills s
         INNER JOIN skill_categories c ON c.id = s.category_id
         LEFT JOIN user_skill_ratings r ON r.skill_id = s.id AND r.user_id = ?
         WHERE s.status = "Active" AND c.type = "Skill Category"
         ORDER BY c.name, s.name',
        'i',
        [$selectedStudentId]
    );

    foreach ($skillRows as $skillRow) {
        $score = (int) $skillRow['score'];
        $category = (string) $skillRow['category'];
        $skills[$category]['icon'] = $skillRow['icon'];
        $skills[$category]['items'][] = $skillRow;

        $categoryStats[$category]['total'] = ($categoryStats[$category]['total'] ?? 0) + 1;
        $categoryStats[$category]['rated'] = ($categoryStats[$category]['rated'] ?? 0) + ($score > 0 ? 1 : 0);
        $categoryStats[$category]['score_sum'] = ($categoryStats[$category]['score_sum'] ?? 0) + $score;

        $ratingCounts[$score]++;
        $ratedCount += $score > 0 ? 1 : 0;
        $averageRating += $score;
        $totalSkillCount++;
    }
}

$completionPct = $totalSkillCount > 0 ? (int) round(($ratedCount / $totalSkillCount) * 100) : 0;
$averageRating = $totalSkillCount > 0 ? round($averageRating / $totalSkillCount, 1) : 0;
$categoryLabels = array_keys($categoryStats);
$categoryAverages = array_map(static function (array $row): int {
    return (int) round(($row['score_sum'] / max((int) $row['total'], 1)) * 20);
}, $categoryStats);
$distributionLabels = ['Not Rated', '1', '2', '3', '4', '5'];
$distributionValues = array_values($ratingCounts);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Skill Map - Student Reviews</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="/fyp_skillmapsystem/assets/css/style.css" rel="stylesheet">
</head>
<body>
  <?php require __DIR__ . '/includes/navbar.php'; ?>
  <main class="container-fluid py-4 py-lg-5">
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
      <div>
        <h1 class="fw-bold mb-1">Student Skill Reviews</h1>
        <div class="text-muted">Review student self-assessments, rating coverage, and skill readiness graphs.</div>
      </div>
      <a class="btn btn-outline-secondary" href="/fyp_skillmapsystem/admin/users.php"><i class="bi bi-people me-1"></i>Manage Students</a>
    </div>

    <?php if ($message !== ''): ?>
      <div class="alert alert-info"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <div class="row g-4">
      <div class="col-xl-3">
        <div class="card h-100" data-search-scope>
          <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <h2 class="h5 fw-bold mb-0">Students</h2>
              <span class="badge text-bg-light border"><?= count($students) ?> total</span>
            </div>
            <div class="skillmap-search mb-3">
              <i class="bi bi-search"></i>
              <input class="form-control" type="search" placeholder="Search students" data-search-input>
            </div>
            <div class="skillmap-student-review-list">
              <?php foreach ($students as $student): ?>
                <?php $isSelected = (int) $student['id'] === $selectedStudentId; ?>
                <a class="skillmap-review-student <?= $isSelected ? 'active' : '' ?>" href="/fyp_skillmapsystem/admin/reviews.php?student_id=<?= (int) $student['id'] ?>" data-search-item data-search-text="<?= htmlspecialchars($student['name'] . ' ' . $student['email'] . ' ' . $student['programme'] . ' ' . $student['year_level'] . ' ' . $student['status'], ENT_QUOTES, 'UTF-8') ?>">
                  <?php if (!empty($student['profile_icon'])): ?>
                    <img class="table-profile-icon" src="/fyp_skillmapsystem/<?= htmlspecialchars((string) $student['profile_icon'], ENT_QUOTES, 'UTF-8') ?>" alt="">
                  <?php else: ?>
                    <span class="avatar-circle bg-primary"><?= htmlspecialchars(strtoupper(substr($student['avatar_initials'] ?: $student['name'], 0, 2)), ENT_QUOTES, 'UTF-8') ?></span>
                  <?php endif; ?>
                  <span class="flex-grow-1">
                    <span class="d-block fw-semibold"><?= htmlspecialchars($student['name'], ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="small"><?= htmlspecialchars($student['programme'], ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars($student['year_level'], ENT_QUOTES, 'UTF-8') ?></span>
                  </span>
                  <span class="badge <?= $student['status'] === 'Active' ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= htmlspecialchars($student['status'], ENT_QUOTES, 'UTF-8') ?></span>
                </a>
              <?php endforeach; ?>
              <?php if ($students === []): ?>
                <div class="alert alert-light border mb-0">No student records were found.</div>
              <?php endif; ?>
              <div class="alert alert-light border mb-0 d-none" data-search-empty>No matching students found.</div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-xl-9">
        <?php if (!$selectedStudent): ?>
          <div class="alert alert-warning">No student selected.</div>
        <?php else: ?>
          <div class="row g-3 mb-4">
            <div class="col-md-6 col-xl-3">
              <div class="card h-100"><div class="card-body">
                <div class="text-muted small">Assessment Coverage</div>
                <div class="fs-3 fw-bold"><?= $completionPct ?>%</div>
                <div class="small text-muted"><?= $ratedCount ?> of <?= $totalSkillCount ?> skills rated</div>
              </div></div>
            </div>
            <div class="col-md-6 col-xl-3">
              <div class="card h-100"><div class="card-body">
                <div class="text-muted small">Average Skill Rating</div>
                <div class="fs-3 fw-bold"><?= number_format((float) $averageRating, 1) ?>/5</div>
                <div class="small text-muted">Across active skills</div>
              </div></div>
            </div>
            <div class="col-md-6 col-xl-3">
              <div class="card h-100"><div class="card-body">
                <div class="text-muted small">Best Match</div>
                <div class="fs-3 fw-bold"><?= (int) ($selectedStudent['best_match'] ?? 0) ?>%</div>
                <div class="small text-muted"><?= (int) ($selectedStudent['analyses_done'] ?? 0) ?> analyses</div>
              </div></div>
            </div>
            <div class="col-md-6 col-xl-3">
              <div class="card h-100"><div class="card-body">
                <div class="text-muted small">Student Status</div>
                <div class="mt-2"><?= skillmap_status_badge((string) $selectedStudent['status']) ?></div>
                <div class="small text-muted mt-2"><?= htmlspecialchars($selectedStudent['email'], ENT_QUOTES, 'UTF-8') ?></div>
              </div></div>
            </div>
          </div>

          <div class="row g-4 mb-4">
            <div class="col-lg-7">
              <div class="card h-100">
                <div class="card-body p-4">
                  <div class="d-flex align-items-center gap-3 mb-3">
                    <?php if (!empty($selectedStudent['profile_icon'])): ?>
                      <img class="profile-icon-preview" src="/fyp_skillmapsystem/<?= htmlspecialchars((string) $selectedStudent['profile_icon'], ENT_QUOTES, 'UTF-8') ?>" alt="">
                    <?php endif; ?>
                    <div>
                      <h2 class="h4 fw-bold mb-1"><?= htmlspecialchars($selectedStudent['name'], ENT_QUOTES, 'UTF-8') ?></h2>
                      <div class="text-muted"><?= htmlspecialchars($selectedStudent['programme'], ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars($selectedStudent['year_level'], ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                  </div>
                  <div style="height:280px"><canvas id="reviewCategoryChart"></canvas></div>
                </div>
              </div>
            </div>
            <div class="col-lg-5">
              <div class="card h-100">
                <div class="card-body p-4">
                  <h2 class="h5 fw-bold mb-3">Rating Distribution</h2>
                  <div style="height:280px"><canvas id="reviewDistributionChart"></canvas></div>
                </div>
              </div>
            </div>
          </div>

          <form method="post" class="card" data-search-scope>
            <div class="card-body p-4">
              <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
                <div>
                  <h2 class="h5 fw-bold mb-1">Skill Ratings</h2>
                  <div class="text-muted small">Adjust ratings after reviewing evidence or student updates.</div>
                </div>
                <input type="hidden" name="student_id" value="<?= (int) $selectedStudent['id'] ?>">
                <button class="btn btn-primary" type="submit" name="save_review"><i class="bi bi-check2 me-1"></i>Save Review</button>
              </div>
              <div class="skillmap-search mb-4">
                <i class="bi bi-search"></i>
                <input class="form-control" type="search" placeholder="Search skills or categories" data-search-input>
              </div>

              <div class="d-grid gap-3">
                <?php foreach ($skills as $category => $group): ?>
                  <div class="border rounded-4 p-3 p-md-4" data-search-item data-search-text="<?= htmlspecialchars($category . ' ' . implode(' ', array_column($group['items'], 'name')) . ' ' . implode(' ', array_column($group['items'], 'description')), ENT_QUOTES, 'UTF-8') ?>">
                    <?php $stats = $categoryStats[$category] ?? ['total' => 0, 'rated' => 0, 'score_sum' => 0]; ?>
                    <?php $categoryPct = (int) round(($stats['score_sum'] / max((int) $stats['total'] * 5, 1)) * 100); ?>
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                      <div class="fw-semibold"><i class="bi <?= htmlspecialchars((string) ($group['icon'] ?? 'bi-stars'), ENT_QUOTES, 'UTF-8') ?> me-1 text-primary"></i><?= htmlspecialchars($category, ENT_QUOTES, 'UTF-8') ?></div>
                      <span class="badge text-bg-light border"><?= (int) $stats['rated'] ?> / <?= (int) $stats['total'] ?> rated · <?= $categoryPct ?>%</span>
                    </div>
                    <div class="d-grid gap-3">
                      <?php foreach ($group['items'] as $skill): ?>
                        <div class="skillmap-review-skill">
                          <div class="flex-grow-1">
                            <div class="fw-semibold"><?= htmlspecialchars($skill['name'], ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="small text-muted"><?= htmlspecialchars($skill['description'], ENT_QUOTES, 'UTF-8') ?></div>
                          </div>
                          <div class="skillmap-review-rating">
                            <?= skillmap_star_rating((int) $skill['score']) ?>
                            <input type="hidden" class="skill-rating-value" name="ratings[<?= (int) $skill['skill_id'] ?>]" value="<?= (int) $skill['score'] ?>">
                          </div>
                        </div>
                      <?php endforeach; ?>
                    </div>
                  </div>
                <?php endforeach; ?>
                <div class="alert alert-light border mb-0 d-none" data-search-empty>No matching skills found.</div>
              </div>
            </div>
          </form>
        <?php endif; ?>
      </div>
    </div>
  </main>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
  <script src="/fyp_skillmapsystem/assets/js/app.js"></script>
  <script>
    if (typeof Chart !== 'undefined') {
      const categoryCanvas = document.getElementById('reviewCategoryChart');
      if (categoryCanvas) {
        new Chart(categoryCanvas, {
          type: 'bar',
          data: {
            labels: <?= json_encode($categoryLabels, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>,
            datasets: [{ label: 'Readiness %', data: <?= json_encode($categoryAverages, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>, backgroundColor: '#2563eb', borderRadius: 8 }]
          },
          options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true, max: 100 } }, plugins: { legend: { display: false } } }
        });
      }

      const distributionCanvas = document.getElementById('reviewDistributionChart');
      if (distributionCanvas) {
        new Chart(distributionCanvas, {
          type: 'doughnut',
          data: {
            labels: <?= json_encode($distributionLabels, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>,
            datasets: [{ data: <?= json_encode($distributionValues, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>, backgroundColor: ['#cbd5e1', '#dc2626', '#f59e0b', '#eab308', '#16a34a', '#2563eb'], borderWidth: 0 }]
          },
          options: { responsive: true, maintainAspectRatio: false, cutout: '68%', plugins: { legend: { position: 'bottom' } } }
        });
      }
    }
  </script>
</body>
</html>
