<?php
session_start();

require_once 'db.php';
// $conn is already established in db.php

// Check if user is admin
$isAdmin = isset($_SESSION['rule']) && $_SESSION['rule'] === 'admin';
if (!$isAdmin) {
    header('Location: index.php');
    exit();
}

// Handle change role
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_role'])) {
    $user_id = (int)$_POST['user_id'];
    $new_role = trim($_POST['new_role']);
    
    if (in_array($new_role, ['user', 'admin', 'customer'])) {
        $new_role_safe = $conn->real_escape_string($new_role);
        $update_query = "UPDATE user SET rule='$new_role_safe' WHERE id = $user_id AND id != " . (int)$_SESSION['id'];
        if ($conn->query($update_query)) {
            $success_msg = "เปลี่ยนสิทธิ์ผู้ใช้สำเร็จ!";
        } else {
            $error_msg = "เกิดข้อผิดพลาดในการเปลี่ยนสิทธิ์";
        }
    }
}

// Handle delete user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $user_id = (int)$_POST['user_id'];
    
    if ($user_id != (int)$_SESSION['id']) {
        $delete_query = "DELETE FROM user WHERE id = $user_id";
        if ($conn->query($delete_query)) {
            $success_msg = "ลบผู้ใช้สำเร็จ!";
        } else {
            $error_msg = "เกิดข้อผิดพลาดในการลบผู้ใช้";
        }
    } else {
        $error_msg = "ไม่สามารถลบบัญชีของตนเองได้";
    }
}

// Handle update user profile
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $user_id = (int)$_POST['user_id'];
    
    if ($user_id != (int)$_SESSION['id']) {
        $name = trim($_POST['name']);
        $username = trim($_POST['username']);
        $phone = trim($_POST['phone_number']);
        $new_role = trim($_POST['rule']);
        $money = (int)$_POST['money'];
        
        if (!empty($name) && !empty($username) && !empty($phone) && in_array($new_role, ['user', 'admin', 'customer'])) {
            $name_safe = $conn->real_escape_string($name);
            $username_safe = $conn->real_escape_string($username);
            $phone_safe = $conn->real_escape_string($phone);
            $new_role_safe = $conn->real_escape_string($new_role);
            
            // Check if username or phone already exists
            $check_query = "SELECT id FROM user WHERE (username = '$username_safe' OR phone_number = '$phone_safe') AND id != $user_id";
            $check_result = $conn->query($check_query);
            
            if ($check_result && $check_result->num_rows > 0) {
                $error_msg = "ชื่อผู้ใช้หรือเบอร์โทรนี้มีผู้ใช้งานแล้ว";
            } else {
                // Check if password is provided
                if (!empty($_POST['password'])) {
                    $password = trim($_POST['password']);
                    $password_hash = password_hash($password, PASSWORD_DEFAULT);
                    $update_query = "UPDATE user SET name='$name_safe', username='$username_safe', password='$password_hash', phone_number='$phone_safe', rule='$new_role_safe', money=$money WHERE id = $user_id";
                } else {
                    $update_query = "UPDATE user SET name='$name_safe', username='$username_safe', phone_number='$phone_safe', rule='$new_role_safe', money=$money WHERE id = $user_id";
                }
                
                if ($conn->query($update_query)) {
                    $success_msg = "แก้ไขข้อมูลผู้ใช้สำเร็จ!";
                } else {
                    $error_msg = "เกิดข้อผิดพลาดในการแก้ไขข้อมูล";
                }
            }
        } else {
            $error_msg = "กรุณากรอกข้อมูลให้ครบทั้งหมด";
        }
    } else {
        $error_msg = "ไม่สามารถแก้ไขข้อมูลบัญชีของตนเองได้";
    }
}

