<?php
// Lecturer and admin page for reviewing and editing student skill ratings.

require_once __DIR__ . '/../includes/auth_check.php';
skillmap_require_permission('review_student_skills');
$activePage = 'reviews';
$message = '';

$students = skillmap_fetch_all('SELECT id, name, email, programme, year_level, avatar_initials, status FROM users WHERE role = "student" ORDER BY created_at DESC');
$selectedStudentId = (int) ($_POST['student_id'] ?? $_GET['student_id'] ?? ($students[0]['id'] ?? 0));

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_review'])) {
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
if ($selectedStudentId > 0) {
    $skillRows = skillmap_fetch_all(
        'SELECT s.id AS skill_id, c.name AS category, s.name, s.description, COALESCE(r.rating, 0) AS score
         FROM skills s
         INNER JOIN skill_categories c ON c.id = s.category_id
         LEFT JOIN user_skill_ratings r ON r.skill_id = s.id AND r.user_id = ?
         ORDER BY c.id, s.name',
        'i',
        [$selectedStudentId]
    );

    foreach ($skillRows as $skillRow) {
        $skills[$skillRow['category']][] = $skillRow;
    }
}
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
  <main class="container py-4 py-lg-5">
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
      <div>
        <h1 class="fw-bold mb-1">Student Skill Reviews</h1>
        <div class="text-muted">Lecturer and admin review panel for student skill ratings</div>
      </div>
      <a class="btn btn-outline-secondary" href="/fyp_skillmapsystem/admin/permissions.php">Manage Access</a>
    </div>

    <?php if ($message !== ''): ?>
      <div class="alert alert-info"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <div class="row g-4">
      <div class="col-lg-4">
        <div class="card h-100">
          <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <h2 class="h5 fw-bold mb-0">Students</h2>
              <span class="badge text-bg-light border"><?= count($students) ?> total</span>
            </div>
            <div class="d-grid gap-2">
              <?php foreach ($students as $student): ?>
                <a class="card border-0 shadow-sm text-decoration-none <?= (int) $student['id'] === $selectedStudentId ? 'bg-primary text-white' : 'bg-light text-dark' ?>" href="/fyp_skillmapsystem/admin/reviews.php?student_id=<?= (int) $student['id'] ?>">
                  <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-3">
                      <div class="avatar-circle <?= (int) $student['id'] === $selectedStudentId ? 'bg-white text-primary' : 'bg-primary' ?>"><?= htmlspecialchars(strtoupper(substr($student['avatar_initials'] ?: $student['name'], 0, 2)), ENT_QUOTES, 'UTF-8') ?></div>
                      <div class="flex-grow-1">
                        <div class="fw-semibold"><?= htmlspecialchars($student['name'], ENT_QUOTES, 'UTF-8') ?></div>
                        <div class="small <?= (int) $student['id'] === $selectedStudentId ? 'text-white-50' : 'text-muted' ?>"><?= htmlspecialchars($student['programme'], ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars($student['year_level'], ENT_QUOTES, 'UTF-8') ?></div>
                      </div>
                      <span class="badge <?= $student['status'] === 'Active' ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= htmlspecialchars($student['status'], ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                  </div>
                </a>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      </div>

      <div class="col-lg-8">
        <form method="post" class="card shadow-sm border-0">
          <div class="card-body p-4">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
              <div>
                <h2 class="h4 fw-bold mb-1"><?= htmlspecialchars($selectedStudent['name'] ?? 'Select a student', ENT_QUOTES, 'UTF-8') ?></h2>
                <div class="text-muted"><?= htmlspecialchars($selectedStudent['programme'] ?? '-', ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars($selectedStudent['year_level'] ?? '-', ENT_QUOTES, 'UTF-8') ?></div>
              </div>
              <?php if ($selectedStudent): ?>
                <div class="text-end">
                  <?= skillmap_status_badge($selectedStudent['status']) ?>
                  <input type="hidden" name="student_id" value="<?= (int) $selectedStudent['id'] ?>">
                </div>
              <?php endif; ?>
            </div>

            <?php if (!$selectedStudent): ?>
              <div class="alert alert-warning mb-0">No student records were found.</div>
            <?php else: ?>
              <div class="d-grid gap-3">
                <?php foreach ($skills as $category => $categorySkills): ?>
                  <div class="border rounded-4 p-3 p-md-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                      <div class="fw-semibold"><?= htmlspecialchars($category, ENT_QUOTES, 'UTF-8') ?></div>
                      <span class="badge text-bg-light border"><?= count($categorySkills) ?> skills</span>
                    </div>
                    <div class="d-grid gap-3">
                      <?php foreach ($categorySkills as $skill): ?>
                        <div class="border rounded-4 p-3">
                          <div class="d-flex justify-content-between align-items-start gap-3">
                            <div>
                              <div class="fw-semibold"><?= htmlspecialchars($skill['name'], ENT_QUOTES, 'UTF-8') ?></div>
                              <div class="small text-muted"><?= htmlspecialchars($skill['description'], ENT_QUOTES, 'UTF-8') ?></div>
                            </div>
                            <?= skillmap_rating_badge((int) $skill['score']) ?>
                          </div>
                          <div class="d-flex align-items-center gap-3 mt-3">
                            <?= skillmap_star_rating((int) $skill['score']) ?>
                            <input type="hidden" class="skill-rating-value" name="ratings[<?= (int) $skill['skill_id'] ?>]" value="<?= (int) $skill['score'] ?>">
                          </div>
                        </div>
                      <?php endforeach; ?>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
          <div class="card-footer bg-white border-0 p-4 pt-0 d-flex justify-content-end gap-2">
            <a class="btn btn-outline-secondary" href="/fyp_skillmapsystem/admin/users.php">Back to Users</a>
            <button class="btn btn-primary" type="submit" name="save_review">Save Review</button>
          </div>
        </form>
      </div>
    </div>
  </main>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="/fyp_skillmapsystem/assets/js/app.js"></script>
</body>
</html>
