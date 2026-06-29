<?php
    require_once __DIR__ . '/../settings/admin_common.php';

    $message = '';
    $messageType = 'ok';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';

        try {
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
        } catch (Throwable $e) {
            $messageType = 'error';
            $message = $e->getMessage();
        }
    }

    $orders = [];
    $ordersResult = $mysqli_connection->query(
        "SELECT o.`id`, o.`user_id`, u.`fio`, o.`status`, o.`total_price`, o.`order_date`
         FROM `orders` o
         LEFT JOIN `users` u ON u.`id` = o.`user_id`
         ORDER BY o.`id` DESC"
    );
    if ($ordersResult) {
        while ($row = $ordersResult->fetch_assoc()) {
            $orders[] = $row;
        }
    }

    $orderItemsByOrderId = [];
    if (!empty($orders)) {
        $orderIds = implode(',', array_map(static fn($o) => (int)$o['id'], $orders));
        $itemsResult = $mysqli_connection->query(
            "SELECT oi.`order_id`, oi.`product_id`, oi.`quantity`, p.`name`
             FROM `order_items` oi
             LEFT JOIN `products` p ON p.`id` = oi.`product_id`
             WHERE oi.`order_id` IN ($orderIds)
             ORDER BY oi.`order_id` DESC, oi.`id` ASC"
        );
        if ($itemsResult) {
            while ($itemRow = $itemsResult->fetch_assoc()) {
                $oid = (int)$itemRow['order_id'];
                $orderItemsByOrderId[$oid][] = [
                    'name' => $itemRow['name'] ?? 'Товар удален',
                    'quantity' => (int)$itemRow['quantity'],
                ];
            }
        }
    }

    $adminPageTitle = 'Заказы';
    $adminPageSubtitle = 'Изменение статусов заказов';
    $adminActive = 'orders';
    require __DIR__ . '/admin_header.php';
?>

<?php if ($message !== ''): ?>
    <div class="notice <?= $messageType === 'error' ? 'notice-error' : 'notice-ok' ?>">
        <?= e($message) ?>
    </div>
<?php endif; ?>

<div class="grid">
    <section class="section">
        <h2>Список заказов</h2>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>ID</th><th>Клиент</th><th>Статус</th><th>Сумма</th><th>Дата</th><th>Состав заказа</th><th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <?php
                            $orderId = (int)$order['id'];
                            $items = $orderItemsByOrderId[$orderId] ?? [];
                        ?>
                        <tr>
                            <form method="POST">
                                <td>
                                    <?= $orderId ?>
                                    <input type="hidden" name="id" value="<?= $orderId ?>">
                                </td>
                                <td><?= e((string)($order['fio'] ?? 'Неизвестно')) ?></td>
                                <td>
                                    <select name="status">
                                        <option value="access" <?= ($order['status'] ?? '') === 'access' ? 'selected' : '' ?>>Принят</option>
                                        <option value="in_delivery" <?= ($order['status'] ?? '') === 'in_delivery' ? 'selected' : '' ?>>В доставке</option>
                                        <option value="ready" <?= ($order['status'] ?? '') === 'ready' ? 'selected' : '' ?>>Завершен</option>
                                    </select>
                                </td>
                                <td><?= e((string)$order['total_price']) ?> руб.</td>
                                <td><?= e((string)$order['order_date']) ?></td>
                                <td>
                                    <?php if (empty($items)): ?>
                                        <span style="color:var(--muted,#888)">Нет данных</span>
                                    <?php else: ?>
                                        <?php foreach ($items as $item): ?>
                                            <div><?= e($item['name']) ?> &times; <?= (int)$item['quantity'] ?></div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </td>
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

<?php require __DIR__ . '/admin_footer.php'; ?>
