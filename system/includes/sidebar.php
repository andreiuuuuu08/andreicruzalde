<?php
// Sidebar for enhanced UI/UX with admin profile and audit logs
if (hasRole('admin')) {
    $adminName = $_SESSION['name'] ?? 'Admin';
    $adminEmail = $_SESSION['email'] ?? '';
}
// Helper to get current page for active state
function isActiveSidebar($file) {
    $current = basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
    return $current === $file ? 'active' : '';
}
?>
<aside id="mainSidebar" class="sidebar d-none d-md-block bg-white shadow-lg" style="width: 64px; min-height: 100vh; position: fixed; top: 0; left: 0; z-index: 1030; transition: left 0.3s, width 0.3s; border-top-right-radius: 2rem; border-bottom-right-radius: 2rem; background: linear-gradient(180deg, #f8fafc 0%, #e9f3fa 100%); border-right: 1px solid #e0e0e0;">
    <button id="sidebarToggle" class="btn btn-primary position-fixed" style="top: 1.5rem; left: 64px; z-index: 1050; width: 38px; height: 38px; border-radius: 50%; box-shadow: 0 2px 8px rgba(0,0,0,0.08);" aria-label="Toggle sidebar"><i class="bi bi-chevron-left"></i></button>
    <?php if (hasRole('admin')): ?>
        <div class="sidebar-header p-2 border-bottom bg-gradient text-white text-center rounded-top-4" style="background: linear-gradient(135deg, #0072ff 0%, #43cea2 100%); min-height: 110px;">
            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($adminName); ?>&background=0072ff&color=fff&size=40" class="rounded-circle mb-1 shadow" alt="Admin Profile" width="40" height="40">
            <div class="mt-1"><span class="badge bg-light text-primary" style="font-size: 0.7rem;">Admin</span></div>
        </div>
        <ul class="nav flex-column py-2 px-1" role="menu">
            <li class="nav-item mb-1" role="none">
                <a class="nav-link sidebar-link justify-content-center <?php echo isActiveSidebar('index.php'); ?>" href="<?php echo SITE_URL; ?>/admin/index.php" title="Dashboard" aria-label="Dashboard" role="menuitem" tabindex="0">
                    <i class="bi bi-house-door-fill"></i>
                </a>
            </li>
            <li class="nav-item mb-1" role="none">
                <a class="nav-link sidebar-link justify-content-center <?php echo isActiveSidebar('announcements.php'); ?>" href="<?php echo SITE_URL; ?>/admin/announcements.php" title="Announcements" aria-label="Announcements" role="menuitem" tabindex="0">
                    <i class="bi bi-megaphone-fill"></i>
                </a>
            </li>
            <li class="nav-item mb-1" role="none">
                <a class="nav-link sidebar-link justify-content-center <?php echo isActiveSidebar('audit_logs.php'); ?>" href="<?php echo SITE_URL; ?>/admin/audit_logs.php" title="Audit Logs" aria-label="Audit Logs" role="menuitem" tabindex="0">
                    <i class="bi bi-clipboard-data"></i>
                </a>
            </li>
            <li class="nav-item mb-1" role="none">
                <a class="nav-link sidebar-link justify-content-center <?php echo isActiveSidebar('employees.php'); ?>" href="<?php echo SITE_URL; ?>/admin/employees.php" title="Employees" aria-label="Employees" role="menuitem" tabindex="0">
                    <i class="bi bi-people-fill"></i>
                </a>
            </li>
            <li class="nav-item mb-1" role="none">
                <a class="nav-link sidebar-link justify-content-center <?php echo isActiveSidebar('register_employee.php'); ?>" href="<?php echo SITE_URL; ?>/admin/register_employee.php" title="Register Employee" aria-label="Register Employee" role="menuitem" tabindex="0">
                    <i class="bi bi-person-plus-fill"></i>
                </a>
            </li>
            <li class="nav-item mb-1" role="none">
                <a class="nav-link sidebar-link justify-content-center <?php echo isActiveSidebar('departments.php'); ?>" href="<?php echo SITE_URL; ?>/admin/departments.php" title="Departments" aria-label="Departments" role="menuitem" tabindex="0">
                    <i class="bi bi-diagram-3-fill"></i>
                </a>
            </li>
            <li class="nav-item mb-1" role="none">
                <a class="nav-link sidebar-link justify-content-center <?php echo isActiveSidebar('teamlead.php'); ?>" href="<?php echo SITE_URL; ?>/admin/teamlead.php" title="Team Leads" aria-label="Team Leads" role="menuitem" tabindex="0">
                    <i class="bi bi-person-badge-fill"></i>
                </a>
            </li>
            <li class="nav-item mb-1" role="none">
                <a class="nav-link sidebar-link justify-content-center <?php echo isActiveSidebar('settings.php'); ?>" href="<?php echo SITE_URL; ?>/admin/settings.php" title="System Settings" aria-label="System Settings" role="menuitem" tabindex="0">
                    <i class="bi bi-sliders"></i>
                </a>
            </li>
            <li class="nav-item mb-1" role="none">
                <a class="nav-link sidebar-link justify-content-center <?php echo isActiveSidebar('admin_profile.php'); ?>" href="<?php echo SITE_URL; ?>/admin/admin_profile.php" title="Profile" aria-label="Profile" role="menuitem" tabindex="0">
                    <i class="bi bi-person-circle"></i>
                </a>
            </li>
            <li class="nav-item mb-1" role="none">
                <a class="nav-link sidebar-link justify-content-center <?php echo isActiveSidebar('communications.php'); ?>" href="<?php echo SITE_URL; ?>/admin/communications.php" title="Communications" aria-label="Communications" role="menuitem" tabindex="0">
                    <i class="bi bi-envelope-fill"></i>
                </a>
            </li>
            <li class="nav-item mt-2" role="none">
                <a class="nav-link sidebar-link justify-content-center text-danger" href="<?php echo SITE_URL; ?>/logout.php" title="Logout" aria-label="Logout" role="menuitem" tabindex="0">
                    <i class="bi bi-box-arrow-right"></i>
                </a>
            </li>
        </ul>
    <?php elseif (hasRole('teamlead')): ?>
        <div class="sidebar-header p-2 border-bottom bg-gradient text-white rounded-top-4" style="background: linear-gradient(135deg, #0072ff 0%, #43cea2 100%); min-height: 80px;">
            <i class="bi bi-bar-chart-line-fill" style="font-size:1.5rem;"></i>
        </div>
        <ul class="nav flex-column py-2 px-1" role="menu">
            <li class="nav-item mb-1" role="none">
                <a class="nav-link sidebar-link justify-content-center <?php echo isActiveSidebar('index.php'); ?>" href="<?php echo SITE_URL; ?>/teamlead/index.php" title="Dashboard" aria-label="Dashboard" role="menuitem" tabindex="0">
                    <i class="bi bi-speedometer2"></i>
                </a>
            </li>
            <li class="nav-item mb-1" role="none">
                <a class="nav-link sidebar-link justify-content-center <?php echo isActiveSidebar('team_members.php'); ?>" href="<?php echo SITE_URL; ?>/teamlead/team_members.php" title="Team Members" aria-label="Team Members" role="menuitem" tabindex="0">
                    <i class="bi bi-people-fill"></i>
                </a>
            </li>
            <li class="nav-item mb-1" role="none">
                <a class="nav-link sidebar-link justify-content-center <?php echo isActiveSidebar('pending_feedback.php'); ?>" href="<?php echo SITE_URL; ?>/teamlead/pending_feedback.php" title="Pending Feedback" aria-label="Pending Feedback" role="menuitem" tabindex="0">
                    <i class="bi bi-chat-dots-fill"></i>
                </a>
            </li>
            <li class="nav-item mb-1" role="none">
                <a class="nav-link sidebar-link justify-content-center <?php echo isActiveSidebar('analytics.php'); ?>" href="<?php echo SITE_URL; ?>/teamlead/analytics.php" title="Analytics" aria-label="Analytics" role="menuitem" tabindex="0">
                    <i class="bi bi-bar-chart-line-fill"></i>
                </a>
            </li>
            <li class="nav-item mb-1" role="none">
                <a class="nav-link sidebar-link justify-content-center <?php echo isActiveSidebar('team_performance.php'); ?>" href="<?php echo SITE_URL; ?>/teamlead/team_performance.php" title="Team Performance" aria-label="Team Performance" role="menuitem" tabindex="0">
                    <i class="bi bi-graph-up-arrow"></i>
                </a>
            </li>
            <li class="nav-item mb-1" role="none">
                <a class="nav-link sidebar-link justify-content-center <?php echo isActiveSidebar('communications.php'); ?>" href="<?php echo SITE_URL; ?>/teamlead/communications.php" title="Communications" aria-label="Communications" role="menuitem" tabindex="0">
                    <i class="bi bi-envelope-fill"></i>
                </a>
            </li>
            <li class="nav-item mt-2" role="none">
                <a class="nav-link sidebar-link justify-content-center text-danger" href="<?php echo SITE_URL; ?>/logout.php" title="Logout" aria-label="Logout" role="menuitem" tabindex="0">
                    <i class="bi bi-box-arrow-right"></i>
                </a>
            </li>
        </ul>
    <?php elseif (hasRole('employee')): ?>
        <div class="sidebar-header p-2 border-bottom bg-gradient text-white rounded-top-4" style="background: linear-gradient(135deg, #0072ff 0%, #43cea2 100%); min-height: 80px;">
            <i class="bi bi-bar-chart-line-fill" style="font-size:1.5rem;"></i>
        </div>
        <ul class="nav flex-column py-2 px-1" role="menu">
            <li class="nav-item mb-1" role="none">
                <a class="nav-link sidebar-link justify-content-center <?php echo isActiveSidebar('index.php'); ?>" href="<?php echo SITE_URL; ?>/employee/index.php" title="Dashboard" aria-label="Dashboard" role="menuitem" tabindex="0">
                    <i class="bi bi-speedometer2"></i>
                </a>
            </li>
            <li class="nav-item mb-1" role="none">
                <a class="nav-link sidebar-link justify-content-center <?php echo isActiveSidebar('peer_feedback.php'); ?>" href="<?php echo SITE_URL; ?>/employee/peer_feedback.php" title="Peer Feedback" aria-label="Peer Feedback" role="menuitem" tabindex="0">
                    <i class="bi bi-chat-dots-fill"></i>
                </a>
            </li>
            <li class="nav-item mb-1" role="none">
                <a class="nav-link sidebar-link justify-content-center <?php echo isActiveSidebar('self_assessment.php'); ?>" href="<?php echo SITE_URL; ?>/employee/self_assessment.php" title="Self Assessment" aria-label="Self Assessment" role="menuitem" tabindex="0">
                    <i class="bi bi-person-badge-fill"></i>
                </a>
            </li>
            <li class="nav-item mb-1" role="none">
                <a class="nav-link sidebar-link justify-content-center <?php echo isActiveSidebar('view_feedback.php'); ?>" href="<?php echo SITE_URL; ?>/employee/view_feedback.php" title="View Feedback" aria-label="View Feedback" role="menuitem" tabindex="0">
                    <i class="bi bi-eye-fill"></i>
                </a>
            </li>
            <li class="nav-item mb-1" role="none">
                <a class="nav-link sidebar-link justify-content-center <?php echo isActiveSidebar('profile.php'); ?>" href="<?php echo SITE_URL; ?>/employee/profile.php" title="Profile" aria-label="Profile" role="menuitem" tabindex="0">
                    <i class="bi bi-person-circle"></i>
                </a>
            </li>
            <li class="nav-item mb-1" role="none">
                <a class="nav-link sidebar-link justify-content-center <?php echo isActiveSidebar('change_password.php'); ?>" href="<?php echo SITE_URL; ?>/employee/change_password.php" title="Change Password" aria-label="Change Password" role="menuitem" tabindex="0">
                    <i class="bi bi-key"></i>
                </a>
            </li>
            <li class="nav-item mb-1" role="none">
                <a class="nav-link sidebar-link justify-content-center <?php echo isActiveSidebar('communications.php'); ?>" href="<?php echo SITE_URL; ?>/employee/communications.php" title="Communications" aria-label="Communications" role="menuitem" tabindex="0">
                    <i class="bi bi-envelope-fill"></i>
                </a>
            </li>
            <li class="nav-item mt-2" role="none">
                <a class="nav-link sidebar-link justify-content-center text-danger" href="<?php echo SITE_URL; ?>/logout.php" title="Logout" aria-label="Logout" role="menuitem" tabindex="0">
                    <i class="bi bi-box-arrow-right"></i>
                </a>
            </li>
        </ul>
    <?php endif; ?>
