<div class="topbar glass-panel mb-4 <?php echo isset($topbar_class) ? $topbar_class : ''; ?>">
    <button class="btn btn-light d-lg-none me-2" id="sidebarToggle"><i class="fas fa-bars"></i></button>
    <h4 class="mb-0 fw-bold text-primary"><?php echo isset($page_title) ? $page_title : 'Dashboard'; ?></h4>
    
    <!-- Global Search -->
    <div class="d-none d-md-flex ms-auto me-4" style="max-width: 300px; width: 100%;">
        <div class="input-group">
            <span class="input-group-text bg-white border-0 ps-3"><i class="fas fa-search text-muted"></i></span>
            <input type="text" class="form-control border-0 bg-white" placeholder="Search results, fees..." aria-label="Search">
        </div>
    </div>

    <!-- Notifications -->
    <div class="ms-3 me-3 position-relative notification-wrapper">
        <a href="notifications.php" class="text-secondary"><i class="fas fa-bell fa-lg"></i></a>
    </div>

    <div class="user-profile">
        <div class="text-end d-none d-md-block">
            <small class="text-muted d-block">Welcome,</small>
            <span class="fw-bold"><?php echo htmlspecialchars($_SESSION['student_name'] ?? 'Student'); ?></span>
        </div>
        <div class="user-avatar bg-primary text-white">
            <?php 
            // Fetch photo if not already fetched in main script
            // Assuming $student_photo is available or we default
            if(isset($student_photo) && $student_photo): ?>
                <img src="../<?php echo $student_photo; ?>" alt="Profile" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
            <?php else: ?>
                <?php echo strtoupper(substr($_SESSION['student_name'] ?? 'S', 0, 1)); ?>
            <?php endif; ?>
        </div>
    </div>
</div>