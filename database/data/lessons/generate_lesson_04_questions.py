#!/usr/bin/env python3
"""Regenerate german_grammar_lesson_04_questions.json for plurals, articles, negation."""

from __future__ import annotations

import json
import re
from collections import Counter
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
VOCAB_PATH = ROOT / "vocabulary" / "a1_german_words.json"
OUT_PATH = Path(__file__).resolve().parent / "german_grammar_lesson_04_questions.json"

COURSE = "de-grammar-a1"
LESSON = "plurals-articles-negation"
KEY_PREFIX = f"{COURSE}:l04"

PROMPT = {
    "en": {
        "plural": 'What is the plural of "{article} {lemma}"?',
        "plural_article": 'Which definite article do you use with the plural of "{lemma}" (nominative)?',
        "nom_def": 'Which nominative definite article belongs with "{lemma}"?',
        "akk_def": 'Which accusative definite article does "{article} {lemma}" take?',
        "akk_indef": 'Which accusative indefinite article does "{article} {lemma}" take?',
        "first_mention": 'First mention (nominative): Das ist ___ {lemma}.',
        "second_mention": 'Second mention (nominative): ___ {lemma} ist neu.',
        "kein_form": 'Choose the correct kein-form: Ich habe ___ {noun}.',
        "nicht_kein": "Choose the correct negation: {frame}",
        "nullartikel": "Which option is correct for an indefinite plural?",
        "profession": "Which sentence is correct for a profession with sein?",
    },
    "es": {
        "plural": '¿Cuál es el plural de "{article} {lemma}"?',
        "plural_article": '¿Qué artículo definido usas con el plural de "{lemma}" (nominativo)?',
        "nom_def": '¿Qué artículo definido nominativo va con "{lemma}"?',
        "akk_def": '¿Qué artículo definido acusativo lleva "{article} {lemma}"?',
        "akk_indef": '¿Qué artículo indefinido acusativo lleva "{article} {lemma}"?',
        "first_mention": 'Primera mención (nominativo): Das ist ___ {lemma}.',
        "second_mention": 'Segunda mención (nominativo): ___ {lemma} ist neu.',
        "kein_form": 'Elige la forma correcta de kein-: Ich habe ___ {noun}.',
        "nicht_kein": "Elige la negación correcta: {frame}",
        "nullartikel": "¿Qué opción es correcta para un plural indefinido?",
        "profession": "¿Qué frase es correcta para una profesión con sein?",
    },
    "fa": {
        "plural": 'جمع "{article} {lemma}" چیست؟',
        "plural_article": 'برای جمع "{lemma}" (Nominativ) کدام حرف تعریف معین می‌آید؟',
        "nom_def": 'کدام حرف تعریف معین Nominativ با "{lemma}" می‌آید؟',
        "akk_def": '"{article} {lemma}" در Akkusativ کدام حرف تعریف معین می‌گیرد؟',
        "akk_indef": '"{article} {lemma}" در Akkusativ کدام حرف تعریف نامعین می‌گیرد؟',
        "first_mention": 'اولین اشاره (Nominativ): Das ist ___ {lemma}.',
        "second_mention": 'دومین اشاره (Nominativ): ___ {lemma} ist neu.',
        "kein_form": 'شکل درست kein- را انتخاب کنید: Ich habe ___ {noun}.',
        "nicht_kein": "نفی درست را انتخاب کنید: {frame}",
        "nullartikel": "برای جمع نامعین کدام گزینه درست است؟",
        "profession": "برای شغل با sein کدام جمله درست است؟",
    },
    "ar": {
        "plural": 'ما جمع "{article} {lemma}"؟',
        "plural_article": 'أي أداة تعريف تُستخدم مع جمع "{lemma}" (Nominativ)؟',
        "nom_def": 'أي أداة تعريف في Nominativ تناسب "{lemma}"؟',
        "akk_def": 'أي أداة تعريف في Akkusativ تناسب "{article} {lemma}"؟',
        "akk_indef": 'أي أداة نكرة في Akkusativ تناسب "{article} {lemma}"؟',
        "first_mention": 'الذكر الأول (Nominativ): Das ist ___ {lemma}.',
        "second_mention": 'الذكر الثاني (Nominativ): ___ {lemma} ist neu.',
        "kein_form": 'اختر صيغة kein- الصحيحة: Ich habe ___ {noun}.',
        "nicht_kein": "اختر النفي الصحيح: {frame}",
        "nullartikel": "أي خيار صحيح للجمع النكرة؟",
        "profession": "أي جملة صحيحة للمهنة مع sein؟",
    },
    "tr": {
        "plural": '"{article} {lemma}" sözcüğünün çoğulu nedir?',
        "plural_article": '"{lemma}" çoğulunda (Nominativ) hangi belirli artikel kullanılır?',
        "nom_def": '"{lemma}" ile hangi Nominativ belirli artikel gelir?',
        "akk_def": '"{article} {lemma}" için akkuzatif belirli artikel hangisidir?',
        "akk_indef": '"{article} {lemma}" için akkuzatif belirsiz artikel hangisidir?',
        "first_mention": 'İlk söz ediş (Nominativ): Das ist ___ {lemma}.',
        "second_mention": 'İkinci söz ediş (Nominativ): ___ {lemma} ist neu.',
        "kein_form": 'Doğru kein- biçimini seçin: Ich habe ___ {noun}.',
        "nicht_kein": "Doğru olumsuzu seçin: {frame}",
        "nullartikel": "Belirsiz çoğul için doğru seçenek hangisidir?",
        "profession": "sein ile meslek için doğru cümle hangisidir?",
    },
}

