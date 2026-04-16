<?php
    require_once __DIR__ . '/../settings/connect_database.php';

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $isAdmin = isset($_SESSION['role']) && (int)$_SESSION['role'] === 1;
    if ($isAdmin) {
        header('Location: admin.php');
        exit();
    }

    if (empty($_SESSION['user_id'])) {
        header('Location: login.php');
        exit();
    }

    $userId = (int)$_SESSION['user_id'];

    if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    $flash = $_SESSION['cart_flash'] ?? null;
    unset($_SESSION['cart_flash']);

    $cart = &$_SESSION['cart'];

    $setFlash = function (string $type, string $text) {
        $_SESSION['cart_flash'] = ['type' => $type, 'text' => $text];
        header('Location: cart.php');
        exit();
    };

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';

        try {
            if ($action === 'add') {
                $productId = (int)($_POST['product_id'] ?? 0);
                if ($productId <= 0) {
                    throw new Exception('Некорректный товар');
                }

                $sql = "SELECT `id`, `price` FROM `products` WHERE `id` = ? LIMIT 1";
                $query = $mysqli_connection->prepare($sql);
                if (!$query) {
                    throw new Exception('Ошибка подготовки запроса');
                }
                $query->bind_param('i', $productId);
                $query->execute();
                $result = $query->get_result();
                $product = $result ? $result->fetch_assoc() : null;
                if (!$product) {
                    throw new Exception('Товар не найден');
                }

                $qty = (int)($cart[$productId] ?? 0);
                $qty = max(0, $qty);
                $qty = min(99, $qty + 1);
                $cart[$productId] = $qty;

                $setFlash('ok', 'Товар добавлен в корзину');
            }

            if ($action === 'update_qty') {
                $productId = (int)($_POST['product_id'] ?? 0);
                $qty = (int)($_POST['qty'] ?? 0);

                if ($productId <= 0) {
                    throw new Exception('Некорректный товар');
                }
                if ($qty <= 0) {
                    unset($cart[$productId]);
                } else {
                    $qty = min(99, $qty);
                    $cart[$productId] = $qty;
                }

                $setFlash('ok', 'Количество обновлено');
            }

            if ($action === 'remove') {
                $productId = (int)($_POST['product_id'] ?? 0);
                if ($productId <= 0) {
                    throw new Exception('Некорректный товар');
                }
                unset($cart[$productId]);
                $setFlash('ok', 'Товар удален из корзины');
            }

            if ($action === 'checkout') {
                $items = $cart;
                if (empty($items)) {
                    throw new Exception('Корзина пуста');
                }

                $ids = array_map('intval', array_keys($items));
                $placeholders = implode(',', array_fill(0, count($ids), '?'));

                $sql = "SELECT `id`, `name`, `price`, `imagePath` FROM `products` WHERE `id` IN ($placeholders)";
                $query = $mysqli_connection->prepare($sql);
                if (!$query) {
                    throw new Exception('Ошибка подготовки запроса');
                }

                $types = str_repeat('i', count($ids));
                $bindArgs = [];
                $bindArgs[] = &$types;
                foreach ($ids as $k => $v) {
                    $bindArgs[] = &$ids[$k];
                }
                call_user_func_array([$query, 'bind_param'], $bindArgs);
                $query->execute();
                $result = $query->get_result();

                $productsMap = [];
                if ($result) {
                    while ($row = $result->fetch_assoc()) {
                        $productsMap[(int)$row['id']] = $row;
                    }
                }

                $total = 0.0;
                foreach ($items as $pid => $qty) {
                    $pid = (int)$pid;
                    $qty = (int)$qty;
                    if ($qty <= 0) {
                        continue;
                    }
                    if (!isset($productsMap[$pid])) {
                        continue;
                    }
                    $total += (float)$productsMap[$pid]['price'] * $qty;
                }

                if ($total <= 0) {
                    throw new Exception('Не удалось посчитать сумму заказа');
                }

                $status = 'access';
                $orderDate = date('Y-m-d H:i:s');
                $orderSql = "INSERT INTO `orders`(`user_id`, `status`, `total_price`, `order_date`) VALUES (?, ?, ?, ?)";
                $orderQuery = $mysqli_connection->prepare($orderSql);
                if (!$orderQuery) {
                    throw new Exception('Ошибка подготовки запроса оформления заказа');
                }
                $orderQuery->bind_param('isds', $userId, $status, $total, $orderDate);
                $orderQuery->execute();
                $orderId = (int)$mysqli_connection->insert_id;
                if ($orderId <= 0) {
                    throw new Exception('Не удалось создать заказ');
                }

                $itemSql = "INSERT INTO `order_items`(`order_id`, `product_id`, `quantity`) VALUES (?, ?, ?)";
                $itemQuery = $mysqli_connection->prepare($itemSql);
                if (!$itemQuery) {
                    throw new Exception('Ошибка подготовки запроса сохранения товаров заказа');
                }

                foreach ($items as $pid => $qty) {
                    $pid = (int)$pid;
                    $qty = (int)$qty;
                    if ($pid <= 0 || $qty <= 0 || !isset($productsMap[$pid])) {
                        continue;
                    }

                    $itemQuery->bind_param('iii', $orderId, $pid, $qty);
                    $itemQuery->execute();
                }

                $cart = [];

                $setFlash('ok', 'Заказ оформлен успешно');
            }

            throw new Exception('Неизвестное действие');
        } catch (Throwable $e) {
            $setFlash('error', $e->getMessage());
        }
    }

    // Сборка данных корзины для отображения
    $cartItems = $_SESSION['cart'];
    $products = [];
    $total = 0.0;

    if (!empty($cartItems)) {
        $ids = array_map('intval', array_keys($cartItems));
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "SELECT `id`, `name`, `price`, `imagePath` FROM `products` WHERE `id` IN ($placeholders)";
        $query = $mysqli_connection->prepare($sql);
        if ($query) {
        $types = str_repeat('i', count($ids));
        $bindArgs = [];
        $bindArgs[] = &$types;
        foreach ($ids as $k => $v) {
            $bindArgs[] = &$ids[$k];
        }
        call_user_func_array([$query, 'bind_param'], $bindArgs);
        $query->execute();
        $result = $query->get_result();

            $productsMap = [];
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $productsMap[(int)$row['id']] = $row;
                }
            }

            foreach ($cartItems as $pid => $qty) {
                $pid = (int)$pid;
                $qty = (int)$qty;
                if ($qty <= 0 || !isset($productsMap[$pid])) {
                    continue;
                }
                $product = $productsMap[$pid];
                $lineTotal = (float)$product['price'] * $qty;
                $total += $lineTotal;
                $products[] = [
                    'id' => $pid,
                    'name' => $product['name'] ?? '',
                    'price' => (float)($product['price'] ?? 0),
                    'imagePath' => $product['imagePath'] ?? '',
                    'qty' => $qty,
                    'lineTotal' => $lineTotal,
                ];
            }
        }
    }
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Корзина</title>
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
        .btn-danger{
            background:#ef4444;
            color:#fff;
            box-shadow:none;
        }
        .grid{
            margin-top:18px;
            display:grid;
            grid-template-columns:1fr;
            gap:14px;
        }
        .card{
            border:1px solid var(--stroke);
            background:linear-gradient(180deg, rgba(255,255,255,.95), rgba(255,255,255,.86));
            border-radius:16px;
            box-shadow:0 10px 28px rgba(15, 23, 42, .09);
            padding:14px;
        }
        .items{
            display:grid;
            gap:14px;
        }
        .item{
            display:grid;
            grid-template-columns:160px 1fr;
            gap:14px;
            align-items:start;
        }
        .img-wrap{
            height:120px;
            border-radius:12px;
            overflow:hidden;
            background:#eaf2ff;
            border:1px solid rgba(15, 23, 42, .08);
            display:flex;
            align-items:center;
            justify-content:center;
            color:var(--muted);
            font-size:13px;
            font-weight:600;
        }
        .img-wrap img{
            width:100%;
            height:100%;
            object-fit:cover;
            display:block;
        }
        @media (max-width: 640px){
            .item{grid-template-columns:1fr}
            .img-wrap{height:140px}
        }
        .item h2{margin:0 0 6px;font-size:18px}
        .meta{margin:0;color:var(--muted);font-size:13px}
        .price{
            font-size:18px;
            font-weight:800;
            margin-top:10px;
        }
        input, select{
            width:100%;
            padding:10px 12px;
            border-radius:12px;
            border:1px solid rgba(15,23,42,.18);
            background:#fff;
            color:var(--text);
            outline:none;
        }
        .row{
            display:flex;
            gap:10px;
            flex-wrap:wrap;
            align-items:center;
            margin-top:12px;
        }
        .notice{
            margin-top:14px;
            border-radius:14px;
            padding:12px 14px;
            font-size:14px;
        }
        .notice-ok{
            border:1px solid rgba(22,163,74,.28);
            background:rgba(22,163,74,.08);
            color:#166534;
        }
        .notice-error{
            border:1px solid rgba(239,68,68,.28);
            background:rgba(239,68,68,.08);
            color:#991b1b;
        }
        table{width:100%;border-collapse:collapse;font-size:13px}
        th,td{border:1px solid var(--stroke);padding:8px;text-align:left}
    </style>
