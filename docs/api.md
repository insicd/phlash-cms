# API Phlash (v1)

Autenticazione con **token personale**. Le storie create via API finiscono **sempre** in *In arrivo* (`pending`): non esiste un parametro per pubblicarle in homepage. La promozione resta ai voti della comunità o a un admin.

Base URL: l’indirizzo pubblico del sito, senza slash finale. Esempio: `https://www.ilglobale.it`

## 1. Crea un token

1. Accedi con l’utente che deve firmare i post (consigliato un account dedicato all’auto-posting).
2. Apri il tuo profilo → sezione **API** → **Genera token**.
3. Copia il valore `phl_…` e conservalo. Viene mostrato una sola volta.
4. Un admin può creare/revocare token anche da **Admin → Utenti**.

Il token è un segreto: non metterlo in git, nei log o nelle query string.

## 2. Autenticazione

Su ogni richiesta (tranne `GET /api/v1`):

```
Authorization: Bearer phl_IL_TUO_TOKEN
```

Su alcuni hosting shared l’header `Authorization` viene strippato. In quel caso usa:

```
X-Phlash-Token: phl_IL_TUO_TOKEN
```

Risposta senza token o con token errato: `401` e `{"ok":false,"error":"…"}`.

## 3. Endpoint

| Metodo | Percorso | Auth | Uso |
|--------|----------|------|-----|
| GET | `/api/v1` | no | Elenco endpoint |
| GET | `/api/v1/me` | sì | Verifica il token |
| GET | `/api/v1/topics` | sì | Sezioni (slug da usare in `topic`) |
| POST | `/api/v1/stories` | sì | Crea una storia in coda |
| GET | `/api/v1/stories/{id}` | sì | Stato della storia (solo propri post, o admin) |

### `POST /api/v1/stories`

`Content-Type: application/json`

| Campo | Obbligatorio | Note |
|-------|----------------|------|
| `title` | sì | 8–200 caratteri |
| `body` | sì | Markdown, minimo 80 caratteri |
| `topic` | sì* | Slug sezione, es. `tecnologia` |
| `topic_id` | sì* | Alternativa numerica a `topic` |
| `tags` | no | Stringa `"a, b"` oppure array `["a","b"]` |
| `dept` | no | Dipartimento (max 80); se vuoto usa il nome sezione |
| `source_url` | no | `http://` o `https://` |

\* Serve **uno** tra `topic` e `topic_id`. Eventuali campi `status` / `published` vengono ignorati.

Risposta `201`:

```json
{
  "ok": true,
  "story": {
    "id": 1234,
    "title": "…",
    "slug": "…",
    "status": "pending",
    "topic": "tecnologia",
    "url": "https://esempio.tld/storia/…",
    "upcoming_url": "https://esempio.tld/upcoming"
  }
}
```

Limite: **20 storie all’ora** per utente (`429` se superato).

Errori di validazione: `422`. Storia altrui: `404`.

## 4. Esempi per un auto-poster

### curl

```bash
BASE=https://www.ilglobale.it
TOKEN=phl_…

# verifica
curl -sS -H "Authorization: Bearer $TOKEN" "$BASE/api/v1/me"

# sezioni
curl -sS -H "Authorization: Bearer $TOKEN" "$BASE/api/v1/topics"

# crea storia (sempre in arrivo)
curl -sS -X POST "$BASE/api/v1/stories" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Titolo della storia di prova automatica",
    "body": "Testo in Markdown. Serve un paragrafo vero, non un link nudo: almeno ottanta caratteri per passare la validazione del CMS.",
    "topic": "tecnologia",
    "tags": ["api", "test"],
    "source_url": "https://esempio.tld/articolo-originale"
  }'
```

Se `Authorization` non arriva al PHP:

```bash
curl -sS -X POST "$BASE/api/v1/stories" \
  -H "X-Phlash-Token: $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{ ... }'
```

Senza rewrite Apache, lo stesso percorso è `https://esempio.tld/index.php?r=/api/v1/stories`.

### Python

```python
import os
import requests

BASE = os.environ["PHLASH_URL"].rstrip("/")
TOKEN = os.environ["PHLASH_TOKEN"]
headers = {
    "Authorization": f"Bearer {TOKEN}",
    "Content-Type": "application/json",
}

r = requests.post(
    f"{BASE}/api/v1/stories",
    headers=headers,
    json={
        "title": "Titolo della storia di prova automatica",
        "body": "Testo in Markdown. Serve un paragrafo vero, non un link nudo: almeno ottanta caratteri per passare la validazione del CMS.",
        "topic": "tecnologia",
        "tags": ["api", "autopost"],
    },
    timeout=30,
)
r.raise_for_status()
story = r.json()["story"]
assert story["status"] == "pending"
print(story["id"], story["url"], story["upcoming_url"])
```

Istruzioni per il bot: autenticarsi col token, leggere gli slug da `GET /api/v1/topics`, inviare solo `POST /api/v1/stories`, non tentare di forzare `published`, trattare `429` con un backoff, conservare `story.id` per `GET /api/v1/stories/{id}` se serve sapere se è stata promossa.

## 5. Cosa non fa l’API

- Non pubblica in homepage.
- Non modifica o cancella storie.
- Non vota e non commenta.
- Non accetta HTML grezzo nel `body` (è Markdown come dal form web).
