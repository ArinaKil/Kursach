<?php
    require_once __DIR__ . '/../settings/connect_database.php';
    require_once __DIR__ . '/../Controllers/LoginController.php';

    $error = null;
    $phoneValue = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $phoneValue = trim($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';

        $controller = new LoginController();
        $error = $controller->login($phoneValue, $password);
    }
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход</title>
    <style>
        :root{
            --bg1:#f6fbff;
            --bg2:#fffaf2;
            --card:#ffffff;
            --card2:#ffffff;
            --stroke:rgba(15, 23, 42, .12);
            --text:#0f172a;
            --muted:rgba(15, 23, 42, .68);
            --accent:#22c55e;   
            --accent2:#60a5fa;  
            --danger:#e11d48;
            --shadow:0 18px 50px rgba(15, 23, 42, .12);
            --radius:18px;
        }
        *{box-sizing:border-box}
        html,body{height:100%}
        body{
            margin:0;
            font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial, "Noto Sans", "Helvetica Neue", sans-serif;
            color:var(--text);
            background:
                radial-gradient(900px 650px at 12% 10%, rgba(96,165,250,.28), transparent 60%),
                radial-gradient(820px 620px at 88% 18%, rgba(34,197,94,.18), transparent 60%),
                radial-gradient(900px 700px at 50% 100%, rgba(251, 191, 36, .16), transparent 55%),
                linear-gradient(160deg, var(--bg1), var(--bg2));
            display:flex;
            align-items:center;
            justify-content:center;
            padding:32px 16px;
        }
        .shell{
            width:min(980px, 100%);
            display:grid;
            grid-template-columns: 1.1fr .9fr;
            gap:22px;
            align-items:stretch;
        }
        @media (max-width: 860px){
            .shell{grid-template-columns:1fr}
        }
        .hero{
            border:1px solid var(--stroke);
            background:linear-gradient(180deg, rgba(255,255,255,.92), rgba(255,255,255,.78));
            border-radius:var(--radius);
            box-shadow:var(--shadow);
            padding:28px;
            position:relative;
            overflow:hidden;
            min-height:360px;
        }
        .hero::before{
            content:"";
            position:absolute;
            inset:-2px;
            background:
                radial-gradient(520px 260px at 30% 10%, rgba(96,165,250,.22), transparent 70%),
                radial-gradient(520px 260px at 70% 40%, rgba(34,197,94,.16), transparent 70%);
            filter: blur(0px);
            opacity:.9;
            pointer-events:none;
        }
        .hero > *{position:relative}
        .brand{
            display:flex;
            align-items:center;
            gap:10px;
            margin-bottom:14px;
        }
        .logo{
            width:44px;height:44px;
            border-radius:14px;
            background:linear-gradient(135deg, rgba(96,165,250,.95), rgba(34,197,94,.90));
            box-shadow:0 12px 30px rgba(96,165,250,.20);
            display:grid;
            place-items:center;
            color:#0b1220;
            font-weight:800;
            letter-spacing:.5px;
        }
        .brand-title{font-weight:800;font-size:18px;line-height:1.1}
        .brand-sub{color:var(--muted);font-size:13px;margin-top:2px}
        h1{
            margin:14px 0 10px;
            font-size:34px;
            line-height:1.12;
            letter-spacing:-.6px;
        }
        .lead{
            margin:0;
            color:var(--muted);
            font-size:15px;
            line-height:1.55;
            max-width:54ch;
        }
        .badges{
            display:flex;
            flex-wrap:wrap;
            gap:10px;
            margin-top:18px;
        }
        .badge{
            border:1px solid var(--stroke);
            background:rgba(255,255,255,.75);
            padding:8px 10px;
            border-radius:999px;
            font-size:12px;
            color:var(--muted);
            backdrop-filter: blur(6px);
        }
        .card{
            border:1px solid var(--stroke);
            background:linear-gradient(180deg, rgba(255,255,255,.95), rgba(255,255,255,.86));
            border-radius:var(--radius);
            box-shadow:var(--shadow);
            padding:22px;
        }
        .card h2{
            margin:0 0 6px;
            font-size:18px;
        }
        .card p{
            margin:0 0 14px;
            color:var(--muted);
            font-size:13px;
            line-height:1.45;
        }
        form{margin-top:10px}
        .field{margin:12px 0}
        label{
            display:block;
            font-size:12px;
            color:var(--muted);
            margin:0 0 6px;
        }
        input{
            width:100%;
            padding:12px 12px;
            border-radius:12px;
            border:1px solid rgba(15, 23, 42, .14);
            background:rgba(255,255,255,.92);
            color:var(--text);
            outline:none;
            transition: border-color .15s ease, box-shadow .15s ease, transform .02s ease;
        }
        input::placeholder{color:rgba(15, 23, 42, .42)}
        input:focus{
            border-color: rgba(96,165,250,.75);
            box-shadow: 0 0 0 4px rgba(96,165,250,.18);
        }
        .row{
            display:flex;
            justify-content:space-between;
            align-items:center;
            gap:10px;
            margin-top:10px;
        }
        .btn{
            appearance:none;
            border:none;
            cursor:pointer;
            border-radius:12px;
            padding:12px 14px;
            font-weight:700;
            color:#0b1220;
            background: linear-gradient(135deg, var(--accent2), var(--accent));
            box-shadow: 0 14px 34px rgba(34,197,94,.18);
            width:100%;
            transition: transform .06s ease, filter .15s ease;
        }
        .btn:active{transform: translateY(1px)}
        .btn:hover{filter: brightness(1.03)}
        .links{
            margin-top:12px;
            display:flex;
            justify-content:space-between;
            gap:10px;
            flex-wrap:wrap;
            font-size:12px;
        }
        a{color:rgba(37, 99, 235, .92);text-decoration:none}
        a:hover{text-decoration:underline}
        .error{
            border:1px solid rgba(225,29,72,.22);
            background: rgba(225,29,72,.08);
            color: #9f1239;
            padding:10px 12px;
            border-radius:12px;
            font-size:13px;
            line-height:1.35;
            margin-top:10px;
        }
        .footer-note{
            margin-top:16px;
            color:rgba(15, 23, 42, .55);
            font-size:11px;
            line-height:1.45;
        }
    </style>
