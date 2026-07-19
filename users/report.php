<?php
// Printable and CSV-exportable skill gap report.

require_once __DIR__ . '/../includes/auth_check.php';
skillmap_require_auth(['student']);

$activePage = 'report';
$user = skillmap_current_user();
$userId = (int) ($user['id'] ?? 0);

$analysisStmt = $pdo->prepare(
    'SELECT a.id, a.match_score, a.ai_summary, a.created_at, cr.name AS role_name, cr.description AS role_description
     FROM analyses a
     INNER JOIN career_roles cr ON cr.id = a.target_role_id
     WHERE a.user_id = :user_id
     ORDER BY a.created_at DESC
     LIMIT 1'
);
$analysisStmt->execute(['user_id' => $userId]);
$analysis = $analysisStmt->fetch() ?: null;

$results = [];
$resources = [];
if ($analysis) {
    $resultStmt = $pdo->prepare(
        'SELECT s.name AS skill_name, c.name AS category, ar.status, ar.your_rating, ar.required_rating, ar.gap_value
         FROM analysis_results ar
         INNER JOIN skills s ON s.id = ar.skill_id
         INNER JOIN skill_categories c ON c.id = s.category_id
         WHERE ar.analysis_id = :analysis_id
         ORDER BY FIELD(ar.status, "Missing", "Partial", "Have"), ar.gap_value DESC, s.name'
    );
    $resultStmt->execute(['analysis_id' => (int) $analysis['id']]);
    $results = $resultStmt->fetchAll();

    $resourceStmt = $pdo->prepare(
        'SELECT s.name AS skill_name, lr.title, lr.platform, lr.url, lr.duration_hours, lr.is_free
         FROM analysis_results ar
         INNER JOIN skills s ON s.id = ar.skill_id
         LEFT JOIN learning_resources lr ON lr.skill_id = ar.skill_id
         WHERE ar.analysis_id = :analysis_id AND ar.status IN ("Missing", "Partial")
         ORDER BY FIELD(ar.status, "Missing", "Partial"), ar.gap_value DESC, s.name
         LIMIT 8'
    );
    $resourceStmt->execute(['analysis_id' => (int) $analysis['id']]);
    $resources = $resourceStmt->fetchAll();
}

if ($analysis && ($_GET['format'] ?? '') === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="skill-map-report.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Student', $user['name'] ?? 'Student']);
    fputcsv($out, ['Target Role', $analysis['role_name']]);
    fputcsv($out, ['Match Score', round((float) $analysis['match_score']) . '%']);
    fputcsv($out, []);
    fputcsv($out, ['Skill', 'Category', 'Status', 'Your Rating', 'Required Rating', 'Gap']);
    foreach ($results as $row) {
        fputcsv($out, [$row['skill_name'], $row['category'], $row['status'], $row['your_rating'], $row['required_rating'], $row['gap_value']]);
    }
    fclose($out);
    exit;
}

