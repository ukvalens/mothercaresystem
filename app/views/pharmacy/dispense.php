<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Admin', 'Doctor', 'Nurse'])) {
    header('Location: ../auth/login.php');
    exit;
}

$message = '';
$prescription_id = $_GET['prescription_id'] ?? '';

// Handle dispensing
if ($_POST) {
    $prescription_id = $_POST['prescription_id'];
    $quantity_dispensed = $_POST['quantity_dispensed'];
    $notes = $_POST['notes'];
    
    $stmt = $mysqli->prepare("
        UPDATE prescriptions 
        SET is_dispensed = 1, dispensed_by = ?, dispensed_at = NOW(), 
            quantity = ?, instructions = CONCAT(COALESCE(instructions, ''), '\nDispensing Notes: ', ?)
        WHERE prescription_id = ?
    ");
    $stmt->bind_param("iisi", $_SESSION['user_id'], $quantity_dispensed, $notes, $prescription_id);
    
    if ($stmt->execute()) {
        $message = '<div class="alert alert-success">Medication dispensed successfully</div>';
        
        // Send notification to prescribing doctor
        $notification_stmt = $mysqli->prepare("
            INSERT INTO notifications (user_id, subject, message, notification_type, channel, status)
            SELECT pr.prescribed_by, 'Medication Dispensed', 
                   CONCAT('Medication dispensed for patient: ', p.first_name, ' ', p.last_name, '. Medication: ', pr.medication_name),
                   'System', 'Email', 'Pending'
            FROM prescriptions pr
            JOIN anc_visits av ON pr.visit_id = av.visit_id
            JOIN pregnancies pg ON av.pregnancy_id = pg.pregnancy_id
            JOIN patients p ON pg.patient_id = p.patient_id
            WHERE pr.prescription_id = ?
        ");
        $notification_stmt->bind_param("i", $prescription_id);
        $notification_stmt->execute();
    } else {
        $message = '<div class="alert alert-error">Error dispensing medication</div>';
    }
}

// Get prescription details
if ($prescription_id) {
    $prescription_query = "
        SELECT pr.*, p.first_name, p.last_name, p.contact_number, p.known_allergies,
               u.first_name as doctor_name, av.visit_date
        FROM prescriptions pr
        JOIN anc_visits av ON pr.visit_id = av.visit_id
        JOIN pregnancies pg ON av.pregnancy_id = pg.pregnancy_id
        JOIN patients p ON pg.patient_id = p.patient_id
        LEFT JOIN users u ON pr.prescribed_by = u.user_id
        WHERE pr.prescription_id = ?
    ";
    $stmt = $mysqli->prepare($prescription_query);
    $stmt->bind_param("i", $prescription_id);
    $stmt->execute();
    $prescription = $stmt->get_result()->fetch_assoc();
} else {
    // Get pending prescriptions
    $pending_prescriptions = $mysqli->query("
        SELECT pr.*, p.first_name, p.last_name, u.first_name as doctor_name
        FROM prescriptions pr
        JOIN anc_visits av ON pr.visit_id = av.visit_id
        JOIN pregnancies pg ON av.pregnancy_id = pg.pregnancy_id
        JOIN patients p ON pg.patient_id = p.patient_id
        LEFT JOIN users u ON pr.prescribed_by = u.user_id
        WHERE pr.is_dispensed = 0 AND pr.payment_status = 'Paid'
        ORDER BY pr.created_at ASC
    ");
}

$page_title = 'Dispense Medication - Maternal Care System';
$show_nav = true;
$breadcrumb = [
    ['title' => 'Pharmacy', 'url' => 'index.php'],
    ['title' => 'Dispense Medication']
];
include '../layouts/header.php';
?>
    
    <div class="container">
        <div class="section">
            <h2>💊 Dispense Medication</h2>
            <p style="margin-bottom: 0;">Process medication dispensing for patients</p>
        </div>

        <?= $message ?>

        <?php if ($prescription_id && $prescription): ?>
        <!-- Single prescription dispensing -->
        <div class="section">
            <div style="background: #F8F9FA; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                <h3>Prescription Details</h3>
                <div class="form-row">
                    <div>
                        <p><strong>Patient:</strong> <?= htmlspecialchars($prescription['first_name'] . ' ' . $prescription['last_name']) ?></p>
                        <p><strong>Contact:</strong> <?= htmlspecialchars($prescription['contact_number']) ?></p>
                        <p><strong>Visit Date:</strong> <?= date('M j, Y', strtotime($prescription['visit_date'])) ?></p>
                    </div>
                    <div>
                        <p><strong>Prescribed by:</strong> Dr. <?= htmlspecialchars($prescription['doctor_name']) ?></p>
                        <p><strong>Date Prescribed:</strong> <?= date('M j, Y', strtotime($prescription['created_at'])) ?></p>
                        <?php if ($prescription['known_allergies']): ?>
                            <p><strong style="color: #E63946;">Allergies:</strong> <?= htmlspecialchars($prescription['known_allergies']) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div style="background: #E6F2F1; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                <h4>Medication Information</h4>
                <p><strong>Medication:</strong> <?= htmlspecialchars($prescription['medication_name']) ?></p>
                <p><strong>Dosage:</strong> <?= htmlspecialchars($prescription['dosage']) ?></p>
                <p><strong>Frequency:</strong> <?= htmlspecialchars($prescription['frequency']) ?></p>
                <p><strong>Duration:</strong> <?= htmlspecialchars($prescription['duration']) ?></p>
                <p><strong>Prescribed Quantity:</strong> <?= $prescription['quantity'] ?></p>
                <?php if ($prescription['instructions']): ?>
                    <p><strong>Instructions:</strong> <?= htmlspecialchars($prescription['instructions']) ?></p>
                <?php endif; ?>
                <p><strong>Cost:</strong> <?= number_format($prescription['cost']) ?> RWF</p>
            </div>

            <?php if (!$prescription['is_dispensed']): ?>
            <form method="POST">
                <input type="hidden" name="prescription_id" value="<?= $prescription['prescription_id'] ?>">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="quantity_dispensed">Quantity Dispensed</label>
                        <input type="number" id="quantity_dispensed" name="quantity_dispensed" 
                               value="<?= $prescription['quantity'] ?>" min="1" required>
                    </div>
                    <div class="form-group">
                        <label for="notes">Dispensing Notes</label>
                        <textarea id="notes" name="notes" placeholder="Any additional notes or instructions..."></textarea>
                    </div>
                </div>

                <div style="text-align: center; margin-top: 20px;">
                    <button type="submit" class="btn btn-success">Dispense Medication</button>
                    <a href="dispense.php" class="btn btn-secondary" style="margin-left: 15px;">Back to List</a>
                </div>
            </form>
            <?php else: ?>
            <div style="text-align: center; padding: 20px; background: #2A9D8F; color: white; border-radius: 5px;">
                <h4>✅ Medication Already Dispensed</h4>
                <p>Dispensed on <?= date('M j, Y g:i A', strtotime($prescription['dispensed_at'])) ?></p>
            </div>
            <?php endif; ?>
        </div>

        <?php else: ?>
        <!-- Pending prescriptions list -->
        <div class="section">
            <h3 style="margin-bottom: 15px;">Pending Prescriptions (Payment Confirmed)</h3>
            
            <?php if ($pending_prescriptions->num_rows === 0): ?>
            <div style="padding: 40px; text-align: center; color: #6C757D;">
                <p>No pending prescriptions with confirmed payment found.</p>
                <a href="prescriptions.php" class="btn btn-primary" style="margin-top: 15px;">View All Prescriptions</a>
            </div>
            <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Patient</th>
                        <th>Medication</th>
                        <th>Dosage</th>
                        <th>Quantity</th>
                        <th>Cost</th>
                        <th>Prescribed By</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($prescription = $pending_prescriptions->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($prescription['first_name'] . ' ' . $prescription['last_name']) ?></td>
                        <td><?= htmlspecialchars($prescription['medication_name']) ?></td>
                        <td><?= htmlspecialchars($prescription['dosage']) ?></td>
                        <td><?= $prescription['quantity'] ?></td>
                        <td><?= number_format($prescription['cost']) ?> RWF</td>
                        <td>Dr. <?= htmlspecialchars($prescription['doctor_name']) ?></td>
                        <td>
                            <a href="dispense.php?prescription_id=<?= $prescription['prescription_id'] ?>" class="btn btn-success" style="padding: 5px 10px; font-size: 0.8em;">Dispense</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

<?php include '../layouts/footer.php'; ?>