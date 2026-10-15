# Report clienti — CAME Comunicazione

Report mensile in PDF dei siti dei clienti di CAME Comunicazione (Maurizio Camerada, Genova).
Testi, commenti e interfaccia in italiano. PHP puro, nessuna dipendenza (niente composer).

## Come funziona
`report.php` → per ogni cliente in `clienti.php`:
1. dati: Google Analytics Data API + Search Console (`lib/google.php`, `lib/dati.php`, account di servizio
   `report-clienti@report-clienti-511219.iam.gserviceaccount.com`), oppure per il cliente `recruiting`
   il database MySQL del test Dojo (`lib/imbuto.php`, solo totali, mai dati personali);
2. testi: Claude (`lib/testo.php`, `claude-opus-5-5`, structured outputs, effort medium, `fallbacks: default`), si dà del **tu**;
3. PDF: motore scritto a mano (`lib/pdf.php`, TrueType incorporati da `risorse/`, logo CAME) +
   impaginazione nello stile dei resoconti CAME (`lib/impagina.php`: copertina petrolio #00596D / ciano #0092A9 / lime #A4D233,
   Bricolage Grotesque + Inter Tight + Space Mono). Riferimento visivo: il resoconto di Veleco di ottobre 2026;
4. email con Brevo (`lib/email.php`) da info@camecomunicazione.it.

`automatico => false` (tutti, per ora): il PDF va solo a `EMAIL_CAME` (info@camecomunicazione.it) da controllare;
`true` + `email` del cliente: va al cliente con CAME in copia. Registro in `uscite/registro.csv` (niente doppioni).
Lavoro fatto nel mese (facoltativo): `lavori/<cliente>/<AAAA-MM>.md` (formato in `lavori/LEGGIMI.md`).

## Server
Hostinger, alias SSH `identia` (utente u745278502), cartella `~/report-clienti` fuori dai siti.
`config.php` e `chiave-google.json` solo sul server (chiavi Claude e Brevo copiate da vetrina.si da `installa.sh`).
Cron in hPanel (uno solo): `12 8 4 * * /usr/bin/php /home/u745278502/report-clienti/report.php --tutti`.
Aggiornare il server dal Mac: `cd ~/report-clienti && git pull && sh carica.sh` (chiede conferma, non cancella).
Prova: `ssh identia "cd report-clienti && php report.php --cliente=<id> --mese=AAAA-MM"`.
Parte di un mese: `--dal=AAAA-MM-GG --al=AAAA-MM-GG` (stesso mese; confronto con gli stessi giorni del mese prima, file `<cliente>/<dal>_<al>`).
Analisi approfondita di un cliente: `esporta.php --cliente=<id> [--dal=AAAA-MM-GG]` scrive su stdout un JSON grezzo (Analytics da 12 mesi: canali per mese, fonti, pagine di arrivo, eventi, contatti per giorno/pagina/fonte/città, città, dispositivi, età; Search Console 16 mesi: mesi, ricerche, pagine, ricerca+pagina, paesi, dispositivi), copia in `uscite/<id>/analisi-<data>.json`. Dal Mac: `ssh identia "cd report-clienti && php esporta.php --cliente=veleco" > ~/Desktop/veleco-dati.json`, poi il file si manda a Claude Code.
Senza credito Claude: `--solo-dati` salva i numeri in `uscite/<cliente>/<periodo>-dati.json`; i testi li scrive Claude Code e crea il PDF in locale con `crea_pdf()`.
In locale: `php report.php --cliente=identia --prova --solo-pdf` con `prova/claude-finto.php` su 127.0.0.1:8770
(config.php di prova con CLAUDE_API_URL/BREVO_API_URL finti). Google, Claude e MySQL veri non sono raggiungibili da Claude Code.

## Stato (11/10/2026)
- Funzionano e provati sul server: identia (anche lavori di ottobre), recruiting (Selezione agenti Dojo).
- Collegati, primo report il 4/11/2026: be-first, came, mauriziocamerada, mvrantincendio (prop. 344604720),
  edoardocamerada, motoracer, ferramentagenova, marcofirinu, veleco (referente Valentina Colomba), supzena, tuosito (prop. 556336111).
- Aggiunto l'11/10/2026: postavecchia (Posta Vecchia Cocktail Bar & Tequileria, cocktailbarpostavecchia.it, prop. 491108573, stream G-RLLZ2T2340;
  sito nel repo camerada/claude in su-misura/postavecchia, online dall'11/10/2026), con `lavori/postavecchia/2026-10.md`.
  Da fare dall'utente: dare accesso Visualizzatore all'account di servizio nella proprietà 491108573; Search Console non ancora attiva (il report la salta).
- Report approvati dall'utente («report ok»).
- Da fare quando l'utente lo chiede: file `lavori/` per gli altri clienti, referenti in copertina, passaggio ad `automatico`.

## Repo collegati
- `camerada/identia`: sito identia.it (Analytics, eventi, sitemap). Seguito in un'altra chat.
- `camerada/dojotalentcheck`: landing recruitingagenti + test dojotalentcheck (config.php e vendor/ fuori dal repo).
- `camerada/claude`: TuoSito/vetrina.si.
