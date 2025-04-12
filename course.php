<?php
require_once 'vendor/autoload.php';
session_start();

$host = 'localhost';
$dbname = 'content_marketing_db';
$username = 'root';
$password = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if (!isset($_GET['id'])) {
        header("Location: courses.php");
        exit();
    }

    $course_id = (int)$_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM courses WHERE id = ?");
    $stmt->execute([$course_id]);
    $course = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$course) {
        header("Location: courses.php");
        exit();
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
        $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
        if ($rating >= 1 && $rating <= 5) {
            $stmt = $conn->prepare("UPDATE courses SET rating = ? WHERE id = ?");
            $stmt->execute([$rating, $course_id]);
            header("Location: course.php?id=$course_id");
            exit();
        }
    }

    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    $chat_messages = [];
    if ($user_id) {
        $stmt = $conn->prepare("SELECT * FROM chat_messages WHERE user_id = ? ORDER BY created_at ASC");
        $stmt->execute([$user_id]);
        $chat_messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

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
    <title><?php echo htmlspecialchars($course['title']); ?> - Content Learning Hub</title>
    <link rel="stylesheet" href="styles.css?v=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <div id="loading-spinner">
        <div class="spinner"></div>
    </div>

    <header>
        <div class="logo">
            <img src="images/logo.png" alt="Logo">
        </div>
        <nav>
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="about.php">About</a></li>
                <li><a href="content.php">Content</a></li>
                <li><a href="courses.php" class="active">Courses</a></li>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li><a href="logout.php">Logout</a></li>
                    <?php if ($_SESSION['role'] == 'admin'): ?>
                        <li><a href="dashboard.php">Dashboard</a></li>
                        <li><a href="manage_chatbot.php">Manage Chatbot</a></li>
                    <?php endif; ?>
                <?php else: ?>
                    <li><a href="register.php">Sign Up</a></li>
                    <li><a href="login.php">Login</a></li>
                <?php endif; ?>
            </ul>
            <div class="theme-toggle" id="theme-toggle">
                <i class="fas fa-moon"></i>
            </div>
        </nav>
    </header>

    <section class="course-details">
        <h2><?php echo htmlspecialchars($course['title']); ?></h2>
        <span>Posted on: <?php echo htmlspecialchars($course['created_at']); ?></span>
        <div class="rating">
            <?php for ($i = 1; $i <= 5; $i++): ?>
                <i class="fas fa-star <?php echo $i <= $course['rating'] ? 'filled' : ''; ?>"></i>
            <?php endfor; ?>
            <span class="rating-count"><?php echo htmlspecialchars($course['rating']); ?>/5</span>
        </div>
        <img src="<?php echo htmlspecialchars($course['image']); ?>" alt="Course Image">
        <p><?php echo htmlspecialchars($course['description']); ?></p>

        <?php if (isset($_SESSION['user_id'])): ?>
            <form method="POST">
                <label for="rating">Rate This Course:</label>
                <select name="rating" id="rating">
                    <option value="1">1 Star</option>
                    <option value="2">2 Stars</option>
                    <option value="3">3 Stars</option>
                    <option value="4">4 Stars</option>
                    <option value="5">5 Stars</option>
                </select>
                <button type="submit" class="btn">Submit Rating</button>
            </form>
        <?php else: ?>
            <p>Please <a href="login.php">login</a> to rate this course.</p>
        <?php endif; ?>
    </section>

    <!-- Chatbot -->
    <div class="chatbot-icon" id="chatbot-icon">
        <i class="fas fa-comment"></i>
    </div>
    <div class="chatbot" id="chatbot">
        <div class="chatbot-header">
            <h3>Chat with Us</h3>
            <button onclick="toggleChatbot()">X</button>
        </div>
        <div class="chatbot-body" id="chatbot-body">
            <?php if (!empty($chat_messages)): ?>
                <?php foreach ($chat_messages as $msg): ?>
                    <div class="message <?php echo $msg['sender']; ?>">
                        <?php echo htmlspecialchars($msg['message']); ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="message bot">Hello! How can I help you today?</div>
            <?php endif; ?>
        </div>
        <div class="chatbot-input">
            <input type="text" id="chatbot-input" placeholder="Type your message...">
            <button onclick="sendMessage()">Send</button>
        </div>
    </div>

    <div class="back-to-top" id="back-to-top">
        <i class="fas fa-arrow-up"></i>
    </div>

    <footer>
        <p>© 2025 Content Learning Hub. All Rights Reserved.</p>
        <div class="footer-social">
            <a href="https://facebook.com" target="_blank"><i class="fab fa-facebook-f"></i></a>
            <a href="https://twitter.com" target="_blank"><i class="fab fa-twitter"></i></a>
            <a href="https://linkedin.com" target="_blank"><i class="fab fa-linkedin-in"></i></a>
            <a href="https://instagram.com" target="_blank"><i class="fab fa-instagram"></i></a>
        </div>
    </footer>

    <script>
        // Loading Spinner
        window.addEventListener('load', () => {
            const spinner = document.getElementById('loading-spinner');
            spinner.style.display = 'none';
        });

        // Dark Mode Toggle
        const themeToggle = document.getElementById('theme-toggle');
        const body = document.body;
        const themeIcon = themeToggle.querySelector('i');

        if (localStorage.getItem('theme') === 'dark') {
            body.classList.add('dark-mode');
            themeIcon.classList.replace('fa-moon', 'fa-sun');
        }

        themeToggle.addEventListener('click', () => {
            body.classList.toggle('dark-mode');
            if (body.classList.contains('dark-mode')) {
                themeIcon.classList.replace('fa-moon', 'fa-sun');
                localStorage.setItem('theme', 'dark');
            } else {
                themeIcon.classList.replace('fa-sun', 'fa-moon');
                localStorage.setItem('theme', 'light');
            }
        });

        // Chatbot Functionality
        function toggleChatbot() {
            const chatbot = document.getElementById('chatbot');
            const chatbotIcon = document.getElementById('chatbot-icon');
            if (chatbot.style.display === 'flex') {
                chatbot.style.display = 'none';
                chatbotIcon.style.display = 'flex';
            } else {
                chatbot.style.display = 'flex';
                chatbotIcon.style.display = 'none';
            }
        }

        function sendMessage() {
            const input = document.getElementById('chatbot-input');
            const body = document.getElementById('chatbot-body');
            const message = input.value.trim();

            if (message) {
                const userMessage = document.createElement('div');
                userMessage.className = 'message user';
                userMessage.textContent = message;
                body.appendChild(userMessage);

                fetch('chatbot.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'message=' + encodeURIComponent(message)
                })
                .then(response => response.json())
                .then(data => {
                    const botMessage = document.createElement('div');
                    botMessage.className = 'message bot';
                    botMessage.textContent = data.response;
                    body.appendChild(botMessage);
                    body.scrollTop = body.scrollHeight;
                })
                .catch(error => {
                    console.error('Error:', error);
                    const botMessage = document.createElement('div');
                    botMessage.className = 'message bot';
                    botMessage.textContent = 'Sorry, something went wrong. Please try again.';
                    body.appendChild(botMessage);
                    body.scrollTop = body.scrollHeight;
                });

                input.value = '';
            }
        }

        document.getElementById('chatbot-input').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                sendMessage();
            }
        });

        // Chatbot Dragging Functionality
        const chatbot = document.getElementById('chatbot');
        let isDragging = false;
        let currentX;
        let currentY;
        let initialX;
        let initialY;

        chatbot.addEventListener('mousedown', startDragging);
        chatbot.addEventListener('mousemove', drag);
        chatbot.addEventListener('mouseup', stopDragging);
        chatbot.addEventListener('mouseleave', stopDragging);

        function startDragging(e) {
            initialX = e.clientX - currentX;
            initialY = e.clientY - currentY;
            isDragging = true;
        }

        function drag(e) {
            if (isDragging) {
                e.preventDefault();
                currentX = e.clientX - initialX;
                currentY = e.clientY - initialY;
                chatbot.style.left = currentX + 'px';
                chatbot.style.top = currentY + 'px';
                chatbot.style.bottom = 'auto';
                chatbot.style.right = 'auto';
            }
        }

        function stopDragging() {
            isDragging = false;
        }

        // Initialize chatbot position
        currentX = window.innerWidth - 370;
        currentY = window.innerHeight - 550;
        chatbot.style.left = currentX + 'px';
        chatbot.style.top = currentY + 'px';

        document.getElementById('chatbot-icon').style.display = 'flex';
        document.getElementById('chatbot-icon').addEventListener('click', toggleChatbot);

        // Back to Top Functionality
        const backToTop = document.getElementById('back-to-top');
        window.addEventListener('scroll', () => {
            if (window.scrollY > 300) {
                backToTop.style.display = 'flex';
            } else {
                backToTop.style.display = 'none';
            }
        });

        backToTop.addEventListener('click', () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    </script>
</body>
</html>