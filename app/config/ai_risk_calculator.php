<?php
/**
 * AI Risk Calculator for Maternal Health
 * Calculates pregnancy risk scores based on multiple factors
 */

function calculateRiskScore($patient_data, $visit_data = null, $lab_data = null) {
    $risk_score = 0;
    $risk_factors = [];
    
    // Age-based risk (15% weight)
    $age = $patient_data['age'] ?? 25;
    if ($age < 18 || $age > 35) {
        $age_risk = ($age < 18) ? min(20, (18 - $age) * 2) : min(25, ($age - 35) * 1.5);
        $risk_score += $age_risk;
        $risk_factors[] = "Maternal age: $age years";
    }
    
    // Obstetric history (20% weight)
    $gravida = $patient_data['gravida'] ?? 1;
    $parity = $patient_data['parity'] ?? 0;
    if ($gravida > 5) {
        $risk_score += 15;
        $risk_factors[] = "Grand multiparity (G$gravida)";
    }
    if ($parity == 0 && $age > 30) {
        $risk_score += 10;
        $risk_factors[] = "Elderly primigravida";
    }
    
    // Medical history (25% weight)
    if (!empty($patient_data['medical_history'])) {
        $conditions = strtolower($patient_data['medical_history']);
        if (strpos($conditions, 'hypertension') !== false) {
            $risk_score += 20;
            $risk_factors[] = "Pre-existing hypertension";
        }
        if (strpos($conditions, 'diabetes') !== false) {
            $risk_score += 25;
            $risk_factors[] = "Pre-existing diabetes";
        }
        if (strpos($conditions, 'heart') !== false) {
            $risk_score += 30;
            $risk_factors[] = "Cardiac condition";
        }
    }
    
    // Current visit data (25% weight)
    if ($visit_data) {
        // Blood pressure
        $systolic = $visit_data['blood_pressure_systolic'] ?? 120;
        $diastolic = $visit_data['blood_pressure_diastolic'] ?? 80;
        if ($systolic >= 140 || $diastolic >= 90) {
            $bp_risk = min(25, ($systolic - 140) * 0.5 + ($diastolic - 90) * 0.8);
            $risk_score += max(15, $bp_risk);
            $risk_factors[] = "Elevated blood pressure ($systolic/$diastolic)";
        }
        
        // Weight/BMI
        if (isset($visit_data['bmi']) && $visit_data['bmi'] > 30) {
            $risk_score += 10;
            $risk_factors[] = "Obesity (BMI: {$visit_data['bmi']})";
        }
        
        // Proteinuria
        if (isset($visit_data['proteinuria']) && $visit_data['proteinuria'] !== 'Negative') {
            $protein_risk = ($visit_data['proteinuria'] === '3+') ? 20 : 10;
            $risk_score += $protein_risk;
            $risk_factors[] = "Proteinuria: {$visit_data['proteinuria']}";
        }
    }
    
    // Laboratory data (15% weight)
    if ($lab_data) {
        foreach ($lab_data as $lab) {
            if ($lab['test_name'] === 'Complete Blood Count (CBC)' && $lab['result_status'] === 'Abnormal') {
                $risk_score += 8;
                $risk_factors[] = "Abnormal CBC results";
            }
            if ($lab['test_name'] === 'Blood Sugar (Random)' && $lab['result_status'] === 'Abnormal') {
                $risk_score += 15;
                $risk_factors[] = "Abnormal glucose levels";
            }
            if ($lab['test_name'] === 'HIV Test' && $lab['result_value'] === 'Reactive') {
                $risk_score += 20;
                $risk_factors[] = "HIV positive status";
            }
        }
    }
    
    // Cap the risk score at 100
    $risk_score = min(100, $risk_score);
    
    // Determine risk level
    $risk_level = 'Low';
    if ($risk_score >= 90) {
        $risk_level = 'Critical';
    } elseif ($risk_score >= 70) {
        $risk_level = 'High';
    } elseif ($risk_score >= 30) {
        $risk_level = 'Medium';
    }
    
    return [
        'risk_score' => round($risk_score, 1),
        'risk_level' => $risk_level,
        'risk_factors' => $risk_factors,
        'confidence_level' => 0.85, // Simulated confidence level
        'recommendations' => generateRecommendations($risk_level, $risk_factors)
    ];
}

