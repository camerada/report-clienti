#!/bin/bash
# Handoff automatico – dopo la compattazione: si riparte dal passaggio di consegne.
F="${CLAUDE_PROJECT_DIR:-$(pwd)}/.claude/HANDOFF.md"
echo "HANDOFF AUTOMATICO: la conversazione ha superato i 300.000 token ed è stata compattata."
echo "Prima di continuare: usa la skill handoff e aggiorna .claude/HANDOFF.md (aggiungi in cima una sezione con data e ora, non cancellare le precedenti). Poi riprendi dal primo dei prossimi passi e dillo all'utente in una riga."
if [ -f "$F" ]; then
  echo "--- Ultimo .claude/HANDOFF.md ---"
  head -n 80 "$F"
fi
exit 0
