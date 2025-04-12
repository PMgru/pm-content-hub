<?php
header('Content-Type: application/json');
session_start();

$host = 'localhost';
$dbname = 'content_marketing_db';
$username = 'root';
$password = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $message = isset($_POST['message']) ? trim($_POST['message']) : '';
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

    if (empty($message)) {
        echo json_encode(['response' => 'Please enter a message.']);
        exit();
    }

    // Store the user's message in the database
    $stmt = $conn->prepare("INSERT INTO chat_messages (user_id, message, sender) VALUES (?, ?, 'user')");
    $stmt->execute([$user_id, $message]);

    // Check for automatic responses from chatbot_responses table
    $stmt = $conn->prepare("SELECT response FROM chatbot_responses WHERE keyword = ?");
    $stmt->execute([strtolower($message)]);
    $auto_response = $stmt->fetchColumn();

    if ($auto_response) {
        // If an automatic response is found, store it as a bot message
        $stmt = $conn->prepare("INSERT INTO chat_messages (user_id, message, sender) VALUES (?, ?, 'bot')");
        $stmt->execute([$user_id, $auto_response]);
        echo json_encode(['response' => $auto_response]);
    } else {
        // Default response if no automatic response is found
        $default_response = "Your message has been sent to the admin. Please wait for a reply.";
        echo json_encode(['response' => $default_response]);
    }

} catch (PDOException $e) {
    echo json_encode(['response' => 'Error: ' . $e->getMessage()]);
}
?>