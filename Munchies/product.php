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
        </header>

        <section class="product-grid">
            <div class="card">
                <img src="static/assets/blueberry_muffin.png" alt="Blueberry">
                <div class="card-info">
                    <h3>Blueberry Muffin</h3>
                    <p>A golden-domed treat featuring a tender, buttery crumb and pockets of sweet, bursting blueberries.</p>
                    <div class="card-footer">
                        <span class="price">₱99.00</span>
                        <button class="add-btn">Add to cart</button>
                    </div>
                </div>
            </div>

            <div class="card">
                <img src="static/assets/chocolatechip_muffin.jpg" alt="Chocolate">
                <div class="card-info">
                    <h3>Chocolate Chip Muffin</h3>
                    <p>A rich, buttery muffin packed with semi-sweet chocolate chips that create melty pockets of cocoa throughout.</p>
                    <div class="card-footer">
                        <span class="price">₱95.00</span>
                        <button class="add-btn">Add to cart</button>
                    </div>
                </div>
            </div>

            <div class="card">
                <img src="static/assets/banana_muffin.jpg" alt="Banana">
                <div class="card-info">
                    <h3>Banana Muffin</h3>
                    <p>An aromatic and incredibly moist treat made from ripened bananas that provide a dense texture.</p>
                    <div class="card-footer">
                        <span class="price">₱69.00</span>
                        <button class="add-btn">Add to cart</button>
                    </div>
                </div>
            </div>

            <div class="card">
                <img src="static/assets/appleoatmeal_muffins.jpg" alt="Apple">
                <div class="card-info">
                    <h3>Apple with Oatmeal Muffin</h3>
                    <p>A hearty, wholesome muffin blending chewy oats with tender chunks of tart apple for a textured breakfast.</p>
                    <div class="card-footer">
                        <span class="price">₱89.00</span>
                        <button class="add-btn">Add to cart</button>
                    </div>
                </div>
            </div>

            <div class="card">
                <img src="static/assets/cinnamon_muffin.jpg" alt="Cinnamon">
                <div class="card-info">
                    <h3>Cinnamon Muffin</h3>
                    <p>A light and airy spiced muffin featuring a warm cinnamon-sugar swirl and a crisp, fragrant topping.</p>
                    <div class="card-footer">
                        <span class="price">₱75.00</span>
                        <button class="add-btn">Add to cart</button>
                    </div>
                </div>
            </div>

            <div class="card">
                <img src="static/assets/cappuccino_muffin.jpg" alt="Cappuccino">
                <div class="card-info">
                    <h3>Cappuccino Muffin</h3>
                    <p>A bold, sophisticated pastry infused with espresso notes that offer a deep roasted aroma.</p>
                    <div class="card-footer">
                        <span class="price">₱75.00</span>
                        <button class="add-btn">Add to cart</button>
                    </div>
                </div>
            </div>
        </section>

        <section class="checkout-bar">
            <div class="payment-methods">
                <h4>Payment Methods</h4>
                <label><input type="radio" name="pay"> Cash on Delivery</label>
                <label><input type="radio" name="pay"> Online Cash</label>
            </div>
            <div class="total-section">
                <p>Total: <strong>₱0.00</strong></p>
                <a href="index.php"><button class="submit-btn">Submit</button></a>
            </div>
        </section>
    </main>

    <footer>
        @2026 Munchies. All rights reserved.
    </footer>

</body>
</html>