SKIP_LEMMAS = {
    # Rare / dialect / not useful A1 drill items
    "Beisl",
    # Days / months — odd with article-introduction drills
    "Montag",
    "Dienstag",
    "Mittwoch",
    "Donnerstag",
    "Freitag",
    "Samstag",
    "Sonntag",
    "Januar",
    "Februar",
    "März",
    "April",
    "Mai",
    "Juni",
    "Juli",
    "August",
    "September",
    "Oktober",
    "November",
    "Dezember",
}

NICHT_KEIN_ITEMS = [
    (
        "keine-kinder",
        "Ich habe ___ Kinder.",
        "keine",
        ["nicht", "kein", "keinen"],
    ),
    (
        "keinen-hunger",
        "Ich habe ___ Hunger.",
        "keinen",
        ["nicht", "keine", "kein"],
    ),
    (
        "kein-auto",
        "Ich habe ___ Auto.",
        "kein",
        ["nicht", "keine", "keinen"],
    ),
    (
        "keine-zeit",
        "Ich habe ___ Zeit.",
        "keine",
        ["nicht", "kein", "keinen"],
    ),
    (
        "keinen-kaffee",
        "Ich möchte ___ Kaffee.",
        "keinen",
        ["nicht", "keine", "kein"],
    ),
    (
        "nicht-verheiratet",
        "Ich bin ___ verheiratet.",
        "nicht",
        ["kein", "keine", "keinen"],
    ),
    (
        "nicht-koeln",
        "Ich wohne ___ in Köln.",
        "nicht",
        ["kein", "keine", "keinen"],
    ),
    (
        "nicht-kommen",
        "Ich komme ___ aus Spanien.",
        "nicht",
        ["kein", "keine", "keinen"],
    ),
    (
        "nicht-meine",
        "Das sind ___ meine Kinder.",
        "nicht",
        ["kein", "keine", "keinen"],
    ),
    (
        "nicht-kino",
        "Wir gehen ___ ins Kino.",
        "nicht",
        ["kein", "keine", "keinen"],
    ),
    (
        "keine-lust",
        "Ich habe ___ Lust zu kochen.",
        "keine",
        ["nicht", "kein", "keinen"],
    ),
    (
        "kein-fleisch",
        "Ich esse ___ Fleisch.",
        "kein",
        ["nicht", "keine", "keinen"],
    ),
    (
        "nicht-kochen",
        "Er kann ___ kochen.",
        "nicht",
        ["kein", "keine", "keinen"],
    ),
    (
        "keinen-freund",
        "Sie hat ___ Freund.",
        "keinen",
        ["nicht", "keine", "kein"],
    ),
    (
        "nicht-zufrieden",
        "Ich bin ___ zufrieden.",
        "nicht",
        ["kein", "keine", "keinen"],
    ),
]

KEIN_NOUNS = [
    ("Hunger", "keinen", "der"),
    ("Durst", "keinen", "der"),
    ("Kaffee", "keinen", "der"),
    ("Hund", "keinen", "der"),
    ("Freund", "keinen", "der"),
    ("Tee", "keinen", "der"),
    ("Geld", "kein", "das"),
    ("Auto", "kein", "das"),
    ("Haus", "kein", "das"),
    ("Handy", "kein", "das"),
    ("Brot", "kein", "das"),
    ("Zeit", "keine", "die"),
    ("Lust", "keine", "die"),
    ("Küche", "keine", "die"),
    ("Wohnung", "keine", "die"),
    ("Katze", "keine", "die"),
    ("Kinder", "keine", "plural"),
    ("Äpfel", "keine", "plural"),
    ("Freunde", "keine", "plural"),
    ("Blumen", "keine", "plural"),
]


