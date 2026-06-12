<?php require_once __DIR__ . '/includes/product_page_view.php'; __halt_compiler();

function getPaymentCode(mysqli $conn, int $paymentCode): ?int
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

function getOrCreateUserId(mysqli $conn, array $customer): ?int
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

function getOrCreateProductCode(mysqli $conn, string $productKey, array $product, int $quantity): ?int
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

function saveOrderToDatabase(mysqli $conn, array $customer, int $paymentCode, array $cart, array $products): int|false
{
    mysqli_begin_transaction($conn);

    try {
        $userId = getOrCreateUserId($conn, $customer);
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

            $productCode = getOrCreateProductCode($conn, $productKey, $products[$productKey], $quantity);
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
                $validPaymentCode = getPaymentCode($conn, $paymentCode);

                if (!$validPaymentCode) {
                    $_SESSION['munchies_stock'] = $stockSnapshot;
                    $orderResult['errors'][] = 'Invalid payment method selected.';
                } elseif (!saveOrderToDatabase($conn, $customer, $validPaymentCode, $_SESSION['cart'], $products)) {
                    $_SESSION['munchies_stock'] = $stockSnapshot;
                    $orderResult['errors'][] = 'Order was not saved to database.';
                } else {
                    $_SESSION['cart'] = [];
                    header('Location: index.php');
                    exit();
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
?>
<!DOCTYPE html>

<html lang="en">

<head>
    mysqli_begin_transaction($conn);

    try {
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

            $productCode = getOrCreateProductCode($conn, $productKey, $products[$productKey], $quantity);
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
        return true;
    } catch (Throwable $throwable) {
        mysqli_rollback($conn);
        return false;
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
                $validPaymentCode = getPaymentCode($conn, $paymentCode);

                if (!$validPaymentCode) {
                    $_SESSION['munchies_stock'] = $stockSnapshot;
                    $orderResult['errors'][] = 'Invalid payment method selected.';
                } else {
                    $saleId = saveOrderToDatabase($conn, $customer, $validPaymentCode, $_SESSION['cart'], $products);

                    if (!$saleId) {
                    $_SESSION['munchies_stock'] = $stockSnapshot;
                    $orderResult['errors'][] = 'Order was not saved to database.';
                    } else {
                    $paymentLabelMap = [
                        1 => 'Cash',
                        2 => 'GCash',
                        3 => 'Credit Card',
                        4 => 'Debit Card',
                        5 => 'PayPal',
                    ];

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order | Munchies</title>
    <link rel="stylesheet" href="static/style3.css">
</head>
<body>
    <nav class="navbar">
        <div class="logo_name">Munchies</div>
        <ul class="nav-links">
            <li><a href="index.php">Home</a></li>
            <li><a href="fillout.php">Product</a></li>
            <li><a href="#">About</a></li>
        </ul>
    </nav>

    <main class="container">
        <header class="page-header">
            <h1>What would you like to buy?</h1>
            <p>Hey there! Grab whatever hits the spot and fits exactly what you want right now.</p>
        </header>

        <form action="product.php" method="POST" id="order-form">
            <section class="product-grid">
                <?php foreach ($products as $productId => $product): ?>
                    <?php $stock = $shop->getStock($productId); ?>
                    <div class="card <?php echo $stock === 0 ? 'sold-out' : ''; ?>">
                        <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['alt']); ?>">
                        <div class="card-info">
                            <div>
                                <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                                <p><?php echo htmlspecialchars($product['description']); ?></p>
                            </div>

                            <div class="stock-row">
                                <span class="price">₱<?php echo number_format((float) $product['price'], 2); ?></span>
                                <span class="stock-badge <?php echo $stock === 0 ? 'empty' : ''; ?>">Stock: <?php echo $stock; ?></span>
                            </div>

                            <div class="quantity-row">
                                <label for="qty-<?php echo htmlspecialchars($productId); ?>">Qty</label>
                                <input
                                    type="number"
                                    id="qty-<?php echo htmlspecialchars($productId); ?>"
                                    name="qty[<?php echo htmlspecialchars($productId); ?>]"
                                    min="0"
                                    max="<?php echo $stock; ?>"
                                    value="<?php echo $_SESSION['cart'][$productId] ?? $shop->getInitialQuantity($productId); ?>"
                                    <?php echo $stock === 0 ? 'disabled' : ''; ?>
                                >
                                <button type="submit" name="add_to_cart" value="<?php echo $productId; ?>" class="add-btn" <?php echo $stock === 0 ? 'disabled' : ''; ?>>
                                    Add to Cart
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </section>

            <section class="checkout-bar">
                <div class="cart-summary">
                    <?php if ($customerName !== ''): ?>
                        <div class="customer-note">Ordering as: <?php echo htmlspecialchars($customerName); ?></div>
                    <?php endif; ?>

                    <?php if (!empty($orderResult['errors'])): ?>
                        <section class="message-box error-box">
                            <h4>Please check your order</h4>
                            <ul>
                                <?php foreach ($orderResult['errors'] as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </section>
                    <?php endif; ?>

                    <?php if (!empty($orderResult['success'])): ?>
                        <section class="message-box success-box">
                            <h4>Order updated</h4>
                            <p><?php echo htmlspecialchars(implode(', ', $orderResult['success'])); ?></p>
                        </section>
                    <?php endif; ?>

                    <h4>Your Cart</h4>
                    <?php if (empty($_SESSION['cart'])): ?>
                        <p class="empty-msg">No items added yet.</p>
                    <?php else: ?>
                        <ul class="cart-list">
                            <?php foreach ($_SESSION['cart'] as $id => $qty): ?>
                                <li>
                                    <span><?php echo htmlspecialchars($products[$id]['name']); ?> (x<?php echo $qty; ?>)</span>
                                    <button type="submit" name="remove_item" value="<?php echo $id; ?>" class="remove-btn">Remove</button>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>

                    <div class="payment-methods">
                        <h4>Select Payment Method:</h4>
                        <label><input type="radio" name="pay" value="1"> Cash</label>
                        <label><input type="radio" name="pay" value="2"> GCash</label>
                        <label><input type="radio" name="pay" value="3"> Credit Card</label>
                        <label><input type="radio" name="pay" value="4"> Debit Card</label>
                        <label><input type="radio" name="pay" value="5"> PayPal</label>
                    </div>
                </div>

                <div class="total-section">
                    <p>Total: <strong>₱<?php echo number_format((float) $orderResult['total'], 2); ?></strong></p>
                    <button type="submit" name="final_submit" class="submit-btn">Submit Order</button>
                </div>
            </section>
        </form>
    </main>

    <script>
        const form = document.getElementById('order-form');
        let submittingForm = false;

        if (form) {
            form.addEventListener('submit', function () {
                submittingForm = true;
            });
        }

        window.addEventListener('pagehide', function () {
            if (submittingForm) {
                return;
            }

            const data = new FormData();
            data.append('clear_cart', '1');
            navigator.sendBeacon('product.php', data);
        });
    </script>

    <footer>
        @2026 Munchies. All rights reserved.
    </footer>
</body>
</html>
