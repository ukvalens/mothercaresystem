<?php
session_start();
require_once '../../config/database.php';
require_once '../../config/sms_config.php';
require_once '../../config/activity_hooks.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Receptionist', 'Admin'])) {
    header('Location: ../auth/login.php');
    exit();
}

// Handle payment processing
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $patient_id = $_POST['patient_id'];
    $amount = $_POST['amount'];
    $service_description = $_POST['service_description'];
    $payment_method = $_POST['payment_method'];
    $reference_number = $_POST['reference_number'] ?? '';
    $mobile_number = $_POST['mobile_number'] ?? '';
    $notes = trim($_POST['description'] ?? '');
    $description = $service_description . ($notes ? ' - ' . $notes : '');
    
    // For mobile money, include phone number in reference
    if ($payment_method == 'Mobile Money' && $mobile_number) {
        $reference_number = $mobile_number . ($reference_number ? ' - ' . $reference_number : '');
    }
    
    $stmt = $mysqli->prepare("INSERT INTO payment_transactions (patient_id, amount, payment_method, description, transaction_reference, status, processed_by, processed_at) VALUES (?, ?, ?, ?, ?, 'Completed', ?, NOW())");
    $stmt->bind_param("idsssi", $patient_id, $amount, $payment_method, $description, $reference_number, $_SESSION['user_id']);
    
    if ($stmt->execute()) {
        $transaction_id = $mysqli->insert_id;
        
        // Send notifications
        hook_payment_activity($transaction_id, 'completed', $_SESSION['user_id']);
        
        if ($payment_method == 'Mobile Money' && $mobile_number) {
            // Get patient name for SMS
            $patient_query = $mysqli->prepare("SELECT first_name, last_name FROM patients WHERE patient_id = ?");
            $patient_query->bind_param("i", $patient_id);
            $patient_query->execute();
            $patient_data = $patient_query->get_result()->fetch_assoc();
            $patient_name = $patient_data['first_name'] . ' ' . $patient_data['last_name'];
            
            // Send SMS confirmation
            $sms = new SMSGateway();
            $sms_result = $sms->sendMobileMoneyConfirmation($mobile_number, $transaction_id, $amount, $patient_name);
            
            if ($sms_result['success']) {
                $success = "Payment processed successfully! Transaction ID: T{$transaction_id}<br>✅ SMS confirmation sent to: {$mobile_number}";
            } else {
                $error_details = '';
                if (isset($sms_result['response']['SMSMessageData']['Message'])) {
                    $error_details = $sms_result['response']['SMSMessageData']['Message'];
                } elseif ($sms_result['curl_error']) {
                    $error_details = 'Connection error: ' . $sms_result['curl_error'];
                } else {
                    $error_details = 'HTTP ' . $sms_result['http_code'];
                }
                $success = "Payment processed successfully! Transaction ID: T{$transaction_id}<br>⚠️ SMS failed: {$error_details}<br>Phone: {$mobile_number}";
            }
        } else {
            $success = "Payment processed successfully! Transaction ID: T{$transaction_id}";
        }
    } else {
        $error = "Error processing payment: " . $mysqli->error;
    }
}

$page_title = 'Process Payments';
$page_header = '💳 Process Payments';
$show_nav = true;
$breadcrumb = [
    ['title' => 'Payments']
];

include '../layouts/header.php';

