<?php
// Admin user management: create, edit, and activate/deactivate users.

require_once __DIR__ . '/../includes/auth_check.php';
skillmap_require_permission('manage_users');

$activePage = 'users';
$message = '';
$error = '';
$editUser = null;

function admin_user_initials(string $name): string
{
    return strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $name) ?: 'SM', 0, 2));
}

function admin_user_form_options(PDO $pdo): array
{
    $programmes = $pdo->query('SELECT DISTINCT programme AS value FROM users WHERE programme <> "" ORDER BY programme')->fetchAll();
    $years = $pdo->query('SELECT DISTINCT year_level AS value FROM users WHERE year_level LIKE "Year %" ORDER BY year_level')->fetchAll();
    $accessRoles = $pdo->query('SELECT name AS value FROM access_roles ORDER BY FIELD(name, "student", "lecturer", "staff", "admin"), name')->fetchAll();
    $userRoles = $pdo->query('SELECT DISTINCT role AS value FROM users WHERE role <> "" ORDER BY role')->fetchAll();

    return [
        'programmes' => array_values(array_unique(array_merge(['Information Systems'], array_column($programmes, 'value')))),
        'years' => array_values(array_unique(array_merge(['Year 1', 'Year 2', 'Year 3', 'Year 4'], array_column($years, 'value')))),
        'roles' => array_values(array_unique(array_merge(['student', 'lecturer', 'staff', 'admin'], array_column($accessRoles, 'value'), array_column($userRoles, 'value')))),
    ];
}