// Handle get seller name
if (isset($_GET['get_seller_name']) && isset($_GET['product_id'])) {
    $product_id = (int)$_GET['product_id'];
    // $conn is already global or required via db.php
    $seller_query = "SELECT u.name FROM user u JOIN product p ON u.id = p.seller_id WHERE p.id = $product_id LIMIT 1";
    $seller_result = $conn->query($seller_query);
    if ($seller_result && $seller_result->num_rows > 0) {
        $seller_row = $seller_result->fetch_assoc();
        echo htmlspecialchars($seller_row['name']);
    } else {
        echo 'ไม่ระบุ';
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ผู้ดูแลระบบ - จัดการสมาชิก | MESA SAMP SHOP</title>
    <style>
        :root {
            --bg: #071118;
            --panel: rgba(10, 23, 42, 0.92);
            --text: #e8f1ff;
            --muted: #9bb0d6;
            --accent: #4fd1ff;
            --accent-2: #51d2b7;
            --danger: #ff6b6b;
            --shadow: 0 25px 60px rgba(0, 0, 0, 0.35);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: radial-gradient(circle at top left, rgba(79, 209, 255, 0.18), transparent 25%),
                        radial-gradient(circle at bottom right, rgba(81, 210, 183, 0.18), transparent 18%),
                        linear-gradient(160deg, #040b14 0%, #081923 35%, #0b2033 100%);
            color: var(--text);
            min-height: 100vh;
            padding: 24px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 24px 28px;
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 24px;
            box-shadow: var(--shadow);
            backdrop-filter: blur(10px);
            margin-bottom: 24px;
            flex-wrap: wrap;
            gap: 20px;
        }

        .header h1 {
            font-size: 2rem;
            font-weight: 800;
        }

        .search-container {
            display: flex;
            gap: 12px;
            flex-grow: 1;
            max-width: 600px;
        }

        .search-form {
            display: flex;
            width: 100%;
            gap: 10px;
        }

        .search-input {
            flex-grow: 1;
            padding: 12px 20px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(79, 209, 255, 0.2);
            border-radius: 12px;
            color: #fff;
            outline: none;
            transition: all 0.3s;
        }

        .search-input:focus {
            border-color: var(--accent);
            background: rgba(255, 255, 255, 0.1);
            box-shadow: 0 0 15px rgba(79, 209, 255, 0.15);
        }

        .btn-search {
            padding: 12px 24px;
            background: linear-gradient(135deg, #4fd1ff 0%, #3ca8ff 100%);
            color: #040b14;
            border: none;
            border-radius: 12px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-search:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(79, 209, 255, 0.3);
        }

        .header-nav {
            display: flex;
            gap: 16px;
        }

        .nav-link {
            padding: 12px 20px;
            background: rgba(79, 209, 255, 0.12);
            color: var(--accent);
            text-decoration: none;
            border-radius: 10px;
            border: 1px solid rgba(79, 209, 255, 0.2);
            transition: all 0.2s ease;
            font-weight: 600;
        }

        .nav-link:hover {
            background: rgba(79, 209, 255, 0.2);
            border-color: rgba(79, 209, 255, 0.4);
        }

        .user-info {
            color: #cbd5e1;
            font-weight: 600;
        }

        .table-wrapper {
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 24px;
            overflow-x: auto;
            box-shadow: var(--shadow);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: rgba(0,0,0,0.2);
        }

        th {
            padding: 16px;
            text-align: left;
            color: #cbd5e1;
            font-weight: 700;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        td {
            padding: 18px 16px;
            border-bottom: 1px solid rgba(255,255,255,0.08);
            color: #e2e8f0;
        }

        tbody tr:hover {
            background: rgba(79, 209, 255, 0.08);
        }

        .role-badge {
            display: inline-flex;
            align-items: center;
            padding: 6px 12px;
            border-radius: 999px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .role-admin {
            background: rgba(255, 107, 107, 0.15);
            color: #ffccd5;
            border: 1px solid rgba(255, 107, 107, 0.3);
        }

        .role-customer {
            background: rgba(79, 209, 255, 0.15);
            color: #9ee7ff;
            border: 1px solid rgba(79, 209, 255, 0.3);
        }

        .role-user {
            background: rgba(81, 210, 183, 0.15);
            color: #9ee7ff;
            border: 1px solid rgba(81, 210, 183, 0.3);
        }

        .action-group {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .btn-small {
            padding: 8px 12px;
            border: none;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-edit {
            background: rgba(79, 209, 255, 0.2);
            color: var(--accent);
            border: 1px solid rgba(79, 209, 255, 0.3);
        }

        .btn-edit:hover {
            background: rgba(79, 209, 255, 0.3);
        }

        .btn-delete {
            background: rgba(255, 107, 107, 0.2);
            color: #ffccd5;
            border: 1px solid rgba(255, 107, 107, 0.3);
        }

        .btn-delete:hover {
            background: rgba(255, 107, 107, 0.3);
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(2, 10, 20, 0.78);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: rgba(5, 14, 28, 0.96);
            border: 1px solid rgba(79, 209, 255, 0.12);
            border-radius: 28px;
            padding: 36px;
            box-shadow: var(--shadow);
            max-width: 480px;
            width: 100%;
            animation: slideIn 0.25s ease;
        }

        .modal-header {
            font-size: 1.6rem;
            font-weight: 800;
            margin-bottom: 24px;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            margin-bottom: 10px;
            color: #cbd5e1;
            font-weight: 700;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 14px 16px;
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: 12px;
            font-size: 1rem;
            color: #e2e8f0;
            background: rgba(255,255,255,0.04);
            transition: border-color 0.25s ease;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: rgba(79, 209, 255, 0.5);
        }

        .form-buttons {
            display: flex;
            gap: 14px;
            margin-top: 24px;
        }

        .btn {
            flex: 1;
            padding: 14px 20px;
            border: none;
            border-radius: 12px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-submit {
            background: linear-gradient(135deg, #4fd1ff 0%, #51d2b7 100%);
            color: #071118;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
        }

        .btn-cancel {
            background: rgba(255,255,255,0.08);
            color: #cbd5e1;
            border: 1px solid rgba(255,255,255,0.14);
        }

        .btn-cancel:hover {
            background: rgba(255,255,255,0.12);
        }

        .alert {
            position: fixed;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%);
            padding: 16px 24px;
            border-radius: 14px;
            font-weight: 700;
            box-shadow: 0 18px 40px rgba(0,0,0,0.35);
            z-index: 999;
            animation: slideUp 0.3s ease;
            max-width: 90%;
        }

        .alert-success {
            background: rgba(45, 212, 191, 0.12);
            color: #9ee7ff;
            border: 1px solid rgba(79, 209, 255, 0.25);
        }

        .alert-error {
            background: rgba(255, 107, 107, 0.14);
            color: #ffccd5;
            border: 1px solid rgba(255, 107, 107, 0.3);
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateX(-50%) translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateX(-50%) translateY(0);
            }
        }

        @keyframes slideDown {
            from {
                opacity: 1;
                transform: translateX(-50%) translateY(0);
            }
            to {
                opacity: 0;
                transform: translateX(-50%) translateY(20px);
            }
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                text-align: center;
            }

            .action-group {
                flex-direction: column;
            }

            .btn-small {
                width: 100%;
            }

            table {
                font-size: 0.9rem;
            }

            th, td {
                padding: 12px 8px;
                white-space: nowrap;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <h1>👥 จัดการสมาชิกในระบบ</h1>
                <div class="user-info" style="margin-top: 8px;">ยินดีต้อนรับ, <?php echo htmlspecialchars($_SESSION['name']); ?></div>
            </div>

            <div class="search-container">
                <form action="user.php" method="GET" class="search-form">
                    <input type="text" name="search" class="search-input" placeholder="ค้นหาด้วย ชื่อ, ไอดี, เบอร์โทร หรือ ชื่อผู้ใช้..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                    <button type="submit" class="btn-search">ค้นหา</button>
                    <?php if (isset($_GET['search']) && !empty($_GET['search'])): ?>
                        <a href="user.php" class="nav-link" style="background: rgba(255, 107, 107, 0.15); color: #ff6b6b; border-color: rgba(255, 107, 107, 0.2);">ล้าง</a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="header-nav">
                <a class="nav-link" href="index.php">← กลับไปหน้าหลัก</a>
                <a class="nav-link" href="logout.php">ออกจากระบบ</a>
            </div>
        </div>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>ชื่อ</th>
                        <th>ชื่อผู้ใช้</th>
                        <th>เบอร์โทร</th>
                        <th>สิทธิ์</th>
                        <th>เงิน (฿)</th>
                        <th>การจัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                        $search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
                        $query = "SELECT id, name, username, phone_number, rule, COALESCE(money, 0) as money FROM user";
                        
                        if (!empty($search)) {
                            $query .= " WHERE (name LIKE '%$search%' 
                                        OR username LIKE '%$search%' 
                                        OR phone_number LIKE '%$search%' 
                                        OR id = '$search'
                                        OR rule LIKE '%$search%')";
                        }
                        
                        $query .= " ORDER BY id DESC";
                        $result = $conn->query($query);
                        
                        if ($result && $result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                $role_class = 'role-' . htmlspecialchars($row['rule']);
                                echo '<tr>';
                                echo '  <td>' . (int)$row['id'] . '</td>';
                                echo '  <td>' . htmlspecialchars($row['name']) . '</td>';
                                echo '  <td>' . htmlspecialchars($row['username']) . '</td>';
                                echo '  <td>' . htmlspecialchars($row['phone_number']) . '</td>';
                                echo '  <td><span class="role-badge ' . $role_class . '">' . htmlspecialchars($row['rule']) . '</span></td>';
                                echo '  <td>' . number_format((int)$row['money']) . '</td>';
                                echo '  <td>';
                                echo '    <div class="action-group">';
                                if ((int)$row['id'] !== (int)$_SESSION['id']) {
                                    $dataJson = htmlspecialchars(json_encode([$row['name'], $row['username'], $row['phone_number'], $row['rule'], (int)$row['money']], JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
                                    $nameJson = htmlspecialchars(json_encode($row['name'], JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
                                    echo '      <button class="btn-small btn-edit" data-id="' . (int)$row['id'] . '" data-info=\'' . $dataJson . '\'>แก้ไข</button>';
                                    echo '      <button class="btn-small btn-delete" data-id="' . (int)$row['id'] . '" data-name=\'' . $nameJson . '\'>ลบ</button>';
                                } else {
                                    echo '      <span style="color: #cbd5e1; font-size: 0.85rem;">(บัญชีของคุณ)</span>';
                                }
                                echo '    </div>';
                                echo '  </td>';
                                echo '</tr>';
                            }
                        } else {
                            echo '<tr><td colspan="7" style="text-align: center; color: #cbd5e1;">ไม่มีผู้ใช้งาน</td></tr>';
                        }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Edit User -->
    <div class="modal" id="editModal">
        <div class="modal-content">
            <div class="modal-header">แก้ไขผู้ใช้งาน</div>
            <form method="POST">
                <input type="hidden" name="user_id" id="edit_user_id">
                
                <div class="form-group">
                    <label for="edit_name">ชื่อ</label>
                    <input type="text" id="edit_name" name="name" required>
                </div>

                <div class="form-group">
                    <label for="edit_username">ชื่อผู้ใช้</label>
                    <input type="text" id="edit_username" name="username" required>
                </div>

                <div class="form-group">
                    <label for="edit_phone">เบอร์โทร</label>
                    <input type="text" id="edit_phone" name="phone_number" required>
                </div>

                <div class="form-group">
                    <label for="edit_password">รหัสผ่าน (ปล่อยว่างเพื่อไม่เปลี่ยน)</label>
                    <input type="password" id="edit_password" name="password" placeholder="ปล่อยว่างเพื่อไม่เปลี่ยน">
                </div>

                <div class="form-group">
                    <label for="edit_rule">สิทธิ์</label>
                    <select id="edit_rule" name="rule" required>
                        <option value="user">ผู้ใช้ทั่วไป (user)</option>
                        <option value="customer">ผู้ขาย (customer)</option>
                        <option value="admin">ผู้ดูแลระบบ (admin)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="edit_money">เงิน (บาท)</label>
                    <input type="number" id="edit_money" name="money" min="0" required>
                </div>

                <div class="form-buttons">
                    <button type="button" class="btn btn-cancel" onclick="closeEditModal()">ยกเลิก</button>
                    <button type="submit" name="update_profile" value="1" class="btn btn-submit">บันทึกการเปลี่ยนแปลง</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Alert Container -->
    <div id="alertContainer"></div>

    <script>
        function showAlert(message, type) {
            const alertContainer = document.getElementById('alertContainer');
            const alert = document.createElement('div');
            alert.className = `alert alert-${type}`;
            alert.textContent = message;
            
            alertContainer.appendChild(alert);
            
            setTimeout(function() {
                alert.style.animation = 'slideDown 0.3s ease forwards';
                setTimeout(function() {
                    alert.remove();
                }, 300);
            }, 1500);
        }

        <?php if (isset($success_msg)): ?>
            showAlert('<?php echo addslashes($success_msg); ?>', 'success');
        <?php endif; ?>

        <?php if (isset($error_msg)): ?>
            showAlert('<?php echo addslashes($error_msg); ?>', 'error');
        <?php endif; ?>

        // Event listeners for edit and delete buttons
        document.addEventListener('click', function(e) {
            if (e.target && e.target.classList.contains('btn-edit')) {
                const userId = e.target.getAttribute('data-id');
                const dataJson = e.target.getAttribute('data-info');
                try {
                    const data = JSON.parse(dataJson);
                    document.getElementById('edit_user_id').value = userId;
                    document.getElementById('edit_name').value = data[0];
                    document.getElementById('edit_username').value = data[1];
                    document.getElementById('edit_phone').value = data[2];
                    document.getElementById('edit_rule').value = data[3];
                    document.getElementById('edit_money').value = data[4];
                    document.getElementById('edit_password').value = '';
                    document.getElementById('editModal').classList.add('active');
                } catch (err) {
                    console.error('Error:', err);
                    alert('เกิดข้อผิดพลาดในการเปิด modal');
                }
            }
            
            if (e.target && e.target.classList.contains('btn-delete')) {
                const userId = e.target.getAttribute('data-id');
                const nameJson = e.target.getAttribute('data-name');
                try {
                    const userName = JSON.parse(nameJson);
                    if (confirm('คุณแน่ใจหรือว่าต้องการลบ ' + userName + ' ?')) {
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.innerHTML = '<input type="hidden" name="user_id" value="' + userId + '"><input type="hidden" name="delete_user" value="1">';
                        document.body.appendChild(form);
                        form.submit();
                    }
                } catch (err) {
                    console.error('Error:', err);
                    alert('เกิดข้อผิดพลาดในการลบ');
                }
            }
        });

        function openEditModal(userId, dataJson) {
            try {
                const data = JSON.parse(dataJson);
                document.getElementById('edit_user_id').value = userId;
                document.getElementById('edit_name').value = data[0];
                document.getElementById('edit_username').value = data[1];
                document.getElementById('edit_phone').value = data[2];
                document.getElementById('edit_rule').value = data[3];
                document.getElementById('edit_money').value = data[4];
                document.getElementById('edit_password').value = '';
                document.getElementById('editModal').classList.add('active');
            } catch (e) {
                console.error('Error parsing data:', e);
            }
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.remove('active');
        }

        function deleteUser(userId, userName) {
            if (confirm('คุณแน่ใจหรือว่าต้องการลบ ' + userName + ' ?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = '<input type="hidden" name="user_id" value="' + userId + '"><input type="hidden" name="delete_user" value="1">';
                document.body.appendChild(form);
                form.submit();
            }
        }

        document.getElementById('editModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeEditModal();
            }
        });
    </script>
</body>
</html>