</head>
<body>
    <main class="container">
        <section class="header">
            <div>
                <h1>Корзина</h1>
                <p class="muted">Выберите количество и оформите заказ</p>
            </div>
            <div class="actions">
                <a class="btn btn-light" href="account.php">Личный кабинет</a>
                <a class="btn btn-light" href="index.php">На главную</a>
                <a class="btn btn-light" href="logout.php">Выйти</a>
            </div>
        </section>

        <?php if ($flash): ?>
            <div class="notice <?= htmlspecialchars($flash['type'] ?? 'ok') === 'error' ? 'notice-error' : 'notice-ok' ?>">
                <?= htmlspecialchars($flash['text'] ?? '') ?>
            </div>
        <?php endif; ?>

        <section class="grid">
            <?php if (empty($products)): ?>
                <div class="card">
                    <div style="color:var(--muted);text-align:center;padding:12px 0;">Корзина пуста.</div>
                </div>
            <?php else: ?>
                <div class="card">
                    <div class="items">
                        <?php foreach ($products as $p): ?>
                            <div class="item">
                                <div class="img-wrap">
                                    <?php if (!empty($p['imagePath'])): ?>
                                        <img src="<?= htmlspecialchars($p['imagePath']) ?>" alt="<?= htmlspecialchars($p['name']) ?>">
                                    <?php else: ?>
                                        <span>Нет изображения</span>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <h2><?= htmlspecialchars($p['name']) ?></h2>
                                    <p class="meta">Цена: <?= number_format((float)$p['price'], 0, '.', ' ') ?> руб. / шт.</p>
                                    <div class="price">
                                        Итого: <?= number_format((float)$p['lineTotal'], 0, '.', ' ') ?> руб.
                                    </div>

                                    <div class="row">
                                        <form method="POST" action="cart.php" style="flex:1;min-width:180px;">
                                            <input type="hidden" name="action" value="update_qty">
                                            <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">
                                            <input type="number" name="qty" min="1" max="99" value="<?= (int)$p['qty'] ?>">
                                            <div class="row">
                                                <button class="btn btn-light" type="submit">Обновить</button>
                                            </div>
                                        </form>

                                        <form method="POST" action="cart.php" style="min-width:140px;">
                                            <input type="hidden" name="action" value="remove">
                                            <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">
                                            <div class="row" style="margin-top:0;">
                                                <button class="btn btn-danger" type="submit">Удалить</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="card">
                    <h2 style="margin:0 0 10px;font-size:18px;">Оформление заказа</h2>
                    <table>
                        <tr>
                            <th>Сумма</th>
                            <td><?= number_format((float)$total, 0, '.', ' ') ?> руб.</td>
                        </tr>
                    </table>
                    <form method="POST" action="cart.php">
                        <input type="hidden" name="action" value="checkout">
                        <div class="row">
                            <button class="btn" type="submit" style="width:100%;">Оформить заказ</button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>

