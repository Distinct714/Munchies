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

function munchies_admin_has_archive_column(mysqli $conn): bool
{
    $columnResult = mysqli_query($conn, "SHOW COLUMNS FROM tbl_product LIKE 'is_archived'");
    if (!$columnResult) {
        return false;
    }

    $hasColumn = mysqli_num_rows($columnResult) > 0;
    mysqli_free_result($columnResult);
    return $hasColumn;
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

        if (!$stmt) {
            die('Unable to prepare insert query: ' . mysqli_error($conn));
        }

        mysqli_stmt_bind_param($stmt, 'sssdi', $name, $description, $image, $price, $quantity);
        if (!mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            munchies_admin_flash('Unable to add the product.');
            munchies_admin_redirect();
        }

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

    if ($action === 'archive_product') {
        $productCode = (int) ($_POST['product_code'] ?? 0);

        if ($productCode <= 0) {
            munchies_admin_flash('Invalid product selected for archiving.');
            munchies_admin_redirect();
        }

        if (!munchies_admin_has_archive_column($conn)) {
            $alterResult = mysqli_query($conn, "ALTER TABLE tbl_product ADD COLUMN is_archived TINYINT(1) NOT NULL DEFAULT 0");
            if ($alterResult === false) {
                munchies_admin_flash('Unable to enable archive support.');
                munchies_admin_redirect();
            }
        }

        $stmt = mysqli_prepare($conn, 'UPDATE tbl_product SET is_archived = 1 WHERE product_code = ?');
        if (!$stmt) {
            die('Unable to prepare archive query: ' . mysqli_error($conn));
        }

        mysqli_stmt_bind_param($stmt, 'i', $productCode);
        if (!mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            munchies_admin_flash('Unable to archive the product.');
            munchies_admin_redirect();
        }

        mysqli_stmt_close($stmt);
        munchies_admin_flash('Product archived successfully.');
        munchies_admin_redirect();
    }

    if ($action === 'unarchive_product') {
        $productCode = (int) ($_POST['product_code'] ?? 0);

        if ($productCode <= 0) {
            munchies_admin_flash('Invalid product selected for unarchiving.');
            munchies_admin_redirect();
        }

        if (!munchies_admin_has_archive_column($conn)) {
            munchies_admin_flash('Archive support is not available.');
            munchies_admin_redirect();
        }

        $stmt = mysqli_prepare($conn, 'UPDATE tbl_product SET is_archived = 0 WHERE product_code = ?');
        if (!$stmt) {
            die('Unable to prepare unarchive query: ' . mysqli_error($conn));
        }

        mysqli_stmt_bind_param($stmt, 'i', $productCode);
        if (!mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            munchies_admin_flash('Unable to unarchive the product.');
            munchies_admin_redirect();
        }

        mysqli_stmt_close($stmt);
        munchies_admin_flash('Product unarchived successfully.');
        munchies_admin_redirect();
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
$archivedRows = [];
$archiveColumnExists = munchies_admin_has_archive_column($conn);

$activeQuery = 'SELECT product_code, product_name, product_desc, product_image, price, quantity FROM tbl_product';
if ($archiveColumnExists) {
    $activeQuery .= ' WHERE COALESCE(is_archived, 0) = 0';
}
$activeQuery .= ' ORDER BY product_name ASC';

$result = mysqli_query($conn, $activeQuery);

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $productRows[] = $row;
    }

    mysqli_free_result($result);
}

if ($archiveColumnExists) {
    $archivedQuery = 'SELECT product_code, product_name, product_desc, product_image, price, quantity FROM tbl_product WHERE is_archived = 1 ORDER BY product_name ASC';
    $archivedResult = mysqli_query($conn, $archivedQuery);

    if ($archivedResult) {
        while ($row = mysqli_fetch_assoc($archivedResult)) {
            $archivedRows[] = $row;
        }

        mysqli_free_result($archivedResult);
    }
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
    <link rel="stylesheet" href="static/style5.css">
    <title>Admin | Munchies</title>
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
            <form method="post"
                action="admin.php<?php echo $editProduct ? '?edit=' . (int) $editProduct['product_code'] : ''; ?>">
                <input type="hidden" name="action" value="<?php echo htmlspecialchars($formAction); ?>">
                <?php if ($editProduct): ?>
                    <input type="hidden" name="product_code" value="<?php echo (int) $formValues['product_code']; ?>">
                <?php endif; ?>
                <div class="grid">
                    <label>
                        Product Name
                        <input type="text" name="product_name"
                            value="<?php echo htmlspecialchars((string) $formValues['product_name']); ?>" required>
                    </label>
                    <label>
                        Price
                        <input type="number" name="price" step="0.01" min="0"
                            value="<?php echo htmlspecialchars((string) $formValues['price']); ?>" required>
                    </label>
                    <label>
                        Quantity
                        <input type="number" name="quantity" min="0"
                            value="<?php echo htmlspecialchars((string) $formValues['quantity']); ?>" required>
                    </label>
                </div>
                <label>
                    Description
                    <textarea name="product_desc"
                        required><?php echo htmlspecialchars((string) $formValues['product_desc']); ?></textarea>
                </label>
                <label>
                    Image Path
                    <input type="text" name="product_image"
                        value="<?php echo htmlspecialchars((string) $formValues['product_image']); ?>" required>
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
                                        <a class="link-btn secondary"
                                            href="admin.php?edit=<?php echo (int) $product['product_code']; ?>">Edit</a>
                                        <form method="post" action="admin.php" style="display:inline;"
                                            onsubmit="return confirm('Archive this product?');">
                                            <input type="hidden" name="action" value="archive_product">
                                            <input type="hidden" name="product_code"
                                                value="<?php echo (int) $product['product_code']; ?>">
                                            <button class="secondary" type="submit">Archive</button>
                                        </form>
                                        <form method="post" action="admin.php" style="display:inline;"
                                            onsubmit="return confirm('Delete this product?');">
                                            <input type="hidden" name="action" value="delete_product">
                                            <input type="hidden" name="product_code"
                                                value="<?php echo (int) $product['product_code']; ?>">
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

        <section class="card">
            <h2>Archived Products</h2>
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
                    <?php if (empty($archivedRows)): ?>
                        <tr>
                            <td colspan="6">No archived products.</td>
                        </tr>
                    <?php else: ?>
                        <?php $archivedRowNumber = 0; ?>
                        <?php foreach ($archivedRows as $product): ?>
                            <?php $archivedRowNumber++; ?>
                            <tr>
                                <td><?php echo $archivedRowNumber; ?></td>
                                <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                                <td>₱<?php echo number_format((float) $product['price'], 2); ?></td>
                                <td><?php echo (int) $product['quantity']; ?></td>
                                <td><?php echo htmlspecialchars($product['product_image']); ?></td>
                                <td>
                                    <div class="actions">
                                        <form method="post" action="admin.php" style="display:inline;"
                                            onsubmit="return confirm('Unarchive this product?');">
                                            <input type="hidden" name="action" value="unarchive_product">
                                            <input type="hidden" name="product_code"
                                                value="<?php echo (int) $product['product_code']; ?>">
                                            <button class="secondary" type="submit">Unarchive</button>
                                        </form>
                                        <form method="post" action="admin.php" style="display:inline;"
                                            onsubmit="return confirm('Delete this archived product?');">
                                            <input type="hidden" name="action" value="delete_product">
                                            <input type="hidden" name="product_code"
                                                value="<?php echo (int) $product['product_code']; ?>">
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
