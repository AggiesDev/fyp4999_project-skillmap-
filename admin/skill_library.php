<?php
// Admin skill library CRUD.

require_once __DIR__ . '/../includes/auth_check.php';
skillmap_require_permission('manage_skills');

$activePage = 'skill_library';
$message = '';
$error = '';
$editSkill = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'save_skill') {
        $skillId = (int) ($_POST['skill_id'] ?? 0);
        $categoryId = (int) ($_POST['category_id'] ?? 0);
        $name = trim((string) ($_POST['name'] ?? ''));
        $description = trim((string) ($_POST['description'] ?? ''));
        $difficulty = max(1, min(5, (int) ($_POST['difficulty'] ?? 1)));
        $status = (string) ($_POST['status'] ?? 'Active');
        $status = in_array($status, ['Active', 'Inactive'], true) ? $status : 'Active';

        $categoryCheck = $pdo->prepare('SELECT id FROM skill_categories WHERE id = :id AND type = "Skill Category" LIMIT 1');
        $categoryCheck->execute(['id' => $categoryId]);

        if ($name === '' || $description === '') {
            $error = 'Skill name and description are required.';
        } elseif (!$categoryCheck->fetch()) {
            $error = 'Please choose a valid skill category.';
        } else {
            try {
                if ($skillId > 0) {
                    $stmt = $pdo->prepare(
                        'UPDATE skills
                         SET category_id = :category_id, name = :name, description = :description, difficulty = :difficulty, status = :status
                         WHERE id = :id'
                    );
                    $stmt->execute([
                        'category_id' => $categoryId,
                        'name' => $name,
                        'description' => $description,
                        'difficulty' => $difficulty,
                        'status' => $status,
                        'id' => $skillId,
                    ]);
                    $message = 'Skill updated successfully.';
                } else {
                    $stmt = $pdo->prepare(
                        'INSERT INTO skills (category_id, name, description, difficulty, status)
                         VALUES (:category_id, :name, :description, :difficulty, :status)'
                    );
                    $stmt->execute([
                        'category_id' => $categoryId,
                        'name' => $name,
                        'description' => $description,
                        'difficulty' => $difficulty,
                        'status' => $status,
                    ]);
                    $message = 'Skill created successfully.';
                }
            } catch (PDOException $exception) {
                $error = $exception->getCode() === '23000' ? 'A skill with this name already exists.' : 'Unable to save skill.';
            }
        }
    }

    if ($action === 'delete_skill') {
        $skillId = (int) ($_POST['skill_id'] ?? 0);
        $dependencySql = [
            'user_skill_ratings' => 'SELECT COUNT(*) FROM user_skill_ratings WHERE skill_id = :id',
            'role_skill_benchmarks' => 'SELECT COUNT(*) FROM role_skill_benchmarks WHERE skill_id = :id',
            'analysis_results' => 'SELECT COUNT(*) FROM analysis_results WHERE skill_id = :id',
            'learning_resources' => 'SELECT COUNT(*) FROM learning_resources WHERE skill_id = :id',
            'user_roadmap_progress' => 'SELECT COUNT(*) FROM user_roadmap_progress WHERE skill_id = :id',
        ];
        $hasDependents = false;
        foreach ($dependencySql as $sql) {
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['id' => $skillId]);
            if ((int) $stmt->fetchColumn() > 0) {
                $hasDependents = true;
                break;
            }
        }

        if ($hasDependents) {
            $error = 'This skill is already referenced. Set it inactive instead of deleting it.';
        } elseif ($skillId > 0) {
            $stmt = $pdo->prepare('DELETE FROM skills WHERE id = :id');
            $stmt->execute(['id' => $skillId]);
            $message = 'Skill deleted successfully.';
        }
    }
}