function admin_render_profile_icon_choices(string $selectedIcon): void
{
    foreach (skillmap_profile_icon_options() as $group => $icons) {
        echo '<div class="small fw-semibold text-muted text-capitalize mt-3 mb-2">' . htmlspecialchars($group, ENT_QUOTES, 'UTF-8') . '</div>';
        echo '<div class="profile-icon-grid">';
        foreach ($icons as $icon) {
            $checked = $selectedIcon === $icon ? 'checked' : '';
            echo '<label class="profile-icon-choice">';
            echo '<input type="radio" name="profile_icon" value="' . htmlspecialchars($icon, ENT_QUOTES, 'UTF-8') . '" ' . $checked . '>';
            echo '<img src="/fyp_skillmapsystem/' . htmlspecialchars($icon, ENT_QUOTES, 'UTF-8') . '" alt="">';
            echo '</label>';
        }
        echo '</div>';
    }
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'save_user') {
        $userId = (int) ($_POST['user_id'] ?? 0);
        $name = trim((string) ($_POST['name'] ?? ''));
        $username = strtolower(trim((string) ($_POST['username'] ?? '')));
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $programmeChoice = trim((string) ($_POST['programme'] ?? ''));
        $programmeOther = trim((string) ($_POST['programme_other'] ?? ''));
        $department = trim((string) ($_POST['department'] ?? ''));
        $yearLevel = trim((string) ($_POST['year_level'] ?? ''));
        $roleChoice = trim((string) ($_POST['role'] ?? 'student'));
        $roleOther = strtolower(trim((string) ($_POST['role_other'] ?? '')));
        $status = (string) ($_POST['status'] ?? 'Active');
        $password = (string) ($_POST['password'] ?? '');
        $confirmPassword = (string) ($_POST['confirm_password'] ?? '');
        $gender = skillmap_normalize_gender((string) ($_POST['gender'] ?? 'male'));

        $programme = $programmeChoice === '__other' ? $programmeOther : $programmeChoice;
        $role = $roleChoice === '__other' ? $roleOther : $roleChoice;
        $status = in_array($status, ['Active', 'Inactive'], true) ? $status : 'Active';
        $profileIcon = skillmap_sanitize_profile_icon((string) ($_POST['profile_icon'] ?? ''), $gender, $role);
        $isStudentRole = $role === 'student';

        if ($isStudentRole) {
            $programme = $programme !== '' ? $programme : 'Information Systems';
            $yearLevel = $yearLevel !== '' ? $yearLevel : 'Year 1';
        } else {
            $programme = $department !== '' ? $department : '';
            $yearLevel = 'N/A';
        }

        if ($name === '') {
            $error = 'Full name is required.';
        } elseif ($username === '' || !preg_match('/^[a-z0-9._-]{3,40}$/', $username)) {
            $error = 'Username must be 3-40 characters and use only letters, numbers, dots, underscores, or hyphens.';
        } elseif ($role === '') {
            $error = 'Role is required.';
        } elseif ($programme === '') {
            $error = $isStudentRole ? 'Programme is required.' : 'Department is required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } elseif ($userId === 0 && $password === '') {
            $error = 'Password is required when creating a user.';
        } elseif ($password !== '' && (strlen($password) < 8 || !preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password))) {
            $error = 'Password must be at least 8 characters and include letters and numbers.';
        } elseif ($password !== '' && $confirmPassword === '') {
            $error = 'Please confirm the password.';
        } elseif ($password !== '' && $password !== $confirmPassword) {
            $error = 'Password confirmation does not match.';
        } else {
            try {
                $duplicateEmail = $pdo->prepare('SELECT id FROM users WHERE email = :email AND id <> :id LIMIT 1');
                $duplicateEmail->execute(['email' => $email, 'id' => $userId]);
                $duplicateUsername = $pdo->prepare('SELECT id FROM users WHERE username = :username AND id <> :id LIMIT 1');
                $duplicateUsername->execute(['username' => $username, 'id' => $userId]);

                if ($duplicateEmail->fetchColumn()) {
                    throw new RuntimeException('This email address is already registered.');
                }
                if ($duplicateUsername->fetchColumn()) {
                    throw new RuntimeException('This username is already taken.');
                }

                if ($roleChoice === '__other') {
                    $accessRole = $pdo->prepare('INSERT IGNORE INTO access_roles (name, description) VALUES (:name, :description)');
                    $accessRole->execute([
                        'name' => $role,
                        'description' => 'Created by admin user management',
                    ]);
                }

                if ($userId > 0) {
                    $params = [
                        'name' => $name,
                        'username' => $username,
                        'email' => $email,
                        'role' => $role,
                        'programme' => $programme,
                        'year_level' => $yearLevel,
                        'avatar_initials' => admin_user_initials($name),
                        'gender' => $gender,
                        'profile_icon' => $profileIcon,
                        'status' => $status,
                        'id' => $userId,
                    ];
                    $passwordSql = '';
                    if ($password !== '') {
                        $passwordSql = ', password_hash = :password_hash';
                        $params['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
                    }

                    $stmt = $pdo->prepare(
                        'UPDATE users
                         SET name = :name, username = :username, email = :email, role = :role,
                             programme = :programme, year_level = :year_level, avatar_initials = :avatar_initials,
                             gender = :gender, profile_icon = :profile_icon, status = :status' . $passwordSql . '
                         WHERE id = :id'
                    );
                    $stmt->execute($params);
                    $message = 'User updated successfully.';
                } else {
                    $stmt = $pdo->prepare(
                        'INSERT INTO users (name, username, email, password_hash, role, programme, year_level, avatar_initials, gender, profile_icon, status)
                         VALUES (:name, :username, :email, :password_hash, :role, :programme, :year_level, :avatar_initials, :gender, :profile_icon, :status)'
                    );
                    $stmt->execute([
                        'name' => $name,
                        'username' => $username,
                        'email' => $email,
                        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                        'role' => $role,
                        'programme' => $programme,
                        'year_level' => $yearLevel,
                        'avatar_initials' => admin_user_initials($name),
                        'gender' => $gender,
                        'profile_icon' => $profileIcon,
                        'status' => $status,
                    ]);
                    $message = 'User created successfully.';
                }
            } catch (RuntimeException $exception) {
                $error = $exception->getMessage();
            } catch (PDOException $exception) {
                $error = $exception->getCode() === '23000'
                    ? 'Email or username already exists.'
                    : 'Unable to save the user.';
            }
        }
    }

    if ($action === 'toggle_status') {
        $userId = (int) ($_POST['user_id'] ?? 0);
        $status = (string) ($_POST['status'] ?? 'Active');
        $status = $status === 'Active' ? 'Active' : 'Inactive';
        if ($userId === (int) ($_SESSION['user_id'] ?? 0) && $status === 'Inactive') {
            $error = 'You cannot deactivate your own account.';
        } elseif ($userId > 0) {
            $stmt = $pdo->prepare('UPDATE users SET status = :status WHERE id = :id');
            $stmt->execute(['status' => $status, 'id' => $userId]);
            $message = 'User status updated.';
        }
    }

    if ($action === 'review_request') {
        $requestId = (int) ($_POST['request_id'] ?? 0);
        $decision = (string) ($_POST['decision'] ?? 'Rejected');
        $decision = $decision === 'Approved' ? 'Approved' : 'Rejected';
        $reviewerId = (int) ($_SESSION['user_id'] ?? 0);

        $stmt = $pdo->prepare(
            'SELECT ar.id, ar.user_id, ar.request_type, ar.requested_value, u.role
             FROM account_approval_requests ar
             INNER JOIN users u ON u.id = ar.user_id
             WHERE ar.id = :id AND ar.status = "Pending"
             LIMIT 1'
        );
        $stmt->execute(['id' => $requestId]);
        $request = $stmt->fetch();

        if (!$request) {
            $error = 'Approval request was not found.';
        } else {
            $pdo->beginTransaction();
            try {
                $updateRequest = $pdo->prepare(
                    'UPDATE account_approval_requests
                     SET status = :status, reviewed_by = :reviewed_by, reviewed_at = NOW()
                     WHERE id = :id'
                );
                $updateRequest->execute([
                    'status' => $decision,
                    'reviewed_by' => $reviewerId > 0 ? $reviewerId : null,
                    'id' => $requestId,
                ]);

                if ($decision === 'Approved' && $request['request_type'] === 'role') {
                    $roleName = strtolower(trim((string) $request['requested_value']));
                    if ($roleName !== '') {
                        $addAccessRole = $pdo->prepare('INSERT IGNORE INTO access_roles (name, description) VALUES (:name, :description)');
                        $addAccessRole->execute([
                            'name' => $roleName,
                            'description' => 'Requested during account registration',
                        ]);
                    }
                }

                $pending = $pdo->prepare(
                    'SELECT COUNT(*)
                     FROM account_approval_requests
                     WHERE user_id = :user_id AND status = "Pending"'
                );
                $pending->execute(['user_id' => (int) $request['user_id']]);
                if ((int) $pending->fetchColumn() === 0) {
                    $rejected = $pdo->prepare(
                        'SELECT COUNT(*)
                         FROM account_approval_requests
                         WHERE user_id = :user_id AND status = "Rejected"'
                    );
                    $rejected->execute(['user_id' => (int) $request['user_id']]);
                    $newStatus = (int) $rejected->fetchColumn() === 0 ? 'Active' : 'Inactive';
                    $approvedRole = $pdo->prepare(
                        'SELECT requested_value
                         FROM account_approval_requests
                         WHERE user_id = :user_id AND request_type = "role" AND status = "Approved"
                         ORDER BY reviewed_at DESC
                         LIMIT 1'
                    );
                    $approvedRole->execute(['user_id' => (int) $request['user_id']]);
                    $approvedRoleName = strtolower(trim((string) ($approvedRole->fetchColumn() ?: '')));

                    if ($approvedRoleName !== '') {
                        $activate = $pdo->prepare('UPDATE users SET status = :status, role = :role WHERE id = :id');
                        $activate->execute(['status' => $newStatus, 'role' => $approvedRoleName, 'id' => (int) $request['user_id']]);
                    } else {
                        $activate = $pdo->prepare('UPDATE users SET status = :status WHERE id = :id');
                        $activate->execute(['status' => $newStatus, 'id' => (int) $request['user_id']]);
                    }
                }

                $pdo->commit();
                $message = $decision === 'Approved' ? 'Registration request approved.' : 'Registration request rejected.';
            } catch (Throwable $exception) {
                $pdo->rollBack();
                $error = 'Unable to review this request.';
            }
        }
    }
}

