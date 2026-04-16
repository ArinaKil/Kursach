<?php
    require_once __DIR__ . '/../settings/connect_database.php';

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $isAdmin = isset($_SESSION['role']) && (int)$_SESSION['role'] === 1;
    if (!$isAdmin) {
        header('Location: index.php');
        exit();
    }

    $message = '';
    $messageType = 'ok';

    function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

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

    if (isset($_GET['export']) && $_GET['export'] === 'excel') {
        $usersReport = [];
        $productsReport = [];
        $ordersReport = [];
        $orderItemsReport = [];

        $usersRes = $mysqli_connection->query("SELECT `id`, `fio`, `phone`, `role` FROM `users` ORDER BY `id` ASC");
        if ($usersRes) {
            while ($row = $usersRes->fetch_assoc()) {
                $usersReport[] = $row;
            }
        }

        $productsRes = $mysqli_connection->query("SELECT `id`, `name`, `category`, `price`, `imagePath` FROM `products` ORDER BY `id` ASC");
        if ($productsRes) {
            while ($row = $productsRes->fetch_assoc()) {
                $productsReport[] = $row;
            }
        }

        $ordersRes = $mysqli_connection->query("SELECT `id`, `user_id`, `status`, `total_price`, `order_date` FROM `orders` ORDER BY `id` ASC");
        if ($ordersRes) {
            while ($row = $ordersRes->fetch_assoc()) {
                $ordersReport[] = $row;
            }
        }

        $orderItemsSql = "SELECT oi.`id`, oi.`order_id`, oi.`product_id`, oi.`quantity`, p.`name` AS `product_name`
                          FROM `order_items` oi
                          LEFT JOIN `products` p ON p.`id` = oi.`product_id`
                          ORDER BY oi.`id` ASC";
        $orderItemsRes = $mysqli_connection->query($orderItemsSql);
        if ($orderItemsRes) {
            while ($row = $orderItemsRes->fetch_assoc()) {
                $orderItemsReport[] = $row;
            }
        }

        $filename = 'system_report_' . date('Y-m-d_H-i-s') . '.xls';
        header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo "\xEF\xBB\xBF";
        ?>
        <html lang="ru">
        <head>
            <meta charset="UTF-8">
            <style>
                body{font-family:Arial,sans-serif;font-size:12px}
                h1,h2{margin:0 0 10px}
                h1{font-size:18px}
                h2{font-size:14px;margin-top:20px}
                .meta{margin-bottom:12px;color:#444}
                table{border-collapse:collapse;width:100%;margin-bottom:14px}
                th,td{border:1px solid #aeb4bf;padding:6px;text-align:left;vertical-align:top}
                th{background:#e5e7eb;font-weight:700}
            </style>
        </head>
        <body>
            <h1>Отчет по системе зоомагазина</h1>
            <div class="meta">Сформирован: <?= e(date('d.m.Y H:i:s')) ?></div>
            <table>
                <tr><th>Метрика</th><th>Значение</th></tr>
                <tr><td>Пользователи</td><td><?= count($usersReport) ?></td></tr>
                <tr><td>Товары</td><td><?= count($productsReport) ?></td></tr>
                <tr><td>Заказы</td><td><?= count($ordersReport) ?></td></tr>
                <tr><td>Позиции заказов</td><td><?= count($orderItemsReport) ?></td></tr>
            </table>

            <h2>Пользователи</h2>
            <table>
                <tr><th>ID</th><th>ФИО</th><th>Телефон</th><th>Роль</th></tr>
                <?php foreach ($usersReport as $row): ?>
                    <tr>
                        <td><?= (int)$row['id'] ?></td>
                        <td><?= e((string)$row['fio']) ?></td>
                        <td><?= e((string)$row['phone']) ?></td>
                        <td><?= (int)$row['role'] === 1 ? 'Админ' : 'Клиент' ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>

            <h2>Товары</h2>
            <table>
                <tr><th>ID</th><th>Название</th><th>Категория</th><th>Цена</th><th>Изображение</th></tr>
                <?php foreach ($productsReport as $row): ?>
                    <tr>
                        <td><?= (int)$row['id'] ?></td>
                        <td><?= e((string)$row['name']) ?></td>
                        <td><?= e((string)$row['category']) ?></td>
                        <td><?= e((string)$row['price']) ?></td>
                        <td><?= e((string)$row['imagePath']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>

            <h2>Заказы</h2>
            <table>
                <tr><th>ID</th><th>Клиент ID</th><th>Статус</th><th>Сумма</th><th>Дата</th></tr>
                <?php foreach ($ordersReport as $row): ?>
                    <tr>
                        <td><?= (int)$row['id'] ?></td>
                        <td><?= (int)$row['user_id'] ?></td>
                        <td><?= e((string)$row['status']) ?></td>
                        <td><?= e((string)$row['total_price']) ?></td>
                        <td><?= e((string)$row['order_date']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>

            <h2>Позиции заказов</h2>
            <table>
                <tr><th>ID</th><th>Заказ ID</th><th>Товар ID</th><th>Название товара</th><th>Количество</th></tr>
                <?php foreach ($orderItemsReport as $row): ?>
                    <tr>
                        <td><?= (int)$row['id'] ?></td>
                        <td><?= (int)$row['order_id'] ?></td>
                        <td><?= (int)$row['product_id'] ?></td>
                        <td><?= e((string)($row['product_name'] ?? 'Товар удален')) ?></td>
                        <td><?= (int)$row['quantity'] ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </body>
        </html>
        <?php
        exit();
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $entity = $_POST['entity'] ?? '';
        $action = $_POST['action'] ?? '';

        try {
            if ($entity === 'user') {
                if ($action === 'create') {
                    $fio = trim($_POST['fio'] ?? '');
                    $phone = trim($_POST['phone'] ?? '');
                    $password = (string)($_POST['password'] ?? '');
                    $role = (int)($_POST['role'] ?? 0);

                    if ($fio === '' || $phone === '' || $password === '') {
                        throw new Exception('Для создания пользователя заполните все поля');
                    }

                    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                    $sql = "INSERT INTO `users`(`fio`, `phone`, `password_hash`, `role`) VALUES (?, ?, ?, ?)";
                    $query = $mysqli_connection->prepare($sql);
                    if (!$query) {
                        throw new Exception('Ошибка подготовки запроса создания пользователя');
                    }
                    $query->bind_param('sssi', $fio, $phone, $passwordHash, $role);
                    $query->execute();

                    $message = 'Пользователь создан';
                } elseif ($action === 'update') {
                    $id = (int)($_POST['id'] ?? 0);
                    $fio = trim($_POST['fio'] ?? '');
                    $phone = trim($_POST['phone'] ?? '');
                    $role = (int)($_POST['role'] ?? 0);
                    $newPassword = (string)($_POST['password'] ?? '');

                    if ($id <= 0 || $fio === '' || $phone === '') {
                        throw new Exception('Некорректные данные пользователя для обновления');
                    }

                    if ($newPassword !== '') {
                        $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
                        $sql = "UPDATE `users` SET `fio` = ?, `phone` = ?, `password_hash` = ?, `role` = ? WHERE `id` = ?";
                        $query = $mysqli_connection->prepare($sql);
                        if (!$query) {
                            throw new Exception('Ошибка подготовки запроса обновления пользователя');
                        }
                        $query->bind_param('sssii', $fio, $phone, $passwordHash, $role, $id);
                    } else {
                        $sql = "UPDATE `users` SET `fio` = ?, `phone` = ?, `role` = ? WHERE `id` = ?";
                        $query = $mysqli_connection->prepare($sql);
                        if (!$query) {
                            throw new Exception('Ошибка подготовки запроса обновления пользователя');
                        }
                        $query->bind_param('ssii', $fio, $phone, $role, $id);
                    }

                    $query->execute();
                    $message = 'Пользователь обновлен';
                } elseif ($action === 'delete') {
                    $id = (int)($_POST['id'] ?? 0);
                    if ($id <= 0) {
                        throw new Exception('Некорректный id пользователя');
                    }

                    if (isset($_SESSION['user_id']) && (int)$_SESSION['user_id'] === $id) {
                        throw new Exception('Нельзя удалить текущего авторизованного администратора');
                    }

                    $sql = "DELETE FROM `users` WHERE `id` = ?";
                    $query = $mysqli_connection->prepare($sql);
                    if (!$query) {
                        throw new Exception('Ошибка подготовки запроса удаления пользователя');
                    }
                    $query->bind_param('i', $id);
                    $query->execute();
                    $message = 'Пользователь удален';
                }
            } elseif ($entity === 'product') {
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
            } elseif ($entity === 'order') {
                if ($action === 'update') {
                    $id = (int)($_POST['id'] ?? 0);
                    $status = trim($_POST['status'] ?? 'access');

                    $allowedStatuses = ['access', 'in_delivery', 'ready'];
                    if ($id <= 0) {
                        throw new Exception('Некорректный id заказа');
                    }
                    if (!in_array($status, $allowedStatuses, true)) {
                        throw new Exception('Некорректный статус заказа');
                    }

                    $sql = "UPDATE `orders` SET `status` = ? WHERE `id` = ?";
                    $query = $mysqli_connection->prepare($sql);
                    if (!$query) {
                        throw new Exception('Ошибка подготовки запроса обновления статуса заказа');
                    }

                    $query->bind_param('si', $status, $id);
                    $query->execute();
                    $message = 'Статус заказа обновлен';
                } else {
                    throw new Exception('В админ-панели доступно только обновление статуса заказа');
                }
            }
        } catch (Throwable $e) {
            $messageType = 'error';
            $message = $e->getMessage();
        }
    }

    $users = [];
    $products = [];
    $orders = [];

    $usersResult = $mysqli_connection->query("SELECT `id`, `fio`, `phone`, `role` FROM `users` ORDER BY `id` DESC");
    if ($usersResult) {
        while ($row = $usersResult->fetch_assoc()) {
            $users[] = $row;
        }
    }

    $productsResult = $mysqli_connection->query("SELECT `id`, `name`, `category`, `price`, `imagePath` FROM `products` ORDER BY `id` DESC");
    if ($productsResult) {
        while ($row = $productsResult->fetch_assoc()) {
            $products[] = $row;
        }
    }

    $ordersResult = $mysqli_connection->query("SELECT `id`, `user_id`, `status`, `total_price`, `order_date` FROM `orders` ORDER BY `id` DESC");
    if ($ordersResult) {
        while ($row = $ordersResult->fetch_assoc()) {
            $orders[] = $row;
        }
    }
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ-панель</title>
    <style>
        :root{
            --bg1:#f6fbff;
            --bg2:#fffaf2;
            --stroke:rgba(15, 23, 42, .12);
            --text:#0f172a;
            --muted:rgba(15, 23, 42, .68);
            --accent:#22c55e;
            --accent2:#60a5fa;
            --danger:#dc2626;
            --shadow:0 14px 36px rgba(15, 23, 42, .12);
            --radius:14px;
        }
        *{box-sizing:border-box}
        body{
            margin:0;
            font-family:system-ui, -apple-system, Segoe UI, Roboto, Arial, "Noto Sans", "Helvetica Neue", sans-serif;
            color:var(--text);
            background:
                radial-gradient(900px 650px at 12% 10%, rgba(96,165,250,.28), transparent 60%),
                radial-gradient(820px 620px at 88% 18%, rgba(34,197,94,.18), transparent 60%),
                linear-gradient(160deg, var(--bg1), var(--bg2));
            min-height:100vh;
            padding:20px 14px 28px;
        }
        .container{width:min(1280px,100%);margin:0 auto}
        .header,.section{
            border:1px solid var(--stroke);
            border-radius:var(--radius);
            background:linear-gradient(180deg, rgba(255,255,255,.95), rgba(255,255,255,.86));
            box-shadow:var(--shadow);
            padding:16px;
        }
        .header{display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap}
        h1{margin:0;font-size:26px}
        .muted{margin:4px 0 0;color:var(--muted);font-size:14px}
        .actions{display:flex;gap:8px;flex-wrap:wrap}
        .btn{
            appearance:none;
            border:1px solid var(--stroke);
            border-radius:10px;
            background:#fff;
            color:var(--text);
            text-decoration:none;
            padding:9px 12px;
            font-weight:700;
            cursor:pointer;
        }
        .btn-primary{
            border:none;
            background:#e2e8f0;
            box-shadow:none;
        }
        .btn-danger{
            border:none;
            background:var(--danger);
            color:#fff;
        }
        .btn-excel{
            border:none;
            background:#217346;
            color:#fff;
        }
        .btn-excel:hover{
            background:#1b5f3a;
        }
        .grid{
            display:grid;
            grid-template-columns:1fr;
            gap:14px;
            margin-top:14px;
        }
        .section h2{margin:0 0 10px;font-size:20px}
        .table-wrap{overflow:auto}
        table{width:100%;border-collapse:collapse;font-size:13px}
        th,td{border:1px solid var(--stroke);padding:8px;vertical-align:top}
        th{background:rgba(255,255,255,.85);text-align:left}
        input,select{
            width:100%;
            padding:8px;
            border-radius:8px;
            border:1px solid rgba(15,23,42,.2);
            background:#fff;
        }
        .form-row{
            display:grid;
            grid-template-columns:repeat(auto-fit,minmax(170px,1fr));
            gap:8px;
            margin-bottom:10px;
        }
        .notice{
            margin-top:12px;
            padding:10px 12px;
            border-radius:10px;
            font-size:14px;
        }
        .notice-ok{
            border:1px solid rgba(22,163,74,.28);
            background:rgba(22,163,74,.08);
            color:#166534;
        }
        .notice-error{
            border:1px solid rgba(220,38,38,.28);
            background:rgba(220,38,38,.08);
            color:#991b1b;
        }
    </style>
</head>
<body>
    <main class="container">
        <section class="header">
            <div>
                <h1>Админ-панель</h1>
                <p class="muted">Управление пользователями, товарами и заказами</p>
            </div>
            <div class="actions">
                <a class="btn btn-excel" href="admin.php?export=excel">Скачать Excel-отчет</a>
                <a class="btn" href="logout.php">Выйти</a>
            </div>
        </section>

        <?php if ($message !== ''): ?>
            <div class="notice <?= $messageType === 'error' ? 'notice-error' : 'notice-ok' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <div class="grid">
            <section class="section">
                <h2>Пользователи</h2>
                <form method="POST" class="form-row">
                    <input type="hidden" name="entity" value="user">
                    <input type="hidden" name="action" value="create">
                    <input name="fio" type="text" placeholder="ФИО" required>
                    <input name="phone" type="text" placeholder="Телефон" required>
                    <input name="password" type="password" placeholder="Пароль" required>
                    <select name="role">
                        <option value="0">Пользователь (0)</option>
                        <option value="1">Админ (1)</option>
                    </select>
                    <button type="submit" class="btn btn-primary">Создать</button>
                </form>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th><th>ФИО</th><th>Телефон</th><th>Роль</th><th>Новый пароль</th><th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <form method="POST">
                                        <td>
                                            <?= (int)$user['id'] ?>
                                            <input type="hidden" name="entity" value="user">
                                            <input type="hidden" name="id" value="<?= (int)$user['id'] ?>">
                                        </td>
                                        <td><input type="text" name="fio" value="<?= htmlspecialchars($user['fio']) ?>" required></td>
                                        <td><input type="text" name="phone" value="<?= htmlspecialchars($user['phone']) ?>" required></td>
                                        <td>
                                            <select name="role">
                                                <option value="0" <?= (int)$user['role'] === 0 ? 'selected' : '' ?>>0</option>
                                                <option value="1" <?= (int)$user['role'] === 1 ? 'selected' : '' ?>>1</option>
                                            </select>
                                        </td>
                                        <td><input type="password" name="password" placeholder="Не менять"></td>
                                        <td class="actions">
                                            <button class="btn btn-primary" type="submit" name="action" value="update">Сохранить</button>
                                            <button class="btn btn-danger" type="submit" name="action" value="delete" onclick="return confirm('Удалить пользователя?')">Удалить</button>
                                        </td>
                                    </form>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="section">
                <h2>Товары</h2>
                <form method="POST" class="form-row" enctype="multipart/form-data">
                    <input type="hidden" name="entity" value="product">
                    <input type="hidden" name="action" value="create">
                    <input name="name" type="text" placeholder="Название" required>
                    <input name="category" type="text" placeholder="Категория" required>
                    <input name="price" type="number" step="0.01" min="0" placeholder="Цена">
                    <input name="imageFile" type="file" accept=".jpg,.jpeg,.png,.webp,.gif" required>
                    <button type="submit" class="btn btn-primary">Создать</button>
                </form>
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
                                            <input type="hidden" name="entity" value="product">
                                            <input type="hidden" name="id" value="<?= (int)$product['id'] ?>">
                                        </td>
                                        <td><input type="text" name="name" value="<?= htmlspecialchars($product['name']) ?>" required></td>
                                        <td><input type="text" name="category" value="<?= htmlspecialchars($product['category']) ?>" required></td>
                                        <td><input type="number" step="0.01" min="0" name="price" value="<?= htmlspecialchars((string)$product['price']) ?>"></td>
                                        <td>
                                            <input type="hidden" name="existingImagePath" value="<?= htmlspecialchars($product['imagePath'] ?? '') ?>">
                                            <input type="file" name="imageFile" accept=".jpg,.jpeg,.png,.webp,.gif">
                                            <small><?= htmlspecialchars($product['imagePath'] ?? 'Нет изображения') ?></small>
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

            <section class="section">
                <h2>Заказы</h2>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th><th>User ID</th><th>Статус</th><th>Сумма</th><th>Дата</th><th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <form method="POST">
                                        <td>
                                            <?= (int)$order['id'] ?>
                                            <input type="hidden" name="entity" value="order">
                                            <input type="hidden" name="id" value="<?= (int)$order['id'] ?>">
                                        </td>
                                        <td><?= (int)$order['user_id'] ?></td>
                                        <td>
                                            <select name="status">
                                                <option value="access" <?= ($order['status'] ?? '') === 'access' ? 'selected' : '' ?>>access</option>
                                                <option value="in_delivery" <?= ($order['status'] ?? '') === 'in_delivery' ? 'selected' : '' ?>>in_delivery</option>
                                                <option value="ready" <?= ($order['status'] ?? '') === 'ready' ? 'selected' : '' ?>>ready</option>
                                            </select>
                                        </td>
                                        <td><?= htmlspecialchars((string)$order['total_price']) ?></td>
                                        <td><?= htmlspecialchars((string)$order['order_date']) ?></td>
                                        <td class="actions">
                                            <button class="btn btn-primary" type="submit" name="action" value="update">Сохранить</button>
                                        </td>
                                    </form>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </main>
</body>
</html>
