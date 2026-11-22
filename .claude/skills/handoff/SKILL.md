---
name: handoff
description: Crea o aggiorna il passaggio di consegne (handoff) del progetto in .claude/HANDOFF.md. Usala quando l'utente dice handoff o passaggio di consegne, quando un hook segnala un handoff automatico, o all'inizio di una sessione per riprendere il lavoro.
---

# Handoff – passaggio di consegne

Scopo: riprendere il lavoro senza rileggere tutta la conversazione e senza perdere decisioni. Il file vive nel repo: è la memoria del progetto tra una sessione e l'altra, su qualsiasi dispositivo.

## Inizio sessione
Se esiste `.claude/HANDOFF.md`, leggi la sezione più recente prima di proporre qualsiasi cosa.

## Scrivere l'handoff
1. Distingui ciò che l'utente ha DECISO da ciò che Claude ha solo PROPOSTO.
2. Non inventare: se un dato manca, mettilo in "Punti aperti".
3. Aggiungi la nuova sezione IN CIMA a `.claude/HANDOFF.md`, sotto il titolo; non cancellare le sezioni precedenti. Se il file supera ~300 righe, riassumi le sezioni più vecchie in poche righe.
4. Nessun dato sensibile: password, chiavi API, token, numeri di carta o di conto.
5. In chat (fuori da un repo) consegna un unico blocco copiabile.

## Struttura di ogni sezione

## [data e ora] – [titolo breve]
**Obiettivo**
**Contesto**
**Decisioni prese**
**Stato attuale**
**Materiali** (file e link con percorso)
**Punti aperti**
**Prossimi passi** (in ordine)
