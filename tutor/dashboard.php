<?php
require_once '../config/database.php';
requireRole('tutor');

$tutor_id = $_SESSION['user_id'];

/* -------------------
   HANDLE ACTIONS
------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ADD AVAILABILITY SLOT
    if ($action === 'add_slot') {
        $date = $_POST['date']; // full date
        $start = $_POST['start_time'];
        $end = $_POST['end_time'];
        $mode = $_POST['mode']; // online / face_to_face / both

        if (strtotime($start) >= strtotime($end)) {
            $_SESSION['error'] = "Start time must be before end time.";
            header("Location: dashboard.php");
            exit;
        }

        $stmt = $conn->prepare("
            INSERT INTO schedules (tutor_id, available_day, start_time, end_time, mode)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("issss", $tutor_id, $date, $start, $end, $mode);
        $stmt->execute();
        $stmt->close();

        header("Location: dashboard.php");
        exit;
    }

    // CANCEL AVAILABILITY SLOT
    if ($action === 'cancel_slot') {
        $slot_id = (int)$_POST['slot_id'];
        $stmt = $conn->prepare("DELETE FROM schedules WHERE id = ? AND tutor_id = ?");
        $stmt->bind_param("ii", $slot_id, $tutor_id);
        $stmt->execute();
        $stmt->close();

        header("Location: dashboard.php");
        exit;
    }

    // ACCEPT OR DECLINE STUDENT BOOKING
    if ($action === 'update_booking') {
        $booking_id = (int)$_POST['booking_id'];
        $status = $_POST['status'] === 'accepted' ? 'confirmed' : 'declined';
        $stmt = $conn->prepare("
            UPDATE bookings 
            SET status=? 
            WHERE id=? AND schedule_id IN (SELECT id FROM schedules WHERE tutor_id=?)
        ");
        $stmt->bind_param("sii", $status, $booking_id, $tutor_id);
        $stmt->execute();
        $stmt->close();

        if (!isset($_POST['ajax'])) {
            header("Location: dashboard.php");
            exit;
        } else {
            echo json_encode(['status' => 'success']);
            exit;
        }
    }
}

/* -------------------
   FETCH SLOTS
------------------- */
$slots = [];
$res = $conn->prepare("SELECT * FROM schedules WHERE tutor_id = ? ORDER BY available_day, start_time");
$res->bind_param("i", $tutor_id);
$res->execute();
$slots = $res->get_result()->fetch_all(MYSQLI_ASSOC);
$res->close();

