#!/usr/bin/env python3
"""Regenerate german_grammar_lesson_09_questions.json for Präteritum sein/haben/modals (A2)."""

from __future__ import annotations

import json
from collections import Counter
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
VOCAB_PATH = ROOT / "vocabulary" / "a1_german_words.json"
OUT_PATH = Path(__file__).resolve().parent / "german_grammar_lesson_09_questions.json"

COURSE = "de-grammar-a1"
LESSON = "praeteritum-sein-haben-modals"
KEY_PREFIX = f"{COURSE}:l09"

PROMPT = {
    "en": {
        "conj": "Choose the correct past form: {frame}",
        "tense": "Choose the correct tense form: {frame}",
        "umlaut": "Choose the correct Präteritum form (no umlaut): {frame}",
        "moechten": "Choose the correct wish form (*möchten* / *wollte*): {frame}",
        "order": "Choose the correct sentence: {frame}",
        "marker": "For *sein* / *haben* / modals in this lesson, which tense usually goes with “{marker}”?",
        "same": "Which statement is true?",
    },
    "es": {
        "conj": "Elige la forma de pasado correcta: {frame}",
        "tense": "Elige la forma de tiempo correcta: {frame}",
        "umlaut": "Elige el Präteritum correcto (sin umlaut): {frame}",
        "moechten": "Elige la forma correcta del deseo (*möchten* / *wollte*): {frame}",
        "order": "Elige la frase correcta: {frame}",
        "marker": "En esta lección (*sein* / *haben* / modales), ¿qué tiempo suele ir con “{marker}”?",
        "same": "¿Cuál afirmación es correcta?",
    },
    "fa": {
        "conj": "شکل گذشتهٔ درست را انتخاب کنید: {frame}",
        "tense": "شکل زمانی درست را انتخاب کنید: {frame}",
        "umlaut": "شکل Präteritum درست را انتخاب کنید (بدون اوملاوت): {frame}",
        "moechten": "شکل درست خواستن (*möchten* / *wollte*) را انتخاب کنید: {frame}",
        "order": "جملهٔ درست را انتخاب کنید: {frame}",
        "marker": "در این درس (*sein* / *haben* / افعال کمکی وجهی)، با «{marker}» معمولاً کدام زمان می‌آید؟",
        "same": "کدام گزاره درست است؟",
    },
    "ar": {
        "conj": "اختر صيغة الماضي الصحيحة: {frame}",
        "tense": "اختر صيغة الزمن الصحيحة: {frame}",
        "umlaut": "اختر صيغة Präteritum الصحيحة (بدون أملاوت): {frame}",
        "moechten": "اختر صيغة الرغبة الصحيحة (*möchten* / *wollte*): {frame}",
        "order": "اختر الجملة الصحيحة: {frame}",
        "marker": "في هذا الدرس (*sein* / *haben* / الأفعال الناقصة)، أي زمن يُستخدم عادةً مع “{marker}”؟",
        "same": "أي عبارة صحيحة؟",
    },
    "tr": {
        "conj": "Doğru geçmiş biçimi seçin: {frame}",
        "tense": "Doğru zaman biçimini seçin: {frame}",
        "umlaut": "Doğru Präteritum biçimini seçin (umlaut yok): {frame}",
        "moechten": "Doğru dilek biçimini seçin (*möchten* / *wollte*): {frame}",
        "order": "Doğru cümleyi seçin: {frame}",
        "marker": "Bu derste (*sein* / *haben* / kipler) “{marker}” ile genelde hangi zaman kullanılır?",
        "same": "Hangi ifade doğrudur?",
    },
}

# Predicative adjectives that make sense with sein (A1–A2).
SAFE_ADJECTIVES = [
    "krank", "müde", "jung", "alt", "glücklich", "traurig", "allein",
    "ledig", "verheiratet", "hungrig", "durstig", "nervös", "ruhig",
    "freundlich", "fit", "schlau", "reich", "arm", "gesund", "erkältet",
    "satt", "sauber", "schmutzig", "frisch", "gemütlich", "elegant",
    "dünn", "männlich", "weiblich", "möbliert", "hell", "dunkel",
    "blau", "braun", "gelb", "grau", "grün", "lila", "rot", "schwarz",
    "weiß", "süß", "lecker",
]

BAD_ADJECTIVE_LEMMAS = {
    "dreimal", "zweimal", "eintausend", "insgesamt", "maximal", "mindestens",
    "minus", "plus", "zweit", "zweite", "halb", "erst", "erste", "dritt",
}

PRONOUNS = ["ich", "du", "er", "sie (she)", "es", "wir", "ihr", "sie (they)", "Sie (formal)", "man"]

SEIN = {
    "ich": "war",
    "du": "warst",
    "er": "war",
    "sie (she)": "war",
    "es": "war",
    "wir": "waren",
    "ihr": "wart",
    "sie (they)": "waren",
    "Sie (formal)": "waren",
    "man": "war",
}

