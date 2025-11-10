<?php
session_start();
require_once '../../config/database.php';
require_once '../../config/activity_hooks.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

// Handle message actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'mark_read') {
            $message_id = $_POST['message_id'];
            $stmt = $mysqli->prepare("UPDATE messages SET is_read = 1 WHERE message_id = ? AND to_user_id = ?");
            $stmt->bind_param("ii", $message_id, $_SESSION['user_id']);
            $stmt->execute();
            echo json_encode(['success' => true]);
            exit();
        }
    } else {
        // Send message
        $recipient_id = $_POST['recipient_id'];
        $subject = trim($_POST['subject']);
        $message = trim($_POST['message']);
        $audio_data = isset($_POST['audio_data']) && !empty($_POST['audio_data']) ? $_POST['audio_data'] : null;
        
        $stmt = $mysqli->prepare("INSERT INTO messages (from_user_id, to_user_id, subject, message, audio_data, is_read) VALUES (?, ?, ?, ?, ?, 0)");
        $stmt->bind_param("iisss", $_SESSION['user_id'], $recipient_id, $subject, $message, $audio_data);
        
        if ($stmt->execute()) {
            // Send notifications
            hook_message_sent($_SESSION['user_id'], $recipient_id, $subject, $message);
            
            // Send email notification
            require_once '../../config/email_config.php';
            
            // Get recipient details
            $recipient_query = $mysqli->prepare("SELECT email, CONCAT(first_name, ' ', last_name) as full_name FROM users WHERE user_id = ?");
            $recipient_query->bind_param("i", $recipient_id);
            $recipient_query->execute();
            $recipient_data = $recipient_query->get_result()->fetch_assoc();
            
            // Get sender details
            $sender_query = $mysqli->prepare("SELECT CONCAT(first_name, ' ', last_name) as full_name FROM users WHERE user_id = ?");
            $sender_query->bind_param("i", $_SESSION['user_id']);
            $sender_query->execute();
            $sender_data = $sender_query->get_result()->fetch_assoc();
            
            if ($recipient_data && $sender_data) {
                sendMessageEmail(
                    $recipient_data['email'],
                    $recipient_data['full_name'],
                    $sender_data['full_name'],
                    $message
                );
            }
            
            $success = "Message sent successfully! 📧 Email notification sent.";
        } else {
            $error = "Error sending message";
        }
    }
}

$page_title = 'Messages';
$page_header = '💬 Messages';
$show_nav = true;
include '../layouts/header.php';

// Get unread count
$unread_result = $mysqli->query("SELECT COUNT(*) as count FROM messages WHERE to_user_id = {$_SESSION['user_id']} AND is_read = 0");
$unread_count = $unread_result->fetch_assoc()['count'];

