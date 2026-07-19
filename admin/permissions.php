<?php
// Admin access control page for system roles and permissions.

require_once __DIR__ . '/../includes/auth_check.php';
skillmap_require_permission('manage_permissions');
$activePage = 'permissions';
$message = '';

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
}

$roles = skillmap_access_roles();
$permissions = skillmap_access_permissions();
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
            <div class="table-responsive">
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
        <div class="table-responsive">
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
  </main>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="/fyp_skillmapsystem/assets/js/app.js"></script>
</body>
</html>
