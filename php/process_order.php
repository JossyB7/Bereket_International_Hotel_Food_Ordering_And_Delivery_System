<?php
session_start();
require_once __DIR__ . '/config.php';

if (!isset($_SESSION['user_id']) || empty($_SESSION['cart'])) {
    header('Location: ../index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = getDBConnection();

    $userId = $_SESSION['user_id'];
    $customerName = sanitizeInput($_POST['full_name']);
    $customerEmail = $_SESSION['user_email'] ?? '';
    $phone = sanitizeInput($_POST['phone']);
    $address = sanitizeInput($_POST['address']);
    $notes = sanitizeInput($_POST['notes']);

    $total = (float)$_POST['total_amount'];
    $deliveryFee = (float)DELIVERY_FEE; 
    if ($total >= FREE_DELIVERY_THRESHOLD) { $deliveryFee = 0; }
    $subtotal = $total - $deliveryFee;

    $screenshotName = '';
    if (isset($_FILES['payment_screenshot']) && $_FILES['payment_screenshot']['error'] === 0) {
        $ext = pathinfo($_FILES['payment_screenshot']['name'], PATHINFO_EXTENSION);
        $screenshotName = "PAY_" . time() . "_" . uniqid() . "." . $ext;
        $targetPath = UPLOAD_PATH . 'payments' . DIRECTORY_SEPARATOR . $screenshotName;

        move_uploaded_file($_FILES['payment_screenshot']['tmp_name'], $targetPath);
    }

    $orderNumber = "BRK-" . strtoupper(substr(uniqid(), -8));

    $stmt = $conn->prepare("INSERT INTO orders (order_number, customer_name, customer_email, customer_phone, delivery_address, order_notes, subtotal, delivery_fee, total, payment_screenshot, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");

    $stmt->bind_param("ssssssddds", $orderNumber, $customerName, $customerEmail, $phone, $address, $notes, $subtotal, $deliveryFee, $total, $screenshotName);

    if ($stmt->execute()) {
        $orderId = $conn->insert_id;

        $itemStmt = $conn->prepare("INSERT INTO order_items (order_id, item_name, item_price, quantity, subtotal) VALUES (?, ?, ?, ?, ?)");

        foreach ($_SESSION['cart'] as $item) {
            $itemSub = $item['price'] * $item['quantity'];
            $itemStmt->bind_param("isdid", $orderId, $item['name'], $item['price'], $item['quantity'], $itemSub);
            $itemStmt->execute();
        }

        unset($_SESSION['cart']);
        header("Location: ../order_success.php?id=" . $orderNumber);
        exit;
    } else {
        die("Order Error: " . $conn->error);
    }
}