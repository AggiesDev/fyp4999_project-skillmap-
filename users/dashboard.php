<?php
// Student dashboard showing summary metrics, trends, and quick actions.
require_once __DIR__. '/../includes/auth_check.php';
skillmap_require_auth(['student']);
$activePage = 'dashboard';
$data = skillmap_data()['student'];
$user = skillmap_current_user();
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Skill Map - Dashboard</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="/fyp_skillmapsystem/assets/css/style.css" rel="stylesheet">
<style>
   .skill-progress-row {
        border-bottom: 1px solid #eee;
        padding: 12px 0;
    }
   .skill-progress-row:last-child {
        border-bottom: none;
    }
   .stats-card {
        border: 1px solid #e0e0e0;
        border-radius: 12px;
    }
</style>
</head>
<body class="bg-light">
<?php require __DIR__. '/includes/navbar.php';?>
<main class="container py-4 py-lg-5">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="fw-bold mb-1">Welcome back, <?= htmlspecialchars($data['name'], ENT_QUOTES, 'UTF-8')?> 👋</h1>
            <div class="text-muted small">
                <?= htmlspecialchars($data['programme'], ENT_QUOTES, 'UTF-8')?> · <?= htmlspecialchars($data['year'], ENT_QUOTES, 'UTF-8')?> · <?= htmlspecialchars($data['institution'], ENT_QUOTES, 'UTF-8')?>
            </div>
        </div>
        <div class="col-md-4 text-md-end mt-3 mt-md-0">
            <a class="btn btn-primary" href="/fyp_skillmapsystem/users/analyse.php">
                <i class="bi bi-plus-lg me-1"></i>New Analysis
            </a>
        </div>
    </div>

    <!-- Stats Summary Row -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card h-100 stats-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div class="text-muted small">Overall Match Score</div>
                        <div class="text-primary"><i class="bi bi-graph-up"></i></div>
                    </div>
                    <div class="d-flex align-items-center">
                        <h3 class="fw-bold mb-0 me-2"><?= $data['match_score']?>%</h3>
                        <?= skillmap_progress_ring($data['match_score'])?>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100 stats-card">
                <div class="card-body">
                    <div class="text-muted small mb-2">Skills Assessed</div>
                    <div class="d-flex justify-content-between mb-2">
                        <strong class="fw-bold"><?= $data['skills_assessed']?> / <?= $data['skills_total']?></strong>
                        <span class="text-muted small"><?= round(($data['skills_assessed'] / $data['skills_total']) * 100)?>%</span>
                    </div>
                    <?= skillmap_percent_bar((int) round(($data['skills_assessed'] / $data['skills_total']) * 100))?>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100 stats-card">
                <div class="card-body">
                    <div class="text-muted small mb-2">Badges Earned</div>
                    <h3 class="fw-bold mb-0"><?= $data['badges_earned']?></h3>
                    <div class="mt-3 d-flex gap-2">
                        <?php for($i=0; $i<3; $i++):?>
                            <i class="bi bi-award text-warning"></i>
                        <?php endfor;?>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100 stats-card">
                <div class="card-body">
                    <div class="text-muted small mb-2">Analyses Done</div>
                    <h3 class="fw-bold mb-0"><?= $data['analyses_done']?></h3>
                    <div class="small text-muted mb-2">Last: <?= htmlspecialchars($data['last_analysis'], ENT_QUOTES, 'UTF-8')?></div>
                    <a href="/fyp_skillmapsystem/users/analyse.php" class="btn btn-sm btn-outline-primary">New Analysis</a>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <!-- Progress Table section matching the image -->
        <div class="col-lg-8">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2 class="h5 fw-bold mb-0">Skills Progress</h2>
                        <span class="badge bg-success text-uppercase"><?= $data['progress']['delta_month']?>% This Month</span>
                    </div>
                    <div class="skill-table">
                        <!-- We can loop through a skill list here if available, 
                             but for now we replicate the layout of the image -->
                        <div class="skill-progress-row">
                            <div class="col-md-4 fw-bold">Web Developer</div>
                            <div class="col-md-4">
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar" style="width: 78%"></div>
                                </div>
                            </div>
                            <div class="col-md-3 text-end text-muted">78%</div>
                        </div>
                        <div class="skill-progress-row">
                            <div class="col-md-4 fw-bold">Data Analyst</div>
                            <div class="col-md-4">
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar" style="width: 85%"></div>
                                </div>
                            </div>
                            <div class="col-md-3 text-end text-muted">85%</div>
                        </div>
                        <div class="skill-progress-row">
                            <div class="col-md-4 fw-bold">Student Club Lead</div>
                            <div class="col-md-4">
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar" style="width: 71%"></div>
                                </div>
                            </div>
                            <div class="col-md-3 text-end text-muted">71%</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Chart section -->
        <div class="col-lg-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2 class="h5 fw-bold mb-0">Trend Analysis</h2>
                    </div>
                    <div style="height: 250px;">
                        <canvas id="progressTrendChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Achievements & AI Insight Row -->
    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body p-4">
                    <h2 class="h5 fw-bold mb-3">Achievements</h2>
                    <div class="row g-3">
                        <?php foreach ($data['achievement_badges'] as $badge):?>
                        <div class="col-md-3">
                            <div class="card border-0 bg-light h-100 p-3">
                                <div class="text-center">
                                    <i class="bi bi-award text-warning fs-3 mb-2"></i>
                                    <div class="fw-bold small"><?= htmlspecialchars($badge['name'], ENT_QUOTES, 'UTF-8')?></div>
                                    <div class="text-muted extra-small"><?= htmlspecialchars($badge['date'], ENT_QUOTES, 'UTF-8')?></div>
                                    <span class="badge rounded-pill mt-1 <?= skillmap_badge_tier_class($badge['tier'])?>"><?= ucfirst($badge['tier'])?></span>
                                </div>
                            </div>
                        </div>
                        <?php endforeach;?>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card h-100 border-0 shadow-sm bg-primary bg-opacity-10">
                <div class="card-body p-4">
                    <div class="d-flex align-items-start gap-3">
                        <i class="bi bi-lightning-charge-fill fs-3 text-primary"></i>
                        <div>
                            <h2 class="h5 fw-bold mb-1">AI Insight</h2>
                            <p class="small mb-0 text-muted"><?= htmlspecialchars($data['ai_insight'], ENT_QUOTES, 'UTF-8')?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom Action Buttons -->
    <div class="row g-3 mt-2">
        <div class="col-md-4"><a class="btn btn-outline-primary w-100 py-3" href="/fyp_skillmapsystem/users/profile.php"><i class="bi bi-person-gear me-2"></i>Update Profile</a></div>
        <div class="col-md-4"><a class="btn btn-outline-primary w-100 py-3" href="/fyp_skillmapsystem/users/roadmap.php"><i class="bi bi-map me-2"></i>View Roadmap</a></div>
        <div class="col-md-4"><a class="btn btn-outline-primary w-100 py-3" href="/fyp_skillmapsystem/users/report.php"><i class="bi bi-download me-2"></i>Download Report</a></div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script src="/fyp_skillmapsystem/assets/js/app.js"></script>
<script src="/fyp_skillmapsystem/assets/js/charts.js"></script>
</body>
</html>