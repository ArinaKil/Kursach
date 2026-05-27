<?php
    require_once __DIR__ . '/connect_database.php';

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $isAdmin = isset($_SESSION['role']) && (int)$_SESSION['role'] === 1;
    if (!$isAdmin) {
        header('Location: index.php');
        exit();
    }

    if (!function_exists('e')) {
        function e(string $value): string
        {
            return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        }
    }

    if (!function_exists('normalizePhoneDigits')) {
        function normalizePhoneDigits(string $phone): string
        {
            $digits = preg_replace('/\D+/', '', $phone);
            if ($digits === '') {
                return '';
            }
            if ($digits[0] === '8') {
                $digits = '7' . substr($digits, 1);
            }
            if ($digits[0] !== '7') {
                $digits = '7' . $digits;
            }
            return substr($digits, 0, 11);
        }
    }

    if (!function_exists('formatPhone')) {
        function formatPhone(string $phone): string
        {
            $digits = normalizePhoneDigits($phone);
            if (strlen($digits) !== 11) {
                return $phone;
            }
            return sprintf(
                '+7 (%s) %s-%s-%s',
                substr($digits, 1, 3),
                substr($digits, 4, 3),
                substr($digits, 7, 2),
                substr($digits, 9, 2)
            );
        }
    }

    if (!function_exists('validateFio')) {
        function validateFio(string $fio): bool
        {
            return (bool)preg_match('/^[\x{0400}-\x{04FF}\s\-]+$/u', $fio);
        }
    }

    if (!function_exists('validatePhone')) {
        function validatePhone(string $phone): bool
        {
            return strlen(normalizePhoneDigits($phone)) === 11;
        }
    }

    if (!function_exists('phoneExists')) {
        function phoneExists(mysqli $db, string $phone, ?int $excludeUserId = null): bool
        {
            $digits = normalizePhoneDigits($phone);
            if ($digits === '') {
                return false;
            }

            if ($excludeUserId === null) {
                $sql = "SELECT `phone` FROM `users`";
                $result = $db->query($sql);
            } else {
                $sql = "SELECT `phone` FROM `users` WHERE `id` <> ?";
                $query = $db->prepare($sql);
                $query->bind_param('i', $excludeUserId);
                $query->execute();
                $result = $query->get_result();
            }

            if (!$result) {
                return false;
            }

            while ($row = $result->fetch_assoc()) {
                if (normalizePhoneDigits((string)$row['phone']) === $digits) {
                    return true;
                }
            }
            return false;
        }
    }
?>
