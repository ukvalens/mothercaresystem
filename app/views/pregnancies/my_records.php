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

$page_title = 'My Pregnancy Records';
$page_header = '🤱 My Pregnancy Records';
$show_nav = true;
$breadcrumb = [
    ['title' => 'Pregnancy Records']
];

include '../layouts/header.php';

// Get pregnancy records if patient exists
$pregnancies = null;
if (isset($patient_id)) {
    $pregnancies = $mysqli->query("
        SELECT pr.*, 
               COUNT(av.visit_id) as visit_count,
               MAX(av.visit_date) as last_visit_date,
               MIN(av.visit_date) as first_visit_date,
               DATEDIFF(CURDATE(), pr.lmp_date) DIV 7 as current_weeks,
               DATEDIFF(CURDATE(), pr.lmp_date) % 7 as current_days
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
            <p>Please contact the reception desk to create your patient profile before you can view your pregnancy records.</p>
            <a href="../dashboard/index.php" class="btn">← Back to Dashboard</a>
        </div>
    <?php else: ?>
        
    <div style="margin-bottom: 1rem;">
        <a href="../dashboard/index.php" class="btn btn-secondary">← Back to Dashboard</a>
        <a href="../visits/my_visits.php" class="btn btn-secondary">Visit History</a>
        <a href="../appointments/my_appointments.php" class="btn btn-secondary">My Appointments</a>
    </div>

    <?php if ($pregnancies && $pregnancies->num_rows > 0): ?>
        <?php while ($pregnancy = $pregnancies->fetch_assoc()): ?>
            <div class="section">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <h3 style="color: #0077B6;">Pregnancy #PR<?php echo $pregnancy['pregnancy_id']; ?></h3>
                    <span style="background: <?php echo $pregnancy['current_status'] == 'Active' ? '#28a745' : '#6c757d'; ?>; color: white; padding: 0.25rem 0.75rem; border-radius: 12px; font-size: 0.875rem;">
                        <?php echo $pregnancy['current_status']; ?>
                    </span>
                </div>

                <!-- Pregnancy Overview -->
                <div class="stats">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo date('M d, Y', strtotime($pregnancy['lmp_date'])); ?></div>
                        <div class="stat-label">Last Menstrual Period</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo date('M d, Y', strtotime($pregnancy['expected_delivery_date'])); ?></div>
                        <div class="stat-label">Expected Delivery Date</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $pregnancy['current_weeks']; ?>w <?php echo $pregnancy['current_days']; ?>d</div>
                        <div class="stat-label">Current Gestational Age</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number" style="color: <?php echo $pregnancy['risk_level'] == 'High' ? '#dc3545' : ($pregnancy['risk_level'] == 'Medium' ? '#ffc107' : '#28a745'); ?>"><?php echo $pregnancy['risk_level']; ?></div>
                        <div class="stat-label">Risk Level</div>
                    </div>
                </div>

                <!-- Pregnancy Details -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem; margin: 1rem 0;">
                    <div>
                        <h5 style="color: #0077B6; margin-bottom: 0.5rem;">Obstetric History</h5>
                        <p><strong>Gravida:</strong> <?php echo $pregnancy['gravida']; ?> (Total pregnancies)</p>
                        <p><strong>Parity:</strong> <?php echo $pregnancy['parity']; ?> (Live births)</p>
                        <p><strong>Blood Type:</strong> <?php echo $pregnancy['blood_type'] ?: 'Not specified'; ?></p>
                        <p><strong>Rh Factor:</strong> <?php echo $pregnancy['rh_factor'] ?: 'Not specified'; ?></p>
                    </div>
                    <div>
                        <h5 style="color: #0077B6; margin-bottom: 0.5rem;">Visit Summary</h5>
                        <p><strong>Total ANC Visits:</strong> <?php echo $pregnancy['visit_count']; ?></p>
                        <?php if ($pregnancy['first_visit_date']): ?>
                            <p><strong>First Visit:</strong> <?php echo date('M d, Y', strtotime($pregnancy['first_visit_date'])); ?></p>
                        <?php endif; ?>
                        <?php if ($pregnancy['last_visit_date']): ?>
                            <p><strong>Last Visit:</strong> <?php echo date('M d, Y', strtotime($pregnancy['last_visit_date'])); ?></p>
                        <?php endif; ?>
                        <p><strong>Registered:</strong> <?php echo date('M d, Y', strtotime($pregnancy['created_at'])); ?></p>
                    </div>
                </div>

                <?php if ($pregnancy['high_risk_conditions']): ?>
                <div style="background: #f8d7da; padding: 1rem; border-radius: 4px; margin: 1rem 0; border-left: 4px solid #dc3545;">
                    <h5 style="color: #721c24; margin-bottom: 0.5rem;">⚠️ High Risk Conditions</h5>
                    <p><?php echo nl2br(htmlspecialchars($pregnancy['high_risk_conditions'])); ?></p>
                </div>
                <?php endif; ?>

                <!-- Progress Timeline -->
                <?php if ($pregnancy['current_status'] == 'Active'): ?>
                <div style="background: #e3f2fd; padding: 1rem; border-radius: 4px; margin: 1rem 0;">
                    <h5 style="color: #0277bd; margin-bottom: 0.5rem;">📅 Pregnancy Progress</h5>
                    <?php 
                    $weeks_remaining = 40 - $pregnancy['current_weeks'];
                    $progress_percent = ($pregnancy['current_weeks'] / 40) * 100;
                    ?>
                    <div style="background: #fff; border-radius: 10px; height: 20px; margin: 0.5rem 0; overflow: hidden;">
                        <div style="background: linear-gradient(90deg, #4caf50, #2196f3); height: 100%; width: <?php echo min($progress_percent, 100); ?>%; transition: width 0.3s;"></div>
                    </div>
                    <p><strong>Progress:</strong> <?php echo round($progress_percent, 1); ?>% complete</p>
                    <?php if ($weeks_remaining > 0): ?>
                        <p><strong>Estimated weeks remaining:</strong> <?php echo $weeks_remaining; ?> weeks</p>
                    <?php else: ?>
                        <p style="color: #d32f2f;"><strong>⚠️ Past due date - Please contact your healthcare provider</strong></p>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <!-- Action Buttons -->
                <div style="margin-top: 1rem;">
                    <a href="../visits/my_visits.php" class="btn">View ANC Visits</a>
                    <a href="../appointments/my_appointments.php" class="btn btn-secondary">My Appointments</a>
                    <?php if ($pregnancy['current_status'] == 'Active'): ?>
                        <button onclick="showPregnancyTips(<?php echo $pregnancy['current_weeks']; ?>)" class="btn" style="background: #28a745;">Weekly Tips</button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="section" style="text-align: center; padding: 3rem;">
            <h4 style="color: #6c757d; margin-bottom: 1rem;">No Pregnancy Records Found</h4>
            <p style="color: #6c757d; margin-bottom: 2rem;">You don't have any pregnancy records yet. When you become pregnant, your healthcare provider will register your pregnancy in the system.</p>
            <a href="../appointments/my_appointments.php" class="btn">Book an Appointment</a>
        </div>
    <?php endif; ?>

    <!-- Tips Modal -->
    <div id="tipsModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 2rem; border-radius: 8px; max-width: 500px; width: 90%; max-height: 80%; overflow-y: auto;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <h4 style="color: #0077B6;">Weekly Pregnancy Tips</h4>
                <button onclick="closeTipsModal()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer;">&times;</button>
            </div>
            <div id="tipsContent">
                <!-- Content will be loaded here -->
            </div>
        </div>
    </div>

    <?php endif; ?>
</div>

<script>
function showPregnancyTips(weeks) {
    let tips = '';
    
    if (weeks <= 12) {
        tips = `
            <h5 style="color: #28a745;">First Trimester (Week ${weeks})</h5>
            <ul style="margin: 1rem 0; padding-left: 1.5rem;">
                <li>Take folic acid supplements (400-800 mcg daily)</li>
                <li>Avoid alcohol, smoking, and raw foods</li>
                <li>Get plenty of rest and stay hydrated</li>
                <li>Schedule your first prenatal appointment</li>
                <li>Start taking prenatal vitamins</li>
            </ul>
        `;
    } else if (weeks <= 28) {
        tips = `
            <h5 style="color: #2196f3;">Second Trimester (Week ${weeks})</h5>
            <ul style="margin: 1rem 0; padding-left: 1.5rem;">
                <li>Continue prenatal vitamins and healthy diet</li>
                <li>Stay active with gentle exercise</li>
                <li>Monitor baby's movements</li>
                <li>Consider childbirth education classes</li>
                <li>Schedule glucose screening test</li>
            </ul>
        `;
    } else {
        tips = `
            <h5 style="color: #ff9800;">Third Trimester (Week ${weeks})</h5>
            <ul style="margin: 1rem 0; padding-left: 1.5rem;">
                <li>Prepare your hospital bag</li>
                <li>Finalize birth plan with your doctor</li>
                <li>Monitor baby's movements daily</li>
                <li>Practice breathing exercises</li>
                <li>Prepare for breastfeeding</li>
                <li>Watch for signs of labor</li>
            </ul>
        `;
    }
    
    tips += `
        <div style="background: #f8f9fa; padding: 1rem; border-radius: 4px; margin-top: 1rem;">
            <strong>⚠️ Important:</strong> Always consult with your healthcare provider for personalized advice.
        </div>
    `;
    
    document.getElementById('tipsContent').innerHTML = tips;
    document.getElementById('tipsModal').style.display = 'block';
}

function closeTipsModal() {
    document.getElementById('tipsModal').style.display = 'none';
}

// Close modal when clicking outside
document.getElementById('tipsModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeTipsModal();
    }
});
</script>

<?php include '../layouts/footer.php'; ?>