<?php
// Admin role and benchmark manager.

require_once __DIR__ . '/../includes/auth_check.php';
skillmap_require_permission('manage_roles');

$activePage = 'benchmarks';
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'save_role') {
        $roleId = (int) ($_POST['role_id'] ?? 0);
        $name = trim((string) ($_POST['name'] ?? ''));
        $type = (string) ($_POST['type'] ?? 'Career');
        $description = trim((string) ($_POST['description'] ?? ''));
        $type = in_array($type, ['Career', 'Lead'], true) ? $type : 'Career';

        if ($name === '') {
            $error = 'Role name is required.';
        } else {
            try {
                if ($roleId > 0) {
                    $stmt = $pdo->prepare('UPDATE career_roles SET name = :name, type = :type, description = :description WHERE id = :id');
                    $stmt->execute(['name' => $name, 'type' => $type, 'description' => $description ?: null, 'id' => $roleId]);
                    $message = 'Role updated successfully.';
                } else {
                    $stmt = $pdo->prepare('INSERT INTO career_roles (name, type, description) VALUES (:name, :type, :description)');
                    $stmt->execute(['name' => $name, 'type' => $type, 'description' => $description ?: null]);
                    $_GET['role_id'] = (string) $pdo->lastInsertId();
                    $message = 'Role created successfully.';
                }
            } catch (PDOException $exception) {
                $error = $exception->getCode() === '23000' ? 'A target role with this name already exists.' : 'Unable to save role.';
            }
        }
    }

    if ($action === 'delete_role') {
        $roleId = (int) ($_POST['role_id'] ?? 0);
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM analyses WHERE target_role_id = :id');
        $stmt->execute(['id' => $roleId]);
        if ((int) $stmt->fetchColumn() > 0) {
            $error = 'This role has student analyses. Keep it, or create a new role instead.';
        } elseif ($roleId > 0) {
            $stmt = $pdo->prepare('DELETE FROM career_roles WHERE id = :id');
            $stmt->execute(['id' => $roleId]);
            $message = 'Role deleted successfully.';
        }
    }

    if ($action === 'save_benchmark') {
        $benchmarkId = (int) ($_POST['benchmark_id'] ?? 0);
        $roleId = (int) ($_POST['role_id'] ?? 0);
        $skillId = (int) ($_POST['skill_id'] ?? 0);
        $required = max(1, min(5, (int) ($_POST['required_rating'] ?? 1)));
        $priority = (string) ($_POST['priority'] ?? 'Important');
        $priority = in_array($priority, ['Critical', 'Important', 'Optional'], true) ? $priority : 'Important';

        if ($roleId <= 0 || $skillId <= 0) {
            $error = 'Choose a role and skill for the benchmark.';
        } else {
            if ($benchmarkId > 0) {
                $stmt = $pdo->prepare(
                    'UPDATE role_skill_benchmarks
                     SET skill_id = :skill_id, required_rating = :required_rating, priority = :priority
                     WHERE id = :id AND role_id = :role_id'
                );
                $stmt->execute([
                    'skill_id' => $skillId,
                    'required_rating' => $required,
                    'priority' => $priority,
                    'id' => $benchmarkId,
                    'role_id' => $roleId,
                ]);
                $message = 'Benchmark updated successfully.';
            } else {
                $exists = $pdo->prepare('SELECT id FROM role_skill_benchmarks WHERE role_id = :role_id AND skill_id = :skill_id LIMIT 1');
                $exists->execute(['role_id' => $roleId, 'skill_id' => $skillId]);
                $existingId = (int) ($exists->fetchColumn() ?: 0);
                if ($existingId > 0) {
                    $stmt = $pdo->prepare('UPDATE role_skill_benchmarks SET required_rating = :required_rating, priority = :priority WHERE id = :id');
                    $stmt->execute(['required_rating' => $required, 'priority' => $priority, 'id' => $existingId]);
                    $message = 'Existing benchmark updated successfully.';
                } else {
                    $stmt = $pdo->prepare(
                        'INSERT INTO role_skill_benchmarks (role_id, skill_id, required_rating, priority)
                         VALUES (:role_id, :skill_id, :required_rating, :priority)'
                    );
                    $stmt->execute([
                        'role_id' => $roleId,
                        'skill_id' => $skillId,
                        'required_rating' => $required,
                        'priority' => $priority,
                    ]);
                    $message = 'Benchmark added successfully.';
                }
            }
            $_GET['role_id'] = (string) $roleId;
        }
    }

    if ($action === 'delete_benchmark') {
        $benchmarkId = (int) ($_POST['benchmark_id'] ?? 0);
        $roleId = (int) ($_POST['role_id'] ?? 0);
        if ($benchmarkId > 0) {
            $stmt = $pdo->prepare('DELETE FROM role_skill_benchmarks WHERE id = :id');
            $stmt->execute(['id' => $benchmarkId]);
            $_GET['role_id'] = (string) $roleId;
            $message = 'Benchmark removed successfully.';
        }
    }
}

