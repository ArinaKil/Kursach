<?php
    $adminPageTitle = $adminPageTitle ?? 'Админ-панель';
    $adminPageSubtitle = $adminPageSubtitle ?? '';
    $adminActive = $adminActive ?? '';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($adminPageTitle) ?></title>
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
            display:inline-block;
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
        .nav{
            display:flex;
            gap:8px;
            flex-wrap:wrap;
            margin-top:14px;
        }
        .nav a{
            padding:9px 14px;
            border-radius:10px;
            border:1px solid var(--stroke);
            background:#fff;
            color:var(--text);
            text-decoration:none;
            font-weight:600;
            font-size:14px;
        }
        .nav a.active{
            background:#0f172a;
            color:#fff;
            border-color:#0f172a;
        }
        .grid{
            display:grid;
            grid-template-columns:1fr;
            gap:14px;
            margin-top:14px;
        }
        .cards{
            display:grid;
            grid-template-columns:repeat(auto-fit,minmax(220px,1fr));
            gap:14px;
            margin-top:14px;
        }
        .card{
            border:1px solid var(--stroke);
            border-radius:var(--radius);
            background:linear-gradient(180deg, rgba(255,255,255,.95), rgba(255,255,255,.86));
            box-shadow:var(--shadow);
            padding:18px;
            display:flex;
            flex-direction:column;
            gap:10px;
        }
        .card h3{margin:0;font-size:18px}
        .card p{margin:0;color:var(--muted);font-size:13px;line-height:1.45;flex:1}
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
            font-family:inherit;
            font-size:13px;
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
                <h1><?= e($adminPageTitle) ?></h1>
                <?php if ($adminPageSubtitle !== ''): ?>
                    <p class="muted"><?= e($adminPageSubtitle) ?></p>
                <?php endif; ?>
            </div>
            <div class="actions">
                <?php if ($adminActive !== 'dashboard'): ?>
                    <a class="btn" href="admin.php">На главную админки</a>
                <?php endif; ?>
                <a class="btn" href="logout.php">Выйти</a>
            </div>
        </section>

        <?php if ($adminActive !== 'dashboard'): ?>
            <nav class="nav">
                <a href="admin_users.php" class="<?= $adminActive === 'users' ? 'active' : '' ?>">Пользователи</a>
                <a href="admin_products.php" class="<?= $adminActive === 'products' ? 'active' : '' ?>">Товары</a>
                <a href="admin_orders.php" class="<?= $adminActive === 'orders' ? 'active' : '' ?>">Заказы</a>
            </nav>
        <?php endif; ?>
