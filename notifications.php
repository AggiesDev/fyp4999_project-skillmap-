<?php
// Shared inbox and compose page for admins, lecturers, staff, and students.

require_once __DIR__ . '/includes/functions.php';
skillmap_require_auth();

$user = skillmap_current_user() ?? [];
$userId = (int) ($user['id'] ?? 0);
$userRole = (string) ($user['role'] ?? 'student');
$canSend = skillmap_user_can('send_notifications');
$canSendAdminFeedback = $userRole !== 'admin';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['mark_read'])) {
        $notificationId = (int) ($_POST['notification_id'] ?? 0);
        if ($notificationId > 0 && skillmap_mark_notification_read($notificationId, $userId, $userRole)) {
            $message = 'Notification marked as read.';
        }
    }

    if (isset($_POST['send_admin_feedback']) && $canSendAdminFeedback) {
        $feedbackPayload = [
            'sender_user_id' => $userId,
            'sender_role' => $userRole,
            'recipient_role' => 'admin',
            'notification_type' => 'message',
            'title' => (string) ($_POST['feedback_title'] ?? ''),
            'body' => (string) ($_POST['feedback_body'] ?? ''),
        ];

        $message = skillmap_create_notification($feedbackPayload)
            ? 'Message sent to admin successfully.'
            : 'Please write a title and message before sending.';
    }

    if (isset($_POST['send_notification']) && $canSend) {
        $recipientMode = (string) ($_POST['recipient_mode'] ?? 'role');
        $recipientRole = (string) ($_POST['recipient_role'] ?? 'student');
        $recipientUserId = (int) ($_POST['recipient_user_id'] ?? 0);
        $allowedTargets = skillmap_allowed_notification_targets($userRole);
        $payload = [
            'sender_user_id' => $userId,
            'sender_role' => $userRole,
            'notification_type' => (string) ($_POST['notification_type'] ?? 'message'),
            'title' => (string) ($_POST['title'] ?? ''),
            'body' => (string) ($_POST['body'] ?? ''),
        ];

        if ($recipientMode === 'user') {
            if ($recipientUserId > 0) {
                $recipientUser = skillmap_fetch_one(
                    'SELECT id, role FROM users WHERE id = ? LIMIT 1',
                    'i',
                    [$recipientUserId]
                );

                if (
                    $recipientUser !== null
                    && (in_array('all', $allowedTargets, true) || in_array((string) $recipientUser['role'], $allowedTargets, true))
                ) {
                    $payload['recipient_user_id'] = $recipientUserId;
                    $payload['recipient_role'] = (string) $recipientUser['role'];
                }
            }
        } else {
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
        } elseif ($recipientMode === 'user') {
            $message = 'Please select a valid specific user.';
        } else {
            $message = 'Unable to send notification.';
        }
    }
}

