#!/usr/bin/env python3
"""Regenerate german_grammar_lesson_05_questions.json for cases, pronouns, objects."""

from __future__ import annotations

import json
from collections import Counter
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
VOCAB_PATH = ROOT / "vocabulary" / "a1_german_words.json"
OUT_PATH = Path(__file__).resolve().parent / "german_grammar_lesson_05_questions.json"

COURSE = "de-grammar-a1"
LESSON = "cases-pronouns-objects"
KEY_PREFIX = f"{COURSE}:l05"

PROMPT = {
    "en": {
        "akk_def": 'Which accusative definite article does "{article} {lemma}" take?',
        "akk_indef": 'Which accusative indefinite article does "{article} {lemma}" take?',
        "dat_def": 'Which dative definite article does "{article} {lemma}" take?',
        "dat_indef": 'Which dative indefinite article does "{article} {lemma}" take?',
        "dat_mit": 'Complete with the dative after mit: mit ___ {lemma}.',
        "pron_akk": 'What is the accusative form of "{pronoun}"?',
        "pron_dat": 'What is the dative form of "{pronoun}"?',
        "art_pron_akk": 'Replace "{article} {lemma}" with the correct accusative pronoun.',
        "art_pron_dat": 'Replace "{article} {lemma}" with the correct dative pronoun.',
        "dat_verb": "Choose the correct dative pronoun: {frame}",
        "wen_was": "Choose Wen or Was: {frame}",
        "word_order": "Which sentence has correct dative-before-accusative order?",
        "verb_type": 'Which type is the verb "{verb}"?',
        "plural_dat_n": "Which dative plural form is correct?",
    },
    "es": {
        "akk_def": '¿Qué artículo definido acusativo lleva "{article} {lemma}"?',
        "akk_indef": '¿Qué artículo indefinido acusativo lleva "{article} {lemma}"?',
        "dat_def": '¿Qué artículo definido dativo lleva "{article} {lemma}"?',
        "dat_indef": '¿Qué artículo indefinido dativo lleva "{article} {lemma}"?',
        "dat_mit": 'Completa con dativo tras mit: mit ___ {lemma}.',
        "pron_akk": '¿Cuál es el acusativo de "{pronoun}"?',
        "pron_dat": '¿Cuál es el dativo de "{pronoun}"?',
        "art_pron_akk": 'Sustituye "{article} {lemma}" por el pronombre acusativo correcto.',
        "art_pron_dat": 'Sustituye "{article} {lemma}" por el pronombre dativo correcto.',
        "dat_verb": "Elige el pronombre dativo correcto: {frame}",
        "wen_was": "Elige Wen o Was: {frame}",
        "word_order": "¿Qué oración tiene el orden correcto dativo antes de acusativo?",
        "verb_type": '¿De qué tipo es el verbo "{verb}"?',
        "plural_dat_n": "¿Qué forma de dativo plural es correcta?",
    },
    "fa": {
        "akk_def": '"{article} {lemma}" در Akkusativ کدام حرف تعریف معین می‌گیرد؟',
        "akk_indef": '"{article} {lemma}" در Akkusativ کدام حرف تعریف نامعین می‌گیرد؟',
        "dat_def": '"{article} {lemma}" در Dativ کدام حرف تعریف معین می‌گیرد؟',
        "dat_indef": '"{article} {lemma}" در Dativ کدام حرف تعریف نامعین می‌گیرد؟',
        "dat_mit": 'با Dativ کامل کنید: mit ___ {lemma}.',
        "pron_akk": 'شکل Akkusativ ضمیر "{pronoun}" چیست؟',
        "pron_dat": 'شکل Dativ ضمیر "{pronoun}" چیست؟',
        "art_pron_akk": 'به‌جای "{article} {lemma}" ضمیر Akkusativ درست را بگذارید.',
        "art_pron_dat": 'به‌جای "{article} {lemma}" ضمیر Dativ درست را بگذارید.',
        "dat_verb": "ضمیر Dativ درست را انتخاب کنید: {frame}",
        "wen_was": "Wen یا Was را انتخاب کنید: {frame}",
        "word_order": "کدام جمله ترتیب درست Dativ قبل از Akkusativ دارد؟",
        "verb_type": 'فعل "{verb}" از چه نوعی است؟',
        "plural_dat_n": "کدام شکل Dativ جمع درست است؟",
    },
    "ar": {
        "akk_def": 'أي أداة تعريف في Akkusativ تناسب "{article} {lemma}"؟',
        "akk_indef": 'أي أداة نكرة في Akkusativ تناسب "{article} {lemma}"؟',
        "dat_def": 'أي أداة تعريف في Dativ تناسب "{article} {lemma}"؟',
        "dat_indef": 'أي أداة نكرة في Dativ تناسب "{article} {lemma}"؟',
        "dat_mit": 'أكمل بالـ Dativ بعد mit: mit ___ {lemma}.',
        "pron_akk": 'ما صيغة Akkusativ للضمير "{pronoun}"؟',
        "pron_dat": 'ما صيغة Dativ للضمير "{pronoun}"؟',
        "art_pron_akk": 'استبدل "{article} {lemma}" بالضمير الصحيح في Akkusativ.',
        "art_pron_dat": 'استبدل "{article} {lemma}" بالضمير الصحيح في Dativ.',
        "dat_verb": "اختر ضمير Dativ الصحيح: {frame}",
        "wen_was": "اختر Wen أو Was: {frame}",
        "word_order": "أي جملة لها الترتيب الصحيح Dativ قبل Akkusativ؟",
        "verb_type": 'ما نوع الفعل "{verb}"؟',
        "plural_dat_n": "أي صيغة جمع في Dativ صحيحة؟",
    },
    "tr": {
        "akk_def": '"{article} {lemma}" için akkuzatif belirli artikel hangisidir?',
        "akk_indef": '"{article} {lemma}" için akkuzatif belirsiz artikel hangisidir?',
        "dat_def": '"{article} {lemma}" için datif belirli artikel hangisidir?',
        "dat_indef": '"{article} {lemma}" için datif belirsiz artikel hangisidir?',
        "dat_mit": 'mit sonrası datifle tamamlayın: mit ___ {lemma}.',
        "pron_akk": '"{pronoun}" zamirinin akkuzatifi nedir?',
        "pron_dat": '"{pronoun}" zamirinin datifi nedir?',
        "art_pron_akk": '"{article} {lemma}" ifadesini doğru akkuzatif zamirle değiştirin.',
        "art_pron_dat": '"{article} {lemma}" ifadesini doğru datif zamirle değiştirin.',
        "dat_verb": "Doğru datif zamiri seçin: {frame}",
        "wen_was": "Wen veya Was seçin: {frame}",
        "word_order": "Datif → akkuzatif sırası doğru olan cümle hangisidir?",
        "verb_type": '"{verb}" fiili hangi türdedir?',
        "plural_dat_n": "Doğru datif çoğul biçim hangisidir?",
    },
}

