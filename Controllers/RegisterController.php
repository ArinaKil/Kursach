<?php
    require_once __DIR__ . '/../Models/User.php';
    class RegisterController 
    {
        public function register($fio, $phone, $password) 
        {
            global $mysqli_connection;
            
            if (empty($fio) || empty($phone) || empty($password)) {
                return "Заполните все поля";
            } else {
                if (!empty($phone)) {
                    $sql = "SELECT * FROM `users` WHERE `phone` = ?";
                    $query = $mysqli_connection->prepare($sql);
                    $query->bind_param("s", $phone);
                    $query->execute();
                    $result = $query->get_result();

                    if ($result->num_rows > 0) {
                        return "Такой номер уже зарегистрирован";
                    }
                }
                
                $fio = htmlspecialchars($fio);
                $phone = htmlspecialchars($phone);
                $password = htmlspecialchars($password);
                $standart_role = 0;

                $password = password_hash($password, PASSWORD_DEFAULT);
                $sql = "INSERT INTO `users`(`fio`, `phone`, `password_hash`, `role`) VALUES (?, ?, ?, ?)";
                $query = $mysqli_connection->prepare($sql);
                $query->bind_param("sssi", $fio, $phone, $password, $standart_role);

                if ($query->execute()) {
                    session_start();

                    $newUserId = $mysqli_connection->insert_id;
                    $_SESSION['user_id'] = $newUserId;
                    $_SESSION['fio'] = $fio;
                    $_SESSION['phone'] = $phone;
                    $_SESSION['role'] = $standart_role;

                    header("Location: ../pages/index.php");
                    exit();
                }

                return "Ошибка регистрации";
            }
        }
    }
?>