/* -------------------
   FETCH UPCOMING PENDING BOOKINGS ONLY
------------------- */
$bookings = [];
$bk = $conn->prepare("
    SELECT b.*, u.first_name, u.last_name, s.mode AS tutor_mode, s.available_day, s.start_time, s.end_time
    FROM bookings b
    JOIN users u ON b.student_id = u.id
    JOIN schedules s ON b.schedule_id = s.id
    WHERE s.tutor_id = ? AND b.status = 'pending'
    ORDER BY b.created_at DESC
");
$bk->bind_param("i", $tutor_id);
$bk->execute();
$bookings = $bk->get_result()->fetch_all(MYSQLI_ASSOC);
$bk->close();
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<script src="https://cdn.tailwindcss.com"></script>

<div class="flex h-screen overflow-hidden bg-gray-50">
    <?php include './includes/tutor_sidebar.php'; ?>

    <div class="flex-1 flex flex-col overflow-auto">
        <?php include './includes/tutor_header.php'; ?>

        <main class="p-6 space-y-8">
            <header>
                <h1 class="text-3xl font-bold text-slate-800">Tutor Panel</h1>
                <p class="text-slate-500">Manage your teaching schedule and student bookings.</p>
            </header>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

                <!-- LEFT COLUMN -->
                <div class="lg:col-span-1 space-y-6">

                    <!-- ADD AVAILABILITY -->
                    <section class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
                        <h2 class="text-lg font-bold mb-4 flex items-center text-indigo-600">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2"
                                      d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                            </svg>
                            Add Availability
                        </h2>

                        <form method="POST" class="space-y-4">
                            <input type="hidden" name="action" value="add_slot">

                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Date</label>
                                <input required type="date" name="date"
                                       class="w-full px-4 py-2 border rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none"/>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Start Time</label>
                                    <input required type="time" name="start_time"
                                           class="w-full px-4 py-2 border rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none"/>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">End Time</label>
                                    <input required type="time" name="end_time"
                                           class="w-full px-4 py-2 border rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none"/>
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Session Mode</label>
                                <select name="mode" required
                                        class="w-full px-4 py-2 border rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none">
                                    <option value="online">Online</option>
                                    <option value="face_to_face">Face to Face</option>
                                    <option value="both">Both</option>
                                </select>
                            </div>

                            <button class="w-full bg-indigo-600 text-white font-semibold py-3 rounded-xl hover:bg-indigo-700 transition-colors shadow-lg shadow-indigo-100">
                                Add Slot
                            </button>
                        </form>
                    </section>

                    <!-- STATS -->
                    <section class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
                        <h2 class="text-lg font-bold mb-4">My Stats</h2>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="bg-slate-50 p-4 rounded-xl">
                                <p class="text-2xl font-bold text-indigo-600"><?php echo count($slots); ?></p>
                                <p class="text-xs text-slate-500 uppercase font-medium">Total Slots</p>
                            </div>
                            <div class="bg-slate-50 p-4 rounded-xl">
                                <p class="text-2xl font-bold text-green-600"><?php echo count($bookings); ?></p>
                                <p class="text-xs text-slate-500 uppercase font-medium">Bookings</p>
                            </div>
                        </div>
                    </section>

                </div>

                <!-- RIGHT COLUMN -->
                <div class="lg:col-span-2 space-y-6">

                    <!-- UPCOMING BOOKINGS -->
                    <section>
                        <h2 class="text-xl font-bold mb-4">Upcoming Student Bookings</h2>

                        <?php if (empty($bookings)): ?>
                            <div class="bg-white p-12 rounded-2xl border border-dashed text-center text-slate-400">
                                No students have booked your sessions yet.
                            </div>
                        <?php else: ?>
                            <div class="space-y-3">
                                <?php foreach ($bookings as $booking): ?>
                                    <div class="bg-white p-4 rounded-xl border border-slate-200 flex justify-between items-center">
                                        <div>
                                            <p class="font-bold text-slate-800">
                                                <?php echo htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']); ?>
                                            </p>
                                            <p class="text-xs text-slate-500">
                                                Status: <?php echo ucfirst($booking['status']); ?>
                                            </p>
                                            <p class="text-xs text-slate-400">
                                                Mode: <?php echo ucfirst(str_replace('_',' ', $booking['tutor_mode'] ?? 'online')); ?>
                                            </p>
                                            <p class="text-xs text-gray-500 mt-1">
                                                Date: <?php echo date('l, F j, Y', strtotime($booking['available_day'])); ?><br>
                                                Time: <?php echo date('g:i A', strtotime($booking['start_time'])) . ' - ' . date('g:i A', strtotime($booking['end_time'])); ?>
                                            </p>
                                        </div>

                                        <?php if($booking['status']==='pending'): ?>
                                            <div class="flex gap-2">
                                                <form method="POST" class="inline">
                                                    <input type="hidden" name="action" value="update_booking">
                                                    <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                                    <input type="hidden" name="status" value="accepted">
                                                    <button type="submit" class="px-3 py-1.5 text-xs font-semibold bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                                                        Accept
                                                    </button>
                                                </form>

                                                <form method="POST" class="inline">
                                                    <input type="hidden" name="action" value="update_booking">
                                                    <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                                    <input type="hidden" name="status" value="declined">
                                                    <button type="submit" class="px-3 py-1.5 text-xs font-semibold bg-red-600 text-white rounded-lg hover:bg-red-700 transition">
                                                        Decline
                                                    </button>
                                                </form>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </section>

                    <!-- MY SLOTS -->
                    <section>
                        <h2 class="text-xl font-bold mb-4">My Availability Slots</h2>

                        <?php if (empty($slots)): ?>
                            <div class="bg-white p-12 rounded-2xl border border-dashed text-center text-slate-400">
                                You have not added any availability slots yet.
                            </div>
                        <?php else: ?>
                            <div class="space-y-3">
                                <?php foreach ($slots as $slot): ?>
                                    <div class="bg-white p-4 rounded-xl border border-slate-200 flex justify-between items-center">
                                        <div>
                                            <p class="font-bold text-slate-800">
                                                <?php echo date('l, F j, Y', strtotime($slot['available_day'])); ?>
                                            </p>
                                            <p class="text-xs text-slate-500">
                                                <?php echo date('g:i A', strtotime($slot['start_time'])) . ' - ' . date('g:i A', strtotime($slot['end_time'])); ?>
                                            </p>
                                            <p class="text-xs text-slate-400">
                                                Mode: <?php echo ucfirst(str_replace('_',' ', $slot['mode'] ?? 'online')); ?>
                                            </p>
                                        </div>

                                        <form method="POST">
                                            <input type="hidden" name="action" value="cancel_slot">
                                            <input type="hidden" name="slot_id" value="<?php echo $slot['id']; ?>">
                                            <button class="text-red-600 font-semibold hover:underline">
                                                Cancel
                                            </button>
                                        </form>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </section>

                </div>
            </div>
        </main>
    </div>
</div>
