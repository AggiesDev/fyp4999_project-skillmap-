<?php
// Report download page with preview, export actions, and share controls.

require_once __DIR__ . '/../includes/auth_check.php';
skillmap_require_auth(['student']);
$activePage = 'dashboard';
$data = skillmap_data()['student'];
$report = $data['report'];
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
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
      <div><h1 class="fw-bold mb-1">Download Your Skill Gap Report</h1><div class="text-muted"><?= htmlspecialchars($report['generated'], ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars($data['name'], ENT_QUOTES, 'UTF-8') ?></div></div>
      <div class="text-muted small">Generated on <?= htmlspecialchars($report['generated'], ENT_QUOTES, 'UTF-8') ?></div>
    </div>
    <div class="row g-4">
      <div class="col-lg-8"><div class="card"><div class="card-body p-4"><div class="border rounded-4 overflow-hidden bg-white"><div class="bg-primary text-white p-3"><div class="fw-bold fs-5">Skill Map Skill Gap Report</div><div class="small opacity-75">Universiti Teknologi Malaysia</div></div><div class="p-3 border-bottom d-flex flex-wrap justify-content-between gap-2 small"><span><?= htmlspecialchars($data['name'], ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars($data['programme'], ENT_QUOTES, 'UTF-8') ?></span><span><?= $data['gap_analysis']['role'] ?> · <?= $report['generated'] ?></span></div><div class="p-3"><h2 class="h6 fw-bold">Section 1 - Match Summary</h2><div class="d-flex align-items-center gap-4 mb-4"><?= skillmap_progress_ring($report['match']) ?><div><div class="display-6 fw-bold"><?= $report['match'] ?>%</div><div class="text-muted">Overall match score</div></div></div><h2 class="h6 fw-bold">Section 2 - Skill Assessment</h2><table class="table table-sm"><thead><tr><th>Skill</th><th>Status</th><th>Gap</th></tr></thead><tbody><?php foreach ($data['gap_analysis']['skills'] as $skill): ?><tr><td><?= htmlspecialchars($skill['skill'], ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($skill['status'], ENT_QUOTES, 'UTF-8') ?></td><td><?= $skill['gap'] ?></td></tr><?php endforeach; ?></tbody></table><h2 class="h6 fw-bold mt-4">Section 3 - Top Missing Skills &amp; Resources</h2><ol class="mb-0"><?php foreach ($report['top_missing'] as $item): ?><li class="mb-2"><strong><?= htmlspecialchars($item['skill'], ENT_QUOTES, 'UTF-8') ?></strong> - <?= htmlspecialchars($item['resource'], ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars($item['platform'], ENT_QUOTES, 'UTF-8') ?>)</li><?php endforeach; ?></ol></div></div></div></div></div>
      <div class="col-lg-4"><div class="card"><div class="card-body p-4"><div class="d-flex align-items-center gap-3 mb-3"><div class="skillmap-stats-icon"><i class="bi bi-file-earmark-pdf"></i></div><div><h2 class="h5 fw-bold mb-0">Report Ready</h2><div class="text-muted small"><?= $report['pages'] ?> pages · <?= htmlspecialchars($data['gap_analysis']['role'], ENT_QUOTES, 'UTF-8') ?></div></div></div><div class="small text-muted mb-3">Match score: <?= $report['match'] ?>%</div><div class="d-grid gap-2 mb-3"><button class="btn btn-danger" type="button" onclick="window.print()"><i class="bi bi-download me-1"></i>Download Full PDF</button><a class="btn btn-outline-secondary" href="/fyp_skillmapsystem/users/gap_analysis.php">Download CSV</a><button class="btn btn-outline-primary" data-copy-text="<?= htmlspecialchars($report['share_link'], ENT_QUOTES, 'UTF-8') ?>">Copy Share Link</button></div><div class="accordion mb-3" id="reportInfo"><div class="accordion-item"><h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#reportInfoBody">What's in the report?</button></h2><div id="reportInfoBody" class="accordion-collapse collapse" data-bs-parent="#reportInfo"><div class="accordion-body small text-muted">The report contains your match summary, skill-by-skill breakdown, and prioritized learning actions.</div></div></div></div><a class="link-primary text-decoration-none" href="/fyp_skillmapsystem/users/gap_analysis.php">Back to Results</a></div></div></div>
    </div>
  </main>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="/fyp_skillmapsystem/assets/js/app.js"></script>
</body>
</html>
