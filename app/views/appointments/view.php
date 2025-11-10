<?php
session_start();
require_once '../../config/database.php';
require_once '../../config/activity_hooks.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Doctor') {
    header('Location: ../auth/login.php');
    exit();
}

// Handle appointment actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    $appointment_id = $_POST['appointment_id'] ?? 0;
    
    if ($action && $appointment_id) {
        if ($action == 'accept') {
            $stmt = $mysqli->prepare("UPDATE appointments SET status = 'Confirmed' WHERE appointment_id = ? AND doctor_id = ?");
            $stmt->bind_param("ii", $appointment_id, $_SESSION['user_id']);
            $stmt->execute();
            $success = "Appointment accepted successfully!";
            hook_appointment_activity($appointment_id, 'confirmed', $_SESSION['user_id']);
            
        } elseif ($action == 'reject') {
            $reason = $_POST['reason'] ?? 'No reason provided';
            $stmt = $mysqli->prepare("UPDATE appointments SET status = 'Cancelled', notes = ? WHERE appointment_id = ? AND doctor_id = ?");
            $stmt->bind_param("sii", $reason, $appointment_id, $_SESSION['user_id']);
            $stmt->execute();
            $success = "Appointment rejected successfully!";
            hook_appointment_activity($appointment_id, 'rejected', $_SESSION['user_id']);
            
        } elseif ($action == 'reschedule') {
            $new_date = $_POST['new_date'] ?? '';
            $new_time = $_POST['new_time'] ?? '';
            
            if ($new_date && $new_time) {
                $stmt = $mysqli->prepare("UPDATE appointments SET appointment_date = ?, appointment_time = ?, status = 'Confirmed' WHERE appointment_id = ? AND doctor_id = ?");
                $stmt->bind_param("ssii", $new_date, $new_time, $appointment_id, $_SESSION['user_id']);
                $stmt->execute();
                $success = "Appointment rescheduled successfully!";
                hook_appointment_activity($appointment_id, 'rescheduled', $_SESSION['user_id']);
            }
        } elseif ($action == 'complete') {
            $stmt = $mysqli->prepare("UPDATE appointments SET status = 'Completed' WHERE appointment_id = ? AND doctor_id = ?");
            $stmt->bind_param("ii", $appointment_id, $_SESSION['user_id']);
            $stmt->execute();
            $success = "Appointment marked as completed!";
            hook_appointment_activity($appointment_id, 'completed', $_SESSION['user_id']);
        }
        
        // Send notification and email
        if (isset($success)) {
            $email_result = sendAppointmentNotification($mysqli, $appointment_id, $action, $_POST);
            if ($email_result) {
                $success .= " Email notification sent successfully.";
            } else {
                $success .= " (Email notification failed - check email configuration)";
            }
        }
    }
}

$page_title = 'Doctor Appointments';
$page_header = '👨‍⚕️ My Appointments';
$show_nav = true;
include '../layouts/header.php';

// Get appointments for this doctor
$appointments = $mysqli->query("
    SELECT a.*, 
           p.first_name, p.last_name, p.contact_number, p.email,
           u.email as patient_email
    FROM appointments a
    JOIN patients p ON a.patient_id = p.patient_id
    LEFT JOIN users u ON p.nid = u.nid
    WHERE a.doctor_id = {$_SESSION['user_id']}
    ORDER BY a.appointment_date DESC, a.appointment_time DESC
");

// Get appointment statistics
$stats = $mysqli->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'Scheduled' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'Confirmed' THEN 1 ELSE 0 END) as confirmed,
        SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN status = 'Cancelled' THEN 1 ELSE 0 END) as cancelled
    FROM appointments 
    WHERE doctor_id = {$_SESSION['user_id']}
")->fetch_assoc();

