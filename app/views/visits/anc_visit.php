<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Doctor', 'Nurse'])) {
    header('Location: ../auth/login.php');
    exit();
}
require_once '../../config/database.php';
require_once '../../config/activity_hooks.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $pregnancy_id = $_POST['pregnancy_id'];
    $visit_date = $_POST['visit_date'];
    $gestational_weeks = $_POST['gestational_weeks'];
    $gestational_days = $_POST['gestational_days'];
    $bp_systolic = $_POST['bp_systolic'];
    $bp_diastolic = $_POST['bp_diastolic'];
    $weight = $_POST['weight'];
    $fetal_heart_rate = $_POST['fetal_heart_rate'];
    $fundal_height = $_POST['fundal_height'];
    $symptoms = trim($_POST['symptoms']);
    $assessment = trim($_POST['assessment']);
    $plan = trim($_POST['plan']);
    $next_visit = $_POST['next_visit'];


    $stmt = $mysqli->prepare("INSERT INTO anc_visits (pregnancy_id, visit_date, gestational_age_weeks, gestational_age_days, blood_pressure_systolic, blood_pressure_diastolic, weight_kg, fetal_heart_rate, fundal_height_cm, symptoms_complaints, assessment_notes, plan_notes, next_visit_date, recorded_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isiiiidissssii", $pregnancy_id, $visit_date, $gestational_weeks, $gestational_days, $bp_systolic, $bp_diastolic, $weight, $fetal_heart_rate, $fundal_height, $symptoms, $assessment, $plan, $next_visit, $_SESSION['user_id']);
    
    if ($stmt->execute()) {
        $visit_id = $mysqli->insert_id;
        $success = "ANC visit recorded successfully! Visit ID: AV" . $visit_id;
        
        // Send notifications
        hook_anc_visit_recorded($visit_id, $_SESSION['user_id']);
    } else {
        $error = "Error recording visit: " . $mysqli->error;
    }
}

$page_title = 'ANC Visits';
$page_header = '🩺 ANC Visits';
$show_nav = true;
$breadcrumb = [
    ['title' => 'ANC Visits']
];

include '../layouts/header.php';

