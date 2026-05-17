<!-- MUFFIN CLASS -->

<?php 
// Manages muffin products, session-based stock, and order processing.
class MuffinShop
{
    private array $products;
    private string $stockSessionKey = 'munchies_stock';

    // Sets up product list and initializes stock if it doesn't exist.
    public function __construct()
    {
        $this->products = [
            'blueberry' => [
                'name' => 'Blueberry Muffin',
                'description' => 'A golden-domed treat featuring a tender, buttery crumb and pockets of sweet, bursting blueberries.',
                'price' => 99.00,
                'image' => 'static/assets/blueberry_muffin.png',
                'alt' => 'Blueberry Muffin',
                'default_stock' => 50,
            ],
            'chocolatechip' => [
                'name' => 'Chocolate Chip Muffin',
                'description' => 'A rich, buttery muffin packed with semi-sweet chocolate chips that create melty pockets of cocoa throughout.',
                'price' => 95.00,
                'image' => 'static/assets/chocolatechip_muffin.jpg',
                'alt' => 'Chocolate Chip Muffin',
                'default_stock' => 50,
            ],
            'banana' => [
                'name' => 'Banana Muffin',
                'description' => 'An aromatic and incredibly moist treat made from ripened bananas that provide a dense texture.',
                'price' => 69.00,
                'image' => 'static/assets/banana_muffin.jpg',
                'alt' => 'Banana Muffin',
                'default_stock' => 50,
            ],
            'apple_oatmeal' => [
                'name' => 'Apple with Oatmeal Muffin',
                'description' => 'A hearty, wholesome muffin blending chewy oats with tender chunks of tart apple for a textured breakfast.',
                'price' => 89.00,
                'image' => 'static/assets/appleoatmeal_muffins.jpg',
                'alt' => 'Apple with Oatmeal Muffin',
                'default_stock' => 50,
            ],
            'cinnamon' => [
                'name' => 'Cinnamon Muffin',
                'description' => 'A light and airy spiced muffin featuring a warm cinnamon-sugar swirl and a crisp, fragrant topping.',
                'price' => 75.00,
                'image' => 'static/assets/cinnamon_muffin.jpg',
                'alt' => 'Cinnamon Muffin',
                'default_stock' => 50,
            ],
            'cappuccino' => [
                'name' => 'Cappuccino Muffin',
                'description' => 'A bold, sophisticated pastry infused with espresso notes that offer a deep roasted aroma.',
                'price' => 75.00,
                'image' => 'static/assets/cappuccino_muffin.jpg',
                'alt' => 'Cappuccino Muffin',
                'default_stock' => 50,
            ],
        ];
        // If stock isn't in the session, set it to defaults
        if (!isset($_SESSION[$this->stockSessionKey])) {
            $this->resetStock();
        }
    }
    // Validates and sets class properties. Prevents negative values for price or stock.
    public function __set($name, $value) {
        if ($name == "price" && $value < 0) {
            echo "<p>Invalid price set</p>\n";
        } 
        elseif (($name == "inventory" || $name == "stock") && $value < 0) {
            echo "<p>Invalid inventory set: $value</p>\n";
        } 
        else {
            $this->$name = $value;
        }
    }
    // Runs when the object is copied. Resets price and stock in the product array.
    public function __clone() {
        if (isset($this->products)) {
            foreach ($this->products as &$product) {
                $product['price'] = 0;
                $product['default_stock'] = 0;
            }
        }
    }
    // Allows reading of protected or private class properties.
    public function __get($name) {
        return $this->$name;
    }
    // Returns a simple text summary when the object is used as a string.
    public function __toString() {
        return "MuffinShop Object: Managing " . count($this->products) . " products.";
    }
    // Returns the product list for display.
    public function getProducts(): array {
        return $this->products;
    }
    // Gets current stock for a specific muffin from the session.
    public function getStock(string $productId): int {
        $stock = $_SESSION[$this->stockSessionKey][$productId] ?? 0;
        return max(0, (int) $stock);
    }
    // Checks stock, calculates total price, and updates the session stock.
    public function processOrder(array $quantities): array {
        $errors = [];
        $success = [];
        $total = 0.0;

        foreach ($this->products as $productId => $product) {
            $requested = 0;

            if (isset($quantities[$productId])) {
                $requested = (int) $quantities[$productId];
            }
            // // Prevent negative orders
            if ($requested < 0) {
                $errors[] = 'Quantity for ' . $product['name'] . ' cannot be negative.';
                $requested = 0;
            }
            // Check if item is sold out
            $availableStock = $this->getStock($productId);

            if ($requested > 0 && $availableStock === 0) {
                $errors[] = $product['name'] . ' is already sold out.';
                continue;
            }
            // Adjust order if request is more than available stock
            $purchased = $requested;

            if ($requested > $availableStock) {
                $purchased = $availableStock;
                $errors[] = 'Only ' . $availableStock . ' left for ' . $product['name'] . '. Quantity was adjusted.';
            }
            // If buying, deduct from session stock and add to total
            if ($purchased > 0) {
                $newStock = $availableStock - $purchased;
                $_SESSION[$this->stockSessionKey][$productId] = max(0, $newStock);

                $lineTotal = $purchased * $product['price'];
                $total += $lineTotal;

                $success[] = $purchased . ' x ' . $product['name'];
            }
        }
        // Error if nothing was chosen
        if (empty($success) && empty($errors)) {
            $errors[] = 'Please choose at least one muffin to order.';
        }
        return [
            'errors' => $errors,
            'success' => $success,
            'total' => $total,
        ];
    }
    // Gets the initial value for the quantity input fields.
    public function getInitialQuantity(string $productId): int{
        if (!isset($_POST['qty'][$productId])) {
            return 0;
        }
        return max(0, (int) $_POST['qty'][$productId]);
    }
    // Resets the session stock back to the original default amounts.
    public function resetStock(): void {
        $_SESSION[$this->stockSessionKey] = [];

        foreach ($this->products as $productId => $product) {
            $_SESSION[$this->stockSessionKey][$productId] = $product['default_stock'];
        }
    }
}