HABEN = {
    "ich": "hatte",
    "du": "hattest",
    "er": "hatte",
    "sie (she)": "hatte",
    "es": "hatte",
    "wir": "hatten",
    "ihr": "hattet",
    "sie (they)": "hatten",
    "Sie (formal)": "hatten",
    "man": "hatte",
}

MODALS = {
    "können": {
        "ich": "konnte",
        "du": "konntest",
        "er": "konnte",
        "sie (she)": "konnte",
        "es": "konnte",
        "wir": "konnten",
        "ihr": "konntet",
        "sie (they)": "konnten",
        "Sie (formal)": "konnten",
        "man": "konnte",
    },
    "müssen": {
        "ich": "musste",
        "du": "musstest",
        "er": "musste",
        "sie (she)": "musste",
        "es": "musste",
        "wir": "mussten",
        "ihr": "musstet",
        "sie (they)": "mussten",
        "Sie (formal)": "mussten",
        "man": "musste",
    },
    "dürfen": {
        "ich": "durfte",
        "du": "durftest",
        "er": "durfte",
        "sie (she)": "durfte",
        "es": "durfte",
        "wir": "durften",
        "ihr": "durftet",
        "sie (they)": "durften",
        "Sie (formal)": "durften",
        "man": "durfte",
    },
    "wollen": {
        "ich": "wollte",
        "du": "wolltest",
        "er": "wollte",
        "sie (she)": "wollte",
        "es": "wollte",
        "wir": "wollten",
        "ihr": "wolltet",
        "sie (they)": "wollten",
        "Sie (formal)": "wollten",
        "man": "wollte",
    },
    "sollen": {
        "ich": "sollte",
        "du": "solltest",
        "er": "sollte",
        "sie (she)": "sollte",
        "es": "sollte",
        "wir": "sollten",
        "ihr": "solltet",
        "sie (they)": "sollten",
        "Sie (formal)": "sollten",
        "man": "sollte",
    },
}

PRESENT = {
    "sein": {
        "ich": "bin",
        "du": "bist",
        "er": "ist",
        "sie (she)": "ist",
        "es": "ist",
        "wir": "sind",
        "ihr": "seid",
        "sie (they)": "sind",
        "Sie (formal)": "sind",
        "man": "ist",
    },
    "haben": {
        "ich": "habe",
        "du": "hast",
        "er": "hat",
        "sie (she)": "hat",
        "es": "hat",
        "wir": "haben",
        "ihr": "habt",
        "sie (they)": "haben",
        "Sie (formal)": "haben",
        "man": "hat",
    },
    "können": {
        "ich": "kann",
        "du": "kannst",
        "er": "kann",
        "sie (she)": "kann",
        "es": "kann",
        "wir": "können",
        "ihr": "könnt",
        "sie (they)": "können",
        "Sie (formal)": "können",
        "man": "kann",
    },
    "müssen": {
        "ich": "muss",
        "du": "musst",
        "er": "muss",
        "sie (she)": "muss",
        "es": "muss",
        "wir": "müssen",
        "ihr": "müsst",
        "sie (they)": "müssen",
        "Sie (formal)": "müssen",
        "man": "muss",
    },
    "dürfen": {
        "ich": "darf",
        "du": "darfst",
        "er": "darf",
        "sie (she)": "darf",
        "es": "darf",
        "wir": "dürfen",
        "ihr": "dürft",
        "sie (they)": "dürfen",
        "Sie (formal)": "dürfen",
        "man": "darf",
    },
    "wollen": {
        "ich": "will",
        "du": "willst",
        "er": "will",
        "sie (she)": "will",
        "es": "will",
        "wir": "wollen",
        "ihr": "wollt",
        "sie (they)": "wollen",
        "Sie (formal)": "wollen",
        "man": "will",
    },
    "sollen": {
        "ich": "soll",
        "du": "sollst",
        "er": "soll",
        "sie (she)": "soll",
        "es": "soll",
        "wir": "sollen",
        "ihr": "sollt",
        "sie (they)": "sollen",
        "Sie (formal)": "sollen",
        "man": "soll",
    },
}

# Wrong umlauted lookalikes (never correct in Präteritum of these verbs)
UMLAUT_TRAPS = {
    "konnte": "könnte",
    "konntest": "könntest",
    "konnten": "könnten",
    "konntet": "könntet",
    "musste": "müsste",
    "musstest": "müsstest",
    "mussten": "müssten",
    "musstet": "müsstet",
    "durfte": "dürfte",
    "durftest": "dürftest",
    "durften": "dürften",
    "durftet": "dürftet",
}

PAST_MARKERS = ["gestern", "früher", "letztes Jahr", "2019", "vor drei Jahren", "mit 16 Jahren"]
PRESENT_MARKERS = ["heute", "jetzt", "heute Morgen", "dieses Jahr", "mit 35 Jahren"]

INFINITIVES = [
    "kochen", "schlafen", "kommen", "arbeiten", "lesen", "fahren",
    "spielen", "lernen", "helfen", "bleiben", "gehen", "schreiben",
    "schwimmen", "tanzen", "einkaufen", "aufstehen", "fernsehen", "telefonieren",
]

