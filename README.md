# 🏉 Designatore

Applicazione web per la gestione delle designazioni arbitrali nel rugby. Permette al designatore (o a un suo delegato) di programmare partite singole oltre a **Concentramenti** e **Tornei** multi-squadra, assegnare uno o più arbitri con il relativo ruolo e notificarli via email con un link per accettare o rifiutare la designazione.

---

## Funzionalità principali

### Dashboard pubblica
Accessibile senza autenticazione. Mostra le prossime partite programmate con lo stato della designazione arbitrale. Pensata per essere condivisa con dirigenti e tifosi.

### Autenticazione
L'accesso alle funzioni gestionali è riservato a utenti con ruolo **designatore** o **delegato**. La registrazione pubblica è disabilitata: gli account vengono creati dall'amministratore.

### Gestione arbitri
- Anagrafica completa (nome, email, telefono, livello di patente, disponibilità)
- Livelli supportati: Locale, Regionale, Nazionale, Internazionale
- Stato disponibilità: Disponibile, Limitata, Non disponibile

### Gestione squadre
- Anagrafica squadre con città, campionato/divisione e riferimenti del contatto (opzionali)
- Ordinamento per nome o campionato tramite icone nelle intestazioni della tabella
- Filtro per campionato

### Gestione partite ed eventi
- Programmazione con data/ora, campo, squadre e tipo di competizione
- Default intelligente sulla domenica più prossima (ore 14:30 in ora legale, 15:30 in ora solare)
- Selezione guidata: prima si sceglie il campionato, poi le squadre di quel campionato
- **Concentramenti e Tornei**: eventi che coinvolgono **3 o più squadre**, identificati da un **nome** descrittivo (es. "Concentramento U14 - Roma"). Il form si adatta automaticamente, mostrando la selezione multipla delle squadre al posto del classico casa/ospite
- Controllo automatico dei conflitti: una squadra non può essere impegnata in due incontri nello stesso giorno — vale anche per le squadre coinvolte in Concentramenti e Tornei
- Tipi di competizione: Campionato, Coppa, Amichevole, Internazionale, Torneo (Tournament) e i due tipi multi-squadra **Concentramento** e **Torneo**
- Stati: Programmata, Rinviata, Annullata, Completata

### Designazioni
- Vista settimanale (lunedì–domenica) con navigazione prev/next
- Mostra **tutte** le partite e gli eventi della settimana, compresi quelli senza arbitro assegnato
- **Più arbitri per incontro**, ciascuno con un **ruolo**: Arbitro, Assistente 1, Assistente 2, Osservatore, 4° uomo, 5° uomo, Tutor
- Nelle partite singole ogni ruolo è assegnabile una sola volta; nei Concentramenti e Tornei lo stesso ruolo (es. più "Arbitro") può ripetersi
- Gli incontri senza designazione sono evidenziati con un pulsante "Designa" diretto
- Azioni per designazione esistente: Dettaglio, Modifica, Elimina

### Notifiche email agli arbitri
Quando una designazione viene creata, l'arbitro riceve automaticamente un'email con:
- Dettagli dell'incontro (squadre o nome evento, ruolo assegnato, data, campo, competizione, note)
- **Link per accettare** la designazione → cambia lo stato in *Confermata*
- **Link per rifiutare** la designazione → cambia lo stato in *Annullata*

I link sono firmati con URL sicuri (Laravel Signed Routes) e non richiedono che l'arbitro abbia un account nell'applicazione. Se una designazione viene **rifiutata**, il designatore che l'ha creata riceve a sua volta un'email di notifica.

Se un arbitro non risponde entro 24 ore, riceve automaticamente un sollecito (stessa email, reinviata), ripetuto ogni 24 ore finché non conferma o rifiuta. Gestito dal comando schedulato `designations:send-reminders` (`routes/console.php`, esecuzione oraria) — in ambiente Docker richiede il servizio `scheduler` (già incluso in `docker-compose.yml`); in locale serve un cron/`schedule:work` attivo perché venga eseguito.

### Report designazioni
Esportazione delle designazioni in tre formati:

| Formato | Utilizzo |
|---------|----------|
| **PDF** | Documento A4 formattato con tabella e riepilogo, pronto per la stampa |
| **Markdown** | Tabella `.md` compatibile con Notion, GitHub, Obsidian |
| **Testo** | Formato con emoji per Telegram e WhatsApp, copiato negli appunti con un click |

