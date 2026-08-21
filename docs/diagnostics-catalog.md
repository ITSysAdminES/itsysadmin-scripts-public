# Catálogo de diagnósticos

## WordPress

- `diagnostics/wordpress/verify-wordpress-integrity.php`: compara un árbol local con un manifiesto de checksums de core o plugin. No modifica archivos.
- `diagnostics/wordpress/verify-wordpress-core-remote.php`: obtiene el manifiesto oficial del core y devuelve faltantes y discrepancias. Requiere salida HTTPS y aplica un timeout (15 s por defecto, configurable con `WP_REMOTE_TIMEOUT`). La ruta puede pasarse como primer argumento o mediante `WORDPRESS_ROOT`; sin ambos conserva `/var/www/html`.
- `diagnostics/wordpress/verify-wordpress-plugins-remote.php`: consulta los manifiestos de plugins publicados y compara sus archivos. Marca como `unverified` los plugins de terceros, de un solo archivo o sin manifiesto; la lista `extra_files` solo se calcula cuando el escaneo local es viable y no equivale a una garantía universal.
- `diagnostics/wordpress/editorial-audit.php`: resume publicaciones, páginas, palabras, imágenes, extractos, enlaces y categorías.
- `diagnostics/wordpress/url-audit.php`: cuenta coincidencias de una o más cadenas en contenido editorial por defecto; usa consultas preparadas y solo lectura. `--include-options` y `--include-comments` son opt-in y muestran un aviso porque esas tablas pueden contener configuración sensible o datos personales.

Los tres scripts que cargan WordPress esperan ejecutarse dentro del contenedor o instalación que tenga `/var/www/html/wp-load.php`. Revisa el código antes de adaptar la ruta.

`verify-wordpress-integrity.php` acepta `<root> <manifest> <core|plugin>`, rechaza rutas absolutas o con `..` y no imprime la ruta completa del sistema. `editorial-audit.php` pagina los posts: usa `EDITORIAL_AUDIT_LIMIT` (100 por defecto, máximo 1000), `EDITORIAL_AUDIT_PAGE` o `--limit=N --page=N`.

## Web pública

- `diagnostics/http/public-production-audit.sh`: comprueba páginas y endpoints publicados de ITSysAdmin y PulsoGG.
- `diagnostics/http/validate-public.sh`: valida páginas editoriales, canonical, descripción, JSON-LD y referencias internas.

Ambos scripts realizan peticiones GET/HEAD y no escriben en los sitios. Comparten el inventario en `diagnostics/http/public-audit-inventory.sh`; actualiza ese archivo cuando cambie el conjunto de URLs. El timeout común es 25 segundos (`PUBLIC_AUDIT_MAX_TIME`) y cada script admite `--max-time SEGUNDOS`.

## Active Directory

- `powershell/active-directory/Test-DnsSrv.ps1`: consulta los SRV de LDAP y Kerberos de un dominio.
- `tests/Test-DnsSrv.Tests.ps1`: sustituye la resolución DNS por una función local y verifica las dos consultas esperadas sin red.
