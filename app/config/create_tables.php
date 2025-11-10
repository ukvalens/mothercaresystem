<?php
require_once 'database.php';

// Function to execute SQL and handle errors
function executeSQL($mysqli, $sql, $description) {
    if ($mysqli->query($sql) === TRUE) {
        echo "✓ $description created successfully<br>";
    } else {
        echo "✗ Error creating $description: " . $mysqli->error . "<br>";
    }
}

echo "<h2>Creating Maternal Care System Database Tables</h2>";

// Use existing database - no need to create new one
// Database 'mothercaresystem' should already exist

// 1. CORE USER & SECURITY TABLES
echo "<h3>1. Creating Core User & Security Tables</h3>";

$users_table = "
CREATE TABLE IF NOT EXISTS users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    nid VARCHAR(20) UNIQUE NOT NULL COMMENT 'National Identification Number',
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL COMMENT 'BCrypt hashed password',
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    role ENUM('Admin','Doctor','Nurse','Receptionist','Patient') NOT NULL,
    first_name VARCHAR(50),
    last_name VARCHAR(50),
    date_of_birth DATE,
    gender ENUM('Male','Female','Other'),
    address TEXT,
    specialization VARCHAR(100) COMMENT 'For doctors only',
    license_number VARCHAR(50) COMMENT 'For medical staff',
    is_active BOOLEAN DEFAULT TRUE,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_nid (nid),
    INDEX idx_role (role),
    INDEX idx_email (email),
    INDEX idx_username (username),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

executeSQL($mysqli, $users_table, "Users table");

