$ErrorActionPreference = 'Stop'

$queries = [System.Collections.Generic.List[string]]::new()

function Resolve-DnsName {
    param(
        [Parameter(Mandatory = $true, Position = 0)]
        [string]$Name,

        [Parameter(Mandatory = $true)]
        [string]$Type
    )

    $queries.Add("${Type}:${Name}")

    [pscustomobject]@{
        Name       = $Name
        Type       = $Type
        NameTarget = 'dc01.example.test'
        Port       = 389
        Priority   = 0
        Weight     = 100
    }
}

$scriptPath = Join-Path $PSScriptRoot '..' 'powershell' 'active-directory' 'Test-DnsSrv.ps1'
& $scriptPath -DomainName 'example.test' | Out-Null

$expected = @(
    'SRV:_ldap._tcp.dc._msdcs.example.test'
    'SRV:_kerberos._tcp.example.test'
)

if ($queries.Count -ne $expected.Count) {
    throw "Se esperaban $($expected.Count) consultas DNS y se recibieron $($queries.Count)."
}

for ($index = 0; $index -lt $expected.Count; $index++) {
    if ($queries[$index] -ne $expected[$index]) {
        throw "Consulta inesperada en la posición ${index}: $($queries[$index])"
    }
}

Write-Output 'Test-DnsSrv.Tests.ps1: OK'
