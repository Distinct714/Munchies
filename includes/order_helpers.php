<?php

function munchies_get_payment_code(mysqli $conn, int $paymentCode): ?int
{
    $stmt = mysqli_prepare($conn, 'SELECT payment_code FROM tbl_payment WHERE payment_code = ?');

    if (!$stmt) {
        return null;
    }

    mysqli_stmt_bind_param($stmt, 'i', $paymentCode);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = $result ? mysqli_fetch_assoc($result) : null;
    mysqli_stmt_close($stmt);

    return $row ? (int) $row['payment_code'] : null;
}

function munchies_get_or_create_user_id(mysqli $conn, array $customer): ?int
{
    $firstname = trim($customer['firstname'] ?? '');
    $lastname = trim($customer['lastname'] ?? '');
    $email = trim($customer['email'] ?? '');

    if ($firstname === '' || $lastname === '' || $email === '') {
        return null;
    }

    $stmt = mysqli_prepare($conn, 'SELECT user_id FROM tbl_user WHERE email = ? LIMIT 1');
    if (!$stmt) {
        return null;
    }

    mysqli_stmt_bind_param($stmt, 's', $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = $result ? mysqli_fetch_assoc($result) : null;
    mysqli_stmt_close($stmt);

    if ($row) {
        return (int) $row['user_id'];
    }

    $defaultPassword = password_hash('munchies123', PASSWORD_DEFAULT);
    $stmt = mysqli_prepare($conn, 'INSERT INTO tbl_user (lname, fname, email, user_pass) VALUES (?, ?, ?, ?)');
    if (!$stmt) {
        return null;
    }

    mysqli_stmt_bind_param($stmt, 'ssss', $lastname, $firstname, $email, $defaultPassword);

    if (!mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        return null;
    }

    $userId = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);

    return $userId > 0 ? (int) $userId : null;
}

function munchies_get_or_create_product_code(mysqli $conn, string $productKey, array $product, int $quantity): ?int
{
    $productName = $product['name'] ?? $productKey;
    $description = $product['description'] ?? '';
    $image = $product['image'] ?? '';

    $stmt = mysqli_prepare($conn, 'SELECT product_code FROM tbl_product WHERE product_name = ? LIMIT 1');
    if (!$stmt) {
        return null;
    }

    mysqli_stmt_bind_param($stmt, 's', $productName);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = $result ? mysqli_fetch_assoc($result) : null;
    mysqli_stmt_close($stmt);

    if ($row) {
        return (int) $row['product_code'];
    }

    $stmt = mysqli_prepare($conn, 'INSERT INTO tbl_product (product_name, product_desc, product_image, quantity) VALUES (?, ?, ?, ?)');
    if (!$stmt) {
        return null;
    }

    mysqli_stmt_bind_param($stmt, 'sssi', $productName, $description, $image, $quantity);

    if (!mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        return null;
    }

    $productCode = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);

    return $productCode > 0 ? (int) $productCode : null;
}

function munchies_save_order_to_database(mysqli $conn, array $customer, int $paymentCode, array $cart, array $products): int|false
{
    mysqli_begin_transaction($conn);

    try {
        $userId = munchies_get_or_create_user_id($conn, $customer);
        if (!$userId) {
            throw new RuntimeException('Unable to resolve customer record.');
        }

        $saleDate = date('Y-m-d H:i:s');
        $saleStmt = mysqli_prepare($conn, 'INSERT INTO tbl_sales (user_id, payment_code, sale_date) VALUES (?, ?, ?)');
        if (!$saleStmt) {
            throw new RuntimeException('Unable to create sale record.');
        }

        mysqli_stmt_bind_param($saleStmt, 'iis', $userId, $paymentCode, $saleDate);
        if (!mysqli_stmt_execute($saleStmt)) {
            throw new RuntimeException('Unable to create sale record.');
        }

        $saleId = mysqli_insert_id($conn);
        mysqli_stmt_close($saleStmt);

        foreach ($cart as $productKey => $quantity) {
            $quantity = (int) $quantity;
            if ($quantity <= 0 || !isset($products[$productKey])) {
                continue;
            }

            $productCode = munchies_get_or_create_product_code($conn, $productKey, $products[$productKey], $quantity);
            if (!$productCode) {
                throw new RuntimeException('Unable to save order item.');
            }

            $itemStmt = mysqli_prepare($conn, 'INSERT INTO tbl_sales_item (sale_id, product_code, quantity) VALUES (?, ?, ?)');
            if (!$itemStmt) {
                throw new RuntimeException('Unable to save order item.');
            }

            mysqli_stmt_bind_param($itemStmt, 'iii', $saleId, $productCode, $quantity);
            if (!mysqli_stmt_execute($itemStmt)) {
                throw new RuntimeException('Unable to save order item.');
            }
            mysqli_stmt_close($itemStmt);
        }

        mysqli_commit($conn);
        return $saleId;
    } catch (Throwable $throwable) {
        mysqli_rollback($conn);
        return false;
    }
}
