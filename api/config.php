<?php
// ============================================================
// EDITA estos 4 datos con los de TU base de datos MySQL.
// Tu proveedor de hosting te los da al crear la base de datos
// (en cPanel suele estar en "Bases de datos MySQL").
// ============================================================
$DB_HOST = 'localhost';
$DB_NAME = 'boda';
$DB_USER = 'root';
$DB_PASS = '';
// ============================================================

// Mientras pruebas en tu XAMPP local, deja esto en true para ver el error
// real si algo falla. Cuando subas el sitio a un hosting público, cámbialo
// a false para no mostrar detalles técnicos a los visitantes.
$DEBUG = true;

header('Access-Control-Allow-Origin: *');

try {
    $pdo = new PDO(
        "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    $msg = $DEBUG ? $e->getMessage() : 'No se pudo conectar a la base de datos. Revisa api/config.php';
    die(json_encode(['error' => $msg]));
}

define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('UPLOAD_URL', 'uploads/'); // ruta relativa desde index.html
define('MAX_FILE_SIZE', 8 * 1024 * 1024); // 8 MB por foto
