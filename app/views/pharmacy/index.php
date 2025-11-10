<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Admin', 'Doctor', 'Nurse'])) {
    header('Location: ../auth/login.php');
    exit;
}

// Get pharmacy statistics
$stats_query = "
    SELECT 
        COUNT(*) as total_prescriptions,
        COUNT(CASE WHEN is_dispensed = 0 THEN 1 END) as pending_prescriptions,
        COUNT(CASE WHEN is_dispensed = 1 THEN 1 END) as dispensed_prescriptions,
        SUM(CASE WHEN payment_status = 'Paid' THEN cost ELSE 0 END) as total_revenue
    FROM prescriptions
";
$stats = $mysqli->query($stats_query)->fetch_assoc();

// Get recent prescriptions
$recent_prescriptions = $mysqli->query("
    SELECT pr.*, p.first_name, p.last_name, u.first_name as doctor_name
    FROM prescriptions pr
    JOIN anc_visits av ON pr.visit_id = av.visit_id
    JOIN pregnancies pg ON av.pregnancy_id = pg.pregnancy_id
    JOIN patients p ON pg.patient_id = p.patient_id
    LEFT JOIN users u ON pr.prescribed_by = u.user_id
    ORDER BY pr.created_at DESC
    LIMIT 10
");

$page_title = 'Pharmacy Dashboard - Maternal Care System';
$show_nav = true;
$breadcrumb = [
    ['title' => 'Pharmacy']
];
include '../layouts/header.php';
?>
    
    <div class="container">
        <div class="section">
            <h2>💊 Pharmacy Management</h2>
            <p style="margin-bottom: 0;">Manage prescriptions, inventory, and medication dispensing</p>
        </div>

        <div class="stats">
            <div class="stat-card">
                <div class="stat-number"><?= $stats['total_prescriptions'] ?></div>
                <div class="stat-label">Total Prescriptions</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $stats['pending_prescriptions'] ?></div>
                <div class="stat-label">Pending</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $stats['dispensed_prescriptions'] ?></div>
                <div class="stat-label">Dispensed</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= number_format($stats['total_revenue']) ?></div>
                <div class="stat-label">Revenue (RWF)</div>
            </div>
        </div>

        <div style="display: flex; gap: 15px; margin-bottom: 30px; flex-wrap: wrap;">
            <a href="prescriptions.php" class="btn btn-primary">📋 Manage Prescriptions</a>
            <a href="dispense.php" class="btn btn-success">💊 Dispense Medication</a>
            <a href="inventory.php" class="btn">📦 Inventory</a>
        </div>

        <div class="section">
            <h3 style="margin-bottom: 15px;">Recent Prescriptions</h3>
            <table>
                <thead>
                    <tr>
                        <th>Patient</th>
                        <th>Medication</th>
                        <th>Dosage</th>
                        <th>Status</th>
                        <th>Prescribed By</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($prescription = $recent_prescriptions->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($prescription['first_name'] . ' ' . $prescription['last_name']) ?></td>
                        <td><?= htmlspecialchars($prescription['medication_name']) ?></td>
                        <td><?= htmlspecialchars($prescription['dosage']) ?></td>
                        <td>
                            <?php if ($prescription['is_dispensed']): ?>
                                <span style="background: #2A9D8F; color: white; padding: 4px 8px; border-radius: 4px; font-size: 0.8em;">Dispensed</span>
                            <?php else: ?>
                                <span style="background: #E9C46A; color: #2D2D2D; padding: 4px 8px; border-radius: 4px; font-size: 0.8em;">Pending</span>
                            <?php endif; ?>
                        </td>
                        <td>Dr. <?= htmlspecialchars($prescription['doctor_name']) ?></td>
                        <td>
                            <a href="dispense.php?prescription_id=<?= $prescription['prescription_id'] ?>" class="btn btn-primary" style="padding: 5px 10px; font-size: 0.8em;">View</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php include '../layouts/footer.php'; ?>