$editId = (int) ($_GET['edit'] ?? 0);
if ($editId > 0) {
    $stmt = $pdo->prepare('SELECT id, category_id, name, description, difficulty, status FROM skills WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $editId]);
    $editSkill = $stmt->fetch() ?: null;
}

$categories = $pdo->query('SELECT id, name, icon FROM skill_categories WHERE type = "Skill Category" ORDER BY name')->fetchAll();
$skills = $pdo->query(
    'SELECT s.id, s.name, s.description, s.difficulty, s.status, c.name AS category, c.icon,
            COUNT(DISTINCT rb.role_id) AS role_count,
            COUNT(DISTINCT r.user_id) AS rating_count
     FROM skills s
     INNER JOIN skill_categories c ON c.id = s.category_id
     LEFT JOIN role_skill_benchmarks rb ON rb.skill_id = s.id
     LEFT JOIN user_skill_ratings r ON r.skill_id = s.id
     GROUP BY s.id
     ORDER BY c.name, s.name'
)->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Skill Map - Skill Library</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="/fyp_skillmapsystem/assets/css/style.css" rel="stylesheet">
</head>
<body>
  <?php require __DIR__ . '/includes/navbar.php'; ?>
  <main class="container-fluid py-4 py-lg-5">
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
      <div>
        <h1 class="fw-bold mb-1">Skill Library</h1>
        <div class="text-muted">Create and maintain the skills used by assessments and benchmarks</div>
      </div>
      <div class="d-flex flex-wrap gap-2">
        <button class="btn btn-primary" type="button" data-toggle-panel="skillLibraryForm">
          <i class="bi bi-plus-lg me-1"></i>Add Skill
        </button>
        <a class="btn btn-outline-primary" href="/fyp_skillmapsystem/admin/categories.php">Manage Categories</a>
      </div>
    </div>

    <?php if ($message !== ''): ?><div class="alert alert-success"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
    <?php if ($error !== ''): ?><div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>

    <div class="row g-4">
      <div class="col-xl-4 skillmap-admin-form-side">
        <form method="post" class="card <?= $editSkill || $error !== '' ? '' : 'd-none' ?>" id="skillLibraryForm">
          <div class="card-body p-4">
            <input type="hidden" name="action" value="save_skill">
            <input type="hidden" name="skill_id" value="<?= (int) ($editSkill['id'] ?? 0) ?>">
            <h2 class="h5 fw-bold mb-3"><?= $editSkill ? 'Edit Skill' : 'Add Skill' ?></h2>
            <div class="d-grid gap-3">
              <div><label class="form-label">Skill Name</label><input name="name" class="form-control" value="<?= htmlspecialchars((string) ($editSkill['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required></div>
              <div><label class="form-label">Category</label><select name="category_id" class="form-select" required><?php foreach ($categories as $category): ?><option value="<?= (int) $category['id'] ?>" <?= (int) ($editSkill['category_id'] ?? 0) === (int) $category['id'] ? 'selected' : '' ?>><?= htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select></div>
              <div><label class="form-label">Description</label><textarea name="description" class="form-control" rows="4" required><?= htmlspecialchars((string) ($editSkill['description'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea></div>
              <div class="row g-3">
                <div class="col-md-6"><label class="form-label">Difficulty</label><select name="difficulty" class="form-select"><?php for ($i = 1; $i <= 5; $i++): ?><option value="<?= $i ?>" <?= (int) ($editSkill['difficulty'] ?? 1) === $i ? 'selected' : '' ?>><?= $i ?>/5</option><?php endfor; ?></select></div>
                <div class="col-md-6"><label class="form-label">Status</label><select name="status" class="form-select"><option value="Active" <?= ($editSkill['status'] ?? 'Active') === 'Active' ? 'selected' : '' ?>>Active</option><option value="Inactive" <?= ($editSkill['status'] ?? '') === 'Inactive' ? 'selected' : '' ?>>Inactive</option></select></div>
              </div>
            </div>
          </div>
          <div class="card-footer bg-white border-0 p-4 pt-0 d-flex gap-2">
            <button class="btn btn-primary" type="submit"><i class="bi bi-check2 me-1"></i>Save Skill</button>
            <?php if ($editSkill): ?><a class="btn btn-outline-secondary" href="/fyp_skillmapsystem/admin/skill_library.php">Cancel</a><?php else: ?><button class="btn btn-outline-secondary" type="button" data-toggle-panel="skillLibraryForm">Cancel</button><?php endif; ?>
          </div>
        </form>
      </div>

      <div class="col-xl-8 skillmap-admin-table-col">
        <div class="card" data-search-scope>
          <div class="card-body p-3 p-lg-4">
            <div class="skillmap-search">
              <i class="bi bi-search"></i>
              <input class="form-control" type="search" placeholder="Search skills, category, status, or description" data-search-input>
            </div>
          </div>
          <div class="table-responsive">
            <table class="table align-middle mb-0" id="skillLibraryTable">
              <thead><tr><th>#</th><th>Skill</th><th>Category</th><th>Difficulty</th><th>Usage</th><th>Status</th><th class="skillmap-actions-col">Actions</th></tr></thead>
              <tbody>
                <?php foreach ($skills as $index => $skill): ?>
                  <tr data-filter-value="<?= htmlspecialchars(strtolower($skill['category']), ENT_QUOTES, 'UTF-8') ?>" data-search-item data-search-text="<?= htmlspecialchars($skill['name'] . ' ' . $skill['category'] . ' ' . $skill['status'] . ' ' . $skill['description'], ENT_QUOTES, 'UTF-8') ?>">
                    <td><?= $index + 1 ?></td>
                    <td><div class="fw-semibold"><?= htmlspecialchars($skill['name'], ENT_QUOTES, 'UTF-8') ?></div><div class="small text-muted"><?= htmlspecialchars($skill['description'], ENT_QUOTES, 'UTF-8') ?></div></td>
                    <td><span class="badge badge-soft rounded-pill"><i class="bi <?= htmlspecialchars($skill['icon'], ENT_QUOTES, 'UTF-8') ?> me-1"></i><?= htmlspecialchars($skill['category'], ENT_QUOTES, 'UTF-8') ?></span></td>
                    <td><?= str_repeat('<i class="bi bi-star-fill text-warning"></i>', (int) $skill['difficulty']) ?></td>
                    <td><span class="badge text-bg-light border"><?= (int) $skill['role_count'] ?> roles</span> <span class="badge text-bg-light border"><?= (int) $skill['rating_count'] ?> ratings</span></td>
                    <td><?= skillmap_status_badge($skill['status']) ?></td>
                    <td class="skillmap-actions-col">
                      <div class="d-flex gap-2">
                        <a class="btn btn-sm btn-outline-primary" href="/fyp_skillmapsystem/admin/skill_library.php?edit=<?= (int) $skill['id'] ?>"><i class="bi bi-pencil"></i></a>
                        <form method="post">
                          <input type="hidden" name="action" value="delete_skill">
                          <input type="hidden" name="skill_id" value="<?= (int) $skill['id'] ?>">
                          <button class="btn btn-sm btn-outline-danger" type="submit" onclick="return confirm('Delete this skill?');"><i class="bi bi-trash"></i></button>
                        </form>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
                <?php if ($skills === []): ?><tr><td colspan="7" class="text-center text-muted py-4">No skills found.</td></tr><?php endif; ?>
                <tr class="d-none" data-search-empty><td colspan="7" class="text-center text-muted py-4">No matching skills found.</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
    <?php require __DIR__ . '/../includes/footer.php'; ?>
  </main>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="/fyp_skillmapsystem/assets/js/app.js"></script>
</body>
</html>