$notifications = skillmap_fetch_notification_items($userId, $userRole, 20);
$sentNotifications = $canSend ? skillmap_fetch_sent_notification_items($userId, 20) : [];
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
$isAdminNotificationPanel = in_array($userRole, ['admin', 'lecturer', 'staff'], true);
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
      <?php if ($canSend || $canSendAdminFeedback): ?>
        <div class="d-flex flex-wrap gap-2">
          <?php if ($canSendAdminFeedback): ?>
            <button class="btn btn-outline-primary" type="button" data-toggle-panel="adminFeedbackBox">Send Message to Admin</button>
          <?php endif; ?>
          <?php if ($canSend): ?>
            <button class="btn btn-primary" type="button" data-toggle-panel="composeBox">Sent Notification</button>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </div>

    <?php if ($message !== ''): ?>
      <div class="alert alert-info"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <div class="row g-4 <?= $isAdminNotificationPanel ? 'skillmap-admin-notification-layout' : '' ?>">
      <div class="col-lg-7 d-grid gap-4">
        <?php if ($canSendAdminFeedback): ?>
          <div class="card d-none skillmap-notification-feedback" id="adminFeedbackBox">
            <div class="card-body p-4">
              <h2 class="h5 fw-bold mb-3">Send Message to Admin</h2>
              <form method="post" class="d-grid gap-3">
                <div>
                  <label class="form-label">Title</label>
                  <input type="text" name="feedback_title" class="form-control" placeholder="System feedback title" required>
                </div>
                <div>
                  <label class="form-label">Message</label>
                  <textarea name="feedback_body" class="form-control" rows="4" placeholder="Write your feedback or issue for admin" required></textarea>
                </div>
                <button class="btn btn-primary" type="submit" name="send_admin_feedback">Send to Admin</button>
              </form>
            </div>
          </div>
        <?php endif; ?>

        <div class="card skillmap-notification-inbox">
          <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <h2 class="h5 fw-bold mb-0">Inbox</h2>
              <span class="badge text-bg-light border"><?= count($notifications) ?> items</span>
            </div>
            <div class="d-grid gap-3 skillmap-notification-list" data-paginated-list="5">
              <?php foreach ($notifications as $notification): ?>
                <?php
                  $notificationId = (int) $notification['id'];
                  $isUnread = (int) $notification['is_read'] === 0;
                  $modalId = 'notificationModal' . $notificationId;
                  $bodyText = trim((string) $notification['body']);
                  $preview = strlen($bodyText) > 140 ? substr($bodyText, 0, 140) . '...' : $bodyText;
                ?>
                <div class="border rounded-4 p-3 <?= $isUnread ? 'bg-light' : '' ?>" data-list-item>
                  <div class="d-flex justify-content-between align-items-start gap-3">
                    <div class="d-flex align-items-start gap-3">
                      <div class="avatar-circle bg-primary"><?= htmlspecialchars(substr($notification['sender_initials'] ?: 'SM', 0, 2), ENT_QUOTES, 'UTF-8') ?></div>
                      <div>
                        <div class="fw-semibold"><?= htmlspecialchars($notification['title'], ENT_QUOTES, 'UTF-8') ?></div>
                        <div class="small text-muted mb-2">From <?= htmlspecialchars($notification['sender_name'], ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars($notification['audience'], ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars($notification['created_at'], ENT_QUOTES, 'UTF-8') ?></div>
                        <div class="text-muted"><?= nl2br(htmlspecialchars($preview, ENT_QUOTES, 'UTF-8')) ?></div>
                        <button class="btn btn-sm btn-link px-0 mt-2" type="button" data-bs-toggle="modal" data-bs-target="#<?= htmlspecialchars($modalId, ENT_QUOTES, 'UTF-8') ?>">Open message</button>
                      </div>
                    </div>
                    <div class="text-end">
                      <?= skillmap_status_badge($notification['notification_type']) ?>
                      <?php if ($isUnread): ?>
                        <form method="post" class="mt-2">
                          <input type="hidden" name="notification_id" value="<?= $notificationId ?>">
                          <button class="btn btn-sm btn-outline-primary" type="submit" name="mark_read">Mark Read</button>
                        </form>
                      <?php else: ?>
                        <span class="badge text-bg-success-subtle text-success mt-2">Read</span>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>
                <div class="modal fade" id="<?= htmlspecialchars($modalId, ENT_QUOTES, 'UTF-8') ?>" tabindex="-1" aria-labelledby="<?= htmlspecialchars($modalId, ENT_QUOTES, 'UTF-8') ?>Title" aria-hidden="true">
                  <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content">
                      <div class="modal-header">
                        <div>
                          <h2 class="modal-title fs-5" id="<?= htmlspecialchars($modalId, ENT_QUOTES, 'UTF-8') ?>Title"><?= htmlspecialchars($notification['title'], ENT_QUOTES, 'UTF-8') ?></h2>
                          <div class="small text-muted">From <?= htmlspecialchars($notification['sender_name'], ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars($notification['created_at'], ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                      </div>
                      <div class="modal-body">
                        <div class="mb-3"><?= skillmap_status_badge($notification['notification_type']) ?> <span class="badge text-bg-light border"><?= htmlspecialchars($notification['audience'], ENT_QUOTES, 'UTF-8') ?></span></div>
                        <div class="lh-lg"><?= nl2br(htmlspecialchars($notification['body'], ENT_QUOTES, 'UTF-8')) ?></div>
                      </div>
                      <div class="modal-footer">
                        <?php if ($isUnread): ?>
                          <form method="post" class="me-auto">
                            <input type="hidden" name="notification_id" value="<?= $notificationId ?>">
                            <button class="btn btn-outline-primary" type="submit" name="mark_read">Mark Read</button>
                          </form>
                        <?php endif; ?>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                      </div>
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

        <?php if ($canSend): ?>
          <div class="card skillmap-notification-history">
            <div class="card-body p-4">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h5 fw-bold mb-0">Message History</h2>
                <span class="badge text-bg-light border"><?= count($sentNotifications) ?> sent</span>
              </div>
              <div class="d-grid gap-3 skillmap-notification-list" data-paginated-list="5">
                <?php foreach ($sentNotifications as $sentNotification): ?>
                  <?php
                    $sentId = (int) $sentNotification['id'];
                    $sentModalId = 'sentNotificationModal' . $sentId;
                    $sentBody = trim((string) $sentNotification['body']);
                    $sentPreview = strlen($sentBody) > 140 ? substr($sentBody, 0, 140) . '...' : $sentBody;
                  ?>
                  <div class="border rounded-4 p-3" data-list-item>
                    <div class="d-flex justify-content-between align-items-start gap-3">
                      <div>
                        <div class="fw-semibold"><?= htmlspecialchars($sentNotification['title'], ENT_QUOTES, 'UTF-8') ?></div>
                        <div class="small text-muted mb-2">To <?= htmlspecialchars($sentNotification['audience'], ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars($sentNotification['created_at'], ENT_QUOTES, 'UTF-8') ?></div>
                        <div class="text-muted"><?= nl2br(htmlspecialchars($sentPreview, ENT_QUOTES, 'UTF-8')) ?></div>
                        <button class="btn btn-sm btn-link px-0 mt-2" type="button" data-bs-toggle="modal" data-bs-target="#<?= htmlspecialchars($sentModalId, ENT_QUOTES, 'UTF-8') ?>">Open message</button>
                      </div>
                      <?= skillmap_status_badge($sentNotification['notification_type']) ?>
                    </div>
                  </div>
                  <div class="modal fade" id="<?= htmlspecialchars($sentModalId, ENT_QUOTES, 'UTF-8') ?>" tabindex="-1" aria-labelledby="<?= htmlspecialchars($sentModalId, ENT_QUOTES, 'UTF-8') ?>Title" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered modal-lg">
                      <div class="modal-content">
                        <div class="modal-header">
                          <div>
                            <h2 class="modal-title fs-5" id="<?= htmlspecialchars($sentModalId, ENT_QUOTES, 'UTF-8') ?>Title"><?= htmlspecialchars($sentNotification['title'], ENT_QUOTES, 'UTF-8') ?></h2>
                            <div class="small text-muted">To <?= htmlspecialchars($sentNotification['audience'], ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars($sentNotification['created_at'], ENT_QUOTES, 'UTF-8') ?></div>
                          </div>
                          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                          <div class="mb-3"><?= skillmap_status_badge($sentNotification['notification_type']) ?> <span class="badge text-bg-light border"><?= htmlspecialchars(ucfirst((string) $sentNotification['audience_role']), ENT_QUOTES, 'UTF-8') ?></span></div>
                          <div class="lh-lg"><?= nl2br(htmlspecialchars($sentNotification['body'], ENT_QUOTES, 'UTF-8')) ?></div>
                        </div>
                        <div class="modal-footer">
                          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                      </div>
                    </div>
                  </div>
                <?php endforeach; ?>
                <?php if ($sentNotifications === []): ?>
                  <div class="alert alert-light border mb-0">No sent messages yet.</div>
                <?php endif; ?>
              </div>
            </div>
          </div>
        <?php endif; ?>
      </div>

      <div class="col-lg-5 d-grid gap-4">
        <?php if ($canSend): ?>
          <div class="card d-none skillmap-notification-compose" id="composeBox">
            <div class="card-body p-4">
              <h2 class="h5 fw-bold mb-3">Sent Notification</h2>
              <form method="post" class="d-grid gap-3">
                <div>
                  <label class="form-label">Recipient Mode</label>
                  <div class="btn-group w-100" role="group">
                    <input type="radio" class="btn-check" name="recipient_mode" id="recipientRole" value="role" data-recipient-mode checked>
                    <label class="btn btn-outline-primary" for="recipientRole">Role</label>
                    <input type="radio" class="btn-check" name="recipient_mode" id="recipientUser" value="user" data-recipient-mode>
                    <label class="btn btn-outline-primary" for="recipientUser">User</label>
                  </div>
                </div>
                <div>
                  <label class="form-label">Target Role</label>
                  <select name="recipient_role" class="form-select" data-recipient-role-select>
                    <?php foreach ($allowedTargets as $target): ?>
                      <option value="<?= htmlspecialchars($target, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(ucfirst($target), ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <?php if ($recipientUsers !== []): ?>
                  <div>
                    <label class="form-label">Specific User</label>
                    <select name="recipient_user_id" class="form-select" data-recipient-user-select disabled>
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

        <div class="card skillmap-notification-summary">
          <div class="card-body p-4">
            <h2 class="h5 fw-bold mb-3">Quick Summary</h2>
            <div class="d-grid gap-3">
              <div class="d-flex justify-content-between"><span>Unread</span><strong><?= $unreadCount ?></strong></div>
              <div class="d-flex justify-content-between"><span>Inbox shown</span><strong><?= count($notifications) ?></strong></div>
              <?php if ($canSend): ?>
                <div class="d-flex justify-content-between"><span>Sent shown</span><strong><?= count($sentNotifications) ?></strong></div>
              <?php endif; ?>
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
    <?php require __DIR__ . '/includes/footer.php'; ?>
  </main>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="/fyp_skillmapsystem/assets/js/app.js"></script>
</body>
</html>
