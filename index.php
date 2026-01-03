<?php
/**
 * Webpage Builder - A lightweight JSON-based Flat-File CMS.
 * Ideal for low-resource servers (1 Core CPU / 2GB RAM).
 */

$db_file = 'data.json';

// Initialize JSON database if it doesn't exist
if (!file_exists($db_file)) {
    file_put_contents($db_file, json_encode([], JSON_PRETTY_PRINT));
}

// Fetch data from storage
$raw_data = file_get_contents($db_file);
$all_pages = json_decode($raw_data, true) ?: [];

// --- VIEW LOGIC (Routing) ---
$slug = isset($_GET['page']) ? trim($_GET['page'], '/') : null;

if ($slug) {
    if (isset($all_pages[$slug])) {
        header("Content-Type: text/html; charset=UTF-8");
        echo $all_pages[$slug]['content'];
        exit;
    } else {
        // Fallback to custom 404 page if slug is not found
        http_response_code(404);
        if (file_exists('404.shtml')) {
            include('404.shtml');
        } else {
            echo "<h1 style='color:white; background:#000; padding:20px;'>404 - Content Gone or Never Existed.</h1>";
        }
        exit;
    }
}

// --- SAVE LOGIC (Create/Update) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['slug_input'])) {
    // Basic sanitization for the slug
    $s = preg_replace('/[^a-z0-9-]/', '', strtolower($_POST['slug_input']));
    
    if ($s) {
        $all_pages[$s] = [
            "title" => htmlspecialchars($_POST['title_input']),
            "content" => $_POST['content_input'], // Raw HTML stored here
            "updated" => date("d M Y, H:i")
        ];
        
        // Save back to JSON
        file_put_contents($db_file, json_encode($all_pages, JSON_PRETTY_PRINT));
        
        header("Location: index.php?status=published");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NGID Web Builder | Dashboard</title>
    <style>
        :root { --primary: #00d4ff; --bg: #0a0a0a; --card: #161616; --text: #d1d1d1; }
        body { font-family: 'Segoe UI', Tahoma, sans-serif; background: var(--bg); color: var(--text); margin: 0; padding: 20px; }
        .container { max-width: 1100px; margin: 0 auto; display: flex; gap: 20px; flex-wrap: wrap; }
        .editor-box { flex: 1.2; min-width: 350px; background: var(--card); padding: 25px; border-radius: 12px; border: 1px solid #333; }
        .list-box { flex: 0.8; min-width: 300px; background: var(--card); padding: 25px; border-radius: 12px; border: 1px solid #333; }
        h2 { margin-top: 0; color: var(--primary); border-bottom: 1px solid #333; padding-bottom: 10px; font-size: 1.5rem; }
        input, textarea { width: 100%; padding: 12px; margin: 10px 0; background: #000; border: 1px solid #444; color: #00ff00; border-radius: 6px; box-sizing: border-box; font-family: 'Consolas', monospace; }
        textarea { height: 350px; resize: vertical; }
        button { background: #007bff; color: white; border: none; padding: 15px; border-radius: 6px; cursor: pointer; font-weight: bold; width: 100%; transition: 0.3s; }
        button:hover { background: #0056b3; }
        .card { background: #222; padding: 15px; border-radius: 8px; margin-bottom: 12px; border-left: 5px solid var(--primary); position: relative; }
        .card strong { display: block; color: #fff; margin-bottom: 5px; }
        .card a { color: var(--primary); text-decoration: none; font-size: 14px; }
        .card a:hover { text-decoration: underline; }
        .card .time { font-size: 10px; color: #666; margin-top: 8px; }
        .status-msg { background: #1b5e20; color: #fff; padding: 10px; border-radius: 6px; margin-bottom: 15px; font-size: 14px; }
    </style>
</head>
<body>

<div class="container">
    <div class="editor-box">
        <h2>Web Builder Engine</h2>
        <?php if(isset($_GET['status'])): ?>
            <div class="status-msg">✔ Page successfully published!</div>
        <?php endif; ?>
        <form method="POST">
            <input type="text" name="title_input" placeholder="Page Title" required>
            <input type="text" name="slug_input" placeholder="URL Slug (e.g., promo-2025)" required>
            <textarea name="content_input" placeholder="Paste your HTML/CSS/JS code here..." required></textarea>
            <button type="submit">Publish Changes</button>
        </form>
    </div>

    <div class="list-box">
        <h2>Live Pages</h2>
        <div style="font-size: 12px; color: #666; margin-bottom: 15px;">Inventory: <?= count($all_pages) ?> Published Items</div>
        <?php if (empty($all_pages)): ?>
            <p style="color:#444">Database is empty. Start building!</p>
        <?php else: ?>
            <?php 
            $display_list = array_reverse($all_pages, true);
            foreach ($display_list as $s => $p): ?>
                <div class="card">
                    <strong><?= htmlspecialchars($p['title']) ?></strong>
                    <a href="./<?= $s ?>" target="_blank">view page: /<?= $s ?></a>
                    <div class="time">Last Update: <?= $p['updated'] ?></div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
