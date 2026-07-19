<?php
// Skill profile editor with category tabs, star ratings, and completion tracking.

require_once __DIR__ . '/../includes/auth_check.php';
skillmap_require_auth(['student']);
$activePage = 'profile';
$data = skillmap_data()['student'];
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_profile'])) {
  $user = skillmap_current_user();
  $ratings = is_array($_POST['ratings'] ?? null) ? $_POST['ratings'] : [];
  if ($user && skillmap_save_profile((int) $user['id'], $ratings)) {
    $message = 'Profile saved successfully.';
    $data = skillmap_data()['student'];
  } else {
    $message = 'Unable to save profile changes.';
  }
}

$activeCategory = array_key_first($data['profile_skills']);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Skill Map - My Profile</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="/fyp_skillmapsystem/assets/css/style.css" rel="stylesheet">
</head>
<body>
  <?php require __DIR__ . '/includes/navbar.php'; ?>
  <main class="container py-4 py-lg-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div><h1 class="fw-bold mb-0">My Skill Profile</h1><div class="text-muted">Review and update your self-assessment ratings</div></div>
      <button form="profileForm" type="submit" name="save_profile" class="btn btn-primary"><i class="bi bi-check2 me-1"></i>Save Profile</button>
    </div>
    <?php if ($message !== ''): ?>
      <div class="alert alert-info"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
    <div class="row g-4">
      <div class="col-lg-8">
        <form id="profileForm" method="post"><div class="card"><div class="card-body p-4">
          <ul class="nav nav-pills flex-wrap gap-2 mb-4">
            <?php foreach (array_keys($data['profile_skills']) as $category): ?>
              <li class="nav-item"><button type="button" class="nav-link <?= $category === $activeCategory ? 'active' : 'bg-light text-dark' ?>" data-skillmap-tab="<?= htmlspecialchars($category, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($category, ENT_QUOTES, 'UTF-8') ?></button></li>
            <?php endforeach; ?>
          </ul>
          <?php foreach ($data['profile_skills'] as $category => $skills): ?>
            <div data-skillmap-tab-panel="<?= htmlspecialchars($category, ENT_QUOTES, 'UTF-8') ?>" class="<?= $category === $activeCategory ? '' : 'd-none' ?>">
              <div class="d-grid gap-3">
                <?php foreach ($skills as $skill): ?>
                  <div class="border rounded-4 p-3 p-md-4">
                    <div class="d-flex justify-content-between align-items-start gap-3">
                      <div>
                        <div class="fw-bold"><?= htmlspecialchars($skill['name'], ENT_QUOTES, 'UTF-8') ?></div>
                        <div class="text-muted small"><?= htmlspecialchars($skill['description'], ENT_QUOTES, 'UTF-8') ?></div>
                      </div>
                      <?= skillmap_rating_badge($skill['score']) ?>
                    </div>
                    <div class="d-flex align-items-center gap-3 mt-3">
                      <?= skillmap_star_rating($skill['score']) ?>
                      <input type="hidden" class="skill-rating-value" name="ratings[<?= (int) $skill['id'] ?>]" value="<?= (int) $skill['score'] ?>">
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div></div></form>
      </div>
      <div class="col-lg-4">
        <div class="card mb-4"><div class="card-body p-4 text-center"><h2 class="h5 fw-bold mb-3">Profile Completion</h2><?= skillmap_progress_ring($data['profile_completion']) ?><ul class="list-group list-group-flush text-start mt-4"><?php foreach ($data['profile_categories'] as $category): ?><li class="list-group-item d-flex justify-content-between align-items-center px-0"><span><?= htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8') ?></span><span class="badge rounded-pill <?= $category['done'] ? 'text-bg-success' : 'text-bg-light border text-muted' ?>"><?= $category['done'] ? '<i class="bi bi-check2"></i>' : '<i class="bi bi-circle"></i>' ?></span></li><?php endforeach; ?></ul></div></div>
        <div class="alert skillmap-yellow-banner rounded-4 border-0 mb-0"><div class="d-flex gap-3"><i class="bi bi-lightbulb-fill text-warning fs-4"></i><div><strong>Tip</strong><div class="small">Rate all skills honestly first. Your roadmap becomes more accurate when the profile is complete.</div></div></div></div>
      </div>
    </div>
  </main>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
  <script src="/fyp_skillmapsystem/assets/js/app.js"></script>
  <script src="/fyp_skillmapsystem/assets/js/charts.js"></script>
</body>
</html>
