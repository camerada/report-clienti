#!/bin/sh
# Una tantum (4/11/2026): toglie dalla home dell'account la copia del programma finita lì per errore
# (git checkout del 4/11 alle 10:43 UTC, per un cron con `cd $HOME/...` non espanso).
# Toglie solo i file che quella copia git conosce, la sua cartella .git e le cartelle rimaste vuote.
cd /home/u745278502 || exit 1
if [ ! -d .git ] || [ ! -f dopo-aggiornamento.sh ] || [ ! -d report-clienti ]; then echo "Niente da pulire"; exit 0; fi
git ls-files -z | xargs -0 rm -f
rm -rf .git
for d in lavori/identia lavori/postavecchia lavori lib prova risorse web; do rmdir "$d" 2>/dev/null; done
echo "Home ripulita. Rimasti dal programma:"; ls -d .git .gitignore CLAUDE.md README.md report.php invia.php lib web 2>&1
