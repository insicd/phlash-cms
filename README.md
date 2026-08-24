# Phlash

CMS per community news in **PHP + MySQL**, pensato per hosting shared: niente Composer, niente Node, niente code da compilare. Si carica la cartella sul server, si crea un database, si apre `install.php`.

L’impronta è quella di **Slashdot** (storie lunghe, dipartimenti, commenti annidati, Codardo Anonimo, phlashbox in colonna) con la coda di promozione in stile **Pligg** (le storie partono da *In arrivo* e salgono in homepage con i voti).

## Requisiti

- PHP 7.4 o superiore (PDO MySQL, mbstring)
- MySQL 5.7+ / MariaDB 10.2+
- Apache con `mod_rewrite` (la maggior parte dei shared) **oppure** PHP built-in server in locale

## Installazione su hosting

1. Crea un database MySQL vuoto dal pannello (cPanel, Plesk, ecc.).
2. Carica tutti i file di questa cartella nella document root (o in una sottocartella).
3. Assicurati che la directory sia scrivibile il tempo dell’installazione (serve per creare `config.php`).
4. Apri `https://tuodominio.tld/install.php` e compila host, database, utente, password, account admin.
5. Dopo l’ok, **cancella o rinomina `install.php`**.
6. Se le URL amichevoli non funzionano, l’host probabilmente non ha `mod_rewrite`. In quel caso usa `index.php?r=/storia/slug` oppure chiedi di abilitare rewrite.

`PHLASH_BASE_URL` in `config.php` deve essere l’URL pubblico senza slash finale. L’installer lo propone da solo.

## Sviluppo in locale

```bash
php -S localhost:8080 server.php
```

Poi apri `http://localhost:8080/install.php`. Serve un MySQL in ascolto (MAMP, DBngin, MariaDB locale, Docker, ecc.).

## Come è fatto

- **Template PHP** in `templates/` — nessun Twig/Smarty/Composer
- **Front controller** `index.php` + router minimale
- **Markdown** ristretto per le storie (niente HTML libero)
- **Commenti** in testo, anche anonimi, con captcha aritmetico se non sei loggato
- **Invio storie** solo utenti registrati; coda *In arrivo* + soglia di promozione (default 5 voti)
- **API** in `/api/v1` con token: si possono creare storie solo in *In arrivo* (vedi `docs/api.md`)
- **Admin** in `/admin`: storie, commenti, utenti, sezioni, sondaggio, statistiche visite (Chart.js in locale, senza CDN), impostazioni

Chi è loggato può comunque spuntare «Pubblica come Codardo Anonimo».

## Permessi

Su shared hosting tipico:

```
directory  755
file       644
```

`config.php` contiene le credenziali: non va in git (è già in `.gitignore`).
