<?php
// Shared inbox and compose page for admins, lecturers, staff, and students.

require_once __DIR__ . '/includes/functions.php';
skillmap_require_auth();

$user = skillmap_current_user() ?? [];
$userId = (int) ($user['id'] ?? 0);
$userRole = (string) ($user['role'] ?? 'student');
$canSend = skillmap_user_can('send_notifications');
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['mark_read'])) {
        $notificationId = (int) ($_POST['notification_id'] ?? 0);
        if ($notificationId > 0 && skillmap_mark_notification_read($notificationId, $userId, $userRole)) {
            $message = 'Notification marked as read.';
        }
    }

    if (isset($_POST['send_notification']) && $canSend) {
        $recipientMode = (string) ($_POST['recipient_mode'] ?? 'role');
        $recipientRole = (string) ($_POST['recipient_role'] ?? 'student');
        $recipientUserId = (int) ($_POST['recipient_user_id'] ?? 0);
        $payload = [
            'sender_user_id' => $userId,
            'sender_role' => $userRole,
            'notification_type' => (string) ($_POST['notification_type'] ?? 'message'),
            'title' => (string) ($_POST['title'] ?? ''),
            'body' => (string) ($_POST['body'] ?? ''),
        ];

        if ($recipientMode === 'user' && $recipientUserId > 0) {
            $payload['recipient_user_id'] = $recipientUserId;
        } else {
            $allowedTargets = skillmap_allowed_notification_targets($userRole);
            if (in_array($recipientRole, $allowedTargets, true)) {
                $payload['recipient_role'] = $recipientRole;
            } elseif (in_array('student', $allowedTargets, true)) {
                $payload['recipient_role'] = 'student';
            } elseif ($allowedTargets !== []) {
                $payload['recipient_role'] = $allowedTargets[0];
            } else {
                $payload['recipient_role'] = 'student';
            }
        }

        if (skillmap_create_notification($payload)) {
            $message = 'Notification sent successfully.';
        } else {
            $message = 'Unable to send notification.';
        }
    }
}

$notifications = skillmap_fetch_notification_items($userId, $userRole, 20);
$unreadCount = skillmap_notification_unread_count($userId, $userRole);
$allowedTargets = skillmap_allowed_notification_targets($userRole);
$recipientUsers = [];
if ($canSend) {
    $roleFilter = $userRole === 'admin' ? ['student', 'lecturer', 'staff'] : ($userRole === 'staff' ? ['student', 'lecturer'] : ['student']);
    $placeholders = implode(',', array_fill(0, count($roleFilter), '?'));
    $recipientUsers = skillmap_fetch_all(
        'SELECT id, name, email, role FROM users WHERE role IN (' . $placeholders . ') ORDER BY role ASC, name ASC',
        str_repeat('s', count($roleFilter)),
        $roleFilter
    );
}

$navFile = in_array($userRole, ['admin', 'lecturer', 'staff'], true)
    ? __DIR__ . '/admin/includes/navbar.php'
    : __DIR__ . '/users/includes/navbar.php';
