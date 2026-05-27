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
    $ordersResult = $mysqli_connection->query("SELECT `id`, `user_id`, `status`, `total_price`, `order_date` FROM `orders` ORDER BY `id` DESC");
    if ($ordersResult) {
        while ($row = $ordersResult->fetch_assoc()) {
            $orders[] = $row;
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
                        <th>ID</th><th>User ID</th><th>Статус</th><th>Сумма</th><th>Дата</th><th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <form method="POST">
                                <td>
                                    <?= (int)$order['id'] ?>
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
                                <td><?= e((string)$order['total_price']) ?></td>
                                <td><?= e((string)$order['order_date']) ?></td>
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
