<?php
$pageTitle = "StudyBuddy - Peer Tutoring Made Easy";
include 'includes/header.php';
?>

<!-- Hero Section -->
<div class="hero-section text-center">
    <div class="container">
        <h1 class="display-4 fw-bold mb-3">Welcome to StudyBuddy</h1>
        <p class="lead mb-4">Find peer tutors, book sessions, and improve your grades &mdash; all in one place.</p>
        <?php if (!isset($_SESSION['user_id'])): ?>
            <a href="signup.php" class="btn btn-warning btn-lg me-2 mb-2">
                <i class="bi bi-person-plus"></i> Get Started
            </a>
            <a href="login.php" class="btn btn-outline-light btn-lg mb-2">
                <i class="bi bi-box-arrow-in-right"></i> Login
            </a>
        <?php else: ?>
            <?php
            $dashboardUrl = match($_SESSION['role']) {
                'student' => 'student/dashboard.php',
                'tutor' => 'tutor/dashboard.php',
                'admin' => 'admin/dashboard.php',
                default => '#'
            };
            ?>
            <a href="<?php echo $dashboardUrl; ?>" class="btn btn-warning btn-lg">
                <i class="bi bi-speedometer2"></i> Go to Dashboard
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- Features Section -->
<div class="container mb-5">
    <div class="text-center mb-5">
        <h2 class="fw-bold">How StudyBuddy Works</h2>
        <p class="text-muted">Simple steps to better learning</p>
    </div>
    <div class="row g-4">
        <div class="col-md-4">
            <div class="card h-100 text-center p-4">
                <div class="card-body">
                    <div class="mb-3">
                        <i class="bi bi-search display-4 text-primary"></i>
                    </div>
                    <h5 class="card-title fw-bold">Find a Tutor</h5>
                    <p class="card-text text-muted">Browse available peer tutors by subject and find the right match for your needs.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100 text-center p-4">
                <div class="card-body">
                    <div class="mb-3">
                        <i class="bi bi-calendar-check display-4 text-success"></i>
                    </div>
                    <h5 class="card-title fw-bold">Book a Session</h5>
                    <p class="card-text text-muted">Select a time slot that works for you and book your tutoring session instantly.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100 text-center p-4">
                <div class="card-body">
                    <div class="mb-3">
                        <i class="bi bi-mortarboard display-4 text-warning"></i>
                    </div>
                    <h5 class="card-title fw-bold">Learn &amp; Grow</h5>
                    <p class="card-text text-muted">Attend your session and boost your understanding with personalized peer support.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>