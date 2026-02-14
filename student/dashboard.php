<?php
require_once '../config/database.php';
requireRole('student');

$student_id = $_SESSION['user_id'];
$userData = $_SESSION['user'] ?? ['name' => 'Unknown User', 'email' => 'loading...'];

function getInitials($name)
{
    if (!$name) return '??';
    $parts = explode(' ', $name);
    $initials = '';
    foreach ($parts as $p) $initials .= strtoupper($p[0]);
    return $initials;
}

/* -------------------
   KPI COUNTS
------------------- */
$stats = ['total' => 0, 'pending' => 0, 'completed' => 0];
$kpi = $conn->prepare("
    SELECT 
        COUNT(*) AS total,
        COALESCE(SUM(status='pending'),0) AS pending,
        COALESCE(SUM(status='confirmed'),0) AS completed
    FROM bookings
    WHERE student_id=? AND status!='cancelled'
");
if (!$kpi) die("Prepare failed: " . $conn->error);
$kpi->bind_param("i", $student_id);
$kpi->execute();
$row = $kpi->get_result()->fetch_assoc();
if ($row) $stats = $row;
$kpi->close();

/* -------------------
   Handle Booking
------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'book') {
    $schedule_id = (int)$_POST['schedule_id'];
    $notes = sanitize($conn, $_POST['notes'] ?? '');
    $mode = sanitize($conn, $_POST['mode'] ?? '');

    $check = $conn->prepare("
        SELECT id FROM bookings 
        WHERE student_id=? AND schedule_id=? AND status IN ('pending','confirmed')
    ");
    if (!$check) die("Prepare failed: " . $conn->error);
    $check->bind_param("ii", $student_id, $schedule_id);
    $check->execute();

    if ($check->get_result()->num_rows > 0) {
        $_SESSION['msg'] = ['type' => 'warning', 'text' => 'You have already booked this session.'];
    } else {
        $stmt = $conn->prepare("
            INSERT INTO bookings (student_id, schedule_id, notes, mode)
            VALUES (?,?,?,?)
        ");
        if (!$stmt) die("Prepare failed: " . $conn->error);
        $stmt->bind_param("iiss", $student_id, $schedule_id, $notes, $mode);
        $_SESSION['msg'] = $stmt->execute()
            ? ['type' => 'success', 'text' => 'Session booked successfully!']
            : ['type' => 'danger', 'text' => 'Failed to book session.'];
        $stmt->close();
    }
    $check->close();
    if (!isset($_POST['ajax'])) redirect('dashboard.php');
    exit;
}

/* -------------------
   Handle Cancel Booking
------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'cancel') {
    $booking_id = (int)$_POST['booking_id'];
    $stmt = $conn->prepare("UPDATE bookings SET status='cancelled' WHERE id=? AND student_id=?");
    if (!$stmt) die("Prepare failed: " . $conn->error);
    $stmt->bind_param("ii", $booking_id, $student_id);
    $stmt->execute();
    $stmt->close();

    if (isset($_POST['ajax'])) {
        echo json_encode(['status' => 'success']);
        exit;
    }
    $_SESSION['msg'] = ['type' => 'success', 'text' => 'Booking cancelled.'];
    redirect('dashboard.php');
}

/* -------------------
   Fetch Tutors (exclude booked sessions pending/confirmed)
------------------- */
$tutors = $conn->query("
    SELECT s.*, CONCAT(u.first_name,' ',u.last_name) AS tutor_name, u.subject, s.mode AS tutor_mode
    FROM schedules s
    JOIN users u ON s.tutor_id=u.id
    WHERE u.role='tutor'
      AND s.id NOT IN (
        SELECT schedule_id FROM bookings 
        WHERE student_id = {$student_id} AND status IN ('pending','confirmed')
      )
    ORDER BY tutor_name,
    FIELD(s.available_day,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'),
    s.start_time
");
if (!$tutors) die("Error fetching tutors: " . $conn->error);

/* -------------------
   Fetch My Bookings
------------------- */
$myBookings = $conn->prepare("
    SELECT b.*, s.available_day, s.start_time, s.end_time, s.mode AS tutor_mode,
           CONCAT(u.first_name,' ',u.last_name) AS tutor_name, u.subject
    FROM bookings b
    JOIN schedules s ON b.schedule_id=s.id
    JOIN users u ON s.tutor_id=u.id
    WHERE b.student_id=?
    ORDER BY b.created_at DESC
");
if (!$myBookings) die("Prepare failed: " . $conn->error);
$myBookings->bind_param("i", $student_id);
$myBookings->execute();
$bookings = $myBookings->get_result();
?>

<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<div class="flex h-screen overflow-hidden">
    <?php include './includes/student_sidebar.php'; ?>
    <div class="flex-1 flex flex-col overflow-auto bg-gray-50 transition-colors duration-300">
        <?php include './includes/student_header.php'; ?>
        <main class="p-6 space-y-6">

            <!-- Alerts -->
            <?php if (isset($_SESSION['msg'])):
                $colors = ['success' => 'bg-green-100 text-green-800', 'danger' => 'bg-red-100 text-red-800', 'warning' => 'bg-yellow-100 text-yellow-800'];
                $type = $_SESSION['msg']['type'];
            ?>
                <div class="border px-4 py-3 rounded-lg <?php echo $colors[$type] ?? 'bg-slate-100 text-slate-800'; ?> mb-4">
                    <div class="flex justify-between items-center">
                        <span><?php echo $_SESSION['msg']['text']; ?></span>
                        <button onclick="this.parentElement.parentElement.remove()" class="font-bold">&times;</button>
                    </div>
                </div>
                <?php unset($_SESSION['msg']); ?>
            <?php endif; ?>

            <!-- KPI Cards -->
            <div class="flex flex-wrap gap-4">
                <div class="flex-1 min-w-[220px] bg-white rounded-xl shadow p-4 flex justify-between items-center">
                    <div>
                        <p class="text-xs text-gray-500 font-bold uppercase">Total Bookings</p>
                        <h3 class="text-2xl font-extrabold mt-1 text-gray-900"><?php echo $stats['total']; ?></h3>
                    </div>
                    <i class="bi bi-calendar-check text-3xl text-gray-300"></i>
                </div>
                <div class="flex-1 min-w-[220px] bg-white rounded-xl shadow p-4 flex justify-between items-center">
                    <div>
                        <p class="text-xs text-yellow-500 font-bold uppercase">Pending</p>
                        <h3 class="text-2xl font-extrabold mt-1 text-yellow-600"><?php echo $stats['pending']; ?></h3>
                    </div>
                    <i class="bi bi-hourglass-split text-3xl text-yellow-300"></i>
                </div>
                <div class="flex-1 min-w-[220px] bg-white rounded-xl shadow p-4 flex justify-between items-center">
                    <div>
                        <p class="text-xs text-green-500 font-bold uppercase">Completed</p>
                        <h3 class="text-2xl font-extrabold mt-1 text-green-600"><?php echo $stats['completed']; ?></h3>
                    </div>
                    <i class="bi bi-check-circle-fill text-3xl text-green-300"></i>
                </div>
            </div>

            <!-- Tutors -->
            <div class="bg-white rounded-xl shadow overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                    <h5 class="font-extrabold text-lg text-gray-900 flex items-center gap-2">
                        <i class="bi bi-calendar-event"></i> Available Tutors
                    </h5>
                    <p class="text-sm text-gray-500">Browse tutors & book sessions</p>
                </div>

                <div class="p-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php
                    $grouped = [];
                    if ($tutors) {
                        while ($t = $tutors->fetch_assoc()) {
                            $grouped[$t['tutor_name'] . '|' . $t['subject']][] = $t;
                        }
                    }
                    ?>

                    <?php if (empty($grouped)): ?>
                        <div class="text-center col-span-full py-20 text-gray-500">
                            <i class="bi bi-person-x text-4xl mb-3"></i>
                            <p>No tutors available right now. Check back later!</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($grouped as $key => $scheds):
                            [$tutorName, $subject] = explode('|', $key);
                        ?>
                            <div class="border rounded-xl p-4 bg-gray-50">
                                <div class="flex items-center gap-3 mb-3">
                                    <div class="h-10 w-10 rounded-full bg-gray-900 text-white flex items-center justify-center font-bold text-xs">
                                        <?php echo getInitials($tutorName); ?>
                                    </div>
                                    <div>
                                        <p class="text-sm font-bold text-gray-900"><?php echo htmlspecialchars($tutorName); ?></p>
                                        <p class="text-xs text-gray-500"><?php echo htmlspecialchars($subject ?? 'General'); ?></p>
                                    </div>
                                </div>
                                <?php foreach ($scheds as $s): ?>
                                    <div class="flex justify-between items-center py-2">
                                        <div class="text-xs text-gray-600">
                                            <span class="font-bold"><?php echo date('l, F j, Y', strtotime($s['available_day'])); ?></span><br>
                                            <?php echo date('g:i A', strtotime($s['start_time'])) . ' - ' . date('g:i A', strtotime($s['end_time'])); ?>
                                            <p class="text-[10px] text-gray-400 mt-1">
                                                Mode: <?php echo ucfirst(str_replace('_', ' ', $s['tutor_mode'])); ?>
                                            </p>
                                        </div>
                                        <button onclick="openBookingModal(<?php echo $s['id']; ?>,'<?php echo $s['tutor_mode']; ?>')"
                                            class="px-3 py-1.5 text-xs font-semibold bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                                            Book
                                        </button>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- My Bookings -->
            <div class="bg-white rounded-xl shadow overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                    <h5 class="font-extrabold text-lg text-gray-900 flex items-center gap-2">
                        <i class="bi bi-journal-bookmark"></i> My Bookings
                    </h5>
                    <p class="text-sm text-gray-500">View and manage your sessions</p>
                </div>

                <?php if ($bookings->num_rows > 0): ?>
                    <div class="overflow-x-auto p-6">
                        <table class="min-w-full text-sm text-left">
                            <thead class="bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <?php foreach (['Tutor', 'Subject', 'Day', 'Time', 'Mode', 'Status', 'Action'] as $h): ?>
                                        <th class="px-6 py-3 text-[10px] font-extrabold text-gray-400 uppercase tracking-widest"><?php echo $h; ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <?php while ($b = $bookings->fetch_assoc()): ?>
                                    <tr class="hover:bg-gray-50 transition">
                                        <td class="px-6 py-4 font-bold text-sm"><?php echo htmlspecialchars($b['tutor_name']); ?></td>
                                        <td class="px-6 py-4 text-sm"><?php echo htmlspecialchars($b['subject'] ?? 'General'); ?></td>
                                        <td class="px-6 py-4 text-sm"><?php echo date('l, F j, Y', strtotime($b['available_day'])); ?></td>
                                        <td class="px-6 py-4 text-sm"><?php echo date('g:i A', strtotime($b['start_time'])) . ' - ' . date('g:i A', strtotime($b['end_time'])); ?></td>
                                        <td class="px-6 py-4 text-sm"><?php echo ucfirst(str_replace('_', ' ', $b['mode'] ?? $b['tutor_mode'])); ?></td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase
                                            <?php echo match ($b['status']) {
                                                'pending' => 'bg-yellow-100 text-yellow-700',
                                                'confirmed' => 'bg-green-100 text-green-700',
                                                'cancelled' => 'bg-red-100 text-red-700',
                                                'declined' => 'bg-red-200 text-red-800',
                                                default => 'bg-slate-200 text-slate-600'
                                            }; ?>">
                                                <?php echo ucfirst($b['status']); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <?php if ($b['status'] === 'pending'): ?>
                                                <button onclick="cancelBooking(<?php echo $b['id']; ?>)"
                                                    class="px-3 py-1.5 text-xs font-semibold border border-red-500 text-red-600 rounded-lg hover:bg-red-50 transition">
                                                    Cancel
                                                </button>
                                            <?php else: ?>
                                                <span class="text-gray-400">—</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-20 text-gray-500">
                        <i class="bi bi-bookmark text-4xl mb-3"></i>
                        <p>No bookings yet. Browse tutors above to book a session!</p>
                    </div>
                <?php endif; ?>
            </div>

        </main>
    </div>
</div>

<!-- Booking Modal -->
<div id="bookingModal" class="fixed inset-0 hidden z-50 bg-black/40 flex items-center justify-center px-4">
    <div class="bg-white rounded-xl w-full max-w-md shadow-xl max-h-full overflow-auto transform transition-transform scale-95 opacity-0 duration-200">
        <form method="POST" id="bookingForm">
            <div class="px-6 py-4 border-b border-gray-200">
                <h5 class="font-bold text-lg text-gray-900">Book Session</h5>
            </div>
            <div class="p-6 space-y-4">
                <input type="hidden" name="action" value="book">
                <input type="hidden" name="schedule_id" id="modal_schedule_id">

                <label class="block text-xs font-bold text-gray-500">Select Mode</label>
                <select name="mode" id="modal_mode" required class="w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-green-500 outline-none">
                    <option value="online">Online</option>
                    <option value="face_to_face">Face to Face</option>
                    <option value="both">Both</option>
                </select>

                <textarea name="notes" placeholder="Additional Message (Optional)" class="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-green-500 outline-none"></textarea>
            </div>
            <div class="px-6 py-4 border-t border-gray-200 flex justify-end gap-2">
                <button type="button" onclick="toggleBookingModal()" class="px-4 py-2 rounded-lg border border-gray-300 hover:bg-gray-100">Cancel</button>
                <button type="submit" class="px-4 py-2 rounded-lg bg-green-600 text-white hover:bg-green-700">Confirm</button>
            </div>
        </form>
    </div>
</div>

<script>
    function toggleBookingModal() {
        const modal = document.getElementById('bookingModal');
        const inner = modal.querySelector('div');
        if (modal.classList.contains('hidden')) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            inner.classList.remove('scale-95', 'opacity-0');
            inner.classList.add('scale-100', 'opacity-100');
        } else {
            inner.classList.remove('scale-100', 'opacity-100');
            inner.classList.add('scale-95', 'opacity-0');
            setTimeout(() => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }, 200);
        }
    }

    function openBookingModal(id, tutorMode) {
        document.getElementById('modal_schedule_id').value = id;
        const select = document.getElementById('modal_mode');

        if (tutorMode === 'online') select.innerHTML = '<option value="online">Online</option>';
        else if (tutorMode === 'face_to_face') select.innerHTML = '<option value="face_to_face">Face to Face</option>';
        else select.innerHTML = '<option value="online">Online</option><option value="face_to_face">Face to Face</option>';

        toggleBookingModal();
    }
    // AJAX Cancel Booking
    function cancelBooking(id) {
        if (!confirm('Cancel booking?')) return;
        fetch('', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: 'action=cancel&booking_id=' + id + '&ajax=1'
        }).then(r => r.json()).then(d => {
            if (d.status === 'success') location.reload();
        });
    }
</script>
