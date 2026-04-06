<?php
session_start();

require_once 'db.php';
// $conn is already established in db.php

$error_msg = null;
$success_msg = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $name = trim($_POST['name']);
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $phone = trim($_POST['phone_number']);

    if ($name === '' || $username === '' || $password === '' || $phone === '') {
        $error_msg = 'กรุณากรอกข้อมูลให้ครบทุกช่อง';
    } else {
        $username_safe = $conn->real_escape_string($username);
        $phone_safe = $conn->real_escape_string($phone);

        $result = $conn->query("SELECT id, username, phone_number FROM user WHERE username = '$username_safe' OR phone_number = '$phone_safe'");

        if ($result && $result->num_rows > 0) {
            $existing = $result->fetch_assoc();
            if ($existing['username'] === $username_safe) {
                $error_msg = 'ชื่อผู้ใช้นี้ถูกใช้งานแล้ว โปรดเปลี่ยนชื่อผู้ใช้ใหม่';
            } elseif ($existing['phone_number'] === $phone_safe) {
                $error_msg = 'เบอร์โทรนี้ถูกใช้งานแล้ว โปรดใช้เบอร์อื่น';
            } else {
                $error_msg = 'ชื่อผู้ใช้หรือเบอร์โทรนี้ถูกใช้งานแล้ว';
            }
        } else {
            $name_safe = $conn->real_escape_string($name);
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $rule = 'user';
            $rule_safe = $conn->real_escape_string($rule);

            $lat_safe = isset($_POST['lat']) ? $conn->real_escape_string(trim($_POST['lat'])) : '';
            $lng_safe = isset($_POST['lng']) ? $conn->real_escape_string(trim($_POST['lng'])) : '';
            $address_safe = isset($_POST['address_details']) ? $conn->real_escape_string(trim($_POST['address_details'])) : '';

            // บันทึกข้อมูลโดยตั้งค่า is_verified = 1 ทันที
            $insert = "INSERT INTO user (name, username, password, phone_number, rule, money, is_verified, lat, lng, address_details) 
                       VALUES ('$name_safe', '$username_safe', '$password_hash', '$phone_safe', '$rule_safe', 0, 1, '$lat_safe', '$lng_safe', '$address_safe')";

            if ($conn->query($insert)) {
                header('Location: login.php?registered=1');
                exit();
            } else {
                $error_msg = 'เกิดข้อผิดพลาดในการสมัครสมาชิก: ' . $conn->error;
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
    <title>สมัครสมาชิก | MESA SAMP SHOP</title>
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        * {
            box-sizing: border-box;
        }

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
            body {
                padding: 16px;
            }

            .auth-card {
                padding: 24px;
            }

            .auth-header h1 {
                font-size: 1.8rem;
            }
        }

        /* Map Styles */
        #map-container {
            width: 100%;
            height: 250px;
            border-radius: 16px;
            margin-bottom: 12px;
            border: 1px solid var(--border-light);
        }

        .map-note {
            font-size: 0.85rem;
            color: var(--muted);
            margin-bottom: 18px;
            display: block;
        }
    </style>
</head>

<body>
    <button class="theme-toggle" onclick="toggleTheme()" title="สลับโหมด มืด/สว่าง">🌙</button>

    <div class="auth-card">
        <div class="auth-header">
            <h1>🤝 สมัครสมาชิก</h1>
            <p>ลงทะเบียนเข้าใช้งาน MESA SAMP SHOP แหล่งรวมสคริปต์ SAMP อันดับ 1
                <strong></strong>
            </p>
        </div>

        <?php if ($error_msg): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error_msg); ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="name">👤 ชื่อ-นามสกุล</label>
                <input type="text" id="name" name="name" placeholder="ชื่อของคุณ" required>
            </div>
            <div class="form-group">
                <label for="username">🔑 ชื่อผู้ใช้</label>
                <input type="text" id="username" name="username" placeholder="ชื่อผู้ใช้" required>
            </div>
            <div class="form-group">
                <label for="password">🔒 รหัสผ่าน</label>
                <input type="password" id="password" name="password" placeholder="รหัสผ่าน" required>
            </div>
            <div class="form-group">
                <label for="phone_number">📱 เบอร์โทร (ใช้สำหรับกู้คืนรหัส)</label>
                <input type="text" id="phone_number" name="phone_number" placeholder="0812345678" required>
            </div>



            <button type="submit" name="register" class="btn-submit">🚀 สมัครสมาชิก</button>
        </form>

        <div class="auth-footer">
            มีบัญชีแล้ว? <a href="login.php">เข้าสู่ระบบ</a>
        </div>
    </div>

    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        // Init Map center of Thailand
        var map = L.map('map-container').setView([13.7563, 100.5018], 6);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '© OpenStreetMap'
        }).addTo(map);

        var marker = null;

        // Try to get user current location using Geolocation API
        if ("geolocation" in navigator) {
            navigator.geolocation.getCurrentPosition(function (position) {
                var lat = position.coords.latitude;
                var lng = position.coords.longitude;
                map.setView([lat, lng], 15);
                placeMarker(lat, lng);
            });
        }

        map.on('click', function (e) {
            placeMarker(e.latlng.lat, e.latlng.lng);
        });

        function placeMarker(lat, lng) {
            if (marker) {
                map.removeLayer(marker);
            }
            marker = L.marker([lat, lng]).addTo(map);
            document.getElementById('lat').value = lat;
            document.getElementById('lng').value = lng;

            // ดึงชื่อสถานที่ออโต้
            fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`)
                .then(response => response.json())
                .then(data => {
                    if (data && data.display_name) {
                        document.getElementById('address_details').value = data.display_name;
                    }
                })
                .catch(err => console.log("Geocoding Error:", err));
        }

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