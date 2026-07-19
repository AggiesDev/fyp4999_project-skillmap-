<?php
// Public login and registration entry point for Skill Map.

require_once __DIR__ . '/includes/functions.php';

$demoLogin = '';
$error = '';
$activeTab = $_POST['tab'] ?? 'login';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['register_submit'])) {
        $user = skillmap_register($_POST);
        if ($user) {
            $_SESSION['user'] = $user;
      $destination = $user['role'] === 'admin'
        ? '/fyp_skillmapsystem/admin/analytics.php'
        : (in_array($user['role'], ['lecturer', 'staff'], true) ? '/fyp_skillmapsystem/admin/reviews.php' : '/fyp_skillmapsystem/users/dashboard.php');
      header('Location: ' . $destination);
            exit;
        }
        $error = 'Please complete all registration fields.';
        $activeTab = 'register';
    } elseif (isset($_POST['login_submit'])) {
        $user = skillmap_login(trim((string) ($_POST['email'] ?? '')), (string) ($_POST['password'] ?? ''));
        if ($user) {
            $_SESSION['user'] = $user;
      $destination = $user['role'] === 'admin'
        ? '/fyp_skillmapsystem/admin/analytics.php'
        : (in_array($user['role'], ['lecturer', 'staff'], true) ? '/fyp_skillmapsystem/admin/reviews.php' : '/fyp_skillmapsystem/users/dashboard.php');
      header('Location: ' . $destination);
            exit;
        }
        $error = 'Invalid email or password.';
    }
}

