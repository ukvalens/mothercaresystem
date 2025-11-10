<?php
session_start();
require_once '../../config/database.php';
require_once '../../config/activity_hooks.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Doctor', 'Nurse'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $pregnancy_id = $_POST['pregnancy_id'];
    $delivery_date = $_POST['delivery_date'];
    $delivery_time = $_POST['delivery_time'];
    $delivery_type = $_POST['delivery_type'];
    $baby_gender = $_POST['baby_gender'] ?? null;
    $birth_weight = $_POST['birth_weight'] ?? null;
    $apgar_1min = $_POST['apgar_1min'] ?? null;
    $complications = $_POST['complications'] ?? '';
    
    // Calculate gestational age at delivery
    $pregnancy_query = $mysqli->prepare("SELECT lmp_date FROM pregnancies WHERE pregnancy_id = ?");
    $pregnancy_query->bind_param("i", $pregnancy_id);
    $pregnancy_query->execute();
    $pregnancy_data = $pregnancy_query->get_result()->fetch_assoc();
    
    $gestational_days = (strtotime($delivery_date) - strtotime($pregnancy_data['lmp_date'])) / (60 * 60 * 24);
    $gestational_weeks = floor($gestational_days / 7);
    $gestational_days_remainder = $gestational_days % 7;
    
    // Start transaction
    $mysqli->begin_transaction();
    
    try {
        // Insert delivery record
        $stmt = $mysqli->prepare("INSERT INTO deliveries (pregnancy_id, delivery_date, delivery_time, gestational_age_weeks, gestational_age_days, mode_delivery, gender, birth_weight_kg, apgar_1min, maternal_complications, recorded_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issiissdiis", $pregnancy_id, $delivery_date, $delivery_time, $gestational_weeks, $gestational_days_remainder, $delivery_type, $baby_gender, $birth_weight, $apgar_1min, $complications, $_SESSION['user_id']);
        $stmt->execute();
        $delivery_id = $mysqli->insert_id;
        
        // Update pregnancy status to completed
        $update_stmt = $mysqli->prepare("UPDATE pregnancies SET current_status = 'Completed', updated_at = NOW() WHERE pregnancy_id = ?");
        $update_stmt->bind_param("i", $pregnancy_id);
        $update_stmt->execute();
        
        // Send notifications
        hook_delivery_recorded($delivery_id, $_SESSION['user_id']);
        
        $mysqli->commit();
        echo json_encode([
            'success' => true, 
            'message' => 'Delivery recorded successfully',
            'delivery_id' => $delivery_id,
            'gestational_age' => "{$gestational_weeks} weeks {$gestational_days_remainder} days"
        ]);
        
    } catch (Exception $e) {
        $mysqli->rollback();
        echo json_encode(['success' => false, 'message' => 'Error recording delivery: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>