#!/bin/sh
# Prima installazione sul server (una volta sola), dal Mac:
#   sh installa.sh ~/Downloads/report-clienti-511219-xxxx.json
# 1. carica il programma in ~/report-clienti (fuori dai siti)
# 2. carica la chiave Google come chiave-google.json (leggibile solo dal tuo utente)
# 3. crea config.php prendendo le chiavi di Claude e Brevo dal config.php di vetrina.si, se è sullo stesso account
# Le chiavi restano sul Mac e sul server: non passano da GitHub.
set -e
RADICE=$(cd "$(dirname "$0")" && pwd)
[ -f "$1" ] || { echo "Uso: sh installa.sh percorso/della/chiave.json"; exit 1; }
[ -f "$RADICE/strumenti.conf" ] || cp "$RADICE/strumenti.conf.esempio" "$RADICE/strumenti.conf"
. "$RADICE/strumenti.conf"
: "${CARTELLA:=report-clienti}"
cd "$RADICE"
COPYFILE_DISABLE=1 tar -cf - report.php clienti.php config.example.php lib/*.php risorse/* | ssh "$SSH_ALIAS" "mkdir -p ~/$CARTELLA && cd ~/$CARTELLA && tar -xmf -"
ssh "$SSH_ALIAS" "cat > ~/$CARTELLA/chiave-google.json && chmod 600 ~/$CARTELLA/chiave-google.json" < "$1"
ssh "$SSH_ALIAS" "cd ~/$CARTELLA && if [ -f config.php ]; then echo 'config.php esiste già: non lo tocco.'; else
  cp config.example.php config.php && chmod 600 config.php
  V=\$(ls ~/domains/vetrina.si/public_html/config.php 2>/dev/null || true)
  if [ -n \"\$V\" ]; then
    A=\$(grep \"^define('ANTHROPIC_API_KEY'\" \"\$V\" | head -1); B=\$(grep \"^define('BREVO_API_KEY'\" \"\$V\" | head -1)
    [ -n \"\$A\" ] && sed -i \"s|^define('ANTHROPIC_API_KEY'.*|\$A|\" config.php
    [ -n \"\$B\" ] && sed -i \"s|^define('BREVO_API_KEY'.*|\$B|\" config.php
    echo 'Chiavi di Claude e Brevo copiate da vetrina.si.'
  else echo 'vetrina.si non è su questo account: metti a mano le chiavi in config.php (nano config.php).'; fi
fi
grep -c 'sk-ant-\.\.\.\|xkeysib-\.\.\.' config.php >/dev/null && echo 'ATTENZIONE: in config.php mancano ancora delle chiavi.' || echo 'config.php pronto.'
php -l report.php >/dev/null && echo 'Installato in ~/'$CARTELLA"
