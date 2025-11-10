<?php
session_start();
require_once '../../config/database.php';

if ($_SESSION['role'] !== 'Patient') {
    header('Location: ../dashboard/index.php');
    exit();
}

$page_title = 'My Appointments';
$page_header = '📅 My Appointments';
$show_nav = true;

include '../layouts/header.php';

// Get or create patient record
$patient_query = $mysqli->prepare("SELECT patient_id FROM patients WHERE nid = (SELECT nid FROM users WHERE user_id = ?)");
$patient_query->bind_param("i", $_SESSION['user_id']);
$patient_query->execute();
$patient_result = $patient_query->get_result();
$patient = $patient_result->fetch_assoc();

if (!$patient) {
    $user_query = $mysqli->prepare("SELECT nid, first_name, last_name, email FROM users WHERE user_id = ?");
    $user_query->bind_param("i", $_SESSION['user_id']);
    $user_query->execute();
    $user_data = $user_query->get_result()->fetch_assoc();
    
    $create_patient = $mysqli->prepare("INSERT INTO patients (nid, first_name, last_name, email, registered_by) VALUES (?, ?, ?, ?, ?)");
    $create_patient->bind_param("ssssi", $user_data['nid'], $user_data['first_name'], $user_data['last_name'], $user_data['email'], $_SESSION['user_id']);
    $create_patient->execute();
    $patient_id = $mysqli->insert_id;
} else {
    $patient_id = $patient['patient_id'];
}

// Get appointment statistics
$stats = $mysqli->query("
    SELECT 
        COUNT(*) as total_appointments,
        SUM(CASE WHEN appointment_date >= CURDATE() THEN 1 ELSE 0 END) as upcoming,
        SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN status = 'Cancelled' THEN 1 ELSE 0 END) as cancelled,
        SUM(CASE WHEN status = 'Scheduled' AND appointment_date >= CURDATE() THEN 1 ELSE 0 END) as pending
    FROM appointments 
    WHERE patient_id = $patient_id
