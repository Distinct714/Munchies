<?php

class MuffinShop
{
    private array $products;
    private string $stockSessionKey = 'munchies_stock';

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

        if (!isset($_SESSION[$this->stockSessionKey])) {
            $this->resetStock();
        }
    }

    public function getProducts(): array
    {
        return $this->products;
    }

    public function getStock(string $productId): int
    {
        $stock = $_SESSION[$this->stockSessionKey][$productId] ?? 0;

        return max(0, (int) $stock);
    }

    public function processOrder(array $quantities): array
    {
        $errors = [];
        $success = [];
        $total = 0.0;

        foreach ($this->products as $productId => $product) {
            $requested = 0;

            if (isset($quantities[$productId])) {
                $requested = (int) $quantities[$productId];
            }

            if ($requested < 0) {
                $errors[] = 'Quantity for ' . $product['name'] . ' cannot be negative.';
                $requested = 0;
            }

            $availableStock = $this->getStock($productId);

            if ($requested > 0 && $availableStock === 0) {
                $errors[] = $product['name'] . ' is already sold out.';
                continue;
            }

            $purchased = $requested;

            if ($requested > $availableStock) {
                $purchased = $availableStock;
                $errors[] = 'Only ' . $availableStock . ' left for ' . $product['name'] . '. Quantity was adjusted.';
            }

            if ($purchased > 0) {
                $newStock = $availableStock - $purchased;
                $_SESSION[$this->stockSessionKey][$productId] = max(0, $newStock);

                $lineTotal = $purchased * $product['price'];
                $total += $lineTotal;

                $success[] = $purchased . ' x ' . $product['name'];
            }
        }

        if (empty($success) && empty($errors)) {
            $errors[] = 'Please choose at least one muffin to order.';
        }

        return [
            'errors' => $errors,
            'success' => $success,
            'total' => $total,
        ];
    }

    public function getInitialQuantity(string $productId): int
    {
        if (!isset($_POST['qty'][$productId])) {
            return 0;
        }

        return max(0, (int) $_POST['qty'][$productId]);
    }

    public function resetStock(): void
    {
        $_SESSION[$this->stockSessionKey] = [];

        foreach ($this->products as $productId => $product) {
            $_SESSION[$this->stockSessionKey][$productId] = $product['default_stock'];
        }
    }
}