I report supportano filtri per intervallo di date della partita e stato della designazione. Il range predefinito è il prossimo fine settimana (sabato–domenica).

---

## Stack tecnologico

| Layer | Tecnologia |
|-------|-----------|
| Backend | PHP 8.3 · Laravel 13 |
| Frontend | Blade · Tailwind CSS · Alpine.js |
| Database | SQLite (sviluppo locale) · MySQL 8.4 (Docker) |
| Cache/queue | Redis (Docker) |
| PDF | barryvdh/laravel-dompdf |
| Auth | Laravel Breeze (Blade stack) |
| Test | Pest |
| Dev server | Laravel Herd · Docker Compose |

---

## Installazione

### Requisiti
- PHP ≥ 8.3
- Composer
- Node.js ≥ 18 + npm
- Laravel Herd (opzionale, consigliato su macOS/Windows)

### Avvio rapido

```bash
git clone <repository-url> designatore
cd designatore

# Installa dipendenze, configura .env, esegui migrazioni e build assets
composer run setup
```

### Oppure manualmente

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
php artisan db:seed          # crea utente admin e dati di esempio
npm install
npm run build
```

### Avvio del server di sviluppo

```bash
composer run dev
```

Avvia in parallelo: server Laravel, queue worker e Vite dev server.

---

## Avvio con Docker

In alternativa all'installazione locale, l'app può essere eseguita interamente tramite Docker Compose (Nginx + PHP-FPM + MySQL + Redis).

### Requisiti
- Docker e Docker Compose

### Avvio

```bash
cp .env.docker.example .env.docker
cp .env.example .env
```

Nel file `.env` imposta le variabili del database in modo che puntino ai servizi Docker (coerenti con `.env.docker`):

```env
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=laravel
DB_PASSWORD=secret
REDIS_HOST=redis
```

Poi avvia i container:

```bash
docker compose --env-file .env.docker up -d --build
```

Al primo avvio, esegui dentro il container `app` i comandi di setup:

```bash
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
```

L'applicazione sarà raggiungibile su `http://localhost:8080` (porta configurabile con `APP_PORT` in `.env.docker`).

> Le dipendenze Composer e gli asset Vite vengono compilati durante la build dell'immagine e seminati automaticamente nel container ad ogni avvio (vedi `docker/php/entrypoint.sh`): non serve eseguire `composer install` o `npm run build` a mano dentro i container.

I servizi avviati sono:

| Servizio | Descrizione |
|----------|-------------|
| `app` | PHP-FPM 8.3 (Laravel) |
| `nginx` | Web server, esposto su `APP_PORT` |
| `mysql` | Database MySQL 8.4 |
| `redis` | Cache/queue driver |
| `queue` | Worker per `php artisan queue:work` |
| `scheduler` | Esegue `php artisan schedule:run` ogni minuto (solleciti designazioni, ecc.) |
| `proxy` | Nginx Proxy Manager, esposto su `PROXY_HTTP_PORT`/`PROXY_HTTPS_PORT`/`PROXY_ADMIN_PORT` |

### Dominio e HTTPS con Nginx Proxy Manager

Il servizio `proxy` (Nginx Proxy Manager) espone le porte 80, 443 e 81 (pannello di amministrazione) e fa da reverse proxy davanti al servizio `nginx` interno, permettendo di legare un dominio e generare certificati Let's Encrypt.

1. Punta il DNS del dominio (record A) verso l'IP pubblico del server, sulle porte 80/443.
2. Apri il pannello di amministrazione su `http://<host>:81` (credenziali di default alla prima apertura: `admin@example.com` / `changeme`, da cambiare subito).
3. Crea un nuovo **Proxy Host**:
   - Domain Names: il tuo dominio
   - Forward Hostname/IP: `nginx`
   - Forward Port: `80`
   - Abilita **Block Common Exploits**
4. Nella tab **SSL**, richiedi un nuovo certificato **Let's Encrypt**, abilita **Force SSL** e accetta i termini.

