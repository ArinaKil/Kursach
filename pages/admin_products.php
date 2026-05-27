<?php
    require_once __DIR__ . '/../settings/admin_common.php';

    function uploadProductImage(array $file): string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new Exception('Ошибка загрузки изображения');
        }

        $originalName = (string)($file['name'] ?? '');
        $tmpName = (string)($file['tmp_name'] ?? '');
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

        if (!in_array($extension, $allowedExtensions, true)) {
            throw new Exception('Допустимые форматы изображения: jpg, jpeg, png, webp, gif');
        }

        $uploadDir = __DIR__ . '/../uploads/products';
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0777, true) && !is_dir($uploadDir)) {
            throw new Exception('Не удалось создать папку для изображений');
        }

        $fileName = uniqid('product_', true) . '.' . $extension;
        $targetFile = $uploadDir . '/' . $fileName;

        if (!move_uploaded_file($tmpName, $targetFile)) {
            throw new Exception('Не удалось сохранить изображение');
        }

        return '../uploads/products/' . $fileName;
    }

    $message = '';
    $messageType = 'ok';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';

        try {
            if ($action === 'create') {
                $name = trim($_POST['name'] ?? '');
                $category = trim($_POST['category'] ?? '');
                $price = (float)($_POST['price'] ?? 0);
                $imageFile = $_FILES['imageFile'] ?? null;

                if ($name === '' || $category === '') {
                    throw new Exception('Название и категория товара обязательны');
                }

                if (!$imageFile || ($imageFile['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                    throw new Exception('Выберите изображение товара');
                }

                $imagePath = uploadProductImage($imageFile);

                $sql = "INSERT INTO `products`(`name`, `category`, `price`, `imagePath`) VALUES (?, ?, ?, ?)";
                $query = $mysqli_connection->prepare($sql);
                if (!$query) {
                    throw new Exception('Ошибка подготовки запроса создания товара');
                }
                $query->bind_param('ssds', $name, $category, $price, $imagePath);
                $query->execute();
                $message = 'Товар создан';
            } elseif ($action === 'update') {
                $id = (int)($_POST['id'] ?? 0);
                $name = trim($_POST['name'] ?? '');
                $category = trim($_POST['category'] ?? '');
                $price = (float)($_POST['price'] ?? 0);
                $imagePath = trim($_POST['existingImagePath'] ?? '');
                $imageFile = $_FILES['imageFile'] ?? null;

                if ($id <= 0 || $name === '' || $category === '') {
                    throw new Exception('Некорректные данные товара для обновления');
                }

                if ($imageFile && ($imageFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                    $imagePath = uploadProductImage($imageFile);
                }

                $sql = "UPDATE `products` SET `name` = ?, `category` = ?, `price` = ?, `imagePath` = ? WHERE `id` = ?";
                $query = $mysqli_connection->prepare($sql);
                if (!$query) {
                    throw new Exception('Ошибка подготовки запроса обновления товара');
                }
                $query->bind_param('ssdsi', $name, $category, $price, $imagePath, $id);
                $query->execute();
                $message = 'Товар обновлен';
            } elseif ($action === 'delete') {
                $id = (int)($_POST['id'] ?? 0);
                if ($id <= 0) {
                    throw new Exception('Некорректный id товара');
                }

                $sql = "DELETE FROM `products` WHERE `id` = ?";
                $query = $mysqli_connection->prepare($sql);
                if (!$query) {
                    throw new Exception('Ошибка подготовки запроса удаления товара');
                }
                $query->bind_param('i', $id);
                $query->execute();
                $message = 'Товар удален';
            }
        } catch (Throwable $e) {
            $messageType = 'error';
            $message = $e->getMessage();
        }
    }

    $products = [];
    $productsResult = $mysqli_connection->query("SELECT `id`, `name`, `category`, `price`, `imagePath` FROM `products` ORDER BY `id` DESC");
    if ($productsResult) {
        while ($row = $productsResult->fetch_assoc()) {
            $products[] = $row;
        }
    }

    $adminPageTitle = 'Товары';
    $adminPageSubtitle = 'Каталог зоомагазина';
    $adminActive = 'products';
    require __DIR__ . '/admin_header.php';
?>

<?php if ($message !== ''): ?>
    <div class="notice <?= $messageType === 'error' ? 'notice-error' : 'notice-ok' ?>">
        <?= e($message) ?>
    </div>
<?php endif; ?>

<div class="grid">
    <section class="section">
        <h2>Добавить товар</h2>
        <form method="POST" class="form-row" enctype="multipart/form-data">
            <input type="hidden" name="action" value="create">
            <input name="name" type="text" placeholder="Название" required>
            <input name="category" type="text" placeholder="Категория" required>
            <input name="price" type="number" step="0.01" min="0" placeholder="Цена">
            <input name="imageFile" type="file" accept=".jpg,.jpeg,.png,.webp,.gif" required>
            <button type="submit" class="btn btn-primary">Создать</button>
        </form>
    </section>

    <section class="section">
        <h2>Список товаров</h2>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>ID</th><th>Название</th><th>Категория</th><th>Цена</th><th>Изображение</th><th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                        <tr>
                            <form method="POST" enctype="multipart/form-data">
                                <td>
                                    <?= (int)$product['id'] ?>
                                    <input type="hidden" name="id" value="<?= (int)$product['id'] ?>">
                                </td>
                                <td><input type="text" name="name" value="<?= e((string)$product['name']) ?>" required></td>
                                <td><input type="text" name="category" value="<?= e((string)$product['category']) ?>" required></td>
                                <td><input type="number" step="0.01" min="0" name="price" value="<?= e((string)$product['price']) ?>"></td>
                                <td>
                                    <input type="hidden" name="existingImagePath" value="<?= e($product['imagePath'] ?? '') ?>">
                                    <input type="file" name="imageFile" accept=".jpg,.jpeg,.png,.webp,.gif">
                                    <small><?= e($product['imagePath'] ?? 'Нет изображения') ?></small>
                                </td>
                                <td class="actions">
                                    <button class="btn btn-primary" type="submit" name="action" value="update">Сохранить</button>
                                    <button class="btn btn-danger" type="submit" name="action" value="delete" onclick="return confirm('Удалить товар?')">Удалить</button>
                                </td>
                            </form>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>

<?php require __DIR__ . '/admin_footer.php'; ?>
