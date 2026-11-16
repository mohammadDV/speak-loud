# Vocabulary import files

Versioned CEFR vocabulary JSON for `dictionary:import`.

These files live under `database/data/vocabulary/` (not `storage/`) so they are committed to git and available on deploy.

| File | Lexemes |
|------|---------|
| `a1_german_words.json` | 2024 |
| `a2_german_words.json` | 932 |
| `b1_german_words.json` | 1099 |
| `b2_german_words.json` | 446 |
| `c1_german_words.json` | 408 |
| `c2_german_words.json` | 333 |

Flat source backups: `*_german_words.flat.json`

## Translation languages

Flat sources and import JSON include sense/example translations for:

- `en` (english / example_en)
- `es` (spanish / example_es)
- `fa` (persian / example_fa)
- `ar` (arabic / example_ar)
- `tr` (turkish / example_tr)

Fill or refresh flat translations (Argos offline MT from English):

```bash
# requires a venv with argostranslate + en→{es,fa,ar,tr} packages installed
python3 database/data/vocabulary/fill_flat_translations.py
python3 database/data/vocabulary/fill_flat_translations.py --force   # overwrite
python3 database/data/vocabulary/fill_flat_translations.py A1 A2
```

Then regenerate import JSON and re-import (idempotent via `import_key`).

## Local / production

```bash
php artisan db:seed --class=LanguageSeeder
php artisan db:seed --class=DictionaryReferenceSeeder

# one file
php artisan dictionary:import database/data/vocabulary/a1_german_words.json

# all levels (default path)
php artisan dictionary:import --all

# one level from the folder
php artisan dictionary:import --level=B1

# validate only
php artisan dictionary:import --all --dry-run
```

Re-run after correcting sentences, adding collocations, or adding translation languages. Imports are idempotent via `import_key`.

After import, bump article-quiz caches if needed:

```bash
php artisan tinker --execute 'app(\App\Domain\Learning\Services\ArticleQuizCache::class)->forgetPools();'
```

## Regenerate import JSON from flat sources

```bash
# all levels
python3 database/data/vocabulary/transform_level_to_lexemes.py

# one level
python3 database/data/vocabulary/transform_level_to_lexemes.py A2
```
