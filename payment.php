<?php
require_once 'vendor/autoload.php';
session_start();
if (!isset($_SESSION['username']) || !isset($_SESSION['course_id'])) {
    header("Location: login.html");
    exit();
}

$host = 'localhost';
$dbname = 'content_marketing_db';
$username = 'root';
$password = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $course_id = $_SESSION['course_id'];
    $stmt = $conn->prepare("SELECT * FROM courses WHERE id = ?");
    $stmt->execute([$course_id]);
    $course = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $user_id = $_SESSION['user_id'];
        $transaction_id = $_POST['transaction_id'];
    
        $stmt = $conn->prepare("SELECT email FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        $user_email = $user['email'];
    
        $stmt = $conn->prepare("INSERT INTO enrollments (user_id, course_id, transaction_id, email) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user_id, $course_id, $transaction_id, $user_email]);

        $_SESSION['transaction_id'] = $transaction_id;
        header("Location: confirm_payment.php");
        exit();
    }
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment | Content Learning Hub</title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&family=Poppins:wght@400;600&display=swap" rel="stylesheet">
</head>
<body>
    <header>
        <div class="logo">
            <img src="images/logo.png" alt="Content Learning Hub Logo">
        </div>
        <nav>
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="about.php">About</a></li>
                <li><a href="content.php">Content</a></li>
                <li><a href="courses.php">Courses</a></li>
                <li><a href="dashboard.php"><?php echo $_SESSION['username']; ?></a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>

    <section class="payment">
        <h2>Payment for <?php echo $course['title']; ?></h2>
        <p>Total Amount: ৳<?php echo $course['price']; ?></p>
        <p>Please send the payment to one of the following methods and confirm below:</p>
        <ul>
            <li><strong>Bkash:</strong> 01XXXXXXXXX</li>
            <li><strong>Nagad:</strong> 01XXXXXXXXX</li>
        </ul>
        <form action="payment.php" method="POST">
            <label for="transaction_id">Transaction ID</label>
            <input type="text" id="transaction_id" name="transaction_id" required>
            <button type="submit" class="btn">Confirm Payment</button>
        </form>
    </section>

    <footer>
        <p>© 2025 Content Learning Hub. All Rights Reserved.</p>
    </footer>
</body>
</html>