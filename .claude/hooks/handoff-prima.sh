#!/bin/bash
# Handoff automatico – prima della compattazione (soglia 300.000 token).
# Salva una copia della chat (solo in locale, esclusa da git) e guida il riassunto.
INPUT=$(cat)
TP=$(printf '%s' "$INPUT" | sed -n 's/.*"transcript_path"[[:space:]]*:[[:space:]]*"\([^"]*\)".*/\1/p')
DIR="${CLAUDE_PROJECT_DIR:-$(pwd)}/.claude/handoff/archivio"
mkdir -p "$DIR" 2>/dev/null
if [ -n "$TP" ] && [ -f "$TP" ]; then
  cp "$TP" "$DIR/$(date +%Y%m%d-%H%M%S).jsonl" 2>/dev/null
fi
cat <<'TXT'
Scrivi il riassunto come un passaggio di consegne (handoff), in italiano:
- Obiettivo del lavoro
- Contesto (cliente, progetto, vincoli)
- Decisioni prese dall'utente (solo quelle confermate)
- Stato attuale: cosa è fatto e cosa è in corso
- Materiali: file, cartelle, link, con percorso completo
- Punti aperti e dati mancanti
- Prossimi passi in ordine di priorità
Non perdere nomi di file, numeri, prezzi e decisioni.
TXT
exit 0