PRONOUNS = {
    "ich": {"akk": "mich", "dat": "mir"},
    "du": {"akk": "dich", "dat": "dir"},
    "er": {"akk": "ihn", "dat": "ihm"},
    "sie_sg": {"akk": "sie", "dat": "ihr", "display": "sie (she)"},
    "es": {"akk": "es", "dat": "ihm"},
    "wir": {"akk": "uns", "dat": "uns"},
    "ihr": {"akk": "euch", "dat": "euch", "display": "ihr (you pl.)"},
    "sie_pl": {"akk": "sie", "dat": "ihnen", "display": "sie (they)"},
    "Sie": {"akk": "Sie", "dat": "Ihnen", "display": "Sie (formal)"},
}

ARTICLE_TO_PRONOUN = {
    "der": {"akk": "ihn", "dat": "ihm"},
    "das": {"akk": "es", "dat": "ihm"},
    "die": {"akk": "sie", "dat": "ihr"},
}

VERB_TYPES = {
    "kommen": "intransitive",
    "wohnen": "intransitive",
    "schlafen": "intransitive",
    "gehen": "intransitive",
    "sein": "intransitive",
    "lachen": "intransitive",
    "essen": "transitive",
    "trinken": "transitive",
    "lesen": "transitive",
    "treffen": "transitive",
    "lieben": "transitive",
    "besuchen": "transitive",
    "haben": "transitive",
    "finden": "transitive",
    "sehen": "transitive",
    "hören": "transitive",
    "geben": "ditransitive",
    "bringen": "ditransitive",
    "schenken": "ditransitive",
    "zeigen": "ditransitive",
    "verkaufen": "ditransitive",
    "servieren": "ditransitive",
    "kaufen": "ditransitive",
}

