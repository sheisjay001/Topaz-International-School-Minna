<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <a href="index.php" class="sidebar-brand">
            <i class="fas fa-user-graduate me-2"></i>TISM STUDENT
        </a>
        <button class="sidebar-close" id="sidebarClose"><i class="fas fa-times"></i></button>
    </div>
    <div class="sidebar-menu">
        <a href="index.php" class="menu-item <?php echo ($current_page == 'index.php') ? 'active' : ''; ?>">
            <i class="fas fa-tachometer-alt"></i> Dashboard
        </a>
        <a href="profile.php" class="menu-item <?php echo ($current_page == 'profile.php') ? 'active' : ''; ?>">
            <i class="fas fa-user"></i> My Profile
        </a>
        <a href="results.php" class="menu-item <?php echo ($current_page == 'results.php') ? 'active' : ''; ?>">
            <i class="fas fa-chart-bar"></i> My Results
        </a>
        <a href="timetable.php" class="menu-item <?php echo ($current_page == 'timetable.php') ? 'active' : ''; ?>">
            <i class="fas fa-calendar-alt"></i> Timetables
        </a>
        <a href="attendance.php" class="menu-item <?php echo ($current_page == 'attendance.php') ? 'active' : ''; ?>">
            <i class="fas fa-calendar-check"></i> Attendance
        </a>
        <a href="fees.php" class="menu-item <?php echo ($current_page == 'fees.php') ? 'active' : ''; ?>">
            <i class="fas fa-money-bill-wave"></i> School Fees
        </a>
        <a href="cbt.php" class="menu-item <?php echo ($current_page == 'cbt.php') ? 'active' : ''; ?>">
            <i class="fas fa-laptop-code"></i> CBT Exams
        </a>
        <a href="notifications.php" class="menu-item <?php echo ($current_page == 'notifications.php') ? 'active' : ''; ?>">
            <i class="fas fa-bell"></i> Notifications
        </a>
        <a href="activity_log.php" class="menu-item <?php echo ($current_page == 'activity_log.php') ? 'active' : ''; ?>">
            <i class="fas fa-history"></i> Activity Log
        </a>
        <a href="../includes/logout.php?type=student" class="menu-item mt-5 text-danger">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</div>