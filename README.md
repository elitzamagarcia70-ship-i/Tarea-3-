<<<<<<< HEAD
# Sistema de Gestión de Documentos de una Oficina
**UNACH · ETDA · Campus IV Tapachula** — Laboratorio práctico, Desarrollo de aplicaciones web y móviles

Plataforma web responsiva (Mobile First) para registrar, digitalizar, buscar y dar seguimiento a los documentos que recibe una oficina.
Tecnologías: **HTML5, CSS3, jQuery, PHP 8 y MySQL**.

## Funcionalidades
- **Registro** de documentos: No. de oficio, fecha, asunto, remitente, destinatario y estatus.
- **Digitalización:** tomar una foto con la cámara del celular o seleccionar un PDF. **Todo se almacena como PDF** (la foto se convierte a PDF en el navegador antes de subirse).
- **Estatus:** Recibido, En proceso y Atendido (se cambia desde Editar).
- **Búsqueda y filtros** por oficio, asunto, remitente, destinatario, estatus y rango de fechas, con paginación.
- **Visor integrado:** el botón 👁 muestra el PDF dentro de la plataforma, con botón de descarga.
- **CRUD completo:** crear, consultar, editar y eliminar.
- **Reportes:** totales por estatus, documentos por mes, remitentes y destinatarios frecuentes; exportación a CSV e impresión/PDF.
- **Acceso desde el celular** con un código QR.

## Inicio de sesión
- Al abrir el sistema se pide usuario y contraseña (`login.php`). Sin sesión no se puede ver nada: ni las pantallas, ni la API, ni los PDF.
- La primera vez que se abre `login.php` se crea la tabla `usuarios` y el usuario inicial: **admin / admin123**. Cámbialo en cuanto puedas.
- Las contraseñas se guardan cifradas (`password_hash`). Los PDF ya no se abren directo desde `uploads/`; se entregan por `archivo.php` solo con sesión.
- El botón **Cerrar sesión** está en la barra superior y en el menú.

## Instalación (XAMPP)
1. Instalar XAMPP y encender **Apache** y **MySQL**.
2. Copiar la carpeta `gestion-documentos` en `C:\xampp\htdocs\`.
3. Abrir `http://localhost/phpmyadmin`, ir a **Importar**, elegir `database.sql` y pulsar **Importar**. Se crea la base `gestion_docs` con datos de prueba.
   - Si ya tenías la base de una versión anterior, importa en su lugar `migracion_bd.sql` (solo renombra columnas).
4. Revisar usuario y contraseña de MySQL en `config.php` (XAMPP: `root` sin contraseña).
5. Abrir `http://localhost/gestion-documentos/`.

Para archivos pesados, en `php.ini` conviene `upload_max_filesize=12M` y `post_max_size=14M`.

## Uso (flujo mínimo)
recibir → digitalizar → registrar → buscar → actualizar estatus → consultar PDF
1. **Nuevo Documento** → llenar los campos → **Tomar foto** o **Seleccionar archivo PDF** → **Guardar Documento**.
2. Buscar con la barra superior o filtrar por estatus y fechas.
3. **✎ Editar** para cambiar el estatus u otros datos; **👁** para ver el PDF; **⬇** para descargarlo; **🗑** para eliminar.
4. **Reportes** en el menú para ver estadísticas y exportarlas.
5. **Abrir en celular** muestra un QR (el celular debe estar en la misma red wifi).

## Estructura
```
gestion-documentos/
├── index.php        Listado, formulario, visor y QR
├── reportes.php     Pantalla de reportes
├── login.php / logout.php / auth.php / archivo.php   Inicio de sesión, cierre, control de sesión y entrega protegida de PDF
├── api.php          Backend (JSON): listar, guardar, eliminar, reportes y exportar CSV
├── config.php       Conexión a MySQL (PDO)
├── database.sql     Creación de la base, tabla y datos de prueba
├── migracion_bd.sql Actualización para bases creadas con la versión anterior
├── assets/          style.css (Mobile First), app.js, reportes.js, logo-unach.png
└── uploads/         PDF almacenados (nombre aleatorio)
```

## Modelo de datos (tabla `documentos`)
| Campo | Tipo | Descripción |
|---|---|---|
| id_documento | INT, PK, autoincremental | Identificador único |
| no_oficio | VARCHAR(30), único | Número o folio |
| fecha | DATE | Fecha de recepción |
| asunto | VARCHAR(200) | Descripción breve |
| remitente | VARCHAR(120) | Quien remite |
| destinatario | VARCHAR(120) | Área o persona destinataria |
| estatus | ENUM | Recibido / En proceso / Atendido |
| archivo_pdf | VARCHAR(255) | Nombre del PDF en `uploads/` |
Índices en `fecha` y `estatus`.

## Seguridad y buenas prácticas
- Consultas **preparadas** (PDO) contra inyección SQL.
- Validación en el servidor: campos obligatorios, longitudes, fecha real, estatus permitido.
- Archivos: se verifica el tipo **real** (no solo la extensión), máximo 10 MB, nombre aleatorio; la carpeta `uploads` no lista su contenido.
- Texto escapado en pantalla contra XSS.
- Credenciales solo en `config.php`. En un servidor real hay que usar un usuario de MySQL propio con contraseña.

## Actores y casos de uso
- **Personal de oficina (administrador):** registrar, consultar, editar estatus, eliminar y generar reportes.
- Flujo: recepción del oficio en papel → digitalización (foto o PDF) → registro → seguimiento por estatus → consulta del PDF.

## Pruebas realizadas
| Prueba | Resultado esperado |
|---|---|
| Registrar con PDF y con foto | Se crea el registro y en `uploads/` queda un `.pdf` |
| Número de oficio repetido | Mensaje "Ese número de oficio ya existe" |
| Campos vacíos o fecha inválida | El servidor rechaza con mensaje |
| Archivo que no es PDF | Mensaje de error |
| Búsqueda y filtros | La tabla muestra solo coincidencias |
| Cambio de estatus | La etiqueta cambia de color |
| Visor 👁 y descarga ⬇ | El PDF se ve dentro de la plataforma y se descarga |
| Responsivo | Celular (tarjetas, menú inferior), tableta y escritorio |

## Solución de problemas
- *Página en blanco o tabla vacía:* revisar que MySQL esté encendido y los datos de `config.php`.
- *No sube archivos grandes:* subir `upload_max_filesize` y `post_max_size` en `php.ini` y reiniciar Apache.
- *El QR no abre en el celular:* escribir la IP de la PC en el campo "Dirección" y permitir Apache en el Firewall de Windows.
=======
# Tarea-3-
>>>>>>> f666feb5ec8b08e9cdf22edab2049ca32bf605d8
