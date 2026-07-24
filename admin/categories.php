<?php
// Admin category and target-role entry management.

require_once __DIR__ . '/../includes/auth_check.php';
skillmap_require_permission('manage_skills');

$activePage = 'categories';
$message = '';
$error = '';
$editCategory = null;
$postAction = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');
    $postAction = $action;

    if ($action === 'save_category') {
        $categoryId = (int) ($_POST['category_id'] ?? 0);
        $name = trim((string) ($_POST['name'] ?? ''));
        $type = (string) ($_POST['type'] ?? 'Skill Category');
        $icon = trim((string) ($_POST['icon'] ?? 'bi-folder'));
        $type = in_array($type, ['Skill Category', 'Target Role'], true) ? $type : 'Skill Category';

        if ($name === '' || $icon === '') {
            $error = 'Category name and icon are required.';
        } else {
            try {
                if ($categoryId > 0) {
                    $stmt = $pdo->prepare('UPDATE skill_categories SET name = :name, type = :type, icon = :icon WHERE id = :id');
                    $stmt->execute(['name' => $name, 'type' => $type, 'icon' => $icon, 'id' => $categoryId]);
                    $message = 'Category updated successfully.';
                } else {
                    $stmt = $pdo->prepare('INSERT INTO skill_categories (name, type, icon) VALUES (:name, :type, :icon)');
                    $stmt->execute(['name' => $name, 'type' => $type, 'icon' => $icon]);
                    $message = 'Category created successfully.';
                }
            } catch (PDOException $exception) {
                $error = $exception->getCode() === '23000' ? 'A category or target-role entry with this name already exists.' : 'Unable to save category.';
            }
        }
    }

    if ($action === 'delete_category') {
        $categoryId = (int) ($_POST['category_id'] ?? 0);
        $dependents = 0;
        if ($categoryId > 0) {
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM skills WHERE category_id = :id');
            $stmt->execute(['id' => $categoryId]);
            $dependents = (int) $stmt->fetchColumn();
        }

        if ($dependents > 0) {
            $error = 'This category still has skills. Move or delete those skills first.';
        } elseif ($categoryId > 0) {
            $stmt = $pdo->prepare('DELETE FROM skill_categories WHERE id = :id');
            $stmt->execute(['id' => $categoryId]);
            $message = 'Category deleted successfully.';
        }
    }
}

