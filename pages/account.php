<?php
    require_once __DIR__ . '/../settings/connect_database.php';

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Админу на личный кабинет не надо
    if (isset($_SESSION['role']) && (int)$_SESSION['role'] === 1) {
        header('Location: admin.php');
        exit();
    }

    if (empty($_SESSION['user_id'])) {
        header('Location: login.php');
        exit();
    }

    $userId = (int)$_SESSION['user_id'];
    $fio = trim((string)($_SESSION['fio'] ?? ''));
    $phone = trim((string)($_SESSION['phone'] ?? ''));

    $message = '';
    $messageType = 'ok';

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'change_password') {
        try {
            $currentPassword = (string)($_POST['current_password'] ?? '');
            $newPassword = (string)($_POST['new_password'] ?? '');
            $confirmPassword = (string)($_POST['confirm_password'] ?? '');

            $currentPassword = trim($currentPassword);
            $newPassword = trim($newPassword);
            $confirmPassword = trim($confirmPassword);

            if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
                throw new Exception('Заполните все поля для смены пароля');
            }

            if ($newPassword !== $confirmPassword) {
                throw new Exception('Новые пароли не совпадают');
            }

            if (mb_strlen($newPassword) < 6) {
                throw new Exception('Новый пароль должен быть минимум 6 символов');
            }

            $sql = "SELECT `password_hash` FROM `users` WHERE `id` = ? LIMIT 1";
            $query = $mysqli_connection->prepare($sql);
            if (!$query) {
                throw new Exception('Ошибка подготовки запроса');
            }
            $query->bind_param('i', $userId);
            $query->execute();
            $result = $query->get_result();
            $row = $result ? $result->fetch_assoc() : null;

            $passwordHash = $row['password_hash'] ?? null;
            if (!$passwordHash) {
                throw new Exception('Пользователь не найден');
            }

            if (!password_verify($currentPassword, $passwordHash)) {
                throw new Exception('Текущий пароль введен неверно');
            }

            $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $updateSql = "UPDATE `users` SET `password_hash` = ? WHERE `id` = ?";
            $updateQuery = $mysqli_connection->prepare($updateSql);
            if (!$updateQuery) {
                throw new Exception('Ошибка подготовки запроса обновления пароля');
            }
            $updateQuery->bind_param('si', $newPasswordHash, $userId);
            $updateQuery->execute();

            $messageType = 'ok';
            $message = 'Пароль успешно изменен';
        } catch (Throwable $e) {
            $messageType = 'error';
            $message = $e->getMessage();
        }
    }

    $orders = [];
    $orderItemsByOrderId = [];
    $ordersError = null;

    if (isset($mysqli_connection) && $mysqli_connection instanceof mysqli) {
        $sql = "SELECT `id`, `status`, `total_price`, `order_date` FROM `orders` WHERE `user_id` = ? ORDER BY `id` DESC";
        $query = $mysqli_connection->prepare($sql);
        if ($query) {
            $query->bind_param('i', $userId);
            $query->execute();
            $result = $query->get_result();

            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $orders[] = $row;
                }
            }

            if (!empty($orders)) {
                $orderIds = array_map(static function ($order) {
                    return (int)($order['id'] ?? 0);
                }, $orders);
                $orderIds = array_values(array_filter($orderIds, static function ($id) {
                    return $id > 0;
                }));

                if (!empty($orderIds)) {
                    $idsSql = implode(',', $orderIds);
                    $itemsSql = "SELECT oi.`order_id`, oi.`product_id`, oi.`quantity`, p.`name`
                                 FROM `order_items` oi
                                 LEFT JOIN `products` p ON p.`id` = oi.`product_id`
                                 WHERE oi.`order_id` IN ($idsSql)
                                 ORDER BY oi.`order_id` DESC, oi.`id` ASC";
                    $itemsResult = $mysqli_connection->query($itemsSql);
                    if ($itemsResult) {
                        while ($itemRow = $itemsResult->fetch_assoc()) {
                            $oid = (int)($itemRow['order_id'] ?? 0);
                            if (!isset($orderItemsByOrderId[$oid])) {
                                $orderItemsByOrderId[$oid] = [];
                            }
                            $orderItemsByOrderId[$oid][] = [
                                'product_id' => (int)($itemRow['product_id'] ?? 0),
                                'name' => (string)($itemRow['name'] ?? 'Товар удален'),
                                'quantity' => (int)($itemRow['quantity'] ?? 0),
                            ];
                        }
                    }
                }
            }
        } else {
            $ordersError = 'Не удалось подготовить запрос на загрузку заказов';
        }
    $statusLabels = [
        'access' => 'Принят',
        'in_delivery' => 'В доставке',
        'ready' => 'Завершен',
    ];

    } else {
        $ordersError = 'Нет подключения к базе данных';
    }
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Личный кабинет</title>
    <style>
        :root{
            --bg1:#f6fbff;
            --bg2:#fffaf2;
            --stroke:rgba(15, 23, 42, .12);
            --text:#0f172a;
            --muted:rgba(15, 23, 42, .68);
            --accent:#22c55e;
            --accent2:#60a5fa;
            --shadow:0 18px 50px rgba(15, 23, 42, .12);
            --radius:18px;
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
        }
        .container{
            width:min(1120px, 100%);
            margin:0 auto;
            padding:28px 16px 36px;
        }
        .header{
            border:1px solid var(--stroke);
            background:linear-gradient(180deg, rgba(255,255,255,.95), rgba(255,255,255,.86));
            border-radius:var(--radius);
            box-shadow:var(--shadow);
            padding:22px;
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:14px;
            flex-wrap:wrap;
        }
        h1{margin:0;font-size:28px;line-height:1.2}
        .muted{margin:6px 0 0;color:var(--muted);font-size:14px}
        .actions{display:flex;gap:10px;flex-wrap:wrap}
        .btn{
            appearance:none;
            border:none;
            cursor:pointer;
            border-radius:12px;
            padding:11px 14px;
            font-weight:700;
            color:#0b1220;
            background:#e2e8f0;
            box-shadow:none;
            text-decoration:none;
            display:inline-flex;
            align-items:center;
            justify-content:center;
        }
        .btn-light{
            background:#fff;
            border:1px solid var(--stroke);
            box-shadow:none;
        }
        .grid{
            margin-top:18px;
            display:grid;
            grid-template-columns: 1fr;
            gap:14px;
        }
        @media (min-width: 900px){
            .grid{grid-template-columns: 0.9fr 1.1fr}
        }
        .card{
            border:1px solid var(--stroke);
            background:linear-gradient(180deg, rgba(255,255,255,.95), rgba(255,255,255,.86));
            border-radius:16px;
            box-shadow:0 10px 28px rgba(15, 23, 42, .09);
            padding:14px;
        }
        h2{margin:0 0 10px;font-size:20px}
        .kv{display:grid;grid-template-columns: 140px 1fr;row-gap:8px;column-gap:10px}
        .k{color:var(--muted);font-size:13px}
        .v{font-size:14px}
        .table-wrap{overflow:auto}
        table{width:100%;border-collapse:collapse;font-size:13px}
        th,td{border:1px solid var(--stroke);padding:8px;vertical-align:top}
        th{background:rgba(255,255,255,.85);text-align:left}
        .empty{
            border:1px dashed var(--stroke);
            background:rgba(255,255,255,.64);
            border-radius:14px;
            padding:16px;
            color:var(--muted);
            text-align:center;
        }
        .error{
            border:1px solid rgba(225,29,72,.24);
            background:rgba(225,29,72,.08);
            color:#9f1239;
            border-radius:14px;
            padding:12px 14px;
            font-size:14px;
        }
        .notice-ok{
            border:1px solid rgba(22,163,74,.28);
            background:rgba(22,163,74,.08);
            color:#166534;
            border-radius:14px;
            padding:12px 14px;
            font-size:14px;
            margin-top:12px;
        }
        label{
            display:block;
            font-size:12px;
            color:var(--muted);
            margin:12px 0 6px;
        }
        input{
            width:100%;
            padding:10px 12px;
            border-radius:12px;
            border:1px solid rgba(15,23,42,.18);
            background:#fff;
            color:var(--text);
            outline:none;
        }
        input:focus{
            box-shadow: 0 0 0 4px rgba(96,165,250,.18);
            border-color: rgba(96,165,250,.75);
        }
        .form-actions{
            margin-top:12px;
            display:flex;
            gap:10px;
            flex-wrap:wrap;
        }
    </style>
