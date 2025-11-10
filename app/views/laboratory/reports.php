<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Admin', 'Doctor', 'Nurse'])) {
    header('Location: ../auth/login.php');
    exit;
}

// Get filter parameters
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');
$test_type = $_GET['test_type'] ?? '';
$status_filter = $_GET['status_filter'] ?? '';

// Build query conditions
$conditions = ["DATE(lt.test_date) BETWEEN ? AND ?"];
$params = [$start_date, $end_date];
$types = "ss";

if ($test_type) {
    $conditions[] = "lt.test_type = ?";
    $params[] = $test_type;
    $types .= "s";
}

if ($status_filter) {
    if ($status_filter === 'pending') {
        $conditions[] = "lt.result_date IS NULL";
    } else {
        $conditions[] = "lt.result_status = ?";
        $params[] = $status_filter;
        $types .= "s";
    }
}

$where_clause = "WHERE " . implode(" AND ", $conditions);

// Get laboratory statistics
$stats_query = "
    SELECT 
        COUNT(*) as total_tests,
        COUNT(CASE WHEN result_date IS NOT NULL THEN 1 END) as completed_tests,
        COUNT(CASE WHEN result_date IS NULL THEN 1 END) as pending_tests,
        COUNT(CASE WHEN result_status = 'Normal' THEN 1 END) as normal_results,
        COUNT(CASE WHEN result_status = 'Abnormal' THEN 1 END) as abnormal_results,
        COUNT(CASE WHEN result_status = 'Critical' THEN 1 END) as critical_results,
        SUM(cost) as total_revenue
    FROM laboratory_tests lt
    $where_clause
";

