<?php
    require_once __DIR__ . '/../settings/connect_database.php';

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (isset($_SESSION['role']) && (int)$_SESSION['role'] === 1) {
        header('Location: admin.php');
        exit();
    }

    $isAuthorized = !empty($_SESSION['user_id']);
    $userName = trim((string)($_SESSION['fio'] ?? ''));

    if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isAuthorized) {
        $action = $_POST['action'] ?? '';
        $productId = (int)($_POST['product_id'] ?? 0);

        if ($productId > 0) {
            $currentQty = (int)($_SESSION['cart'][$productId] ?? 0);

            if ($action === 'add') {
                $_SESSION['cart'][$productId] = min(99, max(1, $currentQty + 1));
            } elseif ($action === 'inc') {
                $_SESSION['cart'][$productId] = min(99, max(1, $currentQty + 1));
            } elseif ($action === 'dec') {
                $newQty = $currentQty - 1;
                if ($newQty <= 0) {
                    unset($_SESSION['cart'][$productId]);
                } else {
                    $_SESSION['cart'][$productId] = $newQty;
                }
            }
        }

        header('Location: index.php');
        exit();
    }

    $products = [];
    $productsError = null;

    if (isset($mysqli_connection) && $mysqli_connection instanceof mysqli) {
        $sql = "SELECT `id`, `name`, `category`, `price`, `imagePath` FROM `products` ORDER BY `id` DESC";
        $query = $mysqli_connection->prepare($sql);

        if ($query) {
            $query->execute();
            $result = $query->get_result();

            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $products[] = $row;
                }
            }
        } else {
            $productsError = 'Не удалось загрузить список товаров';
        }
    } else {
        $productsError = 'Нет подключения к базе данных';
    }
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Главная</title>
    <style>
        :root{
            --bg1:#f6fbff;
            --bg2:#fffaf2;
            --card:#ffffff;
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
                radial-gradient(900px 700px at 50% 100%, rgba(251,191,36,.16), transparent 55%),
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
        .title{
            margin:0;
            font-size:30px;
            line-height:1.15;
            letter-spacing:-.5px;
        }
        .subtitle{
            margin:6px 0 0;
            color:var(--muted);
            font-size:14px;
        }
        .actions{
            display:flex;
            gap:10px;
            flex-wrap:wrap;
        }
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
            transition:filter .15s ease, transform .06s ease;
        }
        .btn:hover{filter:brightness(1.03)}
        .btn:active{transform:translateY(1px)}
        .btn-light{
            background:#ffffff;
            border:1px solid var(--stroke);
            box-shadow:none;
        }
        .catalog{
            margin-top:18px;
            display:grid;
            grid-template-columns:repeat(auto-fill, minmax(250px, 1fr));
            gap:14px;
        }
        .card{
            border:1px solid var(--stroke);
            background:linear-gradient(180deg, rgba(255,255,255,.95), rgba(255,255,255,.86));
            border-radius:16px;
            box-shadow:0 10px 28px rgba(15, 23, 42, .09);
            padding:14px;
            display:flex;
            flex-direction:column;
            gap:10px;
        }
        .img-wrap{
            height:170px;
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
        .name{
            margin:0;
            font-size:17px;
            line-height:1.25;
        }
        .meta{
            color:var(--muted);
            font-size:13px;
            margin:0;
        }
        .price{
            font-size:20px;
            font-weight:800;
            margin-top:auto;
        }
        .qty-controls{
            display:flex;
            align-items:center;
            gap:8px;
            margin-top:6px;
        }
        .qty{
            min-width:36px;
            text-align:center;
            font-size:14px;
            font-weight:700;
            color:var(--text);
        }
        .qty-btn{
            width:36px;
            height:36px;
            border-radius:10px;
            border:1px solid var(--stroke);
            background:#fff;
            color:var(--text);
            font-size:20px;
            line-height:1;
            cursor:pointer;
            display:inline-flex;
            align-items:center;
            justify-content:center;
            padding:0;
        }
        .hint{
            margin-top:18px;
            border:1px solid var(--stroke);
            background:rgba(255,255,255,.82);
            border-radius:14px;
            padding:12px 14px;
            color:var(--muted);
            font-size:13px;
        }
        .error{
            margin-top:18px;
            border:1px solid rgba(225,29,72,.24);
            background:rgba(225,29,72,.08);
            color:#9f1239;
            border-radius:14px;
            padding:12px 14px;
            font-size:14px;
        }
        .empty{
            margin-top:18px;
            border:1px dashed var(--stroke);
            background:rgba(255,255,255,.64);
            border-radius:14px;
            padding:16px;
            color:var(--muted);
            text-align:center;
        }
    </style>
</head>
<body>
    <main class="container">
        <section class="header" aria-label="Шапка магазина">
            <div>
                <h1 class="title">Магазин зоотоваров</h1>
                <?php if ($isAuthorized): ?>
                    <p class="subtitle">Здравствуйте, <?= htmlspecialchars($userName !== '' ? $userName : 'пользователь') ?>. Покупайте что душе угодно.</p>
                <?php endif; ?>
            </div>

            <div class="actions">
                <?php if ($isAuthorized): ?>
                    <?php if (isset($_SESSION['role']) && (int)$_SESSION['role'] === 1): ?>
                        <a class="btn btn-light" href="admin.php">Админ-панель</a>
                    <?php endif; ?>
                    <?php if (!isset($_SESSION['role']) || (int)$_SESSION['role'] !== 1): ?>
                        <a class="btn btn-light" href="account.php">Личный кабинет</a>
                        <a class="btn btn-light" href="cart.php">Корзина</a>
                    <?php endif; ?>
                    <a class="btn btn-light" href="logout.php">Выйти</a>
                <?php else: ?>
                    <a class="btn btn-light" href="login.php">Войти</a>
                    <a class="btn" href="register.php">Регистрация</a>
                <?php endif; ?>
            </div>
        </section>

        <?php if (!empty($productsError)): ?>
            <div class="error"><?= htmlspecialchars($productsError) ?></div>
        <?php elseif (empty($products)): ?>
            <div class="empty">Пока нет доступных товаров.</div>
        <?php else: ?>
            <section class="catalog" aria-label="Каталог товаров">
                <?php foreach ($products as $product): ?>
                    <article class="card">
                        <div class="img-wrap">
                            <?php if (!empty($product['imagePath'])): ?>
                                <img src="<?= htmlspecialchars($product['imagePath']) ?>" alt="<?= htmlspecialchars($product['name'] ?? 'Товар') ?>" />
                            <?php else: ?>
                                <span>Нет изображения</span>
                            <?php endif; ?>
                        </div>

                        <h2 class="name"><?= htmlspecialchars($product['name'] ?? 'Без названия') ?></h2>
                        <p class="meta">Категория: <?= htmlspecialchars($product['category'] ?? 'Не указана') ?></p>
                        <div class="price"><?= number_format((float)($product['price'] ?? 0), 0, '.', ' ') ?> руб.</div>

                        <?php if ($isAuthorized): ?>
                            <?php $pid = (int)($product['id'] ?? 0); ?>
                            <?php $cartQty = (int)($_SESSION['cart'][$pid] ?? 0); ?>
                            <?php if ($cartQty > 0): ?>
                                <div class="qty-controls">
                                    <form method="POST">
                                        <input type="hidden" name="action" value="dec">
                                        <input type="hidden" name="product_id" value="<?= $pid ?>">
                                        <button class="qty-btn" type="submit" aria-label="Уменьшить количество">-</button>
                                    </form>
                                    <div class="qty"><?= $cartQty ?></div>
                                    <form method="POST">
                                        <input type="hidden" name="action" value="inc">
                                        <input type="hidden" name="product_id" value="<?= $pid ?>">
                                        <button class="qty-btn" type="submit" aria-label="Увеличить количество">+</button>
                                    </form>
                                </div>
                            <?php else: ?>
                                <form method="POST">
                                    <input type="hidden" name="action" value="add">
                                    <input type="hidden" name="product_id" value="<?= $pid ?>">
                                    <button class="btn" type="submit">Добавить в корзину</button>
                                </form>
                            <?php endif; ?>
                        <?php else: ?>
                            <a class="btn btn-light" href="login.php">Войдите, чтобы добавить</a>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>
    </main>
</body>
</html>