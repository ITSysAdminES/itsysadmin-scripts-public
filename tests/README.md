# Pruebas

Las pruebas no deben conectarse a producción. Usa entradas sintéticas, nombres de
dominio de ejemplo y directorios temporales controlados.

`Test-DnsSrv.Tests.ps1` sustituye la resolución DNS por una función local y comprueba
que la herramienta consulte los localizadores LDAP y Kerberos correctos. Se puede
ejecutar sin red:

```powershell
./tests/Test-DnsSrv.Tests.ps1
```