$activePage = 'notifications';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Skill Map - Notifications</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="/fyp_skillmapsystem/assets/css/style.css" rel="stylesheet">
</head>
<body>
  <?php require $navFile; ?>
  <main class="container py-4 py-lg-5">
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
      <div>
        <h1 class="fw-bold mb-1">Notifications</h1>
        <div class="text-muted">Unread: <?= $unreadCount ?> · Role: <?= htmlspecialchars(ucfirst($userRole), ENT_QUOTES, 'UTF-8') ?></div>
      </div>
      <?php if ($canSend): ?>
        <a class="btn btn-outline-primary" href="#composeBox">Compose Message</a>
      <?php endif; ?>
    </div>

    <?php if ($message !== ''): ?>
      <div class="alert alert-info"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <div class="row g-4">
      <div class="col-lg-7">
        <div class="card h-100">
          <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <h2 class="h5 fw-bold mb-0">Inbox</h2>
              <span class="badge text-bg-light border"><?= count($notifications) ?> items</span>
            </div>
            <div class="d-grid gap-3">
              <?php foreach ($notifications as $notification): ?>
                <div class="border rounded-4 p-3 <?= (int) $notification['is_read'] === 0 ? 'bg-light' : '' ?>">
                  <div class="d-flex justify-content-between align-items-start gap-3">
                    <div class="d-flex align-items-start gap-3">
                      <div class="avatar-circle bg-primary"><?= htmlspecialchars(substr($notification['sender_initials'] ?: 'SM', 0, 2), ENT_QUOTES, 'UTF-8') ?></div>
                      <div>
                        <div class="fw-semibold"><?= htmlspecialchars($notification['title'], ENT_QUOTES, 'UTF-8') ?></div>
                        <div class="small text-muted mb-2">From <?= htmlspecialchars($notification['sender_name'], ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars($notification['audience'], ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars($notification['created_at'], ENT_QUOTES, 'UTF-8') ?></div>
                        <div><?= nl2br(htmlspecialchars($notification['body'], ENT_QUOTES, 'UTF-8')) ?></div>
                      </div>
                    </div>
                    <div class="text-end">
                      <?= skillmap_status_badge($notification['notification_type']) ?>
                      <?php if ((int) $notification['is_read'] === 0): ?>
                        <form method="post" class="mt-2">
                          <input type="hidden" name="notification_id" value="<?= (int) $notification['id'] ?>">
                          <button class="btn btn-sm btn-outline-primary" type="submit" name="mark_read">Mark Read</button>
                        </form>
                      <?php else: ?>
                        <span class="badge text-bg-success-subtle text-success mt-2">Read</span>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
              <?php if ($notifications === []): ?>
                <div class="alert alert-light border mb-0">No notifications yet.</div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>

      <div class="col-lg-5 d-grid gap-4">
        <?php if ($canSend): ?>
          <div class="card" id="composeBox">
            <div class="card-body p-4">
              <h2 class="h5 fw-bold mb-3">Compose Notification</h2>
              <form method="post" class="d-grid gap-3">
                <div>
                  <label class="form-label">Recipient Mode</label>
                  <div class="btn-group w-100" role="group">
                    <input type="radio" class="btn-check" name="recipient_mode" id="recipientRole" value="role" checked>
                    <label class="btn btn-outline-primary" for="recipientRole">Role</label>
                    <input type="radio" class="btn-check" name="recipient_mode" id="recipientUser" value="user">
                    <label class="btn btn-outline-primary" for="recipientUser">User</label>
                  </div>
                </div>
                <div>
                  <label class="form-label">Target Role</label>
                  <select name="recipient_role" class="form-select">
                    <?php foreach ($allowedTargets as $target): ?>
                      <option value="<?= htmlspecialchars($target, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(ucfirst($target), ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <?php if ($recipientUsers !== []): ?>
                  <div>
                    <label class="form-label">Specific User</label>
                    <select name="recipient_user_id" class="form-select">
                      <option value="">Select a user</option>
                      <?php foreach ($recipientUsers as $recipientUser): ?>
                        <option value="<?= (int) $recipientUser['id'] ?>"><?= htmlspecialchars($recipientUser['name'], ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars(ucfirst($recipientUser['role']), ENT_QUOTES, 'UTF-8') ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                <?php endif; ?>
                <div>
                  <label class="form-label">Type</label>
                  <select name="notification_type" class="form-select">
                    <option value="message">Message</option>
                    <option value="info">Info</option>
                    <option value="alert">Alert</option>
                    <option value="reminder">Reminder</option>
                  </select>
                </div>
                <div>
                  <label class="form-label">Title</label>
                  <input type="text" name="title" class="form-control" placeholder="Title" required>
                </div>
                <div>
                  <label class="form-label">Message</label>
                  <textarea name="body" class="form-control" rows="5" placeholder="Write the notification message" required></textarea>
                </div>
                <button class="btn btn-primary" type="submit" name="send_notification">Send Notification</button>
              </form>
            </div>
          </div>
        <?php endif; ?>

        <div class="card">
          <div class="card-body p-4">
            <h2 class="h5 fw-bold mb-3">Quick Summary</h2>
            <div class="d-grid gap-3">
              <div class="d-flex justify-content-between"><span>Unread</span><strong><?= $unreadCount ?></strong></div>
              <div class="d-flex justify-content-between"><span>Total shown</span><strong><?= count($notifications) ?></strong></div>
              <?php if (($userRole === 'student')): ?>
                <a class="btn btn-outline-primary" href="/fyp_skillmapsystem/users/profile.php">Update Skill Profile</a>
              <?php else: ?>
                <a class="btn btn-outline-primary" href="/fyp_skillmapsystem/admin/reviews.php">Review Students</a>
              <?php endif; ?>
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
