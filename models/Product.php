<?php
    class Product {
        private $id;
        private $name;
        private $category;
        private $price;
        private $imagePath;
    
        public function __construct($id, $name, $category, $price, $imagePath) {
            $this->id = $id;
            $this->name = $name;
            $this->category = $category;
            $this->price = $price;
            $this->imagePath = $imagePath;
        }
    }
?>