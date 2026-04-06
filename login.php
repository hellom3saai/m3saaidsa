<?php
session_start();

require_once 'db.php';
// $conn is already established in db.php

$error_msg = null;
$success_msg = null;

if (isset($_GET['registered'])) {
    $success_msg = 'สมัครสมาชิกสำเร็จแล้ว กรุณาเข้าสู่ระบบ';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if ($username === '' || $password === '') {
        $error_msg = 'กรุณากรอกชื่อผู้ใช้และรหัสผ่าน';
    } else {
        $username_safe = $conn->real_escape_string($username);
        $result = $conn->query("SELECT * FROM user WHERE username = '$username_safe' LIMIT 1");

        if ($result && $result->num_rows === 1) {
            $user = $result->fetch_assoc();
            $stored = $user['password'];
            $isValid = password_verify($password, $stored) || $password === $stored;

            if ($isValid) {
                $_SESSION['id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['rule'] = $user['rule'];
                header('Location: index.php');
                exit();
            } else {
                $error_msg = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
            }
        } else {
            $error_msg = 'ไม่พบชื่อผู้ใช้นี้ในระบบ';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ | MESA SAMP SHOP</title>
    <style>
        * { box-sizing: border-box; }
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
            background: radial-gradient(circle at top left, rgba(154, 123, 222, 0.12), transparent 35%),
                        linear-gradient(to bottom, var(--bg), #FAF7F0) !important;
            color: var(--text);
            padding: 24px;
            transition: background 0.3s, color 0.3s;
        }

        .auth-card {
            width: 100%;
            max-width: 520px;
            background: var(--panel);
            border: 1px solid var(--border-light);
            border-radius: 28px;
            padding: 36px;
            box-shadow: var(--shadow);
            z-index: 5;
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

        .auth-header {
            margin-bottom: 24px;
            text-align: left;
        }

        .auth-header h1 {
            font-size: 2.2rem;
            margin-bottom: 8px;
            color: var(--text);
        }

        .auth-header p {
            color: var(--muted);
            line-height: 1.6;
            margin: 0;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--muted);
            font-weight: 600;
            font-size: 0.95rem;
        }

        .form-group input {
            width: 100%;
            padding: 14px 16px;
            border-radius: 16px;
            border: 1px solid var(--border-light);
            background: #FAFAF7;
            color: var(--text);
            font-size: 1rem;
            transition: all 0.2s;
        }

        .form-group input:focus {
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
            margin-bottom: 18px;
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

        .auth-footer {
            margin-top: 20px;
            text-align: center;
            color: var(--muted);
        }

        .auth-footer a {
            color: var(--accent);
            text-decoration: none;
            font-weight: 700;
        }

        @media (max-width: 600px) {
            body { padding: 16px; }
            .auth-card { padding: 24px; }
            .auth-header h1 { font-size: 1.8rem; }
        }
    </style>
</head>
<body>
    <button class="theme-toggle" onclick="toggleTheme()" title="สลับโหมด มืด/สว่าง">🌙</button>

    <div class="auth-card">
        <div class="auth-header">
            <h1>🔐 เข้าสู่ระบบ</h1>
            <p>เพื่อจัดการสคริปต์และพอยท์ส่วนตัวของคุณ</p>
        </div>

        <?php if ($error_msg): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error_msg); ?></div>
        <?php endif; ?>

        <?php if ($success_msg): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success_msg); ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="username">🔑 ชื่อผู้ใช้</label>
                <input type="text" id="username" name="username" placeholder="ชื่อผู้ใช้" required>
            </div>
            <div class="form-group">
                <label for="password">🔒 รหัสผ่าน</label>
                <input type="password" id="password" name="password" placeholder="รหัสผ่าน" required>
            </div>
            <button type="submit" name="login" class="btn-submit">🚀 เข้าสู่ระบบ</button>
        </form>

        <div class="auth-footer">
            ยังไม่มีบัญชี? <a href="regis.php">สมัครสมาชิก</a>
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