Le porte esposte da Nginx Proxy Manager sono configurabili in `.env.docker` (`PROXY_HTTP_PORT`, `PROXY_HTTPS_PORT`, `PROXY_ADMIN_PORT`); i suoi dati (configurazione e certificati) sono persistiti nei volumi `proxy_data` e `proxy_letsencrypt`.

Per fermare i container:

```bash
docker compose down
```

---

## Credenziali di default

| Campo | Valore |
|-------|--------|
| Email | `test@example.com` |
| Password | `password` |
| Ruolo | `designatore` |

> ⚠️ Cambiare le credenziali prima di mettere in produzione.

---

## Configurazione email

In sviluppo le email vengono scritte nel log (`storage/logs/laravel.log`). Per inviare email reali, configurare le variabili `MAIL_*` nel file `.env`:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_FROM_ADDRESS=designatore@example.com
MAIL_FROM_NAME="Designatore Rugby"
```

### Invio via Gmail API (OAuth2)

In alternativa a SMTP, l'app supporta un mailer `gmail` che invia le email tramite Gmail API usando `google/apiclient`, con un transport Symfony Mailer custom (`app/Mail/Transport/GmailApiTransport.php`) registrato in `AppServiceProvider`. Utile per usare direttamente una casella Gmail/Workspace senza password per app né relay SMTP.

> Nota: Google **non** supporta gli scope Gmail (né altri scope sensibili) tramite il Device Authorization Grant ("inserisci questo codice su google.com/device") — quel flusso funziona solo per un set ristretto di API a basso rischio. Per Gmail va usato il classico Authorization Code flow con redirect, gestito dal comando `gmail:authorize` qui sotto tramite un piccolo listener locale su porta fissa.

1. Su [Google Cloud Console](https://console.cloud.google.com/), crea un progetto, abilita la **Gmail API** e crea una credenziale OAuth2 di tipo **App desktop** (`Desktop app`). Annota `Client ID` e `Client secret`.
2. Nella **schermata consenso OAuth** del progetto:
   - in **Ambiti** (Scopes), aggiungi lo scope `.../auth/gmail.send` (Gmail API);
   - se l'app è in stato **Testing**, aggiungi in **Utenti di test** l'indirizzo Gmail da cui vuoi inviare le notifiche (altrimenti Google rifiuta l'autorizzazione).
3. Configura `.env`:
   ```env
   MAIL_MAILER=gmail
   GMAIL_CLIENT_ID=...
   GMAIL_CLIENT_SECRET=...
   ```
4. Genera il refresh token (una tantum), accedendo con l'account Google autorizzato al passo 2:
   ```bash
   php artisan gmail:authorize
   # oppure, se l'app gira in Docker:
   docker compose exec app php artisan gmail:authorize
   ```
   Il comando apre un listener sulla porta `8901` (già pubblicata dal servizio `app` in `docker-compose.yml`, configurabile con `GMAIL_AUTH_PORT` in `.env.docker`) e stampa l'URL di autorizzazione. Se lo esegui su un **server remoto senza browser**, apri prima un tunnel SSH dal tuo computer:
   ```bash
   ssh -L 8901:localhost:8901 <utente>@<host-remoto>
   ```
   poi apri l'URL stampato dal comando nel browser locale: dopo il consenso, il comando riceve il redirect e stampa il valore da copiare in `.env`:
   ```env
   GMAIL_REFRESH_TOKEN=...
   ```
   Se il comando gira dentro un container Docker, ricorda che `.env` è condiviso via bind mount con l'host: dopo averlo aggiornato basta un `docker compose restart app queue` perché i container lo rileggano.

L'account Google autorizzato deve avere accesso allo scope `gmail.send`; non serve alcuna configurazione SMTP.

---

## Struttura delle route principali

| Route | Accesso | Descrizione |
|-------|---------|-------------|
| `GET /` | Pubblico | Dashboard partite in programma |
| `GET /login` | Guest | Pagina di accesso |
| `GET /dashboard` | Auth | Dashboard privata con statistiche |
| `GET /referees` | Auth | Elenco arbitri |
| `GET /teams` | Auth | Elenco squadre |
| `GET /rugby-matches` | Auth | Elenco partite |
| `GET /designations` | Auth | Vista settimanale designazioni |
| `GET /reports` | Auth | Generazione report |
| `GET /designations/{id}/respond/{action}` | Pubblico (firmato) | Accetta/rifiuta designazione |
