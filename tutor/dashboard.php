<?php
require_once '../config/database.php';
requireRole('tutor');

$tutor_id = $_SESSION['user_id'];

// Handle Add Schedule
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        $day = sanitize($conn, $_POST['available_day']);
        $start = sanitize($conn, $_POST['start_time']);
        $end = sanitize($conn, $_POST['end_time']);

        if ($start >= $end) {
            $_SESSION['msg'] = ['type' => 'danger', 'text' => 'End time must be after start time.'];
        } else {
            $stmt = $conn->prepare("INSERT INTO schedules (tutor_id, available_day, start_time, end_time) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isss", $tutor_id, $day, $start, $end);
            if ($stmt->execute()) {
                $_SESSION['msg'] = ['type' => 'success', 'text' => 'Schedule added successfully!'];
            } else {
                $_SESSION['msg'] = ['type' => 'danger', 'text' => 'Failed to add schedule.'];
            }
            $stmt->close();
        }
        redirect('dashboard.php');
    }

    if ($_POST['action'] === 'update') {
        $id = (int)$_POST['schedule_id'];
        $day = sanitize($conn, $_POST['available_day']);
        $start = sanitize($conn, $_POST['start_time']);
        $end = sanitize($conn, $_POST['end_time']);

        if ($start >= $end) {
            $_SESSION['msg'] = ['type' => 'danger', 'text' => 'End time must be after start time.'];
        } else {
            $stmt = $conn->prepare("UPDATE schedules SET available_day=?, start_time=?, end_time=? WHERE id=? AND tutor_id=?");
            $stmt->bind_param("sssii", $day, $start, $end, $id, $tutor_id);
            if ($stmt->execute()) {
                $_SESSION['msg'] = ['type' => 'success', 'text' => 'Schedule updated successfully!'];
            }
            $stmt->close();
        }
        redirect('dashboard.php');
    }

    if ($_POST['action'] === 'delete') {
        $id = (int)$_POST['schedule_id'];
        $stmt = $conn->prepare("DELETE FROM schedules WHERE id=? AND tutor_id=?");
        $stmt->bind_param("ii", $id, $tutor_id);
        if ($stmt->execute()) {
            $_SESSION['msg'] = ['type' => 'success', 'text' => 'Schedule deleted.'];
        }
        $stmt->close();
        redirect('dashboard.php');
    }
}

// Fetch schedules
$stmt = $conn->prepare("SELECT * FROM schedules WHERE tutor_id = ? ORDER BY FIELD(available_day, 'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'), start_time");
$stmt->bind_param("i", $tutor_id);
$stmt->execute();
$schedules = $stmt->get_result();

// Fetch upcoming bookings for this tutor
$bookingStmt = $conn->prepare("
    SELECT b.*, s.available_day, s.start_time, s.end_time, u.full_name AS student_name 
    FROM bookings b 
    JOIN schedules s ON b.schedule_id = s.id 
    JOIN users u ON b.student_id = u.id 
    WHERE s.tutor_id = ? 
    ORDER BY b.created_at DESC 
    LIMIT 10
");
$bookingStmt->bind_param("i", $tutor_id);
$bookingStmt->execute();
$bookings = $bookingStmt->get_result();

$days = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];

$pageTitle = "Tutor Dashboard - StudyBuddy";
include '../includes/header.php';
?>

<div class="container py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4">
        <div>
            <h2 class="fw-bold mb-1"><i class="bi bi-speedometer2"></i> Tutor Dashboard</h2>
            <p class="text-muted mb-0">Manage your availability schedule</p>
        </div>
        <button class="btn btn-primary mt-2 mt-md-0" data-bs-toggle="modal" data-bs-target="#addScheduleModal">
            <i class="bi bi-plus-circle"></i> Add Schedule
        </button>
    </div>

    <?php if (isset($_SESSION['msg'])): ?>
        <div class="alert alert-<?php echo $_SESSION['msg']['type']; ?> alert-dismissible fade show">
            <?php echo $_SESSION['msg']['text']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['msg']); ?>
    <?php endif; ?>

    <!-- My Schedules -->
    <div class="card mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0 fw-bold"><i class="bi bi-calendar3"></i> My Availability</h5>
        </div>
        <div class="card-body p-0">
            <?php if ($schedules->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Day</th>
                                <th>Start Time</th>
                                <th>End Time</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $schedules->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <span class="badge bg-primary"><?php echo $row['available_day']; ?></span>
                                    </td>
                                    <td><?php echo date('g:i A', strtotime($row['start_time'])); ?></td>
                                    <td><?php echo date('g:i A', strtotime($row['end_time'])); ?></td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-outline-primary me-1" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#editModal<?php echo $row['id']; ?>">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Delete this schedule?')">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="schedule_id" value="<?php echo $row['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>

                                <!-- Edit Modal -->
                                <div class="modal fade" id="editModal<?php echo $row['id']; ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form method="POST">
                                                <div class="modal-header">
                                                    <h5 class="modal-title fw-bold">Edit Schedule</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <input type="hidden" name="action" value="update">
                                                    <input type="hidden" name="schedule_id" value="<?php echo $row['id']; ?>">
                                                    <div class="mb-3">
                                                        <label class="form-label fw-semibold">Day</label>
                                                        <select class="form-select" name="available_day" required>
                                                            <?php foreach ($days as $d): ?>
                                                                <option value="<?php echo $d; ?>" <?php echo $row['available_day'] === $d ? 'selected' : ''; ?>>
                                                                    <?php echo $d; ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-6">
                                                            <label class="form-label fw-semibold">Start Time</label>
                                                            <input type="time" class="form-control" name="start_time" 
                                                                   value="<?php echo $row['start_time']; ?>" required>
                                                        </div>
                                                        <div class="col-6">
                                                            <label class="form-label fw-semibold">End Time</label>
                                                            <input type="time" class="form-control" name="end_time" 
                                                                   value="<?php echo $row['end_time']; ?>" required>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-primary">Save Changes</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="bi bi-calendar-x display-4 text-muted"></i>
                    <p class="text-muted mt-2">No schedules yet. Click "Add Schedule" to get started!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Bookings for this tutor -->
    <div class="card">
        <div class="card-header bg-white">
            <h5 class="mb-0 fw-bold"><i class="bi bi-journal-check"></i> Session Bookings</h5>
        </div>
        <div class="card-body p-0">
            <?php if ($bookings->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Day</th>
                                <th>Time</th>
                                <th>Status</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($b = $bookings->fetch_assoc()): ?>
                                <tr>
                                    <td class="fw-semibold"><?php echo htmlspecialchars($b['student_name']); ?></td>
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
                                    <td><?php echo htmlspecialchars($b['notes'] ?? '-'); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="bi bi-inbox display-4 text-muted"></i>
                    <p class="text-muted mt-2">No bookings yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Add Schedule Modal -->
<div class="modal fade" id="addScheduleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Add New Schedule</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Available Day</label>
                        <select class="form-select" name="available_day" required>
                            <option value="">Select a day</option>
                            <?php foreach ($days as $d): ?>
                                <option value="<?php echo $d; ?>"><?php echo $d; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-6">
                            <label class="form-label fw-semibold">Start Time</label>
                            <input type="time" class="form-control" name="start_time" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">End Time</label>
                            <input type="time" class="form-control" name="end_time" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Add Schedule
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>