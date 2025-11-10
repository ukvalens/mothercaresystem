<?php
session_start();
require_once '../../config/database.php';

if (!isset($_GET['id'])) {
    header('Location: anc_visit.php');
    exit();
}

$visit_id = $_GET['id'];

// Get visit details
$visit_query = $mysqli->prepare("
    SELECT av.*, p.first_name, p.last_name, p.patient_id, pr.lmp_date, pr.expected_delivery_date,
           CONCAT(u.first_name, ' ', u.last_name) as recorded_by_name, u.role as recorder_role
    FROM anc_visits av
    JOIN pregnancies pr ON av.pregnancy_id = pr.pregnancy_id
    JOIN patients p ON pr.patient_id = p.patient_id
    LEFT JOIN users u ON av.recorded_by = u.user_id
    WHERE av.visit_id = ?
");
$visit_query->bind_param("i", $visit_id);
$visit_query->execute();
$visit = $visit_query->get_result()->fetch_assoc();

if (!$visit) {
    header('Location: anc_visit.php');
    exit();
}

$page_title = 'ANC Visit Details';
$page_header = '🩺 ANC Visit Details';
$show_nav = true;
$breadcrumb = [
    ['title' => 'ANC Visits', 'url' => 'anc_visit.php'],
    ['title' => 'Visit Details']
];

include '../layouts/header.php';
?>

<div class="container">
    <div style="margin-bottom: 1rem;">
        <a href="anc_visit.php" class="btn btn-secondary">← Back to ANC Visits</a>
        <a href="../patients/view.php?id=<?php echo $visit['patient_id']; ?>" class="btn btn-secondary">View Patient</a>
    </div>

    <!-- Visit Header -->
    <div class="section">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
            <h3 style="color: #0077B6;">ANC Visit #AV<?php echo $visit['visit_id']; ?></h3>
            <div style="text-align: right;">
                <p><strong>Patient:</strong> <?php echo $visit['first_name'] . ' ' . $visit['last_name']; ?> (P<?php echo $visit['patient_id']; ?>)</p>
                <p><strong>Visit Date:</strong> <?php echo date('M d, Y', strtotime($visit['visit_date'])); ?></p>
            </div>
        </div>
    </div>

    <!-- Visit Information -->
    <div class="section">
        <h4 style="color: #0077B6; margin-bottom: 1rem;">Visit Information</h4>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem;">
            <div>
                <p><strong>Gestational Age:</strong> <?php echo $visit['gestational_age_weeks']; ?> weeks <?php echo $visit['gestational_age_days']; ?> days</p>
                <p><strong>Expected Delivery Date:</strong> <?php echo date('M d, Y', strtotime($visit['expected_delivery_date'])); ?></p>
                <p><strong>Recorded By:</strong> <?php echo $visit['recorded_by_name']; ?> (<?php echo $visit['recorder_role']; ?>)</p>
                <p><strong>Recorded On:</strong> <?php echo date('M d, Y H:i', strtotime($visit['created_at'])); ?></p>
            </div>
            <div>
                <?php if ($visit['next_visit_date']): ?>
                    <p><strong>Next Visit Scheduled:</strong> <?php echo date('M d, Y', strtotime($visit['next_visit_date'])); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Vital Signs -->
    <div class="section">
        <h4 style="color: #0077B6; margin-bottom: 1rem;">Vital Signs</h4>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
            <div class="stat-card">
                <div class="stat-number"><?php echo $visit['blood_pressure_systolic']; ?>/<?php echo $visit['blood_pressure_diastolic']; ?></div>
                <div class="stat-label">Blood Pressure (mmHg)</div>
                <?php 
                $bp_status = '';
                if ($visit['blood_pressure_systolic'] >= 140 || $visit['blood_pressure_diastolic'] >= 90) {
                    $bp_status = '<small style="color: #dc3545;">High BP - Monitor closely</small>';
                } elseif ($visit['blood_pressure_systolic'] < 90 || $visit['blood_pressure_diastolic'] < 60) {
                    $bp_status = '<small style="color: #ffc107;">Low BP</small>';
                } else {
                    $bp_status = '<small style="color: #28a745;">Normal</small>';
                }
                echo $bp_status;
                ?>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $visit['weight_kg']; ?></div>
                <div class="stat-label">Weight (kg)</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $visit['fetal_heart_rate']; ?></div>
                <div class="stat-label">Fetal Heart Rate (bpm)</div>
                <?php 
                $fhr_status = '';
                if ($visit['fetal_heart_rate'] < 110 || $visit['fetal_heart_rate'] > 160) {
                    $fhr_status = '<small style="color: #dc3545;">Abnormal - Requires attention</small>';
                } else {
                    $fhr_status = '<small style="color: #28a745;">Normal</small>';
                }
                echo $fhr_status;
                ?>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $visit['fundal_height_cm']; ?></div>
                <div class="stat-label">Fundal Height (cm)</div>
                <?php 
                $expected_fh = $visit['gestational_age_weeks'];
                $fh_diff = abs($visit['fundal_height_cm'] - $expected_fh);
                if ($fh_diff > 2) {
                    echo '<small style="color: #ffc107;">±' . $fh_diff . 'cm from expected</small>';
                } else {
                    echo '<small style="color: #28a745;">Within normal range</small>';
                }
                ?>
            </div>
        </div>
    </div>

    <!-- Laboratory Results -->
    <?php if ($visit['urine_protein'] || $visit['urine_glucose'] || $visit['hemoglobin_level']): ?>
    <div class="section">
        <h4 style="color: #0077B6; margin-bottom: 1rem;">Laboratory Results</h4>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
            <?php if ($visit['urine_protein']): ?>
            <div class="stat-card">
                <div class="stat-number"><?php echo $visit['urine_protein']; ?></div>
                <div class="stat-label">Urine Protein</div>
                <?php if (in_array($visit['urine_protein'], ['2+', '3+'])): ?>
                    <small style="color: #dc3545;">Elevated - Monitor for preeclampsia</small>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <?php if ($visit['urine_glucose']): ?>
            <div class="stat-card">
                <div class="stat-number"><?php echo $visit['urine_glucose']; ?></div>
                <div class="stat-label">Urine Glucose</div>
                <?php if (in_array($visit['urine_glucose'], ['1+', '2+'])): ?>
                    <small style="color: #ffc107;">Elevated - Screen for GDM</small>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <?php if ($visit['hemoglobin_level']): ?>
            <div class="stat-card">
                <div class="stat-number"><?php echo $visit['hemoglobin_level']; ?></div>
                <div class="stat-label">Hemoglobin (g/dL)</div>
                <?php 
                if ($visit['hemoglobin_level'] < 11) {
                    echo '<small style="color: #dc3545;">Low - Anemia</small>';
                } elseif ($visit['hemoglobin_level'] > 14) {
                    echo '<small style="color: #ffc107;">High</small>';
                } else {
                    echo '<small style="color: #28a745;">Normal</small>';
                }
                ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Clinical Notes -->
    <div class="section">
        <h4 style="color: #0077B6; margin-bottom: 1rem;">Clinical Notes</h4>
        
        <?php if ($visit['symptoms_complaints']): ?>
        <div style="margin-bottom: 1rem;">
            <h5 style="color: #495057;">Symptoms & Complaints</h5>
            <p style="background: #f8f9fa; padding: 1rem; border-radius: 4px; margin-top: 0.5rem;"><?php echo nl2br(htmlspecialchars($visit['symptoms_complaints'])); ?></p>
        </div>
        <?php endif; ?>
        
        <div style="margin-bottom: 1rem;">
            <h5 style="color: #495057;">Clinical Assessment</h5>
            <p style="background: #f8f9fa; padding: 1rem; border-radius: 4px; margin-top: 0.5rem;"><?php echo nl2br(htmlspecialchars($visit['assessment_notes'])); ?></p>
        </div>
        
        <?php if ($visit['complications_noted']): ?>
        <div style="margin-bottom: 1rem;">
            <h5 style="color: #dc3545;">Complications Noted</h5>
            <p style="background: #f8d7da; padding: 1rem; border-radius: 4px; margin-top: 0.5rem; border-left: 4px solid #dc3545;"><?php echo nl2br(htmlspecialchars($visit['complications_noted'])); ?></p>
        </div>
        <?php endif; ?>
        
        <div style="margin-bottom: 1rem;">
            <h5 style="color: #495057;">Plan & Recommendations</h5>
            <p style="background: #d4edda; padding: 1rem; border-radius: 4px; margin-top: 0.5rem; border-left: 4px solid #28a745;"><?php echo nl2br(htmlspecialchars($visit['plan_notes'])); ?></p>
        </div>
    </div>

    <!-- Actions -->
    <div class="section">
        <h4 style="color: #0077B6; margin-bottom: 1rem;">Actions</h4>
        <a href="../pregnancies/view.php?id=<?php echo $visit['pregnancy_id']; ?>" class="btn">View Pregnancy Record</a>
        <?php if ($visit['next_visit_date'] && strtotime($visit['next_visit_date']) <= strtotime('+1 week')): ?>
            <a href="anc_visit.php?pregnancy_id=<?php echo $visit['pregnancy_id']; ?>" class="btn" style="background: #28a745;">Schedule Follow-up Visit</a>
        <?php endif; ?>
        <?php if (in_array($_SESSION['role'], ['Doctor', 'Nurse'])): ?>
            <a href="anc_visit.php?pregnancy_id=<?php echo $visit['pregnancy_id']; ?>" class="btn">Record New Visit</a>
        <?php endif; ?>
    </div>
</div>

<?php include '../layouts/footer.php'; ?>