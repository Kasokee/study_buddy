<?php
require_once '../config/database.php';
requireRole('admin');

// Handle booking status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_booking') {
        $booking_id = (int)$_POST['booking_id'];
        $status = sanitize($conn, $_POST['status']);
        if (in_array($status, ['pending', 'confirmed', 'cancelled'])) {
            $stmt = $conn->prepare("UPDATE bookings SET status = ? WHERE id = ?");
            $stmt->bind_param("si", $status, $booking_id);
            if ($stmt->execute()) {
                $_SESSION['msg'] = ['type' => 'success', 'text' => 'Booking status updated.'];
            }
            $stmt->close();
        }
        redirect('dashboard.php');
    }

    if ($_POST['action'] === 'delete_user') {
        $user_id = (int)$_POST['user_id'];
        // Don't allow deleting self
        if ($user_id !== $_SESSION['user_id']) {
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            if ($stmt->execute()) {
                $_SESSION['msg'] = ['type' => 'success', 'text' => 'User deleted.'];
            }
            $stmt->close();
        }
        redirect('dashboard.php');
    }

    if ($_POST['action'] === 'delete_schedule') {
        $schedule_id = (int)$_POST['schedule_id'];
        $stmt = $conn->prepare("DELETE FROM schedules WHERE id = ?");
        $stmt->bind_param("i", $schedule_id);
        if ($stmt->execute()) {
            $_SESSION['msg'] = ['type' => 'success', 'text' => 'Schedule deleted.'];
        }
        $stmt->close();
        redirect('dashboard.php');
    }
}

// Fetch counts
$totalStudents = $conn->query("SELECT COUNT(*) as c FROM users WHERE role='student'")->fetch_assoc()['c'];
$totalTutors = $conn->query("SELECT COUNT(*) as c FROM users WHERE role='tutor'")->fetch_assoc()['c'];
$totalSchedules = $conn->query("SELECT COUNT(*) as c FROM schedules")->fetch_assoc()['c'];
$totalBookings = $conn->query("SELECT COUNT(*) as c FROM bookings")->fetch_assoc()['c'];

// Fetch all users
$users = $conn->query("SELECT * FROM users ORDER BY role, full_name");

