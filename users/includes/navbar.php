<?php
// Shared student navbar with navigation, notifications, and account actions.

$activePage = $activePage ?? '';
$user = skillmap_current_user() ?? ['id' => 0, 'role' => 'student', 'name' => 'Student', 'initials' => 'SM'];
$roleLabel = ucwords(str_replace('_', ' ', (string) ($user['role'] ?? 'student')));
$unreadCount = skillmap_notification_unread_count((int) ($user['id'] ?? 0), (string) ($user['role'] ?? 'student'));

$navItems = [
    ['key' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'bi-grid-1x2', 'href' => '/fyp_skillmapsystem/users/dashboard.php'],
    ['key' => 'profile', 'label' => 'Profile', 'icon' => 'bi-person', 'href' => '/fyp_skillmapsystem/users/profile.php'],
    ['key' => 'assessment', 'label' => 'Assessment', 'icon' => 'bi-stars', 'href' => '/fyp_skillmapsystem/users/skills_assessment.php'],
    ['key' => 'analyse', 'label' => 'Analyse', 'icon' => 'bi-search', 'href' => '/fyp_skillmapsystem/users/analyse.php'],
    ['key' => 'roadmap', 'label' => 'Roadmap', 'icon' => 'bi-map', 'href' => '/fyp_skillmapsystem/users/roadmap.php'],
    ['key' => 'progress', 'label' => 'Progress', 'icon' => 'bi-graph-up', 'href' => '/fyp_skillmapsystem/users/progress.php'],
    ['key' => 'report', 'label' => 'Report', 'icon' => 'bi-file-earmark-text', 'href' => '/fyp_skillmapsystem/users/report.php'],
];
?>
<nav class="navbar navbar-expand-lg navbar-dark navbar-sm-primary px-3 px-lg-4 shadow-sm">
  <a class="navbar-brand skillmap-navbar-brand fw-bold d-flex align-items-center gap-2" href="/fyp_skillmapsystem/users/dashboard.php">
    <img class="skillmap-nav-logo" src="/fyp_skillmapsystem/SkillMapLogoPackage/app-icons/icon-64x64.png" alt="">
    <span>Skill Map</span>
    <span class="skillmap-role-badge"><?= htmlspecialchars($roleLabel, ENT_QUOTES, 'UTF-8') ?></span>
  </a>
  <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#studentNavbar" aria-controls="studentNavbar" aria-expanded="false" aria-label="Toggle navigation">
    <span class="navbar-toggler-icon"></span>
  </button>
  <div class="collapse navbar-collapse" id="studentNavbar">
    <ul class="navbar-nav mx-auto gap-1 my-3 my-lg-0 align-items-lg-center">
      <?php foreach ($navItems as $item): ?>
        <li class="nav-item">
          <a class="nav-link rounded-pill px-3 <?= $activePage === $item['key'] ? 'active' : '' ?>" href="<?= htmlspecialchars($item['href'], ENT_QUOTES, 'UTF-8') ?>">
            <i class="bi <?= htmlspecialchars($item['icon'], ENT_QUOTES, 'UTF-8') ?> me-1"></i><?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?>
          </a>
        </li>
      <?php endforeach; ?>
    </ul>
    <div class="d-flex align-items-center gap-2 text-white">
      <a class="nav-link position-relative px-2 <?= $activePage === 'notifications' ? 'active' : '' ?>" href="/fyp_skillmapsystem/users/notifications.php" aria-label="Notifications">
        <i class="bi bi-bell"></i>
        <?php if ($unreadCount > 0): ?>
          <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"><?= $unreadCount ?></span>
        <?php endif; ?>
      </a>
      <?php if (!empty($user['profile_icon'])): ?>
        <img class="navbar-profile-icon" src="/fyp_skillmapsystem/<?= htmlspecialchars((string) $user['profile_icon'], ENT_QUOTES, 'UTF-8') ?>" alt="" title="<?= htmlspecialchars((string) ($user['name'] ?? 'Student'), ENT_QUOTES, 'UTF-8') ?>">
      <?php else: ?>
        <span class="avatar-circle" title="<?= htmlspecialchars((string) ($user['name'] ?? 'Student'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) ($user['initials'] ?? 'SM'), ENT_QUOTES, 'UTF-8') ?></span>
      <?php endif; ?>
      <a class="nav-link d-flex align-items-center gap-1" href="/fyp_skillmapsystem/logout.php">
        <i class="bi bi-box-arrow-right"></i><span>Logout</span>
      </a>
    </div>
  </div>
</nav>
