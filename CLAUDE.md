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
Tag Manager (27/10/2026): `gtm.php` (solo lettura, scope tagmanager.readonly in `lib/google.php`) elenca i contenitori visibili all'account di servizio; `--contenitore=GTM-XXXX` scrive su stderr il riepilogo della versione Live (tag con tipo, attivatori, pausa, consenso) e su stdout il JSON completo (copia in `uscite/gtm/`). Serve: account di servizio aggiunto in GTM (Gestione utenti del contenitore, Lettura) + Tag Manager API abilitata nel progetto report-clienti-511219 (fatto dall'utente il 27/10 per Veleco GTM-MZBP64KK). Dal Mac: `ssh identia "cd report-clienti && php gtm.php --contenitore=GTM-MZBP64KK" > ~/Desktop/gtm-veleco.json`.
Dati GA4 settimanali per il report social (2/11/2026): `ga4-settimanale.php` (solo lettura, stessa chiave e stessa `ga4()` del report mensile) scarica da GA4 di un cliente (predefinito `tuosito`, prop. 556336111) la settimana lunedì-domenica appena chiusa **e quella prima**: totali, solo sessioni social (canale Organic/Paid Social di GA4, che include Facebook e Instagram senza UTM, oppure medium social), sessioni/utenti/sessioni coinvolte per sessionSource/sessionMedium/sessionCampaignName/sessionDefaultChannelGroup, eventi (eventName, eventCount), conteggio degli eventi di `clienti.php` (+ `prova_gratuita`) sulle sole sessioni social e per campagna. Scrive `ga4-latest.json` e `ga4-AAAA-Wnn.json` (numero ISO = «Sett. NN» di Notion) in `~/domains/<sito>/public_html/_report-privato/` con `.htaccess` «deny all»; dopo la scrittura controlla da fuori che l'indirizzo risponda 403 (se risponde 200 cancella i file ed esce con errore; con 404 avvisa che la cartella potrebbe non essere sotto la document root). In caso di errore `ga4-latest.json` ha `stato: errore` + `messaggio` e parte un'email a CAME; log in `uscite/ga4-settimanale.log`. Lo legge il «Report social settimanale» (attività programmata di Claude, lunedì 7:45) con lo strumento Hostinger `hosting_files_website-content` (dal filesystem, quindi il deny-all non lo blocca). Cron in hPanel: `30 7 * * 1 /usr/bin/php /home/u745278502/report-clienti/ga4-settimanale.php >/dev/null 2>&1`. Opzioni: `--settimana=AAAA-MM-GG`, `--stampa` (JSON su stdout, non scrive), `--diagnostica` (eventi, sorgenti, auto-referral e domini degli ultimi 30 giorni: serve a vedere cosa arriva davvero a GA4). Cartella e indirizzo di controllo si cambiano con `GA4_SETTIMANALE_CARTELLA` / `GA4_SETTIMANALE_URL` in `config.php`. Analytics conta solo chi accetta i cookie (Consent Mode): i clic sono meno di quelli di Metricool.
Senza credito Claude: `--solo-dati` salva i numeri in `uscite/<cliente>/<periodo>-dati.json`; i testi li scrive Claude Code e crea il PDF in locale con `crea_pdf()`.
In locale: `php report.php --cliente=identia --prova --solo-pdf` con `prova/claude-finto.php` su 127.0.0.1:8770
(config.php di prova con CLAUDE_API_URL/BREVO_API_URL finti). Google, Claude e MySQL veri non sono raggiungibili da Claude Code.

## Stato (11/10/2026)
- Funzionano e provati sul server: identia (anche lavori di ottobre), recruiting (Selezione agenti Dojo).
- Collegati, primo report il 4/11/2026: be-first, came, mauriziocamerada, mvrantincendio (prop. 344604720),
  edoardocamerada, motoracer, ferramentagenova, marcofirinu, veleco (referente Valentina Colomba), supzena, tuosito (prop. 556336111).
- Aggiunto l'11/10/2026: postavecchia (Posta Vecchia Cocktail Bar & Tequileria, cocktailbarpostavecchia.it, prop. 491108573, stream G-RLLZ2T2340;
  sito nel repo camerada/claude in su-misura/postavecchia, online dall'11/10/2026), con `lavori/postavecchia/2026-10.md`.
  Accesso Visualizzatore all'account di servizio dato dall'utente e verificato il 23/10/2026 sul server (`--solo-dati` dall'11 al 22/10 riuscito);
  programma aggiornato sul server lo stesso giorno. Search Console non ancora attiva (il report la salta).
- Report approvati dall'utente («report ok»).
- 4/11/2026: niente più «prossimi passi» (sembravano lavori già decisi): il report finisce con «Cosa si potrebbe migliorare» (consigli come proposte, chiave `consigli`) e il riquadro «Vuoi approfondire i consigli per metterli in pratica?» con pulsante WhatsApp cliccabile (messaggio precompilato con sito e periodo, `WHATSAPP_CAME` in config.php del server, link anche nell email al cliente) + email. Contatti dal sito = «tocchi», non chiamate.
- Da fare quando l'utente lo chiede: file `lavori/` per gli altri clienti, referenti in copertina, passaggio ad `automatico`.

## Repo collegati
- `camerada/identia`: sito identia.it (Analytics, eventi, sitemap). Seguito in un'altra chat.
- `camerada/dojotalentcheck`: landing recruitingagenti + test dojotalentcheck (config.php e vendor/ fuori dal repo).
- `camerada/claude`: TuoSito/vetrina.si.