</head>
<body>
    <main class="container">
        <section class="header" aria-label="Шапка личного кабинета">
            <div>
                <h1>Личный кабинет</h1>
                <p class="muted">
                    <?= htmlspecialchars($fio !== '' ? $fio : 'Пользователь') ?>
                </p>
            </div>
            <div class="actions">
                <a class="btn btn-light" href="index.php">На главную</a>
                <a class="btn btn-light" href="cart.php">Корзина</a>
                <a class="btn btn-light" href="logout.php">Выйти</a>
            </div>
        </section>

        <section class="grid">
            <div class="card">
                <h2>Мои данные</h2>
                <div class="kv">
                    <div class="k">ФИО</div>
                    <div class="v"><?= htmlspecialchars($fio !== '' ? $fio : '-') ?></div>
                    <div class="k">Телефон</div>
                    <div class="v"><?= htmlspecialchars($phone !== '' ? $phone : '-') ?></div>
                </div>

                <h2 style="margin-top:18px;font-size:18px">Смена пароля</h2>
                <?php if ($message !== ''): ?>
                    <div class="<?= $messageType === 'error' ? 'error' : 'notice-ok' ?>">
                        <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" autocomplete="on">
                    <input type="hidden" name="action" value="change_password">

                    <label for="current_password">Текущий пароль</label>
                    <input id="current_password" name="current_password" type="password" required>

                    <label for="new_password">Новый пароль</label>
                    <input id="new_password" name="new_password" type="password" required>

                    <label for="confirm_password">Повторите новый пароль</label>
                    <input id="confirm_password" name="confirm_password" type="password" required>

                    <div class="form-actions">
                        <button class="btn btn-light" type="submit">Сменить пароль</button>
                    </div>
                </form>
            </div>

            <div class="card">
                <h2>Мои заказы</h2>
                <?php if (!empty($ordersError)): ?>
                    <div class="error"><?= htmlspecialchars($ordersError) ?></div>
                <?php elseif (empty($orders)): ?>
                    <div class="empty">Пока нет заказов.</div>
                <?php else: ?>
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Статус</th>
                                    <th>Сумма</th>
                                    <th>Дата</th>
                                    <th>Состав заказа</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                    <?php
                                        $orderId = (int)($order['id'] ?? 0);
                                        $statusCode = (string)($order['status'] ?? 'access');
                                        $statusRu = $statusLabels[$statusCode] ?? $statusCode;
                                        $items = $orderItemsByOrderId[$orderId] ?? [];
                                    ?>
                                    <tr>
                                        <td><?= htmlspecialchars($statusRu) ?></td>
                                        <td><?= htmlspecialchars((string)($order['total_price'] ?? 0)) ?></td>
                                        <td><?= htmlspecialchars((string)($order['order_date'] ?? '')) ?></td>
                                        <td>
                                            <?php if (empty($items)): ?>
                                                <span style="color:var(--muted)">Нет данных</span>
                                            <?php else: ?>
                                                <?php foreach ($items as $item): ?>
                                                    <div>
                                                        <?= htmlspecialchars($item['name']) ?>
                                                        x <?= (int)$item['quantity'] ?>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </main>
</body>
</html>

