<?php
session_start();

if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

require_once 'db.php';
// $conn is already established in db.php

$uname = $conn->real_escape_string($_SESSION['username']);

// อัปเดตข้อมูลเมื่อมีการกด Submit
$success_msg = null;
$error_msg = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $lat_safe = isset($_POST['lat']) ? $conn->real_escape_string(trim($_POST['lat'])) : '';
    $lng_safe = isset($_POST['lng']) ? $conn->real_escape_string(trim($_POST['lng'])) : '';
    $address_safe = isset($_POST['address_details']) ? $conn->real_escape_string(trim($_POST['address_details'])) : '';
    $phone_safe = isset($_POST['phone_number']) ? $conn->real_escape_string(trim($_POST['phone_number'])) : '';
    
    $update = "UPDATE user SET lat='$lat_safe', lng='$lng_safe', address_details='$address_safe', phone_number='$phone_safe' WHERE username='$uname'";
    if ($conn->query($update)) {
        $success_msg = "อัปเดตข้อมูลส่วนตัวสำเร็จ!";
    } else {
        $error_msg = "เกิดข้อผิดพลาด: " . $conn->error;
    }
}

// Fetch user data
$query = "SELECT * FROM user WHERE username = '$uname' LIMIT 1";
$result = $conn->query($query);
$user_data = $result->fetch_assoc();

$lat = !empty($user_data['lat']) ? $user_data['lat'] : '13.7563'; // Default center
$lng = !empty($user_data['lng']) ? $user_data['lng'] : '100.5018';
$has_location = !empty($user_data['lat']) && !empty($user_data['lng']);

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ข้อมูลส่วนตัวและการตั้งค่า | MESA SAMP SHOP</title>
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
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
            --success: #51d2b7;
            --shadow: 0 12px 30px rgba(154, 123, 222, 0.08);
            --border-light: #EBE5F2;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            background: linear-gradient(to bottom, #FDFBF7, #FAF7F0);
            color: var(--text);
            font-family: 'Inter', sans-serif;
            padding: 24px;
        }

        .container {
            max-width: 800px;
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
            font-size: 1.8rem;
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

        .profile-card {
            background: var(--panel);
            border: 1px solid var(--border-light);
            border-radius: 20px;
            padding: 30px;
            box-shadow: var(--shadow);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--text);
            font-weight: 600;
        }

        .form-group input[type="text"] {
            width: 100%;
            padding: 14px 16px;
            border-radius: 12px;
            border: 1px solid var(--border-light);
            background: #FDFBF7;
            font-size: 1rem;
            color: var(--text);
            transition: all 0.2s;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--accent);
            background: #fff;
        }

        #map-container {
            width: 100%;
            height: 350px;
            border-radius: 16px;
            border: 1px solid var(--border-light);
            margin-bottom: 10px;
        }

        .map-note {
            font-size: 0.85rem;
            color: var(--muted);
            margin-top: 5px;
            display: block;
        }

        .btn-submit {
            display: inline-block;
            width: 100%;
            background: linear-gradient(135deg, var(--accent) 0%, var(--accent-2) 100%);
            color: white;
            border: none;
            padding: 16px;
            border-radius: 16px;
            font-size: 1.1rem;
            font-weight: bold;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            margin-top: 10px;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(154, 123, 222, 0.3);
        }
        
        .alert {
            padding: 14px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-weight: 600;
        }

        .alert-success {
            background: rgba(81, 210, 183, 0.15);
            color: #2b8b74;
            border: 1px solid rgba(81, 210, 183, 0.4);
        }

        .alert-error {
            background: rgba(255, 112, 112, 0.15);
            color: #d63939;
            border: 1px solid rgba(255, 112, 112, 0.3);
        }

        @media (max-width: 600px) {
            body { padding: 16px; }
            .site-header { flex-direction: column; text-align: center; gap: 16px; padding: 20px; }
            .profile-card { padding: 20px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="site-header">
            <h1>📍 ข้อมูลส่วนตัวและการตั้งค่า</h1>
            <a href="index.php" class="back-btn">← กลับหน้าร้าน</a>
        </div>

        <?php if ($success_msg): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success_msg); ?></div>
        <?php endif; ?>
        <?php if ($error_msg): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error_msg); ?></div>
        <?php endif; ?>

        <div class="profile-card">
            <form method="POST">
                <div class="form-group">
                    <label>ชื่อผู้ใช้งาน (Username)</label>
                    <input type="text" value="<?php echo htmlspecialchars($user_data['username']); ?>" disabled style="background: #EBE5F2; cursor: not-allowed;">
                    <span class="map-note">ไม่สามารถแก้ไขชื่อผู้ใช้ได้</span>
                </div>
                
                <div class="form-group">
                    <label>เบอร์โทรศัพท์ (สำหรับติดต่อ)</label>
                    <input type="text" name="phone_number" value="<?php echo htmlspecialchars($user_data['phone_number']); ?>" required>
                </div>

                <div class="form-group">
                    <label>📍 แผนที่ปักหมุดบ้าน / จุดจัดส่ง</label>
                    <div id="map-container"></div>
                    <span class="map-note">เลื่อนแผนที่และคลิกเพื่อระบุตำแหน่งของคุณ (ถ้ามี)</span>
                    
                    <input type="hidden" id="lat" name="lat" value="<?php echo htmlspecialchars($user_data['lat']); ?>">
                    <input type="hidden" id="lng" name="lng" value="<?php echo htmlspecialchars($user_data['lng']); ?>">
                </div>

                <div class="form-group">
                    <label>รายละเอียดที่อยู่ (บ้านเลขที่, ซอย, จุดสังเกต)</label>
                    <input type="text" name="address_details" value="<?php echo htmlspecialchars($user_data['address_details'] ?? ''); ?>" placeholder="เช่น บ้านสีเขียว หน้าปากซอยมีร้านสะดวกซื้อ" required>
                </div>

                <button type="submit" name="update_profile" class="btn-submit">💾 บันทึกข้อมูล</button>
            </form>
        </div>
    </div>

    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        var initialLat = <?php echo floatval($lat); ?>;
        var initialLng = <?php echo floatval($lng); ?>;
        var hasLocation = <?php echo $has_location ? 'true' : 'false'; ?>;

        var map = L.map('map-container').setView([initialLat, initialLng], hasLocation ? 16 : 6);
        
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '© OpenStreetMap'
        }).addTo(map);

        var marker = null;

        // ถ้าเคยมีพิกัด ให้ปักหมุดรอไว้เลย
        if (hasLocation) {
            placeMarker(initialLat, initialLng);
        } else if ("geolocation" in navigator) {
            // ถ้ายังไม่มีพิกัด ขอพิกัด GPS ปัจจุบัน
            navigator.geolocation.getCurrentPosition(function(position) {
                var cLat = position.coords.latitude;
                var cLng = position.coords.longitude;
                map.setView([cLat, cLng], 15);
                placeMarker(cLat, cLng);
            });
        }

        map.on('click', function(e) {
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
                    if(data && data.display_name) {
                        document.querySelector('input[name="address_details"]').value = data.display_name;
                    }
                })
                .catch(err => console.log("Geocoding Error:", err));
        }
    </script>
</body>
</html>