VERB_TYPE_LABELS = {
    "intransitive": {
        "en": "intransitive (subject only)",
        "es": "intransitivo (solo sujeto)",
        "fa": "لازم (فقط فاعل)",
        "ar": "لازم (فاعل فقط)",
        "tr": "geçişsiz (yalnızca özne)",
    },
    "transitive": {
        "en": "transitive (subject + accusative)",
        "es": "transitivo (sujeto + acusativo)",
        "fa": "متعدی (فاعل + Akkusativ)",
        "ar": "متعدٍ (فاعل + Akkusativ)",
        "tr": "geçişli (özne + akkuzatif)",
    },
    "ditransitive": {
        "en": "ditransitive (dative person + accusative thing)",
        "es": "ditransitivo (persona dativa + cosa acusativa)",
        "fa": "دونه‌مفعولی (شخص Dativ + چیز Akkusativ)",
        "ar": "ذو مفعولين (شخص Dativ + شيء Akkusativ)",
        "tr": "çift nesneli (datif kişi + akkuzatif nesne)",
    },
}

WORD_ORDER_ITEMS = [
    (
        "vater-sohn",
        "Der Vater kauft dem Sohn einen Computer.",
        [
            "Der Vater kauft einen Computer dem Sohn.",
            "Der Vater dem Sohn kauft einen Computer.",
            "Dem Sohn der Vater kauft einen Computer.",
        ],
    ),
    (
        "mutter-tochter",
        "Die Mutter schenkt der Tochter ein Handy.",
        [
            "Die Mutter schenkt ein Handy der Tochter.",
            "Die Mutter der Tochter schenkt ein Handy.",
            "Ein Handy die Mutter schenkt der Tochter.",
        ],
    ),
    (
        "gaeste-haus",
        "Wir zeigen den Gästen das Haus.",
        [
            "Wir zeigen das Haus den Gästen.",
            "Wir den Gästen zeigen das Haus.",
            "Das Haus den Gästen zeigen wir.",
        ],
    ),
    (
        "kellner-mann",
        "Der Kellner bringt dem Mann den Fisch.",
        [
            "Der Kellner bringt den Fisch dem Mann.",
            "Der Kellner dem Mann bringt den Fisch.",
            "Den Fisch dem Mann bringt der Kellner.",
        ],
    ),
    (
        "arzt-frau",
        "Dr. König gibt der Frau die Tabletten.",
        [
            "Dr. König gibt die Tabletten der Frau.",
            "Dr. König der Frau gibt die Tabletten.",
            "Die Tabletten der Frau gibt Dr. König.",
        ],
    ),
    (
        "freunde-kaffee",
        "Er serviert den Freunden den Kaffee.",
        [
            "Er serviert den Kaffee den Freunden.",
            "Er den Freunden serviert den Kaffee.",
            "Den Kaffee den Freunden serviert er.",
        ],
    ),
    (
        "freund-auto",
        "Ich verkaufe dem Freund das Auto.",
        [
            "Ich verkaufe das Auto dem Freund.",
            "Ich dem Freund verkaufe das Auto.",
            "Das Auto dem Freund verkaufe ich.",
        ],
    ),
    (
        "kind-suppe",
        "Wir kochen dem Kind eine Suppe.",
        [
            "Wir kochen eine Suppe dem Kind.",
            "Wir dem Kind kochen eine Suppe.",
            "Eine Suppe dem Kind kochen wir.",
        ],
    ),
]

