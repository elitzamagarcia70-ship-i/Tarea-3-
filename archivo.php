<?php
// Entrega un archivo de uploads/ solo si hay sesión iniciada.
require 'auth.php';
exigir_login(true);
$f = basename($_GET['f'] ?? '');
$ruta = __DIR__ . '/uploads/' . $f;
if ($f === '' || $f[0] === '.' || !is_file($ruta)) { http_response_code(404); exit('No encontrado'); }
$mime = (new finfo(FILEINFO_MIME_TYPE))->file($ruta);
header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($ruta));
header('X-Content-Type-Options: nosniff');
header('Content-Disposition: ' . (isset($_GET['dl']) ? 'attachment' : 'inline') . '; filename="' . $f . '"');
readfile($ruta);
