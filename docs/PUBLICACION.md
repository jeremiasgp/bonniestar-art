# Publicar Bonnie Star en Hostinger

Destino: **https://bonniestar.art**. Este procedimiento prepara archivos locales;
no conecta al hosting ni publica. Commit, tag y transferencia quedan para después
de la aprobación del resultado. La pantalla «Under construction» existente se
mantiene hasta completar la migración y sus comprobaciones.

## Preparación repetible

Con Docker iniciado, desde la raíz del repositorio:

```text
npm run media:audit
npm run media:optimize
npm run verify:static
npm test
npm run verify
npm run release:prepare -- --url https://bonniestar.art
```

No hace falta instalar dependencias npm. En PowerShell se puede usar `npm.cmd`.
`media:optimize` respalda primero la base y uploads en `.cache/releases/before-webp-*`.
Luego convierte cada versión con el editor nativo de WordPress: calidad 85 para
la imagen completa y 80 para los tamaños responsive. Conserva dimensiones,
IDs, títulos, textos alternativos y relaciones de galerías. Usa WebP solo cuando
reduce el peso. Los originales y sus metadatos previos se conservan, y las
imágenes ya procesadas se omiten al repetir el comando. No se ejecuta al cargar
el sitio ni instala plugins. Para imágenes agregadas en el futuro, volver a correr
el comando antes de preparar otra migración de medios.

`release:prepare` crea una carpeta nueva `.cache/releases/hostinger-*` con:

- `site-files.tar.gz`: tema propio, plugin propio y plugins activos (actualmente
  Polylang). Excluye pruebas, herramientas CLI, WordPress core,
  configuración local, dependencias de desarrollo e historial Git.
- `media-01.tar.gz`, `media-02.tar.gz`, etc.: uploads completos en grupos de hasta
  unos 100 MiB antes de comprimir. Cada archivo es un tar independiente: se
  extraen todos en la misma raíz, sin concatenarlos. Facilitan la subida desde
  el administrador y conservan también los originales de alta resolución.
- `database-production.sql.gz` y su versión `.sql`: exportación completa, con
  las URLs reemplazadas por `https://bonniestar.art`. WP-CLI procesa los valores
  serializados y conserva la columna GUID como identificador estable. La base
  local no cambia; tampoco se cambia su URL a producción.
- `manifest.json`: versiones de WordPress/PHP/plugins, prefijo de tablas,
  conteos de contenido y SHA-256 de cada archivo.

Estas carpetas están ignoradas por Git. La base contiene cuentas y hashes de
contraseñas: se importa por phpMyAdmin, **no se sube a `public_html`**. Las
credenciales de base de datos y salts de producción se configuran en el hosting;
no se incluyen en los paquetes. No se copia `.env` ni `wp-config.php` local.

## Primera migración, después de aprobar

1. Hacer un backup de los archivos y de la base que ya existan en Hostinger,
   incluyendo la implementación actual de «Under construction». Identificar si
   usa `index.html`, un sitio WordPress o una opción de hPanel. No sobrescribir
   esa base: crear una base nueva y conservar la anterior para volver atrás.
2. Confirmar PHP **8.3 o superior**, soporte WebP y WordPress compatible con las
   versiones del manifiesto. El tema y el plugin requieren WordPress **7.1+**;
   para esta primera migración usar la misma versión de core indicada en el
   paquete. Instalar core desde una distribución oficial o el instalador de
   Hostinger. El Docker local no se sube al hosting.
3. Abrir el Administrador de archivos del dominio y localizar su raíz real
   (habitualmente `public_html`). Subir `site-files.tar.gz` y **todos** los
   `media-*.tar.gz`, y extraer cada uno allí: debe quedar
   `public_html/wp-content/...`, sin otra carpeta intermedia.
   Mantener la pantalla de construcción mientras se valida. Eliminar el archivo
   comprimido del servidor cuando termine cada extracción. No subir el SQL.
4. Crear una base MySQL vacía y su usuario desde hPanel. En su phpMyAdmin,
   importar `database-production.sql.gz`. El dump contiene las cuentas y toda la
   configuración local, incluidos idiomas, galerías y orden manual. La cuenta
   administrativa creada por un instalador de Hostinger no sustituye la cuenta
   importada. Guardar las credenciales de producción en el gestor de contraseñas.