$editId = (int) ($_GET['edit'] ?? 0);
if ($editId > 0) {
    $stmt = $pdo->prepare('SELECT id, name, username, email, role, programme, year_level, avatar_initials, gender, profile_icon, status, last_login_at FROM users WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $editId]);
    $editUser = $stmt->fetch() ?: null;
}

$users = $pdo->query(
    'SELECT u.id, u.name, u.username, u.email, u.role, u.programme, u.year_level, u.avatar_initials, u.gender, u.profile_icon, u.status, u.last_login_at,
            COALESCE(COUNT(a.id), 0) AS analyses,
            COALESCE(ROUND(MAX(a.match_score)), 0) AS best_match,
            COALESCE((SELECT cr.name FROM analyses a2 INNER JOIN career_roles cr ON cr.id = a2.target_role_id WHERE a2.user_id = u.id ORDER BY a2.match_score DESC LIMIT 1), "-") AS top_role,
            COALESCE((SELECT current_streak FROM learning_streaks ls WHERE ls.user_id = u.id), 0) AS streak,
            DATE_FORMAT(u.last_login_at, "%e %b %Y, %h:%i %p") AS last_login,
            DATE_FORMAT(u.updated_at, "%e %b %Y") AS last_active
     FROM users u
     LEFT JOIN analyses a ON a.user_id = u.id
     WHERE u.role <> "admin"
     GROUP BY u.id
     ORDER BY u.created_at DESC'
)->fetchAll();

$totalUsers = count($users);
$activeUsers = count(array_filter($users, static fn(array $user): bool => $user['status'] === 'Active'));
$studentCount = count(array_filter($users, static fn(array $user): bool => $user['role'] === 'student'));
$avgBest = $totalUsers > 0 ? (int) round(array_sum(array_map(static fn(array $user): int => (int) $user['best_match'], $users)) / $totalUsers) : 0;
$pendingRequests = skillmap_pending_account_requests();
$formOptions = admin_user_form_options($pdo);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Skill Map - Users</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="/fyp_skillmapsystem/assets/css/style.css" rel="stylesheet">
</head>
<body>
  <?php require __DIR__ . '/includes/navbar.php'; ?>
  <main class="container-fluid py-4 py-lg-5">
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
      <div>
        <h1 class="fw-bold mb-1">User Management</h1>
        <div class="text-muted">Create accounts, update roles, and control access status</div>
      </div>
      <div class="d-flex flex-wrap gap-2">
        <button class="btn btn-primary" type="button" data-toggle-panel="adminUserForm">
          <i class="bi bi-person-plus me-1"></i>Add New User
        </button>
        <a class="btn btn-outline-secondary" href="/fyp_skillmapsystem/admin/permissions.php">Manage Permissions</a>
      </div>
    </div>

    <?php if ($message !== ''): ?><div class="alert alert-success"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
    <?php if ($error !== ''): ?><div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>

    <div class="row g-3 mb-4">
      <div class="col-md-6 col-xl-3"><div class="card"><div class="card-body"><div class="text-muted small">Total Users</div><div class="fs-3 fw-bold"><?= $totalUsers ?></div></div></div></div>
      <div class="col-md-6 col-xl-3"><div class="card"><div class="card-body"><div class="text-muted small">Active Users</div><div class="fs-3 fw-bold"><?= $activeUsers ?></div></div></div></div>
      <div class="col-md-6 col-xl-3"><div class="card"><div class="card-body"><div class="text-muted small">Students</div><div class="fs-3 fw-bold"><?= $studentCount ?></div></div></div></div>
      <div class="col-md-6 col-xl-3"><div class="card"><div class="card-body"><div class="text-muted small">Avg Best Match</div><div class="fs-3 fw-bold"><?= $avgBest ?>%</div></div></div></div>
    </div>

    <div class="row g-4">
      <div class="col-xl-4 skillmap-admin-form-side">
        <?php if ($pendingRequests !== []): ?>
          <div class="card mb-4">
            <div class="card-body p-4">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h5 fw-bold mb-0">Pending Approvals</h2>
                <span class="badge text-bg-warning"><?= count($pendingRequests) ?></span>
              </div>
              <div class="d-grid gap-3">
                <?php foreach ($pendingRequests as $request): ?>
                  <div class="border rounded-4 p-3">
                    <div class="fw-semibold"><?= htmlspecialchars($request['name'], ENT_QUOTES, 'UTF-8') ?></div>
                    <div class="small text-muted mb-2"><?= htmlspecialchars($request['email'], ENT_QUOTES, 'UTF-8') ?></div>
                    <div class="small">
                      Requested <?= htmlspecialchars($request['request_type'], ENT_QUOTES, 'UTF-8') ?>:
                      <strong><?= htmlspecialchars($request['requested_value'], ENT_QUOTES, 'UTF-8') ?></strong>
                    </div>
                    <?php if ($request['request_type'] === 'role'): ?>
                      <div class="form-text">Approved custom roles are added to access roles. Assign system access from Permissions if needed.</div>
                    <?php endif; ?>
                    <div class="d-flex gap-2 mt-3">
                      <form method="post">
                        <input type="hidden" name="action" value="review_request">
                        <input type="hidden" name="request_id" value="<?= (int) $request['id'] ?>">
                        <button class="btn btn-sm btn-success" type="submit" name="decision" value="Approved">Approve</button>
                      </form>
                      <form method="post">
                        <input type="hidden" name="action" value="review_request">
                        <input type="hidden" name="request_id" value="<?= (int) $request['id'] ?>">
                        <button class="btn btn-sm btn-outline-danger" type="submit" name="decision" value="Rejected">Reject</button>
                      </form>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          </div>
        <?php endif; ?>

        <form method="post" class="card <?= $editUser || $error !== '' ? '' : 'd-none' ?>" id="adminUserForm">
          <div class="card-body p-4">
            <input type="hidden" name="action" value="save_user">
            <input type="hidden" name="user_id" value="<?= (int) ($editUser['id'] ?? 0) ?>">
            <h2 class="h5 fw-bold mb-3"><?= $editUser ? 'Edit User' : 'Create User' ?></h2>
            <div class="d-grid gap-3">
              <div><label class="form-label">Name</label><input name="name" class="form-control" value="<?= htmlspecialchars((string) ($editUser['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required></div>
              <div><label class="form-label">Username</label><input name="username" class="form-control" value="<?= htmlspecialchars((string) ($editUser['username'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required></div>
              <div><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="<?= htmlspecialchars((string) ($editUser['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required></div>
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label">Gender</label>
                  <?php $selectedGender = skillmap_normalize_gender((string) ($editUser['gender'] ?? 'male')); ?>
                  <select name="gender" class="form-select" data-profile-gender>
                    <option value="male" <?= $selectedGender === 'male' ? 'selected' : '' ?>>Male</option>
                    <option value="female" <?= $selectedGender === 'female' ? 'selected' : '' ?>>Female</option>
                  </select>
                </div>
                <div class="col-md-6 d-flex align-items-end">
                  <?php $selectedIcon = skillmap_sanitize_profile_icon((string) ($editUser['profile_icon'] ?? ''), $selectedGender, (string) ($editUser['role'] ?? 'student')); ?>
                  <div class="text-center">
                    <img class="profile-icon-preview" src="/fyp_skillmapsystem/<?= htmlspecialchars($selectedIcon, ENT_QUOTES, 'UTF-8') ?>" alt="">
                    <button class="btn btn-sm btn-outline-primary mt-2" type="button" data-toggle-panel="adminProfileIconPanel">
                      <i class="bi bi-images me-1"></i>Change User Icon
                    </button>
                  </div>
                </div>
              </div>
              <div id="adminProfileIconPanel" class="d-none">
                <label class="form-label">Profile Icon</label>
                <?php admin_render_profile_icon_choices($selectedIcon); ?>
              </div>
              <div class="row g-3">
                <div class="col-md-6" data-admin-student-field-wrap>
                  <label class="form-label">Programme</label>
                  <?php $selectedProgramme = (string) ($editUser['programme'] ?? ($formOptions['programmes'][0] ?? 'Information Systems')); ?>
                  <select name="programme" class="form-select" data-other-select="adminProgrammeOtherWrap" data-admin-student-field required>
                    <?php foreach ($formOptions['programmes'] as $programme): ?>
                      <option value="<?= htmlspecialchars($programme, ENT_QUOTES, 'UTF-8') ?>" <?= $selectedProgramme === $programme ? 'selected' : '' ?>><?= htmlspecialchars($programme, ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                    <option value="__other">Other</option>
                  </select>
                </div>
                <div class="col-md-6" data-admin-student-field-wrap>
                  <label class="form-label">Year Level</label>
                  <?php $selectedYear = (string) ($editUser['year_level'] ?? 'Year 1'); ?>
                  <select name="year_level" class="form-select" data-admin-student-field required>
                    <?php foreach ($formOptions['years'] as $year): ?>
                      <option value="<?= htmlspecialchars($year, ENT_QUOTES, 'UTF-8') ?>" <?= $selectedYear === $year ? 'selected' : '' ?>><?= htmlspecialchars($year, ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>
              <div id="adminProgrammeOtherWrap" class="d-none">
                <label class="form-label">Programme Name</label>
                <input type="text" name="programme_other" class="form-control" placeholder="Write programme name">
              </div>
              <div id="adminDepartmentWrap" class="d-none">
                <label class="form-label">Department</label>
                <input type="text" name="department" class="form-control" value="<?= htmlspecialchars($selectedProgramme, ENT_QUOTES, 'UTF-8') ?>" placeholder="Academic department or office">
              </div>
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label">Role</label>
                  <?php $selectedRole = (string) ($editUser['role'] ?? 'student'); ?>
                  <select name="role" class="form-select" data-other-select="adminRoleOtherWrap" data-admin-user-role required>
                    <?php foreach ($formOptions['roles'] as $role): ?>
                      <option value="<?= htmlspecialchars($role, ENT_QUOTES, 'UTF-8') ?>" <?= $selectedRole === $role ? 'selected' : '' ?>><?= htmlspecialchars(ucwords(str_replace('_', ' ', $role)), ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                    <option value="__other">Other</option>
                  </select>
                </div>
                <div class="col-md-6"><label class="form-label">Status</label><select name="status" class="form-select"><option value="Active" <?= ($editUser['status'] ?? 'Active') === 'Active' ? 'selected' : '' ?>>Active</option><option value="Inactive" <?= ($editUser['status'] ?? '') === 'Inactive' ? 'selected' : '' ?>>Inactive</option></select></div>
              </div>
              <div id="adminRoleOtherWrap" class="d-none">
                <label class="form-label">Role Name</label>
                <input type="text" name="role_other" class="form-control" placeholder="Write role name">
                <div class="form-text">A new access role will be created automatically.</div>
              </div>
              <div>
                <label class="form-label"><?= $editUser ? 'New Password' : 'Password' ?></label>
                <input type="password" name="password" class="form-control" <?= $editUser ? '' : 'required' ?>>
                <div class="form-text"><?= $editUser ? 'Leave blank to keep current password.' : 'Required for new users.' ?> Use at least 8 characters with letters and numbers.</div>
              </div>
              <div>
                <label class="form-label">Confirm Password</label>
                <input type="password" name="confirm_password" class="form-control" <?= $editUser ? '' : 'required' ?>>
              </div>
            </div>
          </div>
          <div class="card-footer bg-white border-0 p-4 pt-0 d-flex gap-2">
            <button class="btn btn-primary" type="submit"><i class="bi bi-check2 me-1"></i>Save User</button>
            <?php if ($editUser): ?><a class="btn btn-outline-secondary" href="/fyp_skillmapsystem/admin/users.php">Cancel</a><?php else: ?><button class="btn btn-outline-secondary" type="button" data-toggle-panel="adminUserForm">Cancel</button><?php endif; ?>
          </div>
        </form>
      </div>

      <div class="col-xl-8 skillmap-admin-table-col">
        <div class="card" data-search-scope>
          <div class="card-body p-3 p-lg-4">
            <div class="skillmap-search">
              <i class="bi bi-search"></i>
              <input class="form-control" type="search" placeholder="Search users, roles, programme, email, or status" data-search-input>
            </div>
          </div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table align-middle mb-0">
                <thead><tr><th>User</th><th>Role</th><th>Programme</th><th>Last Login</th><th>Analyses</th><th>Best Match</th><th>Status</th><th class="skillmap-actions-col">Actions</th></tr></thead>
                <tbody>
                  <?php foreach ($users as $row): ?>
                    <tr data-search-item data-search-text="<?= htmlspecialchars($row['name'] . ' ' . $row['username'] . ' ' . $row['email'] . ' ' . $row['role'] . ' ' . $row['programme'] . ' ' . $row['year_level'] . ' ' . $row['status'] . ' ' . $row['top_role'], ENT_QUOTES, 'UTF-8') ?>">
                      <td><div class="d-flex align-items-center gap-3"><?php if (!empty($row['profile_icon'])): ?><img class="table-profile-icon bg-primary" src="/fyp_skillmapsystem/<?= htmlspecialchars($row['profile_icon'], ENT_QUOTES, 'UTF-8') ?>" alt=""><?php else: ?><div class="avatar-circle bg-primary"><?= htmlspecialchars($row['avatar_initials'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?><div><div class="fw-semibold"><?= htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8') ?></div><div class="small text-muted"><?= htmlspecialchars($row['email'], ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars($row['username'], ENT_QUOTES, 'UTF-8') ?></div></div></div></td>
                      <td><span class="badge text-bg-light border"><?= htmlspecialchars(ucfirst($row['role']), ENT_QUOTES, 'UTF-8') ?></span></td>
                      <td><?= htmlspecialchars($row['programme'], ENT_QUOTES, 'UTF-8') ?><div class="small text-muted"><?= htmlspecialchars($row['year_level'], ENT_QUOTES, 'UTF-8') ?></div></td>
                      <td><?= htmlspecialchars((string) ($row['last_login'] ?? 'Never'), ENT_QUOTES, 'UTF-8') ?></td>
                      <td><?= (int) $row['analyses'] ?></td>
                      <td><?= (int) $row['best_match'] ?>%<div class="small text-muted"><?= htmlspecialchars($row['top_role'], ENT_QUOTES, 'UTF-8') ?></div></td>
                      <td><?= skillmap_status_badge($row['status']) ?></td>
                      <td class="skillmap-actions-col">
                        <div class="d-flex flex-wrap gap-2">
                          <a class="btn btn-sm btn-outline-primary" href="/fyp_skillmapsystem/admin/users.php?edit=<?= (int) $row['id'] ?>"><i class="bi bi-pencil"></i></a>
                          <?php if ($row['role'] === 'student'): ?><a class="btn btn-sm btn-outline-secondary" href="/fyp_skillmapsystem/admin/reviews.php?student_id=<?= (int) $row['id'] ?>"><i class="bi bi-person-check"></i></a><?php endif; ?>
                          <form method="post">
                            <input type="hidden" name="action" value="toggle_status">
                            <input type="hidden" name="user_id" value="<?= (int) $row['id'] ?>">
                            <input type="hidden" name="status" value="<?= $row['status'] === 'Active' ? 'Inactive' : 'Active' ?>">
                            <button class="btn btn-sm <?= $row['status'] === 'Active' ? 'btn-outline-warning' : 'btn-outline-success' ?>" type="submit" onclick="return confirm('Update this user status?');">
                              <i class="bi <?= $row['status'] === 'Active' ? 'bi-pause-circle' : 'bi-play-circle' ?>"></i>
                            </button>
                          </form>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                  <?php if ($users === []): ?><tr><td colspan="8" class="text-center text-muted py-4">No users found.</td></tr><?php endif; ?>
                  <tr class="d-none" data-search-empty><td colspan="8" class="text-center text-muted py-4">No matching users found.</td></tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
    <?php require __DIR__ . '/../includes/footer.php'; ?>
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

    const adminRoleSelect = document.querySelector('[data-admin-user-role]');
    const adminStudentFields = document.querySelectorAll('[data-admin-student-field]');
    const adminStudentWraps = document.querySelectorAll('[data-admin-student-field-wrap]');
    const adminDepartmentWrap = document.getElementById('adminDepartmentWrap');
    const adminDepartmentInput = adminDepartmentWrap?.querySelector('input[name="department"]');
    const adminProgrammeSelect = document.querySelector('select[name="programme"]');
    const adminProgrammeOtherWrap = document.getElementById('adminProgrammeOtherWrap');

    const syncAdminUserRole = () => {
      const role = adminRoleSelect?.value || 'student';
      const isStudent = role === 'student';

      adminStudentWraps.forEach((wrap) => {
        wrap.classList.toggle('d-none', !isStudent);
      });
      adminStudentFields.forEach((field) => {
        field.disabled = !isStudent;
        field.required = isStudent;
      });

      if (adminDepartmentWrap) {
        adminDepartmentWrap.classList.toggle('d-none', isStudent);
      }
      if (adminDepartmentInput) {
        adminDepartmentInput.required = !isStudent;
      }

      if (!isStudent && adminProgrammeOtherWrap) {
        adminProgrammeOtherWrap.classList.add('d-none');
        adminProgrammeOtherWrap.querySelectorAll('input').forEach((input) => {
          input.required = false;
        });
      } else if (adminProgrammeOtherWrap && adminProgrammeSelect?.value === '__other') {
        adminProgrammeOtherWrap.classList.remove('d-none');
        adminProgrammeOtherWrap.querySelectorAll('input').forEach((input) => {
          input.required = true;
        });
      }
    };

    adminRoleSelect?.addEventListener('change', syncAdminUserRole);
    adminProgrammeSelect?.addEventListener('change', syncAdminUserRole);
    syncAdminUserRole();
  </script>
</body>
</html>