// Get messages
$messages = $mysqli->query("
    SELECT m.*, 
           CONCAT(s.first_name, ' ', s.last_name) as sender_name, s.role as sender_role,
           CONCAT(r.first_name, ' ', r.last_name) as recipient_name, r.role as recipient_role
    FROM messages m
    LEFT JOIN users s ON m.from_user_id = s.user_id
    LEFT JOIN users r ON m.to_user_id = r.user_id
    WHERE m.from_user_id = {$_SESSION['user_id']} OR m.to_user_id = {$_SESSION['user_id']}
    ORDER BY m.sent_at DESC
    LIMIT 50
");

// Get users for messaging
$users = $mysqli->query("
    SELECT user_id, CONCAT(first_name, ' ', last_name) as full_name, role 
    FROM users 
    WHERE user_id != {$_SESSION['user_id']} AND is_active = 1
    ORDER BY role, first_name
");
?>

<div class="container">
    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <div style="margin-bottom: 1rem; display: flex; justify-content: space-between; align-items: center;">
        <div>
            <a href="../dashboard/index.php" class="btn btn-secondary">← Back</a>
            <button onclick="showCompose()" class="btn">Compose</button>
        </div>
        <div style="background: #0077B6; color: white; padding: 0.5rem 1rem; border-radius: 20px;">
            📧 <?php echo $unread_count; ?> New
        </div>
    </div>

    <div class="section">
        <h3>Messages 
            <button onclick="markAllRead()" class="btn" style="font-size: 12px; padding: 0.3rem 0.6rem; margin-left: 1rem;">Mark All Read</button>
            <button onclick="deleteAll()" class="btn" style="font-size: 12px; padding: 0.3rem 0.6rem; margin-left: 0.5rem; background: #dc3545;">Delete All</button>
        </h3>
        
        <?php if ($messages && $messages->num_rows > 0): ?>
            <div style="max-height: 600px; overflow-y: auto;">
                <?php while ($msg = $messages->fetch_assoc()): ?>
                    <div style="border: 1px solid #ddd; border-radius: 8px; padding: 1rem; margin-bottom: 1rem; background: <?php echo $msg['to_user_id'] == $_SESSION['user_id'] && $msg['is_read'] == 0 ? '#f8f9fa' : '#ffffff'; ?>;">
                        
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                            <h5 style="color: #0077B6; margin: 0;">
                                <?php if ($msg['to_user_id'] == $_SESSION['user_id'] && $msg['is_read'] == 0): ?>
                                    <span style="color: #dc3545;">●</span>
                                <?php endif; ?>
                                <?php echo htmlspecialchars($msg['subject']); ?>
                            </h5>
                            <small><?php echo date('M d, H:i', strtotime($msg['sent_at'])); ?></small>
                        </div>
                        
                        <div style="margin-bottom: 0.5rem; color: #6c757d;">
                            <?php if ($msg['from_user_id'] == $_SESSION['user_id']): ?>
                                To: <?php echo $msg['recipient_name']; ?> (<?php echo $msg['recipient_role']; ?>)
                            <?php else: ?>
                                From: <?php echo $msg['sender_name']; ?> (<?php echo $msg['sender_role']; ?>)
                            <?php endif; ?>
                        </div>
                        
                        <div style="margin-bottom: 1rem;">
                            <?php echo nl2br(htmlspecialchars($msg['message'])); ?>
                        </div>
                        
                        <?php if (strpos($msg['message'], '[Voice Message Recorded') !== false): ?>
                            <div style="background: #f0f0f0; padding: 0.5rem; border-radius: 4px; margin-bottom: 1rem;">
                                🎤 Voice Message
                                <button onclick="playVoice(<?php echo $msg['message_id']; ?>)" style="background: #28a745; color: white; border: none; padding: 0.3rem 0.6rem; border-radius: 4px; margin-left: 1rem;">
                                    ▶️ Play
                                </button>
                                <button onclick="replayVoice(<?php echo $msg['message_id']; ?>)" style="background: #17a2b8; color: white; border: none; padding: 0.3rem 0.6rem; border-radius: 4px; margin-left: 0.5rem;">
                                    🔄 Replay
                                </button>
                            </div>
                        <?php endif; ?>
                        
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="background: <?php echo $msg['from_user_id'] == $_SESSION['user_id'] ? '#28a745' : '#0077B6'; ?>; color: white; padding: 0.2rem 0.5rem; border-radius: 12px; font-size: 0.75rem;">
                                <?php echo $msg['from_user_id'] == $_SESSION['user_id'] ? 'Sent' : 'Received'; ?>
                            </span>
                            
                            <div>
                                <?php if ($msg['from_user_id'] != $_SESSION['user_id'] && $msg['is_read'] == 0): ?>
                                    <button onclick="markRead(<?php echo $msg['message_id']; ?>)" style="background: #17a2b8; color: white; border: none; padding: 0.3rem 0.6rem; border-radius: 4px; font-size: 12px; margin-right: 0.5rem;">
                                        📖 Mark Read
                                    </button>
                                <?php endif; ?>
                                
                                <button onclick="deleteMsg(<?php echo $msg['message_id']; ?>)" style="background: #dc3545; color: white; border: none; padding: 0.3rem 0.6rem; border-radius: 4px; font-size: 12px; margin-right: 0.5rem;">
                                    🗑️ Delete
                                </button>
                                
                                <?php if ($msg['from_user_id'] != $_SESSION['user_id']): ?>
                                    <button onclick="reply('<?php echo htmlspecialchars($msg['sender_name']); ?>', <?php echo $msg['from_user_id']; ?>, 'Re: <?php echo htmlspecialchars($msg['subject']); ?>')" class="btn" style="font-size: 12px; padding: 0.3rem 0.6rem;">
                                        Reply
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div style="text-align: center; padding: 3rem;">
                <h4 style="color: #6c757d;">No Messages</h4>
                <button onclick="showCompose()" class="btn">Send Your First Message</button>
            </div>
        <?php endif; ?>
    </div>

    <!-- Compose Modal -->
    <div id="composeModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 2rem; border-radius: 8px; max-width: 500px; width: 90%;">
            <h4 style="color: #0077B6; margin-bottom: 1rem;">Compose Message</h4>
            <form method="POST" onsubmit="return submitMessage()">
                <div class="form-group">
                    <label>To *</label>
                    <select name="recipient_id" id="recipient" required>
                        <option value="">Select Recipient</option>
                        <?php while ($user = $users->fetch_assoc()): ?>
                            <option value="<?php echo $user['user_id']; ?>"><?php echo $user['full_name']; ?> (<?php echo $user['role']; ?>)</option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Subject *</label>
                    <input type="text" name="subject" id="subject" required>
                </div>
                <div class="form-group">
                    <label>Message *</label>
                    <textarea name="message" id="messageText" rows="4" required></textarea>
                    <input type="hidden" name="audio_data" id="audioData">
                    <div style="margin-top: 0.5rem;">
                        <button type="button" id="recordBtn" onclick="toggleRecord()" style="background: #dc3545; color: white; border: none; padding: 0.5rem 1rem; border-radius: 4px; margin-right: 0.5rem;">
                            🎤 Record
                        </button>
                        <button type="button" id="playBtn" onclick="playPreview()" style="background: #28a745; color: white; border: none; padding: 0.5rem 1rem; border-radius: 4px; display: none;">
                            ▶️ Preview
                        </button>
                    </div>
                </div>
                <button type="submit" class="btn">Send</button>
                <button type="button" onclick="closeCompose()" class="btn btn-secondary">Cancel</button>
            </form>
        </div>
    </div>
</div>

<script>
let recorder, audioChunks = [], recordedAudio;

function showCompose() {
    document.getElementById('composeModal').style.display = 'block';
}

function closeCompose() {
    document.getElementById('composeModal').style.display = 'none';
    document.querySelector('form').reset();
    resetRecording();
}

function reply(name, userId, subject) {
    document.getElementById('recipient').value = userId;
    document.getElementById('subject').value = subject;
    showCompose();
}

async function toggleRecord() {
    const btn = document.getElementById('recordBtn');
    if (!recorder) {
        const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
        recorder = new MediaRecorder(stream);
        
        recorder.ondataavailable = e => audioChunks.push(e.data);
        recorder.onstop = () => {
            const blob = new Blob(audioChunks, { type: 'audio/wav' });
            const reader = new FileReader();
            reader.onload = () => {
                recordedAudio = reader.result;
                document.getElementById('playBtn').style.display = 'inline-block';
                document.getElementById('messageText').value += '\n[Voice Message Recorded - ' + new Date().toLocaleTimeString() + ']';
            };
            reader.readAsDataURL(blob);
        };
        
        recorder.start();
        btn.textContent = '⏹️ Stop';
        btn.style.background = '#6c757d';
    } else {
        recorder.stop();
        recorder.stream.getTracks().forEach(track => track.stop());
        recorder = null;
        btn.textContent = '🎤 Record';
        btn.style.background = '#dc3545';
    }
}

function playPreview() {
    if (recordedAudio) {
        new Audio(recordedAudio).play();
    }
}

function resetRecording() {
    audioChunks = [];
    recordedAudio = null;
    document.getElementById('playBtn').style.display = 'none';
}

function submitMessage() {
    if (recordedAudio) {
        document.getElementById('audioData').value = recordedAudio;
    }
    return true;
}

function playVoice(id) {
    fetch('get_voice_data.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'message_id=' + id
    })
    .then(r => r.json())
    .then(data => {
        if (data.audio_data) {
            new Audio(data.audio_data).play();
        } else {
            alert('No audio available');
        }
    });
}

function replayVoice(id) {
    playVoice(id);
}

function markRead(id) {
    fetch('index_fixed.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=mark_read&message_id=' + id
    })
    .then(() => location.reload());
}

function deleteMsg(id) {
    if (confirm('Delete this message?')) {
        fetch('delete.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'message_id=' + id
        })
        .then(() => location.reload());
    }
}

function markAllRead() {
    fetch('mark_read.php', { method: 'POST' })
    .then(() => location.reload());
}

function deleteAll() {
    if (confirm('Delete ALL messages?')) {
        fetch('delete_all.php', { method: 'POST' })
        .then(() => location.reload());
    }
}
</script>

<?php include '../layouts/footer.php'; ?>