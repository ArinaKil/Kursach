<?php
    require_once __DIR__ . '/../Models/Order.php';
    class OrderController 
    {
        public function getOrders() 
        {
            global $mysqli_connection;

            $sql = "SELECT * FROM `orders`";
            $query = $mysqli_connection->prepare($sql);
            $query->execute();
            $result = $query->get_result();

            $orders = [];
            while ($row = $result->fetch_assoc()) {
                $orders[] = new Order(
                    $row['id'] ?? null,
                    $row['user_id'] ?? null,
                    $row['total_price'] ?? null,
                    $row['order_date'] ?? null,
                    $row['status'] ?? "access"
                );
            }
            return $orders;
        }

        public function getOrderById($id) 
        {
            global $mysqli_connection;
            
            $sql = "SELECT * FROM `orders` WHERE `id` = ?";
            $query = $mysqli_connection->prepare($sql);
            $query->bind_param("i", $id);
            $query->execute();
            $result = $query->get_result();

            if ($row = $result->fetch_assoc()) {
                return new Order(
                    $row['id'] ?? null,
                    $row['user_id'] ?? null,
                    $row['total_price'] ?? null,
                    $row['order_date'] ?? null,
                    $row['status'] ?? "access"
                );
            }

            return null;
        }

        public function createOrder($user_id, $total_price, $order_date, $status = "access") 
        {
            global $mysqli_connection;
            
            $sql = "INSERT INTO `orders`(`user_id`, `status`, `total_price`, `order_date`) VALUES (?, ?, ?, ?)";
            $query = $mysqli_connection->prepare($sql);
            $query->bind_param("isds", $user_id, $status, $total_price, $order_date);

            return $query->execute();
        }

        public function updateOrder($id, $user_id, $total_price, $order_date, $status) 
        {
            global $mysqli_connection;
            
            $sql = "UPDATE `orders` SET `user_id` = ?, `status` = ?, `total_price` = ?, `order_date` = ? WHERE `id` = ?";
            $query = $mysqli_connection->prepare($sql);
            $query->bind_param("isdsi", $user_id, $status, $total_price, $order_date, $id);

            return $query->execute();
        }

        public function deleteOrder($id) 
        {
            global $mysqli_connection;
            
            $sql = "DELETE FROM `orders` WHERE `id` = ?";
            $query = $mysqli_connection->prepare($sql);
            $query->bind_param("i", $id);
            return $query->execute();
        }
    }
?>