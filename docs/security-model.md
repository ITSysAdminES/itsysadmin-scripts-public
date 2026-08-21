# Modelo de seguridad

## Nunca subir

- Tokens de Cloudflare, GitHub, MailPoet o IndexNow.
- Claves privadas SSH o WireGuard.
- Contraseñas, cookies de sesión o archivos `.env`.
- `wp-config.php`, volcados MariaDB y copias de WordPress.
- Nombres de host internos que no sean necesarios para reproducir una prueba.

## Antes de cada commit

1. Revisar `git status`.
2. Revisar el contenido preparado con `git diff --cached`.
3. Buscar patrones de secretos y direcciones internas.
4. Confirmar que el script no escribe fuera de la ruta indicada.
5. Documentar permisos, modo lectura y rollback.

## Releases

Una release solo se crea después de probar el script en un entorno aislado. El artículo
de ITSysAdmin enlazará a una release concreta, no a la rama `main`.
