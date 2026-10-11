<?php
// Procesa el formulario de subida de fotos (varias a la vez) y redirige
// de vuelta a index.php — sin JSON, sin fetch, formulario HTML normal.
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php#galeria');
    exit;
}

$name = trim($_POST['name'] ?? '') ?: 'Invitado anónimo';
$caption = trim($_POST['caption'] ?? '');
$name = mb_substr($name, 0, 100);
$caption = mb_substr($caption, 0, 255);

$allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

if (!is_dir(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0775, true);
}

$saved = 0;

if (isset($_FILES['photos']) && is_array($_FILES['photos']['name'])) {
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $total = count($_FILES['photos']['name']);

    for ($i = 0; $i < $total; $i++) {
        if ($_FILES['photos']['error'][$i] !== UPLOAD_ERR_OK) {
            continue;
        }
        $tmpName = $_FILES['photos']['tmp_name'][$i];
        $size = $_FILES['photos']['size'][$i];

        if ($size > MAX_FILE_SIZE) {
            continue;
        }

        $mime = finfo_file($finfo, $tmpName);
        if (!isset($allowed[$mime])) {
            continue;
        }

        $ext = $allowed[$mime];
        $filename = uniqid('photo_', true) . '.' . $ext;
        $destination = UPLOAD_DIR . $filename;

        if (!move_uploaded_file($tmpName, $destination)) {
            continue;
        }

        $stmt = $pdo->prepare('INSERT INTO photos (name, caption, filename) VALUES (?, ?, ?)');
        $stmt->execute([$name, $caption, $filename]);
        $saved++;
    }
    finfo_close($finfo);
}

header('Location: ../index.php?uploaded=' . $saved . '#galeria');
exit;
