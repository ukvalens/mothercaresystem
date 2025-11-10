<?php
session_start();
require_once '../../config/database.php';
require_once '../../config/activity_hooks.php';

if (!in_array($_SESSION['role'], ['Receptionist', 'Admin'])) {
    header('Location: ../dashboard/index.php');
    exit();
}

$page_title = 'Patient Registration';
$page_header = '👤 Patient Registration';
$show_nav = true;
$breadcrumb = [
    ['title' => 'Patient Registration']
];

include '../layouts/header.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nid = trim($_POST['nid']);
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $date_of_birth = $_POST['date_of_birth'];
    $gender = $_POST['gender'];
    $contact_number = trim($_POST['contact_number']);
    $email = trim($_POST['email']);
    $address = trim($_POST['address']);
    $emergency_contact_name = trim($_POST['emergency_contact_name']);
    $emergency_contact_phone = trim($_POST['emergency_contact_phone']);
    $blood_type = $_POST['blood_type'];

    if (empty($nid) || empty($first_name) || empty($last_name) || empty($date_of_birth) || empty($contact_number)) {
        $error = "Please fill in all required fields";
    } else {
        $check = $mysqli->prepare("SELECT patient_id FROM patients WHERE nid = ?");
        $check->bind_param("s", $nid);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $error = "Patient with this NID already exists";
        } else {
            $stmt = $mysqli->prepare("INSERT INTO patients (nid, first_name, last_name, date_of_birth, gender, contact_number, email, address, emergency_contact_name, emergency_contact_phone, blood_type, registered_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssssssssi", $nid, $first_name, $last_name, $date_of_birth, $gender, $contact_number, $email, $address, $emergency_contact_name, $emergency_contact_phone, $blood_type, $_SESSION['user_id']);
            
            if ($stmt->execute()) {
                $patient_id = $mysqli->insert_id;
                $success = "Patient registered successfully! Patient ID: P" . $patient_id;
                
                // Send notifications
                hook_patient_registered($patient_id, $_SESSION['user_id']);
            } else {
                $error = "Error registering patient";
            }
        }
    }
}
?>


    <div class="container">
        <div class="section">
            <div style="margin-bottom: 1rem;">
                <a href="../dashboard/index.php" class="btn btn-secondary">← Back to Dashboard</a>
                <a href="list.php" class="btn btn-secondary">View All Patients</a>
            </div>
            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            <?php if (isset($error)): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label>First Name *</label>
                        <input type="text" name="first_name" required>
                    </div>
                    <div class="form-group">
                        <label>Last Name *</label>
                        <input type="text" name="last_name" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>National ID *</label>
                        <input type="text" name="nid" required>
                    </div>
                    <div class="form-group">
                        <label>Date of Birth *</label>
                        <input type="date" name="date_of_birth" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Gender *</label>
                        <select name="gender" required>
                            <option value="">Select Gender</option>
                            <option value="Female">Female</option>
                            <option value="Male">Male</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Blood Type</label>
                        <select name="blood_type">
                            <option value="">Select Blood Type</option>
                            <option value="A+">A+</option>
                            <option value="A-">A-</option>
                            <option value="B+">B+</option>
                            <option value="B-">B-</option>
                            <option value="AB+">AB+</option>
                            <option value="AB-">AB-</option>
                            <option value="O+">O+</option>
                            <option value="O-">O-</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Contact Number *</label>
                        <input type="tel" name="contact_number" required>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email">
                    </div>
                </div>

                <div class="form-group">
                    <label>Address</label>
                    <textarea name="address" rows="3"></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Emergency Contact Name</label>
                        <input type="text" name="emergency_contact_name">
                    </div>
                    <div class="form-group">
                        <label>Emergency Contact Phone</label>
                        <input type="tel" name="emergency_contact_phone">
                    </div>
                </div>

                <button type="submit" class="btn">Register Patient</button>
                <a href="../dashboard/index.php" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>

<?php include '../layouts/footer.php'; ?>