// Get recent transactions
$recent_transactions = $mysqli->query("
    SELECT pt.*, p.first_name, p.last_name,
           CONCAT(u.first_name, ' ', u.last_name) as processed_by_name
    FROM payment_transactions pt
    JOIN patients p ON pt.patient_id = p.patient_id
    LEFT JOIN users u ON pt.processed_by = u.user_id
    ORDER BY pt.created_at DESC
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
        <button onclick="showTab('process')" class="btn" id="processTab">Process Payment</button>
        <button onclick="showTab('history')" class="btn btn-secondary" id="historyTab">Payment History</button>
    </div>

    <!-- Process Payment Tab -->
    <div id="processContent" class="section">
        <h3 style="color: #0077B6; margin-bottom: 1rem;">Process New Payment</h3>
        
        <form method="POST" id="paymentForm">
            <div class="form-group">
                <label>Patient *</label>
                <select name="patient_id" required onchange="loadPatientInfo(this.value)">
                    <option value="">Select Patient</option>
                    <?php
                    $patients = $mysqli->query("SELECT patient_id, first_name, last_name, contact_number FROM patients WHERE is_active = 1 ORDER BY first_name");
                    while ($patient = $patients->fetch_assoc()) {
                        echo "<option value='{$patient['patient_id']}'>P{$patient['patient_id']} - {$patient['first_name']} {$patient['last_name']} ({$patient['contact_number']})</option>";
                    }
                    ?>
                </select>
            </div>

            <div style="background: #f8f9fa; padding: 1rem; border-radius: 4px; margin: 1rem 0;">
                <h4 style="color: #0077B6; margin-bottom: 1rem;">Payment Details</h4>
                <div class="form-row">
                    <div class="form-group">
                        <label>Service Description *</label>
                        <select name="service_description" required onchange="updateAmount(this.value)">
                            <option value="">Select Service</option>
                            <option value="ANC Visit" data-amount="50">ANC Visit - $50</option>
                            <option value="Consultation" data-amount="75">Consultation - $75</option>
                            <option value="Emergency Visit" data-amount="100">Emergency Visit - $100</option>
                            <option value="Laboratory Tests" data-amount="30">Laboratory Tests - $30</option>
                            <option value="Ultrasound" data-amount="80">Ultrasound - $80</option>
                            <option value="Delivery" data-amount="500">Delivery - $500</option>
                            <option value="Other" data-amount="0">Other (Custom Amount)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Amount ($) *</label>
                        <input type="number" step="0.01" name="amount" id="amount" min="0" required>
                    </div>
                </div>
            </div>

            <div style="background: #f8f9fa; padding: 1rem; border-radius: 4px; margin: 1rem 0;">
                <h4 style="color: #0077B6; margin-bottom: 1rem;">Payment Method</h4>
                <div class="form-row">
                    <div class="form-group">
                        <label>Payment Method *</label>
                        <select name="payment_method" required onchange="togglePaymentFields(this.value)">
                            <option value="">Select Method</option>
                            <option value="Cash">Cash</option>
                            <option value="Credit Card">Credit Card</option>
                            <option value="Debit Card">Debit Card</option>
                            <option value="Bank Transfer">Bank Transfer</option>
                            <option value="Mobile Money">Mobile Money (SMS Confirmation)</option>
                            <option value="Insurance">Insurance</option>
                        </select>
                    </div>
                </div>
                
                <div id="mobileMoneyFields" style="display: none; background: #e8f4fd; padding: 1rem; border-radius: 4px; margin: 0.5rem 0;">
                    <h5 style="color: #0077B6; margin-bottom: 0.5rem;">📱 Mobile Money Details</h5>
                    <div style="background: #d1ecf1; padding: 0.75rem; border-radius: 4px; margin-bottom: 1rem; border-left: 4px solid #17a2b8;">
                        <small style="color: #0c5460;"><strong>SMS Integration Active:</strong> Real SMS confirmations will be sent via Africa's Talking API. Ensure your API credentials are configured in sms_config.php</small>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Mobile Number * <small>(For payment tracking)</small></label>
                            <input type="tel" name="mobile_number" id="mobileNumber" placeholder="e.g., +250788123456" pattern="\+?[0-9]{10,15}">
                            <small style="color: #666;">Enter the mobile money account number used for this payment</small>
                        </div>
                        <div class="form-group">
                            <label>Transaction Reference <small>(Optional)</small></label>
                            <input type="text" name="reference_number" placeholder="Mobile money transaction ID (if available)">
                        </div>
                    </div>
                </div>
                
                <div class="form-group" id="referenceField" style="display: none;">
                    <label>Reference Number</label>
                    <input type="text" name="reference_number" placeholder="Transaction/Receipt reference number">
                </div>
            </div>

            <div class="form-group">
                <label>Additional Notes</label>
                <textarea name="description" rows="2" placeholder="Any additional payment details or notes"></textarea>
            </div>

            <button type="submit" class="btn">Process Payment</button>
            <button type="reset" class="btn btn-secondary">Clear Form</button>
        </form>
    </div>

    <!-- Payment History Tab -->
    <div id="historyContent" class="section" style="display: none;">
        <h3 style="color: #0077B6; margin-bottom: 1rem;">Recent Payment Transactions</h3>
        
        <div style="margin-bottom: 1rem;">
            <input type="text" placeholder="Search transactions..." id="searchTransactions" onkeyup="searchTransactions()" style="padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px; width: 300px;">
        </div>
        
        <table id="transactionsTable">
            <thead>
                <tr>
                    <th>Transaction ID</th>
                    <th>Patient</th>
                    <th>Service</th>
                    <th>Amount</th>
                    <th>Method</th>
                    <th>Status</th>
                    <th>Processed By</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($recent_transactions && $recent_transactions->num_rows > 0): ?>
                    <?php while ($transaction = $recent_transactions->fetch_assoc()): ?>
                        <tr>
                            <td>T<?php echo $transaction['transaction_id']; ?></td>
                            <td>P<?php echo $transaction['patient_id']; ?> - <?php echo $transaction['first_name'] . ' ' . $transaction['last_name']; ?></td>
                            <td><?php echo $transaction['description']; ?></td>
                            <td>$<?php echo number_format($transaction['amount'], 2); ?></td>
                            <td><?php echo $transaction['payment_method']; ?></td>
                            <td>
                                <span style="background: <?php echo $transaction['status'] == 'Completed' ? '#28a745' : '#ffc107'; ?>; color: white; padding: 0.25rem 0.5rem; border-radius: 12px; font-size: 0.75rem;">
                                    <?php echo $transaction['status']; ?>
                                </span>
                            </td>
                            <td><?php echo $transaction['processed_by_name']; ?></td>
                            <td><?php echo date('M d, Y H:i', strtotime($transaction['created_at'])); ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="8">No payment transactions found</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function showTab(tab) {
    if (tab === 'process') {
        document.getElementById('processContent').style.display = 'block';
        document.getElementById('historyContent').style.display = 'none';
        document.getElementById('processTab').className = 'btn';
        document.getElementById('historyTab').className = 'btn btn-secondary';
    } else {
        document.getElementById('processContent').style.display = 'none';
        document.getElementById('historyContent').style.display = 'block';
        document.getElementById('processTab').className = 'btn btn-secondary';
        document.getElementById('historyTab').className = 'btn';
    }
}

function updateAmount(service) {
    const select = document.querySelector('select[name="service_description"]');
    const selectedOption = select.options[select.selectedIndex];
    const amount = selectedOption.getAttribute('data-amount');
    
    if (amount && amount !== '0') {
        document.getElementById('amount').value = amount;
    } else {
        document.getElementById('amount').value = '';
    }
}

function loadPatientInfo(patientId) {
    // Could load additional patient info if needed
}

function togglePaymentFields(paymentMethod) {
    const referenceField = document.getElementById('referenceField');
    const mobileMoneyFields = document.getElementById('mobileMoneyFields');
    const mobileNumber = document.getElementById('mobileNumber');
    
    // Hide all fields first
    referenceField.style.display = 'none';
    mobileMoneyFields.style.display = 'none';
    
    // Reset requirements
    if (referenceField.querySelector('input')) {
        referenceField.querySelector('input').required = false;
    }
    if (mobileNumber) {
        mobileNumber.required = false;
    }
    
    if (paymentMethod === 'Mobile Money') {
        mobileMoneyFields.style.display = 'block';
        mobileNumber.required = true;
    } else if (['Credit Card', 'Debit Card', 'Bank Transfer', 'Insurance'].includes(paymentMethod)) {
        referenceField.style.display = 'block';
        referenceField.querySelector('input').required = true;
    }
}

function searchTransactions() {
    const input = document.getElementById('searchTransactions');
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
</script>

<?php include '../layouts/footer.php'; ?>