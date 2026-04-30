<?php
session_start();

require_once __DIR__ . '/classes/MuffinShop.php';

$shop = new MuffinShop();
$products = $shop->getProducts();
$customerName = $_SESSION['munchies_customer_name'] ?? '';
$orderResult = [
    'errors' => [],
    'success' => [],
    'total' => 0,
];

if (!empty($_POST)) {
    $firstName = trim($_POST['firstname'] ?? '');
    $lastName = trim($_POST['lastname'] ?? '');
    $customerName = trim($firstName . ' ' . $lastName);

    if ($customerName !== '') {
        $_SESSION['munchies_customer_name'] = $customerName;
    }

    $hasQuantityData = isset($_POST['qty']) && is_array($_POST['qty']);

    if ($hasQuantityData) {
        $paymentMethod = $_POST['pay'] ?? '';

        if ($paymentMethod === '') {
            $orderResult['errors'][] = 'Please select a payment method.';
        } else {
            $orderResult = $shop->processOrder($_POST['qty']);
        }
    }
}

$paymentMethod = $_POST['pay'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buy - Munchies</title>
    <link rel="stylesheet" href="static/style3.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>

    <nav class="navbar">
        <div class="logo">Munchies</div>
        <ul class="nav-links">
            <li><a href="index.php">Home</a></li>
            <li><a href="fillout.php">Product</a></li>
            <li><a href="#">About</a></li>
        </ul>
    </nav>

    <main class="container">
        <header class="page-header">
            <h1>What would you like to buy?</h1>
            <p>Hey there! The floor is yours—grab whatever hits the spot and fits exactly what you want right now.</p>
            <?php if ($customerName !== ''): ?>
                <div class="customer-note">Hello, <?php echo htmlspecialchars($customerName); ?>. Your order form is ready.</div>
            <?php endif; ?>
        </header>

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
                <p>Total: <strong>₱<?php echo number_format((float) $orderResult['total'], 2); ?></strong></p>
            </section>
        <?php endif; ?>

        <form action="product.php" method="POST">
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
                                <span class="stock-badge <?php echo $stock === 0 ? 'empty' : ''; ?>">
                                    Stock: <?php echo $stock; ?>
                                </span>
                            </div>

                            <div class="quantity-row">
                                <label for="qty-<?php echo htmlspecialchars($productId); ?>">Quantity</label>
                                <input
                                    type="number"
                                    id="qty-<?php echo htmlspecialchars($productId); ?>"
                                    name="qty[<?php echo htmlspecialchars($productId); ?>]"
                                    min="0"
                                    max="<?php echo $stock; ?>"
                                    value="<?php echo $shop->getInitialQuantity($productId); ?>"
                                    <?php echo $stock === 0 ? 'disabled' : ''; ?>
                                >
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </section>

            <section class="checkout-bar">
                <div class="payment-methods">
                    <h4>Payment Methods</h4>
                    <label><input type="radio" name="pay" value="cod" <?php echo $paymentMethod === 'cod' ? 'checked' : ''; ?>> Cash on Delivery</label>
                    <label><input type="radio" name="pay" value="online" <?php echo $paymentMethod === 'online' ? 'checked' : ''; ?>> Online Cash</label>
                </div>
                <div class="total-section">
                    <p>Total: <strong>₱<?php echo number_format((float) $orderResult['total'], 2); ?></strong></p>
                    <button type="submit" class="submit-btn">Submit</button>
                </div>
            </section>
        </form>
    </main>

    <footer>
        @2026 Munchies. All rights reserved.
    </footer>

</body>
</html>