function generateRecommendations($risk_level, $risk_factors) {
    $recommendations = [];
    
    switch ($risk_level) {
        case 'Critical':
            $recommendations[] = "Immediate specialist consultation required";
            $recommendations[] = "Consider hospitalization for monitoring";
            $recommendations[] = "Daily fetal monitoring";
            break;
        case 'High':
            $recommendations[] = "Increased frequency of ANC visits (weekly)";
            $recommendations[] = "Specialist consultation recommended";
            $recommendations[] = "Additional monitoring and tests";
            break;
        case 'Medium':
            $recommendations[] = "Regular ANC visits as scheduled";
            $recommendations[] = "Monitor specific risk factors closely";
            $recommendations[] = "Patient education on warning signs";
            break;
        default:
            $recommendations[] = "Continue routine ANC care";
            $recommendations[] = "Maintain healthy lifestyle";
            $recommendations[] = "Regular follow-up visits";
    }
    
    // Add specific recommendations based on risk factors
    foreach ($risk_factors as $factor) {
        if (strpos($factor, 'blood pressure') !== false) {
            $recommendations[] = "Blood pressure monitoring and management";
        }
        if (strpos($factor, 'diabetes') !== false) {
            $recommendations[] = "Glucose monitoring and dietary counseling";
        }
        if (strpos($factor, 'HIV') !== false) {
            $recommendations[] = "PMTCT protocol implementation";
        }
    }
    
    return array_unique($recommendations);
}

function updatePregnancyRiskScore($pregnancy_id, $mysqli) {
    // Get patient and pregnancy data
    $patient_query = "
        SELECT p.*, pr.*, TIMESTAMPDIFF(YEAR, p.date_of_birth, CURDATE()) as age
        FROM pregnancies pr
        JOIN patients p ON pr.patient_id = p.patient_id
        WHERE pr.pregnancy_id = ?
    ";
    $stmt = $mysqli->prepare($patient_query);
    $stmt->bind_param("i", $pregnancy_id);
    $stmt->execute();
    $patient_data = $stmt->get_result()->fetch_assoc();
    
    if (!$patient_data) return false;
    
    // Get latest visit data
    $visit_query = "
        SELECT * FROM anc_visits 
        WHERE pregnancy_id = ? 
        ORDER BY visit_date DESC 
        LIMIT 1
    ";
    $stmt = $mysqli->prepare($visit_query);
    $stmt->bind_param("i", $pregnancy_id);
    $stmt->execute();
    $visit_data = $stmt->get_result()->fetch_assoc();
    
    // Get laboratory data
    $lab_query = "
        SELECT lt.* FROM laboratory_tests lt
        JOIN anc_visits av ON lt.visit_id = av.visit_id
        WHERE av.pregnancy_id = ?
        ORDER BY lt.test_date DESC
    ";
    $stmt = $mysqli->prepare($lab_query);
    $stmt->bind_param("i", $pregnancy_id);
    $stmt->execute();
    $lab_data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Calculate risk score
    $risk_assessment = calculateRiskScore($patient_data, $visit_data, $lab_data);
    
    // Update pregnancy record
    $update_query = "
        UPDATE pregnancies 
        SET ai_risk_score = ?, risk_level = ? 
        WHERE pregnancy_id = ?
    ";
    $stmt = $mysqli->prepare($update_query);
    $stmt->bind_param("dsi", $risk_assessment['risk_score'], $risk_assessment['risk_level'], $pregnancy_id);
    $stmt->execute();
    
    // Store AI prediction record
    $prediction_query = "
        INSERT INTO ai_risk_predictions 
        (pregnancy_id, risk_score, risk_level, risk_factors, recommendations, confidence_level) 
        VALUES (?, ?, ?, ?, ?, ?)
    ";
    $risk_factors_json = json_encode($risk_assessment['risk_factors']);
    $recommendations_json = json_encode($risk_assessment['recommendations']);
    
    $stmt = $mysqli->prepare($prediction_query);
    $stmt->bind_param("idssssd", 
        $pregnancy_id, 
        $risk_assessment['risk_score'], 
        $risk_assessment['risk_level'], 
        $risk_factors_json, 
        $recommendations_json, 
        $risk_assessment['confidence_level']
    );
    $stmt->execute();
    
    $prediction_id = $mysqli->insert_id;
    
    // Generate alert if high risk
    if ($risk_assessment['risk_level'] === 'High' || $risk_assessment['risk_level'] === 'Critical') {
        $alert_message = "High risk pregnancy detected. Risk score: {$risk_assessment['risk_score']}. Factors: " . implode(', ', array_slice($risk_assessment['risk_factors'], 0, 3));
        
        $alert_query = "
            INSERT INTO risk_alerts 
            (prediction_id, patient_id, alert_type, alert_level, alert_message) 
            VALUES (?, ?, 'High Risk', ?, ?)
        ";
        $stmt = $mysqli->prepare($alert_query);
        $stmt->bind_param("iiss", $prediction_id, $patient_data['patient_id'], $risk_assessment['risk_level'], $alert_message);
        $stmt->execute();
    }
    
    return $risk_assessment;
}
?>