DAT_VERB_ITEMS = [
    ("helfen-dir", "Ich helfe ___. (du)", "dir", ["dich", "du", "ihn"]),
    ("helfen-ihm", "Ich helfe ___. (der Mann)", "ihm", ["ihn", "er", "ihr"]),
    ("helfen-ihr", "Ich helfe ___. (die Frau)", "ihr", ["sie", "ihn", "ihm"]),
    ("helfen-ihnen", "Wir helfen ___. (sie / they)", "ihnen", ["sie", "ihr", "ihm"]),
    ("helfen-ihnen-formal", "Können Sie ___ helfen? (formal you)", "Ihnen", ["Sie", "ihr", "ihnen"]),
    ("antworten-mir", "Bitte antworte ___. (ich)", "mir", ["mich", "ich", "dir"]),
    ("antworten-dir", "Ich antworte ___. (du)", "dir", ["dich", "du", "mir"]),
    ("antworten-ihm", "Ich antworte ___. (er)", "ihm", ["ihn", "er", "ihr"]),
    ("gefallen-mir", "Das Buch gefällt ___. (ich)", "mir", ["mich", "ich", "dir"]),
    ("gefallen-ihr", "Der Film gefällt ___. (sie / she)", "ihr", ["sie", "ihm", "ihn"]),
    ("passen-mir", "Die Jacke passt ___. (ich)", "mir", ["mich", "ich", "ihm"]),
    ("passen-dir", "Die Hose passt ___. (du)", "dir", ["dich", "du", "mir"]),
    ("geht-mir", "Es geht ___ gut. (ich)", "mir", ["mich", "ich", "dir"]),
    ("geht-ihm", "Es geht ___ gut. (er)", "ihm", ["ihn", "er", "ihr"]),
    ("geht-ihnen", "Wie geht es ___? (formal you)", "Ihnen", ["Sie", "ihr", "ihnen"]),
]

WEN_WAS_ITEMS = [
    ("wen-kollegen", "___ sehen Sie? — Meine Kollegen.", "Wen", ["Was", "Wem", "Wer"]),
    ("was-champagner", "___ trinken Sie gerne? — Champagner.", "Was", ["Wen", "Wem", "Wer"]),
    ("wen-freund", "___ besuchst du? — Meinen Freund.", "Wen", ["Was", "Wem", "Wer"]),
    ("was-buch", "___ liest du? — Ein Buch.", "Was", ["Wen", "Wem", "Wer"]),
    ("wem-helfen", "___ hilfst du? — Meiner Schwester.", "Wem", ["Wen", "Was", "Wer"]),
    ("wem-geben", "___ gibst du das Buch? — Dem Lehrer.", "Wem", ["Wen", "Was", "Wer"]),
    ("was-essen", "___ isst du? — Pizza.", "Was", ["Wen", "Wem", "Wer"]),
    ("wen-treffen", "___ triffst du? — Meine Eltern.", "Wen", ["Was", "Wem", "Wer"]),
]

PLURAL_DAT_ITEMS = [
    ("kinder", "den Kindern", ["den Kinder", "dem Kindern", "die Kindern"]),
    ("leute", "den Leuten", ["den Leute", "dem Leuten", "die Leuten"]),
    ("gaeste", "den Gästen", ["den Gäste", "dem Gästen", "die Gästen"]),
    ("freunde", "den Freunden", ["den Freunde", "dem Freunden", "die Freunden"]),
    ("maenner", "den Männern", ["den Männer", "dem Männern", "die Männern"]),
    ("autos", "den Autos", ["den Autosen", "dem Autos", "die Autosen"]),
    ("frauen", "den Frauen", ["den Frauens", "dem Frauen", "die Frauenn"]),
    ("babys", "den Babys", ["den Babysen", "dem Babys", "die Babysen"]),
]

