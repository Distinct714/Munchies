<?php

session_start();

require_once __DIR__ . '/database/db_connect.php';

if (!isset($conn) || !$conn) {
    die('Database connection not available.');
}

function munchies_admin_flash(string $message): void
{
    $_SESSION['admin_flash'] = $message;
}

function munchies_admin_clean_string(?string $value): string
{
    return trim((string) $value);
}

function munchies_admin_clean_price($value): float
{
    return round((float) $value, 2);
}

function munchies_admin_clean_quantity($value): int
{
    return max(0, (int) $value);
}

function munchies_admin_get_product(mysqli $conn, int $productCode): ?array
{
    $stmt = mysqli_prepare($conn, 'SELECT product_code, product_name, product_desc, product_image, price, quantity FROM tbl_product WHERE product_code = ? LIMIT 1');

    if (!$stmt) {
        return null;
    }

    mysqli_stmt_bind_param($stmt, 'i', $productCode);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = $result ? mysqli_fetch_assoc($result) : null;
    mysqli_stmt_close($stmt);

    return $row ?: null;
}

function munchies_admin_redirect(string $query = ''): void
{
    $target = 'admin.php';

    if ($query !== '') {
        $target .= '?' . $query;
    }

    header('Location: ' . $target);
    exit();
}

$flashMessage = $_SESSION['admin_flash'] ?? '';
unset($_SESSION['admin_flash']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_product') {
        $name = munchies_admin_clean_string($_POST['product_name'] ?? '');
        $description = munchies_admin_clean_string($_POST['product_desc'] ?? '');
        $image = munchies_admin_clean_string($_POST['product_image'] ?? '');
        $price = munchies_admin_clean_price($_POST['price'] ?? 0);
        $quantity = munchies_admin_clean_quantity($_POST['quantity'] ?? 0);

        if ($name === '' || $description === '' || $image === '') {
            munchies_admin_flash('All product fields are required.');
            munchies_admin_redirect();
        }

            $stmt = mysqli_prepare($conn, 'INSERT INTO tbl_product (product_name, product_desc, product_image, price, quantity) VALUES (?, ?, ?, ?, ?)');

        mysqli_stmt_close($stmt);
        munchies_admin_flash('Product added successfully.');
        munchies_admin_redirect();
    }

    if ($action === 'update_product') {
        $productCode = (int) ($_POST['product_code'] ?? 0);
        $name = munchies_admin_clean_string($_POST['product_name'] ?? '');
        $description = munchies_admin_clean_string($_POST['product_desc'] ?? '');
        $image = munchies_admin_clean_string($_POST['product_image'] ?? '');
        $price = munchies_admin_clean_price($_POST['price'] ?? 0);
        $quantity = munchies_admin_clean_quantity($_POST['quantity'] ?? 0);

        if ($productCode <= 0 || $name === '' || $description === '' || $image === '') {
            munchies_admin_flash('Fill in every field before updating the product.');
            munchies_admin_redirect(isset($_POST['product_code']) ? 'edit=' . $productCode : '');
        }

        $stmt = mysqli_prepare($conn, 'UPDATE tbl_product SET product_name = ?, product_desc = ?, product_image = ?, price = ?, quantity = ? WHERE product_code = ?');

        if (!$stmt) {
            die('Unable to prepare update query: ' . mysqli_error($conn));
        }

        mysqli_stmt_bind_param($stmt, 'sssdii', $name, $description, $image, $price, $quantity, $productCode);

        if (!mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            munchies_admin_flash('Unable to update the product.');
            munchies_admin_redirect('edit=' . $productCode);
        }

        mysqli_stmt_close($stmt);
        munchies_admin_flash('Product updated successfully.');
        munchies_admin_redirect('edit=' . $productCode);
    }

    if ($action === 'delete_product') {
        $productCode = (int) ($_POST['product_code'] ?? 0);

        if ($productCode <= 0) {
            munchies_admin_flash('Invalid product selected for deletion.');
            munchies_admin_redirect();
        }

        $stmt = mysqli_prepare($conn, 'DELETE FROM tbl_product WHERE product_code = ?');

        if (!$stmt) {
            die('Unable to prepare delete query: ' . mysqli_error($conn));
        }

        mysqli_stmt_bind_param($stmt, 'i', $productCode);

        if (!mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            munchies_admin_flash('Unable to delete the product.');
            munchies_admin_redirect();
        }

        mysqli_stmt_close($stmt);
        munchies_admin_flash('Product deleted successfully.');
        munchies_admin_redirect();
    }
}

$editProduct = null;
if (isset($_GET['edit'])) {
    $editProduct = munchies_admin_get_product($conn, (int) $_GET['edit']);
}

$productRows = [];
$result = mysqli_query($conn, 'SELECT product_code, product_name, product_desc, product_image, price, quantity FROM tbl_product ORDER BY product_name ASC');

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $productRows[] = $row;
    }

    mysqli_free_result($result);
}

