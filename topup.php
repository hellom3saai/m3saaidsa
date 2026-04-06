<?php
session_start();
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

require_once 'db.php';
// $conn is already established in db.php

// Table initialization is already handled in db.php

$error_msg = null;
$success_msg = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['topup_slip'])) {
    $amount = (float)$_POST['amount'];
    
    if ($amount <= 0) {
        $error_msg = "กรุณาระบุจำนวนเงินให้ถูกต้อง";
    } elseif (!isset($_FILES['slip']) || $_FILES['slip']['error'] !== UPLOAD_ERR_OK) {
        $error_msg = "กรุณาแนบรูปภาพสลิปที่ถูกต้อง";
    } else {
        $file_tmp = $_FILES['slip']['tmp_name'];
        $file_name = $_FILES['slip']['name'];
        $file_size = $_FILES['slip']['size'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        $allowed_exts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (!in_array($file_ext, $allowed_exts)) {
            $error_msg = "รองรับเฉพาะไฟล์รูปภาพ (jpg, png, gif, webp) เท่านั้น";
        } elseif ($file_size > 5 * 1024 * 1024) { // 5MB limit
            $error_msg = "ขนาดไฟล์ต้องไม่เกิน 5MB";
        } else {
            // สร้างโฟลเดอร์ถ้าไม่มี
            if (!is_dir('uploads')) {
                mkdir('uploads', 0755, true);
            }
            
            $new_file_name = 'slip_' . uniqid() . '.' . $file_ext;
            $destination = 'uploads/' . $new_file_name;
            
            if (move_uploaded_file($file_tmp, $destination)) {
                $uname = $conn->real_escape_string($_SESSION['username']);
                $slip_path_safe = $conn->real_escape_string($destination);
                
                $insert = $conn->query("INSERT INTO slip_topup (username, amount, slip_image, status) VALUES ('$uname', $amount, '$slip_path_safe', 'pending')");
                if ($insert) {
                    $success_msg = "ส่งข้อมูลสลิปเรียบร้อยแล้ว กรุณารอแอดมินตรวจสอบครับ";
                } else {
                    $error_msg = "เกิดข้อผิดพลาดในการบันทึกข้อมูล";
                }
            } else {
                $error_msg = "ไม่สามารถอัปโหลดไฟล์สลิปได้";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เติมเงินเข้าระบบ | MESA SAMP SHOP</title>
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
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Inter', sans-serif;
            background: radial-gradient(circle at top right, rgba(154,123,222,0.12), transparent 35%),
                        linear-gradient(to bottom, var(--bg), #FAF7F0) !important;
            color: var(--text);
            padding: 24px;
            transition: background 0.3s, color 0.3s;
        }

        .theme-toggle {
            position: fixed;
            top: 20px;
            right: 20px;
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
            z-index: 1000;
        }
        .theme-toggle:hover {
            border-color: var(--accent);
            transform: translateY(-2px);
        }

        .topup-card {
            width: 100%;
            max-width: 480px;
            background: var(--panel);
            border: 1px solid var(--border-light);
            border-radius: 28px;
            padding: 36px;
            box-shadow: var(--shadow);
        }

        .topup-header {
            text-align: center;
            margin-bottom: 24px;
        }

        .topup-header h1 {
            font-size: 2.2rem;
            margin-bottom: 10px;
            color: var(--text);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .topup-header p {
            color: var(--muted);
            line-height: 1.6;
            margin-bottom: 20px;
        }

        .qr-box {
            text-align: center;
            margin-bottom: 24px;
            background: var(--panel-soft);
            padding: 16px;
            border-radius: 16px;
            border: 1px solid rgba(154, 123, 222, 0.2);
        }

        .qr-box img {
            max-width: 250px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--muted);
            font-weight: 600;
        }

        .form-group input[type="number"], .form-group input[type="file"] {
            width: 100%;
            padding: 14px 16px;
            border-radius: 16px;
            border: 1px solid var(--border-light);
            background: #FAFAF7;
            color: var(--text);
            font-size: 1rem;
            box-sizing: border-box;
            transition: all 0.2s;
        }

        .form-group input[type="number"]:focus, .form-group input[type="file"]:focus {
            outline: none;
            background: #FFFFFF;
            border-color: var(--accent);
            box-shadow: 0 0 0 4px rgba(154, 123, 222, 0.15);
        }

        .btn-submit {
            width: 100%;
            padding: 16px;
            border: none;
            border-radius: 16px;
            font-size: 1.05rem;
            font-weight: 700;
            cursor: pointer;
            background: linear-gradient(135deg, var(--accent) 0%, var(--accent-2) 100%);
            color: #FFFFFF;
            box-shadow: 0 8px 24px rgba(154, 123, 222, 0.3);
            transition: transform 0.2s, box-shadow 0.2s;
            margin-top: 8px;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 32px rgba(154, 123, 222, 0.4);
        }

        .alert {
            margin-bottom: 20px;
            padding: 14px 18px;
            border-radius: 16px;
            font-weight: 600;
            text-align: center;
        }

        .alert-error {
            background: rgba(255, 112, 112, 0.1);
            color: var(--danger);
            border: 1px solid rgba(255, 112, 112, 0.3);
        }

        .alert-success {
            background: var(--panel);
            color: var(--accent);
            border: 1px solid var(--accent);
        }

        .back-link {
            display: block;
            text-align: center;
            margin-top: 24px;
            color: var(--muted);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.2s;
        }

        .back-link:hover {
            color: var(--accent);
        }

        @media (max-width: 600px) {
            body { padding: 16px; }
            .topup-card { padding: 24px; }
            .topup-header h1 { font-size: 1.8rem; }
        }
    </style>
</head>
<body>
    <button class="theme-toggle" onclick="toggleTheme()" title="สลับโหมด มืด/สว่าง">🌙</button>
    <div class="topup-card">
        <div class="topup-header">
            <h1>💎 เติมเงิน (โอนผ่านบัญชี)</h1>
            <p>สแกน QR Code ด้านล่างเพื่อโอนเงิน<br><strong>(1 บาท = 1 พอยท์)</strong></p>
        </div>

        <?php if ($error_msg): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error_msg); ?></div>
        <?php endif; ?>
        
        <?php if ($success_msg): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success_msg); ?></div>
        <?php endif; ?>

        <div class="qr-box">
            <!-- ดึงรูป qrcode.png มาโชว์ -->
            <img src="qrcode.png" alt="QR Code รับเงิน" onerror="this.src='https://via.placeholder.com/250x250?text=Please+Upload+qrcode.png';">
            <p style="margin-top: 12px; color: var(--accent); font-weight: 600; font-size: 0.9rem;">สแกนเพื่อโอนเงินผ่าน <strong>พร้อมเพย์ / TrueMoney</strong></p>
        </div>

        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="amount">💸 จำนวนเงินที่โอน (บาท)</label>
                <input type="number" id="amount" name="amount" placeholder="เช่น 50" min="1" step="0.5" required>
            </div>
            <div class="form-group">
                <label for="slip">📸 แนบรูปภาพสลิปที่โอนสำเร็จแล้ว</label>
                <input type="file" id="slip" name="slip" accept="image/*" required>
            </div>
            <button type="submit" name="topup_slip" class="btn-submit">🚀 แจ้งโอนเงิน</button>
        </form>

        <a href="index.php" class="back-link">← กลับหน้าร้านค้า</a>
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
