[CmdletBinding()]
param(
    [Parameter(Mandatory = $true)]
    [ValidateNotNullOrEmpty()]
    [string]$DomainName
)

$ErrorActionPreference = 'Stop'

Write-Output "== Registros SRV de Active Directory: $DomainName =="
Write-Output "Consulta de LDAP:"
Resolve-DnsName -Type SRV "_ldap._tcp.dc._msdcs.$DomainName" |
    Select-Object Name, Type, NameTarget, Port, Priority, Weight |
    Format-Table -AutoSize

Write-Output "Consulta de Kerberos:"
Resolve-DnsName -Type SRV "_kerberos._tcp.$DomainName" |
    Select-Object Name, Type, NameTarget, Port, Priority, Weight |
    Format-Table -AutoSize

Write-Output "`nSiguiente paso recomendado: comparar esta salida con dcdiag /test:dns /DnsRecordRegistration."