$audit_logs_table = "
CREATE TABLE IF NOT EXISTS audit_logs (
    log_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    action_type ENUM('CREATE','UPDATE','DELETE','LOGIN','LOGOUT','VIEW','EXPORT') NOT NULL,
    entity VARCHAR(50) NOT NULL COMMENT 'Table or entity name',
    entity_id INT COMMENT 'ID of the affected record',
    old_values JSON COMMENT 'Previous values before change',
    new_values JSON COMMENT 'New values after change',
    ip_address VARCHAR(45),
    user_agent TEXT,
    description TEXT,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_timestamp (timestamp),
    INDEX idx_entity (entity, entity_id),
    INDEX idx_action_type (action_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

executeSQL($mysqli, $audit_logs_table, "Audit logs table");

// 2. PATIENT MANAGEMENT TABLES
echo "<h3>2. Creating Patient Management Tables</h3>";

$patients_table = "
CREATE TABLE IF NOT EXISTS patients (
    patient_id INT PRIMARY KEY AUTO_INCREMENT,
    nid VARCHAR(20) UNIQUE NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    date_of_birth DATE NOT NULL,
    gender ENUM('Male','Female','Other') NOT NULL,
    contact_number VARCHAR(20) NOT NULL,
    alternate_contact VARCHAR(20),
    email VARCHAR(100),
    address TEXT,
    occupation VARCHAR(100),
    education_level ENUM('None','Primary','Secondary','University','Other'),
    marital_status ENUM('Single','Married','Divorced','Widowed'),
    emergency_contact_name VARCHAR(100),
    emergency_contact_phone VARCHAR(20),
    emergency_contact_relationship VARCHAR(50),
    insurance_provider VARCHAR(100),
    insurance_number VARCHAR(50),
    insurance_expiry DATE,
    medical_history TEXT COMMENT 'Chronic conditions, past illnesses',
    surgical_history TEXT COMMENT 'Previous surgeries',
    known_allergies TEXT,
    current_medications TEXT,
    family_medical_history TEXT,
    blood_type ENUM('A+','A-','B+','B-','AB+','AB-','O+','O-','Unknown'),
    rh_factor ENUM('Positive','Negative','Unknown'),
    is_active BOOLEAN DEFAULT TRUE,
    registered_by INT NOT NULL COMMENT 'User who registered the patient',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (registered_by) REFERENCES users(user_id),
    INDEX idx_full_name (first_name, last_name),
    INDEX idx_contact (contact_number),
    INDEX idx_dob (date_of_birth),
    INDEX idx_insurance (insurance_provider),
    INDEX idx_registered_by (registered_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

executeSQL($mysqli, $patients_table, "Patients table");

$obstetric_history_table = "
CREATE TABLE IF NOT EXISTS obstetric_history (
    history_id INT PRIMARY KEY AUTO_INCREMENT,
    patient_id INT NOT NULL,
    gravida INT NOT NULL DEFAULT 1 COMMENT 'Total number of pregnancies',
    parity INT NOT NULL DEFAULT 0 COMMENT 'Number of viable births',
    living_children INT DEFAULT 0,
    abortions INT DEFAULT 0 COMMENT 'Spontaneous or induced',
    stillbirths INT DEFAULT 0,
    previous_csections INT DEFAULT 0 COMMENT 'Previous Cesarean sections',
    last_delivery_date DATE,
    previous_complications TEXT COMMENT 'Complications in previous pregnancies',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (patient_id) REFERENCES patients(patient_id) ON DELETE CASCADE,
    INDEX idx_patient_id (patient_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

executeSQL($mysqli, $obstetric_history_table, "Obstetric history table");

// 3. PREGNANCY MANAGEMENT TABLES
echo "<h3>3. Creating Pregnancy Management Tables</h3>";

$pregnancies_table = "
CREATE TABLE IF NOT EXISTS pregnancies (
    pregnancy_id INT PRIMARY KEY AUTO_INCREMENT,
    patient_id INT NOT NULL,
    lmp_date DATE NOT NULL COMMENT 'Last Menstrual Period date',
    estimated_conception_date DATE,
    expected_delivery_date DATE NOT NULL,
    gestational_age_weeks INT DEFAULT 0,
    gestational_age_days INT DEFAULT 0,
    parity INT DEFAULT 0,
    gravida INT DEFAULT 1,
    blood_type ENUM('A+','A-','B+','B-','AB+','AB-','O+','O-','Unknown'),
    rh_factor ENUM('Positive','Negative','Unknown'),
    current_status ENUM('Active','Completed','Transferred','Lost to Follow-up','Terminated') DEFAULT 'Active',
    ai_risk_score DECIMAL(5,2) DEFAULT 0 COMMENT '0-100 risk score',
    risk_level ENUM('Low','Medium','High','Critical') DEFAULT 'Low',
    high_risk_conditions TEXT COMMENT 'Pre-existing conditions that make pregnancy high risk',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (patient_id) REFERENCES patients(patient_id) ON DELETE CASCADE,
    INDEX idx_patient_id (patient_id),
    INDEX idx_status (current_status),
    INDEX idx_edd (expected_delivery_date),
    INDEX idx_risk_level (risk_level)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

executeSQL($mysqli, $pregnancies_table, "Pregnancies table");

// 4. CLINICAL MANAGEMENT TABLES
echo "<h3>4. Creating Clinical Management Tables</h3>";

$anc_visits_table = "
CREATE TABLE IF NOT EXISTS anc_visits (
    visit_id INT PRIMARY KEY AUTO_INCREMENT,
    pregnancy_id INT NOT NULL,
    visit_date DATE NOT NULL,
    visit_number INT NOT NULL COMMENT '1st, 2nd, 3rd visit etc.',
    gestational_age_weeks INT,
    gestational_age_days INT,
    
    -- Vital Signs
    blood_pressure_systolic INT,
    blood_pressure_diastolic INT,
    weight_kg DECIMAL(5,2),
    height_cm DECIMAL(5,2),
    bmi DECIMAL(4,1),
    temperature DECIMAL(4,1),
    pulse_rate INT,
    respiratory_rate INT,
    
    -- Fetal Assessment
    fetal_heart_rate INT,
    fundal_height_cm INT,
    presentation ENUM('Cephalic','Breech','Transverse','Unknown'),
    fetal_movement ENUM('Active','Normal','Reduced','Absent'),
    
    -- Physical Examination
    edema ENUM('None','1+','2+','3+','4+'),
    proteinuria ENUM('Negative','Trace','1+','2+','3+'),
    glucose_urine ENUM('Negative','Trace','1+','2+','3+'),
    anemia_signs BOOLEAN DEFAULT FALSE,
    
    -- Symptoms and Complaints
    symptoms_complaints TEXT,
    physical_examination TEXT,
    
    -- Clinical Assessment
    assessment_notes TEXT,
    diagnosis TEXT,
    plan_notes TEXT,
    
    -- Follow-up
    next_visit_date DATE,
    next_visit_weeks INT,
    
    recorded_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (pregnancy_id) REFERENCES pregnancies(pregnancy_id) ON DELETE CASCADE,
    FOREIGN KEY (recorded_by) REFERENCES users(user_id),
    INDEX idx_pregnancy_id (pregnancy_id),
    INDEX idx_visit_date (visit_date),
    INDEX idx_recorded_by (recorded_by),
    UNIQUE KEY unique_pregnancy_visit (pregnancy_id, visit_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

executeSQL($mysqli, $anc_visits_table, "ANC visits table");

$laboratory_tests_table = "
CREATE TABLE IF NOT EXISTS laboratory_tests (
    test_id INT PRIMARY KEY AUTO_INCREMENT,
    visit_id INT NOT NULL,
    test_type ENUM('Blood','Urine','Ultrasound','Other') NOT NULL,
    test_name VARCHAR(100) NOT NULL,
    test_code VARCHAR(20),
    normal_range TEXT,
    result_value VARCHAR(100),
    result_unit VARCHAR(20),
    result_status ENUM('Normal','Abnormal','Critical'),
    result_notes TEXT,
    test_date DATE,
    result_date DATE,
    conducted_by INT,
    verified_by INT,
    payment_status ENUM('Pending','Paid') DEFAULT 'Pending',
    cost DECIMAL(10,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (visit_id) REFERENCES anc_visits(visit_id) ON DELETE CASCADE,
    FOREIGN KEY (conducted_by) REFERENCES users(user_id),
    FOREIGN KEY (verified_by) REFERENCES users(user_id),
    INDEX idx_visit_id (visit_id),
    INDEX idx_test_type (test_type),
    INDEX idx_test_date (test_date),
    INDEX idx_result_status (result_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

executeSQL($mysqli, $laboratory_tests_table, "Laboratory tests table");

$ultrasound_scans_table = "
CREATE TABLE IF NOT EXISTS ultrasound_scans (
    scan_id INT PRIMARY KEY AUTO_INCREMENT,
    visit_id INT NOT NULL,
    scan_type ENUM('Dating','Anomaly','Growth','Doppler','Other') NOT NULL,
    gestational_age_weeks INT,
    gestational_age_days INT,
    bpd_mm DECIMAL(6,2) COMMENT 'Biparietal Diameter',
    hc_mm DECIMAL(6,2) COMMENT 'Head Circumference',
    ac_mm DECIMAL(6,2) COMMENT 'Abdominal Circumference',
    fl_mm DECIMAL(6,2) COMMENT 'Femur Length',
    estimated_fetal_weight_grams INT,
    amniotic_fluid_index DECIMAL(5,2),
    placenta_location VARCHAR(100),
    placenta_grade ENUM('0','1','2','3'),
    presentation ENUM('Cephalic','Breech','Transverse','Unknown'),
    biophysical_profile INT COMMENT '0-10 score',
    doppler_studies TEXT,
    findings TEXT,
    impressions TEXT,
    recommendations TEXT,
    performed_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (visit_id) REFERENCES anc_visits(visit_id) ON DELETE CASCADE,
    FOREIGN KEY (performed_by) REFERENCES users(user_id),
    INDEX idx_visit_id (visit_id),
    INDEX idx_scan_type (scan_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

executeSQL($mysqli, $ultrasound_scans_table, "Ultrasound scans table");

$prescriptions_table = "
CREATE TABLE IF NOT EXISTS prescriptions (
    prescription_id INT PRIMARY KEY AUTO_INCREMENT,
    visit_id INT NOT NULL,
    medication_name VARCHAR(100) NOT NULL,
    dosage VARCHAR(50) NOT NULL,
    frequency VARCHAR(50) NOT NULL,
    duration VARCHAR(50) NOT NULL,
    quantity INT,
    instructions TEXT,
    prescribed_by INT NOT NULL,
    cost DECIMAL(10,2) DEFAULT 0,
    payment_status ENUM('Pending','Paid') DEFAULT 'Pending',
    is_dispensed BOOLEAN DEFAULT FALSE,
    dispensed_by INT,
    dispensed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (visit_id) REFERENCES anc_visits(visit_id) ON DELETE CASCADE,
    FOREIGN KEY (prescribed_by) REFERENCES users(user_id),
    FOREIGN KEY (dispensed_by) REFERENCES users(user_id),
    INDEX idx_visit_id (visit_id),
    INDEX idx_prescribed_by (prescribed_by),
    INDEX idx_is_dispensed (is_dispensed)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

executeSQL($mysqli, $prescriptions_table, "Prescriptions table");

// 5. DELIVERY & POSTNATAL TABLES
echo "<h3>5. Creating Delivery & Postnatal Tables</h3>";

$deliveries_table = "
CREATE TABLE IF NOT EXISTS deliveries (
    delivery_id INT PRIMARY KEY AUTO_INCREMENT,
    pregnancy_id INT NOT NULL,
    delivery_date DATE NOT NULL,
    delivery_time TIME,
    gestational_age_weeks INT,
    gestational_age_days INT,
    
    -- Delivery Details
    mode_delivery ENUM('Normal','Cesarean','Assisted Vacuum','Assisted Forceps','Breech') NOT NULL,
    indication TEXT COMMENT 'Reason for C-section or assisted delivery',
    delivery_outcome ENUM('Live Birth','Stillbirth','Neonatal Death'),
    birth_weight_kg DECIMAL(4,2),
    birth_length_cm DECIMAL(4,1),
    head_circumference_cm DECIMAL(4,1),
    apgar_1min INT COMMENT '0-10 score',
    apgar_5min INT COMMENT '0-10 score',
    gender ENUM('Male','Female','Undetermined'),
    
    -- Maternal Outcomes
    maternal_complications TEXT,
    blood_loss_ml INT,
    placenta_complete BOOLEAN,
    episiotomy BOOLEAN,
    tears ENUM('None','1st Degree','2nd Degree','3rd Degree','4th Degree'),
    anesthesia_type VARCHAR(100),
    
    -- Neonatal Outcomes
    neonatal_complications TEXT,
    resuscitation_required BOOLEAN DEFAULT FALSE,
    nicu_admission BOOLEAN DEFAULT FALSE,
    breastfeeding_initiated BOOLEAN DEFAULT FALSE,
    
    recorded_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (pregnancy_id) REFERENCES pregnancies(pregnancy_id) ON DELETE CASCADE,
    FOREIGN KEY (recorded_by) REFERENCES users(user_id),
    INDEX idx_pregnancy_id (pregnancy_id),
    INDEX idx_delivery_date (delivery_date),
    INDEX idx_mode_delivery (mode_delivery)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

executeSQL($mysqli, $deliveries_table, "Deliveries table");

$postnatal_visits_table = "
CREATE TABLE IF NOT EXISTS postnatal_visits (
    postnatal_id INT PRIMARY KEY AUTO_INCREMENT,
    delivery_id INT NOT NULL,
    visit_date DATE NOT NULL,
    visit_type ENUM('Maternal','Neonatal','Both') NOT NULL,
    days_postpartum INT,
    
    -- Maternal Examination
    maternal_bp_systolic INT,
    maternal_bp_diastolic INT,
    maternal_temperature DECIMAL(4,1),
    maternal_weight_kg DECIMAL(5,2),
    uterine_involution VARCHAR(50),
    lochia VARCHAR(50),
    perineum_healing VARCHAR(50),
    breast_examination VARCHAR(100),
    contraception_provided VARCHAR(100),
    
    -- Neonatal Examination
    neonatal_weight_kg DECIMAL(4,2),
    neonatal_temperature DECIMAL(4,1),
    feeding_method ENUM('Exclusive Breastfeeding','Formula','Mixed','Other'),
    umbilical_cord VARCHAR(50),
    jaundice_present BOOLEAN DEFAULT FALSE,
    neonatal_issues TEXT,
    
    -- General
    assessment_notes TEXT,
    plan_notes TEXT,
    next_visit_date DATE,
    recorded_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (delivery_id) REFERENCES deliveries(delivery_id) ON DELETE CASCADE,
    FOREIGN KEY (recorded_by) REFERENCES users(user_id),
    INDEX idx_delivery_id (delivery_id),
    INDEX idx_visit_date (visit_date),
    INDEX idx_visit_type (visit_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

executeSQL($mysqli, $postnatal_visits_table, "Postnatal visits table");

// 6. APPOINTMENT & COMMUNICATION TABLES
echo "<h3>6. Creating Appointment & Communication Tables</h3>";

$appointments_table = "
CREATE TABLE IF NOT EXISTS appointments (
    appointment_id INT PRIMARY KEY AUTO_INCREMENT,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    appointment_type ENUM('ANC Visit','Delivery Planning','Postnatal Check','Review','Ultrasound','Laboratory','Other') NOT NULL,
    status ENUM('Scheduled','Confirmed','Completed','Cancelled','No Show') DEFAULT 'Scheduled',
    reason TEXT,
    notes TEXT,
    reminder_sent BOOLEAN DEFAULT FALSE,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (patient_id) REFERENCES patients(patient_id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES users(user_id),
    FOREIGN KEY (created_by) REFERENCES users(user_id),
    INDEX idx_patient_id (patient_id),
    INDEX idx_doctor_id (doctor_id),
    INDEX idx_appointment_date (appointment_date, appointment_time),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

executeSQL($mysqli, $appointments_table, "Appointments table");

$notifications_table = "
CREATE TABLE IF NOT EXISTS notifications (
    notification_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    patient_id INT,
    to_email VARCHAR(100),
    to_phone VARCHAR(20),
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    message_type ENUM('HTML','Plaintext') DEFAULT 'HTML',
    notification_type ENUM('Appointment Reminder','Payment Due','Clinical Alert','System','Test Results','Other') NOT NULL,
    channel ENUM('Email','SMS','System') NOT NULL,
    status ENUM('Pending','Sent','Failed','Delivered') DEFAULT 'Pending',
    sent_at TIMESTAMP NULL,
    delivery_confirmation TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (patient_id) REFERENCES patients(patient_id),
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at),
    INDEX idx_notification_type (notification_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

executeSQL($mysqli, $notifications_table, "Notifications table");

// 7. FINANCIAL MANAGEMENT TABLES
echo "<h3>7. Creating Financial Management Tables</h3>";

$service_catalog_table = "
CREATE TABLE IF NOT EXISTS service_catalog (
    service_id INT PRIMARY KEY AUTO_INCREMENT,
    service_code VARCHAR(20) UNIQUE NOT NULL,
    service_name VARCHAR(100) NOT NULL,
    service_type ENUM('Consultation','Laboratory','Medication','Procedure','Delivery','Ultrasound','Other') NOT NULL,
    department VARCHAR(50),
    cost DECIMAL(10,2) NOT NULL,
    duration_minutes INT,
    is_active BOOLEAN DEFAULT TRUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_service_type (service_type),
    INDEX idx_department (department),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

executeSQL($mysqli, $service_catalog_table, "Service catalog table");

$payment_transactions_table = "
CREATE TABLE IF NOT EXISTS payment_transactions (
    transaction_id INT PRIMARY KEY AUTO_INCREMENT,
    patient_id INT NOT NULL,
    invoice_id INT,
    service_type ENUM('Registration','Consultation','Laboratory','Medication','Procedure','Delivery','Package','Other') NOT NULL,
    service_id INT COMMENT 'Reference to specific service record',
    description VARCHAR(255) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('Cash','Card','Mobile Money','Insurance','Bank Transfer') NOT NULL,
    mobile_money_number VARCHAR(20),
    transaction_reference VARCHAR(100),
    card_last_four VARCHAR(4),
    insurance_claim_number VARCHAR(50),
    status ENUM('Pending','Completed','Failed','Refunded') DEFAULT 'Pending',
    processed_by INT NOT NULL,
    processed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (patient_id) REFERENCES patients(patient_id) ON DELETE CASCADE,
    FOREIGN KEY (processed_by) REFERENCES users(user_id),
    INDEX idx_patient_id (patient_id),
    INDEX idx_status (status),
    INDEX idx_processed_at (processed_at),
    INDEX idx_payment_method (payment_method)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

executeSQL($mysqli, $payment_transactions_table, "Payment transactions table");

$billing_invoices_table = "
CREATE TABLE IF NOT EXISTS billing_invoices (
    invoice_id INT PRIMARY KEY AUTO_INCREMENT,
    patient_id INT NOT NULL,
    visit_id INT,
    invoice_number VARCHAR(50) UNIQUE NOT NULL,
    invoice_date DATE NOT NULL,
    due_date DATE NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    amount_paid DECIMAL(10,2) DEFAULT 0,
    balance DECIMAL(10,2) DEFAULT 0,
    invoice_status ENUM('Draft','Pending','Partial','Paid','Overdue','Cancelled') DEFAULT 'Pending',
    services_json JSON COMMENT 'Details of services included',
    notes TEXT,
    generated_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (patient_id) REFERENCES patients(patient_id) ON DELETE CASCADE,
    FOREIGN KEY (visit_id) REFERENCES anc_visits(visit_id),
    FOREIGN KEY (generated_by) REFERENCES users(user_id),
    INDEX idx_patient_id (patient_id),
    INDEX idx_invoice_status (invoice_status),
    INDEX idx_due_date (due_date),
    INDEX idx_invoice_number (invoice_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

executeSQL($mysqli, $billing_invoices_table, "Billing invoices table");

// 8. AI & RISK MANAGEMENT TABLES
echo "<h3>8. Creating AI & Risk Management Tables</h3>";

$ai_risk_predictions_table = "
CREATE TABLE IF NOT EXISTS ai_risk_predictions (
    prediction_id INT PRIMARY KEY AUTO_INCREMENT,
    pregnancy_id INT NOT NULL,
    visit_id INT,
    risk_score DECIMAL(5,2) NOT NULL COMMENT '0-100 scale',
    risk_level ENUM('Low','Medium','High','Critical') NOT NULL,
    risk_factors JSON COMMENT 'Specific factors contributing to risk',
    predicted_conditions JSON COMMENT 'Specific conditions predicted',
    recommendations TEXT,
    confidence_level DECIMAL(4,3) COMMENT '0-1 scale',
    model_version VARCHAR(50),
    reviewed_by INT,
    reviewed_at TIMESTAMP NULL,
    review_notes TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (pregnancy_id) REFERENCES pregnancies(pregnancy_id) ON DELETE CASCADE,
    FOREIGN KEY (visit_id) REFERENCES anc_visits(visit_id),
    FOREIGN KEY (reviewed_by) REFERENCES users(user_id),
    INDEX idx_pregnancy_id (pregnancy_id),
    INDEX idx_risk_level (risk_level),
    INDEX idx_generated_at (generated_at),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

executeSQL($mysqli, $ai_risk_predictions_table, "AI risk predictions table");

$risk_alerts_table = "
CREATE TABLE IF NOT EXISTS risk_alerts (
    alert_id INT PRIMARY KEY AUTO_INCREMENT,
    prediction_id INT NOT NULL,
    patient_id INT NOT NULL,
    alert_type ENUM('High Risk','Critical Value','Missed Appointment','Payment Due','System','Test Results') NOT NULL,
    alert_level ENUM('Low','Medium','High','Critical') NOT NULL,
    alert_message TEXT NOT NULL,
    assigned_to INT COMMENT 'Staff member assigned to handle',
    status ENUM('New','Acknowledged','In Progress','Resolved','Escalated') DEFAULT 'New',
    acknowledged_by INT,
    acknowledged_at TIMESTAMP NULL,
    resolved_by INT,
    resolved_at TIMESTAMP NULL,
    resolution_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (prediction_id) REFERENCES ai_risk_predictions(prediction_id) ON DELETE CASCADE,
    FOREIGN KEY (patient_id) REFERENCES patients(patient_id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_to) REFERENCES users(user_id),
    FOREIGN KEY (acknowledged_by) REFERENCES users(user_id),
    FOREIGN KEY (resolved_by) REFERENCES users(user_id),
    INDEX idx_patient_id (patient_id),
    INDEX idx_status (status),
    INDEX idx_alert_level (alert_level),
    INDEX idx_alert_type (alert_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

executeSQL($mysqli, $risk_alerts_table, "Risk alerts table");

// 9. SYSTEM CONFIGURATION TABLES
echo "<h3>9. Creating System Configuration Tables</h3>";

$system_settings_table = "
CREATE TABLE IF NOT EXISTS system_settings (
    setting_id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_type ENUM('String','Integer','Boolean','JSON','Array') DEFAULT 'String',
    description TEXT,
    category VARCHAR(50),
    is_public BOOLEAN DEFAULT FALSE,
    updated_by INT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (updated_by) REFERENCES users(user_id),
    INDEX idx_setting_key (setting_key),
    INDEX idx_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

executeSQL($mysqli, $system_settings_table, "System settings table");

echo "<h3>10. Inserting Initial Data</h3>";

// Insert default system settings
$default_settings = "
INSERT IGNORE INTO system_settings (setting_key, setting_value, setting_type, description, category, is_public) VALUES
('hospital_name', 'Maternal Care Hospital', 'String', 'Name of the healthcare facility', 'General', TRUE),
('hospital_address', 'Kigali, Rwanda', 'String', 'Physical address of the hospital', 'General', TRUE),
('hospital_phone', '+250788123456', 'String', 'Main contact number', 'General', TRUE),
('hospital_email', 'info@maternalcare.rw', 'String', 'Main email address', 'General', TRUE),
('system_currency', 'RWF', 'String', 'Default currency for financial transactions', 'Financial', TRUE),
('appointment_reminder_hours', '24', 'Integer', 'Hours before appointment to send reminder', 'Notifications', FALSE),
('high_risk_threshold', '70', 'Integer', 'Risk score threshold for high risk classification', 'Clinical', FALSE),
('critical_risk_threshold', '90', 'Integer', 'Risk score threshold for critical risk classification', 'Clinical', FALSE)";

executeSQL($mysqli, $default_settings, "Default system settings");

// Insert default services
$default_services = "
INSERT IGNORE INTO service_catalog (service_code, service_name, service_type, department, cost, duration_minutes, description) VALUES
('REG001', 'Patient Registration', 'Other', 'Reception', 5000, 15, 'Initial patient registration fee'),
('CON001', 'Doctor Consultation', 'Consultation', 'Clinical', 10000, 30, 'General physician consultation'),
('CON002', 'Specialist Consultation', 'Consultation', 'Clinical', 15000, 45, 'Specialist doctor consultation'),
('LAB001', 'Blood Test - Basic', 'Laboratory', 'Laboratory', 5000, 5, 'Basic blood work including CBC'),
('LAB002', 'Blood Test - Comprehensive', 'Laboratory', 'Laboratory', 15000, 10, 'Comprehensive blood panel'),
('LAB003', 'Urine Analysis', 'Laboratory', 'Laboratory', 3000, 5, 'Complete urine analysis'),
('US001', 'Dating Ultrasound', 'Ultrasound', 'Radiology', 20000, 30, 'Early pregnancy dating scan'),
('US002', 'Anomaly Scan', 'Ultrasound', 'Radiology', 25000, 45, 'Detailed fetal anomaly scan'),
('MED001', 'Prenatal Vitamins', 'Medication', 'Pharmacy', 8000, 0, 'One month supply of prenatal vitamins'),
('DEL001', 'Normal Delivery', 'Delivery', 'Maternity', 50000, 0, 'Normal vaginal delivery package'),
('DEL002', 'Cesarean Section', 'Delivery', 'Maternity', 150000, 0, 'Cesarean section delivery package')";

executeSQL($mysqli, $default_services, "Default service catalog");

// Insert default admin user
$default_admin = "
INSERT IGNORE INTO users (nid, username, password, email, role, first_name, last_name, is_active) VALUES
('admin001', 'admin', '$2y$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@maternalcare.rw', 'Admin', 'System', 'Administrator', TRUE)";

executeSQL($mysqli, $default_admin, "Default admin user");

echo "<h2>✅ Database setup completed successfully!</h2>";
echo "<p><strong>Default Admin Login:</strong><br>";
echo "Username: admin<br>";
echo "Password: password<br>";
echo "Email: admin@maternalcare.rw</p>";

$mysqli->close();
?>