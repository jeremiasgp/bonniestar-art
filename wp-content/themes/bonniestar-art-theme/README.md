# Bonnie Star: portfolio de obras

La home muestra las Obras publicadas, con una grilla masonry inspirada en
https://www.katrinapallon.com/paintings-2024-2026. Conserva las proporciones
de cada imagen. Cada obra abre un lightbox con su imagen principal y detalles.
Los títulos y medidas aparecen dentro del visor. El contacto queda en el header;
la portada muestra únicamente el catálogo de obras.

## Arquitectura y edición

- `theme.json` es la fuente de colores, tipografía local, espaciados y tokens.
  El espacio entre obras está en `settings.custom.portfolio.gap`.
- `style.css` conserva la capa `starter-base` para los fundamentos y componentes
  con clases propias fuera de la capa, para sobreescribir los defaults de core.
  Las propiedades son lógicas; los colores se referencian mediante tokens.
  La URL del CSS usa la fecha de modificación del archivo como versión de caché,
  tanto en el frontend como en el Editor. Guardar cambios y recargar solicita
  la nueva versión sin tener que editar manualmente la cabecera del tema.
- `patterns/artist-home.php` usa bloques nativos. El bloque Consulta muestra
  18 Obras por página, con el orden manual de **Obras → Ordenar obras** y
  desempate por título e ID, con paginación. Respeta el idioma
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
  Instagram y correo, sin enlace redundante a Obras. El menú móvil usa el diálogo nativo de
  WordPress; el encabezado sticky contempla la barra de administración.
  Su fondo y borde abarcan todo el viewport, conservando la alineación del
  contenido. No tiene degradado ni sombra inferior.
- El logo `.brand-logo` usa Glacial Indifference Regular 400 en mayúsculas,
  tracking 0.30 em, espacio de palabra 0.15 em e interlineado 1.1. La fuente
  WOFF2 es local y su licencia y procedencia están en
  `assets/fonts/glacial-indifference/`. La familia se registra en `theme.json`;
  sus medidas están en `settings.custom.brand`. El color usa el token `ink`.
  El logo mide 26 px desde 60 rem y 22 px por debajo, siempre a la izquierda.
  En móvil el header conserva una fila: Instagram, correo y hamburger quedan a
  la derecha. Hasta 399 px usa el token `compactLetterSpacing` de 0.12 em y
  márgenes menores para conservar el logo de 22 px y los controles accesibles
  sin desbordar a 320 px.
- El sobre abre un popover nativo con enlace `mailto:` e icono de copiar.
  Su flecha apunta al centro del sobre y acompaña la posición del popover.
  La sombra oscura usa el token `settings.custom.header.popoverShadow`.
  Al copiar, solo destella el fondo del botón; la confirmación no agrega texto
  visible ni cambia el tamaño del popover. Se respeta movimiento reducido.
  `assets/js/contact.js` agrega portapapeles, mensaje de resultado y posición
  dentro del viewport; Escape y clic exterior los resuelve el navegador. Si
  falla la copia selecciona el texto y ofrece copiarlo manualmente. Los colores
  y espaciados se toman de los tokens del tema.
- `assets/favicon/` contiene la B de Glacial Indifference en PNG locales, con los
  colores del tema. Se usa en frontend, login y administrador, salvo que se
  configure un icono del sitio nativo de WordPress, que tiene prioridad.
- El header incorpora el bloque nativo de Polylang con banderas enlazadas de los
  otros idiomas (ES/EN/FR). Oculta el idioma actual y conserva los nombres
  accesibles y áreas de interacción de 44 px. El nombre y la navegación regresan
  a la portada del idioma actual. El selector conserva traducciones al navegar
  desde una ficha; si no hay traducción, Polylang enlaza a la portada de destino.
  La detección del navegador se configura en Idiomas → Ajustes de Polylang;
  usa el navegador en la primera visita y recuerda la elección posterior.
  Las banderas se muestran junto a la navegación en escritorio; en móvil están
  dentro del menú desplegable mediante el bloque nativo de idiomas de navegación.
  Las banderas se configuran en Idiomas → Idiomas y usan los SVG locales de
  Polylang; la instalación actual asigna Argentina, Estados Unidos y Francia.
  La bandera argentina se configura en el idioma `es`, conservando su locale
  `es_ES` y las traducciones existentes. El cambio viaja en la base exportada.
  Los controles de contacto y banderas miden 36 × 44 px y están agrupados con
  separación de 16 px entre grupos, definidos en los tokens `settings.custom.header`.

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

Las secciones About y Contacto y sus enlaces fueron retirados de la portada.
El logo es texto en la tipografía local elegida.
El footer muestra `bonniestar.art - liebybonnie@gmail.com` en un párrafo nativo
editable desde Apariencia → Editor → Patrones → Partes de plantilla → Footer.
El texto «Pintura al óleo…» usa el bloque nativo Descripción corta del sitio:
se edita en **Ajustes → Generales → Descripción corta**, y el footer refleja el
cambio sin tocar código. También puede editarse mediante ese bloque en el Editor.
El título del sitio se administra en Ajustes → Generales.

Los cambios guardados en el Editor del sitio prevalecen sobre los archivos del
tema. Exportar los cambios deseados a código para que sean reutilizables; no
borrar personalizaciones de base de datos automáticamente.

## Verificación

Ejecutar `npm run verify:static`, `npm test` y `npm run verify` con Docker activo.
Revisar en navegador la home en móvil y escritorio, el menú con teclado,
las fichas y el lightbox. PHP lint y WPCS no sustituyen la revisión visual ni
la inspección del Editor. Las imágenes originales y el PDF no forman parte del
tema: la biblioteca y los registros de Obras se almacenan en volúmenes Docker.
