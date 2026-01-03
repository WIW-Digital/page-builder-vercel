<?php
/**
 * Web Builder - Vercel Edition (Serverless)
 * Using MockAPI as data.json alternative
 */

$mockapi_url = "https://xxxxxx.mockapi.io/pages"; // <--- USE YOUR OWN MOCKAPI

// --- FETCH & CONVERT DATA (Logic Mapping) ---
function get_all_pages($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);
    curl_close($ch);
    
    $raw_array = json_decode($response, true) ?: [];
    
    $mapped = [];
    foreach($raw_array as $item) {
        // MockAPI pake 'id', tapi kita butuh 'slug' buat routing
        if (isset($item['slug'])) {
            $mapped[$item['slug']] = $item;
        }
    }
    return $mapped;
}

$all_pages = get_all_pages($mockapi_url);

// --- VIEW LOGIC (Routing) ---
$slug = isset($_GET['page']) ? trim($_GET['page'], '/') : null;

if ($slug) {
    if (isset($all_pages[$slug])) {
        header("Content-Type: text/html; charset=UTF-8");
        echo $all_pages[$slug]['content'];
        exit;
    } else {
        http_response_code(404);
        echo "<h1 style='color:white; background:#000; padding:20px;'>404 - Slug /$slug Gak Ditemukan!</h1>";
        exit;
    }
}

// --- SAVE LOGIC ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['slug_input'])) {
    $s = preg_replace('/[^a-z0-9-]/', '', strtolower($_POST['slug_input']));
    
    if ($s) {
        $payload = [
            "slug" => $s,
            "title" => htmlspecialchars($_POST['title_input']),
            "content" => $_POST['content_input'],
            "updated" => date("d M Y, H:i")
        ];

        $ch = curl_init($mockapi_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_exec($ch);
        curl_close($ch);
        
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
    <title>Web Builder</title>
    <style>
        :root { --primary: #00d4ff; --bg: #0a0a0a; --card: #161616; --text: #d1d1d1; }
        body { font-family: 'Segoe UI', sans-serif; background: var(--bg); color: var(--text); padding: 20px; }
        .container { max-width: 1100px; margin: 0 auto; display: flex; gap: 20px; flex-wrap: wrap; }
        .editor-box { flex: 1.2; background: var(--card); padding: 25px; border-radius: 12px; border: 1px solid #333; }
        .list-box { flex: 0.8; background: var(--card); padding: 25px; border-radius: 12px; border: 1px solid #333; }
        h2 { color: var(--primary); border-bottom: 1px solid #333; padding-bottom: 10px; }
        input, textarea { width: 100%; padding: 12px; margin: 10px 0; background: #000; border: 1px solid #444; color: #00ff00; border-radius: 6px; box-sizing: border-box; }
        textarea { height: 300px; font-family: monospace; }
        button { background: #007bff; color: white; border: none; padding: 15px; border-radius: 6px; cursor: pointer; font-weight: bold; width: 100%; }
        .card { background: #222; padding: 15px; border-radius: 8px; margin-bottom: 12px; border-left: 5px solid var(--primary); }
        .card a { color: var(--primary); text-decoration: none; font-size: 14px; }
        .status-msg { background: #1b5e20; color: #fff; padding: 10px; border-radius: 6px; margin-bottom: 15px; }
    </style>
</head>
<body>
<div class="container">
    <div class="editor-box">
        <h2>Web Builder</h2>
        <?php if(isset($_GET['status'])): ?>
            <div class="status-msg">✔ Page Published!</div>
        <?php endif; ?>
        <form method="POST">
            <input type="text" name="title_input" placeholder="Page Title" required>
            <input type="text" name="slug_input" placeholder="URL Slug (e.g., promo-ngid)" required>
            <textarea name="content_input" placeholder="Paste HTML/CSS Code..." required></textarea>
            <button type="submit">Publish to Cloud</button>
        </form>
    </div>
    <div class="list-box">
        <h2>Live Pages</h2>
        <div style="font-size: 12px; color: #666; margin-bottom: 15px;">Total: <?= count($all_pages) ?></div>
        <?php foreach (array_reverse($all_pages, true) as $slug_key => $p): ?>
            <div class="card">
                <strong><?= htmlspecialchars($p['title']) ?></strong><br>
                <a href="index.php?page=<?= $slug_key ?>" target="_blank">view: /<?= $slug_key ?></a>
            </div>
        <?php endforeach; ?>
    </div>
</div>
</body>
</html>

</body>
</html>
