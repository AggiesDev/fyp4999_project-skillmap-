<?php
// Student account profile and credential manager.

require_once __DIR__ . '/../includes/auth_check.php';
skillmap_require_auth(['student']);

$activePage = 'profile';
$user = skillmap_current_user();
$userId = (int) ($user['id'] ?? 0);
$message = '';
$error = '';

function profile_fetch_credentials(PDO $pdo, int $userId): array
{
    $stmt = $pdo->prepare(
        'SELECT id, entry_type, title, issuer, notes, earned_at
         FROM user_credentials
         WHERE user_id = :user_id
         ORDER BY created_at DESC'
    );
    $stmt->execute(['user_id' => $userId]);
    return $stmt->fetchAll();
}

function profile_fetch_user(PDO $pdo, int $userId): ?array
{
    $stmt = $pdo->prepare(
        'SELECT id, name, username, email, role, programme, year_level, avatar_initials, gender, profile_icon, status
         FROM users
         WHERE id = :id
         LIMIT 1'
    );
    $stmt->execute(['id' => $userId]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function profile_fetch_credential(PDO $pdo, int $userId, int $credentialId): ?array
{
    if ($credentialId <= 0) {
        return null;
    }

    $stmt = $pdo->prepare(
        'SELECT id, entry_type, title, issuer, notes, earned_at
         FROM user_credentials
         WHERE id = :id AND user_id = :user_id
         LIMIT 1'
    );
    $stmt->execute(['id' => $credentialId, 'user_id' => $userId]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function profile_render_icon_choices(string $selectedIcon): void
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

    if ($action === 'update_profile') {
        $name = trim((string) ($_POST['name'] ?? ''));
        $programme = trim((string) ($_POST['programme'] ?? ''));
        $yearLevel = trim((string) ($_POST['year_level'] ?? ''));
        $gender = skillmap_normalize_gender((string) ($_POST['gender'] ?? 'male'));
        $profileIcon = skillmap_sanitize_profile_icon((string) ($_POST['profile_icon'] ?? ''), $gender, (string) ($user['role'] ?? 'student'));

        if ($name === '' || $programme === '' || $yearLevel === '') {
            $error = 'Name, programme, and year level are required.';
        } else {
            $initials = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $name) ?: 'SM', 0, 2));
            $stmt = $pdo->prepare(
                'UPDATE users
                 SET name = :name, programme = :programme, year_level = :year_level,
                     avatar_initials = :avatar_initials, gender = :gender, profile_icon = :profile_icon
                 WHERE id = :id'
            );
            $stmt->execute([
                'name' => $name,
                'programme' => $programme,
                'year_level' => $yearLevel,
                'avatar_initials' => $initials,
                'gender' => $gender,
                'profile_icon' => $profileIcon,
                'id' => $userId,
            ]);

            $freshUser = profile_fetch_user($pdo, $userId);
            if ($freshUser) {
                set_authenticated_user($freshUser);
                $user = skillmap_current_user();
            }

            $message = 'Profile updated successfully.';
        }
    }

    if ($action === 'save_credential') {
        $credentialId = (int) ($_POST['credential_id'] ?? 0);
        $entryType = (string) ($_POST['entry_type'] ?? 'Skill');
        $entryType = in_array($entryType, ['Skill', 'Certification'], true) ? $entryType : 'Skill';
        $title = trim((string) ($_POST['title'] ?? ''));
        $issuer = trim((string) ($_POST['issuer'] ?? ''));
        $notes = trim((string) ($_POST['notes'] ?? ''));
        $earnedAt = trim((string) ($_POST['earned_at'] ?? ''));
        $earnedAt = $earnedAt !== '' ? $earnedAt : null;

        if ($title === '') {
            $error = 'Credential title is required.';
        } elseif ($credentialId > 0) {
            $stmt = $pdo->prepare(
                'UPDATE user_credentials
                 SET entry_type = :entry_type, title = :title, issuer = :issuer, notes = :notes, earned_at = :earned_at
                 WHERE id = :id AND user_id = :user_id'
            );
            $stmt->execute([
                'entry_type' => $entryType,
                'title' => $title,
                'issuer' => $issuer !== '' ? $issuer : null,
                'notes' => $notes !== '' ? $notes : null,
                'earned_at' => $earnedAt,
                'id' => $credentialId,
                'user_id' => $userId,
            ]);
            $message = 'Credential updated successfully.';
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO user_credentials (user_id, entry_type, title, issuer, notes, earned_at)
                 VALUES (:user_id, :entry_type, :title, :issuer, :notes, :earned_at)'
            );
            $stmt->execute([
                'user_id' => $userId,
                'entry_type' => $entryType,
                'title' => $title,
                'issuer' => $issuer !== '' ? $issuer : null,
                'notes' => $notes !== '' ? $notes : null,
                'earned_at' => $earnedAt,
            ]);
            $message = 'Credential added successfully.';
        }
    }

    if ($action === 'delete_credential') {
        $credentialId = (int) ($_POST['credential_id'] ?? 0);
        if ($credentialId > 0) {
            $stmt = $pdo->prepare('DELETE FROM user_credentials WHERE id = :id AND user_id = :user_id');
            $stmt->execute(['id' => $credentialId, 'user_id' => $userId]);
            $message = 'Credential deleted successfully.';
        }
    }
}

