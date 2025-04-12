<?php
require_once 'vendor/autoload.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$host = 'localhost';
$dbname = 'content_marketing_db';
$username = 'root';
$password = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Fetch all chat messages
    $stmt = $conn->query("SELECT cm.*, u.username FROM chat_messages cm LEFT JOIN users u ON cm.user_id = u.id ORDER BY cm.created_at ASC");
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Handle admin reply
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reply'])) {
        $user_id = $_POST['user_id'];
        $reply = trim($_POST['reply_message']);
        if (!empty($reply)) {
            $stmt = $conn->prepare("INSERT INTO chat_messages (user_id, message, sender) VALUES (?, ?, 'admin')");
            $stmt->execute([$user_id, $reply]);
            header("Location: manage_chatbot.php");
            exit();
        }
    }

    // Handle automatic response addition
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_response'])) {
        $keyword = trim($_POST['keyword']);
        $response = trim($_POST['response']);
        if (!empty($keyword) && !empty($response)) {
            $stmt = $conn->prepare("INSERT INTO chatbot_responses (keyword, response) VALUES (?, ?)");
            $stmt->execute([strtolower($keyword), $response]);
            header("Location: manage_chatbot.php");
            exit();
        }
    }

    // Fetch all automatic responses
    $stmt = $conn->query("SELECT * FROM chatbot_responses");
    $auto_responses = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Chatbot - Content Learning Hub</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header>
        <div class="logo">
            <img src="images/logo.png" alt="Logo">
        </div>
        <nav>
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>

    <section class="manage-chatbot">
        <h2>Manage Chatbot</h2>

        <h3>Chat Messages</h3>
        <div class="chat-messages">
            <?php foreach ($messages as $message): ?>
                <div class="message <?php echo $message['sender']; ?>">
                    <strong><?php echo $message['sender'] === 'user' ? ($message['username'] ?? 'Guest') : $message['sender']; ?>:</strong>
                    <p><?php echo htmlspecialchars($message['message']); ?></p>
                    <small><?php echo $message['created_at']; ?></small>
                    <?php if ($message['sender'] === 'user'): ?>
                        <form method="POST">
                            <input type="hidden" name="user_id" value="<?php echo $message['user_id']; ?>">
                            <textarea name="reply_message" placeholder="Type your reply..." required></textarea>
                            <button type="submit" name="reply" class="btn">Reply</button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <h3>Add Automatic Response</h3>
        <form method="POST">
            <label for="keyword">Keyword:</label>
            <input type="text" id="keyword" name="keyword" required>
            <label for="response">Response:</label>
            <textarea id="response" name="response" required></textarea>
            <button type="submit" name="add_response" class="btn">Add Response</button>
        </form>

        <h3>Automatic Responses</h3>
        <table>
            <thead>
                <tr>
                    <th>Keyword</th>
                    <th>Response</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($auto_responses as $response): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($response['keyword']); ?></td>
                        <td><?php echo htmlspecialchars($response['response']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>

    <footer>
        <p>© 2025 Content Learning Hub. All Rights Reserved.</p>
    </footer>
</body>
</html>