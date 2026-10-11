-- Ejecuta este archivo en tu base de datos MySQL (phpMyAdmin, Adminer, o la
-- terminal de tu hosting) antes de usar la página.

CREATE TABLE IF NOT EXISTS photos (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  caption VARCHAR(255) NOT NULL DEFAULT '',
  filename VARCHAR(255) NOT NULL,
  likes INT UNSIGNED NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
