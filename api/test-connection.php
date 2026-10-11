<?php
// Abre este archivo directamente en tu navegador para probar la conexión:
// http://localhost/boda/api/test-connection.php
// (ajusta "boda" si tu carpeta dentro de htdocs se llama distinto)

header('Content-Type: text/plain; charset=utf-8');

echo "Probando conexión a MySQL...\n\n";

try {
    require 'config.php';
    echo "✅ Conexión a la base de datos '$DB_NAME' exitosa.\n\n";

    $stmt = $pdo->query("SHOW TABLES LIKE 'photos'");
    if ($stmt->rowCount() === 0) {
        echo "⚠️  La base de datos conecta, pero la tabla 'photos' no existe todavía.\n";
        echo "   Solución: abre phpMyAdmin, entra a la base de datos '$DB_NAME',\n";
        echo "   ve a la pestaña SQL, pega el contenido de schema.sql y ejecútalo.\n";
        exit;
    }

    $count = $pdo->query('SELECT COUNT(*) AS total FROM photos')->fetch();
    echo "✅ La tabla 'photos' existe.\n";
    echo "   Fotos registradas actualmente: {$count['total']}\n\n";

    if (!is_dir(UPLOAD_DIR)) {
        echo "⚠️  La carpeta uploads/ no existe todavía (se creará sola en la primera foto que se suba).\n";
    } elseif (!is_writable(UPLOAD_DIR)) {
        echo "⚠️  La carpeta uploads/ existe pero no tiene permiso de escritura.\n";
        echo "   En Windows con XAMPP esto casi nunca pasa; en Linux/macOS dale permiso 755 o 775.\n";
    } else {
        echo "✅ La carpeta uploads/ existe y se puede escribir en ella.\n";
    }

    echo "\nTodo listo. Si la página web sigue sin conectar, revisa la consola\n";
    echo "del navegador (F12 → pestaña Network) al subir una foto, para ver\n";
    echo "el mensaje de error exacto que devuelve api/upload.php o api/photos.php.\n";

} catch (Throwable $e) {
    echo "❌ Falló la conexión. Mensaje exacto:\n\n";
    echo "   " . $e->getMessage() . "\n\n";
    echo "Causas comunes:\n";
    echo "- MySQL no está encendido en el panel de XAMPP (botón 'Start' junto a MySQL).\n";
    echo "- El nombre de la base de datos en api/config.php no coincide con el que creaste en phpMyAdmin.\n";
    echo "- El usuario/contraseña no coinciden (en XAMPP por defecto es usuario 'root' sin contraseña).\n";
}