$roles = $pdo->query(
    'SELECT cr.id, cr.name, cr.type, cr.description,
            COUNT(rb.id) AS skills,
            COALESCE(ROUND(AVG(rb.required_rating), 1), 0) AS avg_required,
            SUM(rb.priority = "Critical") AS critical_count
     FROM career_roles cr
     LEFT JOIN role_skill_benchmarks rb ON rb.role_id = cr.id
     GROUP BY cr.id
     ORDER BY cr.type, cr.name'
)->fetchAll();

$selectedRoleId = (int) ($_GET['role_id'] ?? ($roles[0]['id'] ?? 0));
$selectedRole = null;
foreach ($roles as $role) {
    if ((int) $role['id'] === $selectedRoleId) {
        $selectedRole = $role;
        break;
    }
}
if (!$selectedRole && $roles !== []) {
    $selectedRole = $roles[0];
    $selectedRoleId = (int) $selectedRole['id'];
}
$roleFormRole = isset($_GET['new']) ? null : $selectedRole;

$benchmarks = [];
if ($selectedRoleId > 0) {
    $stmt = $pdo->prepare(
        'SELECT rb.id, rb.skill_id, rb.required_rating, rb.priority, s.name AS skill_name, c.name AS category
         FROM role_skill_benchmarks rb
         INNER JOIN skills s ON s.id = rb.skill_id
         INNER JOIN skill_categories c ON c.id = s.category_id
         WHERE rb.role_id = :role_id
         ORDER BY FIELD(rb.priority, "Critical", "Important", "Optional"), rb.required_rating DESC, s.name'
    );
    $stmt->execute(['role_id' => $selectedRoleId]);
    $benchmarks = $stmt->fetchAll();
}

