# Lightbox de imágenes y galerías

Funcionalidad independiente del tema, sin librerías, bundler, opciones guardadas,
tablas ni endpoints. Se integra con bloques Imagen/Galería y galerías clásicas.
El JavaScript y el CSS no dependen del nombre del proyecto.

## Comportamiento

- Cada galería tiene su propia navegación circular. Las imágenes individuales
  se abren sin controles anterior/siguiente. También se admiten contenedores
  personalizados con `data-gallery-lightbox`.
- Botones, flechas del teclado, Inicio/Fin y gestos horizontales en pantallas
  táctiles. Escape cierra; el foco vuelve al elemento que abrió el modal.
- `<dialog>` nativo mantiene el resto del documento inerte y el foco dentro del
  modal. El fondo no se desplaza. En navegadores sin soporte se conserva la
  imagen/enlace normal, sin cargar polyfills.
- El modal muestra la imagen sin recortar y sin agrandarla por encima de sus
  dimensiones naturales. Las leyendas se leen como texto, nunca como HTML.
- Estados de carga/error, anuncios para lectores de pantalla, objetivos táctiles
  de 44 px y respeto por reducción de movimiento. No hay reproducción automática.
- Respeta enlaces a páginas, adjuntos, tiendas y enlaces de descarga; los enlaces
  directos a imágenes se abren en el modal. Ctrl/Cmd/clic conserva su acción normal.
- Se pueden agregar imágenes al DOM después de cargar la página.

## Resolución

En la biblioteca de WordPress se obtiene `wp_get_attachment_image_src( $id, 'full' )`.
Esto respeta el tamaño completo que WordPress sirve (incluida su versión `-scaled`
cuando corresponde). No se adivinan nombres quitando sufijos ni se carga el archivo
original de cámara por defecto. Solo se descarga la imagen grande al abrirla; no
se precarga toda la galería.

Para imágenes externas se usa `data-gallery-lightbox-src`/`data-full-url`, el enlace
directo al archivo o el candidato más ancho del `srcset`. Sin esa información se
usa la fuente disponible: el visor no puede recuperar resolución que no exista.
Los protocolos admitidos son HTTP/HTTPS. No se hacen peticiones remotas desde PHP.

## Integración y reutilización

`includes/lightbox.php` registra los assets con `wp_enqueue_scripts`: CSS y script
en el head, con `defer` para no bloquear el parseo. Se cargan en el frontend también
cuando las galerías proceden de plantillas, patrones o contenido dinámico; el modal
se crea al primer clic. No se cargan en administración, feeds o peticiones JSON.

El render de bloques desactiva temporalmente el lightbox de core, incluso para
bloques existentes que lo tenían habilitado. No cambia contenido ni preferencias
guardadas. La opción nativa del editor no controla este visor.

Para excluir una imagen/galería/contenedor usar la clase `no-lightbox` o el atributo
`data-no-lightbox`. Para desactivar toda la integración en ciertas páginas, usar el
filtro `bonniestar_art_lightbox_enabled` (también restituye el comportamiento de core).

El CSS del plugin tiene colores del sistema como fallback y hereda la fuente.
El tema adapta estas variables en `.wp-gallery-lightbox` usando sus propios tokens:

```css
.wp-gallery-lightbox {
    --gallery-lightbox-surface: var(--wp--preset--color--surface);
    --gallery-lightbox-text: var(--wp--preset--color--ink);
    --gallery-lightbox-accent: var(--wp--preset--color--accent);
    --gallery-lightbox-backdrop: var(--wp--preset--color--ink);
}
```

Para trasladarlo al boilerplate copiar `includes/lightbox.php`, `assets/lightbox.js`
y `assets/lightbox.css`, e incluir el PHP desde el bootstrap. Adaptar namespace,
dominio de traducción, ruta al archivo principal y filtro al plugin de destino.
Las etiquetas del visor se traducen en PHP y se pasan mediante `wp_localize_script`.
Las plantillas y la grilla de miniaturas siguen siendo responsabilidad del tema.

## Comprobaciones

Con el proyecto iniciado:

```text
npm run wp -- eval-file /var/www/html/wp-content/plugins/bonniestar-art-plugin/tests/integration.php
```

La prueba usa datos de adjuntos solo en la caché de esa ejecución, sin escribir
entradas, opciones o archivos en la biblioteca. Para probar las interacciones en
Chrome/Chromium con Node 20 (indicar la ruta al navegador instalado):

```text
node --experimental-websocket wp-content/plugins/bonniestar-art-plugin/tests/browser.mjs "C:/Program Files/Google/Chrome/Application/chrome.exe"
```

El servidor, perfil y medios sintéticos de esa prueba son temporales e independientes
de WordPress. No se incorporan obras de muestra al sitio.