def distractors_for(correct: str, pool: list[str], n: int = 3) -> list[str]:
    opts: list[str] = []
    for candidate in pool:
        if candidate != correct and candidate not in opts and str(candidate).strip():
            opts.append(candidate)
        if len(opts) >= n:
            break
    i = 0
    while len(opts) < n:
        cand = f"{correct}-{i + 1}"
        if cand not in opts:
            opts.append(cand)
        i += 1
        if i > 20:
            raise RuntimeError(f"Could not build distractors for {correct!r}")
    return opts[:n]


def wrong_plurals(lemma: str, plural: str) -> list[str]:
    opts: list[str] = []
    for cand in (
        lemma,
        lemma + "e",
        lemma + "en",
        lemma + "er",
        lemma + "s",
        lemma + "n",
        re.sub(r"a", "ä", lemma, count=1) + "e" if "a" in lemma else lemma + "e",
        re.sub(r"u", "ü", lemma, count=1) + "er" if "u" in lemma else lemma + "er",
    ):
        if cand != plural and cand not in opts:
            opts.append(cand)
    return distractors_for(plural, opts)


def akk_def(article: str) -> str:
    return {"der": "den", "das": "das", "die": "die"}[article]


def akk_indef(article: str) -> str:
    return {"der": "einen", "das": "ein", "die": "eine"}[article]


def nom_indef(article: str) -> str:
    return {"der": "ein", "das": "ein", "die": "eine"}[article]


