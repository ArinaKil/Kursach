<?php
    require_once __DIR__ . '/../settings/admin_common.php';

    $message = '';
    $messageType = 'ok';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';

        try {
            if ($action === 'create') {
                $fio = trim($_POST['fio'] ?? '');
                $phoneRaw = trim($_POST['phone'] ?? '');
                $password = (string)($_POST['password'] ?? '');
                $role = (int)($_POST['role'] ?? 0);

                if ($fio === '' || $phoneRaw === '' || $password === '') {
                    throw new Exception('Для создания пользователя заполните все поля');
                }
                if (!validateFio($fio)) {
                    throw new Exception('ФИО должно содержать только кириллицу, пробелы и дефис');
                }
                if (!validatePhone($phoneRaw)) {
                    throw new Exception('Некорректный формат телефона. Ожидается +7 (XXX) XXX-XX-XX');
                }

                $phone = formatPhone($phoneRaw);
                if (phoneExists($mysqli_connection, $phone)) {
                    throw new Exception('Пользователь с таким телефоном уже существует');
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
                $phoneRaw = trim($_POST['phone'] ?? '');
                $role = (int)($_POST['role'] ?? 0);
                $newPassword = (string)($_POST['password'] ?? '');

                if ($id <= 0 || $fio === '' || $phoneRaw === '') {
                    throw new Exception('Некорректные данные пользователя для обновления');
                }
                if (!validateFio($fio)) {
                    throw new Exception('ФИО должно содержать только кириллицу, пробелы и дефис');
                }
                if (!validatePhone($phoneRaw)) {
                    throw new Exception('Некорректный формат телефона. Ожидается +7 (XXX) XXX-XX-XX');
                }

                $phone = formatPhone($phoneRaw);
                if (phoneExists($mysqli_connection, $phone, $id)) {
                    throw new Exception('Этот телефон уже используется другим пользователем');
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
        } catch (Throwable $e) {
            $messageType = 'error';
            $message = $e->getMessage();
        }
    }

    $users = [];
    $usersResult = $mysqli_connection->query("SELECT `id`, `fio`, `phone`, `role` FROM `users` ORDER BY `id` DESC");
    if ($usersResult) {
        while ($row = $usersResult->fetch_assoc()) {
            $users[] = $row;
        }
    }

    $adminPageTitle = 'Пользователи';
    $adminPageSubtitle = 'Управление учётными записями';
    $adminActive = 'users';
    require __DIR__ . '/admin_header.php';
?>

<?php if ($message !== ''): ?>
    <div class="notice <?= $messageType === 'error' ? 'notice-error' : 'notice-ok' ?>">
        <?= e($message) ?>
    </div>
<?php endif; ?>

<div class="grid">
    <section class="section">
        <h2>Добавить пользователя</h2>
        <form method="POST" class="form-row" id="createUserForm">
            <input type="hidden" name="action" value="create">
            <input name="fio" type="text" placeholder="ФИО (кириллица)" data-fio required>
            <input name="phone" type="tel" placeholder="+7 (900) 000-00-00" data-phone required>
            <input name="password" type="password" placeholder="Пароль" required>
            <select name="role">
                <option value="0">Пользователь (0)</option>
                <option value="1">Админ (1)</option>
            </select>
            <button type="submit" class="btn btn-primary">Создать</button>
        </form>
    </section>

    <section class="section">
        <h2>Список пользователей</h2>
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
                                    <input type="hidden" name="id" value="<?= (int)$user['id'] ?>">
                                </td>
                                <td><input type="text" name="fio" value="<?= e((string)$user['fio']) ?>" data-fio required></td>
                                <td><input type="tel" name="phone" value="<?= e((string)$user['phone']) ?>" data-phone required></td>
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
</div>

<script>
    (function () {
        const cyrillicAllowed = /[Ѐ-ӿ\s\-]/g;

        function sanitizeFio(value) {
            const matches = value.match(cyrillicAllowed);
            return matches ? matches.join('') : '';
        }

        function attachFio(input) {
            input.addEventListener('input', function () {
                const start = input.selectionStart;
                const before = input.value;
                const cleaned = sanitizeFio(before);
                if (cleaned !== before) {
                    input.value = cleaned;
                    const diff = before.length - cleaned.length;
                    const pos = Math.max(0, start - diff);
                    input.setSelectionRange(pos, pos);
                }
            });

            input.addEventListener('paste', function (e) {
                e.preventDefault();
                const text = (e.clipboardData || window.clipboardData).getData('text');
                const cleaned = sanitizeFio(text);
                const start = input.selectionStart;
                const end = input.selectionEnd;
                input.value = input.value.slice(0, start) + cleaned + input.value.slice(end);
                const pos = start + cleaned.length;
                input.setSelectionRange(pos, pos);
            });
        }

        function formatPhone(digits) {
            if (digits.length === 0) return '';
            if (digits[0] === '8') digits = '7' + digits.slice(1);
            if (digits[0] !== '7') digits = '7' + digits;
            digits = digits.slice(0, 11);

            let result = '+7';
            if (digits.length > 1) result += ' (' + digits.slice(1, 4);
            if (digits.length >= 4) result += ')';
            if (digits.length >= 5) result += ' ' + digits.slice(4, 7);
            if (digits.length >= 8) result += '-' + digits.slice(7, 9);
            if (digits.length >= 10) result += '-' + digits.slice(9, 11);
            return result;
        }

        function attachPhone(input) {
            function applyMask() {
                const digits = input.value.replace(/\D/g, '');
                input.value = formatPhone(digits);
            }

            input.addEventListener('focus', function () {
                if (!input.value) input.value = '+7 (';
            });

            input.addEventListener('input', applyMask);

            input.addEventListener('blur', function () {
                if (input.value === '+7 (' || input.value === '+7') input.value = '';
            });

            input.addEventListener('keydown', function (e) {
                if (e.key === 'Backspace' && input.selectionStart === input.value.length) {
                    const digits = input.value.replace(/\D/g, '');
                    if (digits.length > 1) {
                        e.preventDefault();
                        input.value = formatPhone(digits.slice(0, -1));
                    }
                }
            });

            input.addEventListener('paste', function (e) {
                e.preventDefault();
                const text = (e.clipboardData || window.clipboardData).getData('text');
                const digits = (input.value + text).replace(/\D/g, '');
                input.value = formatPhone(digits);
            });

            if (input.value) applyMask();
        }

        document.querySelectorAll('input[data-fio]').forEach(attachFio);
        document.querySelectorAll('input[data-phone]').forEach(attachPhone);
    })();
</script>

<?php require __DIR__ . '/admin_footer.php'; ?>
