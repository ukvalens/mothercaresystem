    <div class="footer">
        <div class="footer-content">
            <div class="footer-section">
                <h4>🏥 Maternal Care System</h4>
                <p>Comprehensive healthcare management for maternal and child health services.</p>
                <p>Empowering healthcare providers with AI-driven insights and streamlined workflows.</p>
            </div>
            <div class="footer-section">
                <h4>Quick Links</h4>
                <p><a href="../dashboard/index.php">Dashboard</a></p>
                <p><a href="../patients/list.php">Patient Management</a></p>
                <p><a href="../appointments/schedule.php">Appointments</a></p>
                <p><a href="../reports/daily.php">Reports</a></p>
            </div>
            <div class="footer-section">
                <h4>Support</h4>
                <p><a href="../messages/index.php">Messages</a></p>
                <p><a href="../notifications/index.php">Notifications</a></p>
                <p>Email: support@maternalcare.rw</p>
                <p>Phone: +250 788 123 456</p>
            </div>
            <div class="footer-section">
                <h4>System Info</h4>
                <p>Version: 1.0.0</p>
                <p>Last Updated: <?= date('M Y') ?></p>
                <p>User: <?= $_SESSION['full_name'] ?? $_SESSION['username'] ?></p>
                <p>Role: <?= $_SESSION['role'] ?></p>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?= date('Y') ?> Maternal Care System. All rights reserved. | Designed for healthcare excellence.</p>
        </div>
    </div>
</body>
</html>