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
    if ($user['role'] != 'admin') {
        header("Location: dashboard.php");
        exit();
    }

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $title = $_POST['title'];
        $description = $_POST['description'];
        $price = $_POST['price'];
        $course_link = $_POST['course_link'];
        $meta_keywords = $_POST['meta_keywords'];
        $meta_description = $_POST['meta_description'];
        $focus_keyword = $_POST['focus_keyword'];

        // Calculate SEO Score
        $seo_score = 0;
        if (strlen($title) >= 50 && strlen($title) <= 60) $seo_score += 20;
        if (strlen($meta_description) >= 120 && strlen($meta_description) <= 160) $seo_score += 20;
        if (!empty($focus_keyword) && strpos($description, $focus_keyword) !== false) $seo_score += 20;
        if (!empty($meta_keywords)) $seo_score += 20;

        $featured_image = '';
        if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] == 0) {
            $target_dir = "images/";
            if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
            $target_file = $target_dir . time() . '_' . basename($_FILES["featured_image"]["name"]);
            if (move_uploaded_file($_FILES["featured_image"]["tmp_name"], $target_file)) {
                $featured_image = basename($target_file);
            }
        }

        $stmt = $conn->prepare("INSERT INTO courses (title, description, price, course_link, featured_image, meta_keywords, meta_description, seo_score) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$title, $description, $price, $course_link, $featured_image, $meta_keywords, $meta_description, $seo_score]);
        echo "<script>alert('Course added successfully! SEO Score: $seo_score%'); window.location.href='dashboard.php';</script>";
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
    <title>Add Course | Content Learning Hub</title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script>
        function calculateSEOScore() {
            let score = 0;
            const title = document.getElementById('title').value;
            const metaDesc = document.getElementById('meta_description').value;
            const focusKeyword = document.getElementById('focus_keyword').value;
            const description = document.getElementById('description').value;

            if (title.length >= 50 && title.length <= 60) score += 20;
            if (metaDesc.length >= 120 && metaDesc.length <= 160) score += 20;
            if (focusKeyword && description.includes(focusKeyword)) score += 20;
            if (document.querySelector('input[name="meta_keywords"]').value) score += 20;

            document.getElementById('seo-score').innerText = `SEO Score: ${score}%`;
            if (score < 60) {
                document.getElementById('seo-suggestions').innerText = 'Suggestions: Optimize title length (50-60 chars), meta description (120-160 chars), use focus keyword in description.';
            } else if (score < 80) {
                document.getElementById('seo-suggestions').innerText = 'Suggestions: Ensure focus keyword appears in the description and add more details.';
            } else {
                document.getElementById('seo-suggestions').innerText = 'Great job! Your course is well-optimized.';
            }
        }
    </script>
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
                <li><a href="dashboard.php"><?php echo $_SESSION['username']; ?></a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>

    <section class="publish">
        <h2>Add New Course</h2>
        <form action="add_course.php" method="POST" enctype="multipart/form-data" oninput="calculateSEOScore()">
            <label for="title">Course Title</label>
            <input type="text" id="title" name="title" required>
            
            <label for="description">Description</label>
            <textarea id="description" name="description" rows="5" required></textarea>
            
            <label for="price">Price (৳)</label>
            <input type="number" id="price" name="price" step="0.01" required>
            
            <label for="course_link">Course Link</label>
            <input type="url" id="course_link" name="course_link" required>
            
            <label for="featured_image">Featured Image</label>
            <input type="file" id="featured_image" name="featured_image">
            
            <h3>SEO Settings</h3>
            <label for="focus_keyword">Focus Keyword</label>
            <input type="text" id="focus_keyword" name="focus_keyword" placeholder="Enter your main keyword">
            
            <label for="meta_keywords">Meta Keywords (for SEO)</label>
            <input type="text" id="meta_keywords" name="meta_keywords" placeholder="Enter keywords separated by commas">
            
            <label for="meta_description">Meta Description (for SEO)</label>
            <textarea id="meta_description" name="meta_description" rows="3" placeholder="Enter a short description (150-160 characters)"></textarea>
            
            <div id="seo-score">SEO Score: 0%</div>
            <div id="seo-suggestions"></div>
            
            <button type="submit" class="btn">Add Course</button>
        </form>
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