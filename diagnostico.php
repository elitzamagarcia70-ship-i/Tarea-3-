<?php
// Página de diagnóstico: revisa lo necesario para guardar documentos y PDF. Requiere sesión iniciada.
require 'auth.php';
exigir_login();

$r = []; // cada revisión: [ok, título, detalle]
function chk(array &$r, bool $ok, string $t, string $d = ''): void { $r[] = [$ok, $t, $d]; }

chk($r, true, 'Sesión iniciada', 'Usuario: ' . ($_SESSION['nombre'] ?? '?'));
chk($r, PHP_VERSION_ID >= 80000, 'Versión de PHP', PHP_VERSION);
chk($r, class_exists('finfo'), 'Extensión fileinfo (valida que el archivo sea PDF)', class_exists('finfo') ? 'Activa' : 'Está apagada: en php.ini quita el ; de extension=fileinfo y reinicia Apache');
chk($r, (bool)ini_get('file_uploads'), 'PHP permite subir archivos (file_uploads)', ini_get('file_uploads') ? 'Sí' : 'No');
chk($r, true, 'Límites de PHP', 'upload_max_filesize=' . ini_get('upload_max_filesize') . ' · post_max_size=' . ini_get('post_max_size') . ' (se recomienda 12M y 14M)');

// Carpeta uploads
$dir = __DIR__ . '/uploads';
chk($r, is_dir($dir), 'Carpeta uploads existe', $dir);
chk($r, is_dir($dir) && is_writable($dir), 'Carpeta uploads tiene permiso de escritura', is_writable($dir) ? 'Sí' : 'No: dale permiso de escritura a la carpeta');
$prueba = $dir . '/.prueba_' . bin2hex(random_bytes(4));
$escribe = is_dir($dir) && @file_put_contents($prueba, 'x') !== false;
if ($escribe) @unlink($prueba);
chk($r, $escribe, 'Se puede escribir un archivo en uploads', $escribe ? 'Sí' : 'No se pudo escribir');

// Base de datos
try {
  require 'config.php';
  chk($r, true, 'Conexión a MySQL (gestion_docs)', 'Conectada');
  $cols = array_column($pdo->query('SHOW COLUMNS FROM documentos')->fetchAll(), 'Field');
  foreach (['id_documento', 'no_oficio', 'fecha', 'asunto', 'remitente', 'destinatario', 'estatus', 'archivo_pdf'] as $c)
    chk($r, in_array($c, $cols, true), "Columna documentos.$c", in_array($c, $cols, true) ? 'Existe' : 'Falta. Si tu base es antigua, importa migracion_bd.sql');
  $n = (int)$pdo->query('SELECT COUNT(*) FROM documentos')->fetchColumn();
  chk($r, true, 'Documentos registrados', (string)$n);
  chk($r, true, 'Tabla usuarios', (int)$pdo->query('SELECT COUNT(*) FROM usuarios')->fetchColumn() . ' usuario(s)');
} catch (Throwable $e) {
  chk($r, false, 'Base de datos', $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Diagnóstico · Gestión de Documentos</title>
<link rel="stylesheet" href="assets/style.css">
<style>ul.dx{list-style:none;padding:0;margin:0}.dx li{display:flex;gap:12px;padding:12px 4px;border-bottom:1px solid var(--line)}.dx b{flex:none;width:22px}.dx .ok b{color:#1d7a3a}.dx .no b{color:#c62828}.dx small{display:block;color:var(--muted);word-break:break-word}</style>
</head>
<body>
<main style="max-width:760px;margin:0 auto;padding:16px">
  <section class="panel">
    <div class="head"><h1>Diagnóstico</h1><a class="btn ghost" href="index.php">Volver al sistema</a></div>
    <ul class="dx">
    <?php foreach ($r as [$ok, $t, $d]): ?>
      <li class="<?= $ok ? 'ok' : 'no' ?>"><b><?= $ok ? '✔' : '✘' ?></b><div><?= htmlspecialchars($t) ?><small><?= htmlspecialchars($d) ?></small></div></li>
    <?php endforeach; ?>
    </ul>
  </section>
</main>
</body>
</html>
