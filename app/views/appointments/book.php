<?php
session_start();
require_once '../../config/database.php';
require_once '../../config/activity_hooks.php';

if ($_SESSION['role'] !== 'Patient') {
    header('Location: ../dashboard/index.php');
    exit();
}

$page_title = 'Book Appointment';
$page_header = '📅 Book New Appointment';
$show_nav = true;
$breadcrumb = [
    ['title' => 'My Appointments', 'url' => 'my_appointments.php'],
    ['title' => 'Book New']
];

include '../layouts/header.php';

// Get or create patient record
$patient_query = $mysqli->prepare("SELECT patient_id FROM patients WHERE nid = (SELECT nid FROM users WHERE user_id = ?)");
$patient_query->bind_param("i", $_SESSION['user_id']);
$patient_query->execute();
$patient_result = $patient_query->get_result();
$patient = $patient_result->fetch_assoc();

if (!$patient) {
    // Create patient record from user data
    $user_query = $mysqli->prepare("SELECT nid, first_name, last_name, email FROM users WHERE user_id = ?");
    $user_query->bind_param("i", $_SESSION['user_id']);
    $user_query->execute();
    $user_data = $user_query->get_result()->fetch_assoc();
    
    $create_patient = $mysqli->prepare("INSERT INTO patients (nid, first_name, last_name, email, registered_by) VALUES (?, ?, ?, ?, ?)");
    $create_patient->bind_param("ssssi", $user_data['nid'], $user_data['first_name'], $user_data['last_name'], $user_data['email'], $_SESSION['user_id']);
    $create_patient->execute();
    $patient_id = $mysqli->insert_id;
} else {
    $patient_id = $patient['patient_id'];
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $doctor_id = $_POST['doctor_id'];
    $appointment_date = $_POST['appointment_date'];
    $appointment_time = $_POST['appointment_time'];
    $appointment_type = $_POST['appointment_type'];
    $reason = trim($_POST['reason']);

    // Check if slot is available
    $check_slot = $mysqli->prepare("SELECT appointment_id FROM appointments WHERE doctor_id = ? AND appointment_date = ? AND appointment_time = ?");
    $check_slot->bind_param("iss", $doctor_id, $appointment_date, $appointment_time);
    $check_slot->execute();
    
    if ($check_slot->get_result()->num_rows > 0) {
        $error = "This time slot is already booked. Please choose another time.";
    } else {
        $stmt = $mysqli->prepare("INSERT INTO appointments (patient_id, doctor_id, appointment_date, appointment_time, appointment_type, reason, created_by, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'Scheduled')");
        $stmt->bind_param("iissssi", $patient_id, $doctor_id, $appointment_date, $appointment_time, $appointment_type, $reason, $_SESSION['user_id']);
        
        if ($stmt->execute()) {
            $appointment_id = $mysqli->insert_id;
            $success = "Appointment booked successfully! You will receive a confirmation.";
            
            // Send notifications
            hook_appointment_activity($appointment_id, 'scheduled', $_SESSION['user_id']);
        } else {
            $error = "Error booking appointment. Please try again.";
        }
    }
}
?>

<div class="container">
    <div style="margin-bottom: 1rem;">
        <a href="my_appointments.php" class="btn btn-secondary">← Back to My Appointments</a>
        <a href="../dashboard/index.php" class="btn btn-secondary">Dashboard</a>
    </div>

    <div class="section">
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Select Doctor</label>
                <select name="doctor_id" required>
                    <option value="">Choose a Doctor</option>
                    <?php
                    $doctors = $mysqli->query("SELECT user_id, first_name, last_name, specialization FROM users WHERE role = 'Doctor' AND is_active = 1");
                    while ($doctor = $doctors->fetch_assoc()) {
                        $spec = $doctor['specialization'] ? " - " . $doctor['specialization'] : "";
                        echo "<option value='{$doctor['user_id']}'>Dr. {$doctor['first_name']} {$doctor['last_name']}{$spec}</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Preferred Date</label>
                    <input type="date" name="appointment_date" required min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                </div>
                <div class="form-group">
                    <label>Preferred Time</label>
                    <select name="appointment_time" required>
                        <option value="">Select Time</option>
                        <?php
                        for ($hour = 8; $hour <= 17; $hour++) {
                            for ($min = 0; $min < 60; $min += 30) {
                                $time = sprintf("%02d:%02d", $hour, $min);
                                echo "<option value='$time'>$time</option>";
                            }
                        }
                        ?>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label>Appointment Type</label>
                <select name="appointment_type" required>
                    <option value="">Select Type</option>
                    <option value="ANC Visit">ANC Visit</option>
                    <option value="Ultrasound">Ultrasound</option>
                    <option value="Consultation">General Consultation</option>
                    <option value="Follow-up">Follow-up Visit</option>
                    <option value="Emergency">Emergency</option>
                </select>
            </div>

            <div class="form-group">
                <label>Reason for Visit</label>
                <textarea name="reason" rows="4" placeholder="Please describe your symptoms or reason for the appointment"></textarea>
            </div>

            <button type="submit" class="btn">Book Appointment</button>
            <a href="my_appointments.php" class="btn btn-secondary">Cancel</a>
        </form>
    </div>

    <div class="section">
        <h3>Available Time Slots</h3>
        <p><strong>Clinic Hours:</strong> Monday - Friday: 8:00 AM - 6:00 PM</p>
        <p><strong>Emergency Services:</strong> Available 24/7</p>
        <p><strong>Note:</strong> Appointments are scheduled every 30 minutes. Please arrive 15 minutes early.</p>
    </div>
</div>

<?php include '../layouts/footer.php'; ?>