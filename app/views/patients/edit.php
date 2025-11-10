<?php
session_start();
require_once '../../config/database.php';

if (!in_array($_SESSION['role'], ['Admin', 'Receptionist'])) {
    header('Location: ../dashboard/index.php');
    exit();
}

if (!isset($_GET['id'])) {
    header('Location: list.php');
    exit();
}

$patient_id = $_GET['id'];

// Get patient details
$patient_query = $mysqli->prepare("SELECT * FROM patients WHERE patient_id = ? AND is_active = 1");
$patient_query->bind_param("i", $patient_id);
$patient_query->execute();
$patient = $patient_query->get_result()->fetch_assoc();

if (!$patient) {
    header('Location: list.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $date_of_birth = $_POST['date_of_birth'];
    $gender = $_POST['gender'];
    $contact_number = $_POST['contact_number'];
    $email = $_POST['email'];
    $address = $_POST['address'];
    $blood_type = $_POST['blood_type'];
    $emergency_contact_name = $_POST['emergency_contact_name'];
    $emergency_contact_phone = $_POST['emergency_contact_phone'];
    $medical_history = $_POST['medical_history'];
    
    $stmt = $mysqli->prepare("UPDATE patients SET first_name = ?, last_name = ?, date_of_birth = ?, gender = ?, contact_number = ?, email = ?, address = ?, blood_type = ?, emergency_contact_name = ?, emergency_contact_phone = ?, medical_history = ? WHERE patient_id = ?");
    $stmt->bind_param("sssssssssssi", $first_name, $last_name, $date_of_birth, $gender, $contact_number, $email, $address, $blood_type, $emergency_contact_name, $emergency_contact_phone, $medical_history, $patient_id);
    
    if ($stmt->execute()) {
        $success = "Patient information updated successfully!";
        // Refresh patient data
        $patient_query->execute();
        $patient = $patient_query->get_result()->fetch_assoc();
    } else {
        $error = "Error updating patient information: " . $mysqli->error;
    }
}

$page_title = 'Edit Patient';
$page_header = '✏️ Edit Patient';
$show_nav = true;
$breadcrumb = [
    ['title' => 'Patients', 'url' => 'list.php'],
    ['title' => 'Edit Patient']
];

include '../layouts/header.php';
?>

<div class="container">
    <div style="margin-bottom: 1rem;">
        <a href="view.php?id=<?php echo $patient_id; ?>" class="btn btn-secondary">← Back to Patient</a>
        <a href="list.php" class="btn btn-secondary">Patient List</a>
    </div>

    <div class="section">
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <h3 style="color: #0077B6; margin-bottom: 1rem;">Edit Patient Information</h3>
        
        <form method="POST">
            <div class="form-row">
                <div class="form-group">
                    <label>First Name *</label>
                    <input type="text" name="first_name" value="<?php echo htmlspecialchars($patient['first_name']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Last Name *</label>
                    <input type="text" name="last_name" value="<?php echo htmlspecialchars($patient['last_name']); ?>" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Date of Birth *</label>
                    <input type="date" name="date_of_birth" value="<?php echo $patient['date_of_birth']; ?>" required>
                </div>
                <div class="form-group">
                    <label>Gender *</label>
                    <select name="gender" required>
                        <option value="Female" <?php echo $patient['gender'] == 'Female' ? 'selected' : ''; ?>>Female</option>
                        <option value="Male" <?php echo $patient['gender'] == 'Male' ? 'selected' : ''; ?>>Male</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Contact Number *</label>
                    <input type="tel" name="contact_number" value="<?php echo htmlspecialchars($patient['contact_number']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($patient['email']); ?>">
                </div>
            </div>

            <div class="form-group">
                <label>Address *</label>
                <textarea name="address" rows="2" required><?php echo htmlspecialchars($patient['address']); ?></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Blood Type</label>
                    <select name="blood_type">
                        <option value="">Select Blood Type</option>
                        <option value="A+" <?php echo $patient['blood_type'] == 'A+' ? 'selected' : ''; ?>>A+</option>
                        <option value="A-" <?php echo $patient['blood_type'] == 'A-' ? 'selected' : ''; ?>>A-</option>
                        <option value="B+" <?php echo $patient['blood_type'] == 'B+' ? 'selected' : ''; ?>>B+</option>
                        <option value="B-" <?php echo $patient['blood_type'] == 'B-' ? 'selected' : ''; ?>>B-</option>
                        <option value="AB+" <?php echo $patient['blood_type'] == 'AB+' ? 'selected' : ''; ?>>AB+</option>
                        <option value="AB-" <?php echo $patient['blood_type'] == 'AB-' ? 'selected' : ''; ?>>AB-</option>
                        <option value="O+" <?php echo $patient['blood_type'] == 'O+' ? 'selected' : ''; ?>>O+</option>
                        <option value="O-" <?php echo $patient['blood_type'] == 'O-' ? 'selected' : ''; ?>>O-</option>
                    </select>
                </div>
            </div>

            <div style="background: #f8f9fa; padding: 1rem; border-radius: 4px; margin: 1rem 0;">
                <h4 style="color: #0077B6; margin-bottom: 1rem;">Emergency Contact</h4>
                <div class="form-row">
                    <div class="form-group">
                        <label>Emergency Contact Name *</label>
                        <input type="text" name="emergency_contact_name" value="<?php echo htmlspecialchars($patient['emergency_contact_name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Emergency Contact Phone *</label>
                        <input type="tel" name="emergency_contact_phone" value="<?php echo htmlspecialchars($patient['emergency_contact_phone']); ?>" required>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label>Medical History</label>
                <textarea name="medical_history" rows="3" placeholder="Previous medical conditions, allergies, surgeries, etc."><?php echo htmlspecialchars($patient['medical_history']); ?></textarea>
            </div>

            <button type="submit" class="btn">Update Patient Information</button>
            <a href="view.php?id=<?php echo $patient_id; ?>" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</div>

<?php include '../layouts/footer.php'; ?>