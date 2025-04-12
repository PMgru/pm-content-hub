<?php
require_once 'vendor/autoload.php';
session_start();
if (!isset($_SESSION['username']) || !isset($_SESSION['course_id']) || !isset($_SESSION['transaction_id'])) {
    header("Location: login.html");
    exit();
}

require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require 'PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$host = 'localhost';
$dbname = 'content_marketing_db';
$username = 'root';
$password = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $course_id = $_SESSION['course_id'];
    $user_id = $_SESSION['user_id'];
    $transaction_id = $_SESSION['transaction_id'];

    $stmt = $conn->prepare("SELECT * FROM courses WHERE id = ?");
    $stmt->execute([$course_id]);
    $course = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt = $conn->prepare("SELECT * FROM enrollments WHERE user_id = ? AND course_id = ? AND transaction_id = ?");
    $stmt->execute([$user_id, $course_id, $transaction_id]);
    $enrollment = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt = $conn->prepare("SELECT email FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    $user_email = $user['email'];

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        // Send email with PHPMailer
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'pialmahmud0171@gmail.com'; // আপনার Gmail ঠিকানা
            $mail->Password = 'yioy yldo jctd ryjd'; // আপনার App Password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            $mail->setFrom('your-email@gmail.com', 'Content Learning Hub');
            $mail->addAddress($user_email);

            $mail->isHTML(true);
            $mail->Subject = 'Enrollment Confirmation - ' . $course['title'];
            $mail->Body = "Dear " . $_SESSION['username'] . ",<br><br>You have successfully enrolled in the course: <strong>" . $course['title'] . "</strong>.<br>Access the course here: <a href='" . $course['course_link'] . "'>" . $course['course_link'] . "</a><br><br>Best regards,<br>Content Learning Hub";

            $mail->send();
            unset($_SESSION['course_id']);
            unset($_SESSION['transaction_id']);
            echo "<script>alert('Course link has been sent to your email!'); window.location.href='courses.php';</script>";
        } catch (Exception $e) {
            echo "<script>alert('Failed to send email. Error: {$mail->ErrorInfo}'); window.location.href='courses.php';</script>";
        }
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
    <title>Confirm Payment | Content Learning Hub</title>
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

    <section class="confirm-payment">
        <h2>Payment Confirmation</h2>
        <p><strong>Course:</strong> <?php echo $course['title']; ?></p>
        <p><strong>User:</strong> <?php echo $_SESSION['username']; ?></p>
        <p><strong>Date:</strong> <?php echo $enrollment['created_at']; ?></p>
        <p><strong>Transaction ID:</strong> <?php echo $transaction_id; ?></p>
        <form action="confirm_payment.php" method="POST">
            <button type="submit" class="btn">OK</button>
        </form>
    </section>

    <footer>
        <p>© 2025 Content Learning Hub. All Rights Reserved.</p>
    </footer>
</body>
</html>