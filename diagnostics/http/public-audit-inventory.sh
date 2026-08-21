#!/usr/bin/env bash

# Shared inventory for the read-only public audits. Update URLs here when the
# editorial inventory changes; the audit scripts should not contain duplicates.

PUBLIC_AUDIT_DEFAULT_MAX_TIME=25

PUBLIC_AUDIT_URLS=(
  'https://itsysadmin.es/'
  'https://itsysadmin.es/cloud/cloudflare-tunnel-error-502-1033-diagnostico/'
  'https://itsysadmin.es/en/cloud-guides/cloudflare-tunnel-502-error-1033-troubleshooting/'
  'https://itsysadmin.es/cloud/wordpress-cloudflare-bucle-redirecciones-contenido-mixto/'
  'https://itsysadmin.es/en/cloud-guides/wordpress-cloudflare-redirect-loop-mixed-content/'
  'https://itsysadmin.es/microsoft/active-directory-target-principal-name-incorrect-replicacion/'
  'https://itsysadmin.es/en/microsoft-guides/active-directory-target-principal-name-incorrect-replication/'
  'https://itsysadmin.es/linux/synology-container-manager-permission-denied-volumen/'
  'https://itsysadmin.es/en/linux-guides/synology-container-manager-permission-denied-volume/'
  'https://itsysadmin.es/microsoft/microsoft-365-correo-no-llega-message-trace-delivered/'
  'https://itsysadmin.es/en/microsoft-guides/microsoft-365-message-trace-delivered-email-missing/'
  'https://itsysadmin.es/cloud/cloudflare-universal-ssl-certificado-pendiente-no-valido/'
  'https://itsysadmin.es/en/cloud-guides/cloudflare-universal-ssl-certificate-pending-not-trusted/'
  'https://pulsogg.es/'
  'https://pulsogg.es/league-of-legends/oscarinin-giantx-volver-lec-reconstruir-imagen/'
  'https://pulsogg.es/league-of-legends/flakked-giantx-regreso-lec-ansiedad-scrims/'
  'https://pulsogg.es/league-of-legends/elyoya-capitan-crisis-movistar-koi-lec-2026/'
  'https://pulsogg.es/league-of-legends/myrwn-incomodar-draft-creatividad-critica-top-lec/'
  'https://pulsogg.es/call-of-duty/jurnii-antes-cdl-heretics-call-of-duty-espanol/'
)

PUBLIC_AUDIT_ENDPOINTS=(
  'https://itsysadmin.es/robots.txt'
  'https://itsysadmin.es/wp-sitemap.xml'
  'https://itsysadmin.es/sitemap-en.xml'
  'https://pulsogg.es/robots.txt'
  'https://pulsogg.es/wp-sitemap.xml'
)

PUBLIC_AUDIT_DOMAINS=(itsysadmin.es pulsogg.es)

PUBLIC_VALIDATE_URLS=(
  'https://itsysadmin.es/microsoft/active-directory-cuenta-bloqueada-evento-4740-origen/'
  'https://itsysadmin.es/cloud/wordpress-programacion-perdida-wp-cron-docker-cloudflare/'
  'https://itsysadmin.es/linux/docker-compose-wordpress-mariadb-healthcheck-orden-arranque/'
  'https://itsysadmin.es/cloud/cloudflare-proteger-wp-login-googlebot-seo-verified-bots/'
  'https://pulsogg.es/counter-strike-2/utilidad-cs2-como-leer-ejecucion-smokes-flashes-molotov/'
  'https://pulsogg.es/valorant/economia-valorant-comprar-guardar-ronda-bonus-guia/'
  'https://pulsogg.es/call-of-duty/veto-mapas-cdl-hardpoint-search-destroy-overload/'
  'https://pulsogg.es/league-of-legends/vision-antes-objetivo-lol-control-rio-baron-dragon/'
  'https://pulsogg.es/explorar/'
  'https://pulsogg.es/comunidad/'
)
