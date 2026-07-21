<?php
// Public login and registration entry point for Skill Map.

require_once __DIR__ . '/includes/functions.php';

$error = '';
$message = '';
$activeTab = $_POST['tab'] ?? 'login';
$registrationOptions = skillmap_registration_options();
$currentYear = date('Y');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (isset($_POST['register_submit'])) {
        $user = skillmap_register($_POST);
        if ($user) {
            if (($user['status'] ?? 'Active') === 'Inactive') {
                $message = 'Your account was created and is waiting for admin approval.';
                $activeTab = 'login';
            } else {
                $_SESSION['user'] = $user;
                $destination = skillmap_default_destination($user);
                header('Location: ' . $destination);
                exit;
            }
        }
        if ($message === '') {
            $error = (string) ($_SESSION['skillmap_registration_error'] ?? 'Please complete all registration fields, or use a unique email and username.');
            unset($_SESSION['skillmap_registration_error']);
            $activeTab = 'register';
        }
    } elseif (isset($_POST['login_submit'])) {
        $user = skillmap_login(trim((string) ($_POST['email'] ?? '')), (string) ($_POST['password'] ?? ''));
        if ($user) {
            $_SESSION['user'] = $user;
            $destination = skillmap_default_destination($user);
            header('Location: ' . $destination);
            exit;
        }
        $error = 'Invalid email or password.';
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Skill Map</title>
  <link rel="icon" type="image/png" href="/fyp_skillmapsystem/SkillMapLogoPackage/favicon/favicon-32x32.png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="/fyp_skillmapsystem/assets/css/style.css" rel="stylesheet">
</head>
<body class="skillmap-auth-page">
  <main class="skillmap-auth-shell">
    <section class="skillmap-auth-brand-panel">
      <div class="skillmap-auth-topbar">
        <img class="skillmap-login-logo" src="/fyp_skillmapsystem/SkillMapLogoPackage/logo-full/logo-transparent-256w.png" alt="Skill Map" style="max-width: 200px;">
        <!-- <span class="skillmap-auth-institution">Universiti Teknologi Malaysia</span> -->
      </div>

      <div class="skillmap-auth-hero">
        <span class="skillmap-auth-kicker">FYP4999 · FDSIT . INTI International University</span>
        <h1>Map your skills, close the gaps, and move with a clearer plan.</h1>
        <p>Skill Map helps students compare their current skills with target roles, build a learning roadmap, and share progress with lecturers or administrators.</p>
      </div>

      <!-- <div class="skillmap-auth-showcase">
        <div class="skillmap-showcase-main">
          <div class="d-flex justify-content-between align-items-start gap-3 mb-4">
            <div>
              <div class="small text-muted">Readiness snapshot</div>
              <div class="display-6 fw-bold">82%</div>
            </div>
            <span class="badge text-bg-primary rounded-pill">AI Assisted</span>
          </div>
          <div class="d-grid gap-3">
            <div>
              <div class="d-flex justify-content-between small mb-1"><span>Skill Assessment</span><span>Complete</span></div>
              <div class="progress"><div class="progress-bar bg-success" style="width: 92%"></div></div>
            </div>
            <div>
              <div class="d-flex justify-content-between small mb-1"><span>Target Role Match</span><span>82%</span></div>
              <div class="progress"><div class="progress-bar" style="width: 82%"></div></div>
            </div>
            <div>
              <div class="d-flex justify-content-between small mb-1"><span>Roadmap Progress</span><span>64%</span></div>
              <div class="progress"><div class="progress-bar bg-warning" style="width: 64%"></div></div>
            </div>
          </div>
        </div>
        <div class="skillmap-showcase-strip">
          <div><i class="bi bi-stars"></i><span>Assess</span></div>
          <div><i class="bi bi-bullseye"></i><span>Analyse</span></div>
          <div><i class="bi bi-map"></i><span>Improve</span></div>
        </div>
      </div> -->

      <div class="skillmap-auth-footer">Develop by Aggies · &copy; <?= htmlspecialchars($currentYear, ENT_QUOTES, 'UTF-8') ?> Skill Map License. All rights reserved.</div>
    </section>

    <section class="skillmap-auth-form-panel">
      <div class="skillmap-auth-card">
        <div class="text-center mb-4">
          <img class="skillmap-auth-card-logo" src="/fyp_skillmapsystem/SkillMapLogoPackage/app-icons/icon-128x128.png" alt="">
          <h2 class="h4 fw-bold mb-1">Welcome</h2>
          <div class="text-muted small">Login or create your Skill Map account</div>
        </div>

        <ul class="nav nav-pills nav-fill skillmap-auth-tabs mb-4" id="authTabs" role="tablist">
          <li class="nav-item" role="presentation"><button class="nav-link <?= $activeTab === 'login' ? 'active' : '' ?>" data-bs-toggle="pill" data-bs-target="#loginPane" type="button">Login</button></li>
          <li class="nav-item" role="presentation"><button class="nav-link <?= $activeTab === 'register' ? 'active' : '' ?>" data-bs-toggle="pill" data-bs-target="#registerPane" type="button">Create Account</button></li>
        </ul>

        <?php if ($error !== ''): ?>
          <div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        <?php if ($message !== ''): ?>
          <div class="alert alert-info"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <div class="tab-content">
          <div class="tab-pane fade <?= $activeTab === 'login' ? 'show active' : '' ?>" id="loginPane">
            <form method="post" class="d-grid gap-3">
              <input type="hidden" name="tab" value="login">
              <div>
                <label class="form-label">Email Address or Username</label>
                <input type="text" name="email" class="form-control form-control-lg" placeholder="student@gmail.com or demostudent" required>
              </div>
              <div>
                <label class="form-label">Password</label>
                <div class="input-group input-group-lg">
                  <input type="password" name="password" id="loginPassword" class="form-control" required>
                  <button class="btn btn-outline-secondary" type="button" data-toggle-password="loginPassword"><i class="bi bi-eye-slash"></i></button>
                </div>
              </div>
              <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                <a href="mailto:support@utm.my?subject=Skill%20Map%20Password%20Reset" class="small text-decoration-none">Forgot password?</a>
                <a href="#registerPane" class="small text-decoration-none" data-bs-toggle="pill">Create an account</a>
              </div>
              <button type="submit" name="login_submit" class="btn btn-primary btn-lg w-100">Login to Skill Map</button>
              <div class="skillmap-demo-box">
                <div class="fw-semibold mb-1">Demo Accounts</div>
                <div>admin@gmail.com / admin@123</div>
                <div>lecturer@gmail.com / lecturer@123</div>
                <div>student@gmail.com / student@123</div>
                <div>demostaff@gmail.com / staff@123</div>
              </div>
            </form>
          </div>

          <div class="tab-pane fade <?= $activeTab === 'register' ? 'show active' : '' ?>" id="registerPane">
            <form method="post" class="skillmap-register-form">
              <input type="hidden" name="tab" value="register">
              <div class="skillmap-form-grid">
                <div>
                  <label class="form-label">Full Name</label>
                  <input type="text" name="name" class="form-control" placeholder="Full name" required>
                </div>
                <div>
                  <label class="form-label">Email Address</label>
                  <input type="email" name="email" class="form-control" placeholder="student@gmail.com" required>
                </div>
                <div>
                  <label class="form-label">Username</label>
                  <input type="text" name="username" class="form-control" placeholder="Username">
                </div>
                <div>
                  <label class="form-label">Gender</label>
                  <select name="gender" class="form-select" data-profile-gender>
                    <option value="male">Male</option>
                    <option value="female">Female</option>
                  </select>
                </div>
              </div>

              <div class="skillmap-icon-compact">
                <img class="profile-icon-preview" src="/fyp_skillmapsystem/<?= htmlspecialchars(skillmap_default_profile_icon('male', 'student'), ENT_QUOTES, 'UTF-8') ?>" alt="">
                <div class="flex-grow-1">
                  <div class="fw-semibold">Profile Icon</div>
                  <div class="small text-muted">Shown in your navbar and profile.</div>
                </div>
                <button class="btn btn-sm btn-outline-primary" type="button" data-toggle-panel="registerIconPanel">
                  <i class="bi bi-images me-1"></i>Change
                </button>
              </div>
              <div id="registerIconPanel" class="d-none">
                <?php foreach (skillmap_profile_icon_options() as $group => $icons): ?>
                  <div class="small fw-semibold text-muted text-capitalize mt-3 mb-2"><?= htmlspecialchars($group, ENT_QUOTES, 'UTF-8') ?></div>
                  <div class="profile-icon-grid">
                    <?php foreach ($icons as $icon): ?>
                      <label class="profile-icon-choice">
                        <input type="radio" name="profile_icon" value="<?= htmlspecialchars($icon, ENT_QUOTES, 'UTF-8') ?>" <?= $icon === skillmap_default_profile_icon('male', 'student') ? 'checked' : '' ?>>
                        <img src="/fyp_skillmapsystem/<?= htmlspecialchars($icon, ENT_QUOTES, 'UTF-8') ?>" alt="">
                      </label>
                    <?php endforeach; ?>
                  </div>
                <?php endforeach; ?>
              </div>

              <div class="skillmap-form-grid">
                <div>
                  <label class="form-label">Programme</label>
                  <select name="programme" class="form-select" data-other-select="programmeOtherWrap" data-student-field required>
                    <?php foreach ($registrationOptions['programmes'] as $programme): ?>
                      <option value="<?= htmlspecialchars($programme, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($programme, ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                    <option value="__other">Other</option>
                  </select>
                </div>
                <div>
                  <label class="form-label">Year</label>
                  <select name="year" class="form-select" data-student-field required>
                    <?php foreach ($registrationOptions['years'] as $year): ?>
                      <option value="<?= htmlspecialchars($year, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($year, ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>
              <div id="programmeOtherWrap" class="d-none">
                <label class="form-label">Programme Name</label>
                <input type="text" name="programme_other" class="form-control" placeholder="Write your programme name">
                <div class="form-text">New programmes require admin approval before the account can be used.</div>
              </div>
              <div id="departmentWrap" class="d-none">
                <label class="form-label">Department</label>
                <input type="text" name="department" class="form-control" placeholder="Academic department or office">
              </div>

              <div class="skillmap-form-grid">
                <div>
                  <label class="form-label">Role</label>
                  <select name="role" class="form-select" data-other-select="roleOtherWrap" data-register-role required>
                    <?php foreach ($registrationOptions['roles'] as $role): ?>
                      <option value="<?= htmlspecialchars($role, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(ucfirst($role), ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                    <option value="__other">Other</option>
                  </select>
                </div>
                <div>
                  <label class="form-label">Password</label>
                  <div class="input-group">
                    <input type="password" name="password" id="registerPassword" class="form-control" required>
                    <button class="btn btn-outline-secondary" type="button" data-toggle-password="registerPassword"><i class="bi bi-eye-slash"></i></button>
                  </div>
                </div>
              </div>
              <div id="roleOtherWrap" class="d-none">
                <label class="form-label">Role Name</label>
                <input type="text" name="role_other" class="form-control" placeholder="Write your requested role">
                <div class="form-text">New roles require admin approval before the account can be used.</div>
              </div>

              <div>
                <label class="form-label">Confirm Password</label>
                <div class="input-group">
                  <input type="password" name="confirm_password" id="registerConfirmPassword" class="form-control" required>
                  <button class="btn btn-outline-secondary" type="button" data-toggle-password="registerConfirmPassword"><i class="bi bi-eye-slash"></i></button>
                </div>
                <div class="form-text">Use at least 8 characters with letters and numbers.</div>
              </div>

              <button type="submit" name="register_submit" class="btn btn-primary btn-lg w-100">Create Account</button>
            </form>
          </div>
        </div>
      </div>

      <div class="skillmap-auth-footer text-center mt-3">Develop by Aggies · &copy; <?= htmlspecialchars($currentYear, ENT_QUOTES, 'UTF-8') ?> Skill Map License</div>
    </section>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="/fyp_skillmapsystem/assets/js/app.js"></script>
  <script>
    document.querySelectorAll('[data-other-select]').forEach((select) => {
      const target = document.getElementById(select.getAttribute('data-other-select'));
      const sync = () => {
        if (!target) return;
        const isOther = select.value === '__other';
        target.classList.toggle('d-none', !isOther);
        target.querySelectorAll('input').forEach((input) => {
          input.required = isOther;
        });
      };
      select.addEventListener('change', sync);
      sync();
    });

    const roleSelect = document.querySelector('[data-register-role]');
    const studentFields = document.querySelectorAll('[data-student-field]');
    const departmentWrap = document.getElementById('departmentWrap');
    const departmentInput = departmentWrap?.querySelector('input[name="department"]');
    const programmeOtherWrap = document.getElementById('programmeOtherWrap');
    const programmeSelect = document.querySelector('select[name="programme"]');
    const syncRegisterRole = () => {
      const role = roleSelect?.value || 'student';
      const isStudent = role === 'student' || role === '__other';
      studentFields.forEach((field) => {
        field.disabled = !isStudent;
        field.required = isStudent;
        field.closest('.skillmap-form-grid > div, div')?.classList.toggle('d-none', !isStudent);
      });
      if (departmentWrap) {
        departmentWrap.classList.toggle('d-none', isStudent);
      }
      if (departmentInput) {
        departmentInput.required = !isStudent;
      }
      if (!isStudent && programmeOtherWrap) {
        programmeOtherWrap.classList.add('d-none');
        programmeOtherWrap.querySelectorAll('input').forEach((input) => {
          input.required = false;
        });
      } else if (programmeOtherWrap && programmeSelect?.value === '__other') {
        programmeOtherWrap.classList.remove('d-none');
        programmeOtherWrap.querySelectorAll('input').forEach((input) => {
          input.required = true;
        });
      }
    };
    roleSelect?.addEventListener('change', syncRegisterRole);
    syncRegisterRole();
  </script>
</body>
</html>