</head>
<body>
    <div class="shell">
        <section class="hero" aria-label="Описание магазина">

            <h1>Вход в личный кабинет</h1>
            <p class="lead">
                Авторизуйтесь, чтобы оформлять заказы и отслеживать статус доставки.
            </p>

            <div class="badges" aria-label="Преимущества">
                <div class="badge">Быстрый заказ</div>
                <div class="badge">Классные товары</div>
                <div class="badge">История покупок</div>
            </div>
        </section>

        <section class="card" aria-label="Форма входа">
            <h2>Авторизация</h2>
            <p>Введите телефон и пароль, указанные при регистрации.</p>

            <?php if (!empty($error)): ?>
                <div class="error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" autocomplete="on" novalidate>
                <div class="field">
                    <label for="phone">Телефон</label>
                    <input
                        id="phone"
                        name="phone"
                        type="tel"
                        inputmode="tel"
                        placeholder="+7 (900) 000-00-00"
                        value="<?= htmlspecialchars($phoneValue) ?>"
                        required
                    />
                </div>

                <div class="field">
                    <label for="password">Пароль</label>
                    <input
                        id="password"
                        name="password"
                        type="password"
                        placeholder="Введите пароль"
                        required
                    />
                </div>

                <div class="row">
                    <button class="btn" type="submit">Войти</button>
                </div>

                <div class="links">
                    <a href="register.php">Нет аккаунта? Регистрация</a>
                </div>
            </form>
        </section>
    </div>
</body>
</html>