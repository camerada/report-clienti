# Lavoro fatto, mese per mese (facoltativo)

Un file per cliente e mese: `lavori/<cliente>/<AAAA-MM>.md` (es. `lavori/identia/2026-10.md`).
Se c'è, il report aggiunge «Cosa abbiamo fatto». È l'unica parte del file che finisce nel report.
«Ci serve da te» e «Prossimi passi» restano promemoria nostri: nel report non compaiono
(le richieste al cliente si fanno a parte; i consigli del report nascono solo dai numeri).
Se non c'è, il report esce lo stesso con i soli dati.

```
## Fatto
Etichetta breve: una o due frasi su cosa abbiamo fatto.

## Ci serve da te
Una domanda o richiesta per riga.

## Prossimi passi
Etichetta breve: cosa dobbiamo fare noi (promemoria, non va nel report).
```
Dopo averlo scritto: `sh carica.sh` (carica anche la cartella lavori/).
