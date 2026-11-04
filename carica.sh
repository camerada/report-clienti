#!/bin/sh
# Carica il programma dei report sul server (cartella fuori dai siti), solo i file cambiati.
#   sh carica.sh
# Non tocca mai config.php, chiave-google.json né uscite/ sul server. Non cancella niente.
# Copia anche web/invia.php in camecomunicazione.it/report/ (pulsante «Invia al cliente» delle anteprime).
set -e
RADICE=$(cd "$(dirname "$0")" && pwd)
. "$RADICE/strumenti.conf"
: "${SSH_ALIAS:?SSH_ALIAS mancante in strumenti.conf}" "${CARTELLA:=report-clienti}"
cd "$RADICE"
FILE=$(ls report.php invia.php web/invia.php esporta.php gtm.php ga4-settimanale.php clienti.php config.example.php lib/*.php risorse/* $(find lavori -type f 2>/dev/null))
echo "Carico su $SSH_ALIAS:~/$CARTELLA :"; echo "$FILE"
printf "Procedo? [s/N] "; read -r ok
[ "$ok" = "s" ] || [ "$ok" = "S" ] || { echo "Annullato."; exit 0; }
# shellcheck disable=SC2086
COPYFILE_DISABLE=1 tar -cf - $FILE | ssh "$SSH_ALIAS" "mkdir -p ~/$CARTELLA && cd ~/$CARTELLA && tar -xmf - && php -l report.php >/dev/null && php -l invia.php >/dev/null && php -l ga4-settimanale.php >/dev/null && mkdir -p ~/domains/camecomunicazione.it/public_html/report && cp web/invia.php ~/domains/camecomunicazione.it/public_html/report/invia.php && for f in lib/*.php; do php -l \$f >/dev/null; done && echo 'Caricato, sintassi PHP ok.'"