SKIP_LEMMAS = {
    "Beisl",
    "Bekannte",
    "Erwachsene",
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


def is_plurale_tantum(lexeme: dict) -> bool:
    source = lexeme.get("source_german") or ""
    return "(Pl" in source or "Pl.)" in source


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


def akk_def(article: str) -> str:
    return {"der": "den", "das": "das", "die": "die"}[article]


def akk_indef(article: str) -> str:
    return {"der": "einen", "das": "ein", "die": "eine"}[article]


def dat_def(article: str) -> str:
    return {"der": "dem", "das": "dem", "die": "der"}[article]


def dat_indef(article: str) -> str:
    return {"der": "einem", "das": "einem", "die": "einer"}[article]


def main() -> None:
    vocab = json.loads(VOCAB_PATH.read_text(encoding="utf-8"))
    nouns: list[dict] = []
    seen: set[str] = set()
    for lexeme in vocab["lexemes"]:
        lemma = lexeme["lemma"]
        if lemma in seen or lemma in SKIP_LEMMAS or " " in lemma or "-" in lemma:
            continue
        # Plural-only nouns use die/den, not feminine der/einer — skip from gender drills.
        if is_plurale_tantum(lexeme):
            continue
        for sense in lexeme.get("senses", []):
            if sense.get("part_of_speech") != "noun":
                continue
            detail = sense.get("german_noun") or {}
            article = detail.get("article")
            gender = detail.get("grammatical_gender")
            if article not in {"der", "die", "das"}:
                continue
            if gender not in {"masculine", "feminine", "neuter"}:
                continue
            nouns.append(
                {
                    "lemma": lemma,
                    "article": article,
                    "import_key": sense.get("import_key"),
                }
            )
            seen.add(lemma)
            break

    priority = {
        "Mann",
        "Frau",
        "Kind",
        "Buch",
        "Auto",
        "Tisch",
        "Stuhl",
        "Hund",
        "Katze",
        "Freund",
        "Freundin",
        "Lehrer",
        "Lehrerin",
        "Haus",
        "Wohnung",
        "Apfel",
        "Brot",
        "Kaffee",
        "Tee",
        "Film",
        "Stift",
        "Handy",
        "Computer",
        "Pass",
        "Tasche",
        "Schule",
        "Stadt",
        "Name",
        "Sohn",
        "Tochter",
        "Gast",
        "Fisch",
        "Saft",
        "Jacke",
        "Wagen",
        "Zug",
        "Bus",
        "Taxi",
    }
    nouns_sorted = sorted(nouns, key=lambda n: (0 if n["lemma"] in priority else 1, n["lemma"]))
    case_nouns = nouns_sorted[:280]
    pronoun_nouns = [n for n in nouns_sorted if n["article"] in ARTICLE_TO_PRONOUN][:120]

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

    # 1) Accusative / dative articles from vocabulary
    for noun in case_nouns:
        lemma = noun["lemma"]
        article = noun["article"]
        add_q(
            f"{KEY_PREFIX}:akk-def:{lemma}",
            "accusative",
            lemma,
            akk_def(article),
            [a for a in ("den", "das", "die", "dem", "der") if a != akk_def(article)],
            {
                lang: {"prompt": t["akk_def"].format(article=article, lemma=lemma)}
                for lang, t in PROMPT.items()
            },
            {"kind": "akk_def", "article": article},
            noun.get("import_key"),
        )
        add_q(
            f"{KEY_PREFIX}:akk-indef:{lemma}",
            "accusative",
            lemma,
            akk_indef(article),
            [a for a in ("einen", "ein", "eine", "einem", "einer") if a != akk_indef(article)],
            {
                lang: {"prompt": t["akk_indef"].format(article=article, lemma=lemma)}
                for lang, t in PROMPT.items()
            },
            {"kind": "akk_indef", "article": article},
            noun.get("import_key"),
        )
        add_q(
            f"{KEY_PREFIX}:dat-def:{lemma}",
            "dative",
            lemma,
            dat_def(article),
            [a for a in ("dem", "der", "den", "das", "die") if a != dat_def(article)],
            {
                lang: {"prompt": t["dat_def"].format(article=article, lemma=lemma)}
                for lang, t in PROMPT.items()
            },
            {"kind": "dat_def", "article": article},
            noun.get("import_key"),
        )
        add_q(
            f"{KEY_PREFIX}:dat-indef:{lemma}",
            "dative",
            lemma,
            dat_indef(article),
            [a for a in ("einem", "einer", "einen", "ein", "eine") if a != dat_indef(article)],
            {
                lang: {"prompt": t["dat_indef"].format(article=article, lemma=lemma)}
                for lang, t in PROMPT.items()
            },
            {"kind": "dat_indef", "article": article},
            noun.get("import_key"),
        )
        add_q(
            f"{KEY_PREFIX}:dat-mit:{lemma}",
            "dative",
            lemma,
            dat_def(article),
            [a for a in ("dem", "der", "den", "das", "die", "ein") if a != dat_def(article)],
            {
                lang: {"prompt": t["dat_mit"].format(lemma=lemma)}
                for lang, t in PROMPT.items()
            },
            {"kind": "dat_mit", "article": article},
            noun.get("import_key"),
        )

    # 2) Personal pronouns
    for key, forms in PRONOUNS.items():
        display = forms.get("display", key)
        if key == "sie_sg":
            display = "sie (she)"
        elif key == "sie_pl":
            display = "sie (they)"
        elif key == "Sie":
            display = "Sie (formal)"
        elif key == "ihr":
            display = "ihr (you pl.)"
        add_q(
            f"{KEY_PREFIX}:pron-akk:{key}",
            "pronouns",
            key,
            forms["akk"],
            [p for p in ("mich", "dich", "ihn", "sie", "es", "uns", "euch", "Sie") if p != forms["akk"]],
            {
                lang: {"prompt": t["pron_akk"].format(pronoun=display)}
                for lang, t in PROMPT.items()
            },
            {"kind": "pronoun_accusative"},
        )
        add_q(
            f"{KEY_PREFIX}:pron-dat:{key}",
            "pronouns",
            key,
            forms["dat"],
            [p for p in ("mir", "dir", "ihm", "ihr", "uns", "euch", "ihnen", "Ihnen") if p != forms["dat"]],
            {
                lang: {"prompt": t["pron_dat"].format(pronoun=display)}
                for lang, t in PROMPT.items()
            },
            {"kind": "pronoun_dative"},
        )

    # 3) Article → pronoun
    for noun in pronoun_nouns:
        lemma = noun["lemma"]
        article = noun["article"]
        add_q(
            f"{KEY_PREFIX}:art-pron-akk:{lemma}",
            "pronouns",
            lemma,
            ARTICLE_TO_PRONOUN[article]["akk"],
            [p for p in ("ihn", "sie", "es", "ihm", "ihr") if p != ARTICLE_TO_PRONOUN[article]["akk"]],
            {
                lang: {"prompt": t["art_pron_akk"].format(article=article, lemma=lemma)}
                for lang, t in PROMPT.items()
            },
            {"kind": "article_to_pronoun_akk", "article": article},
            noun.get("import_key"),
        )
        add_q(
            f"{KEY_PREFIX}:art-pron-dat:{lemma}",
            "pronouns",
            lemma,
            ARTICLE_TO_PRONOUN[article]["dat"],
            [p for p in ("ihm", "ihr", "ihnen", "ihn", "sie", "es") if p != ARTICLE_TO_PRONOUN[article]["dat"]],
            {
                lang: {"prompt": t["art_pron_dat"].format(article=article, lemma=lemma)}
                for lang, t in PROMPT.items()
            },
            {"kind": "article_to_pronoun_dat", "article": article},
            noun.get("import_key"),
        )

    # 4) Dative verbs
    for key, frame, answer, wrong in DAT_VERB_ITEMS:
        add_q(
            f"{KEY_PREFIX}:dat-verb:{key}",
            "dative_verbs",
            answer,
            answer,
            wrong,
            {
                lang: {"prompt": t["dat_verb"].format(frame=frame)}
                for lang, t in PROMPT.items()
            },
            {"kind": "dative_verb"},
        )

    # 5) Wen / Was / Wem
    for key, frame, answer, wrong in WEN_WAS_ITEMS:
        add_q(
            f"{KEY_PREFIX}:wen-was:{key}",
            "question_words",
            answer,
            answer,
            wrong,
            {
                lang: {"prompt": t["wen_was"].format(frame=frame)}
                for lang, t in PROMPT.items()
            },
            {"kind": "wen_was_wem"},
        )

    # 6) Word order
    for key, answer, wrong in WORD_ORDER_ITEMS:
        add_q(
            f"{KEY_PREFIX}:order:{key}",
            "word_order",
            "dat-akk-order",
            answer,
            wrong,
            {lang: {"prompt": t["word_order"]} for lang, t in PROMPT.items()},
            {"kind": "dat_before_akk"},
        )

    # 7) Verb types — English labels as answers (stable + readable in MCQ)
    for verb, kind in VERB_TYPES.items():
        answer = VERB_TYPE_LABELS[kind]["en"]
        wrong = [VERB_TYPE_LABELS[k]["en"] for k in VERB_TYPE_LABELS if k != kind] + [
            "modal (can / must / want)"
        ]
        translations = {
            lang: {"prompt": templates["verb_type"].format(verb=verb)}
            for lang, templates in PROMPT.items()
        }
        add_q(
            f"{KEY_PREFIX}:verb-type:{verb}",
            "verb_types",
            verb,
            answer,
            wrong,
            translations,
            {"kind": "verb_type", "type_key": kind},
        )

    # 8) Dative plural -n
    for key, answer, wrong in PLURAL_DAT_ITEMS:
        add_q(
            f"{KEY_PREFIX}:plural-dat:{key}",
            "dative",
            key,
            answer,
            wrong,
            {lang: {"prompt": t["plural_dat_n"]} for lang, t in PROMPT.items()},
            {"kind": "dative_plural_n"},
        )

    # Regression checks
    by_key = {q["import_key"]: q for q in questions}
    expected = {
        f"{KEY_PREFIX}:akk-def:Mann": "den",
        f"{KEY_PREFIX}:akk-indef:Mann": "einen",
        f"{KEY_PREFIX}:akk-def:Frau": "die",
        f"{KEY_PREFIX}:akk-def:Kind": "das",
        f"{KEY_PREFIX}:dat-def:Mann": "dem",
        f"{KEY_PREFIX}:dat-def:Frau": "der",
        f"{KEY_PREFIX}:dat-def:Kind": "dem",
        f"{KEY_PREFIX}:dat-indef:Frau": "einer",
        f"{KEY_PREFIX}:pron-akk:ich": "mich",
        f"{KEY_PREFIX}:pron-dat:ich": "mir",
        f"{KEY_PREFIX}:pron-akk:er": "ihn",
        f"{KEY_PREFIX}:pron-dat:er": "ihm",
        f"{KEY_PREFIX}:pron-dat:sie_pl": "ihnen",
        f"{KEY_PREFIX}:pron-dat:Sie": "Ihnen",
        f"{KEY_PREFIX}:dat-verb:helfen-dir": "dir",
        f"{KEY_PREFIX}:dat-verb:geht-ihnen": "Ihnen",
        f"{KEY_PREFIX}:wen-was:wen-kollegen": "Wen",
        f"{KEY_PREFIX}:wen-was:wem-helfen": "Wem",
        f"{KEY_PREFIX}:order:vater-sohn": "Der Vater kauft dem Sohn einen Computer.",
        f"{KEY_PREFIX}:verb-type:geben": VERB_TYPE_LABELS["ditransitive"]["en"],
        f"{KEY_PREFIX}:verb-type:wohnen": VERB_TYPE_LABELS["intransitive"]["en"],
        f"{KEY_PREFIX}:plural-dat:kinder": "den Kindern",
        f"{KEY_PREFIX}:plural-dat:autos": "den Autos",
    }
    for key, answer in expected.items():
        actual = by_key.get(key, {}).get("answer")
        assert actual == answer, f"{key}: expected {answer!r}, got {actual!r}"

    # sie prompts must differ
    assert (
        by_key[f"{KEY_PREFIX}:pron-akk:sie_sg"]["translations"]["en"]["prompt"]
        != by_key[f"{KEY_PREFIX}:pron-akk:sie_pl"]["translations"]["en"]["prompt"]
    )

    # Dative-verb prompts must include a disambiguating cue
    for question in questions:
        if question["meta"].get("kind") != "dative_verb":
            continue
        prompt = question["translations"]["en"]["prompt"]
        assert "(" in prompt, f"ambiguous dative verb prompt: {prompt!r}"

    # Verb-type prompts must not leak the correct label
    for question in questions:
        if question["meta"].get("kind") != "verb_type":
            continue
        for lang, translation in question["translations"].items():
            prompt = translation["prompt"]
            assert "[" not in prompt, f"verb-type answer leak ({lang}): {prompt!r}"
            assert question["answer"] not in prompt

    # ihr must be disambiguated from dative "ihr" (her)
    assert "you pl" in by_key[f"{KEY_PREFIX}:pron-akk:ihr"]["translations"]["en"]["prompt"]

    # No pluralia tantum in gender/article drills
    for question in questions:
        if question["meta"].get("kind") not in {
            "akk_def",
            "akk_indef",
            "dat_def",
            "dat_indef",
            "dat_mit",
            "article_to_pronoun_akk",
            "article_to_pronoun_dat",
        }:
            continue
        assert question["source_lemma"] not in {
            "Bauchschmerzen",
            "Chips",
            "Daten",
            "Eltern",
            "Leute",
            "Ferien",
            "Geschwister",
            "Möbel",
        }, f"plurale tantum in drill: {question['import_key']}"

    for question in questions:
        assert question["answer"] not in question["distractors"]
        assert len(question["distractors"]) == 3
        assert len(set(question["distractors"])) == 3
        for lang in ("en", "es", "fa", "ar", "tr"):
            assert question["translations"][lang]["prompt"].strip()
        blob = " ".join(t["prompt"] for t in question["translations"].values())
        for token in ("PDF", "Page ", "Section "):
            assert token not in blob

    # No accusative sentence frames that break n-declension nouns
    for question in questions:
        if question["meta"].get("kind") in {"akk_def", "akk_indef", "dat_def", "dat_indef"}:
            prompt = question["translations"]["en"]["prompt"]
            assert "Ich sehe" not in prompt and "Ich kaufe" not in prompt

    keys = [q["import_key"] for q in questions]
    assert len(keys) == len(set(keys)), "duplicate import_key"
    assert len(questions) >= 1000, f"need ≥1000 questions, got {len(questions)}"

    payload = {
        "meta": {
            "lesson_code": LESSON,
            "course_code": COURSE,
            "min_count": 1000,
            "generated_count": len(questions),
            "translation_languages": ["en", "es", "fa", "ar", "tr"],
            "source_vocabulary": "a1_german_words.json",
            "source_lesson": "german_grammar_lesson_05.md",
            "nouns_used": len(case_nouns),
        },
        "questions": questions,
    }
    OUT_PATH.write_text(json.dumps(payload, ensure_ascii=False, indent=2), encoding="utf-8")
    print(f"wrote {OUT_PATH} count={len(questions)}")
    print(Counter(q["topic"] for q in questions))
    print(Counter((q.get("meta") or {}).get("kind") for q in questions))


if __name__ == "__main__":
    main()
