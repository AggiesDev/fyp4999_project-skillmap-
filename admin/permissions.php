<?php
// Admin access control page for system roles and permissions.

require_once __DIR__ . '/../includes/auth_check.php';
skillmap_require_permission('manage_permissions');
$activePage = 'permissions';
$message = '';
$selectedUserRole = (string) ($_POST['selected_user_role'] ?? $_GET['role'] ?? 'student');
$selectedUserId = (int) ($_POST['selected_user_id'] ?? $_GET['user_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_role'])) {
        $roleName = strtolower(trim((string) ($_POST['role_name'] ?? '')));
        $roleDescription = trim((string) ($_POST['role_description'] ?? ''));

        if ($roleName !== '') {
            $inserted = skillmap_db_query('INSERT IGNORE INTO access_roles (name, description) VALUES (?, ?)', 'ss', [$roleName, $roleDescription]);
            $message = $inserted === true ? 'Role saved successfully.' : 'Unable to save the role.';
        } else {
            $message = 'Role name is required.';
        }
    }

    if (isset($_POST['save_permissions'])) {
        $submittedRoles = is_array($_POST['role_permissions'] ?? null) ? $_POST['role_permissions'] : [];
        $roles = skillmap_access_roles();
        $permissions = skillmap_access_permissions();

        foreach ($roles as $role) {
            $roleId = (int) $role['id'];
            skillmap_db_query('DELETE FROM access_role_permissions WHERE role_id = ?', 'i', [$roleId]);

            $checkedPermissions = is_array($submittedRoles[$roleId] ?? null) ? $submittedRoles[$roleId] : [];
            foreach ($permissions as $permission) {
                if (!in_array((string) $permission['id'], $checkedPermissions, true)) {
                    continue;
                }

                skillmap_db_query(
                    'INSERT INTO access_role_permissions (role_id, permission_id, enabled) VALUES (?, ?, 1)',
                    'ii',
                    [$roleId, (int) $permission['id']]
                );
            }
        }

        $message = 'Permissions updated successfully.';
    }

    if (isset($_POST['save_user_permissions'])) {
        $selectedUserId = (int) ($_POST['selected_user_id'] ?? 0);
        $submittedPermissions = is_array($_POST['user_permissions'] ?? null) ? $_POST['user_permissions'] : [];
        $selectedUser = $selectedUserId > 0 ? skillmap_fetch_one('SELECT id, role FROM users WHERE id = ? LIMIT 1', 'i', [$selectedUserId]) : null;

        if ($selectedUser !== null) {
            skillmap_db_query('DELETE FROM user_permissions WHERE user_id = ?', 'i', [$selectedUserId]);
            foreach (skillmap_access_permissions() as $permission) {
                if (!in_array((string) $permission['id'], $submittedPermissions, true)) {
                    continue;
                }

                skillmap_db_query(
                    'INSERT INTO user_permissions (user_id, permission_id, enabled) VALUES (?, ?, 1)',
                    'ii',
                    [$selectedUserId, (int) $permission['id']]
                );
            }
            $selectedUserRole = (string) $selectedUser['role'];
            $message = 'User permissions updated successfully.';
        } else {
            $message = 'Please select a valid user before saving user permissions.';
        }
    }
}

