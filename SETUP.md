# Cómo instalar tu página de boda en tu hosting

Este sitio es **PHP + HTML puro** con MySQL como base de datos — sin
JavaScript/AJAX de por medio para el backend. Los formularios (subir fotos,
dar like) son formularios HTML normales que se procesan directo en el
servidor. Funciona con prácticamente cualquier hosting compartido
(Hostinger, GoDaddy, HostGator, cPanel genérico, XAMPP local, etc.).

## Qué contiene esta carpeta

```
index.php              → la página completa (diseño, formulario, galería, RSVP)
api/
  config.php             → aquí pones tus datos de conexión a MySQL
  upload.php               → procesa el formulario de subida de fotos
  like.php                   → procesa el formulario de "me gusta"
  download-all.php             → arma el ZIP con todas las fotos para ustedes
  test-connection.php            → diagnóstico rápido de la conexión a MySQL
uploads/                  → aquí se guardan las fotos que suben los invitados
schema.sql                 → crea la tabla en tu base de datos
```

## Antes de subir: pon tu número de WhatsApp

El botón "Confirmar por WhatsApp" necesita el número real de la pareja.
Abre `index.php`, busca `521234567890` (aparece dos veces) y reemplázalo
por el número real con código de país, sin espacios ni signos — por
ejemplo, para un número de México sería `521XXXXXXXXXX`.

## ¿No conecta? Prueba esto primero

Abre directamente en tu navegador:

```
http://localhost/boda/api/test-connection.php
```

(ajusta `boda` si tu carpeta dentro de `htdocs` se llama distinto). Este
archivo te dice exactamente qué está fallando: si MySQL no está encendido,
si el nombre de la base de datos no coincide, si falta la tabla `photos`,
o si la carpeta `uploads/` no tiene permisos. Corrígelo según lo que te
diga y vuelve a intentar.

## Paso 1 — Crea la base de datos MySQL

**Si usas XAMPP local:** ya está configurado para conectarse a una base de
datos llamada `boda` con usuario `root` sin contraseña (lo normal en
XAMPP). Solo asegúrate de crearla en phpMyAdmin y correr `schema.sql`
(Paso 2).

**Si es un hosting en línea:**
1. Entra al panel de tu hosting (normalmente cPanel) y busca **"Bases de
   datos MySQL"**.
2. Crea una base de datos nueva (por ejemplo `boda_xr`).
3. Crea un usuario de MySQL con contraseña, y asígnalo a esa base de datos
   con todos los permisos.
4. Anota host, nombre de base de datos, usuario y contraseña para el Paso 3.

## Paso 2 — Crea la tabla

1. Abre **phpMyAdmin** (en XAMPP: `http://localhost/phpmyadmin`).
2. Entra a tu base de datos y ve a la pestaña **"SQL"**.
3. Copia y pega todo el contenido de `schema.sql` y ejecútalo.

Esto crea una tabla llamada `photos` donde se guardará el nombre del
invitado, su mensaje, el nombre del archivo de la foto, los likes y la
fecha.

## Paso 3 — Configura la conexión

Abre `api/config.php` y revisa/edita estas líneas:

```php
$DB_HOST = 'localhost';
$DB_NAME = 'boda';
$DB_USER = 'root';
$DB_PASS = '';
```

Si vas a subir esto a un hosting en línea (no XAMPP), cambia estos 4
valores por los de tu hosting, y pon `$DEBUG = false;` para no mostrar
errores técnicos a los visitantes.

## Paso 4 — Sube todos los archivos

Sube **toda esta carpeta completa** (respetando la estructura de
subcarpetas) a la raíz de tu hosting o de tu `htdocs` en XAMPP.

Al terminar, debe verse así:

```
htdocs/boda/   (o public_html/ en un hosting en línea)
  index.php
  api/
    config.php
    upload.php
    like.php
    download-all.php
    test-connection.php
  uploads/
  schema.sql
```

## Paso 5 — Permisos de la carpeta uploads

La carpeta `uploads/` necesita permiso de escritura para que el servidor
pueda guardar las fotos. En XAMPP local esto casi nunca da problema. En un
hosting en línea, desde el administrador de archivos de cPanel, clic
derecho sobre `uploads` → **Permisos** → ponla en `755` (o `775` si `755`
da error al subir fotos).

## Paso 6 — Pruébalo

Entra a `http://localhost/boda/` (o tu dominio) y prueba: subir una foto,
darle "me gusta" a una, y el botón de confirmar por WhatsApp. Todo debería
funcionar recargando la página normal (sin necesitar JavaScript).

Si algo falla, revisa:

- **La página muestra un error de conexión** → corre
  `api/test-connection.php` (ver arriba) para el diagnóstico exacto.
- **"No se pudo guardar la foto en el servidor"** o el contador de fotos
  sube pero no aparecen → revisa los permisos de la carpeta `uploads/`
  (Paso 5).
- El botón **"Descargar todas"** no funciona → tu hosting necesita tener
  habilitada la extensión `ZipArchive` de PHP. Casi todos los hostings la
  traen activada por defecto.
- El botón de WhatsApp abre con un número inválido → revisa que hayas
  reemplazado `521234567890` en `index.php` por el número real (ver
  arriba).

## Notas de seguridad y mantenimiento

- Ya incluí un archivo `.htaccess` dentro de `uploads/` que bloquea la
  ejecución de scripts ahí — es una protección básica para que nadie suba
  código malicioso disfrazado de imagen.
- Este backend es intencionalmente simple (pensado para el uso de un
  evento, no para tráfico masivo). No tiene sistema de administrador ni
  borrado de fotos vía la página — si necesitas quitar alguna foto,
  bórrala directamente desde phpMyAdmin (tabla `photos`) y desde la
  carpeta `uploads/`.
- Los "me gusta" solo suman (no se pueden quitar) — es un contador de
  cariño, no un sistema de "unlike".
- Después de la boda, descarga el ZIP con todas las fotos (botón
  "Descargar todas") y guárdalo en tu computadora o en la nube que
  prefieras como respaldo.
