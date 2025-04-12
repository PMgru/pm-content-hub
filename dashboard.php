<?php
require_once 'vendor/autoload.php';
session_start();
if (!isset($_SESSION['username'])) {
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

    $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt = $conn->prepare("SELECT * FROM posts WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $conn->prepare("SELECT * FROM enrollments WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $enrollments = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Content Learning Hub</title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="loader">
        <div class="spinner"></div>
    </div>

    <div class="theme-toggle">
        <i class="fas fa-moon"></i>
    </div>

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
                <li><a href="dashboard.php" class="active"><?php echo $_SESSION['username']; ?></a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>

    <section class="dashboard">
        <h2>Welcome, <?php echo $_SESSION['username']; ?>!</h2>
        <?php if ($user['role'] == 'admin'): ?>
            <div class="admin-actions">
                <a href="publish.php" class="btn">Publish New Post</a>
                <a href="add_course.php" class="btn">Add New Course</a>
            </div>
        <?php endif; ?>

        <h3>Your Posts</h3>
        <div class="post-list">
            <?php foreach ($posts as $post): ?>
                <div class="post">
                    <h3><?php echo $post['title']; ?></h3>
                    <p><?php echo substr(strip_tags($post['body']), 0, 150); ?>...</p>
                    <a href="post.php?id=<?php echo $post['id']; ?>" class="btn">View Post</a>
                </div>
            <?php endforeach; ?>
        </div>

        <h3>Your Enrollments</h3>
        <div class="enrollment-list">
            <?php foreach ($enrollments as $enrollment): ?>
                <div class="enrollment">
                    <p>Course ID: <?php echo $enrollment['course_id']; ?></p>
                    <p>Enrolled on: <?php echo $enrollment['created_at']; ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <div class="chatbot">
        <div class="chatbot-header">
            <span>Chat with Us</span>
            <button onclick="toggleChatbot()">X</button>
        </div>
        <div class="chatbot-body" id="chatbot-body">
            <div class="message bot">Hello! How can I help you today?</div>
        </div>
        <div class="chatbot-input">
            <input type="text" id="chatbot-input" placeholder="Type your message..." onkeypress="if(event.keyCode==13) sendMessage()">
            <button onclick="sendMessage()">Send</button>
        </div>
    </div>

    <button class="back-to-top" onclick="scrollToTop()"><i class="fas fa-arrow-up"></i></button>

    <footer>
        <p>© 2025 Content Learning Hub. All Rights Reserved.</p>
    </footer>

    <script>
        // Loader
        window.addEventListener('load', () => {
            document.querySelector('.loader').style.display = 'none';
        });

        // Theme Toggle
        document.querySelector('.theme-toggle').addEventListener('click', () => {
            document.body.classList.toggle('dark-theme');
            const icon = document.querySelector('.theme-toggle i');
            icon.classList.toggle('fa-moon');
            icon.classList.toggle('fa-sun');
        });

        // Chatbot
        function toggleChatbot() {
            const chatbot = document.querySelector('.chatbot');
            chatbot.style.display = chatbot.style.display === 'none' ? 'block' : 'none';
        }

        function sendMessage() {
            const input = document.getElementById('chatbot-input');
            const message = input.value.trim();
            if (!message) return;

            const chatBody = document.getElementById('chatbot-body');
            chatBody.innerHTML += `<div class="message user">${message}</div>`;
            input.value = '';

            fetch('chatbot.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `message=${encodeURIComponent(message)}`
            })
            .then(response => response.json())
            .then(data => {
                chatBody.innerHTML += `<div class="message bot">${data.response}</div>`;
                chatBody.scrollTop = chatBody.scrollHeight;
            });
        }

        // Smooth Scroll
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });

        // Back to Top
        function scrollToTop() {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
        window.addEventListener('scroll', () => {
            const backToTop = document.querySelector('.back-to-top');
            if (window.scrollY > 300) backToTop.style.display = 'block';
            else backToTop.style.display = 'none';
        });
    </script>
</body>
</html>