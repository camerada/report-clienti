# Report clienti — CAME Comunicazione

Ogni mese, per ogni cliente in `clienti.php`: numeri da Google Analytics e Search Console → Claude scrive
i testi in parole semplici → PDF nello stile dei resoconti CAME (copertina, In breve, grafico dei 3 mesi,
cosa ci dicono i dati, i numeri del mese, prossimi passi) → email.

Lavoro fatto nel mese (facoltativo): `lavori/<cliente>/<AAAA-MM>.md`, vedi `lavori/LEGGIMI.md`.
Caratteri (Bricolage Grotesque, Inter Tight, Space Mono, licenza OFL) e logo in `risorse/`.

- `automatico => false`: il PDF arriva solo a CAME (`EMAIL_CAME`), da controllare e inoltrare a mano.
- `automatico => true` + `email` del cliente: va direttamente al cliente, CAME in copia.

Gira sul server Hostinger (stesso account di identia.it) nella cartella `~/report-clienti`, fuori dai siti.
Un cron lo lancia il 4 di ogni mese (Search Console ha 2-3 giorni di ritardo sui dati).

Esempio con dati finti: `prova/esempio-identia.pdf`.

## Comandi

```sh
php report.php --cliente=identia                  # mese scorso → anteprima a CAME
php report.php --cliente=identia --mese=2026-10   # un mese preciso
php report.php --cliente=identia --solo-pdf       # solo il PDF in uscite/, niente email
php report.php --tutti                            # tutti i clienti (lo usa il cron)
php report.php --cliente=identia --prova          # dati finti, per provare l'impaginazione

php ga4-settimanale.php                           # JSON GA4 della settimana scorsa per il report social (tuosito)
php ga4-settimanale.php --diagnostica             # cosa arriva a GA4 negli ultimi 30 giorni (eventi, sorgenti)
php ga4-settimanale.php --stampa                  # JSON su schermo, senza scrivere niente
```

Un report già inviato al cliente non riparte due volte (`uscite/registro.csv`); `--forza` per rifarlo.
Se qualcosa va storto, arriva un'email «Report di … non riuscito» a CAME.

## Messa in funzione (una volta sola)

### 1. Account di servizio Google (legge Analytics e Search Console)
1. https://console.cloud.google.com → nuovo progetto «report-clienti».
2. «API e servizi» → «Abilita API»: **Google Analytics Data API** e **Google Search Console API**.
3. «IAM e amministrazione» → «Account di servizio» → Crea («report-clienti») → nessun ruolo → Fine.
4. Apri l'account → «Chiavi» → Aggiungi chiave → JSON: si scarica un file. È una password: non va su GitHub né per email.
5. Copia l'indirizzo dell'account (tipo `report-clienti@….iam.gserviceaccount.com`).

### 2. Dare accesso, per ogni cliente
- **Analytics**: Amministrazione → Gestione accessi all'account (o alla proprietà) → + → l'indirizzo dell'account di servizio → ruolo **Visualizzatore**.
- **Search Console**: Impostazioni → Utenti e autorizzazioni → Aggiungi utente → stesso indirizzo → **Limitato**.

### 3. Server (dal Mac, nella cartella del repo)
```sh
sh installa.sh ~/Downloads/<chiave-scaricata>.json   # programma + chiave Google + config.php (chiavi prese da vetrina.si)
ssh identia "cd report-clienti && php report.php --cliente=identia --mese=2026-10"   # prova vera → email a CAME
```
Dopo modifiche al codice: `sh carica.sh`.

### 4. Cron (hPanel → Avanzate → Cron job)
Il 4 di ogni mese alle 8:12:
```
12 8 4 * * /usr/bin/php /home/<utente>/report-clienti/report.php --tutti
```
Il lunedì alle 7:30 (dati GA4 per il report social settimanale, vedi `ga4-settimanale.php`):
```
30 7 * * 1 /usr/bin/php /home/<utente>/report-clienti/ga4-settimanale.php >/dev/null 2>&1
```

## Aggiungere un cliente
Copia il blocco `identia` in `clienti.php`, cambia ID Analytics, Search Console, colore, eventi di contatto
e note; dai accesso all'account di servizio (punto 2); `sh carica.sh`.

## Costi
Claude: una chiamata per report, pochi centesimi (`claude-opus-5-5`, sforzo medio). Google: gratis. Brevo: 1 email.
