<?php
// API del sistema: listar, guardar (crear/editar) y eliminar documentos.
require 'auth.php';
exigir_login(true);
require 'config.php';
header('Content-Type: application/json; charset=utf-8');

// Responde en JSON con un código HTTP y termina
function out($d, $c = 200) {
  http_response_code($c);
  echo json_encode($d, JSON_UNESCAPED_UNICODE);
  exit;
}

// Filtro por rango de fechas (desde / hasta) para los reportes
function filtroFechas(array $g, array &$p): string {
  $w = ['1=1'];
  if (!empty($g['desde'])) { $w[] = 'fecha >= ?'; $p[] = $g['desde']; }
  if (!empty($g['hasta'])) { $w[] = 'fecha <= ?'; $p[] = $g['hasta']; }
  return implode(' AND ', $w);
}

$a = $_REQUEST['action'] ?? '';

try {
  if ($a === 'list') {
    // --- Listado con búsqueda, filtros y paginación ---
    $w = ['1=1']; $p = [];
    if (($q = trim($_GET['q'] ?? '')) !== '') {
      $w[] = '(no_oficio LIKE ? OR asunto LIKE ? OR remitente LIKE ? OR destinatario LIKE ?)';
      array_push($p, "%$q%", "%$q%", "%$q%", "%$q%");
    }
    if (!empty($_GET['estatus'])) { $w[] = 'estatus = ?'; $p[] = $_GET['estatus']; }
    if (!empty($_GET['desde']))   { $w[] = 'fecha >= ?';  $p[] = $_GET['desde']; }
    if (!empty($_GET['hasta']))   { $w[] = 'fecha <= ?';  $p[] = $_GET['hasta']; }
    $where = implode(' AND ', $w);
    $per = 5; $page = max(1, (int)($_GET['page'] ?? 1));
    $st = $pdo->prepare("SELECT COUNT(*) FROM documentos WHERE $where"); $st->execute($p);
    $total = (int)$st->fetchColumn();
    $off = ($page - 1) * $per;
    $st = $pdo->prepare("SELECT * FROM documentos WHERE $where ORDER BY fecha DESC, id_documento DESC LIMIT $per OFFSET $off");
    $st->execute($p);
    out(['rows' => $st->fetchAll(), 'total' => $total, 'page' => $page, 'per' => $per]);

  } elseif ($a === 'save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // --- Crear o editar un documento ---
    // Si el archivo supera post_max_size, PHP vacía $_POST y $_FILES
    if (($_SERVER['CONTENT_LENGTH'] ?? 0) > 0 && empty($_POST) && empty($_FILES))
      out(['error' => 'El archivo es demasiado grande para PHP. Sube upload_max_filesize y post_max_size en php.ini'], 413);

    $id = (int)($_POST['id'] ?? 0);
    $v = [];
    // Validación de datos en el servidor: obligatorios, longitud máxima, fecha real y estatus permitido
    $max = ['no_oficio' => 30, 'fecha' => 10, 'asunto' => 200, 'remitente' => 120, 'destinatario' => 120, 'estatus' => 20];
    foreach ($max as $k => $largo) {
      $v[$k] = trim($_POST[$k] ?? '');
      if ($v[$k] === '') out(['error' => "Falta el campo: $k"], 422);
      if (mb_strlen($v[$k]) > $largo) out(['error' => "El campo $k admite máximo $largo caracteres"], 422);
    }
    $f = DateTime::createFromFormat('Y-m-d', $v['fecha']);
    if (!$f || $f->format('Y-m-d') !== $v['fecha']) out(['error' => 'La fecha no es válida'], 422);
    if (!in_array($v['estatus'], ['Recibido','En proceso','Atendido'])) out(['error' => 'Estatus inválido'], 422);

    // --- Archivo adjunto: PDF (las fotos ya llegan convertidas a PDF desde el navegador) ---
    $archivo = null;
    if (isset($_FILES['archivo']) && $_FILES['archivo']['error'] !== UPLOAD_ERR_NO_FILE) {
      $err = $_FILES['archivo']['error'];
      if ($err === UPLOAD_ERR_INI_SIZE || $err === UPLOAD_ERR_FORM_SIZE)
        out(['error' => 'El archivo pesa más de lo que permite PHP. Sube upload_max_filesize y post_max_size en php.ini'], 413);
      if ($err !== UPLOAD_ERR_OK) out(['error' => "Error al subir el archivo (código $err)"], 500);

      // Se valida el tipo REAL del archivo (no solo su extensión)
      $tipos = ['application/pdf' => 'pdf']; // el almacenamiento es solo en PDF
      $mime = (new finfo(FILEINFO_MIME_TYPE))->file($_FILES['archivo']['tmp_name']);
      if (!isset($tipos[$mime]) || $_FILES['archivo']['size'] > 10 * 1024 * 1024)
        out(['error' => 'Solo se permiten archivos PDF de hasta 10 MB'], 422);

      $dir = __DIR__ . '/uploads';
      if (!is_dir($dir) && !mkdir($dir, 0775, true))
        out(['error' => 'No existe la carpeta uploads y no se pudo crear'], 500);
      if (!is_writable($dir))
        out(['error' => 'La carpeta uploads no tiene permiso de escritura'], 500);

      $archivo = bin2hex(random_bytes(8)) . '.' . $tipos[$mime]; // nombre aleatorio
      if (!move_uploaded_file($_FILES['archivo']['tmp_name'], "$dir/$archivo"))
        out(['error' => 'No se pudo guardar el archivo en la carpeta uploads'], 500);
    }
    if (!$id && !$archivo) out(['error' => 'Adjunta una foto o un PDF del documento'], 422);

    if ($id) {
      $sql = 'UPDATE documentos SET no_oficio=?,fecha=?,asunto=?,remitente=?,destinatario=?,estatus=?' . ($archivo ? ',archivo_pdf=?' : '') . ' WHERE id_documento=?';
      $pdo->prepare($sql)->execute(array_merge(array_values($v), $archivo ? [$archivo] : [], [$id]));
    } else {
      $pdo->prepare('INSERT INTO documentos (no_oficio,fecha,asunto,remitente,destinatario,estatus,archivo_pdf) VALUES (?,?,?,?,?,?,?)')
          ->execute(array_merge(array_values($v), [$archivo]));
    }
    out(['ok' => true]);

  } elseif ($a === 'report') {
    // --- Reportes: totales por estatus, por mes y los más frecuentes ---
    $p = []; $where = filtroFechas($_GET, $p);
    $q = function (string $sql) use ($pdo, $p) { $st = $pdo->prepare($sql); $st->execute($p); return $st->fetchAll(); };
    $estatus = ['Recibido' => 0, 'En proceso' => 0, 'Atendido' => 0];
    foreach ($q("SELECT estatus, COUNT(*) n FROM documentos WHERE $where GROUP BY estatus") as $r) $estatus[$r['estatus']] = (int)$r['n'];
    out([
      'total'         => array_sum($estatus),
      'estatus'       => $estatus,
      'meses'         => array_reverse($q("SELECT DATE_FORMAT(fecha, '%Y-%m') mes, COUNT(*) n FROM documentos WHERE $where GROUP BY mes ORDER BY mes DESC LIMIT 12")),
      'remitentes'    => $q("SELECT remitente nombre, COUNT(*) n FROM documentos WHERE $where GROUP BY remitente ORDER BY n DESC, remitente LIMIT 5"),
      'destinatarios' => $q("SELECT destinatario nombre, COUNT(*) n FROM documentos WHERE $where GROUP BY destinatario ORDER BY n DESC, destinatario LIMIT 5"),
    ]);

  } elseif ($a === 'export') {
    // --- Exportar a CSV (se abre en Excel) ---
    $p = []; $where = filtroFechas($_GET, $p);
    $st = $pdo->prepare("SELECT no_oficio, fecha, asunto, remitente, destinatario, estatus FROM documentos WHERE $where ORDER BY fecha DESC, id_documento DESC");
    $st->execute($p);
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="reporte_documentos_' . date('Ymd') . '.csv"');
    echo "\xEF\xBB\xBF"; // para que Excel respete los acentos
    $f = fopen('php://output', 'w');
    fputcsv($f, ['No. Oficio', 'Fecha', 'Asunto', 'Remitente', 'Destinatario', 'Estatus']);
    while ($r = $st->fetch(PDO::FETCH_NUM)) fputcsv($f, $r);
    fclose($f);
    exit;

  } elseif ($a === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // --- Eliminar un documento y su archivo ---
    $st = $pdo->prepare('SELECT archivo_pdf FROM documentos WHERE id_documento=?'); $st->execute([(int)$_POST['id']]);
    if ($f = $st->fetchColumn()) @unlink(__DIR__ . "/uploads/$f");
    $pdo->prepare('DELETE FROM documentos WHERE id_documento=?')->execute([(int)$_POST['id']]);
    out(['ok' => true]);

  } else out(['error' => 'Acción no válida'], 400);

} catch (PDOException $e) {
  $dup = $e->getCode() == 23000; // número de oficio repetido
  out(['error' => $dup ? 'Ese número de oficio ya existe' : 'Error de base de datos: ' . $e->getMessage()], $dup ? 409 : 500);
} catch (Throwable $e) {
  out(['error' => 'Error del servidor: ' . $e->getMessage()], 500);
}
