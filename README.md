# 🏉 Designatore

Applicazione web per la gestione delle designazioni arbitrali nel rugby. Permette al designatore (o a un suo delegato) di programmare le partite, assegnare gli arbitri e notificarli via email con un link per accettare o rifiutare la designazione.

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

### Gestione partite
- Programmazione con data/ora, campo, squadre e tipo di competizione
- Default intelligente sulla domenica più prossima (ore 14:30 in ora legale, 15:30 in ora solare)
- Selezione guidata: prima si sceglie il campionato, poi le squadre di quel campionato
- Controllo automatico dei conflitti: non è possibile assegnare una squadra a due partite nello stesso giorno
- Tipi di competizione: Campionato, Coppa, Amichevole, Internazionale, Torneo
- Stati: Programmata, Rinviata, Annullata, Completata

### Designazioni
- Vista settimanale (lunedì–domenica) con navigazione prev/next
- Mostra **tutte** le partite della settimana, comprese quelle senza arbitro assegnato
- Le partite senza designazione sono evidenziate con un pulsante "Designa" diretto
- Azioni per designazione esistente: Dettaglio, Modifica, Elimina

### Notifiche email agli arbitri
Quando una designazione viene creata, l'arbitro riceve automaticamente un'email con:
- Dettagli della partita (squadre, data, campo, competizione, note)
- **Link per accettare** la designazione → cambia lo stato in *Confermata*
- **Link per rifiutare** la designazione → cambia lo stato in *Annullata*

I link sono firmati con URL sicuri (Laravel Signed Routes) e non richiedono che l'arbitro abbia un account nell'applicazione.

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
| Database | SQLite (sviluppo) |
| PDF | barryvdh/laravel-dompdf |
| Auth | Laravel Breeze (Blade stack) |
| Test | Pest |
| Dev server | Laravel Herd |

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
