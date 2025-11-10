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

$page_title = 'My Bills & Payments';
$page_header = '💳 My Bills & Payments';
$show_nav = true;
$breadcrumb = [
    ['title' => 'Billing']
];

include '../layouts/header.php';

// Get payment transactions if patient exists
$transactions = null;
$summary = null;
if (isset($patient_id)) {
    // Get all payment transactions for this patient
    $transactions = $mysqli->query("
        SELECT pt.*,
               CONCAT(u.first_name, ' ', u.last_name) as processed_by_name
        FROM payment_transactions pt
        LEFT JOIN users u ON pt.processed_by = u.user_id
        WHERE pt.patient_id = $patient_id
        ORDER BY pt.created_at DESC
    ");
    
    // Get payment summary
    $summary_query = $mysqli->query("
        SELECT 
            COUNT(*) as total_bills,
            SUM(amount) as total_amount,
            SUM(CASE WHEN status = 'Completed' THEN amount ELSE 0 END) as paid_amount,
            SUM(CASE WHEN status = 'Pending' THEN amount ELSE 0 END) as pending_amount,
            SUM(CASE WHEN status = 'Failed' THEN amount ELSE 0 END) as failed_amount
        FROM payment_transactions 
        WHERE patient_id = $patient_id
    ");
    $summary = $summary_query->fetch_assoc();
}
?>

<div class="container">
    <?php if (isset($error)): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
        <div class="section">
            <p>Please contact the reception desk to create your patient profile before you can view your billing information.</p>
            <a href="../dashboard/index.php" class="btn">← Back to Dashboard</a>
        </div>
    <?php else: ?>
        
    <!-- Navigation -->
    <div style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; padding: 1.5rem; border-radius: 10px; margin-bottom: 2rem; text-align: center;">
        <h2 style="margin: 0 0 0.5rem 0;">💳 My Bills & Payment History</h2>
        <p style="margin: 0; opacity: 0.9;">Track your payments and outstanding balances</p>
    </div>
    
    <div style="margin-bottom: 2rem; display: flex; gap: 1rem; flex-wrap: wrap;">
        <a href="../dashboard/index.php" class="btn btn-secondary">← Dashboard</a>
        <a href="../appointments/my_appointments.php" class="btn btn-secondary">📅 Appointments</a>
        <a href="../visits/my_visits.php" class="btn btn-secondary">🏥 Visit History</a>
    </div>

    <!-- Payment Summary -->
    <?php if ($summary && $summary['total_bills'] > 0): ?>
    <div class="stats">
        <div class="stat-card">
            <div class="stat-number"><?php echo $summary['total_bills']; ?></div>
            <div class="stat-label">Total Bills</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">RWF <?php echo number_format($summary['total_amount']); ?></div>
            <div class="stat-label">Total Amount</div>
        </div>
        <div class="stat-card">
            <div class="stat-number" style="color: #28a745;">RWF <?php echo number_format($summary['paid_amount']); ?></div>
            <div class="stat-label">Paid Amount</div>
        </div>
        <div class="stat-card">
            <div class="stat-number" style="color: <?php echo $summary['pending_amount'] > 0 ? '#ffc107' : '#6c757d'; ?>">RWF <?php echo number_format($summary['pending_amount']); ?></div>
            <div class="stat-label">Pending Amount</div>
        </div>
        <?php if ($summary['failed_amount'] > 0): ?>
        <div class="stat-card">
            <div class="stat-number" style="color: #dc3545;">RWF <?php echo number_format($summary['failed_amount']); ?></div>
            <div class="stat-label">Failed Payments</div>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Outstanding Bills Alert -->
    <?php if ($summary && $summary['pending_amount'] > 0): ?>
    <div class="alert" style="background: #fff3cd; color: #856404; border: 1px solid #ffeaa7;">
        <strong>💰 Outstanding Balance:</strong> 
        You have RWF <?php echo number_format($summary['pending_amount']); ?> in pending payments.
        Please contact reception for payment options.
    </div>
    <?php endif; ?>

    <!-- Payment History -->
    <div class="section">
        <h3 style="color: #0077B6; margin-bottom: 1rem;">Payment History</h3>
        
        <?php if ($transactions && $transactions->num_rows > 0): ?>
            <div style="margin-bottom: 1rem; display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
                <input type="text" placeholder="Search transactions..." id="searchPayments" onkeyup="searchPayments()" style="padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px; width: 300px;">
                <select id="statusFilter" onchange="filterByStatus()" style="padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;">
                    <option value="all">All Status</option>
                    <option value="Completed">Completed</option>
                    <option value="Pending">Pending</option>
                    <option value="Failed">Failed</option>
                </select>
            </div>
            
            <table id="transactionsTable">
                <thead>
                    <tr>
                        <th>Transaction ID</th>
                        <th>Date</th>
                        <th>Service</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Payment Method</th>
                        <th>Reference</th>
                        <th>Processed By</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($transaction = $transactions->fetch_assoc()): ?>
                        <tr style="background: <?php echo $transaction['status'] == 'Completed' ? '#f0f8f0' : ($transaction['status'] == 'Pending' ? '#fff3cd' : '#ffebee'); ?>;">
                            <td><strong>T<?php echo $transaction['transaction_id']; ?></strong></td>
                            <td><?php echo date('M d, Y H:i', strtotime($transaction['created_at'])); ?></td>
                            <td><?php echo htmlspecialchars($transaction['description']); ?></td>
                            <td><strong>RWF <?php echo number_format($transaction['amount']); ?></strong></td>
                            <td>
                                <span style="background: <?php 
                                    echo $transaction['status'] == 'Completed' ? '#28a745' : 
                                        ($transaction['status'] == 'Pending' ? '#ffc107' : '#dc3545'); 
                                ?>; color: white; padding: 0.25rem 0.5rem; border-radius: 12px; font-size: 0.75rem;">
                                    <?php echo $transaction['status']; ?>
                                </span>
                            </td>
                            <td><?php echo $transaction['payment_method']; ?></td>
                            <td><?php echo $transaction['transaction_reference'] ?: '-'; ?></td>
                            <td><?php echo $transaction['processed_by_name'] ?: 'System'; ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div style="text-align: center; padding: 2rem;">
                <p style="color: #6c757d; margin-bottom: 1rem;">No billing records found.</p>
                <p>Your payment history will appear here after your first appointment or service.</p>
                <a href="../appointments/my_appointments.php" class="btn">Book an Appointment</a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Payment Details Modal -->
    <div id="paymentModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 2rem; border-radius: 8px; max-width: 500px; width: 90%; max-height: 80%; overflow-y: auto;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <h4 style="color: #0077B6;">Payment Details</h4>
                <button onclick="closeModal()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer;">&times;</button>
            </div>
            <div id="paymentDetailsContent">
                <!-- Content will be loaded here -->
            </div>
        </div>
    </div>

    <!-- Payment Request Modal -->
    <div id="paymentRequestModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 2rem; border-radius: 8px; max-width: 400px; width: 90%;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <h4 style="color: #0077B6;">Payment Request</h4>
                <button onclick="closePaymentModal()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer;">&times;</button>
            </div>
            <div style="text-align: center;">
                <p style="margin-bottom: 1rem;">To make a payment, please contact our reception desk or visit the hospital.</p>
                <p style="color: #6c757d; margin-bottom: 1rem;">Payment methods accepted:</p>
                <ul style="text-align: left; margin-bottom: 1rem;">
                    <li>Cash</li>
                    <li>Credit/Debit Card</li>
                    <li>Bank Transfer</li>
                    <li>Insurance</li>
                </ul>
                <p style="color: #0077B6; font-weight: bold;">Reception: +250 123 456 789</p>
            </div>
        </div>
    </div>

    <?php endif; ?>
</div>

<script>
function searchPayments() {
    const input = document.getElementById('searchPayments');
    const filter = input.value.toUpperCase();
    const table = document.getElementById('transactionsTable');
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

function showTransactionDetails(transactionId) {
    // Show transaction details
    document.getElementById('paymentDetailsContent').innerHTML = `
        <p>Transaction ID: T${transactionId}</p>
        <p>For detailed transaction information, please contact reception.</p>
        <p><strong>Reception:</strong> +250 123 456 789</p>
    `;
    document.getElementById('paymentModal').style.display = 'block';
}

function requestPayment() {
    document.getElementById('paymentRequestModal').style.display = 'block';
}

function filterByStatus() {
    const statusFilter = document.getElementById('statusFilter').value;
    const table = document.getElementById('transactionsTable');
    const rows = table.getElementsByTagName('tr');
    
    for (let i = 1; i < rows.length; i++) {
        const statusCell = rows[i].getElementsByTagName('td')[4];
        if (statusFilter === 'all' || statusCell.textContent.includes(statusFilter)) {
            rows[i].style.display = '';
        } else {
            rows[i].style.display = 'none';
        }
    }
}

function closeModal() {
    document.getElementById('paymentModal').style.display = 'none';
}

function closePaymentModal() {
    document.getElementById('paymentRequestModal').style.display = 'none';
}

// Close modals when clicking outside
document.getElementById('paymentModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});

document.getElementById('paymentRequestModal').addEventListener('click', function(e) {
    if (e.target === this) closePaymentModal();
});
</script>

<?php include '../layouts/footer.php'; ?>