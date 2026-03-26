<?php
    require_once __DIR__ . '/../Models/User.php';
    class LoginController 
    {
        public function login($phone, $password) 
        {
            global $mysqli_connection;

            if (empty($phone) || empty($password)) {
                return "Заполните все поля";
            }

            $phone = htmlspecialchars($phone);
            $password = htmlspecialchars($password);

            $sql = "SELECT * FROM `users` WHERE `phone` = ?";
            $query = $mysqli_connection->prepare($sql);
            $query->bind_param("s", $phone);
            $query->execute();
            $result = $query->get_result();

            if ($result && $result->num_rows > 0) {
                $userRow = $result->fetch_assoc();
                $password_hash = $userRow['password_hash'] ?? null;

                if ($password_hash && password_verify($password, $password_hash)) {
                    session_start();

                    $_SESSION['user_id'] = $userRow['id'];
                    $_SESSION['fio'] = $userRow['fio'];
                    $_SESSION['phone'] = $userRow['phone'];
                    $_SESSION['role'] = $userRow['role'];

                    header("Location: ../pages/index.php");
                    exit();
                }
            }

            return "Неверный номер или пароль";
        }
    }
?>