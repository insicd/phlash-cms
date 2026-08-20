# Importatore Blogger

Legge l’export **Google Takeout** in `Takeout/` e crea storie Phlash: Markdown, tag dalle etichette, date originali, media locali in `uploads/import/`. I post importati **non** hanno fonte: diventano contenuti del sito.

Le immagini Google (`blogger.googleusercontent.com`, `bp.blogspot.com`) vengono copiate dal Takeout quando c’è un filename, altrimenti scaricate e salvate in `uploads/import/`.

## Uso da terminale

```bash
php tools/blogger-importer/import.php --list
php tools/blogger-importer/import.php --blog="ilGlobale.it" --dry-run --limit=5
php tools/blogger-importer/import.php --blog="ilGlobale.it"
php tools/blogger-importer/import.php --repair
```

`--repair` toglie le fonti dai post già importati e riscrive gli URL Google verso `uploads/`.

Altre opzioni: `--user=admin` `--site-url=https://www.ilglobale.it` `--include-drafts` `--include-spam`

## Uso da browser

Dal pannello admin: **Importa da Blogger (Takeout)**. La prima volta conviene la simulazione. Per un import già fatto usa **Ripara import esistenti**.

I post già presenti (stesso slug del filename Blogger) vengono saltati. I commenti spam del feed sono esclusi, salvo `--include-spam`.
Le sezioni Phlash si scelgono dalle etichette (Tecnologia, Cultura, Politica, …); il resto va in Notizie. Tutte le etichette diventano tag.
