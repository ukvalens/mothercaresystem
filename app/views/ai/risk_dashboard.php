<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Admin', 'Doctor'])) {
    header('Location: ../auth/login.php');
    exit;
}

// Get AI risk statistics
$stats_query = "
    SELECT 
        COUNT(DISTINCT pr.pregnancy_id) as total_pregnancies,
        COUNT(CASE WHEN pr.risk_level = 'High' OR pr.risk_level = 'Critical' THEN 1 END) as high_risk_count,
        COUNT(CASE WHEN pr.risk_level = 'Critical' THEN 1 END) as critical_risk_count,
        AVG(pr.ai_risk_score) as avg_risk_score
    FROM pregnancies pr
    WHERE pr.current_status = 'Active'
";
$stats = $mysqli->query($stats_query)->fetch_assoc();

// Get recent high-risk predictions
$high_risk_patients = $mysqli->query("
    SELECT pr.pregnancy_id, pr.ai_risk_score, pr.risk_level, 
           p.first_name, p.last_name, p.contact_number,
           pr.expected_delivery_date,
           (SELECT MAX(av.visit_date) FROM anc_visits av WHERE av.pregnancy_id = pr.pregnancy_id) as last_visit
    FROM pregnancies pr
    JOIN patients p ON pr.patient_id = p.patient_id
    WHERE pr.current_status = 'Active' AND pr.risk_level IN ('High', 'Critical')
    ORDER BY pr.ai_risk_score DESC
    LIMIT 10
");

// Get risk distribution
$risk_distribution = $mysqli->query("
    SELECT risk_level, COUNT(*) as count
    FROM pregnancies 
    WHERE current_status = 'Active'
    GROUP BY risk_level
");

$page_title = 'AI Risk Dashboard - Maternal Care System';
$show_nav = true;
$breadcrumb = [
    ['title' => 'AI Risk Assessment']
];
include '../layouts/header.php';
?>
    
    <div class="container">
        <div class="section">
            <h2>🤖 AI Risk Assessment Dashboard</h2>
            <p style="margin-bottom: 0;">AI-powered pregnancy risk prediction and monitoring</p>
        </div>

        <div class="stats">
            <div class="stat-card">
                <div class="stat-number"><?= $stats['total_pregnancies'] ?></div>
                <div class="stat-label">Active Pregnancies</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $stats['high_risk_count'] ?></div>
                <div class="stat-label">High Risk Cases</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $stats['critical_risk_count'] ?></div>
                <div class="stat-label">Critical Risk</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= number_format($stats['avg_risk_score'], 1) ?></div>
                <div class="stat-label">Average Risk Score</div>
            </div>
        </div>

        <div style="display: flex; gap: 15px; margin-bottom: 30px; flex-wrap: wrap;">
            <a href="risk_alerts.php" class="btn btn-primary">⚠️ Risk Alerts</a>
            <a href="model_settings.php" class="btn btn-success">⚙️ AI Settings</a>
            <a href="../reports/daily.php" class="btn">📊 Risk Reports</a>
        </div>

        <!-- Risk Distribution Chart -->
        <div class="section">
            <h3 style="margin-bottom: 15px;">Risk Level Distribution</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                <?php 
                $risk_colors = [
                    'Low' => '#2A9D8F',
                    'Medium' => '#E9C46A', 
                    'High' => '#E63946',
                    'Critical' => '#D90429'
                ];
                while ($risk = $risk_distribution->fetch_assoc()): 
                ?>
                <div style="background: <?= $risk_colors[$risk['risk_level']] ?? '#6C757D' ?>; color: white; padding: 20px; border-radius: 10px; text-align: center;">
                    <div style="font-size: 2em; font-weight: bold;"><?= $risk['count'] ?></div>
                    <div><?= $risk['risk_level'] ?> Risk</div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>

        <!-- High Risk Patients -->
        <div class="section">
            <h3 style="margin-bottom: 15px;">High Risk Patients Requiring Attention</h3>
            <?php if ($high_risk_patients->num_rows === 0): ?>
            <div style="padding: 40px; text-align: center; color: #6C757D;">
                <p>✅ No high-risk patients currently identified.</p>
                <p>All active pregnancies are within normal risk parameters.</p>
            </div>
            <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Patient</th>
                        <th>Risk Score</th>
                        <th>Risk Level</th>
                        <th>EDD</th>
                        <th>Last Visit</th>
                        <th>Contact</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($patient = $high_risk_patients->fetch_assoc()): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']) ?></strong></td>
                        <td>
                            <div style="display: flex; align-items: center;">
                                <div style="width: 50px; height: 8px; background: #E6F2F1; border-radius: 4px; margin-right: 10px;">
                                    <div style="width: <?= $patient['ai_risk_score'] ?>%; height: 100%; background: <?= $patient['ai_risk_score'] >= 90 ? '#D90429' : ($patient['ai_risk_score'] >= 70 ? '#E63946' : '#E9C46A') ?>; border-radius: 4px;"></div>
                                </div>
                                <strong><?= number_format($patient['ai_risk_score'], 1) ?></strong>
                            </div>
                        </td>
                        <td>
                            <span style="background: <?= $patient['risk_level'] === 'Critical' ? '#D90429' : '#E63946' ?>; color: white; padding: 4px 8px; border-radius: 4px; font-size: 0.8em;">
                                <?= $patient['risk_level'] ?>
                            </span>
                        </td>
                        <td><?= date('M j, Y', strtotime($patient['expected_delivery_date'])) ?></td>
                        <td>
                            <?php if ($patient['last_visit']): ?>
                                <?= date('M j, Y', strtotime($patient['last_visit'])) ?>
                            <?php else: ?>
                                <span style="color: #E63946;">No visits</span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($patient['contact_number']) ?></td>
                        <td>
                            <a href="../patients/view.php?pregnancy_id=<?= $patient['pregnancy_id'] ?>" class="btn btn-primary" style="padding: 5px 10px; font-size: 0.8em;">View</a>
                            <a href="risk_alerts.php?pregnancy_id=<?= $patient['pregnancy_id'] ?>" class="btn btn-success" style="padding: 5px 10px; font-size: 0.8em;">Alerts</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>

<?php include '../layouts/footer.php'; ?>