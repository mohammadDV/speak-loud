# Grammar lessons data

JSON content for Learning courses, lessons, and grammar quiz question banks.

## Files

| File | Purpose |
|------|---------|
| `german_a1_grammar_course.json` | Course + lesson metadata and localized bodies (`en/es/fa/ar/tr`) |
| `german_grammar_lesson_01.md` / `*_01_questions.json` / `generate_lesson_01_questions.py` | Pronouns & present tense |
| `german_grammar_lesson_02.md` / `*_02_questions.json` / `generate_lesson_02_questions.py` | Modal verbs |
| `german_grammar_lesson_03.md` / `*_03_questions.json` / `generate_lesson_03_questions.py` | Questions & word order |
| `german_grammar_lesson_04.md` / `*_04_questions.json` / `generate_lesson_04_questions.py` | Plurals, articles & negation |
| `german_grammar_lesson_05.md` / `*_05_questions.json` / `generate_lesson_05_questions.py` | Cases, pronouns & objects |
| `german_grammar_lesson_06.md` / `*_06_questions.json` / `generate_lesson_06_questions.py` | Prepositions: time & place |
| `update_course_lesson_0N.py` | Upsert localized lesson N into the course JSON |

## Import

```bash
php artisan lessons:import
# or
php artisan lessons:import database/data/lessons --dry-run
php artisan lessons:import --prune
```

Directory import loads the course file first, then every `*_questions.json` file (sorted).

Upserts are idempotent on course/lesson `code` and question `import_key`.
