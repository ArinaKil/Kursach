<?php
    require_once __DIR__ . '/../Models/User.php';
    class UserController 
    {
        public function getUsers() 
        {
            global $mysqli_connection;
            
            $sql = "SELECT * FROM `users`";
            $query = $mysqli_connection->prepare($sql);
            $query->execute();
            $result = $query->get_result();

            $users = [];
            while ($row = $result->fetch_assoc()) {
                $users[] = new User(
                    $row['id'] ?? null,
                    $row['fio'] ?? null,
                    $row['phone'] ?? null,
                    $row['password_hash'] ?? null,
                    $row['role'] ?? null
                );
            }

            return $users;
        }

        public function getUserById($id) 
        {
            global $mysqli_connection;
            
            $sql = "SELECT * FROM `users` WHERE `id` = ?";
            $query = $mysqli_connection->prepare($sql);
            $query->bind_param("i", $id);
            $query->execute();
        }

        public function createUser($fio, $phone, $password, $role) 
        {
            global $mysqli_connection;

            $password = password_hash($password, PASSWORD_DEFAULT);
            $sql = "INSERT INTO `users`(`fio`, `phone`, `password_hash`, `role`) VALUES (?, ?, ?, ?)";
            $query = $mysqli_connection->prepare($sql);
            $query->bind_param("sssi", $fio, $phone, $password, $role);
            $query->execute();
        }

        public function updateUser($id, $fio, $phone, $password, $role) 
        {
            global $mysqli_connection;
            $password = password_hash($password, PASSWORD_DEFAULT);
            $sql = "UPDATE `users` SET `fio` = ?, `phone` = ?, `password_hash` = ?, `role` = ? WHERE `id` = ?";
            $query = $mysqli_connection->prepare($sql);
            $query->bind_param("ssssi", $fio, $phone, $password, $role, $id);
            $query->execute();
        }

        public function deleteUser($id) 
        {
            global $mysqli_connection;
            
            $sql = "DELETE FROM `users` WHERE `id` = ?";
            $query = $mysqli_connection->prepare($sql);
            $query->bind_param("i", $id);
            $query->execute();
        }
    }
?>