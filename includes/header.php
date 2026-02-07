<?php if (session_status() === PHP_SESSION_NONE) session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'StudyBuddy'; ?></title>
    <link rel="stylesheet" href="./style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <style>
        :root {
            --primary: #0d6efd;
            --primary-dark: #0a58ca;
            --success: #198754;
            --bg-light: #f8f9fa;
        }
        body {
            background-color: var(--bg-light);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .navbar-brand {
            font-weight: 700;
            font-size: 1.4rem;
        }
        .navbar-brand i {
            color: #ffc107;
        }
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
            transition: transform 0.2s;
        }
        .card:hover {
            transform: translateY(-2px);
        }
        .btn-primary {
            border-radius: 8px;
            font-weight: 500;
        }
        .table th {
            font-weight: 600;
            background-color: #f1f3f5;
        }
        .badge {
            font-weight: 500;
            padding: 6px 12px;
        }
        .stat-card {
            border-left: 4px solid var(--primary);
        }
        .stat-card .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary);
        }
        .hero-section {
            background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
            color: white;
            padding: 60px 0;
            margin-bottom: 30px;
        }
        .content-wrapper {
            flex: 1;
        }
        footer {
            margin-top: auto;
            background-color: #212529;
            color: #adb5bd;
            padding: 20px 0;
        }
        .alert {
            border-radius: 10px;
            border: none;
        }
        @media (max-width: 768px) {
            .hero-section {
                padding: 30px 0;
            }
            .stat-card .stat-number {
                font-size: 1.5rem;
            }
        }
    </style>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-book"></i> StudyBuddy
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="nav-item">
                            <span class="nav-link text-light">
                                <i class="bi bi-person-circle"></i> 
                                <?php echo htmlspecialchars($_SESSION['full_name']); ?>
                                <span class="badge bg-info ms-1"><?php echo ucfirst($_SESSION['role']); ?></span>
                            </span>
                        </li>
                        <?php if ($_SESSION['role'] === 'student'): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="student/dashboard.php">
                                    <i class="bi bi-speedometer2"></i> Dashboard
                                </a>
                            </li>
                        <?php elseif ($_SESSION['role'] === 'tutor'): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="tutor/dashboard.php">
                                    <i class="bi bi-speedometer2"></i> Dashboard
                                </a>
                            </li>
                        <?php elseif ($_SESSION['role'] === 'admin'): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="admin/dashboard.php">
                                    <i class="bi bi-speedometer2"></i> Dashboard
                                </a>
                            </li>
                        <?php endif; ?>
                        <li class="nav-item">
                            <a class="nav-link text-warning" href="logout.php">
                                <i class="bi bi-box-arrow-right"></i> Logout
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">
                                <i class="bi bi-box-arrow-in-right"></i> Login
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="signup.php">
                                <i class="bi bi-person-plus"></i> Sign Up
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="content-wrapper">