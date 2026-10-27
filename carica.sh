#!/bin/sh
# Carica il programma dei report sul server (cartella fuori dai siti), solo i file cambiati.
#   sh carica.sh
# Non tocca mai config.php, chiave-google.json né uscite/ sul server. Non cancella niente.
set -e
RADICE=$(cd "$(dirname "$0")" && pwd)
. "$RADICE/strumenti.conf"
: "${SSH_ALIAS:?SSH_ALIAS mancante in strumenti.conf}" "${CARTELLA:=report-clienti}"
cd "$RADICE"
FILE=$(ls report.php esporta.php gtm.php clienti.php config.example.php lib/*.php risorse/* $(find lavori -type f 2>/dev/null))
echo "Carico su $SSH_ALIAS:~/$CARTELLA :"; echo "$FILE"
printf "Procedo? [s/N] "; read -r ok
[ "$ok" = "s" ] || [ "$ok" = "S" ] || { echo "Annullato."; exit 0; }
# shellcheck disable=SC2086
COPYFILE_DISABLE=1 tar -cf - $FILE | ssh "$SSH_ALIAS" "mkdir -p ~/$CARTELLA && cd ~/$CARTELLA && tar -xmf - && php -l report.php >/dev/null && for f in lib/*.php; do php -l \$f >/dev/null; done && echo 'Caricato, sintassi PHP ok.'"
