# ITSysAdmin Scripts (public)

Read-only diagnostics for Windows Server, Active Directory, Bash/HTTP, WordPress, Docker and Synology environments.

This public repository is a history-free export of the reviewed public tree at commit `c04c39c77af1e5008a37c2aa365f6253a5ea1eec`. Code links should use a release or commit, not a moving branch.

## Scope

- PowerShell checks for Active Directory DNS SRV records.
- PHP audits for WordPress integrity, core/plugin checksums, editorial content and URLs.
- Bash audits for public HTTP responses, metadata, redirects and inventories.
- Read-only examples and tests.

The scripts are diagnostics, not a deployment system. Review each script and run it against staging first. They do not modify WordPress, DNS, Docker or services.

## Requirements

- Bash, curl and ShellCheck for the HTTP audits.
- PHP 7.4+ for the WordPress scripts; remote checks require HTTPS and a configured timeout.
- Windows PowerShell 5.1+ and PSScriptAnalyzer for the Active Directory check.
- A WordPress installation is required only for scripts that load `wp-load.php`.

Never pass credentials on command lines. Do not publish command output, cookies, database dumps, `.env` files, `wp-config.php`, private keys or internal addresses.

## Testing

The CI workflow runs PHP syntax checks, ShellCheck, PowerShell linting and a local secret-pattern scan without uploading file contents. Environment-dependent WordPress, Windows and remote HTTP checks must be run in an isolated environment by the operator.

## Guías relacionadas en ITSysAdmin

Consulta el [hub público de herramientas de diagnóstico](https://itsysadmin.es/herramientas-de-diagnostico/) y estas guías prácticas:

- [Test-DnsSrv.ps1: comprobar registros SRV de Active Directory](https://itsysadmin.es/microsoft/active-directory-dns-registros-srv-ausentes/)
- [Auditoría de Cloudflare y WordPress](https://itsysadmin.es/cloud/wordpress-cloudflare-bucle-redirecciones-contenido-mixto/)
- [Diagnóstico de Docker y MariaDB](https://itsysadmin.es/linux/docker-compose-wordpress-mariadb-healthcheck-orden-arranque/)
- [Auditoría HTTP de una web no accesible](https://itsysadmin.es/cloud/diagnosticar-web-no-accesible-desde-ip-publica/)

Cada guía explica el contexto, los requisitos y los límites del script correspondiente. Los diagnósticos son de solo lectura y deben revisarse antes de ejecutarlos.

## License and security

Scripts are MIT licensed. See [LICENSE](LICENSE), [SECURITY.md](SECURITY.md) and [docs/security-model.md](docs/security-model.md).