// Fetch all schedules with tutor name
$schedules = $conn->query("
    SELECT s.*, u.full_name AS tutor_name, u.subject 
    FROM schedules s 
    JOIN users u ON s.tutor_id = u.id 
    ORDER BY u.full_name, FIELD(s.available_day, 'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday')
");

// Fetch all bookings
$bookings = $conn->query("
    SELECT b.*, 
           s.available_day, s.start_time, s.end_time,
           st.full_name AS student_name,
           tu.full_name AS tutor_name, tu.subject
    FROM bookings b 
    JOIN schedules s ON b.schedule_id = s.id 
    JOIN users st ON b.student_id = st.id 
    JOIN users tu ON s.tutor_id = tu.id 
    ORDER BY b.created_at DESC
");

$pageTitle = "Admin Dashboard - StudyBuddy";
include '../includes/header.php';
?>

<div class="container py-4">
    <div class="mb-4">
        <h2 class="fw-bold mb-1"><i class="bi bi-shield-check"></i> Admin Dashboard</h2>
        <p class="text-muted mb-0">Monitor users, schedules, and bookings</p>
    </div>

    <?php if (isset($_SESSION['msg'])): ?>
        <div class="alert alert-<?php echo $_SESSION['msg']['type']; ?> alert-dismissible fade show">
            <?php echo $_SESSION['msg']['text']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['msg']); ?>
    <?php endif; ?>

    <!-- Stats Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="card stat-card p-3" style="border-left-color: #0d6efd;">
                <div class="card-body p-0">
                    <p class="text-muted small mb-1">Students</p>
                    <p class="stat-number mb-0" style="color: #0d6efd;"><?php echo $totalStudents; ?></p>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card stat-card p-3" style="border-left-color: #198754;">
                <div class="card-body p-0">
                    <p class="text-muted small mb-1">Tutors</p>
                    <p class="stat-number mb-0" style="color: #198754;"><?php echo $totalTutors; ?></p>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card stat-card p-3" style="border-left-color: #ffc107;">
                <div class="card-body p-0">
                    <p class="text-muted small mb-1">Schedules</p>
                    <p class="stat-number mb-0" style="color: #ffc107;"><?php echo $totalSchedules; ?></p>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card stat-card p-3" style="border-left-color: #dc3545;">
                <div class="card-body p-0">
                    <p class="text-muted small mb-1">Bookings</p>
                    <p class="stat-number mb-0" style="color: #dc3545;"><?php echo $totalBookings; ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Users Table -->
    <div class="card mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0 fw-bold"><i class="bi bi-people"></i> All Users</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Subject</th>
                            <th>Joined</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($u = $users->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $u['id']; ?></td>
                                <td class="fw-semibold"><?php echo htmlspecialchars($u['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($u['email']); ?></td>
                                <td>
                                    <?php
                                    $roleClass = match($u['role']) {
                                        'admin' => 'bg-danger',
                                        'tutor' => 'bg-success',
                                        'student' => 'bg-primary',
                                        default => 'bg-secondary'
                                    };
                                    ?>
                                    <span class="badge <?php echo $roleClass; ?>"><?php echo ucfirst($u['role']); ?></span>
                                </td>
                                <td><?php echo htmlspecialchars($u['subject'] ?? '-'); ?></td>
                                <td><?php echo date('M d, Y', strtotime($u['created_at'])); ?></td>
                                <td class="text-end">
                                    <?php if ($u['id'] !== $_SESSION['user_id']): ?>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Delete this user? This will also delete their schedules and bookings.')">
                                            <input type="hidden" name="action" value="delete_user">
                                            <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">You</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Schedules Table -->
    <div class="card mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0 fw-bold"><i class="bi bi-calendar3"></i> All Schedules</h5>
        </div>
        <div class="card-body p-0">
            <?php if ($schedules->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Tutor</th>
                                <th>Subject</th>
                                <th>Day</th>
                                <th>Time</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($s = $schedules->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $s['id']; ?></td>
                                    <td class="fw-semibold"><?php echo htmlspecialchars($s['tutor_name']); ?></td>
                                    <td><?php echo htmlspecialchars($s['subject'] ?? '-'); ?></td>
                                    <td><span class="badge bg-primary"><?php echo $s['available_day']; ?></span></td>
                                    <td><?php echo date('g:i A', strtotime($s['start_time'])) . ' - ' . date('g:i A', strtotime($s['end_time'])); ?></td>
                                    <td class="text-end">
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Delete this schedule?')">
                                            <input type="hidden" name="action" value="delete_schedule">
                                            <input type="hidden" name="schedule_id" value="<?php echo $s['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-4">
                    <p class="text-muted">No schedules created yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Bookings Table -->
    <div class="card">
        <div class="card-header bg-white">
            <h5 class="mb-0 fw-bold"><i class="bi bi-journal-check"></i> All Bookings</h5>
        </div>
        <div class="card-body p-0">
            <?php if ($bookings->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Student</th>
                                <th>Tutor</th>
                                <th>Day</th>
                                <th>Time</th>
                                <th>Status</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($b = $bookings->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $b['id']; ?></td>
                                    <td class="fw-semibold"><?php echo htmlspecialchars($b['student_name']); ?></td>
                                    <td><?php echo htmlspecialchars($b['tutor_name']); ?></td>
                                    <td><span class="badge bg-primary"><?php echo $b['available_day']; ?></span></td>
                                    <td><?php echo date('g:i A', strtotime($b['start_time'])) . ' - ' . date('g:i A', strtotime($b['end_time'])); ?></td>
                                    <td>
                                        <?php
                                        $statusClass = match($b['status']) {
                                            'pending' => 'bg-warning text-dark',
                                            'confirmed' => 'bg-success',
                                            'cancelled' => 'bg-danger',
                                            default => 'bg-secondary'
                                        };
                                        ?>
                                        <span class="badge <?php echo $statusClass; ?>"><?php echo ucfirst($b['status']); ?></span>
                                    </td>
                                    <td class="text-end">
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="action" value="update_booking">
                                            <input type="hidden" name="booking_id" value="<?php echo $b['id']; ?>">
                                            <select name="status" class="form-select form-select-sm d-inline-block" 
                                                    style="width: auto;" onchange="this.form.submit()">
                                                <option value="pending" <?php echo $b['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                <option value="confirmed" <?php echo $b['status'] === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                                <option value="cancelled" <?php echo $b['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                            </select>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-4">
                    <p class="text-muted">No bookings yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>