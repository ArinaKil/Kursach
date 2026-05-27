<?php
    require_once __DIR__ . '/../settings/admin_common.php';

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

    $usersCount = 0;
    $productsCount = 0;
    $ordersCount = 0;

    if ($res = $mysqli_connection->query("SELECT COUNT(*) AS c FROM `users`")) {
        $usersCount = (int)($res->fetch_assoc()['c'] ?? 0);
    }
    if ($res = $mysqli_connection->query("SELECT COUNT(*) AS c FROM `products`")) {
        $productsCount = (int)($res->fetch_assoc()['c'] ?? 0);
    }
    if ($res = $mysqli_connection->query("SELECT COUNT(*) AS c FROM `orders`")) {
        $ordersCount = (int)($res->fetch_assoc()['c'] ?? 0);
    }

    $adminPageTitle = 'Админ-панель';
    $adminPageSubtitle = 'Управление пользователями, товарами и заказами';
    $adminActive = 'dashboard';
    require __DIR__ . '/admin_header.php';
?>

<div class="actions" style="margin-top:14px">
    <a class="btn btn-excel" href="admin.php?export=excel">Скачать Excel-отчет</a>
</div>

<div class="cards">
    <div class="card">
        <h3>Пользователи</h3>
        <p>Создание, редактирование и удаление учётных записей. Всего: <strong><?= $usersCount ?></strong>.</p>
        <a class="btn btn-primary" href="admin_users.php">Перейти к пользователям</a>
    </div>
    <div class="card">
        <h3>Товары</h3>
        <p>Каталог зоомагазина: добавление, изменение, удаление. Всего: <strong><?= $productsCount ?></strong>.</p>
        <a class="btn btn-primary" href="admin_products.php">Перейти к товарам</a>
    </div>
    <div class="card">
        <h3>Заказы</h3>
        <p>Изменение статусов заказов клиентов. Всего: <strong><?= $ordersCount ?></strong>.</p>
        <a class="btn btn-primary" href="admin_orders.php">Перейти к заказам</a>
    </div>
</div>

<?php require __DIR__ . '/admin_footer.php'; ?>
