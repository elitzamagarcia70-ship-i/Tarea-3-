<?php
// Sesión del sistema: arranca la sesión y ofrece funciones para exigir que el usuario haya entrado.
if (session_status() === PHP_SESSION_NONE) {
  session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off']);
  session_start();
}

function sesion_activa(): bool {
  return !empty($_SESSION['id_usuario']);
}

// Páginas: si no hay sesión, manda a login.php. API (json = true): responde 401.
function exigir_login(bool $json = false): void {
  if (sesion_activa()) return;
  if ($json) {
    http_response_code(401);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Sesión vencida. Inicia sesión de nuevo.'], JSON_UNESCAPED_UNICODE);
  } else {
    header('Location: login.php');
  }
  exit;
}
