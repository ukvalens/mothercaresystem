<?php
session_start();
require_once '../../config/database.php';

if (!in_array($_SESSION['role'], ['Receptionist', 'Admin', 'Doctor'])) {
    header('Location: ../dashboard/index.php');
    exit();
}

$page_title = 'Schedule Appointment';
$page_header = '📅 Schedule Appointment';
$show_nav = true;
$breadcrumb = [
    ['title' => 'Appointments', 'url' => 'view.php'],
    ['title' => 'Schedule New']
];

include '../layouts/header.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $patient_id = $_POST['patient_id'];
    $doctor_id = $_POST['doctor_id'];
    $appointment_date = $_POST['appointment_date'];
    $appointment_time = $_POST['appointment_time'];
    $appointment_type = $_POST['appointment_type'];
    $reason = trim($_POST['reason']);

    $stmt = $mysqli->prepare("INSERT INTO appointments (patient_id, doctor_id, appointment_date, appointment_time, appointment_type, reason, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iissssi", $patient_id, $doctor_id, $appointment_date, $appointment_time, $appointment_type, $reason, $_SESSION['user_id']);
    
    if ($stmt->execute()) {
        $success = "Appointment scheduled successfully!";
    } else {
        $error = "Error scheduling appointment";
    }
}
?>


    <div class="container">
        <div class="section">
            <div style="margin-bottom: 1rem;">
                <a href="view.php" class="btn btn-secondary">← Back to Appointments</a>
                <a href="../dashboard/index.php" class="btn btn-secondary">Dashboard</a>
            </div>
            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            <?php if (isset($error)): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label>Patient</label>
                    <select name="patient_id" required>
                        <option value="">Select Patient</option>
                        <?php
                        $patients = $mysqli->query("SELECT patient_id, first_name, last_name FROM patients WHERE is_active = 1 ORDER BY first_name");
                        while ($patient = $patients->fetch_assoc()) {
                            echo "<option value='{$patient['patient_id']}'>P{$patient['patient_id']} - {$patient['first_name']} {$patient['last_name']}</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Doctor</label>
                    <select name="doctor_id" required>
                        <option value="">Select Doctor</option>
                        <?php
                        $doctors = $mysqli->query("SELECT user_id, first_name, last_name FROM users WHERE role = 'Doctor' AND is_active = 1");
                        while ($doctor = $doctors->fetch_assoc()) {
                            echo "<option value='{$doctor['user_id']}'>Dr. {$doctor['first_name']} {$doctor['last_name']}</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Appointment Date</label>
                    <input type="date" name="appointment_date" required min="<?php echo date('Y-m-d'); ?>">
                </div>

                <div class="form-group">
                    <label>Appointment Time</label>
                    <input type="time" name="appointment_time" required>
                </div>

                <div class="form-group">
                    <label>Appointment Type</label>
                    <select name="appointment_type" required>
                        <option value="">Select Type</option>
                        <option value="ANC Visit">ANC Visit</option>
                        <option value="Ultrasound">Ultrasound</option>
                        <option value="Laboratory">Laboratory</option>
                        <option value="Review">Review</option>
                        <option value="Delivery Planning">Delivery Planning</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Reason/Notes</label>
                    <textarea name="reason" rows="3"></textarea>
                </div>

                <button type="submit" class="btn">Schedule Appointment</button>
                <a href="view.php" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>

<?php include '../layouts/footer.php'; ?>