$editId = isset($_GET['new']) ? 0 : (int) ($_GET['edit'] ?? 0);
if ($editId > 0) {
    $stmt = $pdo->prepare('SELECT id, name, type, icon FROM skill_categories WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $editId]);
    $editCategory = $stmt->fetch() ?: null;
}
$showCategoryForm = $editCategory || isset($_GET['new']) || ($error !== '' && $postAction === 'save_category');
$addCategoryHref = isset($_GET['new']) ? '/fyp_skillmapsystem/admin/categories.php' : '/fyp_skillmapsystem/admin/categories.php?new=1';

$categories = $pdo->query(
    'SELECT sc.id, sc.name, sc.type, sc.icon, DATE_FORMAT(sc.updated_at, "%e %b %Y") AS updated,
            COUNT(s.id) AS skill_count
     FROM skill_categories sc
     LEFT JOIN skills s ON s.category_id = sc.id
     GROUP BY sc.id
     ORDER BY FIELD(sc.type, "Skill Category", "Target Role"), sc.name'
)->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Skill Map - Categories</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="/fyp_skillmapsystem/assets/css/style.css" rel="stylesheet">
</head>
<body>
  <?php require __DIR__ . '/includes/navbar.php'; ?>
  <main class="container-fluid py-4 py-lg-5">
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
      <div>
        <h1 class="fw-bold mb-1">Manage Categories</h1>
        <div class="text-muted">Maintain skill categories and target-role icon entries</div>
      </div>
      <div class="d-flex flex-wrap gap-2">
        <a class="btn btn-primary" href="<?= htmlspecialchars($addCategoryHref, ENT_QUOTES, 'UTF-8') ?>">
          <i class="bi bi-plus-lg me-1"></i>Add Category
        </a>
        <a class="btn btn-outline-primary" href="/fyp_skillmapsystem/admin/benchmarks.php">Role Benchmarks</a>
      </div>
    </div>

    <?php if ($message !== ''): ?><div class="alert alert-success"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
    <?php if ($error !== ''): ?><div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>

    <div class="row g-4">
      <div class="col-lg-4 skillmap-admin-form-side">
        <form method="post" class="card <?= $showCategoryForm ? '' : 'd-none' ?>" id="categoryForm">
          <div class="card-body p-4">
            <input type="hidden" name="action" value="save_category">
            <input type="hidden" name="category_id" value="<?= (int) ($editCategory['id'] ?? 0) ?>">
            <h2 class="h5 fw-bold mb-3"><?= $editCategory ? 'Edit Category' : 'Add Category' ?></h2>
            <div class="d-grid gap-3">
              <div><label class="form-label">Name</label><input name="name" class="form-control" value="<?= htmlspecialchars((string) ($editCategory['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required></div>
              <div><label class="form-label">Type</label><select name="type" class="form-select"><option value="Skill Category" <?= ($editCategory['type'] ?? '') === 'Skill Category' ? 'selected' : '' ?>>Skill Category</option><option value="Target Role" <?= ($editCategory['type'] ?? '') === 'Target Role' ? 'selected' : '' ?>>Target Role</option></select></div>
              <div><label class="form-label">Bootstrap Icon Class</label><input name="icon" class="form-control" value="<?= htmlspecialchars((string) ($editCategory['icon'] ?? 'bi-folder'), ENT_QUOTES, 'UTF-8') ?>" required><div class="form-text">Example: bi-code-square, bi-people-fill</div></div>
            </div>
          </div>
          <div class="card-footer bg-white border-0 p-4 pt-0 d-flex gap-2">
            <button class="btn btn-primary" type="submit"><i class="bi bi-check2 me-1"></i>Save Category</button>
            <?php if ($editCategory): ?><a class="btn btn-outline-secondary" href="/fyp_skillmapsystem/admin/categories.php">Cancel</a><?php else: ?><button class="btn btn-outline-secondary" type="button" data-toggle-panel="categoryForm">Cancel</button><?php endif; ?>
          </div>
        </form>
      </div>

      <div class="col-lg-8 skillmap-admin-table-col">
        <div class="card" data-search-scope>
          <div class="card-body p-3 p-lg-4">
            <div class="skillmap-search">
              <i class="bi bi-search"></i>
              <input class="form-control" type="search" placeholder="Search categories, type, or icon" data-search-input>
            </div>
          </div>
          <div class="table-responsive">
            <table class="table align-middle mb-0">
              <thead><tr><th>#</th><th>Name</th><th>Type</th><th>Icon</th><th>Skills</th><th>Updated</th><th class="skillmap-actions-col">Actions</th></tr></thead>
              <tbody>
                <?php foreach ($categories as $index => $category): ?>
                  <tr data-search-item data-search-text="<?= htmlspecialchars($category['name'] . ' ' . $category['type'] . ' ' . $category['icon'], ENT_QUOTES, 'UTF-8') ?>">
                    <td><?= $index + 1 ?></td>
                    <td class="fw-semibold"><i class="bi <?= htmlspecialchars($category['icon'], ENT_QUOTES, 'UTF-8') ?> me-2 text-primary"></i><?= htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><span class="badge text-bg-light border"><?= htmlspecialchars($category['type'], ENT_QUOTES, 'UTF-8') ?></span></td>
                    <td><code><?= htmlspecialchars($category['icon'], ENT_QUOTES, 'UTF-8') ?></code></td>
                    <td><?= (int) $category['skill_count'] ?></td>
                    <td><?= htmlspecialchars($category['updated'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="skillmap-actions-col">
                      <div class="d-flex gap-2">
                        <?php $editCategoryHref = $editId === (int) $category['id'] ? '/fyp_skillmapsystem/admin/categories.php' : '/fyp_skillmapsystem/admin/categories.php?edit=' . (int) $category['id']; ?>
                        <a class="btn btn-sm btn-outline-primary" href="<?= htmlspecialchars($editCategoryHref, ENT_QUOTES, 'UTF-8') ?>"><i class="bi bi-pencil"></i></a>
                        <form method="post">
                          <input type="hidden" name="action" value="delete_category">
                          <input type="hidden" name="category_id" value="<?= (int) $category['id'] ?>">
                          <button class="btn btn-sm btn-outline-danger" type="submit" onclick="return confirm('Delete this category?');"><i class="bi bi-trash"></i></button>
                        </form>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
                <?php if ($categories === []): ?><tr><td colspan="7" class="text-center text-muted py-4">No categories found.</td></tr><?php endif; ?>
                <tr class="d-none" data-search-empty><td colspan="7" class="text-center text-muted py-4">No matching categories found.</td></tr>
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