SUBJECT_FRAMES = {
    "ich": "Ich ___ gestern krank.",
    "du": "Du ___ gestern müde.",
    "er": "Er ___ früher ledig.",
    "sie (she)": "Sie (she) ___ früher in Berlin.",
    "es": "Es ___ gestern kalt.",
    "wir": "Wir ___ letztes Jahr in Italien.",
    "ihr": "Ihr ___ gestern im Büro.",
    "sie (they)": "Sie (they) ___ früher Nachbarn.",
    "Sie (formal)": "___ Sie gestern auf dem Meeting? (formal you)",
    "man": "Früher ___ man oft zu Hause.",
}

HABEN_FRAMES = {
    "ich": "Früher ___ ich keine Kinder.",
    "du": "Gestern ___ du keine Zeit.",
    "er": "Er ___ früher ein altes Auto.",
    "sie (she)": "Sie (she) ___ letztes Jahr eine Party.",
    "es": "Das Kind ___ gestern Fieber.",
    "wir": "Wir ___ früher wenig Geld.",
    "ihr": "Ihr ___ gestern Glück.",
    "sie (they)": "Sie (they) ___ früher kein Handy.",
    "Sie (formal)": "___ Sie gestern Zeit? (formal you)",
    "man": "Früher ___ man mehr Zeit.",
}

def modal_frame(pronoun: str, verb: str, infinitive: str) -> str:
    if pronoun == "Sie (formal)":
        return f"___ Sie früher oft {infinitive}? ({verb}, formal you)"
    if pronoun == "man":
        return f"Früher ___ man oft {infinitive}. ({verb})"
    subject = {
        "ich": "ich",
        "du": "du",
        "er": "er",
        "sie (she)": "sie (she)",
        "es": "das Kind",
        "wir": "wir",
        "ihr": "ihr",
        "sie (they)": "sie (they)",
    }[pronoun]
    marker = {
        "ich": "Früher",
        "du": "Gestern",
        "er": "Früher",
        "sie (she)": "Gestern",
        "es": "Früher",
        "wir": "Letztes Jahr",
        "ihr": "Gestern",
        "sie (they)": "Früher",
    }[pronoun]
    return f"{marker} ___ {subject} oft {infinitive}. ({verb})"


def slug(pronoun: str) -> str:
    return (
        pronoun.replace(" ", "-")
        .replace("(", "")
        .replace(")", "")
        .replace(".", "")
        .replace("/", "-")
    )


def distractors_for(correct: str, pool: list[str], n: int = 3) -> list[str]:
    opts: list[str] = []
    seen = {correct.casefold()}
    for candidate in pool:
        text = str(candidate).strip()
        if not text:
            continue
        key = text.casefold()
        if key in seen:
            continue
        opts.append(text)
        seen.add(key)
        if len(opts) >= n:
            break
    if len(opts) < n:
        raise RuntimeError(f"Could not build distractors for {correct!r} from {pool!r}")
    return opts[:n]


def all_past_forms() -> list[str]:
    forms = set(SEIN.values()) | set(HABEN.values())
    for table in MODALS.values():
        forms.update(table.values())
    forms.update(UMLAUT_TRAPS.values())
    for table in PRESENT.values():
        forms.update(table.values())
    forms.update(["möchte", "möchtest", "möchten", "möchtet", "wollte", "wolltest"])
    return sorted(forms)


