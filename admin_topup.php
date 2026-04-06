<?php
session_start();

require_once 'db.php';
// $conn is already established in db.php

// เช็คสิทธิ์ว่าต้องเป็น Admin
if (!isset($_SESSION['username']) || !isset($_SESSION['rule']) || $_SESSION['rule'] !== 'admin') {
    header('Location: index.php');
    exit();
}

// Table initialization is already handled in db.php

$success_msg = null;
$error_msg = null;

// จัดการ อนุมัติ / ปฏิเสธ สลิป
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $slip_id = (int)$_POST['slip_id'];
    
    // ดึงข้อมูลสลิป
    $q = $conn->query("SELECT * FROM slip_topup WHERE id = $slip_id AND status = 'pending'");
    if ($q && $q->num_rows > 0) {
        $slip = $q->fetch_assoc();
        $amount = (float)$slip['amount'];
        $uname = $conn->real_escape_string($slip['username']);
        
        if ($action === 'approve') {
            // เพิ่มเงิน
            $conn->query("UPDATE user SET money = money + $amount WHERE username = '$uname'");
            // อัปเดตสถานะสลิป
            $conn->query("UPDATE slip_topup SET status = 'approved' WHERE id = $slip_id");
            $success_msg = "✅ อนุมัติสลิปของ $uname จำนวน $amount พอยท์ สำเร็จ!";
        } elseif ($action === 'reject') {
            // ปฏิเสธอย่างเดียว
            $conn->query("UPDATE slip_topup SET status = 'rejected' WHERE id = $slip_id");
            $error_msg = "❌ ปฏิเสธสลิปของ $uname เรียบร้อยแล้ว";
        }
    }
}

// ดึงรายการที่รออนุมัติทั้งหมด (เรียงจากเก่าไปใหม่)
$pending_slips = $conn->query("SELECT * FROM slip_topup WHERE status = 'pending' ORDER BY created_at ASC");
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการสลิปเติมเงิน | MESA SAMP SHOP Admin</title>
    <style>
        /* (ธีม Minimal Egg & Purple) */
        :root {
            --bg: #FDFBF7;
            --panel: #FFFFFF;
            --panel-soft: rgba(160, 120, 230, 0.05);
            --text: #332F37;
            --muted: #8E8A95;
            --accent: #9A7BDE;
            --danger: #FF7070;
            --success: #2dd4bf;
            --border-light: #EBE5F2;
        }

        body {
            margin: 0;
            background: linear-gradient(to bottom, #FDFBF7, #FAF7F0);
            color: var(--text);
            font-family: 'Inter', sans-serif;
            padding: 24px;
        }

        .admin-container {
            max-width: 900px;
            margin: 0 auto;
            background: var(--panel);
            border: 1px solid var(--border-light);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 12px 30px rgba(154, 123, 222, 0.05);
        }

        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--border-light);
        }

        .admin-header h1 {
            margin: 0;
            color: var(--accent);
        }

        .back-btn {
            background: var(--panel-soft);
            color: var(--accent);
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 12px;
            border: 1px solid var(--border-light);
            font-weight: 600;
        }

        .slip-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
        }

        .slip-card {
            background: var(--panel-soft);
            border: 1px solid var(--border-light);
            border-radius: 16px;
            overflow: hidden;
            text-align: center;
            padding-bottom: 16px;
        }

        .slip-image {
            width: 100%;
            height: 300px;
            object-fit: cover;
            border-bottom: 1px solid var(--border-light);
            cursor: pointer;
            transition: opacity 0.2s;
        }
        .slip-image:hover { opacity: 0.8; }

        .slip-details {
            padding: 16px;
        }

        .amount {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--text);
            margin: 10px 0;
        }

        .btn-group {
            display: flex;
            gap: 10px;
            justify-content: center;
            padding: 0 16px;
        }

        .btn {
            border: none;
            padding: 10px 16px;
            border-radius: 10px;
            font-weight: bold;
            cursor: pointer;
            flex: 1;
            color: white;
        }

        .btn-approve { background: var(--success); }
        .btn-reject { background: var(--danger); }

        .alert {
            padding: 14px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-weight: 600;
            text-align: center;
        }
        .alert-success { background: rgba(45, 212, 191, 0.1); color: var(--success); }
        .alert-error { background: rgba(255, 112, 112, 0.1); color: var(--danger); }
        .empty { text-align: center; color: var(--muted); padding: 40px; }

        @media (max-width: 600px) {
            body { padding: 16px; }
            .admin-container { padding: 20px; }
            .admin-header { flex-direction: column; gap: 16px; text-align: center; }
            .slip-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <h1>📥 จัดการสลิปเติมเงิน</h1>
            <a href="index.php" class="back-btn">กลับหน้าร้าน</a>
        </div>

        <?php if ($success_msg): ?>
            <div class="alert alert-success"><?php echo $success_msg; ?></div>
        <?php endif; ?>
        <?php if ($error_msg): ?>
            <div class="alert alert-error"><?php echo $error_msg; ?></div>
        <?php endif; ?>

        <?php if ($pending_slips && $pending_slips->num_rows > 0): ?>
            <div class="slip-grid">
                <?php while ($row = $pending_slips->fetch_assoc()): ?>
                    <div class="slip-card">
                        <a href="<?php echo htmlspecialchars($row['slip_image']); ?>" target="_blank" title="คลิกเพื่อดูรูปใหญ่">
                            <img src="<?php echo htmlspecialchars($row['slip_image']); ?>" class="slip-image" alt="สลิปโอนเงิน">
                        </a>
                        <div class="slip-details">
                            <div>แจ้งโดย: <strong><?php echo htmlspecialchars($row['username']); ?></strong></div>
                            <div class="amount">฿<?php echo number_format($row['amount'], 2); ?></div>
                            <div style="font-size: 0.8rem; color: var(--muted);">เวลา: <?php echo $row['created_at']; ?></div>
                        </div>
                        <div class="btn-group">
                            <form method="POST" style="flex:1;">
                                <input type="hidden" name="slip_id" value="<?php echo $row['id']; ?>">
                                <input type="hidden" name="action" value="approve">
                                <button type="submit" class="btn btn-approve" onclick="return confirm('ยืนยันให้อนุมัติสลิปนี้?');">✔️ อนุมัติ</button>
                            </form>
                            <form method="POST" style="flex:1;">
                                <input type="hidden" name="slip_id" value="<?php echo $row['id']; ?>">
                                <input type="hidden" name="action" value="reject">
                                <button type="submit" class="btn btn-reject" onclick="return confirm('ไม่อนุมัติสลิปนี้ แน่ใจไหม?');">❌ ปฏิเสธ</button>
                            </form>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="empty">🎉 ไม่มีสลิปรอตรวจสอบในขณะนี้</div>
        <?php endif; ?>
    </div>
</body>
</html>
