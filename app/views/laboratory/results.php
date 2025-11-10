<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Admin', 'Doctor', 'Nurse'])) {
    header('Location: ../auth/login.php');
    exit;
}

$message = '';
$test_id = $_GET['test_id'] ?? '';

// Handle result submission
if ($_POST) {
    $test_id = $_POST['test_id'];
    $result_value = $_POST['result_value'];
    $result_unit = $_POST['result_unit'];
    $result_status = $_POST['result_status'];
    $result_notes = $_POST['result_notes'];
    
    $stmt = $mysqli->prepare("
        UPDATE laboratory_tests 
        SET result_value = ?, result_unit = ?, result_status = ?, result_notes = ?, result_date = CURDATE(), verified_by = ?
        WHERE test_id = ?
    ");
    $stmt->bind_param("ssssii", $result_value, $result_unit, $result_status, $result_notes, $_SESSION['user_id'], $test_id);
    
    if ($stmt->execute()) {
        $message = '<div class="alert alert-success">Test result saved successfully</div>';
        
        // Send notification if critical result
        if ($result_status === 'Critical') {
            $notification_stmt = $mysqli->prepare("
                INSERT INTO notifications (user_id, subject, message, notification_type, channel, status)
                SELECT av.recorded_by, 'Critical Lab Result Alert', 
                       CONCAT('Critical laboratory result for patient. Test: ', lt.test_name, ', Result: ', lt.result_value, ' ', lt.result_unit),
                       'Clinical Alert', 'Email', 'Pending'
                FROM laboratory_tests lt
                JOIN anc_visits av ON lt.visit_id = av.visit_id
                WHERE lt.test_id = ?
            ");
            $notification_stmt->bind_param("i", $test_id);
            $notification_stmt->execute();
        }
    } else {
        $message = '<div class="alert alert-error">Error saving result</div>';
    }
}

// Get pending tests or specific test
if ($test_id) {
    $tests_query = "
        SELECT lt.*, p.first_name, p.last_name, p.contact_number, av.visit_date, av.visit_number,
               u.first_name as doctor_name
        FROM laboratory_tests lt
        JOIN anc_visits av ON lt.visit_id = av.visit_id
        JOIN pregnancies pr ON av.pregnancy_id = pr.pregnancy_id
        JOIN patients p ON pr.patient_id = p.patient_id
        LEFT JOIN users u ON av.recorded_by = u.user_id
        WHERE lt.test_id = ?
    ";
    $stmt = $mysqli->prepare($tests_query);
    $stmt->bind_param("i", $test_id);
    $stmt->execute();
    $tests = $stmt->get_result();
} else {
    $tests = $mysqli->query("
        SELECT lt.*, p.first_name, p.last_name, p.contact_number, av.visit_date, av.visit_number,
               u.first_name as doctor_name
        FROM laboratory_tests lt
        JOIN anc_visits av ON lt.visit_id = av.visit_id
        JOIN pregnancies pr ON av.pregnancy_id = pr.pregnancy_id
        JOIN patients p ON pr.patient_id = p.patient_id
        LEFT JOIN users u ON av.recorded_by = u.user_id
        WHERE lt.result_date IS NULL
        ORDER BY lt.test_date ASC
    ");
}
?>

<?php
$page_title = 'Laboratory Results - Maternal Care System';
$show_nav = true;
$breadcrumb = [
    ['title' => 'Laboratory', 'url' => 'index.php'],
    ['title' => 'Results']
];
include '../layouts/header.php';
?>
    
    <div class="container">
        <div class="section">
            <h2>🔬 Laboratory Results</h2>
            <p style="margin-bottom: 0;">Enter and manage laboratory test results</p>
        </div>

        <?= $message ?>

        <?php if ($test_id && $test = $tests->fetch_assoc()): ?>
        <!-- Single test result entry -->
        <div class="section">
            <div style="background: #F8F9FA; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                <h3>Test Information</h3>
                <p><strong>Patient:</strong> <?= htmlspecialchars($test['first_name'] . ' ' . $test['last_name']) ?></p>
                <p><strong>Test:</strong> <?= htmlspecialchars($test['test_name']) ?> (<?= $test['test_code'] ?>)</p>
                <p><strong>Test Date:</strong> <?= date('M j, Y', strtotime($test['test_date'])) ?></p>
                <p><strong>Normal Range:</strong> <?= htmlspecialchars($test['normal_range']) ?></p>
                <p><strong>Ordered by:</strong> Dr. <?= htmlspecialchars($test['doctor_name']) ?></p>
            </div>

            <form method="POST">
                <input type="hidden" name="test_id" value="<?= $test['test_id'] ?>">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="result_value">Result Value</label>
                        <input type="text" id="result_value" name="result_value" value="<?= htmlspecialchars($test['result_value'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="result_unit">Unit</label>
                        <input type="text" id="result_unit" name="result_unit" value="<?= htmlspecialchars($test['result_unit'] ?? '') ?>" placeholder="e.g., mg/dL, g/dL">
                    </div>
                </div>

                <div class="form-group">
                    <label for="result_status">Result Status</label>
                    <select id="result_status" name="result_status" required>
                        <option value="">Select Status</option>
                        <option value="Normal" <?= ($test['result_status'] ?? '') === 'Normal' ? 'selected' : '' ?>>Normal</option>
                        <option value="Abnormal" <?= ($test['result_status'] ?? '') === 'Abnormal' ? 'selected' : '' ?>>Abnormal</option>
                        <option value="Critical" <?= ($test['result_status'] ?? '') === 'Critical' ? 'selected' : '' ?>>Critical</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="result_notes">Notes/Comments</label>
                    <textarea id="result_notes" name="result_notes" placeholder="Additional notes about the result..."><?= htmlspecialchars($test['result_notes'] ?? '') ?></textarea>
                </div>

                <div style="text-align: center;">
                    <button type="submit" class="btn btn-success">Save Result</button>
                    <a href="results.php" class="btn btn-secondary" style="margin-left: 15px;">Back to List</a>
                </div>
            </form>
        </div>

        <?php else: ?>
        <!-- Pending tests list -->
        <div class="section">
            <h3 style="margin-bottom: 15px;">Pending Laboratory Tests</h3>
            
            <?php if ($tests->num_rows === 0): ?>
            <div style="padding: 40px; text-align: center; color: #6C757D;">
                <p>No pending laboratory tests found.</p>
                <a href="order_tests.php" class="btn btn-primary" style="margin-top: 15px;">Order New Tests</a>
            </div>
            <?php else: ?>
            <?php while ($test = $tests->fetch_assoc()): ?>
            <div style="padding: 20px; border-bottom: 1px solid #E6F2F1;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                    <div>
                        <div style="font-weight: bold; color: #0077B6; font-size: 1.1em;"><?= htmlspecialchars($test['test_name']) ?></div>
                        <div style="color: #2D2D2D; font-weight: 500;"><?= htmlspecialchars($test['first_name'] . ' ' . $test['last_name']) ?></div>
                    </div>
                    <a href="results.php?test_id=<?= $test['test_id'] ?>" class="btn btn-primary">Enter Result</a>
                </div>
                <div style="color: #6C757D; font-size: 0.9em;">
                    Test Code: <?= $test['test_code'] ?> | 
                    Test Date: <?= date('M j, Y', strtotime($test['test_date'])) ?> | 
                    Normal Range: <?= htmlspecialchars($test['normal_range']) ?> |
                    Ordered by: Dr. <?= htmlspecialchars($test['doctor_name']) ?>
                </div>
            </div>
            <?php endwhile; ?>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

<?php include '../layouts/footer.php'; ?>