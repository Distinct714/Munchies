<?php // PHP BACKEND FOR HANDLING MUNCHIES LOGIC SYSTEM

session_start(); // Starts the session to track the cart and user data.

// Includes the external class file that manages product data and order logic.
require_once __DIR__ . '/classes/MuffinShop.php';

// Creates an instance of the MuffinShop class.
$shop = new MuffinShop();

// Retrieves the list of muffins (name, price, etc.) from MuffinShop().
$products = $shop->getProducts();

// COOKIE & SESSION LOGIC
$customerName = $_SESSION['munchies_customer_name'] ?? ($_COOKIE['munchies_user'] ?? '');

// Initialize cart and order results
if (!isset($_SESSION['cart'])) { $_SESSION['cart'] = []; }
$orderResult = ['errors' => [], 'success' => [], 'total' => 0];

// This block runs only when a user clicks a button (Add, Remove, or Submit).
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1. CAPTURING CUSTOMER NAME & SETTING COOKIES
    $firstName = trim($_POST['firstname'] ?? '');
    $lastName = trim($_POST['lastname'] ?? '');
    
    if ($firstName !== '' || $lastName !== '') {
        $customerName = trim($firstName . ' ' . $lastName);
        $_SESSION['munchies_customer_name'] = $customerName;
        setcookie('munchies_user', $customerName, time() + 60, "/"); 
    }
    
    // 2. ADD TO CART LOGIC
    if (isset($_POST['add_to_cart'])) {
        $productId = $_POST['add_to_cart'];
        $qty = (int)($_POST['qty'][$productId] ?? 0);
        if ($qty > 0) {
            $_SESSION['cart'][$productId] = $qty;
        }
    }

    // 3. REMOVE ITEM LOGIC
    if (isset($_POST['remove_item'])) {
        $removeId = $_POST['remove_item'];
        unset($_SESSION['cart'][$removeId]);
    }

    // 4. FINAL ORDER SUBMISSION
    if (isset($_POST['final_submit'])) {
        $paymentMethod = $_POST['pay'] ?? '';
        
        // Check 1: Validate payment method
        if ($paymentMethod === '') {
            $orderResult['errors'][] = 'Please select a payment method before submitting.';
        } 
        
        // Check 2: Validate that the cart is not empty
        if (empty($_SESSION['cart'])) {
            $orderResult['errors'][] = 'Your cart is empty. Please add at least one muffin.';
        }

        // Only proceed to process the order if the above checks passed
        if (empty($orderResult['errors'])) {
            $process = $shop->processOrder($_SESSION['cart']);
            
            if (!empty($process['errors'])) {
                // If there are stock issues, show them on the current page
                $orderResult['errors'] = array_merge($orderResult['errors'], $process['errors']);
            } 
            else {
                 // Clear the cart
                $_SESSION['cart'] = [];
                
                // Clear the cookie after a successful order 
                setcookie('munchies_user', '', time() - 60, "/");

                // Redirect to homepage after order successful
                header("Location: index.php");
                exit(); 
            }
        }
    }
}

// Calculate and display running total price shown in the checkout bar.
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
            <p>Hey there! Grab whatever hits the spot and fits exactly what you want right now.</p>
        </header>


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
                        <label><input type="radio" name="pay" value="cod"> COD</label>
                        <label><input type="radio" name="pay" value="online"> Online</label>
                    </div>
                </div>

                <div class="total-section">
                    <p>Total: <strong>₱<?php echo number_format((float) $orderResult['total'], 2); ?></strong></p>
                    <button type="submit" name="final_submit" class="submit-btn">Submit Order</button>
                </div>
            </section>
        </form>
    </main>

    <footer>
        @2026 Munchies. All rights reserved.
    </footer>
</body>
</html>
