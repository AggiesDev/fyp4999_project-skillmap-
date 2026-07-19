<?php
// Student notification inbox.

require_once __DIR__ . '/../includes/auth_check.php';
skillmap_require_auth(['student']);

$activePage = 'notifications';
$user = skillmap_current_user();
$userId = (int) ($user['id'] ?? 0);
$role = (string) ($user['role'] ?? 'student');
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['notification_action'] ?? '');

    if ($action === 'read_one') {
        $notificationId = (int) ($_POST['notification_id'] ?? 0);
        if ($notificationId > 0 && skillmap_mark_notification_read($notificationId, $userId, $role)) {
            $message = 'Notification marked as read.';
        }
    } elseif ($action === 'read_all') {
        $items = skillmap_fetch_notification_items($userId, $role, 100);
        foreach ($items as $item) {
            skillmap_mark_notification_read((int) $item['id'], $userId, $role);
        }
        $message = 'All notifications marked as read.';
    }
}

$notifications = skillmap_fetch_notification_items($userId, $role, 50);
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
  <?php require __DIR__ . '/includes/navbar.php'; ?>
  <main class="container py-4 py-lg-5">
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
      <div>
        <h1 class="fw-bold mb-1">Notifications</h1>
        <div class="text-muted">Messages and reminders from admins, lecturers, and the system.</div>
      </div>
      <?php if ($notifications !== []): ?>
        <form method="post">
          <input type="hidden" name="notification_action" value="read_all">
          <button class="btn btn-outline-primary" type="submit"><i class="bi bi-check2-all me-1"></i>Mark All Read</button>
        </form>
      <?php endif; ?>
    </div>

    <?php if ($message !== ''): ?>
      <div class="alert alert-success"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <div class="card">
      <div class="card-body p-4">
        <?php if ($notifications === []): ?>
          <div class="text-center text-muted py-5">
            <i class="bi bi-bell fs-1 d-block mb-3"></i>
            No notifications yet.
          </div>
        <?php else: ?>
          <div class="d-grid gap-3">
            <?php foreach ($notifications as $notification): ?>
              <?php $isRead = (int) $notification['is_read'] === 1; ?>
              <div class="border rounded-4 p-3 <?= $isRead ? 'bg-white' : 'bg-soft-blue' ?>">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                  <div class="d-flex gap-3">
                    <span class="skillmap-stats-icon flex-shrink-0">
                      <i class="bi <?= $notification['notification_type'] === 'alert' ? 'bi-exclamation-triangle' : ($notification['notification_type'] === 'reminder' ? 'bi-clock' : 'bi-chat-left-text') ?>"></i>
                    </span>
                    <div>
                      <div class="d-flex flex-wrap align-items-center gap-2">
                        <h2 class="h6 fw-bold mb-0"><?= htmlspecialchars((string) $notification['title'], ENT_QUOTES, 'UTF-8') ?></h2>
                        <?php if (!$isRead): ?><span class="badge text-bg-primary">Unread</span><?php endif; ?>
                      </div>
                      <div class="small text-muted mt-1">
                        From <?= htmlspecialchars((string) $notification['sender_name'], ENT_QUOTES, 'UTF-8') ?> -
                        <?= htmlspecialchars(date('j M Y, g:i A', strtotime((string) $notification['created_at'])), ENT_QUOTES, 'UTF-8') ?>
                      </div>
                      <p class="mb-0 mt-2 text-muted"><?= nl2br(htmlspecialchars((string) $notification['body'], ENT_QUOTES, 'UTF-8')) ?></p>
                    </div>
                  </div>
                  <?php if (!$isRead): ?>
                    <form method="post">
                      <input type="hidden" name="notification_action" value="read_one">
                      <input type="hidden" name="notification_id" value="<?= (int) $notification['id'] ?>">
                      <button class="btn btn-sm btn-outline-primary" type="submit">Mark Read</button>
                    </form>
                  <?php endif; ?>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </main>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="/fyp_skillmapsystem/assets/js/app.js"></script>
</body>
</html>
