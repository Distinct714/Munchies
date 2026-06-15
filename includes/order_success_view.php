<?php
session_start();

$orderSuccess = $_SESSION['order_success'] ?? null;
unset($_SESSION['order_success']);
?>

<!DOCTYPE html>

<html lang="en">
    
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Success | Munchies</title>
    <link rel="stylesheet" href="static/style3.css">

    <style>
        .success-shell {
            min-height: 100vh;
            display: grid;
            place-items: center;
            background: #F8F5F2;
        }

        .success-card {
            width: 750px;
            background: #F8F5F2;
            border-radius: 4px;
            padding: 2rem;
            box-shadow: 0px 2px 2px 2px rgba(0, 0, 0, 0.1);
        }

        .success-list {
            padding-left: 1.25rem;
        }

        .success-actions {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            margin-top: 1.5rem;
        }

        .success-actions a {
            text-decoration: none;
        }
    </style>

</head>

<body>

    <main class="success-shell">
        <section class="success-card">
            <?php if (!$orderSuccess): ?>
                <h1>No order data found.</h1>
                <p>Start a new order from fillout page.</p>
                <div class="success-actions">
                    <a href="fillout.php" class="submit-btn">Back to Fill Out</a>
                    <a href="index.php" class="submit-btn">Home</a>
                </div>
            <?php else: ?>
                <h1>Order saved.</h1>
                <p>Record number #<?php echo htmlspecialchars((string) $orderSuccess['sale_id']); ?> locked in.</p>
                <p><strong>Name:</strong> <?php echo htmlspecialchars($orderSuccess['customer_name'] ?? ''); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($orderSuccess['email'] ?? ''); ?></p>
                <p><strong>Payment:</strong> <?php echo htmlspecialchars($orderSuccess['payment_method'] ?? ''); ?></p>

                <h2>Items</h2>
                <ul class="success-list">
                    <?php foreach (($orderSuccess['items'] ?? []) as $item): ?>
                        <li>
                            <?php echo htmlspecialchars($item['name']); ?>
                            x<?php echo (int) $item['quantity']; ?>
                            - ₱<?php echo number_format(((float) $item['price']) * ((int) $item['quantity']), 2); ?>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <p><strong>Total:</strong> ₱<?php echo number_format((float) ($orderSuccess['total'] ?? 0), 2); ?></p>

                <div class="success-actions">
                    <a href="fillout.php" class="submit-btn">New Order</a>
                    <a href="index.php" class="submit-btn">Home</a>
                </div>
            <?php endif; ?>
        </section>
    </main>

</body>

</html>
