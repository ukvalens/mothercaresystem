<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Admin', 'Doctor'])) {
    header('Location: ../auth/login.php');
    exit;
}

$message = '';
$visit_id = $_GET['visit_id'] ?? '';

// Get patient info if visit_id provided
$patient_info = null;
if ($visit_id) {
    $patient_query = "
        SELECT p.*, pr.pregnancy_id, av.visit_date, av.visit_number
        FROM anc_visits av
        JOIN pregnancies pr ON av.pregnancy_id = pr.pregnancy_id
        JOIN patients p ON pr.patient_id = p.patient_id
        WHERE av.visit_id = ?
    ";
    $stmt = $mysqli->prepare($patient_query);
    $stmt->bind_param("i", $visit_id);
    $stmt->execute();
    $patient_info = $stmt->get_result()->fetch_assoc();
}

// Common laboratory tests
$common_tests = [
    ['type' => 'Blood', 'name' => 'Complete Blood Count (CBC)', 'code' => 'CBC', 'normal_range' => 'Hb: 11-15 g/dL', 'cost' => 5000],
    ['type' => 'Blood', 'name' => 'Blood Sugar (Random)', 'code' => 'RBS', 'normal_range' => '70-140 mg/dL', 'cost' => 3000],
    ['type' => 'Blood', 'name' => 'Blood Sugar (Fasting)', 'code' => 'FBS', 'normal_range' => '70-100 mg/dL', 'cost' => 3000],
    ['type' => 'Blood', 'name' => 'HIV Test', 'code' => 'HIV', 'normal_range' => 'Non-reactive', 'cost' => 8000],
    ['type' => 'Blood', 'name' => 'Syphilis (VDRL)', 'code' => 'VDRL', 'normal_range' => 'Non-reactive', 'cost' => 5000],
    ['type' => 'Blood', 'name' => 'Hepatitis B', 'code' => 'HBsAg', 'normal_range' => 'Negative', 'cost' => 10000],
    ['type' => 'Urine', 'name' => 'Urine Analysis', 'code' => 'UA', 'normal_range' => 'Protein: Negative', 'cost' => 3000],
    ['type' => 'Urine', 'name' => 'Urine Culture', 'code' => 'UC', 'normal_range' => 'No growth', 'cost' => 8000],
];

