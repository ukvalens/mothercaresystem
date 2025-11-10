<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Patient' || !isset($_GET['id'])) {
    exit('Unauthorized');
}

$payment_id = $_GET['id'];

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

// Get payment details with security check
$payment_query = $mysqli->prepare("
    SELECT pt.*,
           CONCAT(u.first_name, ' ', u.last_name) as processed_by_name, u.role as processor_role
    FROM payment_transactions pt
    LEFT JOIN users u ON pt.processed_by = u.user_id
    WHERE pt.transaction_id = ? AND pt.patient_id = ?
");
$payment_query->bind_param("ii", $payment_id, $patient_id);
$payment_query->execute();
$payment = $payment_query->get_result()->fetch_assoc();

if (!$payment) {
    exit('Payment record not found');
}
?>

<div style="margin-bottom: 1rem;">
    <p><strong>Transaction ID:</strong> T<?php echo $payment['transaction_id']; ?></p>
    <p><strong>Date:</strong> <?php echo date('M d, Y', strtotime($payment['created_at'])); ?></p>
    <p><strong>Service:</strong> <?php echo $payment['service_description']; ?></p>
    <p><strong>Amount:</strong> $<?php echo number_format($payment['amount'], 2); ?></p>
    <p><strong>Status:</strong> 
        <span style="background: <?php 
            echo $payment['status'] == 'Completed' ? '#28a745' : 
                ($payment['status'] == 'Pending' ? '#ffc107' : '#dc3545'); 
        ?>; color: white; padding: 0.25rem 0.5rem; border-radius: 12px; font-size: 0.75rem;">
            <?php echo $payment['status']; ?>
        </span>
    </p>
</div>



<div style="background: #f8f9fa; padding: 1rem; border-radius: 4px; margin: 1rem 0;">
    <h5 style="color: #0077B6; margin-bottom: 0.5rem;">Payment Information</h5>
    <p><strong>Payment Method:</strong> <?php echo $payment['payment_method'] ?: 'Not specified'; ?></p>
    <?php if ($payment['reference_number']): ?>
        <p><strong>Reference Number:</strong> <?php echo $payment['reference_number']; ?></p>
    <?php endif; ?>
    <?php if ($payment['processed_by_name']): ?>
        <p><strong>Processed By:</strong> <?php echo $payment['processed_by_name']; ?> (<?php echo $payment['processor_role']; ?>)</p>
    <?php endif; ?>
    <p><strong>Created:</strong> <?php echo date('M d, Y H:i', strtotime($payment['created_at'])); ?></p>
</div>

<?php if ($payment['description']): ?>
<div style="background: #e3f2fd; padding: 1rem; border-radius: 4px; margin: 1rem 0;">
    <h5 style="color: #1976d2; margin-bottom: 0.5rem;">Description</h5>
    <p><?php echo nl2br(htmlspecialchars($payment['description'])); ?></p>
</div>
<?php endif; ?>

<?php if ($payment['status'] != 'Completed'): ?>
<div style="background: #fff3cd; padding: 1rem; border-radius: 4px; margin: 1rem 0; text-align: center;">
    <p style="color: #856404; margin-bottom: 0.5rem;"><strong>💰 Payment Required</strong></p>
    <p style="color: #856404;">Please contact reception to complete this payment.</p>
    <p style="color: #0077B6; font-weight: bold; margin-top: 0.5rem;">Reception: +250 123 456 789</p>
</div>
<?php else: ?>
<div style="background: #d4edda; padding: 1rem; border-radius: 4px; margin: 1rem 0; text-align: center;">
    <p style="color: #155724;"><strong>✅ Payment Completed</strong></p>
    <p style="color: #155724;">Thank you for your payment!</p>
</div>
<?php endif; ?>

<div style="margin-top: 1rem; text-align: center;">
    <small style="color: #6c757d;">
        For billing inquiries, please contact our reception desk.
    </small>
</div>