$skills = $pdo->query(
    'SELECT s.id, s.name, c.name AS category
     FROM skills s
     INNER JOIN skill_categories c ON c.id = s.category_id
     WHERE s.status = "Active"
     ORDER BY c.name, s.name'
)->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Skill Map - Benchmarks</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="/fyp_skillmapsystem/assets/css/style.css" rel="stylesheet">
</head>
<body>
  <?php require __DIR__ . '/includes/navbar.php'; ?>
  <main class="container-fluid py-4 py-lg-5">
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
      <div>
        <h1 class="fw-bold mb-1">Benchmark Manager</h1>
        <div class="text-muted">Maintain target roles and required skill ratings</div>
      </div>
      <a class="btn btn-outline-primary" href="/fyp_skillmapsystem/admin/skill_library.php">Skill Library</a>
    </div>

    <?php if ($message !== ''): ?><div class="alert alert-success"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
    <?php if ($error !== ''): ?><div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>

    <div class="row g-4">
      <div class="col-xl-3">
        <div class="card mb-4" data-search-scope>
          <div class="card-body p-3 p-lg-4">
            <div class="fw-bold mb-3">Roles</div>
            <div class="skillmap-search mb-3">
              <i class="bi bi-search"></i>
              <input class="form-control" type="search" placeholder="Search roles" data-search-input>
            </div>
            <div class="skillmap-role-scroll">
              <?php foreach ($roles as $role): ?>
                <div class="skillmap-role-list-item <?= (int) $role['id'] === $selectedRoleId ? 'active' : '' ?>" data-search-item data-search-text="<?= htmlspecialchars($role['name'] . ' ' . $role['type'] . ' ' . $role['description'], ENT_QUOTES, 'UTF-8') ?>">
                  <a class="skillmap-role-list-main" href="/fyp_skillmapsystem/admin/benchmarks.php?role_id=<?= (int) $role['id'] ?>">
                    <div class="d-flex justify-content-between gap-2">
                      <span class="fw-semibold text-truncate"><?= htmlspecialchars($role['name'], ENT_QUOTES, 'UTF-8') ?></span>
                      <span class="badge text-bg-light border"><?= htmlspecialchars($role['type'], ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <div class="small opacity-75"><?= (int) $role['skills'] ?> skills · <?= (float) $role['avg_required'] ?> avg</div>
                  </a>
                  <div class="skillmap-role-list-actions">
                    <a class="btn btn-sm btn-outline-primary" href="/fyp_skillmapsystem/admin/benchmarks.php?role_id=<?= (int) $role['id'] ?>" title="Edit role" aria-label="Edit <?= htmlspecialchars($role['name'], ENT_QUOTES, 'UTF-8') ?>">
                      <i class="bi bi-pencil"></i>
                    </a>
                    <form method="post">
                      <input type="hidden" name="action" value="delete_role">
                      <input type="hidden" name="role_id" value="<?= (int) $role['id'] ?>">
                      <button class="btn btn-sm btn-outline-danger" type="submit" onclick="return confirm('Delete this role?');" title="Delete role" aria-label="Delete <?= htmlspecialchars($role['name'], ENT_QUOTES, 'UTF-8') ?>">
                        <i class="bi bi-trash"></i>
                      </button>
                    </form>
                  </div>
                </div>
              <?php endforeach; ?>
              <?php if ($roles === []): ?><div class="alert alert-light border mb-0">No roles yet.</div><?php endif; ?>
              <div class="alert alert-light border mb-0 d-none" data-search-empty>No matching roles found.</div>
            </div>
          </div>
        </div>

        <form method="post" class="card">
          <div class="card-body p-4">
            <input type="hidden" name="action" value="save_role">
            <input type="hidden" name="role_id" value="<?= (int) ($roleFormRole['id'] ?? 0) ?>">
            <h2 class="h6 fw-bold mb-3"><?= $roleFormRole ? 'Edit Selected Role' : 'Add Role' ?></h2>
            <div class="d-grid gap-3">
              <div><label class="form-label">Role Name</label><input name="name" class="form-control" value="<?= htmlspecialchars((string) ($roleFormRole['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required></div>
              <div><label class="form-label">Type</label><select name="type" class="form-select"><option value="Career" <?= ($roleFormRole['type'] ?? '') === 'Career' ? 'selected' : '' ?>>Career</option><option value="Lead" <?= ($roleFormRole['type'] ?? '') === 'Lead' ? 'selected' : '' ?>>Lead</option></select></div>
              <div><label class="form-label">Description</label><textarea name="description" class="form-control" rows="3"><?= htmlspecialchars((string) ($roleFormRole['description'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea></div>
            </div>
          </div>
          <div class="card-footer bg-white border-0 p-4 pt-0 d-flex flex-wrap gap-2">
            <button class="btn btn-primary btn-sm" type="submit">Save Role</button>
            <a class="btn btn-outline-secondary btn-sm" href="/fyp_skillmapsystem/admin/benchmarks.php?new=1">New</a>
          </div>
        </form>
      </div>

      <div class="col-xl-9">
        <div class="card mb-4">
          <div class="card-body p-4">
            <h2 class="h5 fw-bold mb-3">Add Benchmark Skill</h2>
            <?php if (!$selectedRole): ?>
              <div class="alert alert-light border mb-0">Create a role before adding benchmarks.</div>
            <?php else: ?>
              <form method="post" class="row g-3 align-items-end">
                <input type="hidden" name="action" value="save_benchmark">
                <input type="hidden" name="role_id" value="<?= (int) $selectedRoleId ?>">
                <div class="col-lg-5"><label class="form-label">Skill</label><select name="skill_id" class="form-select" required><?php foreach ($skills as $skill): ?><option value="<?= (int) $skill['id'] ?>"><?= htmlspecialchars($skill['category'] . ' - ' . $skill['name'], ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select></div>
                <div class="col-lg-2"><label class="form-label">Required</label><select name="required_rating" class="form-select"><?php for ($i = 1; $i <= 5; $i++): ?><option value="<?= $i ?>"><?= $i ?>/5</option><?php endfor; ?></select></div>
                <div class="col-lg-3"><label class="form-label">Priority</label><select name="priority" class="form-select"><option>Critical</option><option selected>Important</option><option>Optional</option></select></div>
                <div class="col-lg-2"><button class="btn btn-success w-100" type="submit"><i class="bi bi-plus-lg me-1"></i>Add</button></div>
              </form>
            <?php endif; ?>
          </div>
        </div>

        <div class="card" data-search-scope>
          <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <h2 class="h5 fw-bold mb-0"><?= htmlspecialchars((string) ($selectedRole['name'] ?? 'No Role Selected'), ENT_QUOTES, 'UTF-8') ?></h2>
              <span class="badge text-bg-light border"><?= count($benchmarks) ?> benchmarks</span>
            </div>
            <div class="skillmap-search mb-3">
              <i class="bi bi-search"></i>
              <input class="form-control" type="search" placeholder="Search benchmark skills, category, priority, or rating" data-search-input>
            </div>
            <div class="table-responsive">
              <table class="table align-middle">
                <thead><tr><th>Skill</th><th>Category</th><th>Required Rating</th><th>Priority</th><th class="skillmap-actions-col">Actions</th></tr></thead>
                <tbody>
                  <?php foreach ($benchmarks as $benchmark): ?>
                    <tr data-search-item data-search-text="<?= htmlspecialchars($benchmark['skill_name'] . ' ' . $benchmark['category'] . ' ' . $benchmark['priority'] . ' ' . $benchmark['required_rating'] . '/5', ENT_QUOTES, 'UTF-8') ?>">
                      <td class="skillmap-actions-col">
                        <?php $editFormId = 'benchmarkEdit' . (int) $benchmark['id']; ?>
                        <form id="<?= $editFormId ?>" method="post"></form>
                        <input form="<?= $editFormId ?>" type="hidden" name="action" value="save_benchmark">
                        <input form="<?= $editFormId ?>" type="hidden" name="benchmark_id" value="<?= (int) $benchmark['id'] ?>">
                        <input form="<?= $editFormId ?>" type="hidden" name="role_id" value="<?= (int) $selectedRoleId ?>">
                        <select form="<?= $editFormId ?>" name="skill_id" class="form-select form-select-sm"><?php foreach ($skills as $skill): ?><option value="<?= (int) $skill['id'] ?>" <?= (int) $skill['id'] === (int) $benchmark['skill_id'] ? 'selected' : '' ?>><?= htmlspecialchars($skill['name'], ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select>
                      </td>
                      <td><span class="badge badge-soft rounded-pill"><?= htmlspecialchars($benchmark['category'], ENT_QUOTES, 'UTF-8') ?></span></td>
                      <td><select form="<?= $editFormId ?>" name="required_rating" class="form-select form-select-sm"><?php for ($i = 1; $i <= 5; $i++): ?><option value="<?= $i ?>" <?= (int) $benchmark['required_rating'] === $i ? 'selected' : '' ?>><?= $i ?>/5</option><?php endfor; ?></select></td>
                      <td><select form="<?= $editFormId ?>" name="priority" class="form-select form-select-sm"><?php foreach (['Critical', 'Important', 'Optional'] as $priority): ?><option value="<?= $priority ?>" <?= $benchmark['priority'] === $priority ? 'selected' : '' ?>><?= $priority ?></option><?php endforeach; ?></select></td>
                      <td>
                        <div class="d-flex gap-2">
                          <button form="<?= $editFormId ?>" class="btn btn-sm btn-outline-primary" type="submit"><i class="bi bi-check2"></i></button>
                          <form method="post">
                            <input type="hidden" name="action" value="delete_benchmark">
                            <input type="hidden" name="benchmark_id" value="<?= (int) $benchmark['id'] ?>">
                            <input type="hidden" name="role_id" value="<?= (int) $selectedRoleId ?>">
                            <button class="btn btn-sm btn-outline-danger" type="submit" onclick="return confirm('Remove this benchmark?');"><i class="bi bi-trash"></i></button>
                          </form>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                  <?php if ($benchmarks === []): ?><tr><td colspan="5" class="text-center text-muted py-4">No benchmarks mapped for this role yet.</td></tr><?php endif; ?>
                  <tr class="d-none" data-search-empty><td colspan="5" class="text-center text-muted py-4">No matching benchmarks found.</td></tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </main>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="/fyp_skillmapsystem/assets/js/app.js"></script>
</body>
</html>
