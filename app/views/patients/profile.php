<?php
session_start();
require_once '../../config/database.php';

if ($_SESSION['role'] !== 'Patient') {
    header('Location: ../dashboard/index.php');
    exit();
}

$page_title = 'My Profile';
$page_header = '👤 My Profile';
$show_nav = true;
$breadcrumb = [
    ['title' => 'My Profile']
];

include '../layouts/header.php';

// Get patient record
$patient_query = $mysqli->prepare("SELECT p.*, u.email FROM patients p JOIN users u ON p.nid = u.nid WHERE u.user_id = ?");
$patient_query->bind_param("i", $_SESSION['user_id']);
$patient_query->execute();
$patient_result = $patient_query->get_result();
$patient = $patient_result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $contact_number = trim($_POST['contact_number']);
    $alternate_contact = trim($_POST['alternate_contact']);
    $address = trim($_POST['address']);
    $emergency_contact_name = trim($_POST['emergency_contact_name']);
    $emergency_contact_phone = trim($_POST['emergency_contact_phone']);
    $emergency_contact_relationship = trim($_POST['emergency_contact_relationship']);
    $medical_history = trim($_POST['medical_history']);
    $known_allergies = trim($_POST['known_allergies']);
    $current_medications = trim($_POST['current_medications']);

    $update_stmt = $mysqli->prepare("UPDATE patients SET contact_number = ?, alternate_contact = ?, address = ?, emergency_contact_name = ?, emergency_contact_phone = ?, emergency_contact_relationship = ?, medical_history = ?, known_allergies = ?, current_medications = ? WHERE patient_id = ?");
    $update_stmt->bind_param("sssssssssi", $contact_number, $alternate_contact, $address, $emergency_contact_name, $emergency_contact_phone, $emergency_contact_relationship, $medical_history, $known_allergies, $current_medications, $patient['patient_id']);
    
    if ($update_stmt->execute()) {
        $success = "Profile updated successfully!";
        // Refresh patient data
        $patient_query->execute();
        $patient = $patient_query->get_result()->fetch_assoc();
    } else {
        $error = "Error updating profile. Please try again.";
    }
}
?>

<div class="container">
    <div style="margin-bottom: 1rem;">
        <a href="../dashboard/index.php" class="btn btn-secondary">← Back to Dashboard</a>
    </div>

    <div class="section">
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 2rem;">
            <div>
                <h3>Basic Information</h3>
                <div style="background: #f8f9fa; padding: 1rem; border-radius: 4px;">
                    <p><strong>Name:</strong> <?php echo $patient['first_name'] . ' ' . $patient['last_name']; ?></p>
                    <p><strong>National ID:</strong> <?php echo $patient['nid']; ?></p>
                    <p><strong>Date of Birth:</strong> <?php echo date('M d, Y', strtotime($patient['date_of_birth'])); ?></p>
                    <p><strong>Age:</strong> <?php echo date('Y') - date('Y', strtotime($patient['date_of_birth'])); ?> years</p>
                    <p><strong>Gender:</strong> <?php echo $patient['gender']; ?></p>
                    <p><strong>Blood Type:</strong> <?php echo $patient['blood_type'] ?: 'Not specified'; ?></p>
                    <p><strong>Email:</strong> <?php echo $patient['email']; ?></p>
                </div>
            </div>

            <div>
                <h3>Update Contact & Medical Information</h3>
                <form method="POST">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Primary Contact</label>
                            <input type="tel" name="contact_number" value="<?php echo $patient['contact_number']; ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Alternate Contact</label>
                            <input type="tel" name="alternate_contact" value="<?php echo $patient['alternate_contact']; ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Address</label>
                        <textarea name="address" rows="2"><?php echo $patient['address']; ?></textarea>
                    </div>

                    <h4 style="margin: 1.5rem 0 1rem 0; color: #0077B6;">Emergency Contact</h4>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Emergency Contact Name</label>
                            <input type="text" name="emergency_contact_name" value="<?php echo $patient['emergency_contact_name']; ?>">
                        </div>
                        <div class="form-group">
                            <label>Emergency Contact Phone</label>
                            <input type="tel" name="emergency_contact_phone" value="<?php echo $patient['emergency_contact_phone']; ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Relationship</label>
                        <select name="emergency_contact_relationship">
                            <option value="">Select Relationship</option>
                            <option value="Spouse" <?php echo $patient['emergency_contact_relationship'] == 'Spouse' ? 'selected' : ''; ?>>Spouse</option>
                            <option value="Parent" <?php echo $patient['emergency_contact_relationship'] == 'Parent' ? 'selected' : ''; ?>>Parent</option>
                            <option value="Sibling" <?php echo $patient['emergency_contact_relationship'] == 'Sibling' ? 'selected' : ''; ?>>Sibling</option>
                            <option value="Friend" <?php echo $patient['emergency_contact_relationship'] == 'Friend' ? 'selected' : ''; ?>>Friend</option>
                            <option value="Other" <?php echo $patient['emergency_contact_relationship'] == 'Other' ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>

                    <h4 style="margin: 1.5rem 0 1rem 0; color: #0077B6;">Medical Information</h4>
                    <div class="form-group">
                        <label>Medical History</label>
                        <textarea name="medical_history" rows="3" placeholder="Previous illnesses, surgeries, chronic conditions"><?php echo $patient['medical_history']; ?></textarea>
                    </div>

                    <div class="form-group">
                        <label>Known Allergies</label>
                        <textarea name="known_allergies" rows="2" placeholder="Food allergies, drug allergies, etc."><?php echo $patient['known_allergies']; ?></textarea>
                    </div>

                    <div class="form-group">
                        <label>Current Medications</label>
                        <textarea name="current_medications" rows="2" placeholder="List all current medications and supplements"><?php echo $patient['current_medications']; ?></textarea>
                    </div>

                    <button type="submit" class="btn">Update Profile</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../layouts/footer.php'; ?>