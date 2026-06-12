<?php require_once __DIR__ . '/product_page_controller.php'; ?>
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
</body>
</html>