$stmt = $mysqli->prepare($stats_query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();

// Get detailed test results
$tests_query = "
    SELECT lt.*, p.first_name, p.last_name, p.contact_number, av.visit_date, av.visit_number,
           u1.first_name as ordered_by_name, u2.first_name as verified_by_name
    FROM laboratory_tests lt
    JOIN anc_visits av ON lt.visit_id = av.visit_id
    JOIN pregnancies pr ON av.pregnancy_id = pr.pregnancy_id
    JOIN patients p ON pr.patient_id = p.patient_id
    LEFT JOIN users u1 ON lt.conducted_by = u1.user_id
    LEFT JOIN users u2 ON lt.verified_by = u2.user_id
    $where_clause
    ORDER BY lt.test_date DESC, p.last_name ASC
";

$stmt = $mysqli->prepare($tests_query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$tests = $stmt->get_result();

// Export functionality
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="laboratory_report_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Patient Name', 'Test Name', 'Test Type', 'Test Date', 'Result Value', 'Result Unit', 'Status', 'Cost', 'Ordered By']);
    
    $stmt->execute();
    $export_tests = $stmt->get_result();
    while ($test = $export_tests->fetch_assoc()) {
        fputcsv($output, [
            $test['first_name'] . ' ' . $test['last_name'],
            $test['test_name'],
            $test['test_type'],
            $test['test_date'],
            $test['result_value'] ?? 'Pending',
            $test['result_unit'] ?? '',
            $test['result_status'] ?? 'Pending',
            $test['cost'],
            $test['ordered_by_name'] ?? ''
        ]);
    }
    fclose($output);
    exit;
}
?>

<?php
$page_title = 'Laboratory Reports - Maternal Care System';
$show_nav = true;
$breadcrumb = [
    ['title' => 'Laboratory', 'url' => 'index.php'],
    ['title' => 'Reports']
];
include '../layouts/header.php';
?>
    
    <div class="container">
        <div class="section">
            <h2>📊 Laboratory Reports</h2>
            <p style="margin-bottom: 0;">Comprehensive laboratory test reports and analytics</p>
        </div>

        <!-- Filters -->
        <div class="section">
            <form method="GET">
                <div class="filter-row">
                    <div class="form-group">
                        <label for="start_date">Start Date</label>
                        <input type="date" id="start_date" name="start_date" value="<?= htmlspecialchars($start_date) ?>">
                    </div>
                    <div class="form-group">
                        <label for="end_date">End Date</label>
                        <input type="date" id="end_date" name="end_date" value="<?= htmlspecialchars($end_date) ?>">
                    </div>
                    <div class="form-group">
                        <label for="test_type">Test Type</label>
                        <select id="test_type" name="test_type">
                            <option value="">All Types</option>
                            <option value="Blood" <?= $test_type === 'Blood' ? 'selected' : '' ?>>Blood</option>
                            <option value="Urine" <?= $test_type === 'Urine' ? 'selected' : '' ?>>Urine</option>
                            <option value="Ultrasound" <?= $test_type === 'Ultrasound' ? 'selected' : '' ?>>Ultrasound</option>
                            <option value="Other" <?= $test_type === 'Other' ? 'selected' : '' ?>>Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="status_filter">Status</label>
                        <select id="status_filter" name="status_filter">
                            <option value="">All Status</option>
                            <option value="pending" <?= $status_filter === 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="Normal" <?= $status_filter === 'Normal' ? 'selected' : '' ?>>Normal</option>
                            <option value="Abnormal" <?= $status_filter === 'Abnormal' ? 'selected' : '' ?>>Abnormal</option>
                            <option value="Critical" <?= $status_filter === 'Critical' ? 'selected' : '' ?>>Critical</option>
                        </select>
                    </div>
                    <div>
                        <button type="submit" class="btn btn-primary">Filter Results</button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Statistics -->
        <div class="stats">
            <div class="stat-card">
                <div class="stat-number"><?= $stats['total_tests'] ?></div>
                <div class="stat-label">Total Tests</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $stats['completed_tests'] ?></div>
                <div class="stat-label">Completed</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $stats['pending_tests'] ?></div>
                <div class="stat-label">Pending</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $stats['critical_results'] ?></div>
                <div class="stat-label">Critical Results</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= number_format($stats['total_revenue']) ?></div>
                <div class="stat-label">Revenue (RWF)</div>
            </div>
        </div>

        <!-- Export Actions -->
        <div style="text-align: right; margin-bottom: 15px;">
            <a href="?<?= http_build_query(array_merge($_GET, ['export' => 'csv'])) ?>" class="btn btn-success">📄 Export CSV</a>
        </div>

        <!-- Results Table -->
        <div class="section">
            <h3 style="margin-bottom: 15px;">Laboratory Test Results</h3>
            <table>
                <thead>
                    <tr>
                        <th>Patient</th>
                        <th>Test Name</th>
                        <th>Type</th>
                        <th>Test Date</th>
                        <th>Result</th>
                        <th>Status</th>
                        <th>Cost</th>
                        <th>Ordered By</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($tests->num_rows === 0): ?>
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 40px; color: #6c757d;">
                            No laboratory tests found for the selected criteria.
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php while ($test = $tests->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($test['first_name'] . ' ' . $test['last_name']) ?></td>
                        <td><?= htmlspecialchars($test['test_name']) ?></td>
                        <td><?= $test['test_type'] ?></td>
                        <td><?= date('M j, Y', strtotime($test['test_date'])) ?></td>
                        <td>
                            <?php if ($test['result_value']): ?>
                                <?= htmlspecialchars($test['result_value']) ?> <?= htmlspecialchars($test['result_unit'] ?? '') ?>
                            <?php else: ?>
                                <span style="color: #6C757D;">Pending</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($test['result_status']): ?>
                                <span class="status status-<?= strtolower($test['result_status']) ?>">
                                    <?= $test['result_status'] ?>
                                </span>
                            <?php else: ?>
                                <span class="status status-pending">Pending</span>
                            <?php endif; ?>
                        </td>
                        <td><?= number_format($test['cost']) ?> RWF</td>
                        <td><?= htmlspecialchars($test['ordered_by_name'] ?? 'N/A') ?></td>
                    </tr>
                    <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php include '../layouts/footer.php'; ?>