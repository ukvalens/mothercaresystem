<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Admin', 'Doctor'])) {
    header('Location: ../auth/login.php');
    exit;
}

$message = '';

// Handle alert actions
if ($_POST && isset($_POST['action'])) {
    $alert_id = $_POST['alert_id'];
    $action = $_POST['action'];
    
    if ($action === 'acknowledge') {
        $stmt = $mysqli->prepare("UPDATE risk_alerts SET status = 'Acknowledged', acknowledged_by = ?, acknowledged_at = NOW() WHERE alert_id = ?");
        $stmt->bind_param("ii", $_SESSION['user_id'], $alert_id);
        if ($stmt->execute()) {
            $message = '<div class="alert alert-success">Alert acknowledged successfully</div>';
        }
    } elseif ($action === 'resolve') {
        $resolution_notes = $_POST['resolution_notes'];
        $stmt = $mysqli->prepare("UPDATE risk_alerts SET status = 'Resolved', resolved_by = ?, resolved_at = NOW(), resolution_notes = ? WHERE alert_id = ?");
        $stmt->bind_param("isi", $_SESSION['user_id'], $resolution_notes, $alert_id);
        if ($stmt->execute()) {
            $message = '<div class="alert alert-success">Alert resolved successfully</div>';
        }
    }
}

// Get filter parameters
$status_filter = $_GET['status'] ?? 'active';
$alert_level = $_GET['alert_level'] ?? '';

// Build query conditions
$conditions = ["1=1"];
if ($status_filter === 'active') {
    $conditions[] = "ra.status IN ('New', 'Acknowledged')";
} elseif ($status_filter !== 'all') {
    $conditions[] = "ra.status = '" . $mysqli->real_escape_string($status_filter) . "'";
}

if ($alert_level) {
    $conditions[] = "ra.alert_level = '" . $mysqli->real_escape_string($alert_level) . "'";
}

$where_clause = "WHERE " . implode(" AND ", $conditions);

// Get risk alerts
$alerts_query = "
    SELECT ra.*, p.first_name, p.last_name, p.contact_number,
           pr.ai_risk_score, pr.risk_level, pr.expected_delivery_date,
           u1.first_name as acknowledged_by_name, u2.first_name as resolved_by_name
    FROM risk_alerts ra
    JOIN ai_risk_predictions arp ON ra.prediction_id = arp.prediction_id
    JOIN pregnancies pr ON arp.pregnancy_id = pr.pregnancy_id
    JOIN patients p ON ra.patient_id = p.patient_id
    LEFT JOIN users u1 ON ra.acknowledged_by = u1.user_id
    LEFT JOIN users u2 ON ra.resolved_by = u2.user_id
    $where_clause
    ORDER BY ra.created_at DESC
";

$alerts = $mysqli->query($alerts_query);

