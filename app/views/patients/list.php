<?php
session_start();
require_once '../../config/database.php';

// Handle delete action
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    if (in_array($_SESSION['role'], ['Admin', 'Receptionist'])) {
        $patient_id = $_GET['id'];
        $stmt = $mysqli->prepare("UPDATE patients SET is_active = 0 WHERE patient_id = ?");
        $stmt->bind_param("i", $patient_id);
        if ($stmt->execute()) {
            $success = "Patient deactivated successfully";
        } else {
            $error = "Error deactivating patient";
        }
    }
}

$page_title = 'Patient List';
$page_header = '👥 Patient List';
$show_nav = true;
$breadcrumb = [
    ['title' => 'Patients']
];

include '../layouts/header.php';
?>


    <div class="container">
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div style="margin-bottom: 1rem;">
            <a href="../dashboard/index.php" class="btn btn-secondary">← Back to Dashboard</a>
            <?php if (in_array($_SESSION['role'], ['Receptionist', 'Admin'])): ?>
                <a href="register.php" class="btn">Register New Patient</a>
            <?php endif; ?>
        </div>
        <div class="section">
            <div style="margin-bottom: 1rem;">
                <input type="text" placeholder="Search patients..." id="searchInput" onkeyup="searchPatients()" style="padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px; width: 300px;">
            </div>
            
            <table id="patientTable">
                <thead>
                    <tr>
                        <th>Patient ID</th>
                        <th>Name</th>
                        <th>Age</th>
                        <th>Contact</th>
                        <th>Last Visit</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $patients = $mysqli->query("
                        SELECT p.*, 
                               YEAR(CURDATE()) - YEAR(p.date_of_birth) AS age,
                               MAX(av.visit_date) as last_visit,
                               COUNT(pr.pregnancy_id) as pregnancy_count
                        FROM patients p
                        LEFT JOIN pregnancies pr ON p.patient_id = pr.patient_id
                        LEFT JOIN anc_visits av ON pr.pregnancy_id = av.pregnancy_id
                        WHERE p.is_active = 1
                        GROUP BY p.patient_id
                        ORDER BY p.created_at DESC
                        LIMIT 100
                    ");
                    
                    if ($patients && $patients->num_rows > 0) {
                        while ($patient = $patients->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>P{$patient['patient_id']}</td>";
                            echo "<td>{$patient['first_name']} {$patient['last_name']}</td>";
                            echo "<td>{$patient['age']} years</td>";
                            echo "<td>{$patient['contact_number']}</td>";
                            echo "<td>" . ($patient['last_visit'] ? date('M d, Y', strtotime($patient['last_visit'])) : 'No visits') . "</td>";
                            echo "<td>Active</td>";
                            echo "<td>";
                            echo "<a href='view.php?id={$patient['patient_id']}' class='btn' style='font-size: 12px; padding: 0.3rem 0.6rem;'>View</a> ";
                            if (in_array($_SESSION['role'], ['Admin', 'Receptionist'])) {
                                echo "<a href='edit.php?id={$patient['patient_id']}' class='btn btn-secondary' style='font-size: 12px; padding: 0.3rem 0.6rem;'>Edit</a> ";
                                echo "<a href='?action=delete&id={$patient['patient_id']}' class='btn' style='background: #dc3545; font-size: 12px; padding: 0.3rem 0.6rem;' onclick='return confirm(\"Are you sure you want to deactivate this patient?\")'>Delete</a> ";
                            }
                            if (in_array($_SESSION['role'], ['Doctor', 'Nurse'])) {
                                echo "<a href='../appointments/schedule.php?patient_id={$patient['patient_id']}' class='btn' style='background: #28a745; font-size: 12px; padding: 0.3rem 0.6rem;'>Book</a>";
                            }
                            echo "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='7'>No patients found</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
    function searchPatients() {
        var input = document.getElementById("searchInput");
        var filter = input.value.toUpperCase();
        var table = document.getElementById("patientTable");
        var tr = table.getElementsByTagName("tr");

        for (var i = 1; i < tr.length; i++) {
            var td = tr[i].getElementsByTagName("td");
            var found = false;
            for (var j = 0; j < td.length; j++) {
                if (td[j] && td[j].innerHTML.toUpperCase().indexOf(filter) > -1) {
                    found = true;
                    break;
                }
            }
            tr[i].style.display = found ? "" : "none";
        }
    }
    </script>

<?php include '../layouts/footer.php'; ?>