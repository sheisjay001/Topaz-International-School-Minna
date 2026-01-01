<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' | TISM' : 'Student Dashboard | TISM'; ?></title>
    <link rel="icon" href="../assets/images/logo.jpg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/dark-mode.css">
    <style>
        body {
            background: var(--light-bg);
            background-image: var(--gradient-primary); /* Fallback or subtle bg */
            background-attachment: fixed;
            min-height: 100vh;
        }
        /* Override Dashboard Sidebar for Glassmorphism */
        .sidebar {
            background: rgba(0, 33, 71, 0.95); /* Deep Navy Glass */
            backdrop-filter: blur(10px);
            border-right: 1px solid rgba(255, 255, 255, 0.1);
        }
        .sidebar-brand {
            color: var(--secondary-color) !important;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }
        .menu-item.active {
            background: rgba(255, 215, 0, 0.2); /* Gold tint */
            border-left-color: var(--secondary-color);
            color: #fff;
        }
        .main-content {
            background: transparent; /* Let body gradient show if needed, or keep white/light */
        }
        /* Glass Cards */
        .glass-panel {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 16px;
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.1);
        }
        .stat-card { 
            border: none; 
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            transition: transform 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
    </style>
    <?php if (isset($extra_css)) echo $extra_css; ?>
</head>
<body>