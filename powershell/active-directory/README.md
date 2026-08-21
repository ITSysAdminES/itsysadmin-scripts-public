# Active Directory

Los scripts de esta carpeta son de lectura. Requieren PowerShell con resolución DNS
operativa y no modifican zonas ni servicios.

## Test-DnsSrv.ps1

Comprueba los localizadores SRV de LDAP y Kerberos para un dominio indicado.

```powershell
.\Test-DnsSrv.ps1 -DomainName example.local
```

Para validar el registro dinámico y A/CNAME, utiliza además las pruebas documentadas
por Microsoft en `dcdiag`. No borres registros SRV manualmente como primera medida.
