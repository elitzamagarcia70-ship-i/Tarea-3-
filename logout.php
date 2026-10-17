<?php
// Cierra la sesión y regresa a la pantalla de inicio de sesión
require 'auth.php';
$_SESSION = [];
if (ini_get('session.use_cookies')) {
  $c = session_get_cookie_params();
  setcookie(session_name(), '', time() - 3600, $c['path'], $c['domain'], $c['secure'], $c['httponly']);
}
session_destroy();
header('Location: login.php');
