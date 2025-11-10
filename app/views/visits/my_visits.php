<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Patient') {
    header('Location: ../auth/login.php');
    exit();
}

// Get patient record for current user
$patient_query = $mysqli->prepare("SELECT patient_id FROM patients WHERE nid = (SELECT nid FROM users WHERE user_id = ?)");
$patient_query->bind_param("i", $_SESSION['user_id']);
$patient_query->execute();
$patient_result = $patient_query->get_result();

if ($patient_result->num_rows == 0) {
    $error = "No patient record found. Please contact reception to create your patient profile.";
} else {
    $patient_data = $patient_result->fetch_assoc();
    $patient_id = $patient_data['patient_id'];
}

$page_title = 'My Visit History';
$page_header = '📋 My Visit History';
$show_nav = true;
$breadcrumb = [
    ['title' => 'Visit History']
];

include '../layouts/header.php';

// Get visit history if patient exists
$visits = null;
$pregnancies = null;
if (isset($patient_id)) {
    // Get all visits for this patient
    $visits = $mysqli->query("
        SELECT av.*, pr.lmp_date, pr.expected_delivery_date, pr.pregnancy_id,
               CONCAT(u.first_name, ' ', u.last_name) as doctor_name, u.role as doctor_role
        FROM anc_visits av
        JOIN pregnancies pr ON av.pregnancy_id = pr.pregnancy_id
        LEFT JOIN users u ON av.recorded_by = u.user_id
        WHERE pr.patient_id = $patient_id
        ORDER BY av.visit_date DESC, av.created_at DESC
    ");
    
    // Get pregnancy summary
    $pregnancies = $mysqli->query("
        SELECT pr.*, COUNT(av.visit_id) as visit_count,
               MAX(av.visit_date) as last_visit_date,
               MIN(av.visit_date) as first_visit_date
        FROM pregnancies pr
        LEFT JOIN anc_visits av ON pr.pregnancy_id = av.pregnancy_id
        WHERE pr.patient_id = $patient_id
        GROUP BY pr.pregnancy_id
        ORDER BY pr.created_at DESC
    ");
}
?>

<div class="container">
    <?php if (isset($error)): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
        <div class="section">
            <p>Please contact the reception desk to create your patient profile before you can view your visit history.</p>
            <a href="../dashboard/index.php" class="btn">← Back to Dashboard</a>
        </div>
    <?php else: ?>
        
    <div style="margin-bottom: 1rem;">
        <a href="../dashboard/index.php" class="btn btn-secondary">← Back to Dashboard</a>
        <a href="../pregnancies/my_records.php" class="btn btn-secondary">My Pregnancies</a>
        <a href="../appointments/my_appointments.php" class="btn btn-secondary">My Appointments</a>
    </div>

    <!-- Pregnancy Summary -->
    <div class="section">
        <h3 style="color: #0077B6; margin-bottom: 1rem;">Pregnancy Summary</h3>
        <?php if ($pregnancies && $pregnancies->num_rows > 0): ?>
            <div class="stats">
                <?php while ($pregnancy = $pregnancies->fetch_assoc()): ?>
                    <div class="stat-card">
                        <div class="stat-number">PR<?php echo $pregnancy['pregnancy_id']; ?></div>
                        <div class="stat-label">
                            <strong>EDD:</strong> <?php echo date('M d, Y', strtotime($pregnancy['expected_delivery_date'])); ?><br>
                            <strong>Status:</strong> <?php echo $pregnancy['current_status']; ?><br>
                            <strong>Visits:</strong> <?php echo $pregnancy['visit_count']; ?><br>
                            <?php if ($pregnancy['last_visit_date']): ?>
                                <strong>Last Visit:</strong> <?php echo date('M d, Y', strtotime($pregnancy['last_visit_date'])); ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <p>No pregnancy records found.</p>
        <?php endif; ?>
    </div>

    <!-- Visit History -->
    <div class="section">
        <h3 style="color: #0077B6; margin-bottom: 1rem;">ANC Visit History</h3>
        
        <?php if ($visits && $visits->num_rows > 0): ?>
            <div style="margin-bottom: 1rem;">
                <input type="text" placeholder="Search visits..." id="searchVisits" onkeyup="searchVisits()" style="padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px; width: 300px;">
            </div>
            
            <table id="visitsTable">
                <thead>
                    <tr>
                        <th>Visit Date</th>
                        <th>Pregnancy</th>
                        <th>Gestational Age</th>
                        <th>Blood Pressure</th>
                        <th>Weight</th>
                        <th>Fetal Heart Rate</th>
                        <th>Attended By</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($visit = $visits->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo date('M d, Y', strtotime($visit['visit_date'])); ?></td>
                            <td>PR<?php echo $visit['pregnancy_id']; ?><br>
                                <small>EDD: <?php echo date('M d, Y', strtotime($visit['expected_delivery_date'])); ?></small>
                            </td>
                            <td><?php echo $visit['gestational_age_weeks']; ?>w <?php echo $visit['gestational_age_days']; ?>d</td>
                            <td>
                                <?php echo $visit['blood_pressure_systolic']; ?>/<?php echo $visit['blood_pressure_diastolic']; ?>
                                <?php 
                                if ($visit['blood_pressure_systolic'] >= 140 || $visit['blood_pressure_diastolic'] >= 90) {
                                    echo '<br><small style="color: #dc3545;">High</small>';
                                } elseif ($visit['blood_pressure_systolic'] < 90 || $visit['blood_pressure_diastolic'] < 60) {
                                    echo '<br><small style="color: #ffc107;">Low</small>';
                                } else {
                                    echo '<br><small style="color: #28a745;">Normal</small>';
                                }
                                ?>
                            </td>
                            <td><?php echo $visit['weight_kg']; ?> kg</td>
                            <td>
                                <?php echo $visit['fetal_heart_rate']; ?> bpm
                                <?php 
                                if ($visit['fetal_heart_rate'] < 110 || $visit['fetal_heart_rate'] > 160) {
                                    echo '<br><small style="color: #dc3545;">Abnormal</small>';
                                } else {
                                    echo '<br><small style="color: #28a745;">Normal</small>';
                                }
                                ?>
                            </td>
                            <td><?php echo $visit['doctor_name']; ?><br>
                                <small><?php echo $visit['doctor_role']; ?></small>
                            </td>
                            <td>
                                <button onclick="showVisitDetails(<?php echo $visit['visit_id']; ?>)" class="btn" style="font-size: 12px; padding: 0.3rem 0.6rem;">View Details</button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div style="text-align: center; padding: 2rem;">
                <p style="color: #6c757d; margin-bottom: 1rem;">No ANC visits recorded yet.</p>
                <p>Your visit history will appear here after your first ANC appointment.</p>
                <a href="../appointments/my_appointments.php" class="btn">View My Appointments</a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Visit Details Modal -->
    <div id="visitModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 2rem; border-radius: 8px; max-width: 600px; width: 90%; max-height: 80%; overflow-y: auto;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <h4 style="color: #0077B6;">Visit Details</h4>
                <button onclick="closeModal()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer;">&times;</button>
            </div>
            <div id="visitDetailsContent">
                <!-- Content will be loaded here -->
            </div>
        </div>
    </div>

    <?php endif; ?>
</div>

<script>
function searchVisits() {
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

function showVisitDetails(visitId) {
    // Fetch visit details via AJAX
    fetch(`get_visit_details.php?id=${visitId}`)
        .then(response => response.text())
        .then(data => {
            document.getElementById('visitDetailsContent').innerHTML = data;
            document.getElementById('visitModal').style.display = 'block';
        })
        .catch(error => {
            document.getElementById('visitDetailsContent').innerHTML = '<p>Error loading visit details.</p>';
            document.getElementById('visitModal').style.display = 'block';
        });
}

function closeModal() {
    document.getElementById('visitModal').style.display = 'none';
}

// Close modal when clicking outside
document.getElementById('visitModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal();
    }
});
</script>

<?php include '../layouts/footer.php'; ?>