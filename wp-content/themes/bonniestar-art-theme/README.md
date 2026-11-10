# Bonniestar Art: primera dirección visual

Tema de bloques para un portfolio de óleo y acrílico, hiperrealismo e
impresionismo. Fondo marfil, acentos borgoña, composición sin sidebars y obras
sin recortar. Los textos de portada son una propuesta editorial inicial.

## Editar el diseño

- **Colores:** editar solo `settings.color.palette` en `theme.json`. El tema
  referencia sus variables `--wp--preset--color--surface`, `ink`, `muted`,
  `accent` y `accent-strong`; no duplicar códigos de color en CSS o bloques.
- **Tipografía:** Atkinson Hyperlegible alojada localmente, con licencia OFL
  incluida en `assets/fonts/atkinson-hyperlegible/`. La familia, pesos,
  tamaños y espaciados están en `theme.json`. Texto base de 18 px, interlineado
  1.7 y columna de lectura de hasta 42 rem. Evitar texto justificado o párrafos
  largos en mayúsculas. Una fuente por sí sola no garantiza accesibilidad.
- **Responsive:** en `style.css`, galería de una columna por defecto, dos desde
  40 rem y tres desde 60 rem. El menú completo aparece desde 60 rem; por debajo
  se usa el menú móvil nativo de WordPress. Son límites de layout, no modelos
  concretos de dispositivo. La tipografía y los márgenes se adaptan fluidamente.
- **Header:** sticky, con bloque Logo del sitio y nombre a la izquierda;
  navegación e Instagram a la derecha. Cargar el logo desde el editor. El bloque
  Instagram espera una URL real: WordPress no muestra el icono público hasta
  completarla. No se incluyó una cuenta de ejemplo.
- **Footer:** nombre y descripción corta del sitio, configurables en WordPress.

## Cargar las obras

1. Abrir Apariencia → Editor → Plantillas → Página de inicio.
2. En la sección «La galería», seleccionar el bloque Galería vacío desde la
   vista de lista y subir las imágenes reales. No se incluyen obras de muestra.
3. Conservar la clase `bonniestar-art-gallery` y desactivar el recorte. Completar
   el texto alternativo y una leyenda con título, técnica y medidas.
4. Quitar el párrafo provisional cuando la galería tenga contenido.
5. El plugin amplía las imágenes sin enlace o con enlace al archivo en su lightbox
   JavaScript. Las URLs de páginas/adjuntos conservan su navegación. Usar la clase
   `no-lightbox` en una imagen o galería para excluirla. El lightbox nativo se
   deshabilita durante el render para evitar dos modales sobre la misma imagen.

La portada se define en `templates/front-page.html` y el patrón `artist-home.php`;
se edita como plantilla, no como contenido de una página. El patrón `site-header.php`
genera enlaces a la portada que también funcionan desde páginas interiores y
subdirectorios. Después de guardar una personalización en el Editor, exportarla
al código si debe formar parte del tema: la versión guardada en la base de datos
prevalece sobre estos archivos.

Se conservan `page.html` y `single.html` para las URLs individuales. La galería
no crea entradas o modelos de obra: una ficha con URL propia, metadatos y enlace
desde el modal puede añadirse después en el plugin, cuando se defina ese contenido.

Antes de publicar: cargar logo, obras, URL de Instagram y datos reales de contacto;
revisar textos y probar teclado, modal, zoom de texto y Editor en móvil y escritorio.