// Get recent visits for display
$recent_visits = $mysqli->query("
    SELECT av.*, p.first_name, p.last_name, pr.expected_delivery_date, 
           CONCAT(u.first_name, ' ', u.last_name) as recorded_by_name
    FROM anc_visits av
    JOIN pregnancies pr ON av.pregnancy_id = pr.pregnancy_id
    JOIN patients p ON pr.patient_id = p.patient_id
    LEFT JOIN users u ON av.recorded_by = u.user_id
    ORDER BY av.visit_date DESC, av.created_at DESC
    LIMIT 20
");
?>

<div class="container">
    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    <?php if (isset($error)): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <div style="margin-bottom: 1rem;">
        <a href="../dashboard/index.php" class="btn btn-secondary">← Back to Dashboard</a>
        <a href="../patients/list.php" class="btn btn-secondary">View Patients</a>
    </div>

    <!-- Tabs -->
    <div style="margin-bottom: 1rem;">
        <button onclick="showTab('record')" class="btn" id="recordTab">Record New Visit</button>
        <button onclick="showTab('history')" class="btn btn-secondary" id="historyTab">Visit History</button>
    </div>

    <!-- Record New Visit Tab -->
    <div id="recordContent" class="section">
        <h3 style="color: #0077B6; margin-bottom: 1rem;">Record New ANC Visit</h3>
        
        <form method="POST" id="ancForm">
            <div class="form-group">
                <label>Pregnancy *</label>
                <select name="pregnancy_id" required onchange="loadPregnancyInfo(this.value)">
                    <option value="">Select Pregnancy</option>
                    <?php
                    $pregnancies = $mysqli->query("
                        SELECT p.pregnancy_id, pt.first_name, pt.last_name, p.expected_delivery_date, p.lmp_date,
                               pt.patient_id, p.gestational_age_weeks, p.gestational_age_days
                        FROM pregnancies p 
                        JOIN patients pt ON p.patient_id = pt.patient_id 
                        WHERE p.current_status = 'Active'
                        ORDER BY pt.first_name
                    ");
                    while ($preg = $pregnancies->fetch_assoc()) {
                        echo "<option value='{$preg['pregnancy_id']}' data-weeks='{$preg['gestational_age_weeks']}' data-days='{$preg['gestational_age_days']}' data-patient='{$preg['patient_id']}'>P{$preg['patient_id']} - {$preg['first_name']} {$preg['last_name']} (EDD: " . date('M d, Y', strtotime($preg['expected_delivery_date'])) . ")</option>";
                    }
                    ?>
                </select>
            </div>
            
            <div id="pregnancyInfo" style="background: #e3f2fd; padding: 1rem; border-radius: 4px; margin: 1rem 0; display: none;">
                <h4 style="color: #0077B6;">Pregnancy Information</h4>
                <div id="pregnancyDetails"></div>
            </div>

            <div style="background: #f8f9fa; padding: 1rem; border-radius: 4px; margin: 1rem 0;">
                <h4 style="color: #0077B6; margin-bottom: 1rem;">Visit Information</h4>
                <div class="form-row">
                    <div class="form-group">
                        <label>Visit Date *</label>
                        <input type="date" name="visit_date" value="<?php echo date('Y-m-d'); ?>" required onchange="calculateGestationalAge()">
                    </div>
                    <div class="form-group">
                        <label>Gestational Age (Weeks) *</label>
                        <input type="number" name="gestational_weeks" min="0" max="42" id="gestWeeks" required>
                    </div>
                    <div class="form-group">
                        <label>Days</label>
                        <input type="number" name="gestational_days" min="0" max="6" id="gestDays">
                    </div>
                </div>
            </div>

            <div style="background: #f8f9fa; padding: 1rem; border-radius: 4px; margin: 1rem 0;">
                <h4 style="color: #0077B6; margin-bottom: 1rem;">Vital Signs</h4>
                <div class="form-row">
                    <div class="form-group">
                        <label>Blood Pressure (Systolic) *</label>
                        <input type="number" name="bp_systolic" placeholder="120" min="80" max="200" required>
                        <small>Normal: 90-140 mmHg</small>
                    </div>
                    <div class="form-group">
                        <label>Blood Pressure (Diastolic) *</label>
                        <input type="number" name="bp_diastolic" placeholder="80" min="50" max="120" required>
                        <small>Normal: 60-90 mmHg</small>
                    </div>
                    <div class="form-group">
                        <label>Weight (kg) *</label>
                        <input type="number" step="0.1" name="weight" min="30" max="150" required>
                    </div>
                </div>
            </div>

            <div style="background: #f8f9fa; padding: 1rem; border-radius: 4px; margin: 1rem 0;">
                <h4 style="color: #0077B6; margin-bottom: 1rem;">Fetal Assessment</h4>
                <div class="form-row">
                    <div class="form-group">
                        <label>Fetal Heart Rate (bpm) *</label>
                        <input type="number" name="fetal_heart_rate" placeholder="140" min="110" max="180" required>
                        <small>Normal: 110-160 bpm</small>
                    </div>
                    <div class="form-group">
                        <label>Fundal Height (cm) *</label>
                        <input type="number" name="fundal_height" min="10" max="45" required>
                        <small>Should match gestational weeks ±2cm</small>
                    </div>
                </div>
            </div>
            


            <div style="background: #f8f9fa; padding: 1rem; border-radius: 4px; margin: 1rem 0;">
                <h4 style="color: #0077B6; margin-bottom: 1rem;">Clinical Assessment</h4>
                <div class="form-group">
                    <label>Symptoms & Complaints</label>
                    <textarea name="symptoms" rows="2" placeholder="Patient's reported symptoms, concerns, or complaints"></textarea>
                </div>

                <div class="form-group">
                    <label>Clinical Assessment *</label>
                    <textarea name="assessment" rows="3" placeholder="Clinical findings, examination results, overall assessment" required></textarea>
                </div>



                <div class="form-group">
                    <label>Plan & Recommendations *</label>
                    <textarea name="plan" rows="3" placeholder="Treatment plan, medications, lifestyle recommendations" required></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Next Visit Date</label>
                        <input type="date" name="next_visit" min="<?php echo date('Y-m-d', strtotime('+1 week')); ?>">
                    </div>
                </div>
            </div>

            <button type="submit" class="btn">Record ANC Visit</button>
            <button type="reset" class="btn btn-secondary">Clear Form</button>
        </form>
    </div>

    <!-- Visit History Tab -->
    <div id="historyContent" class="section" style="display: none;">
        <h3 style="color: #0077B6; margin-bottom: 1rem;">Recent ANC Visits</h3>
        
        <div style="margin-bottom: 1rem;">
            <input type="text" placeholder="Search visits..." id="searchVisits" onkeyup="searchVisitHistory()" style="padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px; width: 300px;">
        </div>
        
        <table id="visitsTable">
            <thead>
                <tr>
                    <th>Visit ID</th>
                    <th>Patient</th>
                    <th>Visit Date</th>
                    <th>Gestational Age</th>
                    <th>BP</th>
                    <th>Weight</th>
                    <th>FHR</th>
                    <th>Recorded By</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($recent_visits && $recent_visits->num_rows > 0): ?>
                    <?php while ($visit = $recent_visits->fetch_assoc()): ?>
                        <tr>
                            <td>AV<?php echo $visit['visit_id']; ?></td>
                            <td><?php echo $visit['first_name'] . ' ' . $visit['last_name']; ?></td>
                            <td><?php echo date('M d, Y', strtotime($visit['visit_date'])); ?></td>
                            <td><?php echo $visit['gestational_age_weeks']; ?>w <?php echo $visit['gestational_age_days']; ?>d</td>
                            <td><?php echo $visit['blood_pressure_systolic']; ?>/<?php echo $visit['blood_pressure_diastolic']; ?></td>
                            <td><?php echo $visit['weight_kg']; ?> kg</td>
                            <td><?php echo $visit['fetal_heart_rate']; ?> bpm</td>
                            <td><?php echo $visit['recorded_by_name']; ?></td>
                            <td>
                                <a href="view_visit.php?id=<?php echo $visit['visit_id']; ?>" class="btn" style="font-size: 12px; padding: 0.3rem 0.6rem;">View</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="9">No ANC visits recorded yet</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function showTab(tab) {
    if (tab === 'record') {
        document.getElementById('recordContent').style.display = 'block';
        document.getElementById('historyContent').style.display = 'none';
        document.getElementById('recordTab').className = 'btn';
        document.getElementById('historyTab').className = 'btn btn-secondary';
    } else {
        document.getElementById('recordContent').style.display = 'none';
        document.getElementById('historyContent').style.display = 'block';
        document.getElementById('recordTab').className = 'btn btn-secondary';
        document.getElementById('historyTab').className = 'btn';
    }
}

function loadPregnancyInfo(pregnancyId) {
    const select = document.querySelector('select[name="pregnancy_id"]');
    const selectedOption = select.options[select.selectedIndex];
    const weeks = selectedOption.getAttribute('data-weeks');
    const days = selectedOption.getAttribute('data-days');
    
    if (pregnancyId) {
        document.getElementById('pregnancyInfo').style.display = 'block';
        document.getElementById('gestWeeks').value = weeks;
        document.getElementById('gestDays').value = days;
        calculateGestationalAge();
    } else {
        document.getElementById('pregnancyInfo').style.display = 'none';
    }
}

function calculateGestationalAge() {
    // Auto-calculate based on visit date if needed
    const visitDate = document.querySelector('input[name="visit_date"]').value;
    if (visitDate) {
        // Update gestational age based on visit date
        // This would require LMP date from pregnancy record
    }
}

function searchVisitHistory() {
    const input = document.getElementById('searchVisits');
    const filter = input.value.toUpperCase();
    const table = document.getElementById('visitsTable');
    const tr = table.getElementsByTagName('tr');

    for (let i = 1; i < tr.length; i++) {
        const td = tr[i].getElementsByTagName('td');
        let found = false;
        for (let j = 0; j < td.length; j++) {
            if (td[j] && td[j].innerHTML.toUpperCase().indexOf(filter) > -1) {
                found = true;
                break;
            }
        }
        tr[i].style.display = found ? '' : 'none';
    }
}
</script>

<?php include '../layouts/footer.php'; ?>