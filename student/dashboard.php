<?php
require_once '../config/database.php';
requireRole('student');

$student_id = $_SESSION['user_id'];

$userData = $_SESSION['user'] ?? [
    'name' => 'Unknown User',
    'email' => 'loading...'
];

function getInitials($name) {
    if (!$name) return '??';
    $parts = explode(' ', $name);
    $initials = '';
    foreach ($parts as $p) {
        $initials .= strtoupper($p[0]);
    }
    return $initials;
}

/* -------------------
   KPI COUNTS
------------------- */
$stats = [
    'total' => 0,
    'pending' => 0,
    'completed' => 0
];

$kpi = $conn->prepare("
    SELECT 
        COUNT(*) AS total,
        COALESCE(SUM(status = 'pending'), 0) AS pending,
        COALESCE(SUM(status = 'confirmed'), 0) AS completed
    FROM bookings
    WHERE student_id = ? AND status != 'cancelled'
");
$kpi->bind_param("i", $student_id);
$kpi->execute();
$row = $kpi->get_result()->fetch_assoc();
if ($row) {
    $stats = $row;
}
$kpi->close();

/* -------------------
   Handle Booking
------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'book') {
    $schedule_id = (int)$_POST['schedule_id'];
    $notes = sanitize($conn, $_POST['notes'] ?? '');

    $check = $conn->prepare("
        SELECT id FROM bookings 
        WHERE student_id = ? AND schedule_id = ? AND status != 'cancelled'
    ");
    $check->bind_param("ii", $student_id, $schedule_id);
    $check->execute();

    if ($check->get_result()->num_rows > 0) {
        $_SESSION['msg'] = ['type' => 'warning', 'text' => 'You have already booked this session.'];
    } else {
        $stmt = $conn->prepare("
            INSERT INTO bookings (student_id, schedule_id, notes)
            VALUES (?, ?, ?)
        ");
        $stmt->bind_param("iis", $student_id, $schedule_id, $notes);
        $_SESSION['msg'] = $stmt->execute()
            ? ['type' => 'success', 'text' => 'Session booked successfully!']
            : ['type' => 'danger', 'text' => 'Failed to book session.'];
        $stmt->close();
    }
    $check->close();
    redirect('dashboard.php');
}

/* -------------------
   Handle Cancel Booking
------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'cancel') {
    $booking_id = (int)$_POST['booking_id'];
    $stmt = $conn->prepare("
        UPDATE bookings 
        SET status = 'cancelled'
        WHERE id = ? AND student_id = ?
    ");
    $stmt->bind_param("ii", $booking_id, $student_id);
    $stmt->execute();
    $_SESSION['msg'] = ['type' => 'success', 'text' => 'Booking cancelled.'];
    $stmt->close();
    redirect('dashboard.php');
}

/* -------------------
   Fetch Tutors
------------------- */
$tutors = $conn->query("
    SELECT s.*, CONCAT(u.first_name,' ',u.last_name) AS tutor_name, u.subject
    FROM schedules s
    JOIN users u ON s.tutor_id = u.id
    WHERE u.role = 'tutor'
    ORDER BY tutor_name,
    FIELD(s.available_day,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'),
    s.start_time
");

/* -------------------
   Fetch Bookings
------------------- */
$myBookings = $conn->prepare("
    SELECT b.*, s.available_day, s.start_time, s.end_time,
           CONCAT(u.first_name,' ',u.last_name) AS tutor_name, u.subject
    FROM bookings b
    JOIN schedules s ON b.schedule_id = s.id
    JOIN users u ON s.tutor_id = u.id
    WHERE b.student_id = ?
    ORDER BY b.created_at DESC
");
$myBookings->bind_param("i", $student_id);
$myBookings->execute();
$bookings = $myBookings->get_result();
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<div class="flex h-screen overflow-hidden bg-gray-50">
    <?php include './includes/student_sidebar.php'; ?>

    <div class="flex-1 flex flex-col overflow-auto">
        <?php include './includes/student_header.php'; ?>

        <main class="p-6 space-y-6">

            <?php if (isset($_SESSION['msg'])): ?>
                <div class="alert alert-<?php echo $_SESSION['msg']['type']; ?> alert-dismissible fade show">
                    <?php echo $_SESSION['msg']['text']; ?>
                    <button class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['msg']); ?>
            <?php endif; ?>

            <!-- KPI CARDS (ONE LINE FLEX WITH ICONS) -->
            <div class="flex flex-wrap gap-4">
                <!-- TOTAL BOOKINGS -->
                <div class="flex-1 min-w-[220px] bg-white rounded-xl border border-slate-200 shadow-sm p-4">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-xs text-slate-500 font-bold uppercase">Total Bookings</p>
                            <h3 class="text-2xl font-extrabold text-slate-900 mt-1">
                                <?php echo $stats['total']; ?>
                            </h3>
                        </div>
                        <div class="text-slate-300">
                            <i class="bi bi-calendar-check text-3xl"></i>
                        </div>
                    </div>
                </div>

                <!-- PENDING -->
                <div class="flex-1 min-w-[220px] bg-white rounded-xl border border-slate-200 shadow-sm p-4">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-xs text-slate-500 font-bold uppercase">Pending</p>
                            <h3 class="text-2xl font-extrabold text-yellow-600 mt-1">
                                <?php echo $stats['pending']; ?>
                            </h3>
                        </div>
                        <div class="text-yellow-300">
                            <i class="bi bi-hourglass-split text-3xl"></i>
                        </div>
                    </div>
                </div>

                <!-- COMPLETED -->
                <div class="flex-1 min-w-[220px] bg-white rounded-xl border border-slate-200 shadow-sm p-4">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-xs text-slate-500 font-bold uppercase">Completed</p>
                            <h3 class="text-2xl font-extrabold text-green-600 mt-1">
                                <?php echo $stats['completed']; ?>
                            </h3>
                        </div>
                        <div class="text-green-300">
                            <i class="bi bi-check-circle-fill text-3xl"></i>
                        </div>
                    </div>
                </div>

            </div>


            <!-- AVAILABLE TUTORS -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/30">
                    <h5 class="text-lg font-extrabold text-slate-900 flex items-center gap-2">
                        <i class="bi bi-people"></i> Available Tutors
                    </h5>
                    <p class="text-sm text-slate-500 mt-1">Browse tutors and book sessions</p>
                </div>

                <div class="p-6">
                    <?php
                    $grouped = [];
                    while ($t = $tutors->fetch_assoc()) {
                        $grouped[$t['tutor_name'].'|'.$t['subject']][] = $t;
                    }
                    ?>

                    <?php if (empty($grouped)): ?>
                        <div class="text-center py-20 text-slate-500">
                            <i class="bi bi-person-x text-4xl mb-3"></i>
                            <p>No tutors available right now. Check back later!</p>
                        </div>
                    <?php else: ?>
                        <div class="row g-4">
                            <?php foreach ($grouped as $key => $scheds):
                                [$tutorName, $subject] = explode('|', $key);
                            ?>
                                <div class="col-md-6 col-lg-4">
                                    <div class="border rounded-xl p-4">
                                        <div class="flex items-center gap-3 mb-3">
                                            <div class="h-10 w-10 rounded-full bg-slate-900 text-white flex items-center justify-center font-bold text-xs">
                                                <?php echo getInitials($tutorName); ?>
                                            </div>
                                            <div>
                                                <p class="text-sm font-bold text-slate-900"><?php echo htmlspecialchars($tutorName); ?></p>
                                                <p class="text-xs text-slate-500"><?php echo htmlspecialchars($subject ?? 'General'); ?></p>
                                            </div>
                                        </div>

                                        <?php foreach ($scheds as $s): ?>
                                            <div class="flex justify-between items-center py-2">
                                                <div class="text-xs text-slate-600">
                                                    <span class="font-bold"><?php echo $s['available_day']; ?></span><br>
                                                    <?php echo date('g:i A', strtotime($s['start_time'])) . ' - ' . date('g:i A', strtotime($s['end_time'])); ?>
                                                </div>
                                                <button class="btn btn-sm btn-success"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#book<?php echo $s['id']; ?>">
                                                    Book
                                                </button>
                                            </div>

                                            <div class="modal fade" id="book<?php echo $s['id']; ?>">
                                                <div class="modal-dialog">
                                                    <form method="POST" class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Book Session</h5>
                                                            <button class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <input type="hidden" name="action" value="book">
                                                            <input type="hidden" name="schedule_id" value="<?php echo $s['id']; ?>">
                                                            <textarea class="form-control" name="notes" placeholder="Optional notes"></textarea>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                            <button class="btn btn-success">Confirm</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- MY BOOKINGS -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/30">
                    <h5 class="text-lg font-extrabold text-slate-900 flex items-center gap-2">
                        <i class="bi bi-journal-bookmark"></i> My Bookings
                    </h5>
                    <p class="text-sm text-slate-500 mt-1">View and manage your sessions</p>
                </div>

                <?php if ($bookings->num_rows > 0): ?>
                    <div class="overflow-x-auto">
                        <table class="w-full border-collapse">
                            <thead class="bg-slate-50/80 border-b border-slate-100">
                                <tr>
                                    <?php foreach (['Tutor','Subject','Day','Time','Status','Action'] as $h): ?>
                                        <th class="px-6 py-3 text-[10px] font-extrabold text-slate-400 uppercase tracking-widest">
                                            <?php echo $h; ?>
                                        </th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php while ($b = $bookings->fetch_assoc()): ?>
                                    <tr class="hover:bg-slate-50">
                                        <td class="px-6 py-4 font-bold text-sm"><?php echo htmlspecialchars($b['tutor_name']); ?></td>
                                        <td class="px-6 py-4 text-sm"><?php echo htmlspecialchars($b['subject'] ?? 'General'); ?></td>
                                        <td class="px-6 py-4 text-sm"><?php echo $b['available_day']; ?></td>
                                        <td class="px-6 py-4 text-sm">
                                            <?php echo date('g:i A', strtotime($b['start_time'])) . ' - ' . date('g:i A', strtotime($b['end_time'])); ?>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase
                                            <?php echo match($b['status']) {
                                                'pending' => 'bg-yellow-100 text-yellow-700',
                                                'confirmed' => 'bg-green-100 text-green-700',
                                                'cancelled' => 'bg-red-100 text-red-700',
                                                default => 'bg-slate-200 text-slate-600'
                                            }; ?>">
                                                <?php echo ucfirst($b['status']); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <?php if ($b['status'] === 'pending'): ?>
                                                <form method="POST" onsubmit="return confirm('Cancel booking?')">
                                                    <input type="hidden" name="action" value="cancel">
                                                    <input type="hidden" name="booking_id" value="<?php echo $b['id']; ?>">
                                                    <button class="btn btn-sm btn-outline-danger">Cancel</button>
                                                </form>
                                            <?php else: ?>
                                                <span class="text-slate-400">—</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-20 text-slate-500">
                        <i class="bi bi-bookmark text-4xl mb-3"></i>
                        <p>No bookings yet. Browse tutors above to book a session!</p>
                    </div>
                <?php endif; ?>
            </div>

        </main>
    </div>
</div>
