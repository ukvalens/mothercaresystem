<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Doctor', 'Nurse'])) {
    header('Location: ../auth/login.php');
    exit();
}

$page_title = 'Delivery Management';
$page_header = '👶 Delivery Management';
$show_nav = true;
$breadcrumb = [
    ['title' => 'Deliveries']
];

include '../layouts/header.php';

// Get delivery statistics
$stats_query = $mysqli->query("
    SELECT 
        COUNT(CASE WHEN pr.current_status = 'Active' AND DATEDIFF(CURDATE(), pr.lmp_date) >= 266 THEN 1 END) as near_delivery,
        COUNT(CASE WHEN pr.current_status = 'Active' AND DATEDIFF(CURDATE(), pr.lmp_date) >= 294 THEN 1 END) as overdue,
        COUNT(CASE WHEN pr.current_status = 'Completed' AND DATE(pr.updated_at) = CURDATE() THEN 1 END) as today_deliveries,
        COUNT(CASE WHEN pr.current_status = 'Completed' AND WEEK(pr.updated_at) = WEEK(CURDATE()) THEN 1 END) as week_deliveries
    FROM pregnancies pr
");
$stats = $stats_query->fetch_assoc();

// Get pregnancies near delivery (38+ weeks)
$near_delivery = $mysqli->query("
    SELECT pr.*, p.first_name, p.last_name, p.contact_number,
           DATEDIFF(CURDATE(), pr.lmp_date) DIV 7 as current_weeks
    FROM pregnancies pr
    JOIN patients p ON pr.patient_id = p.patient_id
    WHERE pr.current_status = 'Active' 
    AND DATEDIFF(CURDATE(), pr.lmp_date) >= 266
    ORDER BY pr.expected_delivery_date ASC
");

// Get recent deliveries
$recent_deliveries = $mysqli->query("
    SELECT pr.*, p.first_name, p.last_name,
           DATEDIFF(CURDATE(), pr.lmp_date) DIV 7 as delivery_weeks
    FROM pregnancies pr
    JOIN patients p ON pr.patient_id = p.patient_id
    WHERE pr.current_status = 'Completed'
    ORDER BY pr.updated_at DESC
    LIMIT 10
");
?>

<div class="container">
    <!-- Header Section -->
    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 2rem; border-radius: 10px; margin-bottom: 2rem; text-align: center;">
        <h2 style="margin: 0 0 0.5rem 0;">👶 Delivery Management Center</h2>
        <p style="margin: 0; opacity: 0.9;">Monitor pregnancies, record deliveries, and manage maternal care</p>
    </div>
    
    <!-- Statistics Dashboard -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
        <div style="background: #fff3cd; padding: 1.5rem; border-radius: 8px; text-align: center; border-left: 4px solid #ffc107;">
            <div style="font-size: 2rem; font-weight: bold; color: #856404;"><?php echo $stats['near_delivery']; ?></div>
            <div style="color: #856404; font-weight: 500;">Near Delivery (38+ weeks)</div>
        </div>
        <div style="background: #f8d7da; padding: 1.5rem; border-radius: 8px; text-align: center; border-left: 4px solid #dc3545;">
            <div style="font-size: 2rem; font-weight: bold; color: #721c24;"><?php echo $stats['overdue']; ?></div>
            <div style="color: #721c24; font-weight: 500;">Overdue (42+ weeks)</div>
        </div>
        <div style="background: #d1ecf1; padding: 1.5rem; border-radius: 8px; text-align: center; border-left: 4px solid #17a2b8;">
            <div style="font-size: 2rem; font-weight: bold; color: #0c5460;"><?php echo $stats['today_deliveries']; ?></div>
            <div style="color: #0c5460; font-weight: 500;">Today's Deliveries</div>
        </div>
        <div style="background: #d4edda; padding: 1.5rem; border-radius: 8px; text-align: center; border-left: 4px solid #28a745;">
            <div style="font-size: 2rem; font-weight: bold; color: #155724;"><?php echo $stats['week_deliveries']; ?></div>
            <div style="color: #155724; font-weight: 500;">This Week</div>
        </div>
    </div>
    
    <div style="margin-bottom: 1rem;">
        <a href="../dashboard/index.php" class="btn btn-secondary">← Back to Dashboard</a>
        <a href="../patients/list.php" class="btn btn-secondary">View Patients</a>
        <button onclick="showTab('near')" class="btn" id="nearTab">Near Delivery</button>
        <button onclick="showTab('recent')" class="btn btn-secondary" id="recentTab">Recent Deliveries</button>
    </div>

    <!-- Near Delivery Patients -->
    <div id="nearContent" class="section">
        <h3 style="color: #0077B6; margin-bottom: 1rem;">👶 Patients Near Delivery (38+ weeks)</h3>
        
        <?php if ($stats['near_delivery'] > 0): ?>
        <div style="background: #fff3cd; color: #856404; border: 1px solid #ffeaa7; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
            <strong>⚠️ Priority Alert:</strong> <?php echo $stats['near_delivery']; ?> patient(s) are at 38+ weeks gestation and require close monitoring.
        </div>
        <?php endif; ?>
        
        <?php if ($near_delivery && $near_delivery->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Patient</th>
                        <th>Current Weeks</th>
                        <th>EDD</th>
                        <th>Risk Level</th>
                        <th>Contact</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($pregnancy = $near_delivery->fetch_assoc()): ?>
                        <tr>
                            <td>P<?php echo $pregnancy['patient_id']; ?> - <?php echo $pregnancy['first_name'] . ' ' . $pregnancy['last_name']; ?></td>
                            <td>
                                <?php echo $pregnancy['current_weeks']; ?> weeks
                                <?php if ($pregnancy['current_weeks'] >= 42): ?>
                                    <span style="color: #dc3545; font-weight: bold;">OVERDUE</span>
                                <?php elseif ($pregnancy['current_weeks'] >= 40): ?>
                                    <span style="color: #ffc107; font-weight: bold;">FULL TERM</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($pregnancy['expected_delivery_date'])); ?></td>
                            <td>
                                <span style="color: <?php echo $pregnancy['risk_level'] == 'High' ? '#dc3545' : ($pregnancy['risk_level'] == 'Medium' ? '#ffc107' : '#28a745'); ?>">
                                    <?php echo $pregnancy['risk_level']; ?>
                                </span>
                            </td>
                            <td><?php echo $pregnancy['contact_number']; ?></td>
                            <td>
                                <a href="../patients/view.php?id=<?php echo $pregnancy['patient_id']; ?>" class="btn" style="font-size: 12px; padding: 0.3rem 0.6rem;">👁️ View</a>
                                <button onclick="recordDelivery(<?php echo $pregnancy['pregnancy_id']; ?>, '<?php echo $pregnancy['first_name'] . ' ' . $pregnancy['last_name']; ?>')" class="btn" style="background: #28a745; font-size: 12px; padding: 0.3rem 0.6rem;">👶 Record Delivery</button>
                                <?php if ($pregnancy['contact_number']): ?>
                                <button onclick="callPatient('<?php echo $pregnancy['contact_number']; ?>')" class="btn" style="background: #17a2b8; font-size: 12px; padding: 0.3rem 0.6rem;">📞 Call</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p style="text-align: center; color: #6c757d; padding: 2rem;">No patients currently near delivery.</p>
        <?php endif; ?>
    </div>

    <!-- Recent Deliveries -->
    <div id="recentContent" class="section" style="display: none;">
        <h3 style="color: #0077B6; margin-bottom: 1rem;">📋 Recent Deliveries</h3>
        
        <?php if ($recent_deliveries && $recent_deliveries->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Patient</th>
                        <th>Delivery Week</th>
                        <th>EDD</th>
                        <th>Status</th>
                        <th>Completed</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($delivery = $recent_deliveries->fetch_assoc()): ?>
                        <tr>
                            <td>P<?php echo $delivery['patient_id']; ?> - <?php echo $delivery['first_name'] . ' ' . $delivery['last_name']; ?></td>
                            <td><?php echo $delivery['delivery_weeks']; ?> weeks</td>
                            <td><?php echo date('M d, Y', strtotime($delivery['expected_delivery_date'])); ?></td>
                            <td>
                                <span style="background: #28a745; color: white; padding: 0.25rem 0.5rem; border-radius: 12px; font-size: 0.75rem;">
                                    Delivered
                                </span>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($delivery['updated_at'])); ?></td>
                            <td>
                                <a href="../patients/view.php?id=<?php echo $delivery['patient_id']; ?>" class="btn" style="font-size: 12px; padding: 0.3rem 0.6rem;">👁️ View</a>
                                <button onclick="viewDeliveryDetails(<?php echo $delivery['patient_id']; ?>)" class="btn" style="background: #6f42c1; font-size: 12px; padding: 0.3rem 0.6rem;">📄 Details</button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p style="text-align: center; color: #6c757d; padding: 2rem;">No recent deliveries recorded.</p>
        <?php endif; ?>
    </div>

    <!-- Delivery Recording Modal -->
    <div id="deliveryModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 2rem; border-radius: 8px; max-width: 500px; width: 90%;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <h4 style="color: #0077B6;">👶 Record Delivery</h4>
                <button onclick="closeDeliveryModal()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer;">&times;</button>
            </div>
            <div id="patientInfo" style="background: #f8f9fa; padding: 1rem; border-radius: 4px; margin-bottom: 1rem; display: none;">
                <strong>Patient:</strong> <span id="patientName"></span>
            </div>
            <form id="deliveryForm" onsubmit="submitDelivery(event)">
                <input type="hidden" id="pregnancyId" name="pregnancy_id">
                <div class="form-group">
                    <label>Delivery Date *</label>
                    <input type="date" name="delivery_date" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
                <div class="form-group">
                    <label>Delivery Time *</label>
                    <input type="time" name="delivery_time" required>
                </div>
                <div class="form-group">
                    <label>Delivery Type *</label>
                    <select name="delivery_type" required onchange="toggleComplicationsField(this.value)">
                        <option value="">Select Type</option>
                        <option value="Normal Vaginal Delivery">Normal Vaginal Delivery</option>
                        <option value="Cesarean Section">Cesarean Section</option>
                        <option value="Assisted Vacuum">Assisted Vacuum</option>
                        <option value="Assisted Forceps">Assisted Forceps</option>
                        <option value="Breech Delivery">Breech Delivery</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Baby Gender</label>
                    <select name="baby_gender">
                        <option value="">Select Gender</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                    </select>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Birth Weight (kg)</label>
                        <input type="number" step="0.01" name="birth_weight" min="0.5" max="6" placeholder="e.g., 3.2">
                    </div>
                    <div class="form-group">
                        <label>APGAR Score (1 min)</label>
                        <input type="number" name="apgar_1min" min="0" max="10" placeholder="0-10">
                    </div>
                </div>
                <div class="form-group" id="complicationsField">
                    <label>Complications/Notes</label>
                    <textarea name="complications" rows="3" placeholder="Any delivery complications, observations, or special notes"></textarea>
                </div>
                <button type="submit" class="btn">Record Delivery</button>
                <button type="button" onclick="closeDeliveryModal()" class="btn btn-secondary">Cancel</button>
            </form>
        </div>
    </div>
</div>

<script>
function recordDelivery(pregnancyId, patientName) {
    document.getElementById('pregnancyId').value = pregnancyId;
    document.getElementById('patientName').textContent = patientName;
    document.getElementById('patientInfo').style.display = 'block';
    document.getElementById('deliveryModal').style.display = 'block';
}

function showTab(tab) {
    if (tab === 'near') {
        document.getElementById('nearContent').style.display = 'block';
        document.getElementById('recentContent').style.display = 'none';
        document.getElementById('nearTab').className = 'btn';
        document.getElementById('recentTab').className = 'btn btn-secondary';
    } else {
        document.getElementById('nearContent').style.display = 'none';
        document.getElementById('recentContent').style.display = 'block';
        document.getElementById('nearTab').className = 'btn btn-secondary';
        document.getElementById('recentTab').className = 'btn';
    }
}

function callPatient(phoneNumber) {
    if (confirm('Call patient at ' + phoneNumber + '?')) {
        window.open('tel:' + phoneNumber);
    }
}

function viewDeliveryDetails(patientId) {
    window.open('../patients/view.php?id=' + patientId + '#delivery', '_blank');
}

function toggleComplicationsField(deliveryType) {
    const field = document.getElementById('complicationsField');
    const textarea = field.querySelector('textarea');
    
    if (deliveryType === 'Cesarean Section' || deliveryType.includes('Assisted')) {
        field.style.display = 'block';
        textarea.required = true;
        textarea.placeholder = 'Please specify indication and any complications';
    } else {
        field.style.display = 'block';
        textarea.required = false;
        textarea.placeholder = 'Any delivery complications, observations, or special notes';
    }
}

function closeDeliveryModal() {
    document.getElementById('deliveryModal').style.display = 'none';
    document.getElementById('deliveryForm').reset();
}

function submitDelivery(event) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    
    fetch('record_delivery.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✅ Delivery recorded successfully!\n\nThe pregnancy status has been updated and notifications have been sent.');
            location.reload();
        } else {
            alert('❌ Error: ' + data.message);
        }
    })
    .catch(error => {
        alert('Error recording delivery');
    });
}

// Close modal when clicking outside
document.getElementById('deliveryModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeDeliveryModal();
    }
});
</script>

<?php include '../layouts/footer.php'; ?>