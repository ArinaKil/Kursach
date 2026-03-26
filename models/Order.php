<?php
    class Order {
        private $id;
        private $user_id;
        private $status;
        private $total_price;
        private $order_date;
        private const STATUSES = ['access', 'in_delivery', 'ready'];
        public function __construct($id, $user_id, $total_price, $order_date, $status = "access") {
            if (!in_array($status, self::STATUSES, true)) {
                $status = "access";
            }
            $this->id = $id;
            $this->user_id = $user_id;
            $this->total_price = $total_price;
            $this->order_date = $order_date;
            $this->status = $status;
        }
    }
?>