$roles = skillmap_access_roles();
$permissions = skillmap_access_permissions();
$permissionUsers = skillmap_fetch_all(
    'SELECT id, name, email, role FROM users WHERE status = "Active" ORDER BY role ASC, name ASC'
);
if ($selectedUserId <= 0 && $permissionUsers !== []) {
    $selectedUserId = (int) $permissionUsers[0]['id'];
    $selectedUserRole = (string) $permissionUsers[0]['role'];
}
$rolePermissionRows = skillmap_fetch_all(
    'SELECT ar.id AS role_id, p.id AS permission_id
     FROM access_role_permissions arp
     INNER JOIN access_roles ar ON ar.id = arp.role_id
     INNER JOIN permissions p ON p.id = arp.permission_id
     WHERE arp.enabled = 1'
);
$permissionMap = [];
foreach ($rolePermissionRows as $row) {
    $permissionMap[(int) $row['role_id']][(int) $row['permission_id']] = true;
}
$userPermissionRows = skillmap_fetch_all(
    'SELECT user_id, permission_id FROM user_permissions WHERE enabled = 1'
);
$userPermissionMap = [];
foreach ($userPermissionRows as $row) {
    $userPermissionMap[(int) $row['user_id']][(int) $row['permission_id']] = true;
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Skill Map - Permissions</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="/fyp_skillmapsystem/assets/css/style.css" rel="stylesheet">
</head>
<body>
  <?php require __DIR__ . '/includes/navbar.php'; ?>
  <main class="container py-4 py-lg-5">
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
      <div>
        <h1 class="fw-bold mb-1">Permission Manager</h1>
        <div class="text-muted">Control access for admin, lecturer, staff, and future roles</div>
      </div>
      <a class="btn btn-outline-secondary" href="/fyp_skillmapsystem/admin/reviews.php">Review Students</a>
    </div>

    <?php if ($message !== ''): ?>
      <div class="alert alert-info"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <div class="row g-4 mb-4">
      <div class="col-lg-4">
        <div class="card h-100">
          <div class="card-body p-4">
            <h2 class="h5 fw-bold mb-3">Add Role</h2>
            <form method="post" class="d-grid gap-3">
              <div>
                <label class="form-label">Role Name</label>
                <input type="text" name="role_name" class="form-control" placeholder="coordinator" required>
              </div>
              <div>
                <label class="form-label">Description</label>
                <textarea name="role_description" class="form-control" rows="4" placeholder="Role access summary"></textarea>
              </div>
              <button class="btn btn-primary" type="submit" name="add_role">Save Role</button>
            </form>
          </div>
        </div>
      </div>
      <div class="col-lg-8">
        <div class="card h-100">
          <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <h2 class="h5 fw-bold mb-0">Current Roles</h2>
              <span class="badge text-bg-light border"><?= count($roles) ?> roles</span>
            </div>
            <div class="table-responsive skillmap-permission-scroll">
              <table class="table align-middle mb-0">
                <thead>
                  <tr>
                    <th>Role</th>
                    <th>Description</th>
                    <th>Permissions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($roles as $role): ?>
                    <tr>
                      <td class="fw-semibold"><?= htmlspecialchars($role['name'], ENT_QUOTES, 'UTF-8') ?></td>
                      <td class="text-muted"><?= htmlspecialchars($role['description'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                      <td><?= isset($permissionMap[(int) $role['id']]) ? count($permissionMap[(int) $role['id']]) : 0 ?> enabled</td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>

    <form method="post" class="card shadow-sm border-0">
      <div class="card-body p-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
          <div>
            <h2 class="h4 fw-bold mb-1">Role Permission Matrix</h2>
            <div class="text-muted">Check the permissions each role should have</div>
          </div>
          <button class="btn btn-primary" type="submit" name="save_permissions">Save Permissions</button>
        </div>
        <div class="table-responsive skillmap-permission-scroll">
          <table class="table align-middle">
            <thead>
              <tr>
                <th>Permission</th>
                <?php foreach ($roles as $role): ?>
                  <th><?= htmlspecialchars(ucfirst($role['name']), ENT_QUOTES, 'UTF-8') ?></th>
                <?php endforeach; ?>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($permissions as $permission): ?>
                <tr>
                  <td>
                    <div class="fw-semibold"><?= htmlspecialchars($permission['name'], ENT_QUOTES, 'UTF-8') ?></div>
                    <div class="small text-muted"><?= htmlspecialchars($permission['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                  </td>
                  <?php foreach ($roles as $role): ?>
                    <td class="text-center">
                      <input
                        type="checkbox"
                        class="form-check-input"
                        name="role_permissions[<?= (int) $role['id'] ?>][]"
                        value="<?= (int) $permission['id'] ?>"
                        <?= isset($permissionMap[(int) $role['id']][(int) $permission['id']]) ? 'checked' : '' ?>
                      >
                    </td>
                  <?php endforeach; ?>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </form>

    <form method="post" class="card shadow-sm border-0 mt-4">
      <div class="card-body p-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
          <div>
            <h2 class="h4 fw-bold mb-1">User Permission Override</h2>
            <div class="text-muted">Select a role, choose one user, then grant special permissions for that user only</div>
          </div>
          <button class="btn btn-primary" type="submit" name="save_user_permissions">Accept Permissions</button>
        </div>
        <div class="row g-3 mb-4">
          <div class="col-md-4">
            <label class="form-label">Select Role</label>
            <select class="form-select" name="selected_user_role" data-user-permission-role>
              <?php foreach ($roles as $role): ?>
                <option value="<?= htmlspecialchars($role['name'], ENT_QUOTES, 'UTF-8') ?>" <?= $selectedUserRole === $role['name'] ? 'selected' : '' ?>><?= htmlspecialchars(ucfirst($role['name']), ENT_QUOTES, 'UTF-8') ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-8">
            <label class="form-label">Select User</label>
            <select class="form-select" name="selected_user_id" data-user-permission-user required>
              <?php foreach ($permissionUsers as $permissionUser): ?>
                <option
                  value="<?= (int) $permissionUser['id'] ?>"
                  data-role="<?= htmlspecialchars((string) $permissionUser['role'], ENT_QUOTES, 'UTF-8') ?>"
                  <?= $selectedUserId === (int) $permissionUser['id'] ? 'selected' : '' ?>
                >
                  <?= htmlspecialchars($permissionUser['name'], ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars($permissionUser['email'], ENT_QUOTES, 'UTF-8') ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="table-responsive skillmap-permission-scroll">
          <table class="table align-middle mb-0">
            <thead>
              <tr>
                <th>Permission</th>
                <th class="text-center">Grant to selected user</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($permissions as $permission): ?>
                <tr>
                  <td>
                    <div class="fw-semibold"><?= htmlspecialchars($permission['name'], ENT_QUOTES, 'UTF-8') ?></div>
                    <div class="small text-muted"><?= htmlspecialchars($permission['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                  </td>
                  <td class="text-center">
                    <input
                      type="checkbox"
                      class="form-check-input"
                      name="user_permissions[]"
                      value="<?= (int) $permission['id'] ?>"
                      data-user-permission-checkbox="<?= (int) $permission['id'] ?>"
                      <?= isset($userPermissionMap[$selectedUserId][(int) $permission['id']]) ? 'checked' : '' ?>
                    >
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </form>
    <?php require __DIR__ . '/../includes/footer.php'; ?>
  </main>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="/fyp_skillmapsystem/assets/js/app.js"></script>
  <script>
    const userPermissionMap = <?= json_encode($userPermissionMap, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
    const roleSelect = document.querySelector('[data-user-permission-role]');
    const userSelect = document.querySelector('[data-user-permission-user]');
    const permissionChecks = document.querySelectorAll('[data-user-permission-checkbox]');

    const syncUserPermissionPicker = () => {
      if (!roleSelect || !userSelect) return;
      const selectedRole = roleSelect.value;
      let firstVisible = null;

      Array.from(userSelect.options).forEach((option) => {
        const visible = option.getAttribute('data-role') === selectedRole;
        option.hidden = !visible;
        option.disabled = !visible;
        if (visible && firstVisible === null) {
          firstVisible = option;
        }
      });

      if (userSelect.selectedOptions[0]?.disabled && firstVisible) {
        userSelect.value = firstVisible.value;
      }

      const selectedPermissions = userPermissionMap[userSelect.value] || {};
      permissionChecks.forEach((checkbox) => {
        checkbox.checked = Boolean(selectedPermissions[checkbox.value]);
      });
    };

    roleSelect?.addEventListener('change', syncUserPermissionPicker);
    userSelect?.addEventListener('change', syncUserPermissionPicker);
    syncUserPermissionPicker();
  </script>
</body>
</html>