</aside>
<style>
#sidebarToggle {
    transition: left 0.3s;
}
.sidebar-closed + #sidebarToggle {
    left: 0 !important;
}
.sidebar {
    box-shadow: 0 0.5rem 1rem rgba(0,0,0,.08)!important;
    border-right: 1px solid #e0e0e0;
    background: linear-gradient(180deg, #f8fafc 0%, #e9f3fa 100%);
}
.sidebar-link {
    font-weight: 500;
    color: #222 !important;
    border-radius: 50%;
    padding: 0.5rem 0.5rem;
    margin-bottom: 0.1rem;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.2s, color 0.2s, box-shadow 0.2s;
    outline: none;
    position: relative;
}
.sidebar-link:hover, .sidebar-link:focus, .sidebar-link.active {
    background: linear-gradient(90deg, #e0eafc 0%, #cfdef3 100%);
    color: #0072ff !important;
    box-shadow: 0 2px 8px rgba(67,206,162,0.08);
}
.sidebar-link:focus {
    outline: 2px solid #43cea2;
    outline-offset: 2px;
}
.sidebar .nav-item .bi {
    font-size: 1.25rem;
}
.sidebar-link[title]:hover:after, .sidebar-link[title]:focus:after {
    content: attr(title);
    position: absolute;
    left: 110%;
    top: 50%;
    transform: translateY(-50%);
    background: #222;
    color: #fff;
    padding: 2px 10px;
    border-radius: 6px;
    font-size: 0.85rem;
    white-space: nowrap;
    z-index: 2000;
    opacity: 1;
    pointer-events: none;
    box-shadow: 0 2px 8px rgba(0,0,0,0.12);
}
.sidebar-link[title]:after {
    opacity: 0;
    transition: opacity 0.2s;
}
.sidebar-link[title]:hover:after, .sidebar-link[title]:focus:after {
    opacity: 1;
}
</style>
<script>
const sidebar = document.getElementById('mainSidebar');
const toggleBtn = document.getElementById('sidebarToggle');
let sidebarOpen = true;
if (toggleBtn) {
    toggleBtn.addEventListener('click', function() {
        if (sidebarOpen) {
            sidebar.style.left = '-64px';
            toggleBtn.style.left = '0';
            toggleBtn.innerHTML = '<i class="bi bi-chevron-right"></i>';
            sidebarOpen = false;
        } else {
            sidebar.style.left = '0';
            toggleBtn.style.left = '64px';
            toggleBtn.innerHTML = '<i class="bi bi-chevron-left"></i>';
            sidebarOpen = true;
        }
    });
}
</script>
<!-- Responsive sidebar toggle for mobile -->
<nav class="navbar navbar-light bg-white d-md-none shadow-sm">
    <div class="container-fluid">
        <button class="btn btn-outline-primary" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar" aria-controls="mobileSidebar">
            <i class="bi bi-list"></i> Menu
        </button>
    </div>
</nav>
<div class="offcanvas offcanvas-start" tabindex="-1" id="mobileSidebar" aria-labelledby="mobileSidebarLabel">
    <div class="offcanvas-header bg-primary text-white">
        <h5 class="offcanvas-title" id="mobileSidebarLabel"><i class="bi bi-bar-chart-line-fill me-2"></i>Menu</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body p-0">
        <ul class="nav flex-column py-3" role="menu">
            <?php if (hasRole('admin')): ?>
            <li class="nav-item mb-2" role="none">
                <a class="nav-link d-flex align-items-center text-dark <?php echo isActiveSidebar('index.php'); ?>" href="<?php echo SITE_URL; ?>/admin/index.php" role="menuitem" tabindex="0">
                    <i class="bi bi-house-door-fill me-2"></i> Dashboard
                </a>
            </li>
            <li class="nav-item mb-2" role="none">
                <a class="nav-link d-flex align-items-center text-dark <?php echo isActiveSidebar('announcements.php'); ?>" href="<?php echo SITE_URL; ?>/admin/announcements.php" role="menuitem" tabindex="0">
                    <i class="bi bi-megaphone-fill me-2"></i> Announcements
                </a>
            </li>
            <li class="nav-item mb-2" role="none">
                <a class="nav-link d-flex align-items-center text-dark <?php echo isActiveSidebar('audit_logs.php'); ?>" href="<?php echo SITE_URL; ?>/admin/audit_logs.php" role="menuitem" tabindex="0">
                    <i class="bi bi-clipboard-data me-2"></i> Audit Logs
                </a>
            </li>
            <li class="nav-item mb-2" role="none">
                <a class="nav-link d-flex align-items-center text-dark <?php echo isActiveSidebar('employees.php'); ?>" href="<?php echo SITE_URL; ?>/admin/employees.php" role="menuitem" tabindex="0">
                    <i class="bi bi-people-fill me-2"></i> Employees
                </a>
            </li>
            <li class="nav-item mb-2" role="none">
                <a class="nav-link d-flex align-items-center text-dark <?php echo isActiveSidebar('register_employee.php'); ?>" href="<?php echo SITE_URL; ?>/admin/register_employee.php" role="menuitem" tabindex="0">
                    <i class="bi bi-person-plus-fill me-2"></i> Register Employee
                </a>
            </li>
            <li class="nav-item mb-2" role="none">
                <a class="nav-link d-flex align-items-center text-dark <?php echo isActiveSidebar('departments.php'); ?>" href="<?php echo SITE_URL; ?>/admin/departments.php" role="menuitem" tabindex="0">
                    <i class="bi bi-diagram-3-fill me-2"></i> Departments
                </a>
            </li>
            <li class="nav-item mb-2" role="none">
                <a class="nav-link d-flex align-items-center text-dark <?php echo isActiveSidebar('teamlead.php'); ?>" href="<?php echo SITE_URL; ?>/admin/teamlead.php" role="menuitem" tabindex="0">
                    <i class="bi bi-person-badge-fill me-2"></i> Team Leads
                </a>
            </li>
            <li class="nav-item mb-2" role="none">
                <a class="nav-link d-flex align-items-center text-dark <?php echo isActiveSidebar('settings.php'); ?>" href="<?php echo SITE_URL; ?>/admin/settings.php" role="menuitem" tabindex="0">
                    <i class="bi bi-sliders me-2"></i> System Settings
                </a>
            </li>
            <li class="nav-item mb-2" role="none">
                <a class="nav-link d-flex align-items-center text-dark <?php echo isActiveSidebar('admin_profile.php'); ?>" href="<?php echo SITE_URL; ?>/admin/admin_profile.php" role="menuitem" tabindex="0">
                    <i class="bi bi-person-circle me-2"></i> Profile
                </a>
            </li>
            <li class="nav-item mb-2" role="none">
                <a class="nav-link d-flex align-items-center text-dark <?php echo isActiveSidebar('communications.php'); ?>" href="<?php echo SITE_URL; ?>/admin/communications.php" role="menuitem" tabindex="0">
                    <i class="bi bi-envelope-fill me-2"></i> Communications
                </a>
            </li>
            <?php elseif (hasRole('teamlead')): ?>
            <li class="nav-item mb-2" role="none">
                <a class="nav-link d-flex align-items-center text-dark <?php echo isActiveSidebar('index.php'); ?>" href="<?php echo SITE_URL; ?>/teamlead/index.php" role="menuitem" tabindex="0">
                    <i class="bi bi-speedometer2 me-2"></i> Dashboard
                </a>
            </li>
            <li class="nav-item mb-2" role="none">
                <a class="nav-link d-flex align-items-center text-dark <?php echo isActiveSidebar('team_members.php'); ?>" href="<?php echo SITE_URL; ?>/teamlead/team_members.php" role="menuitem" tabindex="0">
                    <i class="bi bi-people-fill me-2"></i> Team Members
                </a>
            </li>
            <li class="nav-item mb-2" role="none">
                <a class="nav-link d-flex align-items-center text-dark <?php echo isActiveSidebar('pending_feedback.php'); ?>" href="<?php echo SITE_URL; ?>/teamlead/pending_feedback.php" role="menuitem" tabindex="0">
                    <i class="bi bi-chat-dots-fill me-2"></i> Pending Feedback
                </a>
            </li>
            <li class="nav-item mb-2" role="none">
                <a class="nav-link d-flex align-items-center text-dark <?php echo isActiveSidebar('analytics.php'); ?>" href="<?php echo SITE_URL; ?>/teamlead/analytics.php" role="menuitem" tabindex="0">
                    <i class="bi bi-bar-chart-line-fill me-2"></i> Analytics
                </a>
            </li>
            <li class="nav-item mb-2" role="none">
                <a class="nav-link d-flex align-items-center text-dark <?php echo isActiveSidebar('team_performance.php'); ?>" href="<?php echo SITE_URL; ?>/teamlead/team_performance.php" role="menuitem" tabindex="0">
                    <i class="bi bi-graph-up-arrow me-2"></i> Team Performance
                </a>
            </li>
            <li class="nav-item mb-2" role="none">
                <a class="nav-link d-flex align-items-center text-dark <?php echo isActiveSidebar('communications.php'); ?>" href="<?php echo SITE_URL; ?>/teamlead/communications.php" role="menuitem" tabindex="0">
                    <i class="bi bi-envelope-fill me-2"></i> Communications
                </a>
            </li>
            <?php elseif (hasRole('employee')): ?>
            <li class="nav-item mb-2" role="none">
                <a class="nav-link d-flex align-items-center text-dark <?php echo isActiveSidebar('index.php'); ?>" href="<?php echo SITE_URL; ?>/employee/index.php" role="menuitem" tabindex="0">
                    <i class="bi bi-speedometer2 me-2"></i> Dashboard
                </a>
            </li>
            <li class="nav-item mb-2" role="none">
                <a class="nav-link d-flex align-items-center text-dark <?php echo isActiveSidebar('peer_feedback.php'); ?>" href="<?php echo SITE_URL; ?>/employee/peer_feedback.php" role="menuitem" tabindex="0">
                    <i class="bi bi-chat-dots-fill me-2"></i> Peer Feedback
                </a>
            </li>
            <li class="nav-item mb-2" role="none">
                <a class="nav-link d-flex align-items-center text-dark <?php echo isActiveSidebar('self_assessment.php'); ?>" href="<?php echo SITE_URL; ?>/employee/self_assessment.php" role="menuitem" tabindex="0">
                    <i class="bi bi-person-badge-fill me-2"></i> Self Assessment
                </a>
            </li>
            <li class="nav-item mb-2" role="none">
                <a class="nav-link d-flex align-items-center text-dark <?php echo isActiveSidebar('view_feedback.php'); ?>" href="<?php echo SITE_URL; ?>/employee/view_feedback.php" role="menuitem" tabindex="0">
                    <i class="bi bi-eye-fill me-2"></i> View Feedback
                </a>
            </li>
            <li class="nav-item mb-2" role="none">
                <a class="nav-link d-flex align-items-center text-dark <?php echo isActiveSidebar('profile.php'); ?>" href="<?php echo SITE_URL; ?>/employee/profile.php" role="menuitem" tabindex="0">
                    <i class="bi bi-person-circle me-2"></i> Profile
                </a>
            </li>
            <li class="nav-item mb-2" role="none">
                <a class="nav-link d-flex align-items-center text-dark <?php echo isActiveSidebar('change_password.php'); ?>" href="<?php echo SITE_URL; ?>/employee/change_password.php" role="menuitem" tabindex="0">
                    <i class="bi bi-key me-2"></i> Change Password
                </a>
            </li>
            <li class="nav-item mb-2" role="none">
                <a class="nav-link d-flex align-items-center text-dark <?php echo isActiveSidebar('communications.php'); ?>" href="<?php echo SITE_URL; ?>/employee/communications.php" role="menuitem" tabindex="0">
                    <i class="bi bi-envelope-fill me-2"></i> Communications
                </a>
            </li>
            <?php endif; ?>
            <li class="nav-item mt-3" role="none">
                <a class="nav-link d-flex align-items-center text-danger" href="<?php echo SITE_URL; ?>/logout.php" role="menuitem" tabindex="0">
                    <i class="bi bi-box-arrow-right me-2"></i> Logout
                </a>
            </li>
        </ul>
    </div>
</div>
<?php // End of sidebar ?>
