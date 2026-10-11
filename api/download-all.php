<?php
require 'config.php';

$stmt = $pdo->query('SELECT name, filename FROM photos ORDER BY created_at ASC');
$rows = $stmt->fetchAll();

if (empty($rows)) {
    http_response_code(404);
    exit('Aún no hay fotos para descargar.');
}

if (!class_exists('ZipArchive')) {
    http_response_code(500);
    exit('La extensión ZipArchive de PHP no está habilitada en este hosting. Pídele a tu proveedor que la active, o descarga las fotos manualmente desde la carpeta /uploads.');
}

$zipPath = tempnam(sys_get_temp_dir(), 'boda_') . '.zip';
$zip = new ZipArchive();
$zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

$usedNames = [];
foreach ($rows as $row) {
    $filePath = UPLOAD_DIR . $row['filename'];
    if (!file_exists($filePath)) {
        continue;
    }
    $safeName = preg_replace('/[^a-z0-9áéíóúñ]+/i', '_', $row['name']);
    $safeName = $safeName !== '' ? substr($safeName, 0, 30) : 'invitado';
    $usedNames[$safeName] = ($usedNames[$safeName] ?? 0) + 1;
    $ext = pathinfo($row['filename'], PATHINFO_EXTENSION);
    $entryName = $safeName . '_' . $usedNames[$safeName] . '.' . $ext;
    $zip->addFile($filePath, $entryName);
}
$zip->close();

header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="fotos-boda-xitlally-robert.zip"');
header('Content-Length: ' . filesize($zipPath));
readfile($zipPath);
unlink($zipPath);