def main() -> None:
    vocab = json.loads(VOCAB_PATH.read_text(encoding="utf-8"))
    nouns: list[dict] = []
    seen: set[str] = set()
    for lexeme in vocab["lexemes"]:
        lemma = lexeme["lemma"]
        if lemma in seen or " " in lemma or "-" in lemma:
            continue
        for sense in lexeme.get("senses", []):
            if sense.get("part_of_speech") != "noun":
                continue
            detail = sense.get("german_noun") or {}
            article = detail.get("article")
            plural = detail.get("plural_form")
            if article not in {"der", "die", "das"}:
                continue
            if not isinstance(plural, str) or not plural.strip():
                continue
            if lemma in SKIP_LEMMAS:
                continue
            plural = plural.strip()
            if "/" in plural or "," in plural or " " in plural:
                continue
            nouns.append(
                {
                    "lemma": lemma,
                    "article": article,
                    "plural": plural,
                    "import_key": sense.get("import_key"),
                }
            )
            seen.add(lemma)
            break

    questions: list[dict] = []

    def add_q(
        import_key: str,
        topic: str,
        lemma: str,
        answer: str,
        distractors: list[str],
        translations: dict,
        meta: dict | None = None,
        sense_key: str | None = None,
    ) -> None:
        clean = distractors_for(answer, distractors, n=3)
        questions.append(
            {
                "import_key": import_key,
                "topic": topic,
                "source_lemma": lemma,
                "sense_import_key": sense_key,
                "answer": answer,
                "distractors": clean,
                "blank_token": "___",
                "meta": meta or {},
                "translations": translations,
            }
        )

    # Prefer well-known A1 nouns first for golden coverage.
    priority = {
        "Kind",
        "Buch",
        "Mann",
        "Frau",
        "Taxi",
        "Auto",
        "Vater",
        "Mutter",
        "Stuhl",
        "Hand",
        "Haus",
        "Tür",
        "Tisch",
        "Lampe",
        "Apfel",
        "Brot",
        "Hund",
        "Katze",
        "Lehrer",
        "Lehrerin",
        "Freund",
        "Freundin",
        "Stadt",
        "Land",
        "Tag",
        "Jahr",
        "Name",
        "Job",
        "Radio",
        "Foto",
        "Problem",
        "Idee",
        "Übung",
        "Frage",
        "Antwort",
        "Computer",
        "Telefon",
        "Zeitung",
        "Schule",
        "Universität",
        "Name",
        "Architekt",
        "Buchstabe",
    }
    nouns_sorted = sorted(nouns, key=lambda n: (0 if n["lemma"] in priority else 1, n["lemma"]))
    plural_nouns = nouns_sorted[:550]
    article_nouns = nouns_sorted[:220]

    # 1) Plural forms from vocabulary
    for noun in plural_nouns:
        lemma = noun["lemma"]
        article = noun["article"]
        plural = noun["plural"]
        add_q(
            f"{KEY_PREFIX}:plural:{lemma}",
            "plurals",
            lemma,
            plural,
            wrong_plurals(lemma, plural),
            {
                lang: {
                    "prompt": templates["plural"].format(article=article, lemma=lemma)
                }
                for lang, templates in PROMPT.items()
            },
            {"kind": "plural_form", "article": article},
            noun.get("import_key"),
        )
        add_q(
            f"{KEY_PREFIX}:plural-article:{lemma}",
            "plurals",
            lemma,
            "die",
            ["der", "das", "ein"],
            {
                lang: {"prompt": templates["plural_article"].format(lemma=lemma)}
                for lang, templates in PROMPT.items()
            },
            {"kind": "plural_article"},
            noun.get("import_key"),
        )

    # 2) Nominative / accusative articles (bounded set)
    for noun in article_nouns:
        lemma = noun["lemma"]
        article = noun["article"]
        add_q(
            f"{KEY_PREFIX}:nom-def:{lemma}",
            "articles",
            lemma,
            article,
            [a for a in ("der", "die", "das") if a != article] + ["ein"],
            {
                lang: {"prompt": templates["nom_def"].format(lemma=lemma)}
                for lang, templates in PROMPT.items()
            },
            {"kind": "nominative_definite", "article": article},
            noun.get("import_key"),
        )
        add_q(
            f"{KEY_PREFIX}:akk-def:{lemma}",
            "articles",
            lemma,
            akk_def(article),
            [a for a in ("den", "das", "die", "dem", "der") if a != akk_def(article)],
            {
                lang: {
                    "prompt": templates["akk_def"].format(article=article, lemma=lemma)
                }
                for lang, templates in PROMPT.items()
            },
            {"kind": "accusative_definite", "article": article},
            noun.get("import_key"),
        )
        add_q(
            f"{KEY_PREFIX}:akk-indef:{lemma}",
            "articles",
            lemma,
            akk_indef(article),
            [a for a in ("einen", "ein", "eine", "–", "den") if a != akk_indef(article)],
            {
                lang: {
                    "prompt": templates["akk_indef"].format(article=article, lemma=lemma)
                }
                for lang, templates in PROMPT.items()
            },
            {"kind": "accusative_indefinite", "article": article},
            noun.get("import_key"),
        )
        add_q(
            f"{KEY_PREFIX}:first:{lemma}",
            "articles",
            lemma,
            nom_indef(article),
            [a for a in ("ein", "eine", "der", "die", "das") if a != nom_indef(article)],
            {
                lang: {"prompt": templates["first_mention"].format(lemma=lemma)}
                for lang, templates in PROMPT.items()
            },
            {"kind": "first_mention"},
            noun.get("import_key"),
        )
        add_q(
            f"{KEY_PREFIX}:second:{lemma}",
            "articles",
            lemma,
            article,
            [a for a in ("der", "die", "das", "ein", "eine") if a != article],
            {
                lang: {"prompt": templates["second_mention"].format(lemma=lemma)}
                for lang, templates in PROMPT.items()
            },
            {"kind": "second_mention"},
            noun.get("import_key"),
        )

    # 3) kein forms
    for noun, answer, gender in KEIN_NOUNS:
        add_q(
            f"{KEY_PREFIX}:kein:{noun}",
            "negation",
            noun,
            answer,
            [a for a in ("kein", "keine", "keinen", "nicht") if a != answer],
            {
                lang: {"prompt": templates["kein_form"].format(noun=noun)}
                for lang, templates in PROMPT.items()
            },
            {"kind": "kein_form", "gender": gender},
        )

    # 4) nicht vs kein sentence frames
    for key, frame, answer, wrong in NICHT_KEIN_ITEMS:
        add_q(
            f"{KEY_PREFIX}:nicht-kein:{key}",
            "negation",
            answer,
            answer,
            wrong,
            {
                lang: {"prompt": templates["nicht_kein"].format(frame=frame)}
                for lang, templates in PROMPT.items()
            },
            {"kind": "nicht_vs_kein"},
        )

    # 5) Nullartikel / profession curated
    add_q(
        f"{KEY_PREFIX}:nullartikel:kinder",
        "articles",
        "Kinder",
        "Hast du Kinder?",
        ["Hast du einen Kinder?", "Hast du eine Kinder?", "Hast du den Kinder?"],
        {lang: {"prompt": templates["nullartikel"]} for lang, templates in PROMPT.items()},
        {"kind": "nullartikel_plural"},
    )
    add_q(
        f"{KEY_PREFIX}:nullartikel:stifte",
        "articles",
        "Stifte",
        "Das sind Stifte.",
        ["Das sind einen Stifte.", "Das sind eine Stifte.", "Das sind der Stifte."],
        {lang: {"prompt": templates["nullartikel"]} for lang, templates in PROMPT.items()},
        {"kind": "nullartikel_plural"},
    )
    add_q(
        f"{KEY_PREFIX}:profession:pilotin",
        "articles",
        "Pilotin",
        "Ich bin Pilotin von Beruf.",
        [
            "Ich bin eine Pilotin von Beruf.",
            "Ich bin die Pilotin von Beruf.",
            "Ich bin ein Pilotin von Beruf.",
        ],
        {lang: {"prompt": templates["profession"]} for lang, templates in PROMPT.items()},
        {"kind": "profession_nullartikel"},
    )

    # Regression checks
    by_key = {q["import_key"]: q for q in questions}
    expected = {
        f"{KEY_PREFIX}:plural:Kind": "Kinder",
        f"{KEY_PREFIX}:plural:Buch": "Bücher",
        f"{KEY_PREFIX}:plural:Mann": "Männer",
        f"{KEY_PREFIX}:plural:Frau": "Frauen",
        f"{KEY_PREFIX}:plural:Taxi": "Taxis",
        f"{KEY_PREFIX}:plural-article:Kind": "die",
        f"{KEY_PREFIX}:nom-def:Mann": "der",
        f"{KEY_PREFIX}:akk-def:Mann": "den",
        f"{KEY_PREFIX}:akk-indef:Mann": "einen",
        f"{KEY_PREFIX}:akk-def:Frau": "die",
        f"{KEY_PREFIX}:akk-indef:Buch": "ein",
        f"{KEY_PREFIX}:akk-def:Name": "den",
        f"{KEY_PREFIX}:kein:Hunger": "keinen",
        f"{KEY_PREFIX}:kein:Geld": "kein",
        f"{KEY_PREFIX}:kein:Zeit": "keine",
        f"{KEY_PREFIX}:nicht-kein:nicht-verheiratet": "nicht",
        f"{KEY_PREFIX}:nicht-kein:keine-kinder": "keine",
        f"{KEY_PREFIX}:nullartikel:kinder": "Hast du Kinder?",
        f"{KEY_PREFIX}:profession:pilotin": "Ich bin Pilotin von Beruf.",
    }
    for key, answer in expected.items():
        actual = by_key.get(key, {}).get("answer")
        assert actual == answer, f"{key}: expected {answer!r}, got {actual!r}"

    # Accusative prompts must not imply a wrong noun ending (e.g. "Ich sehe ___ Name").
    for question in questions:
        if question["meta"].get("kind") not in {
            "accusative_definite",
            "accusative_indefinite",
        }:
            continue
        prompt = question["translations"]["en"]["prompt"]
        assert "Ich sehe" not in prompt and "Ich kaufe" not in prompt
        assert question["source_lemma"] in prompt

    for lemma in SKIP_LEMMAS:
        assert f"{KEY_PREFIX}:plural:{lemma}" not in by_key
        assert f"{KEY_PREFIX}:akk-def:{lemma}" not in by_key

    for question in questions:
        assert question["answer"] not in question["distractors"]
        assert len(question["distractors"]) == 3
        assert len(set(question["distractors"])) == 3
        for lang in ("en", "es", "fa", "ar", "tr"):
            assert question["translations"][lang]["prompt"].strip()

    keys = [q["import_key"] for q in questions]
    assert len(keys) == len(set(keys)), "duplicate import_key"
    assert len(questions) >= 1000, f"need ≥1000 questions, got {len(questions)}"

    banned = ("PDF", "Page ", "Section ", "handwritten")
    for question in questions:
        blob = " ".join(t["prompt"] for t in question["translations"].values())
        for token in banned:
            assert token not in blob

    payload = {
        "meta": {
            "lesson_code": LESSON,
            "course_code": COURSE,
            "min_count": 1000,
            "generated_count": len(questions),
            "translation_languages": ["en", "es", "fa", "ar", "tr"],
            "source_vocabulary": "a1_german_words.json",
            "source_lesson": "german_grammar_lesson_04.md",
            "nouns_used": len(nouns),
        },
        "questions": questions,
    }
    OUT_PATH.write_text(json.dumps(payload, ensure_ascii=False, indent=2), encoding="utf-8")
    print(f"wrote {OUT_PATH} count={len(questions)} nouns={len(nouns)}")
    print(Counter(q["topic"] for q in questions))
    print(Counter((q.get("meta") or {}).get("kind") for q in questions))


if __name__ == "__main__":
    main()
