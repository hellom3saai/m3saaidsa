<?php
session_start();

require_once 'db.php';
// $conn is already established in db.php

$canAdd = isset($_SESSION['rule']) && in_array($_SESSION['rule'], ['admin', 'customer']);
$isAdmin = isset($_SESSION['rule']) && $_SESSION['rule'] === 'admin';

$user_money = 0;
if (isset($_SESSION['username'])) {
    $username_safe = $conn->real_escape_string($_SESSION['username']);
    $money_q = $conn->query("SELECT money FROM user WHERE username = '$username_safe'");
    if ($money_q && $money_q->num_rows > 0) {
        $user_money = (int) $money_q->fetch_assoc()['money'];
    }
}
$total_users_q = $conn->query("SELECT COUNT(*) as count FROM user");
$total_users = ($total_users_q ? $total_users_q->fetch_assoc()['count'] : 0) + 100;

$total_sales_q = $conn->query("SELECT COUNT(*) as count FROM inventory");
$total_sales = ($total_sales_q ? $total_sales_q->fetch_assoc()['count'] : 0);

$total_products_q = $conn->query("SELECT COUNT(*) as count FROM product");
$total_products = ($total_products_q ? $total_products_q->fetch_assoc()['count'] : 0);

$total_success_topup_q = $conn->query("SELECT COUNT(*) as count FROM slip_topup WHERE status = 'approved'");
$total_success_topup = ($total_success_topup_q ? $total_success_topup_q->fetch_assoc()['count'] : 0);

// Handle delete product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_product'])) {
    if (!$isAdmin) {
        $error_msg = "คุณไม่มีสิทธิ์ลบสินค้า";
    } else {
        $product_id = (int) $_POST['product_id'];
        $delete_query = "DELETE FROM product WHERE id = $product_id";
        if ($conn->query($delete_query)) {
            header('Location: ' . $_SERVER['PHP_SELF'] . '?success=2');
            exit();
        } else {
            $error_msg = "เกิดข้อผิดพลาดในการลบสินค้า";
        }
    }
}

// Handle update product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_product'])) {
    if (!$isAdmin) {
        $error_msg = "คุณไม่มีสิทธิ์แก้ไขสินค้า";
    } else {
        $product_id = (int) $_POST['product_id'];
        $name = trim($_POST['name_product']);
        $details = trim($_POST['details_product']);
        $picture = trim($_POST['picture_product']);
        $price = trim($_POST['price_product']);
        $category = isset($_POST['category']) && is_array($_POST['category']) ? implode(',', $_POST['category']) : (isset($_POST['category']) ? trim($_POST['category']) : '');
        $ammo = isset($_POST['ammo_product']) ? (int) $_POST['ammo_product'] : 0;
        $link_download = isset($_POST['link_download']) ? trim($_POST['link_download']) : '';

        if (!empty($name) && !empty($details) && !empty($price) && !empty($category)) {
            $name_safe = $conn->real_escape_string($name);
            $details_safe = $conn->real_escape_string($details);
            $picture_safe = $conn->real_escape_string($picture);
            $price_safe = (int) $price;
            $category_safe = $conn->real_escape_string($category);
            $ammo_safe = (int) $ammo;
            $link_safe = $conn->real_escape_string($link_download);

            $update_query = "UPDATE product SET name_product='$name_safe', details_product='$details_safe', picture_product='$picture_safe', price_product=$price_safe, Category='$category_safe', ammo_product=$ammo_safe, link_download='$link_safe', video_url='" . $conn->real_escape_string(trim($_POST['video_url'])) . "' WHERE id = $product_id";
            if ($conn->query($update_query)) {
                header('Location: ' . $_SERVER['PHP_SELF'] . '?success=3');
                exit();
            } else {
                $error_msg = "เกิดข้อผิดพลาดในการแก้ไขสินค้า";
            }
        } else {
            $error_msg = "กรุณากรอกข้อมูลให้ครบทั้งหมด";
        }
    }
}

// Handle add new product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    if (!$canAdd) {
        $error_msg = "คุณไม่มีสิทธิ์เพิ่มสินค้า";
    } else {
        $name = trim($_POST['name_product']);
        $details = trim($_POST['details_product']);
        $picture = trim($_POST['picture_product']);
        $price = trim($_POST['price_product']);
        $category = isset($_POST['category']) && is_array($_POST['category']) ? implode(',', $_POST['category']) : (isset($_POST['category']) ? trim($_POST['category']) : '');
        $ammo = isset($_POST['ammo_product']) ? (int) $_POST['ammo_product'] : 0;
        $link_download = isset($_POST['link_download']) ? trim($_POST['link_download']) : '';
        $video_url = isset($_POST['video_url']) ? trim($_POST['video_url']) : '';

        if (!empty($name) && !empty($details) && !empty($price) && !empty($category)) {
            $name_safe = $conn->real_escape_string($name);
            $details_safe = $conn->real_escape_string($details);
            $picture_safe = $conn->real_escape_string($picture);
            $price_safe = (int) $price;
            $category_safe = $conn->real_escape_string($category);
            $ammo_safe = (int) $ammo;
            $link_safe = $conn->real_escape_string($link_download);
            $video_safe = $conn->real_escape_string($video_url);

            $insert_query = "INSERT INTO product (name_product, details_product, picture_product, price_product, Category, ammo_product, link_download, video_url) VALUES ('$name_safe', '$details_safe', '$picture_safe', $price_safe, '$category_safe', $ammo_safe, '$link_safe', '$video_safe')";
            if ($conn->query($insert_query)) {
                header('Location: ' . $_SERVER['PHP_SELF'] . '?success=1');
                exit();
            } else {
                $error_msg = "เกิดข้อผิดพลาด: " . $conn->error;
            }
        } else {
            $error_msg = "กรุณากรอกข้อมูลให้ครบทั้งหมด";
        }
    }
}

// Handle buy product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['buy_product_id'])) {
    if (!isset($_SESSION['username'])) {
        $error_msg = "กรุณาเข้าสู่ระบบก่อนสั่งซื้อสินค้า";
    } else {
        $product_id = (int) $_POST['buy_product_id'];
        $uname = $conn->real_escape_string($_SESSION['username']);

        $check_product = $conn->query("SELECT * FROM product WHERE id = $product_id");
        if ($check_product && $check_product->num_rows > 0) {
            $product = $check_product->fetch_assoc();
            $price = (int) $product['price_product'];
            $ammo = (int) $product['ammo_product'];

            $check_inv = $conn->query("SELECT * FROM inventory WHERE username = '$uname' AND product_id = $product_id");
            if ($check_inv && $check_inv->num_rows > 0) {
                $error_msg = "ท่านมีสินค้านี้ในกระเป๋าอยู่แล้ว ไม่สามารถซื้อซ้ำได้ครับ 🎒";
            } elseif ($ammo <= 0) {
                $error_msg = "ขออภัย ท่านมาช้าไป! สินค้านี้หมดสต็อกแล้ว!";
            } else {
                $check_money = $conn->query("SELECT money FROM user WHERE username = '$uname'");
                $user_bal = (int) $check_money->fetch_assoc()['money'];

                if ($user_bal >= $price) {
                    $conn->query("UPDATE user SET money = money - $price WHERE username = '$uname'");
                    $conn->query("UPDATE product SET ammo_product = ammo_product - 1 WHERE id = $product_id");
                    $conn->query("INSERT INTO inventory (username, product_id) VALUES ('$uname', $product_id)");

                    header('Location: ' . $_SERVER['PHP_SELF'] . '?success=4');
                    exit();
                } else {
                    $error_msg = "พอยท์ไม่พอ! กรุณาเติมเงินก่อนซื้อนะครับ 💸";
                }
            }
        } else {
            $error_msg = "ไม่พบสินค้าที่ต้องการ";
        }
    }
}

// SQLite handles dynamic columns differently, we usually just ensure table is initialized.
// Initialization is already done in db.php