$demoLogin = 'Demo: enter admin@utm.my to access the admin view';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Skill Map</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="/fyp_skillmapsystem/assets/css/style.css" rel="stylesheet">
</head>
<body>
  <div class="container-fluid skillmap-auth-shell">
    <div class="row min-vh-100 g-0">
      <div class="col-lg-7 skillmap-auth-left d-flex align-items-center p-4 p-md-5">
        <div class="w-100 position-relative">
          <div class="d-flex align-items-center gap-3 mb-4">
            <div class="skillmap-brand-mark"><i class="bi bi-map-fill"></i></div>
            <div>
              <h1 class="h2 fw-bold mb-0">Skill Map</h1>
              <div class="text-muted">Information Systems FYP4999 · FDSIT</div>
            </div>
          </div>
          <div class="row align-items-center g-4">
            <div class="col-xl-6">
              <h2 class="display-6 fw-bold mb-3">Discover your gaps. Build your future.</h2>
              <p class="lead text-muted-strong mb-4">Personalised skill analysis for university students, lecturers, and administrators.</p>
              <ul class="list-unstyled d-grid gap-3">
                <li class="d-flex gap-3"><i class="bi bi-check-circle-fill text-primary"></i><span>Personalised AI skill gap reports</span></li>
                <li class="d-flex gap-3"><i class="bi bi-check-circle-fill text-primary"></i><span>Curated learning roadmaps</span></li>
                <li class="d-flex gap-3"><i class="bi bi-check-circle-fill text-primary"></i><span>Leadership &amp; career benchmarks</span></li>
              </ul>
            </div>
            <div class="col-xl-6">
              <div class="position-relative d-flex justify-content-center py-4">
                <div class="skillmap-floating-card" style="top:0;left:5%;"><i class="bi bi-award"></i></div>
                <div class="skillmap-floating-card" style="top:8%;right:4%;"><i class="bi bi-bar-chart"></i></div>
                <div class="skillmap-floating-card" style="bottom:12%;left:0;"><i class="bi bi-key"></i></div>
                <div class="skillmap-floating-card" style="bottom:8%;right:0;"><i class="bi bi-bullseye"></i></div>
                <div class="skillmap-laptop">
                  <div class="skillmap-laptop-screen">
                    <div class="d-flex align-items-center gap-2 mb-3">
                      <span class="badge text-bg-primary">Skill Map</span>
                      <span class="badge text-bg-light border">FYP4999</span>
                    </div>
                    <div class="row g-3">
                      <div class="col-7">
                        <div class="card border-0 shadow-sm rounded-4 p-3 h-100">
                          <div class="small text-muted">Gap overview</div>
                          <div class="fw-bold fs-4">82% match</div>
                          <div class="progress mt-3"><div class="progress-bar bg-primary" style="width:82%"></div></div>
                        </div>
                      </div>
                      <div class="col-5">
                        <div class="card border-0 shadow-sm rounded-4 p-3 h-100 d-flex align-items-center justify-content-center">
                          <i class="bi bi-code-slash fs-1 text-primary"></i>
                        </div>
                      </div>
                    </div>
                    <div class="mt-3 small text-muted">Track your readiness, close your skill gaps, and export a polished report.</div>
                  </div>
                  <div class="skillmap-laptop-base"></div>
                </div>
              </div>
            </div>
          </div>
          <div class="mt-4 small text-muted">FYP4999 · FDSIT · Universiti Teknologi Malaysia</div>
        </div>
      </div>
      <div class="col-lg-5 bg-white d-flex align-items-center justify-content-center p-4 p-md-5">
        <div class="w-100" style="max-width: 460px;">
          <div class="card rounded-5 shadow-lg border-0">
            <div class="card-body p-4 p-md-5">
              <ul class="nav nav-pills nav-fill mb-4" id="authTabs" role="tablist">
                <li class="nav-item" role="presentation"><button class="nav-link <?= $activeTab === 'login' ? 'active' : '' ?>" data-bs-toggle="pill" data-bs-target="#loginPane" type="button">Login</button></li>
                <li class="nav-item" role="presentation"><button class="nav-link <?= $activeTab === 'register' ? 'active' : '' ?>" data-bs-toggle="pill" data-bs-target="#registerPane" type="button">Create Account</button></li>
              </ul>
              <?php if ($error !== ''): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
              <?php endif; ?>
              <div class="tab-content">
                <div class="tab-pane fade <?= $activeTab === 'login' ? 'show active' : '' ?>" id="loginPane">
                  <form method="post" class="d-grid gap-3">
                    <input type="hidden" name="tab" value="login">
                    <div>
                      <label class="form-label">Email Address or Username</label>
                      <input type="text" name="email" class="form-control form-control-lg" placeholder="student@utm.my or demostudent" required>
                    </div>
                    <div>
                      <label class="form-label">Password</label>
                      <div class="input-group input-group-lg">
                        <input type="password" name="password" id="loginPassword" class="form-control" required>
                        <button class="btn btn-outline-secondary" type="button" data-toggle-password="loginPassword"><i class="bi bi-eye-slash"></i></button>
                      </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                      <a href="mailto:support@utm.my?subject=Skill%20Map%20Password%20Reset" class="small text-decoration-none">Forgot password?</a>
                      <a href="#registerPane" class="small text-decoration-none" data-bs-toggle="pill">Register</a>
                    </div>
                    <button type="submit" name="login_submit" class="btn btn-primary btn-lg w-100">Login to Skill Map</button>
                    <div class="alert alert-info mb-0">Demo accounts: admin@gmail.com / admin@123, lecturer@gmail.com / lecturer@123, staff@gmail.com / lecturer@123</div>
                  </form>
                </div>
                <div class="tab-pane fade <?= $activeTab === 'register' ? 'show active' : '' ?>" id="registerPane">
                  <form method="post" class="d-grid gap-3">
                    <input type="hidden" name="tab" value="register">
                    <div>
                      <label class="form-label">Full Name</label>
                      <input type="text" name="name" class="form-control form-control-lg" placeholder="Chong Pei" required>
                    </div>
                    <div>
                      <label class="form-label">Email Address</label>
                      <input type="email" name="email" class="form-control form-control-lg" placeholder="student@utm.my" required>
                    </div>
                    <div>
                      <label class="form-label">Username</label>
                      <input type="text" name="username" class="form-control" placeholder="demostudent">
                    </div>
                    <div class="row g-3">
                      <div class="col-md-6">
                        <label class="form-label">Programme</label>
                        <input type="text" name="programme" class="form-control" value="Information Systems" required>
                      </div>
                      <div class="col-md-6">
                        <label class="form-label">Year</label>
                        <input type="text" name="year" class="form-control" value="Year 4" required>
                      </div>
                    </div>
                    <div>
                      <label class="form-label">Role</label>
                      <select name="role" class="form-select">
                        <option value="student">Student</option>
                        <option value="lecturer">Lecturer</option>
                        <option value="staff">Staff</option>
                      </select>
                    </div>
                    <div>
                      <label class="form-label">Password</label>
                      <div class="input-group input-group-lg">
                        <input type="password" name="password" id="registerPassword" class="form-control" required>
                        <button class="btn btn-outline-secondary" type="button" data-toggle-password="registerPassword"><i class="bi bi-eye-slash"></i></button>
                      </div>
                    </div>
                    <button type="submit" name="register_submit" class="btn btn-primary btn-lg w-100">Create Account</button>
                  </form>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="/fyp_skillmapsystem/assets/js/app.js"></script>
</body>
</html>