// Get alert statistics
$alert_stats = $mysqli->query("
    SELECT 
        COUNT(*) as total_alerts,
        COUNT(CASE WHEN status = 'New' THEN 1 END) as new_alerts,
        COUNT(CASE WHEN status = 'Acknowledged' THEN 1 END) as acknowledged_alerts,
        COUNT(CASE WHEN alert_level = 'Critical' AND status IN ('New', 'Acknowledged') THEN 1 END) as critical_active
    FROM risk_alerts
")->fetch_assoc();

$page_title = 'Risk Alerts - Maternal Care System';
$show_nav = true;
$breadcrumb = [
    ['title' => 'AI Risk Assessment', 'url' => 'risk_dashboard.php'],
    ['title' => 'Risk Alerts']
];
include '../layouts/header.php';
?>
    
    <div class="container">
        <div class="section">
            <h2>⚠️ Risk Alerts Management</h2>
            <p style="margin-bottom: 0;">Monitor and manage AI-generated risk alerts for high-risk pregnancies</p>
        </div>

        <?= $message ?>

        <!-- Alert Statistics -->
        <div class="stats">
            <div class="stat-card">
                <div class="stat-number"><?= $alert_stats['total_alerts'] ?></div>
                <div class="stat-label">Total Alerts</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $alert_stats['new_alerts'] ?></div>
                <div class="stat-label">New Alerts</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $alert_stats['acknowledged_alerts'] ?></div>
                <div class="stat-label">Acknowledged</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $alert_stats['critical_active'] ?></div>
                <div class="stat-label">Critical Active</div>
            </div>
        </div>

        <!-- Filters -->
        <div class="section">
            <form method="GET">
                <div class="form-row">
                    <div class="form-group">
                        <label for="status">Status Filter</label>
                        <select id="status" name="status">
                            <option value="active" <?= $status_filter === 'active' ? 'selected' : '' ?>>Active Alerts</option>
                            <option value="all" <?= $status_filter === 'all' ? 'selected' : '' ?>>All Alerts</option>
                            <option value="New" <?= $status_filter === 'New' ? 'selected' : '' ?>>New Only</option>
                            <option value="Acknowledged" <?= $status_filter === 'Acknowledged' ? 'selected' : '' ?>>Acknowledged</option>
                            <option value="Resolved" <?= $status_filter === 'Resolved' ? 'selected' : '' ?>>Resolved</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="alert_level">Alert Level</label>
                        <select id="alert_level" name="alert_level">
                            <option value="">All Levels</option>
                            <option value="High" <?= $alert_level === 'High' ? 'selected' : '' ?>>High</option>
                            <option value="Critical" <?= $alert_level === 'Critical' ? 'selected' : '' ?>>Critical</option>
                        </select>
                    </div>
                    <div style="align-self: end;">
                        <button type="submit" class="btn btn-primary">Filter</button>
                        <a href="risk_alerts.php" class="btn btn-secondary">Clear</a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Alerts List -->
        <div class="section">
            <h3 style="margin-bottom: 15px;">Risk Alerts</h3>
            <?php if ($alerts->num_rows === 0): ?>
            <div style="padding: 40px; text-align: center; color: #6C757D;">
                <p>No risk alerts found for the selected criteria.</p>
            </div>
            <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Patient</th>
                        <th>Alert Level</th>
                        <th>Risk Score</th>
                        <th>Alert Message</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($alert = $alerts->fetch_assoc()): ?>
                    <tr style="<?= $alert['alert_level'] === 'Critical' && $alert['status'] === 'New' ? 'background: #FFF5F5;' : '' ?>">
                        <td>
                            <strong><?= htmlspecialchars($alert['first_name'] . ' ' . $alert['last_name']) ?></strong><br>
                            <small><?= htmlspecialchars($alert['contact_number']) ?></small>
                        </td>
                        <td>
                            <span style="background: <?= $alert['alert_level'] === 'Critical' ? '#D90429' : '#E63946' ?>; color: white; padding: 4px 8px; border-radius: 4px; font-size: 0.8em;">
                                <?= $alert['alert_level'] ?>
                            </span>
                        </td>
                        <td>
                            <strong><?= number_format($alert['ai_risk_score'], 1) ?></strong>
                            <div style="width: 50px; height: 4px; background: #E6F2F1; border-radius: 2px; margin-top: 2px;">
                                <div style="width: <?= $alert['ai_risk_score'] ?>%; height: 100%; background: <?= $alert['ai_risk_score'] >= 90 ? '#D90429' : '#E63946' ?>; border-radius: 2px;"></div>
                            </div>
                        </td>
                        <td><?= htmlspecialchars($alert['alert_message']) ?></td>
                        <td>
                            <?php if ($alert['status'] === 'New'): ?>
                                <span style="background: #E63946; color: white; padding: 4px 8px; border-radius: 4px; font-size: 0.8em;">New</span>
                            <?php elseif ($alert['status'] === 'Acknowledged'): ?>
                                <span style="background: #E9C46A; color: #2D2D2D; padding: 4px 8px; border-radius: 4px; font-size: 0.8em;">Acknowledged</span><br>
                                <small>by <?= htmlspecialchars($alert['acknowledged_by_name']) ?></small>
                            <?php elseif ($alert['status'] === 'Resolved'): ?>
                                <span style="background: #2A9D8F; color: white; padding: 4px 8px; border-radius: 4px; font-size: 0.8em;">Resolved</span><br>
                                <small>by <?= htmlspecialchars($alert['resolved_by_name']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?= date('M j, Y g:i A', strtotime($alert['created_at'])) ?></td>
                        <td>
                            <?php if ($alert['status'] === 'New'): ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="alert_id" value="<?= $alert['alert_id'] ?>">
                                    <input type="hidden" name="action" value="acknowledge">
                                    <button type="submit" class="btn btn-primary" style="padding: 3px 6px; font-size: 0.7em;">Acknowledge</button>
                                </form>
                            <?php elseif ($alert['status'] === 'Acknowledged'): ?>
                                <button onclick="showResolveForm(<?= $alert['alert_id'] ?>)" class="btn btn-success" style="padding: 3px 6px; font-size: 0.7em;">Resolve</button>
                            <?php else: ?>
                                <span style="color: #6c757d; font-size: 0.8em;">Completed</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Resolve Alert Modal -->
    <div id="resolveModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 30px; border-radius: 10px; width: 90%; max-width: 500px;">
            <h3>Resolve Alert</h3>
            <form method="POST" id="resolveForm">
                <input type="hidden" name="alert_id" id="resolveAlertId">
                <input type="hidden" name="action" value="resolve">
                <div class="form-group">
                    <label for="resolution_notes">Resolution Notes</label>
                    <textarea id="resolution_notes" name="resolution_notes" required placeholder="Describe the actions taken to resolve this alert..."></textarea>
                </div>
                <div style="text-align: center; margin-top: 20px;">
                    <button type="submit" class="btn btn-success">Resolve Alert</button>
                    <button type="button" onclick="hideResolveForm()" class="btn btn-secondary" style="margin-left: 15px;">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function showResolveForm(alertId) {
            document.getElementById('resolveAlertId').value = alertId;
            document.getElementById('resolveModal').style.display = 'block';
        }
        
        function hideResolveForm() {
            document.getElementById('resolveModal').style.display = 'none';
            document.getElementById('resolution_notes').value = '';
        }
    </script>

<?php include '../layouts/footer.php'; ?>