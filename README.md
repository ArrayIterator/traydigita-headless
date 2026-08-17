# TRAYDIGITA HEADLESS


## Translations

Create `.pot` file

```bash
wp i18n make-pot . languages/traydigita.pot --domain=traydigita
```

Create translations from `.pot` (e.g.: `id_ID`)

```bash
msgmerge --update languages/traydigita-id_ID.po languages/traydigita.pot
```

Create `.mo` files

```bash
wp i18n make-mo languages/
```

Create `.json` (JavaScript i18n) translations

This only create JavaScript based i18n

```bash
wp i18n make-json languages/
```
