<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Admin', 'Doctor', 'Nurse'])) {
    header('Location: ../auth/login.php');
    exit;
}

// Get laboratory statistics
$stats_query = "
    SELECT 
        COUNT(*) as total_tests,
        COUNT(CASE WHEN result_date IS NULL THEN 1 END) as pending_tests,
        COUNT(CASE WHEN result_status = 'Critical' THEN 1 END) as critical_results,
        COUNT(CASE WHEN DATE(test_date) = CURDATE() THEN 1 END) as today_tests
    FROM laboratory_tests
";
$stats = $mysqli->query($stats_query)->fetch_assoc();

// Get recent tests
$recent_tests = $mysqli->query("
    SELECT lt.*, p.first_name, p.last_name, u.first_name as doctor_name
    FROM laboratory_tests lt
    JOIN anc_visits av ON lt.visit_id = av.visit_id
    JOIN pregnancies pr ON av.pregnancy_id = pr.pregnancy_id
    JOIN patients p ON pr.patient_id = p.patient_id
    LEFT JOIN users u ON av.recorded_by = u.user_id
    ORDER BY lt.test_date DESC
    LIMIT 10
");
?>

<?php
$page_title = 'Laboratory Dashboard - Maternal Care System';
$show_nav = true;
$breadcrumb = [
    ['title' => 'Laboratory']
];
include '../layouts/header.php';
?>
    
    <div class="container">
        <div class="section">
            <h2>🧪 Laboratory Management</h2>
            <p style="margin-bottom: 0;">Manage laboratory tests, results, and reports</p>
        </div>

        <div class="stats">
            <div class="stat-card">
                <div class="stat-number"><?= $stats['total_tests'] ?></div>
                <div class="stat-label">Total Tests</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $stats['pending_tests'] ?></div>
                <div class="stat-label">Pending Results</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $stats['critical_results'] ?></div>
                <div class="stat-label">Critical Results</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $stats['today_tests'] ?></div>
                <div class="stat-label">Today's Tests</div>
            </div>
        </div>

        <div style="display: flex; gap: 15px; margin-bottom: 30px; flex-wrap: wrap;">
            <a href="order_tests.php" class="btn btn-primary">📋 Order Tests</a>
            <a href="results.php" class="btn btn-success">🔬 Enter Results</a>
            <a href="reports.php" class="btn">📊 Lab Reports</a>
        </div>

        <div class="section">
            <h3 style="margin-bottom: 15px;">Recent Laboratory Tests</h3>
            <table>
                <thead>
                    <tr>
                        <th>Patient</th>
                        <th>Test Name</th>
                        <th>Test Date</th>
                        <th>Status</th>
                        <th>Doctor</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($test = $recent_tests->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($test['first_name'] . ' ' . $test['last_name']) ?></td>
                        <td><?= htmlspecialchars($test['test_name']) ?></td>
                        <td><?= date('M j, Y', strtotime($test['test_date'])) ?></td>
                        <td>
                            <?php if ($test['result_date']): ?>
                                <span class="status status-<?= strtolower($test['result_status'] ?? 'normal') ?>">
                                    <?= $test['result_status'] ?? 'Completed' ?>
                                </span>
                            <?php else: ?>
                                <span class="status status-pending">Pending</span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($test['doctor_name'] ?? 'N/A') ?></td>
                        <td>
                            <a href="results.php?test_id=<?= $test['test_id'] ?>" class="btn btn-primary" style="padding: 5px 10px; font-size: 0.8em;">View</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php include '../layouts/footer.php'; ?>