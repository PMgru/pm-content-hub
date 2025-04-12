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

    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 24;
    $offset = ($page - 1) * $limit;

    $stmt = $conn->prepare("SELECT id, title, image, rating, created_at FROM posts ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $totalStmt = $conn->query("SELECT COUNT(*) FROM posts");
    $total_posts = $totalStmt->fetchColumn();
    $total_pages = ceil($total_posts / $limit);

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
    <title>Content - Content Learning Hub</title>
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
                <li><a href="content.php" class="active">Content</a></li>
                <li><a href="courses.php">Courses</a></li>
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

    <section class="content">
        <h2>Latest Content</h2>
        <div class="post-list">
            <?php if (!empty($posts)): ?>
                <?php $index = 0; ?>
                <?php foreach ($posts as $post): ?>
                    <div class="post" style="--index: <?php echo $index++; ?>;">
                        <img src="<?php echo htmlspecialchars($post['image']); ?>" alt="Post Image">
                        <h3><?php echo htmlspecialchars($post['title']); ?></h3>
                        <span>Posted on: <?php echo htmlspecialchars($post['created_at']); ?></span>
                        <div class="rating">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="fas fa-star <?php echo $i <= $post['rating'] ? 'filled' : ''; ?>"></i>
                            <?php endfor; ?>
                        </div>
                        <a href="post.php?id=<?php echo $post['id']; ?>" class="btn">Read More</a>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No content available.</p>
            <?php endif; ?>
        </div>

        <div class="pagination">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="content.php?page=<?php echo $i; ?>" class="btn <?php echo $page == $i ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
            <?php if ($page < $total_pages): ?>
                <a href="content.php?page=<?php echo $page + 1; ?>" class="btn">Next</a>
            <?php endif; ?>
        </div>
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