5. Configurar el `wp-config.php` del servidor con la base nueva, host de MySQL y
   `$table_prefix` exacto del manifiesto, y generar salts nuevos. Mantener
   `WP_DEBUG` en `false`, `WP_ENVIRONMENT_TYPE` en `production` y
   `DISALLOW_FILE_EDIT` en `true`. Verificar que no haya constantes `WP_HOME` o
   `WP_SITEURL` del instalador apuntando a otro dominio. No trasladar los valores
   de desarrollo que deshabilitan todas las actualizaciones: definir la política
   de actualizaciones y backups en Hostinger.
6. Entrar al administrador con una cuenta importada, cambiar su contraseña y
   revisar el correo administrativo. Confirmar tema y plugins activos según el
   manifiesto. Guardar **Ajustes → Enlaces permanentes**, conservando
   `/%postname%/`; esto genera las reglas del servidor. Revisar Idiomas → Ajustes
   de Polylang y las rutas `/en/` y `/fr/`.
7. Vaciar cachés de WordPress/Hostinger/CDN después de la importación. Revisar las
   17 obras actuales, el orden manual, imágenes responsive, lightbox y detalles,
   favicon, correo y copia, Instagram, menú móvil y cambio de idioma. Comprobar
   que los recursos se sirvan por HTTPS y desde `bonniestar.art`.
8. Con el sitio revisado, retirar la pantalla de construcción o renombrar su
   `index.html` si ese es el mecanismo que usa. Confirmar que WordPress sea el
   índice servido. En Ajustes → Lectura, habilitar la indexación cuando se decida
   abrir el sitio, y comprobar `robots.txt`, sitemap y respuesta de la portada.

No hay cambios remotos automatizados en esta etapa. El paquete incluye una
instantánea completa de uploads, también los JPEG conservados como respaldo;
el ahorro medido corresponde a los archivos que WordPress sirve al visitante,
no al espacio total del backup.

## Actualizaciones posteriores y GitHub

Para preparar solo código, sin importar otra vez la base ni uploads:

```text
npm run release:code -- --url https://bonniestar.art
```

Produce otro `site-files.tar.gz` con los componentes activos. Revisar el manifiesto,
respaldar esos directorios en Hostinger y transferirlos mediante el administrador
de archivos. No reemplazar la base en cada actualización: al comenzar a editar
contenido en producción, la base del hosting pasa a ser la fuente de ese contenido.

GitHub puede guardar releases y generar el paquete de código desde un tag.
La entrega automática requiere confirmar las opciones del plan: SFTP depende del
acceso SSH y no es el mismo protocolo que FTP. Con los accesos confirmados hasta
ahora, el administrador HTTPS de archivos es suficiente para publicar. Si se
habilita SSH/SFTP, se puede agregar un workflow con un GitHub Environment de
producción, aprobación de despliegue, clave en Secrets y verificación de la clave
del servidor. No guardar claves ni contraseñas en el repositorio. GitHub Pages
no ejecuta WordPress ni reemplaza el hosting contratado.

## Recuperación

Ante un fallo en la primera publicación, restaurar los directorios previos y
volver a apuntar `wp-config.php` a la base anterior; restaurar también su pantalla
de construcción. Para revertir la optimización local, el respaldo `before-webp-*`
contiene la base y uploads completos anteriores a la conversión. Restaurarlos es
una operación explícita de recuperación, no parte de los comandos de preparación.

Referencias:

- [Hostinger: extraer archivos](https://www.hostinger.com/support/1583613-how-to-extract-archives-using-the-file-manager-in-hostinger/)
- [Hostinger: transferencia manual](https://www.hostinger.com/support/1583289-how-to-manually-transfer-a-website-to-hostinger/)
- [Hostinger: protocolos y acceso al servidor](https://www.hostinger.com/support/which-file-transfer-and-server-access-options-are-supported-at-hostinger/)
- [WordPress: exportar sustituyendo URLs y datos serializados](https://developer.wordpress.org/cli/commands/search-replace/)
