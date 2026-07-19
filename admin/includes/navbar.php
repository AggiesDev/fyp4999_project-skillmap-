<?php
// Shared admin sidebar navigation for analytics and management pages.

$activePage = $activePage ?? '';
$user = skillmap_current_user() ?? ['name' => 'Admin', 'initials' => 'SM'];
$userRole = $user['role'] ?? 'admin';
$roleLabel = ucwords(str_replace('_', ' ', (string) $userRole));
$brandHref = skillmap_default_destination($user);
$navItems = [
  ['permission' => 'view_admin_dashboard', 'page' => 'analytics', 'href' => '/fyp_skillmapsystem/admin/analytics.php', 'label' => 'Analytics', 'icon' => 'bi-speedometer2'],
  ['permission' => 'manage_skills', 'page' => 'categories', 'href' => '/fyp_skillmapsystem/admin/categories.php', 'label' => 'Categories', 'icon' => 'bi-tags'],
  ['permission' => 'manage_skills', 'page' => 'skill_library', 'href' => '/fyp_skillmapsystem/admin/skill_library.php', 'label' => 'Skill Library', 'icon' => 'bi-journal-code'],
  ['permission' => 'manage_roles', 'page' => 'benchmarks', 'href' => '/fyp_skillmapsystem/admin/benchmarks.php', 'label' => 'Benchmarks', 'icon' => 'bi-diagram-3'],
  ['permission' => 'manage_users', 'page' => 'users', 'href' => '/fyp_skillmapsystem/admin/users.php', 'label' => 'Users', 'icon' => 'bi-people'],
  ['permission' => 'review_student_skills', 'page' => 'reviews', 'href' => '/fyp_skillmapsystem/admin/reviews.php', 'label' => 'Student Reviews', 'icon' => 'bi-person-check'],
  ['permission' => 'send_notifications', 'page' => 'notifications', 'href' => '/fyp_skillmapsystem/admin/notifications.php', 'label' => 'Notifications', 'icon' => 'bi-bell'],
  ['permission' => 'manage_permissions', 'page' => 'permissions', 'href' => '/fyp_skillmapsystem/admin/permissions.php', 'label' => 'Permissions', 'icon' => 'bi-shield-lock'],
];
$visibleNavItems = array_values(array_filter($navItems, static fn(array $item): bool => skillmap_user_can($item['permission'])));
?>
<aside class="skillmap-admin-sidebar" id="adminSidebar">
  <div class="skillmap-admin-brand">
    <a class="text-decoration-none text-white d-flex align-items-center gap-2" href="<?= htmlspecialchars($brandHref, ENT_QUOTES, 'UTF-8') ?>">
      <img class="skillmap-nav-logo" src="/fyp_skillmapsystem/SkillMapLogoPackage/app-icons/icon-64x64.png" alt="">
      <span class="skillmap-admin-brand-text">
        <span class="d-block fw-bold lh-sm">Skill Map</span>
        <span class="skillmap-role-badge mt-1"><?= htmlspecialchars($roleLabel, ENT_QUOTES, 'UTF-8') ?></span>
      </span>
    </a>
    <button class="btn btn-sm btn-outline-light skillmap-admin-toggle" type="button" data-admin-sidebar-toggle aria-label="Show or hide admin menu">
      <i class="bi bi-layout-sidebar-inset"></i>
    </button>
  </div>

  <div class="skillmap-admin-menu">
    <?php foreach ($visibleNavItems as $item): ?>
      <a class="skillmap-admin-menu-link <?= $activePage === $item['page'] ? 'active' : '' ?>" href="<?= htmlspecialchars($item['href'], ENT_QUOTES, 'UTF-8') ?>" title="<?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?>">
        <i class="bi <?= htmlspecialchars($item['icon'], ENT_QUOTES, 'UTF-8') ?>"></i>
        <span><?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?></span>
      </a>
    <?php endforeach; ?>
    <?php if ($visibleNavItems === []): ?>
      <a class="skillmap-admin-menu-link" href="/fyp_skillmapsystem/users/dashboard.php">
        <i class="bi bi-grid-1x2"></i>
        <span>Student Dashboard</span>
      </a>
    <?php endif; ?>
  </div>

  <div class="skillmap-admin-sidebar-footer">
    <div class="d-flex align-items-center gap-2 mb-3">
      <?php if (!empty($user['profile_icon'])): ?>
        <img class="navbar-profile-icon" src="/fyp_skillmapsystem/<?= htmlspecialchars((string) $user['profile_icon'], ENT_QUOTES, 'UTF-8') ?>" alt="">
      <?php else: ?>
        <span class="avatar-circle bg-purple bg-opacity-25"><?= htmlspecialchars($user['initials'] ?? 'SM', ENT_QUOTES, 'UTF-8') ?></span>
      <?php endif; ?>
      <div class="skillmap-admin-user">
        <div class="fw-semibold"><?= htmlspecialchars((string) ($user['name'] ?? 'Admin'), ENT_QUOTES, 'UTF-8') ?></div>
        <div class="small opacity-75"><?= htmlspecialchars($roleLabel, ENT_QUOTES, 'UTF-8') ?></div>
      </div>
    </div>
    <a class="skillmap-admin-menu-link logout" href="/fyp_skillmapsystem/logout.php">
      <i class="bi bi-box-arrow-right"></i>
      <span>Logout</span>
    </a>
    <div class="skillmap-license mt-3">Develop by Aggies<br>&copy; <?= date('Y') ?> Skill Map License. All rights reserved.</div>
  </div>
</aside>
