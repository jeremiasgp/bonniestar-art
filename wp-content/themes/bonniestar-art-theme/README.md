# Bonnie Star: portfolio de obras

La home muestra las Obras publicadas, con una grilla masonry inspirada en
https://www.katrinapallon.com/paintings-2024-2026. Conserva las proporciones
de cada imagen. Cada obra abre un lightbox con su imagen principal y detalles.
Los títulos y medidas aparecen dentro del visor. About y Contacto
completan la portada con información del catálogo de la artista.

## Arquitectura y edición

- `theme.json` es la fuente de colores, tipografía local, espaciados y tokens.
  El espacio entre obras está en `settings.custom.portfolio.gap`.
- `style.css` conserva la capa `starter-base` para los fundamentos y componentes
  con clases propias fuera de la capa, para sobreescribir los defaults de core.
  Las propiedades son lógicas; los colores se referencian mediante tokens.
- `patterns/artist-home.php` usa bloques nativos. El bloque Consulta muestra
  18 Obras por página, ordenadas por título, con paginación. Respeta el idioma
  actual cuando Polylang está activo.
- `.bonniestar-art-masonry` usa columnas CSS: una por defecto, dos desde 25 rem
  y tres desde 60 rem. Cada tarjeta se mantiene completa y la lectura sigue el
  documento: de arriba abajo en una columna, luego la siguiente. No requiere
  JavaScript ni soporte experimental de CSS masonry.
- El corte de 25 rem (400 px con fuente raíz de 16 px) deja dos imágenes de unos
  170 px después de los márgenes y la separación. Hasta 399 px se usa una columna;
  desde 60 rem, tres. El hover reduce suavemente la opacidad durante 240 ms y
  respeta la preferencia de movimiento reducido. El teclado conserva foco visible.
- `patterns/site-header.php` contiene la marca de texto Bonnie Star, enlaces a
  Obras, About y Contacto e Instagram. El menú móvil usa el diálogo nativo de
  WordPress; el encabezado sticky contempla la barra de administración.
  Su fondo y borde abarcan todo el viewport, conservando la alineación del
  contenido. No tiene degradado ni sombra inferior.
- El header incorpora el bloque nativo de Polylang con banderas enlazadas de los
  otros idiomas (ES/EN/FR). Oculta el idioma actual y conserva los nombres
  accesibles y áreas de interacción de 44 px. El nombre y la navegación regresan
  a la portada del idioma actual. El selector conserva traducciones al navegar
  desde una ficha; si no hay traducción, Polylang enlaza a la portada de destino.
  La detección del navegador se configura en Idiomas → Ajustes de Polylang;
  usa el navegador en la primera visita y recuerda la elección posterior.
  Las banderas se configuran en Idiomas → Idiomas y usan los SVG locales de
  Polylang; la instalación actual asigna España, Estados Unidos y Francia según
  los locales es_ES, en_US y fr_FR.

La configuración local usa enlaces amigables (`/%postname%/`) para habilitar las
rutas de idioma `/en/` y `/fr/` y la detección nativa de Polylang. Español es el
idioma de respaldo. Los Query de Obras muestran la traducción publicada de cada
pieza o su original cuando falta esa traducción, sin duplicar registros. Los textos
del sitio y los títulos siguen necesitando sus traducciones editoriales.

Para agregar o modificar una pieza, usar **Obras** en el administrador: título,
imagen destacada, técnica, medidas, contenido y galería de detalles. La home
se actualiza al publicar. No hay que duplicar imágenes en una galería manual.
La técnica de cada obra queda vacía si la fuente no la especifica.

`single-bonniestar_art_obra.html` muestra imagen principal, datos y detalles con
el lightbox del plugin. La ficha se conserva para accesos directos por URL.
La portada usa `is-artwork-lightbox` en Imagen destacada: el plugin agrupa las
imágenes de esa Obra y descarga cada imagen grande cuando se abre. El modelo de
contenido, las traducciones y los metadatos siguen perteneciendo al plugin.

About usa únicamente la descripción y el contacto del catálogo; se puede ampliar
con una biografía aprobada en el Editor del sitio. El logo provisional es texto.
Título y descripción del sitio se administran en Ajustes → Generales.

Los cambios guardados en el Editor del sitio prevalecen sobre los archivos del
tema. Exportar los cambios deseados a código para que sean reutilizables; no
borrar personalizaciones de base de datos automáticamente.

## Verificación

Ejecutar `npm run verify:static`, `npm test` y `npm run verify` con Docker activo.
Revisar en navegador la home en móvil y escritorio, el menú con teclado,
las fichas y el lightbox. PHP lint y WPCS no sustituyen la revisión visual ni
la inspección del Editor. Las imágenes originales y el PDF no forman parte del
tema: la biblioteca y los registros de Obras se almacenan en volúmenes Docker.
