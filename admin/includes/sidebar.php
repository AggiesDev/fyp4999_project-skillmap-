<?php
// Shared admin sidebar for the analytics dashboard and management screens.

$activePage = $activePage ?? '';
?>
<div class="card rounded-4 shadow-sm border-0">
  <div class="card-body p-3 p-lg-4">
    <div class="small text-uppercase text-muted fw-semibold mb-2">Admin Menu</div>
    <div class="list-group list-group-flush">
      <a class="list-group-item list-group-item-action border-0 rounded-3 mb-2 <?= $activePage === 'analytics' ? 'active bg-primary text-white' : '' ?>" href="/fyp_skillmapsystem/admin/analytics.php">Analytics</a>
      <a class="list-group-item list-group-item-action border-0 rounded-3 mb-2 <?= $activePage === 'categories' ? 'active bg-primary text-white' : '' ?>" href="/fyp_skillmapsystem/admin/categories.php">Categories</a>
      <a class="list-group-item list-group-item-action border-0 rounded-3 mb-2 <?= $activePage === 'skill_library' ? 'active bg-primary text-white' : '' ?>" href="/fyp_skillmapsystem/admin/skill_library.php">Skill Library</a>
      <a class="list-group-item list-group-item-action border-0 rounded-3 mb-2 <?= $activePage === 'benchmarks' ? 'active bg-primary text-white' : '' ?>" href="/fyp_skillmapsystem/admin/benchmarks.php">Benchmarks</a>
      <a class="list-group-item list-group-item-action border-0 rounded-3 <?= $activePage === 'users' ? 'active bg-primary text-white' : '' ?>" href="/fyp_skillmapsystem/admin/users.php">Users</a>
    </div>
  </div>
</div>