$formAction = $editProduct ? 'update_product' : 'add_product';
$pageTitle = $editProduct ? 'Edit Product' : 'Add Product';
$submitLabel = $editProduct ? 'Update Product' : 'Add Product';
$formValues = [
    'product_code' => $editProduct['product_code'] ?? '',
    'product_name' => $editProduct['product_name'] ?? '',
    'product_desc' => $editProduct['product_desc'] ?? '',
    'product_image' => $editProduct['product_image'] ?? '',
    'price' => $editProduct['price'] ?? '',
    'quantity' => $editProduct['quantity'] ?? '',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin | Munchies</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 24px;
            background: #f6f3ee;
            color: #222;
        }
        .wrap {
            max-width: 1100px;
            margin: 0 auto;
        }
        h1, h2 {
            margin: 0 0 12px;
        }
        .card {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 20px;
        }
        .flash {
            padding: 12px 14px;
            border-radius: 6px;
            margin-bottom: 16px;
            background: #eef6ff;
            border: 1px solid #c7defd;
        }
        form {
            display: grid;
            gap: 12px;
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 12px;
        }
        label {
            display: grid;
            gap: 6px;
            font-size: 14px;
        }
        input, textarea {
            width: 100%;
            box-sizing: border-box;
            padding: 10px 12px;
            border: 1px solid #bbb;
            border-radius: 6px;
            font: inherit;
            background: #fff;
        }
        textarea {
            min-height: 100px;
            resize: vertical;
        }
        button, .link-btn {
            display: inline-block;
            border: 0;
            border-radius: 6px;
            padding: 10px 14px;
            font: inherit;
            cursor: pointer;
            text-decoration: none;
            color: #fff;
            background: #222;
        }
        .secondary {
            background: #666;
        }
        .danger {
            background: #9f2d2d;
        }
        .toolbar {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
            vertical-align: top;
        }
        th {
            background: #f1f1f1;
        }
        .actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        .muted {
            color: #666;
        }
    </style>
</head>
<body>
    <div class="wrap">
        <h1>Munchies Admin</h1>
        <p class="muted">Minimal product management page for adding, editing, and archiving records.</p>

        <div class="toolbar" style="margin-bottom: 16px;">
            <a class="link-btn secondary" href="index.php">Back to Home</a>
            <a class="link-btn secondary" href="admin.php">Refresh</a>
        </div>

        <?php if ($flashMessage !== ''): ?>
            <div class="flash"><?php echo htmlspecialchars($flashMessage); ?></div>
        <?php endif; ?>

        <section class="card">
            <h2><?php echo htmlspecialchars($pageTitle); ?></h2>
            <form method="post" action="admin.php<?php echo $editProduct ? '?edit=' . (int) $editProduct['product_code'] : ''; ?>">
                <input type="hidden" name="action" value="<?php echo htmlspecialchars($formAction); ?>">
                <?php if ($editProduct): ?>
                    <input type="hidden" name="product_code" value="<?php echo (int) $formValues['product_code']; ?>">
                <?php endif; ?>
                <div class="grid">
                    <label>
                        Product Name
                        <input type="text" name="product_name" value="<?php echo htmlspecialchars((string) $formValues['product_name']); ?>" required>
                    </label>
                    <label>
                        Price
                        <input type="number" name="price" step="0.01" min="0" value="<?php echo htmlspecialchars((string) $formValues['price']); ?>" required>
                    </label>
                    <label>
                        Quantity
                        <input type="number" name="quantity" min="0" value="<?php echo htmlspecialchars((string) $formValues['quantity']); ?>" required>
                    </label>
                </div>
                <label>
                    Description
                    <textarea name="product_desc" required><?php echo htmlspecialchars((string) $formValues['product_desc']); ?></textarea>
                </label>
                <label>
                    Image Path
                    <input type="text" name="product_image" value="<?php echo htmlspecialchars((string) $formValues['product_image']); ?>" required>
                </label>
                <div class="toolbar">
                    <button type="submit"><?php echo htmlspecialchars($submitLabel); ?></button>
                    <?php if ($editProduct): ?>
                        <a class="link-btn secondary" href="admin.php">Cancel Edit</a>
                    <?php endif; ?>
                </div>
            </form>
        </section>

        <section class="card">
            <h2>Products</h2>
            <table>
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>Image</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($productRows)): ?>
                        <tr>
                            <td colspan="6">No products found.</td>
                        </tr>
                    <?php else: ?>
                        <?php $rowNumber = 0; ?>
                        <?php foreach ($productRows as $product): ?>
                            <?php $rowNumber++; ?>
                            <tr>
                                <td><?php echo $rowNumber; ?></td>
                                <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                                <td>₱<?php echo number_format((float) $product['price'], 2); ?></td>
                                <td><?php echo (int) $product['quantity']; ?></td>
                                <td><?php echo htmlspecialchars($product['product_image']); ?></td>
                                <td>
                                    <div class="actions">
                                        <a class="link-btn secondary" href="admin.php?edit=<?php echo (int) $product['product_code']; ?>">Edit</a>
                                        <form method="post" action="admin.php" style="display:inline;" onsubmit="return confirm('Delete this product?');">
                                            <input type="hidden" name="action" value="delete_product">
                                            <input type="hidden" name="product_code" value="<?php echo (int) $product['product_code']; ?>">
                                            <button class="danger" type="submit">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>
    </div>
</body>
</html>
