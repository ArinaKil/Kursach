<?php
    require_once __DIR__ . '/../Models/Product.php';
    class ProductController 
    {
        public function getProducts() 
        {
            global $mysqli_connection;
            
            $sql = "SELECT * FROM `products`";
            $query = $mysqli_connection->prepare($sql);
            $query->execute();
            $result = $query->get_result();

            $products = [];
            while ($row = $result->fetch_assoc()) {
                $products[] = new Product(
                    $row['id'] ?? null,
                    $row['name'] ?? null,
                    $row['category'] ?? null,
                    $row['price'] ?? null,
                    $row['imagePath'] ?? null
                );
            }

            return $products;
        }
        
        public function getProductById($id) 
        {
            global $mysqli_connection;
            
            $sql = "SELECT * FROM `products` WHERE `id` = ?";
            $query = $mysqli_connection->prepare($sql);
            $query->bind_param("i", $id);
            $query->execute();
            $result = $query->get_result();

            if ($row = $result->fetch_assoc()) {
                $category = $row['category'] ?? null;
                $imagePath = $row['imagePath'] ?? null;

                return new Product(
                    $row['id'] ?? null,
                    $row['name'] ?? null,
                    $category,
                    $row['price'] ?? null,
                    $imagePath
                );
            }

            return null;
        }

        public function createProduct($name, $category, $price, $imagePath) 
        {
            global $mysqli_connection;

            $sql = "INSERT INTO `products`(`name`, `category`, `price`, `imagePath`) VALUES (?, ?, ?, ?)";
            $query = $mysqli_connection->prepare($sql);
            $query->bind_param("ssss", $name, $category, $price, $imagePath);
            $query->execute();
        }

        public function updateProduct($id, $name, $category, $price, $imagePath) 
        {
            global $mysqli_connection;

            $sql = "UPDATE `products` SET `name` = ?, `category` = ?, `price` = ?, `imagePath` = ? WHERE `id` = ?";
            $query = $mysqli_connection->prepare($sql);
            $query->bind_param("ssssi", $name, $category, $price, $imagePath, $id);
            $query->execute();
        }

        public function deleteProduct($id) 
        {
            global $mysqli_connection;
            
            $sql = "DELETE FROM `products` WHERE `id` = ?";
            $query = $mysqli_connection->prepare($sql);
            $query->bind_param("i", $id);
            $query->execute();
        }
    }
?>