<?php
    class User {
        private $id;
        private $fio;
        private $phone;
        private $password_hash;
        private $role;

        public function __construct($id, $fio, $phone, $password_hash, $role = 0) {
            $this->id = $id;
            $this->fio = $fio;
            $this->phone = $phone;
            $this->password_hash = $password_hash;
            $this->role = $role;
        }
    }
?>