// Handle form submission
if ($_POST) {
    $visit_id = $_POST['visit_id'];
    $selected_tests = $_POST['selected_tests'] ?? [];
    
    if (empty($selected_tests) || empty($visit_id)) {
        $message = '<div class="alert alert-error">Please select a visit and at least one test</div>';
    } else {
        // Verify visit exists
        $verify_stmt = $mysqli->prepare("SELECT visit_id FROM anc_visits WHERE visit_id = ?");
        $verify_stmt->bind_param("i", $visit_id);
        $verify_stmt->execute();
        if ($verify_stmt->get_result()->num_rows === 0) {
            $message = '<div class="alert alert-error">Invalid visit selected</div>';
        } else {
            $success_count = 0;
            foreach ($selected_tests as $test_index) {
                if (isset($common_tests[$test_index])) {
                    $test = $common_tests[$test_index];
                    $stmt = $mysqli->prepare("
                        INSERT INTO laboratory_tests (visit_id, test_type, test_name, test_code, normal_range, cost, test_date, conducted_by) 
                        VALUES (?, ?, ?, ?, ?, ?, CURDATE(), ?)
                    ");
                    $stmt->bind_param("issssdi", $visit_id, $test['type'], $test['name'], $test['code'], $test['normal_range'], $test['cost'], $_SESSION['user_id']);
                    if ($stmt->execute()) {
                        $success_count++;
                    }
                }
            }
            $message = '<div class="alert alert-success">' . $success_count . ' tests ordered successfully</div>';
        }
    }
}
?>

<?php
$page_title = 'Order Laboratory Tests - Maternal Care System';
$show_nav = true;
$breadcrumb = [
    ['title' => 'Laboratory', 'url' => 'index.php'],
    ['title' => 'Order Tests']
];
include '../layouts/header.php';
?>
    
    <div class="container">
        <div class="section">
            <h2>📋 Order Laboratory Tests</h2>
            <p style="margin-bottom: 0;">Select and order laboratory tests for patients</p>
        </div>

        <?= $message ?>

        <div class="section">
            <?php if ($patient_info): ?>
            <div style="background: #F8F9FA; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                <h3>Patient Information</h3>
                <p><strong>Name:</strong> <?= htmlspecialchars($patient_info['first_name'] . ' ' . $patient_info['last_name']) ?></p>
                <p><strong>Visit:</strong> ANC Visit #<?= $patient_info['visit_number'] ?> - <?= date('M j, Y', strtotime($patient_info['visit_date'])) ?></p>
            </div>
            <?php endif; ?>

            <form method="POST" id="testOrderForm">
                <div class="form-group">
                    <label for="visit_id">Select ANC Visit</label>
                    <select id="visit_id" name="visit_id" required>
                        <option value="">Select a visit...</option>
                        <?php
                        $visits_query = "SELECT av.visit_id, av.visit_date, av.visit_number, p.first_name, p.last_name 
                                        FROM anc_visits av 
                                        JOIN pregnancies pr ON av.pregnancy_id = pr.pregnancy_id 
                                        JOIN patients p ON pr.patient_id = p.patient_id 
                                        ORDER BY av.visit_date DESC";
                        $visits_result = $mysqli->query($visits_query);
                        while ($visit = $visits_result->fetch_assoc()): ?>
                            <option value="<?= $visit['visit_id'] ?>" <?= $visit_id == $visit['visit_id'] ? 'selected' : '' ?>>
                                Visit #<?= $visit['visit_number'] ?> - <?= htmlspecialchars($visit['first_name'] . ' ' . $visit['last_name']) ?> (<?= date('M j, Y', strtotime($visit['visit_date'])) ?>)
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <h3>Select Laboratory Tests</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 15px; margin-top: 20px;">
                    <?php foreach ($common_tests as $index => $test): ?>
                    <label class="test-card" style="border: 2px solid #E6F2F1; padding: 15px; border-radius: 8px; cursor: pointer; transition: all 0.3s; display: block;">
                        <input type="checkbox" name="selected_tests[]" value="<?= $index ?>" onchange="updateTestCard(this, <?= $index ?>)" style="margin-right: 10px;">
                        <div style="font-weight: bold; color: #0077B6;"><?= htmlspecialchars($test['name']) ?></div>
                        <div style="font-size: 0.9em; color: #6C757D; margin-top: 5px;">
                            Type: <?= $test['type'] ?> | Code: <?= $test['code'] ?><br>
                            Normal Range: <?= htmlspecialchars($test['normal_range']) ?><br>
                            Cost: <?= number_format($test['cost']) ?> RWF
                        </div>
                    </label>
                    <?php endforeach; ?>
                </div>

                <div style="background: #0077B6; color: white; padding: 15px; border-radius: 5px; text-align: center; margin-top: 20px;" id="totalCost">
                    Total Cost: 0 RWF
                </div>

                <div style="margin-top: 30px; text-align: center;">
                    <button type="submit" class="btn btn-primary">Order Selected Tests</button>
                    <a href="index.php" class="btn btn-secondary" style="margin-left: 15px;">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <style>
        .test-card:hover { border-color: #0077B6 !important; }
        .test-card.selected { border-color: #0077B6 !important; background: #E6F2F1 !important; }
        .test-card input[type="checkbox"] { margin-right: 10px; }
    </style>

    <script>
        const testCosts = <?= json_encode(array_column($common_tests, 'cost')) ?>;
        
        function updateTestCard(checkbox, index) {
            const card = checkbox.parentElement;
            
            if (checkbox.checked) {
                card.style.borderColor = '#0077B6';
                card.style.background = '#E6F2F1';
            } else {
                card.style.borderColor = '#E6F2F1';
                card.style.background = 'white';
            }
            
            updateTotalCost();
        }
        
        function updateTotalCost() {
            let total = 0;
            document.querySelectorAll('input[name="selected_tests[]"]:checked').forEach(checkbox => {
                const index = parseInt(checkbox.value);
                total += testCosts[index];
            });
            
            document.getElementById('totalCost').textContent = 'Total Cost: ' + total.toLocaleString() + ' RWF';
        }
        
        // Prevent form submission if no tests selected
        document.getElementById('testOrderForm').addEventListener('submit', function(e) {
            const selectedTests = document.querySelectorAll('input[name="selected_tests[]"]:checked');
            if (selectedTests.length === 0) {
                e.preventDefault();
                alert('Please select at least one test');
            }
        });
    </script>

<?php include '../layouts/footer.php'; ?>