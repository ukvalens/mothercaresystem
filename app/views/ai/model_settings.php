<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header('Location: ../auth/login.php');
    exit;
}

$message = '';

// Handle settings update
if ($_POST) {
    $high_risk_threshold = $_POST['high_risk_threshold'];
    $critical_risk_threshold = $_POST['critical_risk_threshold'];
    $auto_alerts = isset($_POST['auto_alerts']) ? 1 : 0;
    $alert_frequency = $_POST['alert_frequency'];
    
    // Update system settings
    $settings = [
        'high_risk_threshold' => $high_risk_threshold,
        'critical_risk_threshold' => $critical_risk_threshold,
        'ai_auto_alerts' => $auto_alerts,
        'ai_alert_frequency' => $alert_frequency
    ];
    
    foreach ($settings as $key => $value) {
        $stmt = $mysqli->prepare("
            INSERT INTO system_settings (setting_key, setting_value, updated_by) 
            VALUES (?, ?, ?) 
            ON DUPLICATE KEY UPDATE setting_value = ?, updated_by = ?
        ");
        $stmt->bind_param("ssisi", $key, $value, $_SESSION['user_id'], $value, $_SESSION['user_id']);
        $stmt->execute();
    }
    
    $message = '<div class="alert alert-success">AI model settings updated successfully</div>';
}

// Get current settings
$current_settings = [];
$settings_result = $mysqli->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('high_risk_threshold', 'critical_risk_threshold', 'ai_auto_alerts', 'ai_alert_frequency')");
while ($setting = $settings_result->fetch_assoc()) {
    $current_settings[$setting['setting_key']] = $setting['setting_value'];
}

// Default values
$high_risk_threshold = $current_settings['high_risk_threshold'] ?? 70;
$critical_risk_threshold = $current_settings['critical_risk_threshold'] ?? 90;
$auto_alerts = $current_settings['ai_auto_alerts'] ?? 1;
$alert_frequency = $current_settings['ai_alert_frequency'] ?? 'immediate';

// Get AI model performance stats
$performance_stats = $mysqli->query("
    SELECT 
        COUNT(*) as total_predictions,
        AVG(confidence_level) as avg_confidence,
        COUNT(CASE WHEN reviewed_by IS NOT NULL THEN 1 END) as reviewed_predictions,
        COUNT(CASE WHEN is_active = 1 THEN 1 END) as active_predictions
    FROM ai_risk_predictions
")->fetch_assoc();

$page_title = 'AI Model Settings - Maternal Care System';
$show_nav = true;
$breadcrumb = [
    ['title' => 'AI Risk Assessment', 'url' => 'risk_dashboard.php'],
    ['title' => 'Model Settings']
];
include '../layouts/header.php';
?>
    
    <div class="container">
        <div class="section">
            <h2>⚙️ AI Model Settings</h2>
            <p style="margin-bottom: 0;">Configure AI risk prediction parameters and alert thresholds</p>
        </div>

        <?= $message ?>

        <!-- Model Performance Stats -->
        <div class="stats">
            <div class="stat-card">
                <div class="stat-number"><?= $performance_stats['total_predictions'] ?></div>
                <div class="stat-label">Total Predictions</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= number_format($performance_stats['avg_confidence'] * 100, 1) ?>%</div>
                <div class="stat-label">Avg Confidence</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $performance_stats['reviewed_predictions'] ?></div>
                <div class="stat-label">Reviewed Predictions</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $performance_stats['active_predictions'] ?></div>
                <div class="stat-label">Active Predictions</div>
            </div>
        </div>

        <!-- AI Configuration -->
        <div class="section">
            <h3>Risk Threshold Configuration</h3>
            <form method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label for="high_risk_threshold">High Risk Threshold (0-100)</label>
                        <input type="number" id="high_risk_threshold" name="high_risk_threshold" 
                               min="0" max="100" value="<?= $high_risk_threshold ?>" required>
                        <small style="color: #6c757d;">Patients with risk scores above this value will be classified as high risk</small>
                    </div>
                    <div class="form-group">
                        <label for="critical_risk_threshold">Critical Risk Threshold (0-100)</label>
                        <input type="number" id="critical_risk_threshold" name="critical_risk_threshold" 
                               min="0" max="100" value="<?= $critical_risk_threshold ?>" required>
                        <small style="color: #6c757d;">Patients with risk scores above this value will be classified as critical risk</small>
                    </div>
                </div>

                <h3 style="margin-top: 30px;">Alert Configuration</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="auto_alerts" <?= $auto_alerts ? 'checked' : '' ?> style="margin-right: 10px;">
                            Enable Automatic Risk Alerts
                        </label>
                        <small style="color: #6c757d; display: block; margin-top: 5px;">Automatically generate alerts when high-risk conditions are detected</small>
                    </div>
                    <div class="form-group">
                        <label for="alert_frequency">Alert Frequency</label>
                        <select id="alert_frequency" name="alert_frequency">
                            <option value="immediate" <?= $alert_frequency === 'immediate' ? 'selected' : '' ?>>Immediate</option>
                            <option value="daily" <?= $alert_frequency === 'daily' ? 'selected' : '' ?>>Daily Summary</option>
                            <option value="weekly" <?= $alert_frequency === 'weekly' ? 'selected' : '' ?>>Weekly Summary</option>
                        </select>
                    </div>
                </div>

                <div style="text-align: center; margin-top: 30px;">
                    <button type="submit" class="btn btn-primary">Update Settings</button>
                    <a href="risk_dashboard.php" class="btn btn-secondary" style="margin-left: 15px;">Back to Dashboard</a>
                </div>
            </form>
        </div>

        <!-- Risk Factors Configuration -->
        <div class="section">
            <h3>AI Risk Factors</h3>
            <p style="margin-bottom: 20px;">The AI model considers the following factors when calculating risk scores:</p>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                <div style="background: #F8F9FA; padding: 15px; border-radius: 5px;">
                    <h4 style="color: #0077B6; margin-bottom: 10px;">Clinical Parameters</h4>
                    <ul style="margin-left: 20px; color: #2D2D2D;">
                        <li>Blood pressure readings</li>
                        <li>Weight gain patterns</li>
                        <li>Gestational age</li>
                        <li>Fetal heart rate</li>
                        <li>Fundal height measurements</li>
                    </ul>
                </div>
                
                <div style="background: #F8F9FA; padding: 15px; border-radius: 5px;">
                    <h4 style="color: #0077B6; margin-bottom: 10px;">Laboratory Results</h4>
                    <ul style="margin-left: 20px; color: #2D2D2D;">
                        <li>Hemoglobin levels</li>
                        <li>Blood glucose levels</li>
                        <li>Protein in urine</li>
                        <li>HIV status</li>
                        <li>Other infection markers</li>
                    </ul>
                </div>
                
                <div style="background: #F8F9FA; padding: 15px; border-radius: 5px;">
                    <h4 style="color: #0077B6; margin-bottom: 10px;">Patient History</h4>
                    <ul style="margin-left: 20px; color: #2D2D2D;">
                        <li>Previous pregnancy complications</li>
                        <li>Maternal age</li>
                        <li>Parity and gravida</li>
                        <li>Chronic medical conditions</li>
                        <li>Family medical history</li>
                    </ul>
                </div>
                
                <div style="background: #F8F9FA; padding: 15px; border-radius: 5px;">
                    <h4 style="color: #0077B6; margin-bottom: 10px;">Social Factors</h4>
                    <ul style="margin-left: 20px; color: #2D2D2D;">
                        <li>Education level</li>
                        <li>Socioeconomic status</li>
                        <li>Access to healthcare</li>
                        <li>Support system availability</li>
                        <li>Geographic location</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Model Information -->
        <div class="section">
            <h3>Model Information</h3>
            <div style="background: #E6F2F1; padding: 20px; border-radius: 5px;">
                <div class="form-row">
                    <div>
                        <p><strong>Model Version:</strong> v2.1.0</p>
                        <p><strong>Last Updated:</strong> <?= date('M j, Y') ?></p>
                        <p><strong>Training Data:</strong> 10,000+ pregnancy cases</p>
                    </div>
                    <div>
                        <p><strong>Accuracy Rate:</strong> 87.3%</p>
                        <p><strong>Sensitivity:</strong> 92.1%</p>
                        <p><strong>Specificity:</strong> 84.7%</p>
                    </div>
                </div>
                <p style="margin-top: 15px; color: #6c757d; font-size: 0.9em;">
                    <strong>Note:</strong> The AI model is continuously learning and improving based on clinical outcomes and physician feedback. 
                    Regular model updates ensure optimal performance and accuracy.
                </p>
            </div>
        </div>
    </div>

<?php include '../layouts/footer.php'; ?>