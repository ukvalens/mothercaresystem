<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Admin', 'Doctor', 'Nurse'])) {
    header('Location: ../auth/login.php');
    exit;
}

// Get filter parameters
$status_filter = $_GET['status'] ?? '';
$date_filter = $_GET['date'] ?? '';

// Build query conditions
$conditions = ["1=1"];
$params = [];
$types = "";

if ($status_filter) {
    if ($status_filter === 'pending') {
        $conditions[] = "pr.is_dispensed = 0";
    } elseif ($status_filter === 'dispensed') {
        $conditions[] = "pr.is_dispensed = 1";
    }
}

if ($date_filter) {
    $conditions[] = "DATE(pr.created_at) = ?";
    $params[] = $date_filter;
    $types .= "s";
}

$where_clause = "WHERE " . implode(" AND ", $conditions);

// Get prescriptions
$prescriptions_query = "
    SELECT pr.*, p.first_name, p.last_name, p.contact_number,
           u1.first_name as doctor_name, u2.first_name as dispensed_by_name,
           av.visit_date
    FROM prescriptions pr
    JOIN anc_visits av ON pr.visit_id = av.visit_id
    JOIN pregnancies pg ON av.pregnancy_id = pg.pregnancy_id
    JOIN patients p ON pg.patient_id = p.patient_id
    LEFT JOIN users u1 ON pr.prescribed_by = u1.user_id
    LEFT JOIN users u2 ON pr.dispensed_by = u2.user_id
    $where_clause
    ORDER BY pr.created_at DESC
";

if ($params) {
    $stmt = $mysqli->prepare($prescriptions_query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $prescriptions = $stmt->get_result();
} else {
    $prescriptions = $mysqli->query($prescriptions_query);
}

$page_title = 'Prescription Management - Maternal Care System';
$show_nav = true;
$breadcrumb = [
    ['title' => 'Pharmacy', 'url' => 'index.php'],
    ['title' => 'Prescriptions']
];
include '../layouts/header.php';
?>
    
    <div class="container">
        <div class="section">
            <h2>📋 Prescription Management</h2>
            <p style="margin-bottom: 0;">View and manage all patient prescriptions</p>
        </div>

        <!-- Filters -->
        <div class="section">
            <form method="GET">
                <div class="form-row">
                    <div class="form-group">
                        <label for="status">Status Filter</label>
                        <select id="status" name="status">
                            <option value="">All Status</option>
                            <option value="pending" <?= $status_filter === 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="dispensed" <?= $status_filter === 'dispensed' ? 'selected' : '' ?>>Dispensed</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="date">Date Filter</label>
                        <input type="date" id="date" name="date" value="<?= htmlspecialchars($date_filter) ?>">
                    </div>
                    <div style="align-self: end;">
                        <button type="submit" class="btn btn-primary">Filter</button>
                        <a href="prescriptions.php" class="btn btn-secondary">Clear</a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Prescriptions Table -->
        <div class="section">
            <h3 style="margin-bottom: 15px;">Prescriptions List</h3>
            <table>
                <thead>
                    <tr>
                        <th>Patient</th>
                        <th>Medication</th>
                        <th>Dosage & Instructions</th>
                        <th>Cost</th>
                        <th>Status</th>
                        <th>Prescribed By</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($prescriptions->num_rows === 0): ?>
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 40px; color: #6c757d;">
                            No prescriptions found for the selected criteria.
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php while ($prescription = $prescriptions->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($prescription['first_name'] . ' ' . $prescription['last_name']) ?></strong><br>
                            <small><?= htmlspecialchars($prescription['contact_number']) ?></small>
                        </td>
                        <td><?= htmlspecialchars($prescription['medication_name']) ?></td>
                        <td>
                            <strong><?= htmlspecialchars($prescription['dosage']) ?></strong><br>
                            <small><?= htmlspecialchars($prescription['frequency']) ?> for <?= htmlspecialchars($prescription['duration']) ?></small><br>
                            <?php if ($prescription['instructions']): ?>
                                <small style="color: #6c757d;"><?= htmlspecialchars($prescription['instructions']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?= number_format($prescription['cost']) ?> RWF</td>
                        <td>
                            <?php if ($prescription['is_dispensed']): ?>
                                <span style="background: #2A9D8F; color: white; padding: 4px 8px; border-radius: 4px; font-size: 0.8em;">Dispensed</span><br>
                                <small>by <?= htmlspecialchars($prescription['dispensed_by_name']) ?></small>
                            <?php else: ?>
                                <span style="background: #E9C46A; color: #2D2D2D; padding: 4px 8px; border-radius: 4px; font-size: 0.8em;">Pending</span>
                            <?php endif; ?>
                        </td>
                        <td>Dr. <?= htmlspecialchars($prescription['doctor_name']) ?></td>
                        <td><?= date('M j, Y', strtotime($prescription['created_at'])) ?></td>
                        <td>
                            <?php if (!$prescription['is_dispensed']): ?>
                                <a href="dispense.php?prescription_id=<?= $prescription['prescription_id'] ?>" class="btn btn-success" style="padding: 5px 10px; font-size: 0.8em;">Dispense</a>
                            <?php else: ?>
                                <span style="color: #6c757d; font-size: 0.8em;">Completed</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php include '../layouts/footer.php'; ?>