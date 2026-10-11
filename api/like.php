<?php
// Suma un "me gusta" a una foto y regresa a la galería — formulario HTML normal.
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) ($_POST['id'] ?? 0);
    if ($id > 0) {
        $stmt = $pdo->prepare('UPDATE photos SET likes = likes + 1 WHERE id = ?');
        $stmt->execute([$id]);
    }
}

$sort = (($_POST['sort'] ?? 'recent') === 'liked') ? 'liked' : 'recent';
header('Location: ../index.php?sort=' . $sort . '#galeria');
exit;
