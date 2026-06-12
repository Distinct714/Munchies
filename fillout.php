<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstname = trim($_POST['firstname'] ?? '');
    $lastname = trim($_POST['lastname'] ?? '');
    $email = trim($_POST['email'] ?? '');

    if ($firstname !== '' && $lastname !== '' && $email !== '') {
        $_SESSION['munchies_customer'] = [
            'firstname' => $firstname,
            'lastname' => $lastname,
            'email' => $email,
        ];
        $_SESSION['munchies_customer_name'] = trim($firstname . ' ' . $lastname);
    }

    header('Location: product.php');
    exit();
}

$savedCustomer = $_SESSION['munchies_customer'] ?? ['firstname' => '', 'lastname' => '', 'email' => ''];
?>

<!DOCTYPE html>

<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fill Out | Munchies</title>
    <link rel="stylesheet" href="static/style2.css">
</head>

    <body class="product-page">

        <nav class="navbar">
            <div class="logo_name">Munchies</div>
            <ul class="nav-links">
                <li><a href="index.php">Home</a></li>
                <li><a href="fillout.php">Product</a></li>
                <li><a href="#">About</a></li>
            </ul>
        </nav>

        <main class="split-container">
            <div class="image-section">
                <img src="static/assets/muffins2.jpg" alt="Muffins Grid">
            </div>

            <div class="form-section">
                <div class="form-wrapper">
                    <h1>Welcome to Munchies!</h1>
                    <p class="subtitle">Fill out the form below to order:</p>

                    <form action="fillout.php" method="POST">
                        <div class="form-group">
                            <label>First Name:</label>
                            <input type="text" name="firstname" placeholder="Fill your first name here" value="<?php echo htmlspecialchars($savedCustomer['firstname']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label>Last Name:</label>
                            <input type="text" name="lastname" placeholder="Fill your last name here" value="<?php echo htmlspecialchars($savedCustomer['lastname']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label>Email:</label>
                            <input type="email" name="email" placeholder="Fill your email here" value="<?php echo htmlspecialchars($savedCustomer['email']); ?>" required>
                        </div>

                        <button type="submit" class="submit-btn">Enter</button>
                    </form>
                </div>
            </div>
        </main>

        <script>
            // Redirect to home after 60 seconds
            setTimeout(function() {
                window.location.href = 'index.php';
            }, 60000);
        </script>

    </body>
</html>
