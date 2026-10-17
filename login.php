<?php
require 'auth.php';
require 'config.php';

// Si ya entró, va directo al sistema
if (sesion_activa()) { header('Location: index.php'); exit; }

// Crea la tabla de usuarios la primera vez y agrega el usuario inicial (admin / admin123)
$pdo->exec("CREATE TABLE IF NOT EXISTS usuarios (
  id_usuario INT AUTO_INCREMENT PRIMARY KEY,
  usuario VARCHAR(40) NOT NULL UNIQUE,
  nombre VARCHAR(80) NOT NULL,
  clave_hash VARCHAR(255) NOT NULL,
  creado TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");
if (!(int)$pdo->query('SELECT COUNT(*) FROM usuarios')->fetchColumn()) {
  $pdo->prepare('INSERT INTO usuarios (usuario, nombre, clave_hash) VALUES (?,?,?)')
      ->execute(['admin', 'Administrador', password_hash('admin123', PASSWORD_DEFAULT)]);
}

$error = '';
$usuario = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $usuario = trim($_POST['usuario'] ?? '');
  $clave = $_POST['clave'] ?? '';
  if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
    $error = 'La página caducó. Intenta de nuevo.';
  } elseif ($usuario === '' || $clave === '') {
    $error = 'Escribe tu usuario y contraseña.';
  } else {
    $st = $pdo->prepare('SELECT * FROM usuarios WHERE usuario = ?');
    $st->execute([$usuario]);
    $u = $st->fetch();
    if ($u && password_verify($clave, $u['clave_hash'])) {
      session_regenerate_id(true);
      $_SESSION['id_usuario'] = (int)$u['id_usuario'];
      $_SESSION['nombre'] = $u['nombre'];
      unset($_SESSION['csrf']);
      header('Location: index.php');
      exit;
    }
    usleep(500000); // frena intentos repetidos
    $error = 'Usuario o contraseña incorrectos.';
  }
}
$_SESSION['csrf'] = bin2hex(random_bytes(16));
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Iniciar sesión · Gestión de Documentos</title>
<link rel="stylesheet" href="assets/login.css">
</head>
<body class="login">
<main class="card">
  <div class="id">
    <div class="shield" aria-hidden="true">
      <svg viewBox="0 0 48 48" width="52" height="52" fill="none" stroke="#e6bcc6" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
        <path d="M24 5 8 11v11c0 10 6.5 17.5 16 21 9.5-3.5 16-11 16-21V11L24 5Z"/>
        <path d="M17 19h14M17 24h10M17 29h14" />
      </svg>
    </div>
    <div>
      <h1>UNACH</h1>
      <p>Gestión de Documentos · Oficina Digital</p>
    </div>
  </div>

  <h2>Iniciar sesión</h2>
  <form method="post" autocomplete="on">
    <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf']) ?>">
    <label for="usuario">Usuario</label>
    <input id="usuario" name="usuario" value="<?= htmlspecialchars($usuario) ?>" autocomplete="username" autocapitalize="none" spellcheck="false" required autofocus>
    <label for="clave">Contraseña</label>
    <input id="clave" name="clave" type="password" autocomplete="current-password" required>
    <p class="error" role="alert"><?= htmlspecialchars($error) ?></p>
    <button type="submit">Entrar</button>
  </form>
</main>
</body>
</html>
