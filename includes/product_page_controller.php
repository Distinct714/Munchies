<?php

session_start();

require_once __DIR__ . '/../database/db_connect.php';
require_once __DIR__ . '/../classes/MuffinShop.php';
require_once __DIR__ . '/order_helpers.php';

if (!isset($conn) || !$conn) {
    die('Database connection not available.');
}

$timeoutDuration = 60;
$currentTime = time();

if (isset($_SESSION['last_activity'])) {
    $timePassed = $currentTime - $_SESSION['last_activity'];
    if ($timePassed > $timeoutDuration) {
        $_SESSION = [];
        session_destroy();
        session_start();
    }
}
$_SESSION['last_activity'] = $currentTime;

$shop = new MuffinShop();
$products = $shop->getProducts();

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$orderResult = ['errors' => [], 'success' => [], 'total' => 0];
$customer = $_SESSION['munchies_customer'] ?? [];
$customerName = $_SESSION['munchies_customer_name'] ?? trim(($customer['firstname'] ?? '') . ' ' . ($customer['lastname'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['clear_cart'])) {
        $_SESSION['cart'] = [];
        http_response_code(204);
        exit();
    }

    if (isset($_POST['add_to_cart'])) {
        $productId = $_POST['add_to_cart'];
        $qty = (int) ($_POST['qty'][$productId] ?? 0);

        if ($qty > 0) {
            $_SESSION['cart'][$productId] = $qty;
        }
    }

    if (isset($_POST['remove_item'])) {
        unset($_SESSION['cart'][$_POST['remove_item']]);
    }

    if (isset($_POST['final_submit'])) {
        $paymentCode = (int) ($_POST['pay'] ?? 0);

        if (empty($customer)) {
            $orderResult['errors'][] = 'Please fill out customer details first.';
        }

        if ($paymentCode <= 0) {
            $orderResult['errors'][] = 'Please select a payment method before submitting.';
        }

        if (empty($_SESSION['cart'])) {
            $orderResult['errors'][] = 'Your cart is empty. Please add at least one muffin.';
        }

        if (empty($orderResult['errors'])) {
            $stockSnapshot = $_SESSION['munchies_stock'] ?? [];
            $process = $shop->processOrder($_SESSION['cart']);

            if (!empty($process['errors'])) {
                $orderResult['errors'] = array_merge($orderResult['errors'], $process['errors']);
                $_SESSION['munchies_stock'] = $stockSnapshot;
            } else {
                $validPaymentCode = munchies_get_payment_code($conn, $paymentCode);

                if (!$validPaymentCode) {
                    $_SESSION['munchies_stock'] = $stockSnapshot;
                    $orderResult['errors'][] = 'Invalid payment method selected.';
                } else {
                    $saleId = munchies_save_order_to_database($conn, $customer, $validPaymentCode, $_SESSION['cart'], $products);

                    if (!$saleId) {
                        $_SESSION['munchies_stock'] = $stockSnapshot;
                        $orderResult['errors'][] = 'Order was not saved to database.';
                    } else {
                        $paymentLabelMap = [1 => 'Cash', 2 => 'GCash', 3 => 'Credit Card', 4 => 'Debit Card', 5 => 'PayPal'];
                        $orderSummaryItems = [];

                        foreach ($_SESSION['cart'] as $productId => $quantity) {
                            if (!isset($products[$productId])) {
                                continue;
                            }

                            $orderSummaryItems[] = [
                                'name' => $products[$productId]['name'],
                                'quantity' => (int) $quantity,
                                'price' => (float) $products[$productId]['price'],
                            ];
                        }

                        $_SESSION['order_success'] = [
                            'sale_id' => $saleId,
                            'customer_name' => $customerName,
                            'email' => $customer['email'] ?? '',
                            'payment_method' => $paymentLabelMap[$validPaymentCode] ?? 'Unknown',
                            'items' => $orderSummaryItems,
                            'total' => array_reduce($orderSummaryItems, function (float $sum, array $item): float {
                                return $sum + ($item['price'] * $item['quantity']);
                            }, 0.0),
                        ];

                        $_SESSION['cart'] = [];
                        header('Location: order_success.php');
                        exit();
                    }
                }
            }
        }
    }
}

foreach ($_SESSION['cart'] as $id => $qty) {
    if (isset($products[$id])) {
        $orderResult['total'] += ($products[$id]['price'] * $qty);
    }
}