function sendAppointmentNotification($mysqli, $appointment_id, $action, $data) {
    // Get appointment and patient details
    $query = $mysqli->query("
        SELECT a.*, p.first_name, p.last_name, p.email, u.email as patient_email,
               d.first_name as doctor_first, d.last_name as doctor_last
        FROM appointments a
        JOIN patients p ON a.patient_id = p.patient_id
        LEFT JOIN users u ON p.nid = u.nid
        JOIN users d ON a.doctor_id = d.user_id
        WHERE a.appointment_id = $appointment_id
    ");
    
    if ($query->num_rows == 0) return false;
    
    $apt = $query->fetch_assoc();
    $patient_email = $apt['patient_email'] ?: $apt['email'];
    
    // Create notification message
    $messages = [
        'accept' => "Your appointment has been confirmed by Dr. {$apt['doctor_first']} {$apt['doctor_last']}",
        'reject' => "Your appointment has been cancelled by Dr. {$apt['doctor_first']} {$apt['doctor_last']}. Reason: " . ($data['reason'] ?? 'Not specified'),
        'reschedule' => "Your appointment has been rescheduled to " . (isset($data['new_date']) ? date('M d, Y', strtotime($data['new_date'])) : 'new date') . " at " . (isset($data['new_time']) ? date('g:i A', strtotime($data['new_time'])) : 'new time'),
        'complete' => "Your appointment has been marked as completed by Dr. {$apt['doctor_first']} {$apt['doctor_last']}"
    ];
    
    $message = $messages[$action] ?? 'Appointment status updated';
    
    // Insert notification
    $subject = "Appointment " . ucfirst($action);
    $notification_type = 'Appointment Update';
    $status = 'Pending';
    
    // Fix: Use correct column names for notifications table
    if ($patient_email) {
        $stmt = $mysqli->prepare("INSERT INTO notifications (to_email, subject, message, notification_type, channel, status) VALUES (?, ?, ?, ?, 'Email', 'Pending')");
        $stmt->bind_param("ssss", $patient_email, $subject, $message, $notification_type);
        $stmt->execute();
    }
    
    // Send email if patient has email
    if ($patient_email) {
        require_once '../../config/email_config.php';
        
        $appointment_data = [
            'date' => date('M d, Y', strtotime($apt['appointment_date'])),
            'time' => date('g:i A', strtotime($apt['appointment_time'])),
            'type' => $apt['appointment_type'],
            'doctor' => "Dr. {$apt['doctor_first']} {$apt['doctor_last']}",
            'message' => $message
        ];
        
        $email_sent = sendAppointmentEmail(
            $patient_email,
            $apt['first_name'] . ' ' . $apt['last_name'],
            $appointment_data,
            $action
        );
        
        // Update notification status
        if ($email_sent) {
            $mysqli->query("UPDATE notifications SET status = 'Sent', sent_at = NOW() WHERE to_email = '$patient_email' AND subject = '$subject' ORDER BY created_at DESC LIMIT 1");
        } else {
            $mysqli->query("UPDATE notifications SET status = 'Failed' WHERE to_email = '$patient_email' AND subject = '$subject' ORDER BY created_at DESC LIMIT 1");
        }
        
        return $email_sent;
    }
    
    return false;
}
?>

<div class="container">
    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <div style="margin-bottom: 1rem;">
        <a href="../dashboard/index.php" class="btn btn-secondary">← Back to Dashboard</a>
    </div>

    <!-- Statistics -->
    <div class="stats">
        <div class="stat-card">
            <div class="stat-number"><?php echo $stats['total']; ?></div>
            <div class="stat-label">Total Appointments</div>
        </div>
        <div class="stat-card">
            <div class="stat-number" style="color: #ffc107;"><?php echo $stats['pending']; ?></div>
            <div class="stat-label">Pending</div>
        </div>
        <div class="stat-card">
            <div class="stat-number" style="color: #28a745;"><?php echo $stats['confirmed']; ?></div>
            <div class="stat-label">Confirmed</div>
        </div>
        <div class="stat-card">
            <div class="stat-number" style="color: #0077B6;"><?php echo $stats['completed']; ?></div>
            <div class="stat-label">Completed</div>
        </div>
        <div class="stat-card">
            <div class="stat-number" style="color: #dc3545;"><?php echo $stats['cancelled']; ?></div>
            <div class="stat-label">Cancelled</div>
        </div>
    </div>

    <!-- Appointments List -->
    <div class="section">
        <h3>My Appointments</h3>
        
        <?php if ($appointments && $appointments->num_rows > 0): ?>
            <div style="max-height: 700px; overflow-y: auto;">
                <?php while ($apt = $appointments->fetch_assoc()): ?>
                    <div style="border: 1px solid #ddd; border-radius: 8px; padding: 1rem; margin-bottom: 1rem; background: #ffffff;">
                        
                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
                            <div>
                                <h5 style="color: #0077B6; margin: 0 0 0.5rem 0;">
                                    <?php echo $apt['first_name'] . ' ' . $apt['last_name']; ?>
                                </h5>
                                <div style="color: #6c757d; font-size: 0.9rem;">
                                    📅 <?php echo date('M d, Y', strtotime($apt['appointment_date'])); ?> at <?php echo date('g:i A', strtotime($apt['appointment_time'])); ?><br>
                                    🏥 <?php echo $apt['appointment_type']; ?><br>
                                    📞 <?php echo $apt['contact_number']; ?>
                                    <?php if ($apt['email']): ?>
                                        <br>📧 <?php echo $apt['email']; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div style="text-align: right;">
                                <span style="background: <?php 
                                    echo $apt['status'] == 'Confirmed' ? '#28a745' : 
                                        ($apt['status'] == 'Scheduled' ? '#ffc107' : 
                                        ($apt['status'] == 'Completed' ? '#0077B6' : '#dc3545')); 
                                ?>; color: white; padding: 0.3rem 0.8rem; border-radius: 15px; font-size: 0.8rem;">
                                    <?php echo $apt['status']; ?>
                                </span>
                                <div style="margin-top: 0.5rem; font-size: 0.8rem; color: #6c757d;">
                                    ID: <?php echo $apt['appointment_id']; ?>
                                </div>
                            </div>
                        </div>
                        
                        <?php if ($apt['reason']): ?>
                            <div style="background: #f8f9fa; padding: 0.5rem; border-radius: 4px; margin-bottom: 1rem;">
                                <strong>Reason:</strong> <?php echo htmlspecialchars($apt['reason']); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($apt['notes']): ?>
                            <div style="background: #fff3cd; padding: 0.5rem; border-radius: 4px; margin-bottom: 1rem;">
                                <strong>Notes:</strong> <?php echo htmlspecialchars($apt['notes']); ?>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Action Buttons -->
                        <?php if ($apt['status'] == 'Scheduled'): ?>
                            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                <button onclick="acceptAppointment(<?php echo $apt['appointment_id']; ?>)" class="btn" style="background: #28a745;">
                                    ✅ Accept
                                </button>
                                <button onclick="showRejectModal(<?php echo $apt['appointment_id']; ?>)" class="btn" style="background: #dc3545;">
                                    ❌ Reject
                                </button>
                                <button onclick="showRescheduleModal(<?php echo $apt['appointment_id']; ?>, '<?php echo $apt['appointment_date']; ?>', '<?php echo $apt['appointment_time']; ?>')" class="btn" style="background: #17a2b8;">
                                    📅 Reschedule
                                </button>
                                <button onclick="sendNotification(<?php echo $apt['appointment_id']; ?>, '<?php echo $apt['first_name'] . ' ' . $apt['last_name']; ?>')" class="btn" style="background: #6f42c1;">
                                    📧 Notify
                                </button>
                            </div>
                        <?php elseif ($apt['status'] == 'Confirmed'): ?>
                            <div style="display: flex; gap: 0.5rem;">
                                <button onclick="markCompleted(<?php echo $apt['appointment_id']; ?>)" class="btn" style="background: #0077B6;">
                                    ✅ Mark Completed
                                </button>
                                <button onclick="sendNotification(<?php echo $apt['appointment_id']; ?>, '<?php echo $apt['first_name'] . ' ' . $apt['last_name']; ?>')" class="btn" style="background: #6f42c1;">
                                    📧 Notify
                                </button>
                            </div>
                        <?php else: ?>
                            <button onclick="sendNotification(<?php echo $apt['appointment_id']; ?>, '<?php echo $apt['first_name'] . ' ' . $apt['last_name']; ?>')" class="btn" style="background: #6f42c1;">
                                📧 Send Notification
                            </button>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div style="text-align: center; padding: 3rem;">
                <h4 style="color: #6c757d;">No Appointments</h4>
                <p style="color: #6c757d;">You don't have any appointments scheduled.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Reject Modal -->
<div id="rejectModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 2rem; border-radius: 8px; max-width: 400px; width: 90%;">
        <h4 style="color: #dc3545; margin-bottom: 1rem;">Reject Appointment</h4>
        <form method="POST">
            <input type="hidden" name="action" value="reject">
            <input type="hidden" name="appointment_id" id="rejectAppointmentId">
            <div class="form-group">
                <label>Reason for rejection:</label>
                <textarea name="reason" rows="3" required placeholder="Please provide a reason..."></textarea>
            </div>
            <button type="submit" class="btn" style="background: #dc3545;">Reject Appointment</button>
            <button type="button" onclick="closeModal('rejectModal')" class="btn btn-secondary">Cancel</button>
        </form>
    </div>
</div>

<!-- Reschedule Modal -->
<div id="rescheduleModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 2rem; border-radius: 8px; max-width: 400px; width: 90%;">
        <h4 style="color: #17a2b8; margin-bottom: 1rem;">Reschedule Appointment</h4>
        <form method="POST">
            <input type="hidden" name="action" value="reschedule">
            <input type="hidden" name="appointment_id" id="rescheduleAppointmentId">
            <div class="form-group">
                <label>New Date:</label>
                <input type="date" name="new_date" id="newDate" required>
            </div>
            <div class="form-group">
                <label>New Time:</label>
                <input type="time" name="new_time" id="newTime" required>
            </div>
            <button type="submit" class="btn" style="background: #17a2b8;">Reschedule</button>
            <button type="button" onclick="closeModal('rescheduleModal')" class="btn btn-secondary">Cancel</button>
        </form>
    </div>
</div>

<!-- Notification Modal -->
<div id="notificationModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 2rem; border-radius: 8px; max-width: 500px; width: 90%;">
        <h4 style="color: #6f42c1; margin-bottom: 1rem;">Send Notification</h4>
        <div class="form-group">
            <label>Patient:</label>
            <input type="text" id="patientName" readonly style="background: #f8f9fa;">
        </div>
        <div class="form-group">
            <label>Message:</label>
            <textarea id="notificationMessage" rows="4" placeholder="Enter your message..."></textarea>
        </div>
        <div style="margin-bottom: 1rem;">
            <label>
                <input type="checkbox" id="sendEmail" checked> Send via Email
            </label>
        </div>
        <button onclick="sendCustomNotification()" class="btn" style="background: #6f42c1;">Send Notification</button>
        <button onclick="closeModal('notificationModal')" class="btn btn-secondary">Cancel</button>
    </div>
</div>

<script>
let currentAppointmentId = 0;

function acceptAppointment(id) {
    if (confirm('Accept this appointment?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="accept">
            <input type="hidden" name="appointment_id" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function showRejectModal(id) {
    document.getElementById('rejectAppointmentId').value = id;
    document.getElementById('rejectModal').style.display = 'block';
}

function showRescheduleModal(id, currentDate, currentTime) {
    document.getElementById('rescheduleAppointmentId').value = id;
    document.getElementById('newDate').value = currentDate;
    document.getElementById('newTime').value = currentTime;
    document.getElementById('rescheduleModal').style.display = 'block';
}

function sendNotification(id, patientName) {
    currentAppointmentId = id;
    document.getElementById('patientName').value = patientName;
    document.getElementById('notificationMessage').value = '';
    document.getElementById('notificationModal').style.display = 'block';
}

function sendCustomNotification() {
    const message = document.getElementById('notificationMessage').value;
    const sendEmail = document.getElementById('sendEmail').checked;
    
    if (!message.trim()) {
        alert('Please enter a message');
        return;
    }
    
    fetch('send_notification.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `appointment_id=${currentAppointmentId}&message=${encodeURIComponent(message)}&send_email=${sendEmail}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Notification sent successfully!');
            closeModal('notificationModal');
        } else {
            alert('Error sending notification: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(error => {
        alert('Error sending notification');
    });
}

function markCompleted(id) {
    if (confirm('Mark this appointment as completed?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="complete">
            <input type="hidden" name="appointment_id" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}
</script>

<?php include '../layouts/footer.php'; ?>