$summary = ['Have' => 0, 'Partial' => 0, 'Missing' => 0];
foreach ($results as $row) {
    $summary[(string) $row['status']]++;
}
$generatedDate = date('j M Y');
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Skill Map - Report</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="/fyp_skillmapsystem/assets/css/style.css" rel="stylesheet">
</head>
<body>
  <?php require __DIR__ . '/includes/navbar.php'; ?>
  <main class="container py-4 py-lg-5">
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4 no-print">
      <div>
        <h1 class="fw-bold mb-1">Skill Gap Report</h1>
        <div class="text-muted">Printable summary generated on <?= htmlspecialchars($generatedDate, ENT_QUOTES, 'UTF-8') ?></div>
      </div>
      <div class="d-flex flex-wrap gap-2">
        <?php if ($analysis): ?>
          <a class="btn btn-outline-secondary" href="/fyp_skillmapsystem/users/report.php?format=csv"><i class="bi bi-filetype-csv me-1"></i>Download CSV</a>
          <button class="btn btn-primary" type="button" onclick="window.print()"><i class="bi bi-printer me-1"></i>Print Report</button>
        <?php endif; ?>
      </div>
    </div>

    <?php if (!$analysis): ?>
      <div class="alert alert-light border">
        No report is available yet. Run a gap analysis first, then return here to print or export your results.
        <a class="alert-link" href="/fyp_skillmapsystem/users/analyse.php">Start analysis</a>.
      </div>
    <?php else: ?>
      <div class="report-sheet bg-white border rounded-4 overflow-hidden">
        <div class="bg-primary text-white p-4">
          <div class="d-flex flex-wrap justify-content-between gap-3">
            <div>
              <div class="h3 fw-bold mb-1">Skill Map Report</div>
              <div class="opacity-75">AI-assisted career skill gap summary</div>
            </div>
            <div class="text-md-end">
              <div class="fw-semibold"><?= htmlspecialchars((string) ($user['name'] ?? 'Student'), ENT_QUOTES, 'UTF-8') ?></div>
              <div class="opacity-75"><?= htmlspecialchars((string) ($user['programme'] ?? '-'), ENT_QUOTES, 'UTF-8') ?> - <?= htmlspecialchars((string) ($user['year'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></div>
            </div>
          </div>
        </div>

        <div class="p-4 p-lg-5">
          <div class="row g-4 mb-4">
            <div class="col-md-4 text-center">
              <?= skillmap_progress_ring((int) round((float) $analysis['match_score']), (string) $analysis['role_name']) ?>
            </div>
            <div class="col-md-8">
              <h2 class="h5 fw-bold">Target Role</h2>
              <p class="mb-2"><?= htmlspecialchars((string) $analysis['role_name'], ENT_QUOTES, 'UTF-8') ?></p>
              <h2 class="h5 fw-bold">Recommendation</h2>
              <p class="text-muted mb-0"><?= htmlspecialchars((string) $analysis['ai_summary'], ENT_QUOTES, 'UTF-8') ?></p>
            </div>
          </div>

          <div class="row g-3 mb-4">
            <div class="col-md-4"><div class="border rounded-4 p-3"><div class="text-success small fw-semibold">Have</div><div class="display-6 fw-bold"><?= $summary['Have'] ?></div></div></div>
            <div class="col-md-4"><div class="border rounded-4 p-3"><div class="text-warning small fw-semibold">Partial</div><div class="display-6 fw-bold"><?= $summary['Partial'] ?></div></div></div>
            <div class="col-md-4"><div class="border rounded-4 p-3"><div class="text-danger small fw-semibold">Missing</div><div class="display-6 fw-bold"><?= $summary['Missing'] ?></div></div></div>
          </div>

          <h2 class="h5 fw-bold mb-3">Skill Breakdown</h2>
          <div class="table-responsive mb-4">
            <table class="table align-middle">
              <thead><tr><th>Skill</th><th>Category</th><th>Status</th><th>Your Rating</th><th>Required</th><th>Gap</th></tr></thead>
              <tbody>
                <?php foreach ($results as $row): ?>
                  <tr>
                    <td class="fw-semibold"><?= htmlspecialchars((string) $row['skill_name'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string) $row['category'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><span class="badge <?= $row['status'] === 'Have' ? 'text-bg-success' : ($row['status'] === 'Partial' ? 'text-bg-warning' : 'text-bg-danger') ?>"><?= htmlspecialchars((string) $row['status'], ENT_QUOTES, 'UTF-8') ?></span></td>
                    <td><?= (int) $row['your_rating'] ?>/5</td>
                    <td><?= (int) $row['required_rating'] ?>/5</td>
                    <td><?= (int) $row['gap_value'] ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>

          <h2 class="h5 fw-bold mb-3">Recommended Learning Resources</h2>
          <?php if ($resources === []): ?>
            <div class="text-muted small">No missing or partial skills require resources for this report.</div>
          <?php else: ?>
            <div class="row g-3">
              <?php foreach ($resources as $resource): ?>
                <div class="col-md-6">
                  <div class="border rounded-4 p-3 h-100">
                    <div class="fw-semibold"><?= htmlspecialchars((string) $resource['skill_name'], ENT_QUOTES, 'UTF-8') ?></div>
                    <div class="small text-muted"><?= $resource['title'] ? htmlspecialchars((string) $resource['title'], ENT_QUOTES, 'UTF-8') : 'Resource pending' ?></div>
                    <div class="d-flex flex-wrap gap-2 mt-2">
                      <?php if ($resource['platform']): ?><span class="badge text-bg-light border"><?= htmlspecialchars((string) $resource['platform'], ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
                      <?php if ($resource['duration_hours'] !== null): ?><span class="badge text-bg-light border"><?= htmlspecialchars((string) $resource['duration_hours'], ENT_QUOTES, 'UTF-8') ?> hours</span><?php endif; ?>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>
  </main>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="/fyp_skillmapsystem/assets/js/app.js"></script>
</body>
</html>
