<div class="topbar glass-panel mb-4 <?php echo isset($topbar_class) ? $topbar_class : ''; ?>">
    <button class="btn btn-light d-lg-none" id="sidebarToggle"><i class="fas fa-bars"></i></button>
    <h4 class="mb-0 fw-bold text-primary"><?php echo isset($page_title) ? $page_title : 'Dashboard'; ?></h4>
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