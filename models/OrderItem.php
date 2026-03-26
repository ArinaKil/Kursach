<?php
    class OrderItem {
        private $id;
        private $order_id;
        private $product_id;
        private $quantity;
        public function __construct($id, $order_id, $product_id, $quantity) {
            $this->id = $id;
            $this->order_id = $order_id;
            $this->product_id = $product_id;
            $this->quantity = $quantity;
        }
    }
?>