def main() -> None:
    vocab = json.loads(VOCAB_PATH.read_text(encoding="utf-8"))
    adjectives: list[str] = []
    seen_adj: set[str] = set()
    for lexeme in vocab["lexemes"]:
        lemma = lexeme.get("lemma") or ""
        if " " in lemma or "-" in lemma or len(lemma) < 3:
            continue
        if lemma.casefold() in BAD_ADJECTIVE_LEMMAS:
            continue
        if lemma not in SAFE_ADJECTIVES:
            continue
        for sense in lexeme.get("senses", []):
            if sense.get("part_of_speech") == "adjective":
                key = lemma.casefold()
                if key not in seen_adj:
                    adjectives.append(lemma)
                    seen_adj.add(key)
                break
        if len(adjectives) >= 40:
            break
    if len(adjectives) < 20:
        adjectives = list(SAFE_ADJECTIVES[:40])

    questions: list[dict] = []
    FORM_POOL = all_past_forms()

    def add_q(
        import_key: str,
        topic: str,
        lemma: str,
        answer: str,
        distractors: list[str],
        translations: dict,
        meta: dict | None = None,
    ) -> None:
        clean = distractors_for(answer, distractors, n=3)
        questions.append(
            {
                "import_key": import_key,
                "topic": topic,
                "source_lemma": lemma,
                "sense_import_key": None,
                "answer": answer,
                "distractors": clean,
                "blank_token": "___",
                "meta": meta or {},
                "translations": translations,
            }
        )

    # 1) sein conjugation
    for pronoun in PRONOUNS:
        answer = SEIN[pronoun]
        frame = SUBJECT_FRAMES[pronoun].replace("___", "___ (sein)")
        if pronoun == "Sie (formal)":
            frame = "___ Sie gestern auf dem Meeting? (sein, formal you)"
        wrong = [SEIN[p] for p in PRONOUNS if SEIN[p] != answer] + [
            PRESENT["sein"][pronoun],
            HABEN[pronoun],
            "bin",
            "ist",
            "seid",
        ]
        add_q(
            f"{KEY_PREFIX}:sein:{slug(pronoun)}",
            "praeteritum_sein_haben",
            "sein",
            answer,
            wrong,
            {lang: {"prompt": t["conj"].format(frame=frame)} for lang, t in PROMPT.items()},
            {"kind": "sein_conj", "pronoun": pronoun},
        )

    # 2) haben conjugation
    for pronoun in PRONOUNS:
        answer = HABEN[pronoun]
        frame = HABEN_FRAMES[pronoun].replace("___", "___ (haben)")
        if pronoun == "Sie (formal)":
            frame = "___ Sie gestern Zeit? (haben, formal you)"
        wrong = [HABEN[p] for p in PRONOUNS if HABEN[p] != answer] + [
            PRESENT["haben"][pronoun],
            SEIN[pronoun],
            "habe",
            "hat",
            "habt",
        ]
        add_q(
            f"{KEY_PREFIX}:haben:{slug(pronoun)}",
            "praeteritum_sein_haben",
            "haben",
            answer,
            wrong,
            {lang: {"prompt": t["conj"].format(frame=frame)} for lang, t in PROMPT.items()},
            {"kind": "haben_conj", "pronoun": pronoun},
        )

    # 3) modal conjugations (+ umlaut traps)
    for verb, table in MODALS.items():
        for pronoun in PRONOUNS:
            answer = table[pronoun]
            inf = INFINITIVES[hash((verb, pronoun)) % len(INFINITIVES)]
            frame = modal_frame(pronoun, verb, inf)
            wrong = [table[p] for p in PRONOUNS if table[p] != answer]
            if answer in UMLAUT_TRAPS:
                wrong.insert(0, UMLAUT_TRAPS[answer])
            wrong.extend(
                [
                    PRESENT[verb][pronoun],
                    PRESENT.get("können", {}).get(pronoun, "kann"),
                    "möchte",
                    "wollte" if verb != "wollen" else "will",
                ]
            )
            add_q(
                f"{KEY_PREFIX}:modal:{verb}:{slug(pronoun)}",
                "praeteritum_modals",
                verb,
                answer,
                wrong,
                {lang: {"prompt": t["conj"].format(frame=frame)} for lang, t in PROMPT.items()},
                {"kind": "modal_conj", "verb": verb, "pronoun": pronoun},
            )

    # 4) Explicit no-umlaut items
    for stem, wrong_u in [
        ("konnte", "könnte"),
        ("musste", "müsste"),
        ("durfte", "dürfte"),
        ("konnten", "könnten"),
        ("mussten", "müssten"),
        ("durften", "dürften"),
        ("konntest", "könntest"),
        ("musstest", "müsstest"),
        ("durftest", "dürftest"),
    ]:
        verb = {
            "konnte": "können",
            "könnte": "können",
            "musste": "müssen",
            "müsste": "müssen",
            "durfte": "dürfen",
            "dürfte": "dürfen",
            "konnten": "können",
            "könnten": "können",
            "mussten": "müssen",
            "müssten": "müssen",
            "durften": "dürfen",
            "dürften": "dürfen",
            "konntest": "können",
            "könntest": "können",
            "musstest": "müssen",
            "müsstest": "müssen",
            "durftest": "dürfen",
            "dürftest": "dürfen",
        }[stem]
        frame = f"Gestern ___ er / sie / du / wir arbeiten. ({verb} — Präteritum, no umlaut)"
        # specialize frame lightly
        if stem.endswith("test"):
            frame = f"Gestern ___ du arbeiten. ({verb} — Präteritum, no umlaut)"
        elif stem.endswith("ten"):
            frame = f"Früher ___ wir oft arbeiten. ({verb} — Präteritum, no umlaut)"
        else:
            frame = f"Gestern ___ er lange arbeiten. ({verb} — Präteritum, no umlaut)"
        add_q(
            f"{KEY_PREFIX}:umlaut:{stem}",
            "praeteritum_modals",
            verb,
            stem,
            [wrong_u, PRESENT[verb]["er"], "möchte", "wollte"],
            {lang: {"prompt": t["umlaut"].format(frame=frame)} for lang, t in PROMPT.items()},
            {"kind": "no_umlaut"},
        )

    # 5) möchten → wollte
    moechten_items = [
        ("gestern-bier", "Gestern ___ ich ein Bier trinken. (past wish)", "wollte", ["möchte", "will", "kann"]),
        ("frueher-rauchen", "Früher ___ ich viel rauchen. (past habit/wish)", "wollte", ["möchte", "will", "mag"]),
        ("heute-wein", "Heute ___ ich einen Wein trinken. (present wish)", "möchte", ["wollte", "will", "musste"]),
        ("jetzt-schlafen", "Jetzt ___ ich schlafen. (present wish)", "möchte", ["wollte", "sollte", "konnte"]),
        ("gestern-schlafen", "Gestern ___ ich schlafen. (past wish)", "wollte", ["möchte", "will", "kann"]),
        ("letztes-jahr", "Letztes Jahr ___ sie nach Spanien fahren. (past wish, sie/she)", "wollte", ["möchte", "will", "wollten"]),
        ("wir-frueher", "Früher ___ wir immer tanzen. (past wish, wir)", "wollten", ["möchten", "wollen", "wollte"]),
        ("du-gestern", "Gestern ___ du nicht arbeiten. (past wish, du)", "wolltest", ["möchtest", "willst", "wollte"]),
    ]
    for key, frame, answer, wrong in moechten_items:
        # Fix distractor that accidentally equals answer pattern
        wrong = [w for w in wrong if w != answer and " " not in w]
        while len(wrong) < 3:
            wrong.append("kann")
        add_q(
            f"{KEY_PREFIX}:moechten:{key}",
            "praeteritum_modals",
            "wollen",
            answer,
            wrong,
            {lang: {"prompt": t["moechten"].format(frame=frame)} for lang, t in PROMPT.items()},
            {"kind": "moechten_wollte"},
        )

    # 6) Tense contrast with markers
    for marker in PAST_MARKERS:
        add_q(
            f"{KEY_PREFIX}:marker-past:{slug(marker)}",
            "praeteritum_vs_praesens",
            marker,
            "Präteritum",
            ["Präsens", "Futur I", "Plusquamperfekt"],
            {lang: {"prompt": t["marker"].format(marker=marker)} for lang, t in PROMPT.items()},
            {"kind": "time_marker", "tense": "past"},
        )
    for marker in PRESENT_MARKERS:
        add_q(
            f"{KEY_PREFIX}:marker-pres:{slug(marker)}",
            "praeteritum_vs_praesens",
            marker,
            "Präsens",
            ["Präteritum", "Futur I", "Plusquamperfekt"],
            {lang: {"prompt": t["marker"].format(marker=marker)} for lang, t in PROMPT.items()},
            {"kind": "time_marker", "tense": "present"},
        )

    # 7) Sentence-level tense picks
    tense_items = [
        ("frueher-war", "Früher ___ ich ledig.", "war", ["bin", "ist", "warst"]),
        ("heute-bin", "Heute ___ ich verheiratet.", "bin", ["war", "ist", "warst"]),
        ("gestern-hatte", "Gestern ___ ich keine Zeit.", "hatte", ["habe", "hat", "hattest"]),
        ("jetzt-habe", "Jetzt ___ ich viel Arbeit.", "habe", ["hatte", "hat", "hatten"]),
        ("2019-waren", "2019 ___ wir in Korea.", "waren", ["sind", "war", "seid"]),
        ("heute-sind", "Heute ___ wir wieder zu Hause.", "sind", ["waren", "war", "seid"]),
        ("frueher-konnte", "Früher ___ er noch nicht lesen.", "konnte", ["kann", "könnte", "konnten"]),
        ("jetzt-kann", "Jetzt ___ er schon lesen.", "kann", ["konnte", "könnte", "können"]),
        ("vor-jahr-musste", "Vor einem Jahr ___ sie viel lernen.", "musste", ["muss", "müsste", "mussten"]),
        ("jetzt-muss", "Jetzt ___ sie nicht so viel lernen.", "muss", ["musste", "müsste", "müssen"]),
        ("gestern-durfte", "Gestern ___ er länger aufbleiben.", "durfte", ["darf", "dürfte", "durften"]),
        ("heute-darf", "Heute ___ er nicht so lange aufbleiben.", "darf", ["durfte", "dürfte", "dürfen"]),
    ]
    for key, frame, answer, wrong in tense_items:
        add_q(
            f"{KEY_PREFIX}:tense:{key}",
            "praeteritum_vs_praesens",
            key,
            answer,
            wrong,
            {lang: {"prompt": t["tense"].format(frame=frame)} for lang, t in PROMPT.items()},
            {"kind": "tense_contrast"},
        )

    # 8) ich/er identity rule
    same_items = [
        (
            "sein-ich-er",
            "In Präteritum, ich and er/sie/es forms of sein are identical (war).",
            [
                "In Präteritum, ich and du forms of sein are identical.",
                "sein always keeps an ending in Präteritum for ich.",
                "sein uses Perfekt only in spoken German.",
            ],
        ),
        (
            "haben-ich-er",
            "In Präteritum, ich and er/sie/es forms of haben are identical (hatte).",
            [
                "haben becomes hattest for both ich and er.",
                "haben has no Präteritum form.",
                "ich hatte and er hat are the same.",
            ],
        ),
        (
            "modal-ich-er",
            "Modal Präteritum: ich and er/sie/es share the -te form (konnte, musste…).",
            [
                "Modal Präteritum always keeps the infinitive umlaut.",
                "ich konnte and du konnte are the same form.",
                "möchten has its own Präteritum möchte.",
            ],
        ),
    ]
    for key, answer, wrong in same_items:
        add_q(
            f"{KEY_PREFIX}:rule:{key}",
            "praeteritum_sein_haben",
            key,
            answer,
            wrong,
            {
                lang: {"prompt": f"{t['same']} ({key.replace('-', ' / ')})"}
                for lang, t in PROMPT.items()
            },
            {"kind": "rule"},
        )

    # 9) Word order: modal + infinitive
    order_items = [
        (
            "musste-kochen",
            "Ich musste früher nie kochen.",
            [
                "Ich früher musste nie kochen.",
                "Ich musste früher nie gekocht.",
                "Ich früher nie kochen musste.",
            ],
        ),
        (
            "konnte-kommen",
            "Gestern konnte er nicht kommen.",
            [
                "Gestern er konnte nicht kommen.",
                "Gestern konnte er nicht gekommen.",
                "Gestern er nicht kommen konnte.",
            ],
        ),
        (
            "durften-rauchen",
            "Früher durften die Gäste im Restaurant rauchen.",
            [
                "Früher die Gäste durften im Restaurant rauchen.",
                "Früher durften die Gäste im Restaurant geraucht.",
                "Früher die Gäste im Restaurant rauchen durften.",
            ],
        ),
        (
            "wollte-schlafen",
            "Gestern wollte ich lange schlafen.",
            [
                "Gestern ich wollte lange schlafen.",
                "Gestern wollte ich lange geschlafen.",
                "Gestern ich lange schlafen wollte.",
            ],
        ),
        (
            "sollte-lernen",
            "Letztes Jahr sollte sie mehr lernen.",
            [
                "Letztes Jahr sie sollte mehr lernen.",
                "Letztes Jahr sollte sie mehr gelernt.",
                "Letztes Jahr sie mehr lernen sollte.",
            ],
        ),
    ]
    for key, answer, wrong in order_items:
        add_q(
            f"{KEY_PREFIX}:order:{key}",
            "praeteritum_modals",
            key,
            answer,
            wrong,
            {
                lang: {
                    "prompt": t["order"].format(
                        frame=f"modal in position 2 + infinitive at end — {key.replace('-', ' / ')}"
                    )
                }
                for lang, t in PROMPT.items()
            },
            {"kind": "word_order"},
        )

    # 10) Modal past form with fixed infinitive (high-value conjugation volume)
    # NOTE: Do not quiz "pick any infinitive" — every infinitive would be valid.
    for verb in MODALS:
        for pronoun in PRONOUNS:
            past = MODALS[verb][pronoun]
            present = PRESENT[verb][pronoun]
            for marker, tense in [("Früher", "past"), ("Gestern", "past"), ("Heute", "present")]:
                answer = past if tense == "past" else present
                if pronoun == "Sie (formal)":
                    frame = f"{marker} ___ Sie oft kochen? ({verb})"
                elif pronoun == "man":
                    frame = f"{marker} ___ man oft kochen. ({verb})"
                else:
                    subject = {
                        "ich": "ich",
                        "du": "du",
                        "er": "er",
                        "sie (she)": "sie (she)",
                        "es": "das Kind",
                        "wir": "wir",
                        "ihr": "ihr",
                        "sie (they)": "sie (they)",
                    }[pronoun]
                    frame = f"{marker} ___ {subject} oft kochen. ({verb})"
                wrong = [past, present, UMLAUT_TRAPS.get(past, "könnte"), "möchte", "will", "muss"]
                add_q(
                    f"{KEY_PREFIX}:modal-mark:{verb}:{slug(pronoun)}:{slug(marker)}",
                    "praeteritum_modals" if tense == "past" else "praeteritum_vs_praesens",
                    verb,
                    answer,
                    wrong,
                    {lang: {"prompt": t["tense"].format(frame=frame)} for lang, t in PROMPT.items()},
                    {"kind": "modal_marker", "verb": verb, "tense": tense},
                )

    # 10c) haben + kein/eine noun frames from common lemmas
    nouns = [
        ("Auto", "das"),
        ("Haus", "das"),
        ("Kind", "das"),
        ("Hund", "der"),
        ("Katze", "die"),
        ("Fahrrad", "das"),
        ("Handy", "das"),
        ("Computer", "der"),
        ("Job", "der"),
        ("Freund", "der"),
        ("Freundin", "die"),
        ("Wohnung", "die"),
        ("Garten", "der"),
        ("Ticket", "das"),
        ("Problem", "das"),
        ("Idee", "die"),
        ("Idee", "die"),
        ("Familie", "die"),
        ("Arbeit", "die"),
        ("Zeit", "die"),
        ("Geld", "das"),
        ("Auto", "das"),
        ("Buch", "das"),
        ("Tisch", "der"),
        ("Stuhl", "der"),
        ("Fenster", "das"),
        ("Zimmer", "das"),
        ("Schule", "die"),
        ("Universität", "die"),
        ("Arzt", "der"),
        ("Lehrer", "der"),
        ("Lehrerin", "die"),
    ]
    # dedupe preserving order
    seen_n = set()
    uniq_nouns = []
    for lemma, art in nouns:
        if lemma in seen_n:
            continue
        seen_n.add(lemma)
        uniq_nouns.append((lemma, art))

    for lemma, article in uniq_nouns:
        for pronoun in ("ich", "du", "er", "sie (she)", "wir", "ihr", "sie (they)", "Sie (formal)", "man"):
            past = HABEN[pronoun]
            present = PRESENT["haben"][pronoun]
            for marker, tense in [("Früher", "past"), ("Gestern", "past"), ("Heute", "present")]:
                answer = past if tense == "past" else present
                if pronoun == "Sie (formal)":
                    frame = f"{marker} ___ Sie kein {lemma}? (haben)" if article != "die" else f"{marker} ___ Sie keine {lemma}? (haben)"
                elif pronoun == "man":
                    kein = "kein" if article in {"der", "das"} else "keine"
                    frame = f"{marker} ___ man {kein} {lemma}. (haben)"
                else:
                    subject = {
                        "ich": "ich",
                        "du": "du",
                        "er": "er",
                        "sie (she)": "sie (she)",
                        "wir": "wir",
                        "ihr": "ihr",
                        "sie (they)": "sie (they)",
                    }[pronoun]
                    kein = "kein" if article in {"der", "das"} else "keine"
                    frame = f"{marker} ___ {subject} {kein} {lemma}. (haben)"
                wrong = [past, present, SEIN[pronoun], "habt", "hast", "hatten"]
                add_q(
                    f"{KEY_PREFIX}:haben-n:{slug(pronoun)}:{slug(marker)}:{lemma}",
                    "praeteritum_sein_haben" if tense == "past" else "praeteritum_vs_praesens",
                    lemma,
                    answer,
                    wrong,
                    {lang: {"prompt": t["tense"].format(frame=frame)} for lang, t in PROMPT.items()},
                    {"kind": "haben_noun", "tense": tense},
                )

    # 11) Adjective complement drills with sein (volume)
    for adj in adjectives:
        for pronoun, answer in [
            ("ich", "war"),
            ("er", "war"),
            ("sie-she", "war"),
            ("wir", "waren"),
            ("du", "warst"),
            ("ihr", "wart"),
            ("sie-they", "waren"),
            ("Sie", "waren"),
        ]:
            subject = {
                "ich": "ich",
                "er": "er",
                "sie-she": "sie (she)",
                "wir": "wir",
                "du": "du",
                "ihr": "ihr",
                "sie-they": "sie (they)",
                "Sie": "Sie (formal)",
            }[pronoun]
            frame = f"Früher ___ {subject} {adj}. (sein)"
            wrong = ["bin", "bist", "ist", "sind", "seid", "warst", "waren", "hatte", "hattest"]
            add_q(
                f"{KEY_PREFIX}:adj:{pronoun}:{adj}",
                "praeteritum_sein_haben",
                adj,
                answer,
                wrong,
                {lang: {"prompt": t["conj"].format(frame=frame)} for lang, t in PROMPT.items()},
                {"kind": "sein_adj", "pronoun": pronoun},
            )
            # present contrast with heute
            present = {
                "ich": "bin",
                "er": "ist",
                "sie-she": "ist",
                "wir": "sind",
                "du": "bist",
                "ihr": "seid",
                "sie-they": "sind",
                "Sie": "sind",
            }[pronoun]
            add_q(
                f"{KEY_PREFIX}:adj-now:{pronoun}:{adj}",
                "praeteritum_vs_praesens",
                adj,
                present,
                [answer, "hatte", "warst", "habt", "seid" if present != "seid" else "sind"],
                {
                    lang: {
                        "prompt": t["tense"].format(
                            frame=f"Heute ___ {subject} {adj}. (sein, present)"
                        )
                    }
                    for lang, t in PROMPT.items()
                },
                {"kind": "sein_adj_present", "pronoun": pronoun},
            )

    # 12) Question inversion with war/waren/hatte
    q_items = [
        ("warst-du", "___ du gestern krank? (sein)", "Warst", ["War", "Waren", "Bist"]),
        ("waren-sie", "___ Sie gestern im Büro? (sein, formal)", "Waren", ["War", "Warst", "Sind"]),
        ("war-er", "___ er früher ledig? (sein)", "War", ["Warst", "Waren", "Ist"]),
        ("hattest-du", "___ du gestern Zeit? (haben)", "Hattest", ["Hatte", "Hatten", "Hast"]),
        ("hatten-sie", "___ Sie gestern Zeit? (haben, formal)", "Hatten", ["Hatte", "Hattest", "Haben"]),
        ("hatte-er", "___ er früher ein Auto? (haben)", "Hatte", ["Hattest", "Hatten", "Hat"]),
        ("musstest-du", "___ du früher viel lernen? (müssen)", "Musstest", ["Musste", "Müsstest", "Musst"]),
        ("konntest-du", "___ du früher gut schwimmen? (können)", "Konntest", ["Konnte", "Könntest", "Kannst"]),
        ("durftest-du", "___ du früher länger aufbleiben? (dürfen)", "Durftest", ["Durfte", "Dürftest", "Darfst"]),
        ("wolltest-du", "___ du gestern schlafen? (wollen)", "Wolltest", ["Wollte", "Möchtest", "Willst"]),
    ]
    for key, frame, answer, wrong in q_items:
        add_q(
            f"{KEY_PREFIX}:q:{key}",
            "praeteritum_sein_haben" if "sein" in frame or "haben" in frame else "praeteritum_modals",
            key,
            answer,
            wrong,
            {lang: {"prompt": t["conj"].format(frame=frame)} for lang, t in PROMPT.items()},
            {"kind": "question_inversion"},
        )

    # Quality gates
    by_key = {q["import_key"]: q for q in questions}
    expected = {
        f"{KEY_PREFIX}:sein:ich": "war",
        f"{KEY_PREFIX}:sein:du": "warst",
        f"{KEY_PREFIX}:haben:ich": "hatte",
        f"{KEY_PREFIX}:modal:können:ich": "konnte",
        f"{KEY_PREFIX}:modal:müssen:du": "musstest",
        f"{KEY_PREFIX}:umlaut:konnte": "konnte",
        f"{KEY_PREFIX}:moechten:gestern-bier": "wollte",
        f"{KEY_PREFIX}:moechten:heute-wein": "möchte",
        f"{KEY_PREFIX}:marker-past:gestern": "Präteritum",
        f"{KEY_PREFIX}:marker-pres:heute": "Präsens",
        f"{KEY_PREFIX}:tense:frueher-war": "war",
        f"{KEY_PREFIX}:order:musste-kochen": "Ich musste früher nie kochen.",
        f"{KEY_PREFIX}:q:warst-du": "Warst",
    }
    for key, answer in expected.items():
        actual = by_key.get(key, {}).get("answer")
        assert actual == answer, f"{key}: expected {answer!r}, got {actual!r}"

    for question in questions:
        assert question["answer"] not in question["distractors"]
        assert len(question["distractors"]) == 3
        assert len(set(question["distractors"])) == 3
        lowers = [o.casefold() for o in [question["answer"], *question["distractors"]]]
        assert len(lowers) == len(set(lowers)), f"case-only collision: {question['import_key']}"
        for lang in ("en", "es", "fa", "ar", "tr"):
            assert question["translations"][lang]["prompt"].strip()
        blob = " ".join(t["prompt"] for t in question["translations"].values())
        for token in (
            "PDF",
            "Page ",
            "Section ",
            "handwritten",
            "Exercise ",
            "Seite ",
            "Eighth lesson",
            "Ninth lesson",
        ):
            assert token not in blob, f"forbidden token in {question['import_key']}: {token}"
        # Never accept umlauted modal past as correct
        if (question.get("meta") or {}).get("kind") in {"modal_conj", "no_umlaut"}:
            assert "ö" not in question["answer"] and "ü" not in question["answer"], question["import_key"]
        assert (question.get("meta") or {}).get("kind") != "infinitive_end", question["import_key"]

    # Same English prompt must not map to different answers (ambiguous quiz items).
    by_en_prompt: dict[str, set[str]] = {}
    for question in questions:
        prompt = question["translations"]["en"]["prompt"]
        by_en_prompt.setdefault(prompt, set()).add(question["answer"])
    ambiguous = {p: a for p, a in by_en_prompt.items() if len(a) > 1}
    assert not ambiguous, f"ambiguous prompts: {list(ambiguous.items())[:3]}"

    keys = [q["import_key"] for q in questions]
    assert len(keys) == len(set(keys)), "duplicate import_key"
    assert len(questions) >= 1000, f"need ≥1000 questions, got {len(questions)}"

    payload = {
        "meta": {
            "lesson_code": LESSON,
            "course_code": COURSE,
            "cefr_level": "A2",
            "min_count": 1000,
            "generated_count": len(questions),
            "translation_languages": ["en", "es", "fa", "ar", "tr"],
            "source_vocabulary": "a1_german_words.json",
            "source_lesson": "german_grammar_lesson_09.md",
        },
        "questions": questions,
    }
    OUT_PATH.write_text(json.dumps(payload, ensure_ascii=False, indent=2), encoding="utf-8")
    print(f"wrote {OUT_PATH} count={len(questions)}")
    print(Counter(q["topic"] for q in questions))
    print(Counter((q.get("meta") or {}).get("kind") for q in questions))


if __name__ == "__main__":
    main()
