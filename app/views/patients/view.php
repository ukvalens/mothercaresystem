<?php
session_start();
require_once '../../config/database.php';

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

$page_title = 'Patient Details';
$page_header = '👤 Patient Details';
$show_nav = true;
$breadcrumb = [
    ['title' => 'Patients', 'url' => 'list.php'],
    ['title' => 'View Patient']
];

include '../layouts/header.php';

// Get pregnancies
$pregnancies = $mysqli->query("SELECT * FROM pregnancies WHERE patient_id = $patient_id ORDER BY created_at DESC");

// Get appointments
$appointments = $mysqli->query("SELECT a.*, CONCAT(u.first_name, ' ', u.last_name) as doctor_name FROM appointments a LEFT JOIN users u ON a.doctor_id = u.user_id WHERE a.patient_id = $patient_id ORDER BY a.appointment_date DESC LIMIT 10");

// Get recent visits
$visits = $mysqli->query("SELECT av.*, pr.lmp_date FROM anc_visits av LEFT JOIN pregnancies pr ON av.pregnancy_id = pr.pregnancy_id WHERE pr.patient_id = $patient_id ORDER BY av.visit_date DESC LIMIT 5");
?>

<div class="container">
    <div style="margin-bottom: 1rem;">
        <a href="list.php" class="btn btn-secondary">← Back to Patient List</a>
        <?php if (in_array($_SESSION['role'], ['Admin', 'Receptionist'])): ?>
            <a href="edit.php?id=<?php echo $patient_id; ?>" class="btn">Edit Patient</a>
        <?php endif; ?>
        <?php if (in_array($_SESSION['role'], ['Doctor', 'Nurse'])): ?>
            <a href="../appointments/schedule.php?patient_id=<?php echo $patient_id; ?>" class="btn" style="background: #28a745;">Book Appointment</a>
        <?php endif; ?>
    </div>

    <!-- Patient Information -->
    <div class="section">
        <h3 style="color: #0077B6; margin-bottom: 1rem;">Patient Information</h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem;">
            <div>
                <p><strong>Patient ID:</strong> P<?php echo $patient['patient_id']; ?></p>
                <p><strong>Full Name:</strong> <?php echo $patient['first_name'] . ' ' . $patient['last_name']; ?></p>
                <p><strong>Date of Birth:</strong> <?php echo date('M d, Y', strtotime($patient['date_of_birth'])); ?></p>
                <p><strong>Age:</strong> <?php echo date('Y') - date('Y', strtotime($patient['date_of_birth'])); ?> years</p>
                <p><strong>Gender:</strong> <?php echo $patient['gender']; ?></p>
                <p><strong>Blood Type:</strong> <?php echo $patient['blood_type'] ?: 'Not specified'; ?></p>
            </div>
            <div>
                <p><strong>Contact Number:</strong> <?php echo $patient['contact_number']; ?></p>
                <p><strong>Email:</strong> <?php echo $patient['email'] ?: 'Not provided'; ?></p>
                <p><strong>Address:</strong> <?php echo $patient['address']; ?></p>
                <p><strong>Emergency Contact:</strong> <?php echo $patient['emergency_contact_name']; ?></p>
                <p><strong>Emergency Phone:</strong> <?php echo $patient['emergency_contact_phone']; ?></p>
                <p><strong>Registered:</strong> <?php echo date('M d, Y', strtotime($patient['created_at'])); ?></p>
            </div>
        </div>
    </div>

    <!-- Pregnancies -->
    <div class="section">
        <h3 style="color: #0077B6; margin-bottom: 1rem;">Pregnancy History</h3>
        <?php if ($pregnancies && $pregnancies->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Pregnancy ID</th>
                        <th>LMP Date</th>
                        <th>EDD</th>
                        <th>Gestational Age</th>
                        <th>Risk Level</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($pregnancy = $pregnancies->fetch_assoc()): ?>
                        <tr>
                            <td>PR<?php echo $pregnancy['pregnancy_id']; ?></td>
                            <td><?php echo date('M d, Y', strtotime($pregnancy['lmp_date'])); ?></td>
                            <td><?php echo date('M d, Y', strtotime($pregnancy['expected_delivery_date'])); ?></td>
                            <td><?php echo $pregnancy['gestational_age_weeks']; ?>w <?php echo $pregnancy['gestational_age_days']; ?>d</td>
                            <td><span style="color: <?php echo $pregnancy['risk_level'] == 'High' ? '#dc3545' : ($pregnancy['risk_level'] == 'Medium' ? '#ffc107' : '#28a745'); ?>"><?php echo $pregnancy['risk_level']; ?></span></td>
                            <td><?php echo $pregnancy['current_status']; ?></td>
                            <td>
                                <a href="../pregnancies/view.php?id=<?php echo $pregnancy['pregnancy_id']; ?>" class="btn" style="font-size: 12px; padding: 0.3rem 0.6rem;">View</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No pregnancy records found.</p>
            <?php if (in_array($_SESSION['role'], ['Doctor', 'Nurse'])): ?>
                <a href="../pregnancies/register.php?patient_id=<?php echo $patient_id; ?>" class="btn">Register Pregnancy</a>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- Recent Appointments -->
    <div class="section">
        <h3 style="color: #0077B6; margin-bottom: 1rem;">Recent Appointments</h3>
        <?php if ($appointments && $appointments->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Doctor</th>
                        <th>Type</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($appointment = $appointments->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo date('M d, Y', strtotime($appointment['appointment_date'])); ?></td>
                            <td><?php echo date('h:i A', strtotime($appointment['appointment_time'])); ?></td>
                            <td><?php echo $appointment['doctor_name'] ?: 'Not assigned'; ?></td>
                            <td><?php echo $appointment['appointment_type']; ?></td>
                            <td><?php echo $appointment['status']; ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No appointments found.</p>
        <?php endif; ?>
    </div>

    <!-- Recent ANC Visits -->
    <div class="section">
        <h3 style="color: #0077B6; margin-bottom: 1rem;">Recent ANC Visits</h3>
        <?php if ($visits && $visits->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Visit Date</th>
                        <th>Gestational Age</th>
                        <th>Weight</th>
                        <th>Blood Pressure</th>
                        <th>Fundal Height</th>
                        <th>Fetal Heart Rate</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($visit = $visits->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo date('M d, Y', strtotime($visit['visit_date'])); ?></td>
                            <td><?php echo $visit['gestational_age_weeks']; ?>w <?php echo $visit['gestational_age_days']; ?>d</td>
                            <td><?php echo $visit['weight_kg']; ?> kg</td>
                            <td><?php echo $visit['blood_pressure_systolic']; ?>/<?php echo $visit['blood_pressure_diastolic']; ?></td>
                            <td><?php echo $visit['fundal_height_cm']; ?> cm</td>
                            <td><?php echo $visit['fetal_heart_rate']; ?> bpm</td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No ANC visits recorded.</p>
        <?php endif; ?>
    </div>
</div>

<?php include '../layouts/footer.php'; ?>