$profile = profile_fetch_user($pdo, $userId);
if (!$profile) {
    header('Location: /fyp_skillmapsystem/logout.php');
    exit;
}

$selectedIcon = skillmap_sanitize_profile_icon((string) ($profile['profile_icon'] ?? ''), (string) ($profile['gender'] ?? 'male'), (string) $profile['role']);
$credentials = profile_fetch_credentials($pdo, $userId);
$editCredential = profile_fetch_credential($pdo, $userId, (int) ($_GET['edit_credential'] ?? 0));
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Skill Map - My Profile</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="/fyp_skillmapsystem/assets/css/style.css" rel="stylesheet">
</head>
<body>
  <?php require __DIR__ . '/includes/navbar.php'; ?>
  <main class="container py-4 py-lg-5">
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
      <div>
        <h1 class="fw-bold mb-1">My Profile</h1>
        <div class="text-muted">Manage your profile details separately from your skills and certifications.</div>
      </div>
      <a class="btn btn-outline-primary" href="/fyp_skillmapsystem/users/skills_assessment.php">
        <i class="bi bi-stars me-1"></i>Skill Assessment
      </a>
    </div>

    <?php if ($message !== ''): ?>
      <div class="alert alert-success"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <div class="row g-4 align-items-start">
      <div class="col-lg-5">
        <form method="post" class="card">
          <div class="card-body p-4">
            <input type="hidden" name="action" value="update_profile">
            <div class="d-flex align-items-center gap-3 mb-4">
              <div class="text-center">
                <img class="profile-icon-preview" src="/fyp_skillmapsystem/<?= htmlspecialchars($selectedIcon, ENT_QUOTES, 'UTF-8') ?>" alt="">
                <button class="btn btn-sm btn-outline-primary mt-2" type="button" data-toggle-panel="profileIconPanel">
                  <i class="bi bi-images me-1"></i>Change User Icon
                </button>
              </div>
              <div>
                <h2 class="h5 fw-bold mb-1">Profile Details</h2>
                <div class="text-muted small"><?= htmlspecialchars($profile['email'], ENT_QUOTES, 'UTF-8') ?></div>
              </div>
            </div>

            <div class="d-grid gap-3">
              <div>
                <label class="form-label">Full Name</label>
                <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($profile['name'], ENT_QUOTES, 'UTF-8') ?>" required>
              </div>
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label">Gender</label>
                  <select name="gender" class="form-select" data-profile-gender>
                    <option value="male" <?= $profile['gender'] === 'male' ? 'selected' : '' ?>>Male</option>
                    <option value="female" <?= $profile['gender'] === 'female' ? 'selected' : '' ?>>Female</option>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Role</label>
                  <input type="text" class="form-control text-capitalize" value="<?= htmlspecialchars($profile['role'], ENT_QUOTES, 'UTF-8') ?>" readonly>
                </div>
              </div>
              <div id="profileIconPanel" class="d-none">
                <label class="form-label">Profile Icon</label>
                <?php profile_render_icon_choices($selectedIcon); ?>
              </div>
              <div>
                <label class="form-label">Programme</label>
                <input type="text" name="programme" class="form-control" value="<?= htmlspecialchars($profile['programme'], ENT_QUOTES, 'UTF-8') ?>" required>
              </div>
              <div>
                <label class="form-label">Year Level</label>
                <input type="text" name="year_level" class="form-control" value="<?= htmlspecialchars($profile['year_level'], ENT_QUOTES, 'UTF-8') ?>" required>
              </div>
              <div>
                <label class="form-label">Username</label>
                <input type="text" class="form-control" value="<?= htmlspecialchars($profile['username'], ENT_QUOTES, 'UTF-8') ?>" readonly>
              </div>
            </div>
          </div>
          <div class="card-footer bg-white border-0 p-4 pt-0">
            <button class="btn btn-primary w-100" type="submit">
              <i class="bi bi-check2 me-1"></i>Save Profile
            </button>
          </div>
        </form>
      </div>

      <div class="col-lg-7">
        <div class="card mb-4">
          <div class="card-body p-4">
            <h2 class="h5 fw-bold mb-3"><?= $editCredential ? 'Edit Skill or Certification' : 'Add Skill or Certification' ?></h2>
            <form method="post" class="row g-3">
              <input type="hidden" name="action" value="save_credential">
              <input type="hidden" name="credential_id" value="<?= (int) ($editCredential['id'] ?? 0) ?>">
              <div class="col-md-4">
                <label class="form-label">Type</label>
                <?php $entryType = (string) ($editCredential['entry_type'] ?? 'Skill'); ?>
                <select name="entry_type" class="form-select">
                  <option value="Skill" <?= $entryType === 'Skill' ? 'selected' : '' ?>>Skill</option>
                  <option value="Certification" <?= $entryType === 'Certification' ? 'selected' : '' ?>>Certification</option>
                </select>
              </div>
              <div class="col-md-8">
                <label class="form-label">Title</label>
                <input type="text" name="title" class="form-control" value="<?= htmlspecialchars((string) ($editCredential['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
              </div>
              <div class="col-md-6">
                <label class="form-label">Issuer</label>
                <input type="text" name="issuer" class="form-control" value="<?= htmlspecialchars((string) ($editCredential['issuer'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
              </div>
              <div class="col-md-6">
                <label class="form-label">Earned Date</label>
                <input type="date" name="earned_at" class="form-control" value="<?= htmlspecialchars((string) ($editCredential['earned_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
              </div>
              <div class="col-12">
                <label class="form-label">Notes</label>
                <textarea name="notes" class="form-control" rows="3"><?= htmlspecialchars((string) ($editCredential['notes'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
              </div>
              <div class="col-12 d-flex flex-wrap gap-2">
                <button class="btn btn-success" type="submit">
                  <i class="bi <?= $editCredential ? 'bi-check2' : 'bi-plus-lg' ?> me-1"></i><?= $editCredential ? 'Update Credential' : 'Add Credential' ?>
                </button>
                <?php if ($editCredential): ?>
                  <a class="btn btn-outline-secondary" href="/fyp_skillmapsystem/users/profile.php">Cancel Edit</a>
                <?php endif; ?>
              </div>
            </form>
          </div>
        </div>

        <div class="card">
          <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <h2 class="h5 fw-bold mb-0">Credentials</h2>
              <span class="badge text-bg-light border"><?= count($credentials) ?> total</span>
            </div>

            <?php if ($credentials === []): ?>
              <div class="alert alert-light border mb-0">No skills or certifications added yet.</div>
            <?php else: ?>
              <div class="table-responsive">
                <table class="table align-middle mb-0">
                  <thead><tr><th>Type</th><th>Title</th><th>Issuer</th><th>Earned</th><th class="text-end">Actions</th></tr></thead>
                  <tbody>
                    <?php foreach ($credentials as $credential): ?>
                      <tr>
                        <td><span class="badge text-bg-light border"><?= htmlspecialchars($credential['entry_type'], ENT_QUOTES, 'UTF-8') ?></span></td>
                        <td>
                          <div class="fw-semibold"><?= htmlspecialchars($credential['title'], ENT_QUOTES, 'UTF-8') ?></div>
                          <?php if ((string) $credential['notes'] !== ''): ?>
                            <div class="small text-muted"><?= htmlspecialchars((string) $credential['notes'], ENT_QUOTES, 'UTF-8') ?></div>
                          <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars((string) ($credential['issuer'] ?: '-'), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) ($credential['earned_at'] ?: '-'), ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                          <div class="d-flex justify-content-end gap-2">
                            <a class="btn btn-sm btn-outline-primary" href="/fyp_skillmapsystem/users/profile.php?edit_credential=<?= (int) $credential['id'] ?>" title="Edit">
                              <i class="bi bi-pencil"></i>
                            </a>
                            <form method="post">
                              <input type="hidden" name="action" value="delete_credential">
                              <input type="hidden" name="credential_id" value="<?= (int) $credential['id'] ?>">
                              <button class="btn btn-sm btn-outline-danger" type="submit" title="Delete" onclick="return confirm('Delete this credential?');">
                                <i class="bi bi-trash"></i>
                              </button>
                            </form>
                          </div>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            <?php endif; ?>
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
