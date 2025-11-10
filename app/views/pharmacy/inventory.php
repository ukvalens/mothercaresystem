<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Admin', 'Doctor', 'Nurse'])) {
    header('Location: ../auth/login.php');
    exit;
}

$message = '';

// Handle inventory updates
if ($_POST && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_medication') {
        $name = $_POST['medication_name'];
        $category = $_POST['category'];
        $unit_cost = $_POST['unit_cost'];
        $stock_quantity = $_POST['stock_quantity'];
        $reorder_level = $_POST['reorder_level'];
        $expiry_date = $_POST['expiry_date'];
        
        $stmt = $mysqli->prepare("
            INSERT INTO medication_inventory (medication_name, category, unit_cost, stock_quantity, reorder_level, expiry_date, added_by)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("ssdiiis", $name, $category, $unit_cost, $stock_quantity, $reorder_level, $expiry_date, $_SESSION['user_id']);
        
        if ($stmt->execute()) {
            $message = '<div class="alert alert-success">Medication added to inventory successfully</div>';
        } else {
            $message = '<div class="alert alert-error">Error adding medication to inventory</div>';
        }
    }
}

// Create inventory table if it doesn't exist
$mysqli->query("
    CREATE TABLE IF NOT EXISTS medication_inventory (
        inventory_id INT PRIMARY KEY AUTO_INCREMENT,
        medication_name VARCHAR(100) NOT NULL,
        category ENUM('Prenatal Vitamins', 'Antibiotics', 'Pain Relief', 'Iron Supplements', 'Other') NOT NULL,
        unit_cost DECIMAL(10,2) NOT NULL,
        stock_quantity INT NOT NULL DEFAULT 0,
        reorder_level INT NOT NULL DEFAULT 10,
        expiry_date DATE,
        is_active BOOLEAN DEFAULT TRUE,
        added_by INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (added_by) REFERENCES users(user_id),
        INDEX idx_medication_name (medication_name),
        INDEX idx_category (category)
    )
");

// Get inventory statistics
$stats = $mysqli->query("
    SELECT 
        COUNT(*) as total_medications,
        COUNT(CASE WHEN stock_quantity <= reorder_level THEN 1 END) as low_stock,
        COUNT(CASE WHEN expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 1 END) as expiring_soon,
        SUM(stock_quantity * unit_cost) as total_value
    FROM medication_inventory WHERE is_active = 1
")->fetch_assoc();

// Get inventory items
$inventory = $mysqli->query("
    SELECT mi.*, u.first_name as added_by_name
    FROM medication_inventory mi
    LEFT JOIN users u ON mi.added_by = u.user_id
    WHERE mi.is_active = 1
    ORDER BY mi.medication_name ASC
");

$page_title = 'Pharmacy Inventory - Maternal Care System';
$show_nav = true;
$breadcrumb = [
    ['title' => 'Pharmacy', 'url' => 'index.php'],
    ['title' => 'Inventory']
];
include '../layouts/header.php';
?>
    
    <div class="container">
        <div class="section">
            <h2>📦 Pharmacy Inventory</h2>
            <p style="margin-bottom: 0;">Manage medication stock and inventory levels</p>
        </div>

        <?= $message ?>

        <!-- Statistics -->
        <div class="stats">
            <div class="stat-card">
                <div class="stat-number"><?= $stats['total_medications'] ?></div>
                <div class="stat-label">Total Medications</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $stats['low_stock'] ?></div>
                <div class="stat-label">Low Stock</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $stats['expiring_soon'] ?></div>
                <div class="stat-label">Expiring Soon</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= number_format($stats['total_value']) ?></div>
                <div class="stat-label">Total Value (RWF)</div>
            </div>
        </div>

        <!-- Add New Medication -->
        <div class="section">
            <h3>Add New Medication</h3>
            <form method="POST">
                <input type="hidden" name="action" value="add_medication">
                <div class="form-row">
                    <div class="form-group">
                        <label for="medication_name">Medication Name</label>
                        <input type="text" id="medication_name" name="medication_name" required>
                    </div>
                    <div class="form-group">
                        <label for="category">Category</label>
                        <select id="category" name="category" required>
                            <option value="">Select Category</option>
                            <option value="Prenatal Vitamins">Prenatal Vitamins</option>
                            <option value="Antibiotics">Antibiotics</option>
                            <option value="Pain Relief">Pain Relief</option>
                            <option value="Iron Supplements">Iron Supplements</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="unit_cost">Unit Cost (RWF)</label>
                        <input type="number" id="unit_cost" name="unit_cost" step="0.01" min="0" required>
                    </div>
                    <div class="form-group">
                        <label for="stock_quantity">Initial Stock Quantity</label>
                        <input type="number" id="stock_quantity" name="stock_quantity" min="0" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="reorder_level">Reorder Level</label>
                        <input type="number" id="reorder_level" name="reorder_level" min="1" value="10" required>
                    </div>
                    <div class="form-group">
                        <label for="expiry_date">Expiry Date</label>
                        <input type="date" id="expiry_date" name="expiry_date">
                    </div>
                </div>
                <div style="text-align: center;">
                    <button type="submit" class="btn btn-primary">Add Medication</button>
                </div>
            </form>
        </div>

        <!-- Inventory List -->
        <div class="section">
            <h3 style="margin-bottom: 15px;">Current Inventory</h3>
            <table>
                <thead>
                    <tr>
                        <th>Medication</th>
                        <th>Category</th>
                        <th>Unit Cost</th>
                        <th>Stock</th>
                        <th>Reorder Level</th>
                        <th>Expiry Date</th>
                        <th>Total Value</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($inventory->num_rows === 0): ?>
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 40px; color: #6c757d;">
                            No medications in inventory. Add some medications to get started.
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php while ($item = $inventory->fetch_assoc()): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($item['medication_name']) ?></strong></td>
                        <td><?= $item['category'] ?></td>
                        <td><?= number_format($item['unit_cost']) ?> RWF</td>
                        <td>
                            <strong><?= $item['stock_quantity'] ?></strong>
                            <?php if ($item['stock_quantity'] <= $item['reorder_level']): ?>
                                <span style="color: #E63946; font-size: 0.8em;">⚠️ Low</span>
                            <?php endif; ?>
                        </td>
                        <td><?= $item['reorder_level'] ?></td>
                        <td>
                            <?php if ($item['expiry_date']): ?>
                                <?= date('M j, Y', strtotime($item['expiry_date'])) ?>
                                <?php if (strtotime($item['expiry_date']) <= strtotime('+30 days')): ?>
                                    <span style="color: #E63946; font-size: 0.8em;">⚠️ Expiring</span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span style="color: #6c757d;">N/A</span>
                            <?php endif; ?>
                        </td>
                        <td><?= number_format($item['stock_quantity'] * $item['unit_cost']) ?> RWF</td>
                        <td>
                            <?php if ($item['stock_quantity'] <= $item['reorder_level']): ?>
                                <span style="background: #E63946; color: white; padding: 4px 8px; border-radius: 4px; font-size: 0.8em;">Low Stock</span>
                            <?php elseif ($item['expiry_date'] && strtotime($item['expiry_date']) <= strtotime('+30 days')): ?>
                                <span style="background: #E9C46A; color: #2D2D2D; padding: 4px 8px; border-radius: 4px; font-size: 0.8em;">Expiring</span>
                            <?php else: ?>
                                <span style="background: #2A9D8F; color: white; padding: 4px 8px; border-radius: 4px; font-size: 0.8em;">Good</span>
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