")->fetch_assoc();
?>


    <div class="container">
        <!-- Header Section -->
        <div style="background: linear-gradient(135deg, #007cba 0%, #0056b3 100%); color: white; padding: 2rem; border-radius: 10px; margin-bottom: 2rem; text-align: center;">
            <h1 style="margin: 0 0 0.5rem 0;">📅 My Appointments</h1>
            <p style="margin: 0; opacity: 0.9;">Manage your healthcare appointments and schedule</p>
        </div>
        
        <!-- Navigation -->
        <div style="margin-bottom: 2rem; display: flex; gap: 1rem; flex-wrap: wrap;">
            <a href="../dashboard/index.php" class="btn btn-secondary">← Dashboard</a>
            <a href="book.php" class="btn" style="background: #28a745;">📅 Book New Appointment</a>
            <a href="../visits/my_visits.php" class="btn btn-secondary">🏥 Visit History</a>
            <a href="../payments/my_bills.php" class="btn btn-secondary">💳 My Bills</a>
        </div>
        
        <!-- Alerts -->
        <?php if (isset($_GET['cancelled'])): ?>
            <div class="alert alert-success">✅ Appointment cancelled successfully. The doctor has been notified.</div>
        <?php endif; ?>
        <?php if (isset($_GET['booked'])): ?>
            <div class="alert alert-success">✅ Appointment booked successfully. You will receive a confirmation.</div>
        <?php endif; ?>
        
        <!-- Statistics -->
        <div class="stats" style="margin-bottom: 2rem;">
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total_appointments']; ?></div>
                <div class="stat-label">Total Appointments</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" style="color: #007cba;"><?php echo $stats['upcoming']; ?></div>
                <div class="stat-label">Upcoming</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" style="color: #28a745;"><?php echo $stats['completed']; ?></div>
                <div class="stat-label">Completed</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" style="color: #ffc107;"><?php echo $stats['pending']; ?></div>
                <div class="stat-label">Pending</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" style="color: #dc3545;"><?php echo $stats['cancelled']; ?></div>
                <div class="stat-label">Cancelled</div>
            </div>
        </div>
        <!-- Upcoming Appointments -->
        <div class="section">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <h3 style="color: #007cba; margin: 0;">🔜 Upcoming Appointments</h3>
                <div style="display: flex; gap: 0.5rem;">
                    <input type="text" placeholder="Search appointments..." id="searchUpcoming" onkeyup="searchAppointments('upcoming')" style="padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px; width: 200px;">
                    <select id="statusFilter" onchange="filterByStatus('upcoming')" style="padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;">
                        <option value="all">All Status</option>
                        <option value="Scheduled">Scheduled</option>
                        <option value="Confirmed">Confirmed</option>
                        <option value="Cancelled">Cancelled</option>
                    </select>
                </div>
            </div>
            
            <?php
            $upcoming_appointments = $mysqli->query("
                SELECT a.*, u.first_name, u.last_name, u.specialization,
                       DATEDIFF(a.appointment_date, CURDATE()) as days_until
                FROM appointments a
                JOIN users u ON a.doctor_id = u.user_id
                WHERE a.patient_id = $patient_id AND a.appointment_date >= CURDATE()
                ORDER BY a.appointment_date, a.appointment_time
            ");
            
            if ($upcoming_appointments && $upcoming_appointments->num_rows > 0):
            ?>
                <div id="upcomingTable" style="max-height: 400px; overflow-y: auto;">
                    <?php while ($apt = $upcoming_appointments->fetch_assoc()): ?>
                        <div class="appointment-card" style="border: 1px solid #ddd; border-radius: 8px; padding: 1.5rem; margin-bottom: 1rem; background: <?php echo $apt['status'] == 'Confirmed' ? '#f0f8f0' : '#ffffff'; ?>; border-left: 4px solid <?php echo $apt['status'] == 'Confirmed' ? '#28a745' : ($apt['status'] == 'Scheduled' ? '#ffc107' : '#dc3545'); ?>;">
                            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
                                <div style="flex: 1;">
                                    <h4 style="margin: 0 0 0.5rem 0; color: #007cba;">
                                        📅 <?php echo date('l, F j, Y', strtotime($apt['appointment_date'])); ?>
                                    </h4>
                                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1rem;">
                                        <div>
                                            <strong>🕐 Time:</strong> <?php echo date('g:i A', strtotime($apt['appointment_time'])); ?><br>
                                            <strong>👨‍⚕️ Doctor:</strong> Dr. <?php echo $apt['first_name'] . ' ' . $apt['last_name']; ?><br>
                                            <?php if ($apt['specialization']): ?>
                                                <strong>🏥 Specialty:</strong> <?php echo $apt['specialization']; ?><br>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <strong>📋 Type:</strong> <?php echo $apt['appointment_type']; ?><br>
                                            <strong>📍 Status:</strong> 
                                            <span style="background: <?php echo $apt['status'] == 'Confirmed' ? '#28a745' : ($apt['status'] == 'Scheduled' ? '#ffc107' : '#dc3545'); ?>; color: white; padding: 0.2rem 0.6rem; border-radius: 12px; font-size: 0.8rem;">
                                                <?php echo $apt['status']; ?>
                                            </span><br>
                                            <strong>⏰ In:</strong> <?php echo $apt['days_until']; ?> day(s)
                                        </div>
                                    </div>
                                    <?php if ($apt['reason']): ?>
                                        <div style="background: #f8f9fa; padding: 0.75rem; border-radius: 6px; margin-bottom: 1rem;">
                                            <strong>📝 Reason:</strong> <?php echo htmlspecialchars($apt['reason']); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                <?php if ($apt['status'] == 'Scheduled' && $apt['days_until'] > 0): ?>
                                    <button onclick="cancelAppointment(<?php echo $apt['appointment_id']; ?>)" class="btn" style="background: #dc3545; font-size: 0.9rem; padding: 0.5rem 1rem;">
                                        ❌ Cancel Appointment
                                    </button>
                                    <button onclick="rescheduleAppointment(<?php echo $apt['appointment_id']; ?>)" class="btn" style="background: #17a2b8; font-size: 0.9rem; padding: 0.5rem 1rem;">
                                        📅 Request Reschedule
                                    </button>
                                <?php endif; ?>
                                <button onclick="viewAppointmentDetails(<?php echo $apt['appointment_id']; ?>)" class="btn" style="background: #6c757d; font-size: 0.9rem; padding: 0.5rem 1rem;">
                                    👁️ View Details
                                </button>
                                <?php if ($apt['days_until'] <= 1 && $apt['status'] == 'Confirmed'): ?>
                                    <span style="background: #28a745; color: white; padding: 0.5rem 1rem; border-radius: 6px; font-size: 0.9rem;">
                                        ✅ Ready for Visit
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 3rem; background: #f8f9fa; border-radius: 8px;">
                    <div style="font-size: 4rem; margin-bottom: 1rem;">📅</div>
                    <h4 style="color: #6c757d; margin-bottom: 1rem;">No Upcoming Appointments</h4>
                    <p style="color: #6c757d; margin-bottom: 2rem;">You don't have any scheduled appointments. Book one now to continue your care.</p>
                    <a href="book.php" class="btn" style="background: #28a745; padding: 1rem 2rem;">📅 Book Your First Appointment</a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Past Appointments -->
        <div class="section">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <h3 style="color: #6c757d; margin: 0;">📋 Appointment History</h3>
                <input type="text" placeholder="Search history..." id="searchPast" onkeyup="searchAppointments('past')" style="padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px; width: 200px;">
            </div>
            
            <?php
            $past_appointments = $mysqli->query("
                SELECT a.*, u.first_name, u.last_name, u.specialization,
                       DATEDIFF(CURDATE(), a.appointment_date) as days_ago
                FROM appointments a
                JOIN users u ON a.doctor_id = u.user_id
                WHERE a.patient_id = $patient_id AND a.appointment_date < CURDATE()
                ORDER BY a.appointment_date DESC
                LIMIT 20
            ");
            
            if ($past_appointments && $past_appointments->num_rows > 0):
            ?>
                <div id="pastTable" style="max-height: 400px; overflow-y: auto;">
                    <?php while ($apt = $past_appointments->fetch_assoc()): ?>
                        <div class="appointment-card" style="border: 1px solid #e0e0e0; border-radius: 8px; padding: 1rem; margin-bottom: 0.5rem; background: #fafafa; opacity: 0.9;">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <div style="flex: 1;">
                                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem; align-items: center;">
                                        <div>
                                            <strong><?php echo date('M d, Y', strtotime($apt['appointment_date'])); ?></strong><br>
                                            <small style="color: #6c757d;"><?php echo $apt['days_ago']; ?> days ago</small>
                                        </div>
                                        <div>
                                            <strong>Dr. <?php echo $apt['first_name'] . ' ' . $apt['last_name']; ?></strong><br>
                                            <small style="color: #6c757d;"><?php echo $apt['appointment_type']; ?></small>
                                        </div>
                                        <div>
                                            <span style="background: <?php echo $apt['status'] == 'Completed' ? '#28a745' : ($apt['status'] == 'Cancelled' ? '#dc3545' : '#6c757d'); ?>; color: white; padding: 0.2rem 0.6rem; border-radius: 12px; font-size: 0.8rem;">
                                                <?php echo $apt['status']; ?>
                                            </span>
                                        </div>
                                        <div>
                                            <button onclick="viewAppointmentDetails(<?php echo $apt['appointment_id']; ?>)" class="btn" style="background: #6c757d; font-size: 0.8rem; padding: 0.3rem 0.8rem;">
                                                View Details
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 2rem; background: #f8f9fa; border-radius: 8px;">
                    <p style="color: #6c757d;">No appointment history available.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Appointment Details Modal -->
        <div id="appointmentModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
            <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 2rem; border-radius: 8px; max-width: 500px; width: 90%; max-height: 80%; overflow-y: auto;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <h4 style="color: #007cba; margin: 0;">Appointment Details</h4>
                    <button onclick="closeModal()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer;">&times;</button>
                </div>
                <div id="appointmentDetailsContent">
                    <!-- Content will be loaded here -->
                </div>
            </div>
        </div>
    </div>

<script>
function searchAppointments(type) {
    const input = document.getElementById(type === 'upcoming' ? 'searchUpcoming' : 'searchPast');
    const filter = input.value.toUpperCase();
    const container = document.getElementById(type === 'upcoming' ? 'upcomingTable' : 'pastTable');
    const cards = container.getElementsByClassName('appointment-card');

    for (let i = 0; i < cards.length; i++) {
        const card = cards[i];
        const text = card.textContent || card.innerText;
        if (text.toUpperCase().indexOf(filter) > -1) {
            card.style.display = '';
        } else {
            card.style.display = 'none';
        }
    }
}

function filterByStatus(type) {
    const statusFilter = document.getElementById('statusFilter').value;
    const container = document.getElementById('upcomingTable');
    const cards = container.getElementsByClassName('appointment-card');
    
    for (let i = 0; i < cards.length; i++) {
        const card = cards[i];
        const statusSpan = card.querySelector('span[style*="background:"]');
        const status = statusSpan ? statusSpan.textContent.trim() : '';
        
        if (statusFilter === 'all' || status === statusFilter) {
            card.style.display = '';
        } else {
            card.style.display = 'none';
        }
    }
}

function cancelAppointment(appointmentId) {
    if (confirm('Are you sure you want to cancel this appointment? The doctor will be notified.')) {
        window.location.href = `cancel.php?id=${appointmentId}`;
    }
}

function rescheduleAppointment(appointmentId) {
    alert('To reschedule your appointment, please contact reception at +250 123 456 789 or visit the hospital.');
}

function viewAppointmentDetails(appointmentId) {
    document.getElementById('appointmentDetailsContent').innerHTML = `
        <div style="text-align: center; padding: 2rem;">
            <div style="font-size: 2rem; margin-bottom: 1rem;">📅</div>
            <p>Appointment ID: ${appointmentId}</p>
            <p>For detailed appointment information and medical records, please contact reception or visit during your appointment.</p>
            <div style="background: #e3f2fd; padding: 1rem; border-radius: 6px; margin: 1rem 0;">
                <p style="margin: 0; color: #1976d2;"><strong>📞 Reception:</strong> +250 123 456 789</p>
            </div>
        </div>
    `;
    document.getElementById('appointmentModal').style.display = 'block';
}

function closeModal() {
    document.getElementById('appointmentModal').style.display = 'none';
}

// Close modal when clicking outside
document.getElementById('appointmentModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});

// Auto-refresh for upcoming appointments
setInterval(() => {
    if (document.visibilityState === 'visible') {
        // Check for appointment updates every 5 minutes
        const now = new Date();
        if (now.getMinutes() % 5 === 0) {
            location.reload();
        }
    }
}, 60000);
</script>

<?php include '../layouts/footer.php'; ?>