# Lavoro fatto, mese per mese (facoltativo)

Un file per cliente e mese: `lavori/<cliente>/<AAAA-MM>.md` (es. `lavori/identia/2026-10.md`).
Se c'è, il report aggiunge «Cosa abbiamo fatto», «Cosa ci serve da te» e usa i prossimi passi già decisi.
Se non c'è, il report esce lo stesso con i soli dati.

```
## Fatto
Etichetta breve: una o due frasi su cosa abbiamo fatto.

## Ci serve da te
Una domanda o richiesta per riga.

## Prossimi passi
Etichetta breve: cosa faremo.
```
Dopo averlo scritto: `sh carica.sh` (carica anche la cartella lavori/).
