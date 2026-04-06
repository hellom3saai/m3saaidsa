<?php
session_start();

if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

require_once 'db.php';
// $conn is already established in db.php

$uname = $conn->real_escape_string($_SESSION['username']);

// ดึงรายการที่ซื้อ (Join ระหว่าง inventory กับ product)
$query = "
    SELECT p.id, p.name_product, p.picture_product, p.Category, p.link_download, i.purchased_at 
    FROM inventory i 
    JOIN product p ON i.product_id = p.id 
    WHERE i.username = '$uname' 
    ORDER BY i.purchased_at DESC
";
$inv_list = $conn->query($query);

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>คลังระบบของคุณ | MESA SAMP SHOP</title>
    <style>
        :root {
            --bg: #FDFBF7;
            --panel: #FFFFFF;
            --panel-soft: rgba(160, 120, 230, 0.05);
            --text: #332F37;
            --muted: #8E8A95;
            --accent: #9A7BDE;
            --accent-2: #B39CDD;
            --danger: #FF7070;
            --shadow: 0 12px 30px rgba(154, 123, 222, 0.08);
            --border-light: #EBE5F2;
        }

        /* Dark Theme Variables */
        body.dark-theme {
            --bg: #0D0B1A;
            --panel: #16132B;
            --panel-soft: rgba(154, 123, 222, 0.05);
            --text: #E2DAF0;
            --muted: #A6A0B3;
            --accent: #B39CDD;
            --accent-2: #D1C4E9;
            --danger: #FF8585;
            --shadow: 0 12px 40px rgba(0, 0, 0, 0.4);
            --border-light: rgba(154, 123, 222, 0.15);
        }

        body {
            margin: 0;
            background: linear-gradient(to bottom, var(--bg), #FAF7F0) !important;
            color: var(--text);
            font-family: 'Inter', sans-serif;
            padding: 24px;
            transition: background 0.3s, color 0.3s;
        }

        .theme-toggle {
            background: var(--panel);
            border: 1px solid var(--border-light);
            color: var(--text);
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 1.2rem;
            transition: all 0.2s;
            box-shadow: var(--shadow);
            margin-right: 12px;
        }
        .theme-toggle:hover {
            border-color: var(--accent);
            transform: translateY(-2px);
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
        }

        .site-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: var(--panel);
            border: 1px solid var(--border-light);
            border-radius: 24px;
            padding: 24px 32px;
            box-shadow: var(--shadow);
            margin-bottom: 30px;
        }

        .site-header h1 {
            margin: 0;
            font-size: 2rem;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .back-btn {
            background: var(--panel-soft);
            color: var(--accent);
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 12px;
            border: 1px solid var(--border-light);
            font-weight: 600;
            transition: all 0.2s;
        }

        .back-btn:hover {
            background: var(--accent);
            color: white;
            border-color: var(--accent);
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 24px;
        }

        .card {
            background: var(--panel);
            border: 1px solid var(--border-light);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: var(--shadow);
            transition: transform 0.2s;
            display: flex;
            flex-direction: column;
        }

        .card:hover {
            transform: translateY(-5px);
            border-color: rgba(154, 123, 222, 0.3);
        }

        .card-image {
            width: 100%;
            height: 180px;
            background: #F4F1F8;
            position: relative;
        }

        .card-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .card-content {
            padding: 20px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .badge {
            display: inline-block;
            background: rgba(154, 123, 222, 0.1);
            color: var(--accent);
            padding: 4px 10px;
            border-radius: 8px;
            font-size: 0.8rem;
            font-weight: bold;
            margin-bottom: 10px;
            align-self: flex-start;
        }

        .card-title {
            font-size: 1.2rem;
            font-weight: bold;
            margin-bottom: 8px;
        }

        .purchased-date {
            font-size: 0.85rem;
            color: var(--muted);
            margin-bottom: 20px;
            flex: 1;
        }

        .btn-download {
            display: block;
            text-align: center;
            background: linear-gradient(135deg, var(--accent) 0%, var(--accent-2) 100%);
            color: white;
            padding: 12px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: bold;
            transition: box-shadow 0.2s, transform 0.2s;
        }

        .btn-download:hover {
            box-shadow: 0 8px 20px rgba(154, 123, 222, 0.3);
            transform: translateY(-2px);
        }
        
        .btn-download.disabled {
            background: #EBE5F2;
            color: #A09DAC;
            pointer-events: none;
            box-shadow: none;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: var(--panel);
            border-radius: 20px;
            border: 1px dashed var(--border-light);
            grid-column: 1 / -1;
        }

        .empty-state h3 {
            color: var(--text);
            margin-bottom: 10px;
        }
        
        .empty-state p {
            color: var(--muted);
        }

        @media (max-width: 600px) {
            body { padding: 16px; }
            .site-header { flex-direction: column; text-align: center; gap: 16px; padding: 20px; }
            .site-header h1 { font-size: 1.5rem; justify-content: center; }
            .grid { grid-template-columns: 1fr; }
        }

    </style>
</head>
<body>
    <div class="container">
        <div class="site-header">
            <h1>📦 คลังระบบและสคริปต์ (My Systems)</h1>
            <div style="display: flex; align-items: center;">
                <button class="theme-toggle" onclick="toggleTheme()" title="สลับโหมด มืด/สว่าง">🌙</button>
                <a href="index.php" class="back-btn">← กลับหน้าร้าน</a>
            </div>
        </div>

        <div class="grid">
            <?php if ($inv_list && $inv_list->num_rows > 0): ?>
                <?php while ($row = $inv_list->fetch_assoc()): 
                    // ใช้รูปแรกถ้ารูปเป็นแบบใส่หลายลิงก์
                    $images = array_filter(array_map('trim', explode(',', $row['picture_product'])));
                    $thumb = !empty($images) ? $images[0] : 'https://via.placeholder.com/280x180?text=No+Image';
                    $dl_link = trim($row['link_download']);
                ?>
                    <div class="card">
                        <div class="card-image">
                            <img src="<?php echo htmlspecialchars($thumb); ?>" alt="Product">
                        </div>
                        <div class="card-content">
                            <div class="badge"><?php echo htmlspecialchars($row['Category'] ?: 'Item'); ?></div>
                            <div class="card-title"><?php echo htmlspecialchars($row['name_product']); ?></div>
                            <div class="purchased-date">ซื้อเมื่อ: <?php echo date('d/m/Y H:i', strtotime($row['purchased_at'])); ?></div>
                            
                            <?php if (!empty($dl_link)): ?>
                                <a href="<?php echo htmlspecialchars($dl_link); ?>" target="_blank" class="btn-download">⬇️ ดาวน์โหลดไฟล์ระบบ</a>
                            <?php else: ?>
                                <a href="#" class="btn-download disabled">⚠️ ยังไม่มีลิงก์ดาวน์โหลด</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state">
                    <h3>คลังของคุณว่างเปล่า 🕸️</h3>
                    <p>คุณยังไม่ได้ซื้อระบบหรือสคริปต์ใดๆ เลย ลองไปเลือกซื้อผ่านหน้าร้านค้าดูสิ!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <script>
        // Theme Toggle Logic
        function toggleTheme() {
            const body = document.body;
            const btn = document.querySelector('.theme-toggle');
            body.classList.toggle('dark-theme');
            
            const isDark = body.classList.contains('dark-theme');
            localStorage.setItem('theme', isDark ? 'dark' : 'light');
            if (btn) btn.innerHTML = isDark ? '☀️' : '🌙';
        }

        // Load theme on startup
        document.addEventListener('DOMContentLoaded', () => {
            const savedTheme = localStorage.getItem('theme');
            const btn = document.querySelector('.theme-toggle');
            if (savedTheme === 'dark') {
                document.body.classList.add('dark-theme');
                if (btn) btn.innerHTML = '☀️';
            }
        });
    </script>
</body>
</html>
