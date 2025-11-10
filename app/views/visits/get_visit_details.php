<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Patient' || !isset($_GET['id'])) {
    exit('Unauthorized');
}

$visit_id = $_GET['id'];

// Get patient ID for security check
$patient_query = $mysqli->prepare("SELECT patient_id FROM patients WHERE nid = (SELECT nid FROM users WHERE user_id = ?)");
$patient_query->bind_param("i", $_SESSION['user_id']);
$patient_query->execute();
$patient_result = $patient_query->get_result();

if ($patient_result->num_rows == 0) {
    exit('Patient record not found');
}

$patient_data = $patient_result->fetch_assoc();
$patient_id = $patient_data['patient_id'];

// Get visit details with security check
$visit_query = $mysqli->prepare("
    SELECT av.*, pr.lmp_date, pr.expected_delivery_date, pr.pregnancy_id,
           CONCAT(u.first_name, ' ', u.last_name) as doctor_name, u.role as doctor_role
    FROM anc_visits av
    JOIN pregnancies pr ON av.pregnancy_id = pr.pregnancy_id
    LEFT JOIN users u ON av.recorded_by = u.user_id
    WHERE av.visit_id = ? AND pr.patient_id = ?
");
$visit_query->bind_param("ii", $visit_id, $patient_id);
$visit_query->execute();
$visit = $visit_query->get_result()->fetch_assoc();

if (!$visit) {
    exit('Visit not found');
}
?>

<div style="margin-bottom: 1rem;">
    <p><strong>Visit Date:</strong> <?php echo date('M d, Y', strtotime($visit['visit_date'])); ?></p>
    <p><strong>Pregnancy:</strong> PR<?php echo $visit['pregnancy_id']; ?> (EDD: <?php echo date('M d, Y', strtotime($visit['expected_delivery_date'])); ?>)</p>
    <p><strong>Gestational Age:</strong> <?php echo $visit['gestational_age_weeks']; ?> weeks <?php echo $visit['gestational_age_days']; ?> days</p>
    <p><strong>Attended By:</strong> <?php echo $visit['doctor_name']; ?> (<?php echo $visit['doctor_role']; ?>)</p>
</div>

<div style="background: #f8f9fa; padding: 1rem; border-radius: 4px; margin: 1rem 0;">
    <h5 style="color: #0077B6; margin-bottom: 0.5rem;">Vital Signs</h5>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem;">
        <div>
            <strong>Blood Pressure:</strong><br>
            <?php echo $visit['blood_pressure_systolic']; ?>/<?php echo $visit['blood_pressure_diastolic']; ?> mmHg
            <?php 
            if ($visit['blood_pressure_systolic'] >= 140 || $visit['blood_pressure_diastolic'] >= 90) {
                echo '<br><small style="color: #dc3545;">⚠️ High - Monitor closely</small>';
            } elseif ($visit['blood_pressure_systolic'] < 90 || $visit['blood_pressure_diastolic'] < 60) {
                echo '<br><small style="color: #ffc107;">⚠️ Low</small>';
            } else {
                echo '<br><small style="color: #28a745;">✓ Normal</small>';
            }
            ?>
        </div>
        <div>
            <strong>Weight:</strong><br>
            <?php echo $visit['weight_kg']; ?> kg
        </div>
        <div>
            <strong>Fetal Heart Rate:</strong><br>
            <?php echo $visit['fetal_heart_rate']; ?> bpm
            <?php 
            if ($visit['fetal_heart_rate'] < 110 || $visit['fetal_heart_rate'] > 160) {
                echo '<br><small style="color: #dc3545;">⚠️ Abnormal</small>';
            } else {
                echo '<br><small style="color: #28a745;">✓ Normal</small>';
            }
            ?>
        </div>
        <div>
            <strong>Fundal Height:</strong><br>
            <?php echo $visit['fundal_height_cm']; ?> cm
            <?php 
            $expected_fh = $visit['gestational_age_weeks'];
            $fh_diff = abs($visit['fundal_height_cm'] - $expected_fh);
            if ($fh_diff > 2) {
                echo '<br><small style="color: #ffc107;">±' . $fh_diff . 'cm from expected</small>';
            } else {
                echo '<br><small style="color: #28a745;">✓ Within normal range</small>';
            }
            ?>
        </div>
    </div>
</div>

<?php if ($visit['symptoms_complaints']): ?>
<div style="background: #fff3cd; padding: 1rem; border-radius: 4px; margin: 1rem 0;">
    <h5 style="color: #856404; margin-bottom: 0.5rem;">Your Reported Symptoms</h5>
    <p><?php echo nl2br(htmlspecialchars($visit['symptoms_complaints'])); ?></p>
</div>
<?php endif; ?>

<div style="background: #d4edda; padding: 1rem; border-radius: 4px; margin: 1rem 0;">
    <h5 style="color: #155724; margin-bottom: 0.5rem;">Clinical Assessment</h5>
    <p><?php echo nl2br(htmlspecialchars($visit['assessment_notes'])); ?></p>
</div>

<div style="background: #d1ecf1; padding: 1rem; border-radius: 4px; margin: 1rem 0;">
    <h5 style="color: #0c5460; margin-bottom: 0.5rem;">Care Plan & Recommendations</h5>
    <p><?php echo nl2br(htmlspecialchars($visit['plan_notes'])); ?></p>
</div>

<?php if ($visit['next_visit_date']): ?>
<div style="background: #e2e3e5; padding: 1rem; border-radius: 4px; margin: 1rem 0;">
    <h5 style="color: #383d41; margin-bottom: 0.5rem;">Next Appointment</h5>
    <p><strong>Scheduled for:</strong> <?php echo date('M d, Y', strtotime($visit['next_visit_date'])); ?></p>
    <?php if (strtotime($visit['next_visit_date']) <= strtotime('+1 week')): ?>
        <small style="color: #0077B6;">📅 Your next appointment is coming up soon!</small>
    <?php endif; ?>
</div>
<?php endif; ?>

<div style="margin-top: 1rem; text-align: center;">
    <small style="color: #6c757d;">
        Visit recorded on <?php echo date('M d, Y H:i', strtotime($visit['created_at'])); ?>
    </small>
</div>