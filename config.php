<?php
// Ajusta usuario y contraseña a tu servidor (XAMPP: root sin contraseña)
$pdo = new PDO('mysql:host=localhost;dbname=gestion_docs;charset=utf8mb4', 'root', '', [
  PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);
