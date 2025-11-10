<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Patient' || !isset($_GET['id'])) {
    exit('Unauthorized');
}

$invoice_id = $_GET['id'];

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

// Get invoice details with security check
$invoice_query = $mysqli->prepare("
    SELECT bi.*,
           CONCAT(u.first_name, ' ', u.last_name) as created_by_name, u.role as creator_role
    FROM billing_invoices bi
    LEFT JOIN users u ON bi.created_by = u.user_id
    WHERE bi.invoice_id = ? AND bi.patient_id = ?
");
$invoice_query->bind_param("ii", $invoice_id, $patient_id);
$invoice_query->execute();
$invoice = $invoice_query->get_result()->fetch_assoc();

if (!$invoice) {
    exit('Invoice not found');
}
?>

<div style="margin-bottom: 1rem;">
    <p><strong>Invoice ID:</strong> INV<?php echo $invoice['invoice_id']; ?></p>
    <p><strong>Date:</strong> <?php echo date('M d, Y', strtotime($invoice['created_at'])); ?></p>
    <p><strong>Service:</strong> <?php echo $invoice['description']; ?></p>
    <p><strong>Amount:</strong> $<?php echo number_format($invoice['total_amount'], 2); ?></p>
    <p><strong>Status:</strong> 
        <span style="background: <?php 
            echo $invoice['status'] == 'Paid' ? '#28a745' : 
                ($invoice['status'] == 'Pending' ? '#ffc107' : '#dc3545'); 
        ?>; color: white; padding: 0.25rem 0.5rem; border-radius: 12px; font-size: 0.75rem;">
            <?php echo $invoice['status']; ?>
        </span>
    </p>
</div>

<div style="background: #f8f9fa; padding: 1rem; border-radius: 4px; margin: 1rem 0;">
    <h5 style="color: #0077B6; margin-bottom: 0.5rem;">Invoice Information</h5>
    <?php if ($invoice['due_date']): ?>
        <p><strong>Due Date:</strong> <?php echo date('M d, Y', strtotime($invoice['due_date'])); ?></p>
    <?php endif; ?>
    <?php if ($invoice['created_by_name']): ?>
        <p><strong>Created By:</strong> <?php echo $invoice['created_by_name']; ?> (<?php echo $invoice['creator_role']; ?>)</p>
    <?php endif; ?>
    <p><strong>Created:</strong> <?php echo date('M d, Y H:i', strtotime($invoice['created_at'])); ?></p>
</div>

<?php if ($invoice['notes']): ?>
<div style="background: #e3f2fd; padding: 1rem; border-radius: 4px; margin: 1rem 0;">
    <h5 style="color: #1976d2; margin-bottom: 0.5rem;">Notes</h5>
    <p><?php echo nl2br(htmlspecialchars($invoice['notes'])); ?></p>
</div>
<?php endif; ?>

<?php if ($invoice['status'] != 'Paid'): ?>
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