<?php
session_start();
require_once '../../config/database.php';

if (!isset($_GET['id']) || $_SESSION['role'] !== 'Patient') {
    header('Location: my_appointments.php');
    exit();
}

$appointment_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

// Get patient ID
$patient_query = $mysqli->prepare("SELECT patient_id FROM patients WHERE nid = (SELECT nid FROM users WHERE user_id = ?)");
$patient_query->bind_param("i", $user_id);
$patient_query->execute();
$patient_result = $patient_query->get_result();
$patient = $patient_result->fetch_assoc();
$patient_id = $patient['patient_id'] ?? 0;

// Verify appointment belongs to patient
$verify_query = $mysqli->prepare("SELECT a.*, u.first_name, u.last_name FROM appointments a JOIN users u ON a.doctor_id = u.user_id WHERE a.appointment_id = ? AND a.patient_id = ?");
$verify_query->bind_param("ii", $appointment_id, $patient_id);
$verify_query->execute();
$appointment = $verify_query->get_result()->fetch_assoc();

if (!$appointment) {
    header('Location: my_appointments.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $reason = trim($_POST['reason']);
    
    // Update appointment status
    $cancel_stmt = $mysqli->prepare("UPDATE appointments SET status = 'Cancelled', notes = CONCAT(COALESCE(notes, ''), '\nCancelled by patient: ', ?) WHERE appointment_id = ?");
    $cancel_stmt->bind_param("si", $reason, $appointment_id);
    
    if ($cancel_stmt->execute()) {
        // Notify doctor
        $doctor_msg = "Appointment cancelled by " . $_SESSION['full_name'] . " for " . date('M d, Y', strtotime($appointment['appointment_date'])) . " at " . $appointment['appointment_time'] . ". Reason: " . $reason;
        $notify_stmt = $mysqli->prepare("INSERT INTO notifications (user_id, subject, message, notification_type, channel, status) VALUES (?, 'Appointment Cancelled', ?, 'Appointment Reminder', 'System', 'Pending')");
        $notify_stmt->bind_param("is", $appointment['doctor_id'], $doctor_msg);
        $notify_stmt->execute();
        
        header('Location: my_appointments.php?cancelled=1');
        exit();
    } else {
        $error = "Error cancelling appointment.";
    }
}

$page_title = 'Cancel Appointment';
$page_header = '❌ Cancel Appointment';
$show_nav = true;
$breadcrumb = [
    ['title' => 'My Appointments', 'url' => 'my_appointments.php'],
    ['title' => 'Cancel']
];

include '../layouts/header.php';
?>

<div class="container">
    <div style="margin-bottom: 1rem;">
        <a href="my_appointments.php" class="btn btn-secondary">← Back to Appointments</a>
    </div>

    <div class="section">
        <h3>Cancel Appointment</h3>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <div style="background: #f8f9fa; padding: 1rem; border-radius: 4px; margin-bottom: 1rem;">
            <h4>Appointment Details</h4>
            <p><strong>Doctor:</strong> Dr. <?php echo $appointment['first_name'] . ' ' . $appointment['last_name']; ?></p>
            <p><strong>Date:</strong> <?php echo date('M d, Y', strtotime($appointment['appointment_date'])); ?></p>
            <p><strong>Time:</strong> <?php echo $appointment['appointment_time']; ?></p>
            <p><strong>Type:</strong> <?php echo $appointment['appointment_type']; ?></p>
        </div>

        <div class="alert alert-error">
            <strong>Important:</strong> Cancelling less than 24 hours before your appointment may result in a cancellation fee.
        </div>

        <form method="POST">
            <div class="form-group">
                <label>Reason for Cancellation</label>
                <textarea name="reason" rows="4" required placeholder="Please provide a reason for cancelling this appointment"></textarea>
            </div>

            <button type="submit" class="btn" style="background: #dc3545;" onclick="return confirm('Are you sure you want to cancel this appointment?')">Cancel Appointment</button>
            <a href="my_appointments.php" class="btn btn-secondary">Keep Appointment</a>
        </form>
    </div>
</div>

<?php include '../layouts/footer.php'; ?>