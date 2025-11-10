<?php
session_start();
require_once '../../config/database.php';
require_once '../../config/activity_hooks.php';

if (!in_array($_SESSION['role'], ['Doctor', 'Nurse', 'Receptionist'])) {
    header('Location: ../dashboard/index.php');
    exit();
}

$message = '';

// Get pregnancy statistics for context
$stats = $mysqli->query("
    SELECT 
        COUNT(CASE WHEN current_status = 'Active' THEN 1 END) as active_pregnancies,
        COUNT(CASE WHEN current_status = 'Active' AND risk_level = 'High' THEN 1 END) as high_risk,
        COUNT(CASE WHEN DATE(created_at) = CURDATE() THEN 1 END) as registered_today
    FROM pregnancies
")->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $patient_id = $_POST['patient_id'];
    $lmp_date = $_POST['lmp_date'];
    $edd = $_POST['edd'];
    $gravida = $_POST['gravida'];
    $parity = $_POST['parity'];
    $abortions = $_POST['abortions'] ?? 0;
    $stillbirths = $_POST['stillbirths'] ?? 0;
    $living_children = $_POST['living_children'] ?? 0;
    $blood_type = $_POST['blood_type'];
    $rh_factor = $_POST['rh_factor'];
    $risk_conditions = trim($_POST['risk_conditions']);
    $family_history = trim($_POST['family_history'] ?? '');
    $previous_complications = trim($_POST['previous_complications'] ?? '');
    $current_medications = trim($_POST['current_medications'] ?? '');
    $allergies = trim($_POST['allergies'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    
    // Validate LMP date
    $lmp_timestamp = strtotime($lmp_date);
    $today = time();
    $max_lmp = strtotime('-42 weeks');
    
    if ($lmp_timestamp > $today) {
        $error = "LMP date cannot be in the future.";
    } elseif ($lmp_timestamp < $max_lmp) {
        $error = "LMP date is too far in the past (over 42 weeks ago).";
    } else {
        // Calculate gestational age
        $conception_date = date('Y-m-d', strtotime($lmp_date . ' + 14 days'));
        $gestational_days = floor(($today - $lmp_timestamp) / (24 * 60 * 60));
        $weeks = floor($gestational_days / 7);
        $days = $gestational_days % 7;
        
        // Advanced risk assessment
        $risk_score = 0;
        $risk_factors = [];
        
        // Age-based risk
        $patient_age_query = $mysqli->prepare("SELECT YEAR(CURDATE()) - YEAR(date_of_birth) as age FROM patients WHERE patient_id = ?");
        $patient_age_query->bind_param("i", $patient_id);
        $patient_age_query->execute();
        $age = $patient_age_query->get_result()->fetch_assoc()['age'];
        
        if ($age < 18) { $risk_score += 15; $risk_factors[] = 'Teenage pregnancy'; }
        if ($age > 35) { $risk_score += 20; $risk_factors[] = 'Advanced maternal age'; }
        if ($age > 40) { $risk_score += 30; $risk_factors[] = 'Very advanced maternal age'; }
        
        // Obstetric history risk
        if ($gravida > 5) { $risk_score += 15; $risk_factors[] = 'Grand multiparity'; }
        if ($parity > 4) { $risk_score += 10; $risk_factors[] = 'High parity'; }
        if ($abortions > 2) { $risk_score += 10; $risk_factors[] = 'Recurrent pregnancy loss'; }
        if ($stillbirths > 0) { $risk_score += 20; $risk_factors[] = 'Previous stillbirth'; }
        
        // Medical conditions risk
        $high_risk_conditions = ['diabetes', 'hypertension', 'heart disease', 'kidney disease', 'autoimmune'];
        foreach ($high_risk_conditions as $condition) {
            if (stripos($risk_conditions, $condition) !== false) {
                $risk_score += 25;
                $risk_factors[] = ucfirst($condition);
            }
        }
        
        // Previous complications risk
        $complications = ['preeclampsia', 'eclampsia', 'gestational diabetes', 'placenta previa', 'abruption'];
        foreach ($complications as $complication) {
            if (stripos($previous_complications, $complication) !== false) {
                $risk_score += 20;
                $risk_factors[] = 'Previous ' . $complication;
            }
        }
        
        // Determine risk level
        if ($risk_score >= 60) {
            $risk_level = 'Critical';
        } elseif ($risk_score >= 40) {
            $risk_level = 'High';
        } elseif ($risk_score >= 20) {
            $risk_level = 'Medium';
        } else {
            $risk_level = 'Low';
        }
        
        // Check for existing active pregnancy
        $check_existing = $mysqli->prepare("SELECT pregnancy_id FROM pregnancies WHERE patient_id = ? AND current_status = 'Active'");
        $check_existing->bind_param("i", $patient_id);
        $check_existing->execute();
        
        if ($check_existing->get_result()->num_rows > 0) {
            $error = "Patient already has an active pregnancy. Please complete or update the existing pregnancy first.";
        } else {
            $mysqli->begin_transaction();
            
            try {
                // Insert pregnancy
                $stmt = $mysqli->prepare("INSERT INTO pregnancies (patient_id, lmp_date, estimated_conception_date, expected_delivery_date, gestational_age_weeks, gestational_age_days, gravida, parity, blood_type, rh_factor, risk_level, ai_risk_score, high_risk_conditions, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("issiiiissssdss", $patient_id, $lmp_date, $conception_date, $edd, $weeks, $days, $gravida, $parity, $blood_type, $rh_factor, $risk_level, $risk_score, $risk_conditions, $notes);
                $stmt->execute();
                $pregnancy_id = $mysqli->insert_id;
                
                // Update/Insert obstetric history
                $obs_stmt = $mysqli->prepare("INSERT INTO obstetric_history (patient_id, gravida, parity, living_children, abortions, stillbirths, previous_complications, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE gravida = VALUES(gravida), parity = VALUES(parity), living_children = VALUES(living_children), abortions = VALUES(abortions), stillbirths = VALUES(stillbirths), previous_complications = VALUES(previous_complications), notes = VALUES(notes)");
                $obs_stmt->bind_param("iiiiiiss", $patient_id, $gravida, $parity, $living_children, $abortions, $stillbirths, $previous_complications, $family_history);
                $obs_stmt->execute();
                
                // Send notifications
                hook_pregnancy_registered($pregnancy_id, $_SESSION['user_id']);
                
                // Send risk update notification if high risk
                if ($risk_level == 'High' || $risk_level == 'Critical') {
                    hook_pregnancy_risk_updated($pregnancy_id, $_SESSION['user_id']);
                }
                
                $mysqli->commit();
                $success = "✅ Pregnancy registered successfully!\n\nPregnancy ID: P$pregnancy_id\nRisk Level: $risk_level\nRisk Score: $risk_score/100\n\nNext Steps:\n• Schedule first ANC visit within 2 weeks\n• Patient has been notified\n" . ($risk_level != 'Low' ? "• High risk alerts sent to medical team" : "");
                
            } catch (Exception $e) {
                $mysqli->rollback();
                $error = "Error registering pregnancy: " . $e->getMessage();
            }
        }
    }
}
?>


<?php
$page_title = 'Register Pregnancy - Maternal Care System';
$show_nav = true;
$breadcrumb = [
    ['title' => 'Pregnancies', 'url' => '../patients/list.php'],
    ['title' => 'Register New']
];
include '../layouts/header.php';
?>

<div class="container">
    <div class="section">
        <h2>🤱 Register New Pregnancy</h2>
        <p style="margin-bottom: 0;">Register a new pregnancy for comprehensive maternal care</p>
    </div>

    <?= $message ?>
    
    <div class="section">

        <form method="POST">
            <h3>Patient Information</h3>
            <div class="form-group">
                <label>Select Patient</label>
                <select name="patient_id" required>
                        <option value="">Choose patient to register pregnancy...</option>
                        <?php
                        $patients = $mysqli->query("
                            SELECT p.patient_id, p.first_name, p.last_name, p.date_of_birth, p.blood_type, p.contact_number,
                                   COUNT(pr.pregnancy_id) as pregnancy_count
                            FROM patients p 
                            LEFT JOIN pregnancies pr ON p.patient_id = pr.patient_id
                            WHERE p.is_active = 1 AND p.gender = 'Female'
                            GROUP BY p.patient_id
                            ORDER BY p.first_name
                        ");
                        while ($patient = $patients->fetch_assoc()) {
                            $age = date('Y') - date('Y', strtotime($patient['date_of_birth']));
                            $pregnancy_history = $patient['pregnancy_count'] > 0 ? " (G{$patient['pregnancy_count']})" : " (New)";
                            echo "<option value='{$patient['patient_id']}' data-blood='{$patient['blood_type']}' data-age='{$age}' data-phone='{$patient['contact_number']}'>P{$patient['patient_id']} - {$patient['first_name']} {$patient['last_name']} (Age: $age){$pregnancy_history}</option>";
                        }
                        ?>
                    </select>
            </div>
            
            <h3>Pregnancy Information</h3>
            <div class="form-row">
                <div class="form-group">
                    <label>Last Menstrual Period (LMP)</label>
                    <input type="date" name="lmp_date" required max="<?= date('Y-m-d') ?>">
                </div>
                <div class="form-group">
                    <label>Expected Delivery Date</label>
                    <input type="date" name="edd" required>
                </div>
            </div>
            
            <h3>Obstetric History</h3>
            <div class="form-row">
                <div class="form-group">
                    <label>Gravida (Total pregnancies)</label>
                    <input type="number" name="gravida" min="1" value="1" required>
                </div>
                <div class="form-group">
                    <label>Parity (Previous births)</label>
                    <input type="number" name="parity" min="0" value="0" required>
                </div>
            </div>
            
            <h3>Medical Information</h3>
            <div class="form-row">
                <div class="form-group">
                    <label>Blood Type</label>
                    <select name="blood_type" required>
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
                <div class="form-group">
                    <label>Rh Factor</label>
                    <select name="rh_factor" required>
                        <option value="">Select Rh Factor</option>
                        <option value="Positive">Positive</option>
                        <option value="Negative">Negative</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label>Medical Conditions</label>
                <textarea name="risk_conditions" rows="3" placeholder="Any pre-existing medical conditions"></textarea>
            </div>
            
            <div class="form-group">
                <label>Notes</label>
                <textarea name="notes" rows="2" placeholder="Additional notes or comments"></textarea>
            </div>

            <div style="text-align: center; margin-top: 30px;">
                <button type="submit" class="btn btn-primary">Register Pregnancy</button>
                <a href="../dashboard/index.php" class="btn btn-secondary" style="margin-left: 15px;">Cancel</a>
            </div>
        </form>
    </div>
</div>



    </div>

<?php include '../layouts/footer.php'; ?>