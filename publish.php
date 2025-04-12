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

    $stmt = $conn->query("SELECT * FROM categories");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $title = $_POST['title'];
        $body = $_POST['body'];
        $category_id = $_POST['category_id'];
        $meta_keywords = $_POST['meta_keywords'];
        $meta_description = $_POST['meta_description'];
        $focus_keyword = $_POST['focus_keyword'];
        $image_alt = $_POST['image_alt'];
        $user_id = $_SESSION['user_id'];
        $header_tags = $_POST['header_tags'];
        $font_style = $_POST['font_style'];
        $image_positions = $_POST['image_positions'] ?? [];

        // Calculate SEO Score
        $seo_score = 0;
        if (strlen($title) >= 50 && strlen($title) <= 60) $seo_score += 20;
        if (strlen($meta_description) >= 120 && strlen($meta_description) <= 160) $seo_score += 20;
        if (!empty($focus_keyword) && strpos($body, $focus_keyword) !== false) $seo_score += 20;
        if (!empty($image_alt)) $seo_score += 20;
        if (!empty($meta_keywords)) $seo_score += 20;

        // Handle multiple images
        $images = [];
        if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
            $target_dir = "images/";
            if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
            foreach ($_FILES['images']['name'] as $key => $name) {
                if ($_FILES['images']['error'][$key] == 0) {
                    $target_file = $target_dir . time() . '_' . basename($name);
                    if (move_uploaded_file($_FILES['images']['tmp_name'][$key], $target_file)) {
                        $images[] = basename($target_file);
                    }
                }
            }
        }

        $featured_image = '';
        if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] == 0) {
            $target_dir = "images/";
            if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
            $target_file = $target_dir . time() . '_' . basename($_FILES["featured_image"]["name"]);
            if (move_uploaded_file($_FILES["featured_image"]["tmp_name"], $target_file)) {
                $featured_image = basename($target_file);
            }
        }

        $stmt = $conn->prepare("INSERT INTO posts (user_id, category_id, title, body, featured_image, images, image_positions, meta_keywords, meta_description, seo_score) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $category_id, $title, $body, $featured_image, json_encode($images), json_encode($image_positions), $meta_keywords, $meta_description, $seo_score]);
        echo "<script>alert('Post published successfully! SEO Score: $seo_score%'); window.location.href='dashboard.php';</script>";
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
    <title>Publish Post | Content Learning Hub</title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&family=Poppins:wght@400;600&family=Open+Sans:wght@400;700&family=Lato:wght@400;700&family=Montserrat:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
    <script>
        tinymce.init({
            selector: '#body',
            plugins: 'advlist autolink lists link image charmap preview anchor searchreplace visualblocks code fullscreen insertdatetime media table paste code help wordcount',
            toolbar: 'undo redo | formatselect | bold italic underline | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat | help',
            content_style: 'body { font-family: Roboto, sans-serif; font-size: 18px; }'
        });

        function addImageField() {
            const container = document.getElementById('image-container');
            const div = document.createElement('div');
            div.className = 'image-field';
            div.innerHTML = `
                <input type="file" name="images[]">
                <label>Image Position:</label>
                <select name="image_positions[]">
                    <option value="before">Before Content</option>
                    <option value="middle">Middle of Content</option>
                    <option value="after">After Content</option>
                </select>
                <label>Image Size (px):</label>
                <input type="number" name="image_sizes[]" placeholder="Width">
                <button type="button" onclick="suggestImagePlacement(this)">Suggest Placement</button>
            `;
            container.appendChild(div);
        }

        function suggestImagePlacement(button) {
            const imageField = button.parentElement;
            const positionSelect = imageField.querySelector('select[name="image_positions[]"]');
            const sizeInput = imageField.querySelector('input[name="image_sizes[]"]');
            const contentLength = document.getElementById('body').value.length;

            if (contentLength < 500) {
                positionSelect.value = 'after';
                sizeInput.value = 300;
                alert('Suggestion: For short content (<500 chars), place the image after the content with a width of 300px.');
            } else if (contentLength < 1000) {
                positionSelect.value = 'middle';
                sizeInput.value = 500;
                alert('Suggestion: For medium content (500-1000 chars), place the image in the middle with a width of 500px.');
            } else {
                positionSelect.value = 'before';
                sizeInput.value = 700;
                alert('Suggestion: For long content (>1000 chars), place the image before the content with a width of 700px.');
            }
        }

        function calculateSEOScore() {
            let score = 0;
            const title = document.getElementById('title').value;
            const metaDesc = document.getElementById('meta_description').value;
            const focusKeyword = document.getElementById('focus_keyword').value;
            const content = tinymce.get('body').getContent();
            const imageAlt = document.getElementById('image_alt').value;

            if (title.length >= 50 && title.length <= 60) score += 20;
            if (metaDesc.length >= 120 && metaDesc.length <= 160) score += 20;
            if (focusKeyword && content.includes(focusKeyword)) score += 20;
            if (imageAlt) score += 20;
            if (document.querySelector('input[name="meta_keywords"]').value) score += 20;

            document.getElementById('seo-score').innerText = `SEO Score: ${score}%`;
            if (score < 60) {
                document.getElementById('seo-suggestions').innerText = 'Suggestions: Optimize title length (50-60 chars), meta description (120-160 chars), use focus keyword in content, and add image alt text.';
            } else if (score < 80) {
                document.getElementById('seo-suggestions').innerText = 'Suggestions: Ensure focus keyword appears in the first paragraph and add more internal links.';
            } else {
                document.getElementById('seo-suggestions').innerText = 'Great job! Your content is well-optimized.';
            }
        }

        function suggestKeywords() {
            const content = tinymce.get('body').getContent({ format: 'text' });
            fetch('https://api-inference.huggingface.co/models/distilbert-base-uncased', {
                method: 'POST',
                headers: {
                    'Authorization': 'Bearer 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',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ inputs: content })
            })
            .then(response => response.json())
            .then(data => {
                if (data && Array.isArray(data)) {
                    const keywords = data.map(item => item.token_str).filter(keyword => keyword.length > 3).join(', ');
                    document.getElementById('meta_keywords').value = keywords;
                    alert('Suggested Keywords: ' + keywords);
                } else {
                    alert('No keywords found.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to suggest keywords. Error: ' + error.message);
            });
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
        <h2>Publish New Post</h2>
        <form action="publish.php" method="POST" enctype="multipart/form-data" oninput="calculateSEOScore()">
            <label for="title">Title</label>
            <input type="text" id="title" name="title" required>
            
            <label for="category_id">Category</label>
            <select id="category_id" name="category_id" required>
                <?php foreach ($categories as $category): ?>
                    <option value="<?php echo $category['id']; ?>"><?php echo $category['name']; ?></option>
                <?php endforeach; ?>
            </select>
            
            <label for="header_tags">Header Tags</label>
            <select id="header_tags" name="header_tags">
                <option value="h1">H1</option>
                <option value="h2">H2</option>
                <option value="h3">H3</option>
                <option value="h4">H4</option>
                <option value="h5">H5</option>
                <option value="h6">H6</option>
            </select>
            
            <label for="font_style">Font Style</label>
            <select id="font_style" name="font_style">
                <option value="Roboto">Roboto</option>
                <option value="Poppins">Poppins</option>
                <option value="Open Sans">Open Sans</option>
                <option value="Lato">Lato</option>
                <option value="Montserrat">Montserrat</option>
            </select>
            
            <label for="body">Content</label>
            <textarea id="body" name="body" rows="10" required></textarea>
            
            <label for="featured_image">Featured Image</label>
            <input type="file" id="featured_image" name="featured_image">
            
            <label for="image_alt">Image Alt Text (for SEO)</label>
            <input type="text" id="image_alt" name="image_alt">
            
            <h3>Additional Images</h3>
            <div id="image-container">
                <div class="image-field">
                    <input type="file" name="images[]">
                    <label>Image Position:</label>
                    <select name="image_positions[]">
                        <option value="before">Before Content</option>
                        <option value="middle">Middle of Content</option>
                        <option value="after">After Content</option>
                    </select>
                    <label>Image Size (px):</label>
                    <input type="number" name="image_sizes[]" placeholder="Width">
                    <button type="button" onclick="suggestImagePlacement(this)">Suggest Placement</button>
                </div>
            </div>
            <button type="button" onclick="addImageField()">Add Image</button>
            
            <h3>SEO Settings</h3>
            <label for="focus_keyword">Focus Keyword</label>
            <input type="text" id="focus_keyword" name="focus_keyword" placeholder="Enter your main keyword">
            
            <label for="meta_keywords">Meta Keywords (for SEO)</label>
            <input type="text" id="meta_keywords" name="meta_keywords" placeholder="Enter keywords separated by commas">
            <button type="button" onclick="suggestKeywords()">Suggest Keywords</button>
            
            <label for="meta_description">Meta Description (for SEO)</label>
            <textarea id="meta_description" name="meta_description" rows="3" placeholder="Enter a short description (150-160 characters)"></textarea>
            
            <div id="seo-score">SEO Score: 0%</div>
            <div id="seo-suggestions"></div>
            
            <button type="submit" class="btn">Publish</button>
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