// Get messages from query string or session
if (isset($_GET['success'])) {
    if ($_GET['success'] == 1) {
        $success_msg = "เพิ่มสินค้าใหม่สำเร็จ!";
    } elseif ($_GET['success'] == 2) {
        $success_msg = "ลบสินค้าสำเร็จ!";
    } elseif ($_GET['success'] == 3) {
        $success_msg = "แก้ไขสินค้าสำเร็จ!";
    } elseif ($_GET['success'] == 4) {
        $success_msg = "สั่งซื้อสินค้าสำเร็จ! สคริปต์ไปอยู่ในกระเป๋าแล้ว 🎒";
    }
} else {
    $success_msg = null;
}
$error_msg = isset($_GET['error']) ? "เกิดข้อผิดพลาด: " . htmlspecialchars($_GET['error']) : (isset($error_msg) ? $error_msg : null);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MESA SAMP SHOP | จำหน่ายระบบ SAMP คุณภาพ</title>
    <style>
        :root {
            --bg: #071118;
            --panel: rgba(10, 23, 42, 0.92);
            --panel-soft: rgba(255, 255, 255, 0.08);
            --text: #e8f1ff;
            --muted: #9bb0d6;
            --accent: #4fd1ff;
            --accent-2: #51d2b7;
            --danger: #ff6b6b;
            --shadow: 0 25px 60px rgba(0, 0, 0, 0.35);
            --border-light: rgba(154, 123, 222, 0.15);
        }

        /* Video Modal Styles */
        .video-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.9);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 3000;
            backdrop-filter: blur(10px);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .video-modal.active {
            display: flex;
            opacity: 1;
        }

        .video-container {
            width: 90%;
            max-width: 900px;
            aspect-ratio: 16 / 9;
            background: #000;
            border-radius: 20px;
            overflow: hidden;
            position: relative;
            box-shadow: 0 0 50px rgba(79, 209, 255, 0.3);
            transform: scale(0.9);
            transition: transform 0.3s ease;
        }

        .video-modal.active .video-container {
            transform: scale(1);
        }

        .video-container iframe {
            width: 100%;
            height: 100%;
            border: none;
        }

        .close-video {
            position: absolute;
            top: -50px;
            right: 0;
            color: #fff;
            font-size: 2rem;
            cursor: pointer;
            background: none;
            border: none;
            transition: transform 0.2s;
        }

        .close-video:hover {
            transform: scale(1.2);
            color: var(--accent);
        }

        .btn-video {
            background: var(--panel-soft);
            color: var(--accent);
            border: 1px solid var(--border-light);
            padding: 8px 14px;
            border-radius: 12px;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
            margin-top: 10px;
            width: fit-content;
        }

        .btn-video:hover {
            background: var(--accent);
            color: #000;
            transform: translateY(-2px);
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
            max-width: 1240px;
            margin: 0 auto;
        }

        .site-header {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            gap: 20px;
            align-items: center;
            padding: 24px 28px;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 24px;
            box-shadow: var(--shadow);
            backdrop-filter: blur(10px);
            position: relative;
            z-index: 999;
        }

        .site-header .title-group {
            max-width: 760px;
        }

        .site-header h1 {
            font-size: clamp(2.4rem, 3vw, 3.2rem);
            line-height: 1.05;
            letter-spacing: -0.03em;
            margin-bottom: 12px;
        }

        .site-header .subtitle {
            color: var(--muted);
            font-size: 1rem;
            line-height: 1.75;
            max-width: 720px;
        }

        .site-header .tagline {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(79, 209, 255, 0.12);
            color: var(--accent);
            padding: 10px 16px;
            border-radius: 999px;
            font-size: 0.95rem;
            font-weight: 600;
            border: 1px solid rgba(79, 209, 255, 0.16);
        }

        .category-filter {
            margin-top: 24px;
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }

        .category-filter button {
            background: rgba(255, 255, 255, 0.07);
            color: var(--text);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 999px;
            padding: 10px 18px;
            cursor: pointer;
            transition: transform 0.2s ease, background 0.2s ease, border-color 0.2s ease;
            font-size: 0.95rem;
            font-weight: 600;
        }

        .category-filter button:hover {
            transform: translateY(-1px);
            background: rgba(79, 209, 255, 0.16);
        }

        .category-filter button.active {
            background: linear-gradient(135deg, rgba(79, 209, 255, 0.28), rgba(81, 210, 183, 0.28));
            border-color: rgba(79, 209, 255, 0.4);
            color: #e8f1ff;
        }

        .user-panel {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 12px;
            position: relative;
        }

        .user-row {
            display: flex;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .user-info {
            color: #cbd5e1;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .user-money {
            background: rgba(79, 209, 255, 0.15);
            color: var(--accent);
            padding: 6px 14px;
            border-radius: 999px;
            font-size: 0.9rem;
            font-weight: 700;
            border: 1px solid rgba(79, 209, 255, 0.3);
        }

        .user-menu-btn {
            background: rgba(79, 209, 255, 0.15);
            border: 1px solid rgba(79, 209, 255, 0.3);
            padding: 8px 14px;
            border-radius: 8px;
            cursor: pointer;
            color: var(--accent);
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            transition: all 0.2s ease;
        }

        .user-menu-btn:hover {
            background: rgba(79, 209, 255, 0.25);
            border-color: rgba(79, 209, 255, 0.5);
        }

        .user-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            background: rgba(5, 14, 28, 0.96);
            border: 1px solid rgba(79, 209, 255, 0.2);
            border-radius: 12px;
            margin-top: 8px;
            box-shadow: var(--shadow);
            min-width: 200px;
            display: none;
            z-index: 1000;
            overflow: hidden;
        }

        .user-dropdown.active {
            display: block;
        }

        .dropdown-item {
            display: block;
            padding: 12px 18px;
            color: #e8f1ff;
            text-decoration: none;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            transition: background 0.2s ease;
            font-weight: 600;
        }

        .dropdown-item:last-child {
            border-bottom: none;
        }

        .dropdown-item:hover {
            background: rgba(79, 209, 255, 0.15);
        }

        .dropdown-item.danger:hover {
            background: rgba(255, 107, 107, 0.15);
            color: #ffccd5;
        }

        .auth-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .user-link {
            color: var(--accent);
            text-decoration: none;
            font-weight: 700;
            padding: 10px 16px;
            border-radius: 10px;
            border: 1px solid rgba(79, 209, 255, 0.3);
            background: rgba(79, 209, 255, 0.1);
            transition: all 0.2s ease;
            display: inline-block;
        }

        .user-link:hover {
            color: #94e1ff;
            background: rgba(79, 209, 255, 0.2);
            border-color: rgba(79, 209, 255, 0.5);
        }

        .permission-note {
            margin: 20px auto 0;
            padding: 18px 22px;
            border-radius: 22px;
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #d1d9ea;
            max-width: 620px;
            text-align: center;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 24px;
            margin-top: 34px;
        }

        .card {
            background: rgba(8, 16, 34, 0.93);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 28px;
            overflow: hidden;
            box-shadow: var(--shadow);
            transition: transform 0.25s ease, border-color 0.25s ease, box-shadow 0.25s ease;
            cursor: pointer;
        }

        .card:hover {
            transform: translateY(-10px);
            border-color: rgba(79, 209, 255, 0.35);
            box-shadow: 0 35px 70px rgba(0, 0, 0, 0.45);
        }

        .card-image {
            width: 100%;
            height: 220px;
            aspect-ratio: 16/9;
            background: linear-gradient(135deg, rgba(79, 209, 255, 0.18), rgba(81, 210, 183, 0.18));
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .card-image::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, transparent 0%, rgba(0, 0, 0, 0.3) 100%);
            z-index: 2;
        }

        .card-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
            display: block;
        }

        .card-content {
            padding: 24px;
        }

        .card-name {
            font-size: 1.35rem;
            color: #f8fafc;
            font-weight: 800;
            margin-bottom: 12px;
        }

        .card-seller {
            font-size: 0.9rem;
            color: #cbd5e1;
            font-weight: 600;
            margin-bottom: 12px;
        }

        .card-stock {
            font-size: 0.9rem;
            color: #cbd5e1;
            font-weight: 600;
            margin-bottom: 12px;
        }

        .card-details {
            font-size: 0.95rem;
            color: #b8c6db;
            margin-bottom: 18px;
            line-height: 1.7;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .card-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
        }

        .card-price {
            font-size: 1.5rem;
            color: var(--accent);
            font-weight: 800;
        }

        .card-price::before {
            content: "฿";
            margin-right: 4px;
        }

        .card-actions {
            display: none;
            position: absolute;
            top: 12px;
            right: 12px;
            gap: 8px;
            z-index: 10;
        }

        .card:hover .card-actions {
            display: flex;
        }

        .action-btn {
            width: 36px;
            height: 36px;
            border: none;
            border-radius: 8px;
            background: rgba(0, 0, 0, 0.6);
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            transition: all 0.2s ease;
            backdrop-filter: blur(10px);
        }

        .action-btn:hover {
            background: rgba(0, 0, 0, 0.8);
        }

        .action-btn.delete:hover {
            background: rgba(255, 107, 107, 0.6);
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.06);
            color: #95a7c2;
            font-size: 0.82rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .empty-state {
            grid-column: 1 / -1;
            text-align: center;
            padding: 80px 20px;
            color: #cbd5e1;
        }

        .empty-state p {
            font-size: 1.1rem;
        }

        .add-button {
            position: fixed;
            bottom: 28px;
            right: 28px;
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, #4fd1ff 0%, #51d2b7 100%);
            color: #071118;
            border: none;
            border-radius: 50%;
            font-size: 34px;
            cursor: pointer;
            box-shadow: 0 18px 40px rgba(79, 209, 255, 0.28);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            display: grid;
            place-items: center;
        }

        .add-button:hover {
            transform: scale(1.08);
            box-shadow: 0 22px 48px rgba(79, 209, 255, 0.35);
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
            overflow-y: auto;
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
            max-width: 520px;
            width: 100%;
            animation: slideIn 0.25s ease;
            margin: 24px 0;
        }

        .modal-header {
            font-size: 1.8rem;
            font-weight: 800;
            color: #f8fafc;
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
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 14px 16px;
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 16px;
            font-size: 1rem;
            font-family: inherit;
            color: #e2e8f0;
            background: rgba(255, 255, 255, 0.04);
            transition: border-color 0.25s ease, background 0.25s ease;
        }

        .form-group input::placeholder,
        .form-group textarea::placeholder {
            color: rgba(226, 232, 240, 0.6);
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: rgba(79, 209, 255, 0.5);
            background: rgba(255, 255, 255, 0.08);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        .image-inputs-group {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .image-input-row {
            display: flex;
            gap: 12px;
            align-items: flex-end;
        }

        .image-input-row input {
            flex: 1;
            padding: 14px 16px;
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 12px;
            font-size: 1rem;
            color: #e2e8f0;
            background: rgba(255, 255, 255, 0.04);
            transition: border-color 0.25s ease;
        }

        .image-input-row input:focus {
            outline: none;
            border-color: rgba(79, 209, 255, 0.5);
            background: rgba(255, 255, 255, 0.08);
        }

        .image-input-row input::placeholder {
            color: rgba(226, 232, 240, 0.6);
        }

        .btn-add-image {
            padding: 12px 16px;
            border: 1px solid rgba(79, 209, 255, 0.3);
            background: rgba(79, 209, 255, 0.1);
            color: var(--accent);
            border-radius: 12px;
            cursor: pointer;
            font-weight: 700;
            transition: all 0.2s ease;
            white-space: nowrap;
        }

        .btn-add-image:hover {
            background: rgba(79, 209, 255, 0.2);
            border-color: rgba(79, 209, 255, 0.5);
        }

        .btn-file-upload {
            padding: 12px 14px;
            border: 1px solid rgba(81, 210, 183, 0.3);
            background: rgba(81, 210, 183, 0.1);
            color: #51d2b7;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 700;
            transition: all 0.2s ease;
            white-space: nowrap;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            position: relative;
        }

        .btn-file-upload:hover {
            background: rgba(81, 210, 183, 0.2);
            border-color: rgba(81, 210, 183, 0.5);
        }

        .btn-file-upload input[type="file"] {
            position: absolute;
            inset: 0;
            opacity: 0;
            cursor: pointer;
            width: 100%;
            height: 100%;
        }

        .btn-file-upload.uploading {
            opacity: 0.6;
            pointer-events: none;
        }

        .img-preview {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            object-fit: cover;
            border: 1px solid rgba(255, 255, 255, 0.15);
            flex-shrink: 0;
            display: none;
        }

        .btn-remove-image {
            padding: 12px 14px;
            border: 1px solid rgba(255, 107, 107, 0.3);
            background: rgba(255, 107, 107, 0.1);
            color: #ffccd5;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 700;
            transition: all 0.2s ease;
            width: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .btn-remove-image:hover {
            background: rgba(255, 107, 107, 0.2);
            border-color: rgba(255, 107, 107, 0.5);
        }

        .form-buttons {
            display: flex;
            gap: 14px;
            margin-top: 24px;
            flex-wrap: wrap;
        }

        .btn {
            flex: 1;
            min-width: 140px;
            padding: 14px 20px;
            border: none;
            border-radius: 14px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .btn-submit {
            background: linear-gradient(135deg, #4fd1ff 0%, #51d2b7 100%);
            color: #071118;
            box-shadow: 0 14px 26px rgba(79, 209, 255, 0.24);
        }

        .btn-submit:hover {
            transform: translateY(-2px);
        }

        .btn-cancel {
            background: rgba(255, 255, 255, 0.08);
            color: #cbd5e1;
            border: 1px solid rgba(255, 255, 255, 0.14);
        }

        .btn-cancel:hover {
            background: rgba(255, 255, 255, 0.12);
        }

        .modal-details {
            max-width: 600px !important;
            padding: 0 !important;
            overflow: hidden;
            position: relative;
            display: flex;
            flex-direction: column;
            max-height: 90vh;
        }

        .modal-details::-webkit-scrollbar {
            display: none;
        }

        .modal-details {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }

        .close-btn {
            position: absolute;
            top: 24px;
            right: 24px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.14);
            color: #cbd5e1;
            width: 44px;
            height: 44px;
            border-radius: 12px;
            cursor: pointer;
            font-size: 1.4rem;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            z-index: 10;
        }

        .close-btn:hover {
            background: rgba(255, 255, 255, 0.15);
            color: #f8fafc;
        }

        .details-container {
            display: grid;
            grid-template-columns: 1fr;
            gap: 0;
            overflow-y: auto;
        }

        .details-container::-webkit-scrollbar {
            display: none;
        }

        .details-container {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }

        .details-gallery {
            position: relative;
            background: rgba(5, 14, 28, 0.8);
            height: 450px;
            flex-shrink: 0;
        }

        .details-gallery .gallery-inner {
            width: 100%;
            height: 100%;
            display: flex;
            transition: transform 0.4s ease;
        }

        .details-gallery img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            flex-shrink: 0;
        }

        .details-info {
            padding: 36px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            background: rgba(5, 14, 28, 0.96);
            border-left: none;
        }

        .details-info h2 {
            font-size: 2rem;
            color: #f8fafc;
            margin-bottom: 16px;
            margin-top: 0;
        }

        .details-category {
            display: inline-block;
            background: rgba(79, 209, 255, 0.15);
            color: #4fd1ff;
            padding: 8px 14px;
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: 700;
            margin-bottom: 16px;
            border: 1px solid rgba(79, 209, 255, 0.25);
            width: fit-content;
        }

        .details-description {
            color: #cbd5e1;
            line-height: 1.6;
            margin-bottom: 24px;
            flex: 1;
            word-wrap: break-word;
            word-break: break-word;
            white-space: normal;
            overflow-wrap: break-word;
        }

        .details-footer {
            display: flex;
            gap: 16px;
            align-items: center;
        }

        .details-price {
            font-size: 1.8rem;
            font-weight: 800;
            background: linear-gradient(135deg, #4fd1ff 0%, #51d2b7 100%);
            background-clip: text;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .details-footer .btn {
            flex: 1;
        }

        @media (max-width: 768px) {
            .details-container {
                grid-template-columns: 1fr;
                min-height: auto;
            }

            .details-gallery {
                height: 220px;
            }

            .details-gallery .gallery-inner {
                height: 100%;
            }

            .details-info {
                border-left: none;
                border-top: none;
                padding: 24px;
            }

            .details-info h2 {
                font-size: 1.5rem;
            }

            .close-btn {
                top: 16px;
                right: 16px;
            }

            .modal-details {
                max-width: 95% !important;
                max-height: 85vh;
            }
        }

        .alert {
            position: fixed;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%);
            padding: 16px 24px;
            border-radius: 14px;
            font-weight: 700;
            box-shadow: 0 18px 40px rgba(0, 0, 0, 0.35);
            z-index: 999;
            animation: slideUp 0.3s ease;
            max-width: 90%;
            width: auto;
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

        .image-gallery {
            position: relative;
            width: 100%;
            height: 220px;
            overflow: hidden;
            border-radius: 12px 12px 0 0;
        }

        .gallery-inner {
            display: flex;
            height: 100%;
            transition: transform 0.4s ease;
            width: 100%;
        }

        .gallery-inner img {
            width: 100%;
            height: 100%;
            min-width: 100%;
            flex-shrink: 0;
            object-fit: cover;
            object-position: center;
        }

        .gallery-nav {
            position: absolute;
            bottom: 12px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 8px;
            z-index: 5;
        }

        .gallery-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.4);
            cursor: pointer;
            transition: all 0.2s ease;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .gallery-dot.active {
            background: rgba(79, 209, 255, 0.8);
            border-color: var(--accent);
        }

        .gallery-btn {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(0, 0, 0, 0.5);
            color: white;
            border: none;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            transition: all 0.2s ease;
            z-index: 4;
        }

        .gallery-btn:hover {
            background: rgba(0, 0, 0, 0.7);
        }

        .gallery-btn.prev {
            left: 8px;
        }

        .gallery-btn.next {
            right: 8px;
        }

        @media (max-width: 820px) {
            .site-header {
                flex-direction: column;
                text-align: center;
            }

            .user-panel {
                align-items: center;
            }

            .user-row {
                justify-content: center;
            }

            .auth-buttons {
                justify-content: center;
            }

            .grid {
                grid-template-columns: 1fr;
            }

            .form-buttons {
                flex-direction: column;
            }
        }

        /* --- MINIMAL EGG & PURPLE OVERRIDES --- */
        :root {
            --bg: #FDFBF7;
            /* Egg white minimal */
            --panel: #FFFFFF;
            --panel-soft: rgba(160, 120, 230, 0.05);
            /* Soft purple tint */
            --text: #332F37;
            /* Very dark warm gray */
            --muted: #8E8A95;
            /* Muted gray for secondary text */
            --accent: #9A7BDE;
            /* Soft purple */
            --accent-2: #B39CDD;
            --danger: #FF7070;
            --shadow: 0 12px 30px rgba(154, 123, 222, 0.08);
            --border-light: #EBE5F2;
            /* Soft purple-gray border */
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
            background: radial-gradient(circle at top right, rgba(154, 123, 222, 0.12), transparent 35%),
                linear-gradient(to bottom, var(--bg), #d5bbebff) !important;
            color: var(--text) !important;
            transition: background 0.3s, color 0.3s;
        }

        /* Elements that were dark now get pure white panel style */
        .site-header,
        .card,
        .modal-content,
        .details-info,
        .user-dropdown {
            background: var(--panel) !important;
            border: 1px solid var(--border-light) !important;
            box-shadow: var(--shadow) !important;
        }

        /* Tagline pill */
        .site-header .tagline {
            background: rgba(154, 123, 222, 0.1) !important;
            border: 1px solid rgba(154, 123, 222, 0.2) !important;
            color: var(--accent) !important;
        }

        /* Category Filter Buttons */
        .category-filter button {
            background: var(--panel) !important;
            color: var(--muted) !important;
            border: 1px solid var(--border-light) !important;
        }

        .category-filter button:hover {
            background: var(--panel-soft) !important;
            color: var(--accent) !important;
        }

        .category-filter button.active {
            background: var(--accent) !important;
            color: #FFFFFF !important;
            border-color: var(--accent) !important;
            box-shadow: 0 4px 12px rgba(154, 123, 222, 0.3) !important;
        }

        /* Outline Buttons & Links */
        .user-money,
        .user-menu-btn,
        .user-link,
        .btn-add-image,
        .btn-file-upload {
            background: var(--panel) !important;
            color: var(--accent) !important;
            border: 1px solid rgba(154, 123, 222, 0.3) !important;
            font-weight: 600 !important;
        }

        .user-money:hover,
        .user-menu-btn:hover,
        .user-link:hover,
        .btn-add-image:hover,
        .btn-file-upload:hover {
            background: var(--panel-soft) !important;
            border-color: var(--accent) !important;
        }

        .user-info {
            color: var(--text) !important;
        }

        .dropdown-item {
            color: var(--text) !important;
            border-bottom: 1px solid var(--border-light) !important;
        }

        .dropdown-item:hover {
            background: var(--panel-soft) !important;
        }

        /* Card Content Text Settings */
        .card-name,
        .modal-header,
        .details-info h2 {
            color: var(--text) !important;
        }

        .card-seller,
        .card-stock,
        .card-details,
        .permission-note,
        .details-description,
        .form-group label {
            color: var(--muted) !important;
        }

        .permission-note {
            background: var(--panel) !important;
            border: 1px solid var(--border-light) !important;
            box-shadow: var(--shadow) !important;
        }

        .card-image {
            background: #F4F1F8 !important;
            border-bottom: 1px solid var(--border-light) !important;
        }

        .card-image::after {
            background: linear-gradient(180deg, transparent 60%, rgba(0, 0, 0, 0.06) 100%) !important;
            /* Lighter overlay */
        }

        /* Badges / Category Labels inside cards and details */
        .badge,
        .details-category-item {
            display: inline-block !important;
            background: rgba(154, 123, 222, 0.1) !important;
            color: var(--accent) !important;
            border: 1px solid rgba(154, 123, 222, 0.2) !important;
            padding: 4px 10px !important;
            border-radius: 8px !important;
            font-size: 0.75rem !important;
            font-weight: 600 !important;
            white-space: nowrap !important;
        }

        .details-category {
            display: flex !important;
            flex-wrap: wrap !important;
            gap: 8px !important;
            background: transparent !important;
            border: none !important;
            padding: 0 !important;
            margin-bottom: 12px !important;
        }

        background: rgba(154, 123, 222, 0.1) !important;
        color: var(--accent) !important;
        border: 1px solid rgba(154, 123, 222, 0.2) !important;
        padding: 4px 8px !important;
        border-radius: 8px !important;
        font-size: 0.75rem !important;
        font-weight: 600 !important;
        }

        .badge-group {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-bottom: 10px;
        }

        .checkbox-group {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
            gap: 10px;
            background: #FAFAF7 !important;
            padding: 15px !important;
            border: 1px solid var(--border-light) !important;
            border-radius: 12px !important;
        }

        .checkbox-item {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            font-size: 0.9rem !important;
            color: var(--text) !important;
        }

        .checkbox-item input[type="checkbox"] {
            width: 18px !important;
            height: 18px !important;
            cursor: pointer;
            accent-color: var(--accent);
        }

        /* Primary action gradients */
        .add-button,
        .btn-submit,
        .details-price {
            background: linear-gradient(135deg, var(--accent) 0%, var(--accent-2) 100%) !important;
            color: #FFFFFF !important;
            box-shadow: 0 8px 24px rgba(154, 123, 222, 0.3) !important;
            border: none !important;
        }

        .details-price {
            -webkit-background-clip: text !important;
            -webkit-text-fill-color: transparent !important;
            box-shadow: none !important;
            /* Because text */
        }

        .add-button:hover,
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 32px rgba(154, 123, 222, 0.4) !important;
        }

        /* Modal Blur */
        .modal {
            background: rgba(253, 251, 247, 0.4) !important;
            backdrop-filter: blur(12px) !important;
        }

        /* Input fields */
        .form-group input,
        .form-group textarea,
        .form-group select {
            background: #FAFAF7 !important;
            border: 1px solid var(--border-light) !important;
            color: var(--text) !important;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            background: #FFFFFF !important;
            border-color: var(--accent) !important;
            box-shadow: 0 0 0 4px rgba(154, 123, 222, 0.15) !important;
        }

        .form-group input::placeholder,
        .form-group textarea::placeholder {
            color: #C0BDC5 !important;
        }

        .details-gallery {
            background: #F4F1F8 !important;
        }

        .img-preview {
            border: 1px solid var(--border-light) !important;
        }

        /* Close Button Modal Details */
        .close-btn {
            background: #FFFFFF !important;
            color: var(--muted) !important;
            border: 1px solid var(--border-light) !important;
            box-shadow: var(--shadow) !important;
        }

        .close-btn:hover {
            color: var(--danger) !important;
            border-color: rgba(255, 112, 112, 0.3) !important;
        }

        /* Alert notifications */
        .alert-success {
            background: var(--panel) !important;
            color: var(--accent) !important;
            border: 1px solid var(--accent) !important;
        }

        /* Empty State */
        .empty-state {
            color: var(--muted) !important;
        }

        /* Icon action buttons inside cards (admin mode) */
        .action-btn {
            background: rgba(255, 255, 255, 0.9) !important;
            color: var(--muted) !important;
            border: 1px solid var(--border-light) !important;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05) !important;
        }

        .action-btn:hover {
            color: var(--accent) !important;
            border-color: var(--accent) !important;
        }

        .action-btn.delete:hover {
            color: var(--danger) !important;
            border-color: var(--danger) !important;
            background: rgba(255, 112, 112, 0.1) !important;
        }

        /* Gallery Dots & Chevrons */
        .gallery-dot {
            background: rgba(154, 123, 222, 0.4) !important;
            border: 1px solid rgba(255, 255, 255, 1) !important;
        }

        .gallery-dot.active {
            background: var(--accent) !important;
        }

        .gallery-btn {
            background: rgba(255, 255, 255, 0.85) !important;
            color: var(--accent) !important;
            border: 1px solid var(--border-light) !important;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05) !important;
        }

        .gallery-btn:hover {
            background: #FFFFFF !important;
            transform: translateY(-50%) scale(1.1) !important;
        }

        .btn-cancel {
            background: #FAFAF7 !important;
            color: var(--muted) !important;
            border: 1px solid var(--border-light) !important;
        }

        .btn-cancel:hover {
            background: var(--border-light) !important;
            color: var(--text) !important;
        }

        /* Stats Dashboard Styles */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-top: 25px;
            width: 100%;
            max-width: 900px;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(154, 123, 222, 0.1);
            border-radius: 16px;
            padding: 15px;
            text-align: center;
            transition: all 0.3s ease;
            backdrop-filter: blur(5px);
        }

        .stat-card:hover {
            background: rgba(154, 123, 222, 0.08);
            border-color: var(--accent);
            transform: translateY(-3px);
        }

        .stat-card .stat-val {
            display: block;
            font-size: 1.6rem;
            font-weight: 800;
            color: var(--accent);
            margin-bottom: 5px;
        }

        .stat-card .stat-label {
            display: block;
            font-size: 0.8rem;
            color: var(--muted);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        @media (max-width: 768px) {
            .stats-row {
                grid-template-columns: repeat(2, 1fr);
                gap: 10px;
            }

            .stat-card .stat-val {
                font-size: 1.3rem;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <header class="site-header">
            <div class="title-group">
                <div class="tagline">MESA SAMP SHOP</div>
                <h1>ศูนย์รวมสคริปต์และระบบ SAMP คุณภาพสูง</h1>
                <p class="subtitle">กำลังมองหา Gamemode, ระบบเสริม หรือสคริปต์เจ๋งๆ สำหรับเซิร์ฟเวอร์ SAMP
                    ของคุณอยู่ใช่ไหม?
                    เราคัดสรรระบบที่ดีที่สุด ตรวจเช็กโค้ดอย่างละเอียดก่อนถึงมือคุณ มั่นใจได้ในคุณภาพและความเสถียร</p>

                <!-- Shop Stats Bar -->
                <div class="stats-row">
                    <!--<div class="stat-card">
                        <span class="stat-val"><?php echo number_format($total_users); ?></span>
                        <span class="stat-label">👥 สมาชิกทั้งหมด</span>
                    </div>-->
                    <div class="stat-card">
                        <span class="stat-val"><?php echo number_format($total_sales); ?></span>
                        <span class="stat-label">🛒 ขายไปแล้ว</span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-val"><?php echo number_format($total_products); ?></span>
                        <span class="stat-label">📦 ระบบในร้าน</span>
                    </div>
                    <!--<div class="stat-card">
                        <span class="stat-val"><?php echo number_format($total_success_topup); ?></span>
                        <span class="stat-label">✅ เติมเงินสำเร็จ</span>
                    </div>-->
                </div>

                <div class="category-filter" aria-label="เลือกหมวดหมู่สินค้า">
                    <button class="active" data-category="All">ทั้งหมด</button>
                    <button data-category="Script">สคริปต์ (Scripts)</button>
                    <button data-category="UI">อินเตอร์เฟซ (UI)</button>
                    <button data-category="System">ระบบเสริม (Systems)</button>
                    <button data-category="Model">โมเดล (Model)</button>
                    <button data-category="Map">แมพ (Map)</button>
                    <button data-category="Skin">สกิน (Skin)</button>
                    <button data-category="Other">อื่นๆ (Other)</button>
                </div>



            </div>
            <div class="user-panel">
                <?php if (isset($_SESSION['username'])): ?>
                    <div class="user-row">
                        <div class="user-info">
                            <span>คุณ <?php echo htmlspecialchars($_SESSION['name']); ?></span>
                        </div>
                        <a href="topup.php" class="user-money" title="คลิกเพื่อเติมเงิน" style="text-decoration: none;">💎
                            <?php echo number_format($user_money); ?> พอยท์ ＋</a>
                        <button class="theme-toggle" onclick="toggleTheme()" title="สลับโหมด มืด/สว่าง"
                            style="background: var(--panel); border: 1px solid var(--border-light); color: var(--text); padding: 8px 12px; border-radius: 12px; cursor: pointer; margin-left: 8px;">🌙</button>
                        <button class="user-menu-btn" onclick="toggleUserMenu()">☰</button>
                    </div>
                    <div class="user-dropdown" id="userDropdown">
                        <a class="dropdown-item" href="my_inventory.php">🎒 กระเป๋าสัมภาระ (My Scripts)</a>
                        <a class="dropdown-item" href="#"
                            onclick="alert('ช่องทางการติดต่อ:\nDiscord: discord.gg/M3saShop\nFacebook: M3sa Shop\nหรือติดต่อแอดมินโดยตรงในเซิร์ฟเวอร์ครับ'); return false;">📞
                            ติดต่อแอดมิน</a>
                        <?php if ($isAdmin): ?>
                            <a class="dropdown-item" href="admin_topup.php">📥 จัดการสลิปเติมเงิน</a>
                            <a class="dropdown-item" href="user.php">👤 จัดการผู้ใช้งาน</a>
                        <?php endif; ?>
                        <a class="dropdown-item danger" href="logout.php">🚪 ออกจากระบบ</a>
                    </div>
                <?php else: ?>
                    <div class="auth-buttons">
                        <a class="user-link" href="login.php">เข้าสู่ระบบ</a>
                        <a class="user-link" href="regis.php">สมัครสมาชิก</a>
                    </div>
                <?php endif; ?>
            </div>
        </header>

        <div class="grid">
            <?php
            $query = "SELECT id, name_product, details_product, picture_product, price_product, Category, ammo_product, link_download, video_url FROM product";
            $result = $conn->query($query);

            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $seller_name = 'ไม่ระบุ';
                    $category_raw = !empty($row['Category']) ? $row['Category'] : 'Other';
                    $categories_arr = array_map('trim', explode(',', $category_raw));
                    $category = htmlspecialchars($category_raw);
                    $images = array_filter(array_map('trim', explode(',', $row['picture_product'])));
                    if (empty($images)) {
                        $images = ['https://via.placeholder.com/560x360/0b1727/ffffff?text=Game+Item'];
                    }
                    $galleryId = 'gallery-' . (int) $row['id'];

                    echo '<div class="card" data-categories="' . $category . '" onclick="showProductDetails(' . htmlspecialchars(json_encode([$row['id'], $row['name_product'], $row['details_product'], $row['picture_product'], $row['price_product'], $row['Category'], $row['video_url']], JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') . ')">';
                    echo '  <div class="card-image">';
                    echo '    <div class="image-gallery" id="' . $galleryId . '">';
                    echo '      <div class="gallery-inner" id="' . $galleryId . '-inner">';

                    foreach ($images as $img) {
                        echo '        <img src="' . $img . '" alt="' . htmlspecialchars($row['name_product']) . '" loading="lazy">';
                    }

                    echo '      </div>';

                    if (count($images) > 1) {
                        echo '      <button class="gallery-btn prev" type="button" onclick="event.stopPropagation();changeImage(\'' . $galleryId . '\', -1)">❮</button>';
                        echo '      <button class="gallery-btn next" type="button" onclick="event.stopPropagation();changeImage(\'' . $galleryId . '\', 1)">❯</button>';
                        echo '      <div class="gallery-nav">';
                        for ($i = 0; $i < count($images); $i++) {
                            echo '        <div class="gallery-dot' . ($i === 0 ? ' active' : '') . '" onclick="goToImage(\'' . $galleryId . '\', ' . $i . ')"></div>';
                        }
                        echo '      </div>';
                    }

                    echo '    </div>';

                    if ($isAdmin) {
                        echo '    <div class="card-actions">';
                        echo '      <button class="action-btn" type="button" onclick="event.stopPropagation();editProduct(' . (int) $row['id'] . ', ' . htmlspecialchars(json_encode([$row['name_product'], $row['details_product'], $row['picture_product'], $row['price_product'], $row['Category'], $row['id'], $row['ammo_product'], $row['link_download'], $row['video_url']], JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') . ');closeProductDetails();">✎</button>';
                        echo '      <button class="action-btn delete" type="button" onclick="event.stopPropagation();deleteProduct(' . (int) $row['id'] . ');closeProductDetails();">✕</button>';
                        echo '    </div>';
                    }

                    echo '  </div>';
                    echo '  <div class="card-content">';
                    echo '    <div class="badge-group">';
                    foreach ($categories_arr as $cat) {
                        echo '<div class="badge">' . htmlspecialchars(trim($cat)) . '</div>';
                    }
                    echo '</div>';
                    echo '    <div class="card-name">' . htmlspecialchars($row['name_product']) . '</div>';

                    echo '    <div class="card-stock">สต๊อก : ' . (isset($row['ammo_product']) ? (int) $row['ammo_product'] : 0) . '</div>';
                    echo '    <div class="card-details">' . htmlspecialchars($row['details_product']) . '</div>';

                    if (!empty($row['video_url'])) {
                        echo '<button class="btn-video" type="button" onclick="event.stopPropagation(); openVideoModal(\'' . htmlspecialchars($row['video_url']) . '\')">📺 ดูวิดีโอตัวอย่าง</button>';
                    }

                    echo '    <div class="card-footer">';
                    echo '      <div class="card-price">' . number_format($row['price_product']) . '</div>';
                    echo '      <div class="badge">NEW</div>';
                    echo '    </div>';
                    echo '  </div>';
                    echo '</div>';
                }
            } else {
                echo '<div class="empty-state"><p>ไม่มีสินค้าที่จะแสดง</p></div>';
            }
            ?>
        </div>
    </div>

    <?php if ($canAdd): ?>
        <button class="add-button" onclick="openModal()">+</button>
    <?php else: ?>
        <div class="permission-note">
            <?php if (isset($_SESSION['username'])): ?>
                หากต้องการฝากขายสินค้าให้ติดต่อ <strong>admin</strong> <strong></strong> เท่านั้น
            <?php else: ?>
                กรุณา <a class="user-link" href="login.php">เข้าสู่ระบบ</a> หรือ <a class="user-link"
                    href="regis.php">สมัครสมาชิก</a> เพื่อเลือกซื้อสินค้า
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Modal Form Add -->
    <div class="modal" id="addModal">
        <div class="modal-content">
            <div class="modal-header">เพิ่มสินค้าใหม่</div>
            <form method="POST" onsubmit="return prepareImages('imageInputsAdd', 'hidden_picture_product')">
                <div class="form-group">
                    <label for="name_product">ชื่อสินค้าสคริปต์</label>
                    <input type="text" id="name_product" name="name_product" placeholder="เช่น ระบบล็อกอิน" required>
                </div>
                <div class="form-group">
                    <label for="details_product">คุณสมบัติสคริปต์/สินค้า</label>
                    <textarea id="details_product" name="details_product" placeholder="อธิบายคุณสมบัติ"
                        required></textarea>
                </div>

                <div class="form-group">
                    <label>รูปภาพสินค้า</label>
                    <div class="image-inputs-group" id="imageInputsAdd">
                        <div class="image-input-row">
                            <img class="img-preview" src="" alt="preview">
                            <input type="url" class="image-input" placeholder="วาง URL รูปภาพ หรือเลือกไฟล์ →"
                                oninput="updatePreview(this)">
                            <label class="btn-file-upload" title="เลือกไฟล์จากเครื่อง">📁
                                <input type="file" accept="image/*" onchange="uploadFile(this, 'imageInputsAdd')">
                            </label>
                            <button type="button" class="btn-add-image"
                                onclick="addImageInput('imageInputsAdd')">+</button>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label for="ammo_product">สต๊อกสินค้า (ชิ้น)</label>
                    <input type="number" id="ammo_product" name="ammo_product" placeholder="0" min="0" required>
                </div>
                <div class="form-group">
                    <label>หมวดหมู่สินค้า (เลือกได้หลายหมวดหมู่)</label>
                    <div class="checkbox-group" id="add_category_group">
                        <label class="checkbox-item"><input type="checkbox" name="category[]" value="Script">
                            Script</label>
                        <label class="checkbox-item"><input type="checkbox" name="category[]" value="UI"> UI</label>
                        <label class="checkbox-item"><input type="checkbox" name="category[]" value="System">
                            System</label>
                        <label class="checkbox-item"><input type="checkbox" name="category[]" value="Model">
                            Model</label>
                        <label class="checkbox-item"><input type="checkbox" name="category[]" value="Map"> Map</label>
                        <label class="checkbox-item"><input type="checkbox" name="category[]" value="Skin">
                            Skin</label>
                        <label class="checkbox-item"><input type="checkbox" name="category[]" value="Other">
                            อื่นๆ</label>
                    </div>
                </div>
                <div class="form-group">
                    <label for="price_product">ราคา (พอยท์)</label>
                    <input type="number" id="price_product" name="price_product" placeholder="0" min="0" required>
                </div>
                <div class="form-group">
                    <label for="link_download">🔗 ลิงก์ดาวน์โหลดสคริปต์/ไฟล์ (URL)</label>
                    <input type="url" id="link_download" name="link_download" placeholder="ถ้ามี..." autocomplete="off">
                </div>
                <div class="form-group">
                    <label for="video_url">📺 ลิงก์วิดีโอ YouTube (ถ้ามี)</label>
                    <input type="url" id="video_url" name="video_url" placeholder="https://www.youtube.com/watch?v=...">
                </div>
                <input type="hidden" name="picture_product" id="hidden_picture_product">
                <div class="form-buttons">
                    <button type="button" class="btn btn-cancel" onclick="closeModal()">ยกเลิก</button>
                    <button type="submit" name="add_product" class="btn btn-submit">เพิ่มสินค้า</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Form Edit -->
    <div class="modal" id="editModal">
        <div class="modal-content">
            <div class="modal-header">แก้ไขสินค้า</div>
            <form method="POST" onsubmit="return prepareImages('imageInputsEdit', 'hidden_edit_picture_product')">
                <input type="hidden" name="product_id" id="edit_product_id">
                <div class="form-group">
                    <label for="edit_name_product">ชื่อสินค้าสคริปต์</label>
                    <input type="text" id="edit_name_product" name="name_product" required>
                </div>
                <div class="form-group">
                    <label for="edit_details_product">คุณสมบัติสคริปต์/สินค้า</label>
                    <textarea id="edit_details_product" name="details_product" required></textarea>
                </div>
                <div class="form-group">
                    <label>รูปภาพสินค้า</label>
                    <div class="image-inputs-group" id="imageInputsEdit">
                    </div>
                </div>
                <div class="form-group">
                    <label for="edit_ammo_product">สต๊อกสินค้า (ชิ้น)</label>
                    <input type="number" id="edit_ammo_product" name="ammo_product" min="0" required>
                </div>
                <div class="form-group">
                    <label>หมวดหมู่สินค้า (เลือกได้หลายหมวดหมู่)</label>
                    <div class="checkbox-group" id="edit_category_group">
                        <label class="checkbox-item"><input type="checkbox" name="category[]" value="Script">
                            Script</label>
                        <label class="checkbox-item"><input type="checkbox" name="category[]" value="UI"> UI</label>
                        <label class="checkbox-item"><input type="checkbox" name="category[]" value="System">
                            System</label>
                        <label class="checkbox-item"><input type="checkbox" name="category[]" value="Model">
                            Model</label>
                        <label class="checkbox-item"><input type="checkbox" name="category[]" value="Map"> Map</label>
                        <label class="checkbox-item"><input type="checkbox" name="category[]" value="Skin">
                            Skin</label>
                        <label class="checkbox-item"><input type="checkbox" name="category[]" value="Other">
                            อื่นๆ</label>
                    </div>
                </div>
                <div class="form-group">
                    <label for="edit_price_product">ราคา (พอยท์)</label>
                    <input type="number" id="edit_price_product" name="price_product" min="0" required>
                </div>
                <div class="form-group">
                    <label for="edit_link_download">🔗 ลิงก์ดาวน์โหลดสคริปต์/ไฟล์ (URL)</label>
                    <input type="url" id="edit_link_download" name="link_download" placeholder="ถ้ามี..."
                        autocomplete="off">
                </div>
                <div class="form-group">
                    <label for="edit_video_url">📺 ลิงก์วิดีโอ YouTube (ถ้ามี)</label>
                    <input type="url" id="edit_video_url" name="video_url"
                        placeholder="https://www.youtube.com/watch?v=...">
                </div>
                <input type="hidden" name="picture_product" id="hidden_edit_picture_product">
                <div class="form-buttons">
                    <button type="button" class="btn btn-cancel" onclick="closeEditModal()">ยกเลิก</button>
                    <button type="submit" name="edit_product" class="btn btn-submit">บันทึกการเปลี่ยนแปลง</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Alert Container -->
    <div id="alertContainer"></div>

    <!-- Product Details Modal -->
    <div class="modal" id="productDetailsModal"
        onclick="if(event.target.id === 'productDetailsModal') closeProductDetails()">
        <div class="modal-content modal-details" onclick="event.stopPropagation()">
            <button class="close-btn" onclick="closeProductDetails()">✕</button>
            <div class="details-container">
                <div class="details-gallery" id="detailsGallery">
                    <div class="gallery-inner" id="detailsGallery-inner"></div>
                    <button class="gallery-btn prev" id="detailsPrev" onclick="changeDetailsImage(-1)"
                        style="display:none;">❮</button>
                    <button class="gallery-btn next" id="detailsNext" onclick="changeDetailsImage(1)"
                        style="display:none;">❯</button>
                    <div class="gallery-nav" id="detailsNav" style="display:none;"></div>
                </div>
                <div class="details-info">
                    <h2 id="detailsName"></h2>
                    <div class="details-category" id="detailsCategory"></div>
                    <p class="details-description" id="detailsDescription"></p>
                    <div class="details-footer">
                        <div class="details-price" id="detailsPrice"></div>
                        <div id="detailsVideoBtn"></div>
                        <button class="btn btn-submit" onclick="buyProduct(currentProductId)">หยิบใส่กระเป๋า /
                            สั่งซื้อ</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Video Modal -->
    <div id="videoModal" class="video-modal" onclick="closeVideoModal()">
        <div class="video-container" onclick="event.stopPropagation()">
            <button class="close-video" onclick="closeVideoModal()">✕</button>
            <div id="youtubePlayer"></div>
        </div>
    </div>

    <script>
        function showAlert(message, type) {
            const alertContainer = document.getElementById('alertContainer');
            const alert = document.createElement('div');
            alert.className = `alert alert-${type}`;
            alert.textContent = message;

            alertContainer.appendChild(alert);

            setTimeout(function () {
                alert.style.animation = 'slideDown 0.3s ease forwards';
                setTimeout(function () {
                    alert.remove();
                }, 300);
            }, 1500);
        }

        // Show alert if there's a success or error message from PHP
        <?php if (isset($success_msg)): ?>
            showAlert('<?php echo addslashes($success_msg); ?>', 'success');
        <?php endif; ?>

        <?php if (isset($error_msg)): ?>
            showAlert('<?php echo addslashes($error_msg); ?>', 'error');
        <?php endif; ?>

        function openModal() {
            document.getElementById('addModal').classList.add('active');
            const container = document.getElementById('imageInputsAdd');
            container.innerHTML = buildImageRow('imageInputsAdd', '', true);
        }

        function closeModal() {
            document.getElementById('addModal').classList.remove('active');
        }

        // Build an image input row HTML string
        function buildImageRow(containerId, value = '', isLast = true) {
            const preview = value ? `<img class="img-preview" src="${value}" alt="preview" style="display:block;">` : `<img class="img-preview" src="" alt="preview">`;
            const removeOrAdd = isLast
                ? `<button type="button" class="btn-add-image" onclick="addImageInput('${containerId}')">+</button>`
                : `<button type="button" class="btn-remove-image" onclick="removeImageRow(this)">✕</button>`;
            return `<div class="image-input-row">
                ${preview}
                <input type="url" class="image-input" placeholder="วาง URL รูปภาพ หรือเลือกไฟล์ →" value="${value}" oninput="updatePreview(this)">
                <label class="btn-file-upload" title="เลือกไฟล์จากเครื่อง">📁
                    <input type="file" accept="image/*" onchange="uploadFile(this, '${containerId}')">
                </label>
                ${removeOrAdd}
            </div>`;
        }

        function addImageInput(containerId) {
            const container = document.getElementById(containerId);
            // Change last row's + to remove
            const lastRow = container.querySelector('.image-input-row:last-child');
            if (lastRow) {
                const addBtn = lastRow.querySelector('.btn-add-image');
                if (addBtn) {
                    const removeBtn = document.createElement('button');
                    removeBtn.type = 'button';
                    removeBtn.className = 'btn-remove-image';
                    removeBtn.textContent = '✕';
                    removeBtn.onclick = function () { removeImageRow(this); };
                    addBtn.replaceWith(removeBtn);
                }
            }
            // Append new row as last (with + button)
            const div = document.createElement('div');
            div.innerHTML = buildImageRow(containerId, '', true);
            container.appendChild(div.firstChild);
        }

        function removeImageRow(button) {
            const container = button.closest('.image-inputs-group');
            button.closest('.image-input-row').remove();
            // Ensure last row has + button
            const rows = container.querySelectorAll('.image-input-row');
            if (rows.length > 0) {
                const lastRow = rows[rows.length - 1];
                if (!lastRow.querySelector('.btn-add-image')) {
                    const removeBtn = lastRow.querySelector('.btn-remove-image');
                    if (removeBtn) {
                        const addBtn = document.createElement('button');
                        addBtn.type = 'button';
                        addBtn.className = 'btn-add-image';
                        addBtn.textContent = '+';
                        addBtn.onclick = function () { addImageInput(container.id); };
                        removeBtn.replaceWith(addBtn);
                    }
                }
            }
        }

        // Upload file via AJAX and fill URL input
        function uploadFile(fileInput, containerId) {
            const file = fileInput.files[0];
            if (!file) return;
            const row = fileInput.closest('.image-input-row');
            const urlInput = row.querySelector('.image-input');
            const previewImg = row.querySelector('.img-preview');
            const label = fileInput.closest('.btn-file-upload');

            label.classList.add('uploading');
            label.title = 'กำลังอัปโหลด...';

            const formData = new FormData();
            formData.append('image', file);

            fetch('upload_image.php', {
                method: 'POST',
                body: formData
            })
                .then(res => res.json())
                .then(data => {
                    if (data.url) {
                        urlInput.value = data.url;
                        if (previewImg) {
                            previewImg.src = data.url;
                            previewImg.style.display = 'block';
                        }
                    } else {
                        alert('❌ อัปโหลดล้มเหลว: ' + (data.error || 'ไม่ทราบสาเหตุ'));
                    }
                })
                .catch(() => alert('❌ เกิดข้อผิดพลาดในการอัปโหลด'))
                .finally(() => {
                    label.classList.remove('uploading');
                    label.title = 'เลือกไฟล์จากเครื่อง';
                    fileInput.value = '';
                });
        }

        // Show thumbnail when URL is typed
        function updatePreview(input) {
            const preview = input.closest('.image-input-row').querySelector('.img-preview');
            if (!preview) return;
            const val = input.value.trim();
            if (val) {
                preview.src = val;
                preview.style.display = 'block';
            } else {
                preview.src = '';
                preview.style.display = 'none';
            }
        }

        function removeImageInput(button) {
            button.parentElement.remove();
        }

        function isValidUrl(string) {
            try {
                new URL(string);
                return true;
            } catch (_) {
                return false;
            }
        }

        function collectImageUrls(containerId) {
            const inputs = document.querySelectorAll('#' + containerId + ' .image-input');
            const urls = [];
            inputs.forEach(input => {
                const url = input.value.trim();
                if (url && isValidUrl(url)) {
                    urls.push(url);
                }
            });
            if (urls.length === 0) {
                alert('⚠️ กรุณาใส่ URL ของรูปภาพที่ถูกต้อง (ขึ้นต้นด้วย https:// หรือ http://)');
                return null;
            }
            return urls.join(', ');
        }

        function prepareImages(containerId, hiddenInputId) {
            const urls = collectImageUrls(containerId);
            if (urls === null) {
                return false;
            }
            document.getElementById(hiddenInputId).value = urls;
            return true;
        }

        let currentProductId = 0;
        function showProductDetails(data) {
            const [id, name, details, pictures, price, category] = data;
            currentProductId = id;
            // AJAX ดึงชื่อผู้ขาย
            fetch('user.php?get_seller_name=1&product_id=' + encodeURIComponent(id))
                .then(response => response.text())
                .then(sellerName => {
                    document.getElementById('detailsName').textContent = name;

                    const catContainer = document.getElementById('detailsCategory');
                    catContainer.innerHTML = '';
                    if (category) {
                        category.split(',').forEach(cat => {
                            const span = document.createElement('span');
                            span.className = 'details-category-item';
                            span.textContent = cat.trim();
                            catContainer.appendChild(span);
                        });
                    }

                    document.getElementById('detailsDescription').textContent = details;
                    document.getElementById('detailsPrice').textContent = '฿ ' + price.toLocaleString('th-TH');
                    // เพิ่มชื่อผู้ขาย
                    let sellerDiv = document.getElementById('detailsSeller');
                    if (!sellerDiv) {
                        sellerDiv = document.createElement('div');
                        sellerDiv.id = 'detailsSeller';
                        sellerDiv.className = 'details-seller';
                        document.getElementById('detailsCategory').after(sellerDiv);
                    }

                });
            // Update modal content
            document.getElementById('detailsName').textContent = name;

            const catContainerMain = document.getElementById('detailsCategory');
            catContainerMain.innerHTML = '';
            if (category) {
                category.split(',').forEach(cat => {
                    const span = document.createElement('span');
                    span.className = 'details-category-item';
                    span.textContent = cat.trim();
                    catContainerMain.appendChild(span);
                });
            }

            document.getElementById('detailsDescription').textContent = details;
            document.getElementById('detailsPrice').textContent = '฿ ' + price.toLocaleString('th-TH');

            // Setup gallery
            const galleryInner = document.getElementById('detailsGallery-inner');
            galleryInner.innerHTML = '';
            pictures.split(',').map(url => url.trim()).filter(url => url && isValidUrl(url)).forEach(img => {
                const imgEl = document.createElement('img');
                imgEl.src = img;
                imgEl.alt = name;
                imgEl.loading = 'lazy';
                galleryInner.appendChild(imgEl);
            });

            // Setup navigation if multiple images
            if (pictures.split(',').map(url => url.trim()).filter(url => url && isValidUrl(url)).length > 1) {
                document.getElementById('detailsPrev').style.display = 'block';
                document.getElementById('detailsNext').style.display = 'block';

                const navContainer = document.getElementById('detailsNav');
                navContainer.innerHTML = '';
                for (let i = 0; i < pictures.split(',').map(url => url.trim()).filter(url => url && isValidUrl(url)).length; i++) {
                    const dot = document.createElement('div');
                    dot.className = 'gallery-dot' + (i === 0 ? ' active' : '');
                    dot.onclick = () => goDetailsImage(i);
                    navContainer.appendChild(dot);
                }
                navContainer.style.display = 'flex';
            } else {
                document.getElementById('detailsPrev').style.display = 'none';
                document.getElementById('detailsNext').style.display = 'none';
                document.getElementById('detailsNav').style.display = 'none';
            }

            // Initialize gallery state
            galleryState['detailsGallery'] = { current: 0, total: pictures.split(',').map(url => url.trim()).filter(url => url && isValidUrl(url)).length };
            updateGallery('detailsGallery');

            // Video button in modal
            const videoUrl = data[6];
            const videoBtnContainer = document.getElementById('detailsVideoBtn');
            videoBtnContainer.innerHTML = '';
            if (videoUrl) {
                const vBtn = document.createElement('button');
                vBtn.className = 'btn-video';
                vBtn.style.width = '100%';
                vBtn.style.justifyContent = 'center';
                vBtn.style.marginBottom = '15px';
                vBtn.innerHTML = '📺 ดูวิดีโอตัวอย่างสินค้า';
                vBtn.onclick = () => openVideoModal(videoUrl);
                videoBtnContainer.appendChild(vBtn);
            }

            // Show modal
            document.getElementById('productDetailsModal').classList.add('active');
        }

        function extractYoutubeId(url) {
            const regExp = /^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|\&v=)([^#\&\?]*).*/;
            const match = url.match(regExp);
            return (match && match[2].length === 11) ? match[2] : null;
        }

        function openVideoModal(url) {
            const videoId = extractYoutubeId(url);
            if (!videoId) {
                alert('URL YouTube ไม่ถูกต้อง');
                return;
            }
            const modal = document.getElementById('videoModal');
            const playerContainer = document.getElementById('youtubePlayer');
            playerContainer.innerHTML = `<iframe src="https://www.youtube.com/embed/${videoId}?autoplay=1" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>`;
            modal.classList.add('active');
        }

        function closeVideoModal() {
            const modal = document.getElementById('videoModal');
            const playerContainer = document.getElementById('youtubePlayer');
            playerContainer.innerHTML = '';
            modal.classList.remove('active');
        }

        function changeDetailsImage(direction) {
            changeImage('detailsGallery', direction);
        }

        function goDetailsImage(index) {
            goToImage('detailsGallery', index);
        }

        function closeProductDetails() {
            document.getElementById('productDetailsModal').classList.remove('active');
        }

        function editProduct(id, data) {
            document.getElementById('edit_product_id').value = data[5];
            document.getElementById('edit_name_product').value = data[0];
            document.getElementById('edit_details_product').value = data[1];
            document.getElementById('edit_price_product').value = data[3];

            // Handle multiple categories in edit form
            const categories = data[4] ? data[4].split(',').map(c => c.trim()) : [];
            const checkboxes = document.querySelectorAll('#edit_category_group input[type="checkbox"]');
            checkboxes.forEach(cb => {
                cb.checked = categories.includes(cb.value);
            });
            // เติมจำนวนสินค้า
            if (document.getElementById('edit_ammo_product')) {
                document.getElementById('edit_ammo_product').value = data[6] || 0;
            }
            if (document.getElementById('edit_link_download')) {
                document.getElementById('edit_link_download').value = data[7] || '';
            }
            if (document.getElementById('edit_video_url')) {
                document.getElementById('edit_video_url').value = data[8] || '';
            }

            // Build image inputs for edit
            const imageUrls = data[2].split(',').map(url => url.trim()).filter(url => url);
            const container = document.getElementById('imageInputsEdit');
            container.innerHTML = '';

            if (imageUrls.length === 0) {
                container.innerHTML = buildImageRow('imageInputsEdit', '', true);
            } else {
                imageUrls.forEach((url, index) => {
                    const isLast = index === imageUrls.length - 1;
                    const div = document.createElement('div');
                    div.innerHTML = buildImageRow('imageInputsEdit', url, isLast);
                    container.appendChild(div.firstChild);
                });
            }

            document.getElementById('editModal').classList.add('active');
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.remove('active');
        }

        function deleteProduct(id) {
            if (confirm('คุณแน่ใจหรือว่าต้องการลบสินค้านี้?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = '<input type="hidden" name="product_id" value="' + id + '"><input type="hidden" name="delete_product" value="1">';
                document.body.appendChild(form);
                form.submit();
            }
        }

        function buyProduct(id) {
            if (confirm('ยืนยันที่จะหยิบสินค้านี้ใส่กระเป๋า? ระบบจะทำการหักพอยท์จากบัญชีของคุณ')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = '<input type="hidden" name="buy_product_id" value="' + id + '">';
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Gallery functions
        const galleryState = {};

        function changeImage(galleryId, direction) {
            if (!galleryState[galleryId]) {
                const images = document.querySelectorAll('#' + galleryId + ' .gallery-inner img');
                galleryState[galleryId] = { current: 0, total: images.length };
            }

            const state = galleryState[galleryId];
            state.current = (state.current + direction + state.total) % state.total;
            updateGallery(galleryId);
        }

        function goToImage(galleryId, index) {
            if (!galleryState[galleryId]) {
                const images = document.querySelectorAll('#' + galleryId + ' .gallery-inner img');
                galleryState[galleryId] = { current: 0, total: images.length };
            }

            galleryState[galleryId].current = index;
            updateGallery(galleryId);
        }

        function updateGallery(galleryId) {
            const state = galleryState[galleryId];
            const inner = document.getElementById(galleryId + '-inner');
            const dots = document.querySelectorAll('#' + galleryId + ' .gallery-dot');

            if (inner) {
                inner.style.transform = 'translateX(-' + (state.current * 100) + '%)';
            }

            dots.forEach((dot, index) => {
                dot.classList.toggle('active', index === state.current);
            });
        }

        function filterCategory(category) {
            const cards = document.querySelectorAll('.card');
            cards.forEach(card => {
                const cardCategories = card.dataset.categories ? card.dataset.categories.split(',').map(c => c.trim()) : [];
                if (category === 'All' || cardCategories.includes(category)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }

        document.querySelectorAll('.category-filter button').forEach(button => {
            button.addEventListener('click', () => {
                document.querySelectorAll('.category-filter button').forEach(btn => btn.classList.remove('active'));
                button.classList.add('active');
                filterCategory(button.dataset.category);
            });
        });

        // Close modal when clicking outside
        document.getElementById('addModal').addEventListener('click', function (e) {
            if (e.target === this) {
                closeModal();
            }
        });

        document.getElementById('editModal').addEventListener('click', function (e) {
            if (e.target === this) {
                closeEditModal();
            }
        });

        function toggleUserMenu() {
            const dropdown = document.getElementById('userDropdown');
            if (dropdown) {
                dropdown.classList.toggle('active');
            }
        }

        document.addEventListener('click', function (e) {
            const dropdown = document.getElementById('userDropdown');
            const menuBtn = document.querySelector('.user-menu-btn');
            if (dropdown && menuBtn && !dropdown.contains(e.target) && !menuBtn.contains(e.target)) {
                dropdown.classList.remove('active');
            }
        });

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