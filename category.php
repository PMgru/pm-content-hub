<?php
require_once 'vendor/autoload.php';
$host = 'localhost';
$dbname = 'content_marketing_db';
$username = 'root';
$password = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $category_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $stmt = $conn->prepare("SELECT name FROM categories WHERE id = ?");
    $stmt->execute([$category_id]);
    $category = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$category) {
        echo "Category not found.";
        exit();
    }

    $stmt = $conn->prepare("SELECT posts.*, categories.name AS category_name, AVG(ratings.rating) as avg_rating 
                            FROM posts 
                            JOIN categories ON posts.category_id = categories.id 
                            LEFT JOIN ratings ON posts.id = ratings.post_id 
                            WHERE posts.category_id = ? 
                            GROUP BY posts.id");
    $stmt->execute([$category_id]);
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $category['name']; ?> | Content Learning Hub</title>
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
                <?php session_start(); if (isset($_SESSION['username'])): ?>
                    <li><a href="dashboard.php"><?php echo $_SESSION['username']; ?></a></li>
                    <li><a href="logout.php">Logout</a></li>
                <?php else: ?>
                    <li><a href="register.html">Sign Up</a></li>
                    <li><a href="login.html">Login</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>

    <section class="content">
        <h2>Posts in <?php echo $category['name']; ?></h2>
        <div class="post-list">
            <?php foreach ($posts as $post): ?>
                <div class="post">
                    <?php if ($post['featured_image']): ?>
                        <img src="images/<?php echo $post['featured_image']; ?>" alt="<?php echo $post['title']; ?>">
                    <?php endif; ?>
                    <h3><a href="post.php?id=<?php echo $post['id']; ?>"><?php echo $post['title']; ?></a></h3>
                    <span>Category: <?php echo $post['category_name']; ?></span>
                    <p><?php echo substr(strip_tags($post['body']), 0, 150); ?>...</p>
                    <div class="rating">
                        <i class="fas fa-star"></i> <?php echo number_format($post['avg_rating'], 1) ?: 'No ratings'; ?>
                    </div>
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