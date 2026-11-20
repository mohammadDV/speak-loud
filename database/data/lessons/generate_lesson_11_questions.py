#!/usr/bin/env python3
"""Regenerate german_grammar_lesson_11_questions.json for reflexive verbs (A2)."""

from __future__ import annotations

import json
from collections import Counter
from pathlib import Path

OUT_PATH = Path(__file__).resolve().parent / "german_grammar_lesson_11_questions.json"

COURSE = "de-grammar-a1"
LESSON = "reflexive-verbs-and-pronouns"
KEY_PREFIX = f"{COURSE}:l11"

PROMPT = {
    "en": {
        "pron": "Choose the correct reflexive pronoun: {frame}",
        "case": "Choose Accusative or Dative reflexive: {frame}",
        "order": "Choose the correct sentence: {frame}",
        "aux": "Choose the correct Perfekt auxiliary: {frame}",
        "type": "Which type of reflexive use is this? {frame}",
        "meaning": "What does this mean? {frame}",
        "rule": "Which statement is true?",
        "blank": "Choose the correct form: {frame}",
        "yesno": "Is “{verb}” reflexive in German?",
    },
    "es": {
        "pron": "Elige el pronombre reflexivo correcto: {frame}",
        "case": "Elige el reflexivo en acusativo o dativo: {frame}",
        "order": "Elige la frase correcta: {frame}",
        "aux": "Elige el auxiliar de Perfekt correcto: {frame}",
        "type": "¿Qué tipo de uso reflexivo es? {frame}",
        "meaning": "¿Qué significa? {frame}",
        "rule": "¿Cuál afirmación es correcta?",
        "blank": "Elige la forma correcta: {frame}",
        "yesno": "¿“{verb}” es reflexivo en alemán?",
    },
    "fa": {
        "pron": "ضمیر انعکاسی درست را انتخاب کنید: {frame}",
        "case": "ضمیر انعکاسی Akk یا Dat را انتخاب کنید: {frame}",
        "order": "جملهٔ درست را انتخاب کنید: {frame}",
        "aux": "فعل کمکی Perfekt درست را انتخاب کنید: {frame}",
        "type": "این چه نوع کاربرد انعکاسی است؟ {frame}",
        "meaning": "معنی چیست؟ {frame}",
        "rule": "کدام گزاره درست است؟",
        "blank": "شکل درست را انتخاب کنید: {frame}",
        "yesno": "آیا «{verb}» در آلمانی انعکاسی است؟",
    },
    "ar": {
        "pron": "اختر الضمير الانعكاسي الصحيح: {frame}",
        "case": "اختر الانعكاسي في Akk أو Dat: {frame}",
        "order": "اختر الجملة الصحيحة: {frame}",
        "aux": "اختر الفعل المساعد الصحيح لـ Perfekt: {frame}",
        "type": "أي نوع من الاستخدام الانعكاسي هذا؟ {frame}",
        "meaning": "ماذا يعني هذا؟ {frame}",
        "rule": "أي عبارة صحيحة؟",
        "blank": "اختر الصيغة الصحيحة: {frame}",
        "yesno": "هل “{verb}” انعكاسي في الألمانية؟",
    },
    "tr": {
        "pron": "Doğru dönüşlü zamiri seçin: {frame}",
        "case": "Akkusativ veya Datif dönüşlü zamiri seçin: {frame}",
        "order": "Doğru cümleyi seçin: {frame}",
        "aux": "Doğru Perfekt yardımcı fiilini seçin: {frame}",
        "type": "Bu hangi dönüşlü kullanım türü? {frame}",
        "meaning": "Bu ne anlama gelir? {frame}",
        "rule": "Hangi ifade doğrudur?",
        "blank": "Doğru biçimi seçin: {frame}",
        "yesno": "Almancada “{verb}” dönüşlü mü?",
    },
}

TYPE_LABELS = {
    "en": {
        "true": "True reflexive (action on yourself)",
        "reciprocal": "Reciprocal (each other)",
        "mandatory": "Mandatory reflexive (pronoun is part of the verb)",
    },
    "es": {
        "true": "Reflexivo verdadero (acción sobre ti)",
        "reciprocal": "Recíproco (el uno al otro)",
        "mandatory": "Reflexivo obligatorio (el pronombre forma parte del verbo)",
    },
    "fa": {
        "true": "انعکاسی واقعی (عمل روی خود)",
        "reciprocal": "دوطرفه (یکدیگر)",
        "mandatory": "انعکاسی اجباری (ضمیر بخشی از فعل است)",
    },
    "ar": {
        "true": "انعكاسي حقيقي (فعل على النفس)",
        "reciprocal": "متبادل (بعضهم بعضاً)",
        "mandatory": "انعكاسي إلزامي (الضمير جزء من الفعل)",
    },
    "tr": {
        "true": "Gerçek dönüşlü (kendine eylem)",
        "reciprocal": "Karşılıklı (birbirine)",
        "mandatory": "Zorunlu dönüşlü (zamir fiilin parçası)",
    },
}

# Answers stored in English (like L10 type labels) for type questions.
TYPE_ANSWER_EN = TYPE_LABELS["en"]

AKK = {
    "ich": "mich",
    "du": "dich",
    "er": "sich",
    "sie (she)": "sich",
    "es": "sich",
    "wir": "uns",
    "ihr": "euch",
    "sie (they)": "sich",
    "Sie (formal)": "sich",
    "man": "sich",
}

DAT = {
    "ich": "mir",
    "du": "dir",
    "er": "sich",
    "sie (she)": "sich",
    "es": "sich",
    "wir": "uns",
    "ihr": "euch",
    "sie (they)": "sich",
    "Sie (formal)": "sich",
    "man": "sich",
}

PRONOUNS = list(AKK.keys())

SUBJECT = {
    "ich": "ich",
    "du": "du",
    "er": "er",
    "sie (she)": "sie (she)",
    "es": "das Kind",
    "wir": "wir",
    "ihr": "ihr",
    "sie (they)": "sie (they)",
    "Sie (formal)": "Sie",
    "man": "man",
}

# Present conjugations for common stems used in frames
CONJ = {
    "waschen": {
        "ich": "wasche",
        "du": "wäschst",
        "er": "wäscht",
        "sie (she)": "wäscht",
        "es": "wäscht",
        "wir": "waschen",
        "ihr": "wascht",
        "sie (they)": "waschen",
        "Sie (formal)": "waschen",
        "man": "wäscht",
    },
    "freuen": {
        "ich": "freue",
        "du": "freust",
        "er": "freut",
        "sie (she)": "freut",
        "es": "freut",
        "wir": "freuen",
        "ihr": "freut",
        "sie (they)": "freuen",
        "Sie (formal)": "freuen",
        "man": "freut",
    },
    "ärgern": {
        "ich": "ärgere",
        "du": "ärgerst",
        "er": "ärgert",
        "sie (she)": "ärgert",
        "es": "ärgert",
        "wir": "ärgern",
        "ihr": "ärgert",
        "sie (they)": "ärgern",
        "Sie (formal)": "ärgern",
        "man": "ärgert",
    },
    "interessieren": {
        "ich": "interessiere",
        "du": "interessierst",
        "er": "interessiert",
        "sie (she)": "interessiert",
        "es": "interessiert",
        "wir": "interessieren",
        "ihr": "interessiert",
        "sie (they)": "interessieren",
        "Sie (formal)": "interessieren",
        "man": "interessiert",
    },
    "kümmern": {
        "ich": "kümmere",
        "du": "kümmerst",
        "er": "kümmert",
        "sie (she)": "kümmert",
        "es": "kümmert",
        "wir": "kümmern",
        "ihr": "kümmert",
        "sie (they)": "kümmern",
        "Sie (formal)": "kümmern",
        "man": "kümmert",
    },
    "rasieren": {
        "ich": "rasiere",
        "du": "rasierst",
        "er": "rasiert",
        "sie (she)": "rasiert",
        "es": "rasiert",
        "wir": "rasieren",
        "ihr": "rasiert",
        "sie (they)": "rasieren",
        "Sie (formal)": "rasieren",
        "man": "rasiert",
    },
    "schminken": {
        "ich": "schminke",
        "du": "schminkst",
        "er": "schminkt",
        "sie (she)": "schminkt",
        "es": "schminkt",
        "wir": "schminken",
        "ihr": "schminkt",
        "sie (they)": "schminken",
        "Sie (formal)": "schminken",
        "man": "schminkt",
    },
    "setzen": {
        "ich": "setze",
        "du": "setzt",
        "er": "setzt",
        "sie (she)": "setzt",
        "es": "setzt",
        "wir": "setzen",
        "ihr": "setzt",
        "sie (they)": "setzen",
        "Sie (formal)": "setzen",
        "man": "setzt",
    },
    "fühlen": {
        "ich": "fühle",
        "du": "fühlst",
        "er": "fühlt",
        "sie (she)": "fühlt",
        "es": "fühlt",
        "wir": "fühlen",
        "ihr": "fühlt",
        "sie (they)": "fühlen",
        "Sie (formal)": "fühlen",
        "man": "fühlt",
    },
    "erinnern": {
        "ich": "erinnere",
        "du": "erinnerst",
        "er": "erinnert",
        "sie (she)": "erinnert",
        "es": "erinnert",
        "wir": "erinnern",
        "ihr": "erinnert",
        "sie (they)": "erinnern",
        "Sie (formal)": "erinnern",
        "man": "erinnert",
    },
    "entscheiden": {
        "ich": "entscheide",
        "du": "entscheidest",
        "er": "entscheidet",
        "sie (she)": "entscheidet",
        "es": "entscheidet",
        "wir": "entscheiden",
        "ihr": "entscheidet",
        "sie (they)": "entscheiden",
        "Sie (formal)": "entscheiden",
        "man": "entscheidet",
    },
    "beeilen": {
        "ich": "beeile",
        "du": "beeilst",
        "er": "beeilt",
        "sie (she)": "beeilt",
        "es": "beeilt",
        "wir": "beeilen",
        "ihr": "beeilt",
        "sie (they)": "beeilen",
        "Sie (formal)": "beeilen",
        "man": "beeilt",
    },
}

# lemma, type, present_key or None for separable special handling, complement after pronoun
VERBS_AKK: list[tuple[str, str, str, str]] = [
    # lemma, type, conj_key, after_pronoun
    ("waschen", "true", "waschen", ""),
    ("rasieren", "true", "rasieren", ""),
    ("schminken", "true", "schminken", ""),
    ("setzen", "true", "setzen", ""),
    ("fühlen", "true", "fühlen", "gut"),
    ("freuen", "mandatory", "freuen", "auf das Fest"),
    ("ärgern", "mandatory", "ärgern", "über den Lärm"),
    ("interessieren", "mandatory", "interessieren", "für Musik"),
    ("kümmern", "mandatory", "kümmern", "um die Kinder"),
    ("erinnern", "mandatory", "erinnern", "an den Termin"),
    ("entscheiden", "mandatory", "entscheiden", "für den Job"),
    ("beeilen", "mandatory", "beeilen", ""),
]  # complements must stay register-safe for all pronouns (no "an dich" with Sie)

SEPARABLE = [
    # (lemma, finite_forms_by_pronoun_builder uses stem+prefix)
    ("anziehen", "zieh", "an", "true"),
    ("ausziehen", "zieh", "aus", "true"),
    ("umziehen", "zieh", "um", "true"),
    ("anmelden", "meld", "an", "mandatory"),
    ("ausruhen", "ruh", "aus", "true"),
]

RECIPROCAL = [
    ("küssen", "küssen", "küsst", "küsst"),
    ("kennenlernen", "lernen ... kennen", "lernt ... kennen", "lernt ... kennen"),
    ("verlieben", "verlieben", "verliebt", "verliebt"),
    ("streiten", "streiten", "streitet", "streitet"),
    ("unterhalten", "unterhalten", "unterhält", "unterhält"),
    ("treffen", "treffen", "trifft", "trifft"),
]

BODY_OBJECTS = [
    "die Hände",
    "das Gesicht",
    "die Haare",
    "den Arm",
    "den Rücken",
    "die Füße",
]

CLOTHING = [
    "einen Mantel",
    "eine Jacke",
    "die Schuhe",
    "einen Pullover",
    "die Hose",
]

PERFECT_PARTS = {
    "waschen": "gewaschen",
    "freuen": "gefreut",
    "ärgern": "geärgert",
    "rasieren": "rasiert",
    "schminken": "geschminkt",
    "anziehen": "angezogen",
    "kennenlernen": "kennengelernt",
    "verlieben": "verliebt",
    "interessieren": "interessiert",
    "kümmern": "gekümmert",
    "beschweren": "beschwert",
    "anmelden": "angemeldet",
    "beeilen": "beeilt",
    "erinnern": "erinnert",
}

MARKERS = [
    "Heute",
    "Jetzt",
    "Jeden Morgen",
    "Am Abend",
    "Oft",
    "Immer",
    "Manchmal",
    "Gleich",
    "Später",
    "Normalerweise",
]


def slug(text: str) -> str:
    return (
        text.replace(" ", "-")
        .replace("(", "")
        .replace(")", "")
        .replace(".", "")
        .replace("/", "-")
        .replace("ä", "ae")
        .replace("ö", "oe")
        .replace("ü", "ue")
        .replace("ß", "ss")
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


def all_refl_forms() -> list[str]:
    return sorted(set(AKK.values()) | set(DAT.values()) | {"mich", "mir", "dich", "dir", "sich", "uns", "euch", "ihm", "ihn", "ihr"})


def separable_finite(stem: str, pronoun: str) -> str:
    # ziehen-like stem change for du/er
    if stem == "zieh":
        table = {
            "ich": "ziehe",
            "du": "ziehst",
            "er": "zieht",
            "sie (she)": "zieht",
            "es": "zieht",
            "wir": "ziehen",
            "ihr": "zieht",
            "sie (they)": "ziehen",
            "Sie (formal)": "ziehen",
            "man": "zieht",
        }
        return table[pronoun]
    if stem == "meld":
        table = {
            "ich": "melde",
            "du": "meldest",
            "er": "meldet",
            "sie (she)": "meldet",
            "es": "meldet",
            "wir": "melden",
            "ihr": "meldet",
            "sie (they)": "melden",
            "Sie (formal)": "melden",
            "man": "meldet",
        }
        return table[pronoun]
    if stem == "ruh":
        table = {
            "ich": "ruhe",
            "du": "ruhst",
            "er": "ruht",
            "sie (she)": "ruht",
            "es": "ruht",
            "wir": "ruhen",
            "ihr": "ruht",
            "sie (they)": "ruhen",
            "Sie (formal)": "ruhen",
            "man": "ruht",
        }
        return table[pronoun]
    raise KeyError(stem)


def main() -> None:
    questions: list[dict] = []
    FORM_POOL = all_refl_forms()

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

    # 1) Accusative reflexive pronouns with standard verbs
    for lemma, vtype, conj_key, after in VERBS_AKK:
        for pronoun in PRONOUNS:
            answer = AKK[pronoun]
            finite = CONJ[conj_key][pronoun]
            subj = SUBJECT[pronoun]
            tail = f" {after}" if after else ""
            if pronoun == "Sie (formal)":
                mid = f" {after}" if after else ""
                frame = f"{finite.capitalize()} Sie ___{mid}? (sich {lemma})".replace("  ", " ").replace(" ?", "?")
            elif pronoun in {"sie (she)", "sie (they)"}:
                frame = f"Sie ({'she' if pronoun == 'sie (she)' else 'they'}) {finite} ___{tail}. (sich {lemma})".replace("  ", " ")
            elif pronoun == "es":
                frame = f"Das Kind {finite} ___{tail}. (sich {lemma})".replace("  ", " ")
            elif pronoun == "man":
                frame = f"Man {finite} ___{tail}. (sich {lemma})".replace("  ", " ")
            else:
                frame = f"{subj[0].upper() + subj[1:]} {finite} ___{tail}. (sich {lemma})".replace("  ", " ")

            wrong = [AKK[p] for p in PRONOUNS if AKK[p] != answer] + [
                DAT[pronoun],
                "ihm",
                "ihn",
                "ihr",
            ]
            add_q(
                f"{KEY_PREFIX}:akk:{slug(lemma)}:{slug(pronoun)}",
                "reflexive_pronouns",
                lemma,
                answer,
                wrong,
                {lang: {"prompt": t["pron"].format(frame=frame)} for lang, t in PROMPT.items()},
                {"kind": "akk_pron", "pronoun": pronoun, "type": vtype},
            )

    # 2) Separable reflexive verbs
    for lemma, stem, prefix, vtype in SEPARABLE:
        for pronoun in PRONOUNS:
            answer = AKK[pronoun]
            finite = separable_finite(stem, pronoun)
            subj = SUBJECT[pronoun]
            if pronoun == "Sie (formal)":
                frame = f"{finite.capitalize()} Sie ___ {prefix}? (sich {lemma})"
            elif pronoun in {"sie (she)", "sie (they)"}:
                label = "she" if pronoun == "sie (she)" else "they"
                frame = f"Sie ({label}) {finite} ___ {prefix}. (sich {lemma})"
            elif pronoun == "es":
                frame = f"Das Kind {finite} ___ {prefix}. (sich {lemma})"
            else:
                frame = f"{subj[0].upper() + subj[1:]} {finite} ___ {prefix}. (sich {lemma})"
            wrong = [AKK[p] for p in PRONOUNS if AKK[p] != answer] + [DAT[pronoun], "ihm", "ihn"]
            add_q(
                f"{KEY_PREFIX}:sep:{slug(lemma)}:{slug(pronoun)}",
                "reflexive_pronouns",
                lemma,
                answer,
                wrong,
                {lang: {"prompt": t["pron"].format(frame=frame)} for lang, t in PROMPT.items()},
                {"kind": "akk_sep", "pronoun": pronoun, "type": vtype},
            )

    # 3) Reciprocal (plural subjects focus + type)
    for lemma, _, _, _ in RECIPROCAL:
        for pronoun in ("wir", "ihr", "sie (they)", "Sie (formal)"):
            answer = AKK[pronoun]
            if lemma == "kennenlernen":
                if pronoun == "wir":
                    frame = "Wir lernen ___ kennen. (sich kennenlernen)"
                elif pronoun == "ihr":
                    frame = "Ihr lernt ___ kennen. (sich kennenlernen)"
                elif pronoun == "sie (they)":
                    frame = "Sie (they) lernen ___ kennen. (sich kennenlernen)"
                else:
                    frame = "Lernen Sie ___ kennen? (sich kennenlernen, formal)"
            elif lemma == "verlieben":
                if pronoun == "wir":
                    frame = "Wir verlieben ___ ineinander. (sich verlieben)"
                elif pronoun == "ihr":
                    frame = "Ihr verliebt ___ ineinander. (sich verlieben)"
                elif pronoun == "sie (they)":
                    frame = "Sie (they) verlieben ___ ineinander. (sich verlieben)"
                else:
                    frame = "Verlieben Sie ___ ineinander? (sich verlieben, formal)"
            elif lemma == "unterhalten":
                if pronoun == "wir":
                    frame = "Wir unterhalten ___ über den Film. (sich unterhalten)"
                elif pronoun == "ihr":
                    frame = "Ihr unterhaltet ___ über den Film. (sich unterhalten)"
                elif pronoun == "sie (they)":
                    frame = "Sie (they) unterhalten ___ über den Film. (sich unterhalten)"
                else:
                    frame = "Unterhalten Sie ___ über den Film? (sich unterhalten, formal)"
            elif lemma == "streiten":
                if pronoun == "wir":
                    frame = "Wir streiten ___ oft. (sich streiten)"
                elif pronoun == "ihr":
                    frame = "Ihr streitet ___ oft. (sich streiten)"
                elif pronoun == "sie (they)":
                    frame = "Sie (they) streiten ___ oft. (sich streiten)"
                else:
                    frame = "Streiten Sie ___ oft? (sich streiten, formal)"
            elif lemma == "treffen":
                if pronoun == "wir":
                    frame = "Wir treffen ___ morgen. (sich treffen)"
                elif pronoun == "ihr":
                    frame = "Ihr trefft ___ morgen. (sich treffen)"
                elif pronoun == "sie (they)":
                    frame = "Sie (they) treffen ___ morgen. (sich treffen)"
                else:
                    frame = "Treffen Sie ___ morgen? (sich treffen, formal)"
            else:  # küssen
                if pronoun == "wir":
                    frame = "Wir küssen ___. (sich küssen)"
                elif pronoun == "ihr":
                    frame = "Ihr küsst ___. (sich küssen)"
                elif pronoun == "sie (they)":
                    frame = "Sie (they) küssen ___. (sich küssen)"
                else:
                    frame = "Küssen Sie ___? (sich küssen, formal)"
            wrong = [AKK[p] for p in PRONOUNS if AKK[p] != answer] + [DAT["ich"], "ihm"]
            add_q(
                f"{KEY_PREFIX}:recip:{slug(lemma)}:{slug(pronoun)}",
                "reflexive_pronouns",
                lemma,
                answer,
                wrong,
                {lang: {"prompt": t["pron"].format(frame=frame)} for lang, t in PROMPT.items()},
                {"kind": "reciprocal_pron", "pronoun": pronoun},
            )

    # 4) Type classification
    type_examples = [
        ("waschen", "true", "Sie wäscht sich."),
        ("rasieren", "true", "Er rasiert sich."),
        ("anziehen", "true", "Ich ziehe mich an."),
        ("küssen", "reciprocal", "Sie küssen sich."),
        ("kennenlernen", "reciprocal", "Wir lernen uns kennen."),
        ("verlieben", "reciprocal", "Sie verlieben sich ineinander."),
        ("freuen", "mandatory", "Ich freue mich auf das Fest."),
        ("ärgern", "mandatory", "Er ärgert sich über den Lärm."),
        ("interessieren", "mandatory", "Sie interessiert sich für Kunst."),
        ("kümmern", "mandatory", "Wir kümmern uns um die Kinder."),
    ]
    for lemma, vtype, example in type_examples:
        answer = TYPE_ANSWER_EN[vtype]
        wrong = [TYPE_ANSWER_EN[k] for k in TYPE_ANSWER_EN if k != vtype]
        # need 3 distractors — duplicate with slight variants from other langs? No — use English only answers
        # Only 2 other types — pad with non-reflexive distractors
        wrong = wrong + [
            "Not reflexive (no pronoun needed)",
            "Passive voice",
        ]
        translations = {
            lang: {"prompt": PROMPT[lang]["type"].format(frame=f"*{example}* (*sich {lemma}*)")}
            for lang in PROMPT
        }
        add_q(
            f"{KEY_PREFIX}:type:{slug(lemma)}",
            "reflexive_types",
            lemma,
            answer,
            wrong,
            translations,
            {"kind": "verb_type", "type": vtype},
        )

    # 5) Dative vs Accusative with body/clothing objects
    for pronoun in ("ich", "du", "er", "sie (she)", "wir", "ihr", "sie (they)", "Sie (formal)", "man"):
        for obj in BODY_OBJECTS:
            answer = DAT[pronoun]
            finite = CONJ["waschen"][pronoun]
            if pronoun == "Sie (formal)":
                frame = f"Waschen Sie ___ {obj}? (waschen + object → Dativ)"
            elif pronoun == "sie (she)":
                frame = f"Sie (she) wäscht ___ {obj}. (waschen + object → Dativ)"
            elif pronoun == "sie (they)":
                frame = f"Sie (they) waschen ___ {obj}. (waschen + object → Dativ)"
            elif pronoun == "es":
                frame = f"Das Kind wäscht ___ {obj}. (waschen + object → Dativ)"
            elif pronoun == "man":
                frame = f"Man wäscht ___ {obj}. (waschen + object → Dativ)"
            else:
                subj = SUBJECT[pronoun]
                frame = f"{subj[0].upper() + subj[1:]} {finite} ___ {obj}. (waschen + object → Dativ)"
            wrong = [AKK[pronoun], DAT["ich"] if answer != "mir" else "dir", "ihn", "ihm", "euch", "uns", "sich"]
            add_q(
                f"{KEY_PREFIX}:dat-wash:{slug(pronoun)}:{slug(obj)}",
                "reflexive_dative",
                "waschen",
                answer,
                wrong,
                {lang: {"prompt": t["case"].format(frame=frame)} for lang, t in PROMPT.items()},
                {"kind": "dat_body", "pronoun": pronoun, "object": obj},
            )

    for pronoun in ("ich", "du", "er", "wir", "ihr", "sie (they)", "Sie (formal)"):
        for obj in CLOTHING:
            answer = DAT[pronoun]
            finite = separable_finite("zieh", pronoun)
            if pronoun == "Sie (formal)":
                frame = f"Ziehen Sie ___ {obj} an? (anziehen + object → Dativ)"
            elif pronoun == "sie (they)":
                frame = f"Sie (they) ziehen ___ {obj} an. (anziehen + object → Dativ)"
            else:
                subj = SUBJECT[pronoun]
                frame = f"{subj[0].upper() + subj[1:]} {finite} ___ {obj} an. (anziehen + object → Dativ)"
            wrong = [AKK[pronoun], "ihm", "ihn", "mir" if answer != "mir" else "dir", "euch"]
            add_q(
                f"{KEY_PREFIX}:dat-an:{slug(pronoun)}:{slug(obj)}",
                "reflexive_dative",
                "anziehen",
                answer,
                wrong,
                {lang: {"prompt": t["case"].format(frame=frame)} for lang, t in PROMPT.items()},
                {"kind": "dat_clothing", "pronoun": pronoun, "object": obj},
            )

    # Contrast: no object → Akk
    for pronoun in ("ich", "du"):
        for lemma, frame_tpl, answer in [
            ("waschen", "{subj} {finite} ___. (no extra object → Akkusativ)", AKK[pronoun]),
            ("anziehen", "{subj} {finite} ___ an. (no extra object → Akkusativ)", AKK[pronoun]),
        ]:
            if lemma == "waschen":
                finite = CONJ["waschen"][pronoun]
            else:
                finite = separable_finite("zieh", pronoun)
            subj = SUBJECT[pronoun][0].upper() + SUBJECT[pronoun][1:]
            frame = frame_tpl.format(subj=subj, finite=finite)
            wrong = [DAT[pronoun], "sich", "uns", "ihm"]
            add_q(
                f"{KEY_PREFIX}:akk-vs-dat:{slug(lemma)}:{slug(pronoun)}:no-obj",
                "reflexive_dative",
                lemma,
                answer,
                wrong,
                {lang: {"prompt": t["case"].format(frame=frame)} for lang, t in PROMPT.items()},
                {"kind": "akk_no_object", "pronoun": pronoun},
            )
            # with object → Dat
            obj = "die Hände" if lemma == "waschen" else "einen Mantel"
            frame2 = (
                f"{subj} {finite} ___ {obj}. (with object → Dativ)"
                if lemma == "waschen"
                else f"{subj} {finite} ___ {obj} an. (with object → Dativ)"
            )
            add_q(
                f"{KEY_PREFIX}:akk-vs-dat:{slug(lemma)}:{slug(pronoun)}:with-obj",
                "reflexive_dative",
                lemma,
                DAT[pronoun],
                [AKK[pronoun], "sich", "uns", "ihm"],
                {lang: {"prompt": t["case"].format(frame=frame2)} for lang, t in PROMPT.items()},
                {"kind": "dat_with_object", "pronoun": pronoun},
            )

    # 6) sich vorstellen meaning / case
    vorstellen_items = [
        ("mich-intro", "Ich möchte ___ vorstellen. (introduce myself)", "mich", ["mir", "dich", "sich"]),
        ("mir-imagine", "Ich kann ___ das nicht vorstellen. (imagine that)", "mir", ["mich", "dir", "sich"]),
        ("dich-intro", "Möchtest du ___ vorstellen? (introduce yourself)", "dich", ["dir", "mich", "sich"]),
        ("dir-imagine", "Kannst du ___ das vorstellen? (imagine that)", "dir", ["dich", "mir", "uns"]),
        ("sich-intro-er", "Er möchte ___ vorstellen. (introduce himself)", "sich", ["ihm", "ihn", "mir"]),
        ("sich-imagine-er", "Er kann ___ das nicht vorstellen. (imagine that)", "sich", ["ihm", "ihn", "mich"]),
        ("uns-intro", "Wir möchten ___ vorstellen. (introduce ourselves)", "uns", ["euch", "sich", "mir"]),
        ("uns-imagine", "Wir können ___ das vorstellen. (imagine that)", "uns", ["euch", "mich", "dir"]),
    ]
    for key, frame, answer, wrong in vorstellen_items:
        add_q(
            f"{KEY_PREFIX}:vorstellen:{key}",
            "reflexive_vorstellen",
            "vorstellen",
            answer,
            wrong,
            {lang: {"prompt": t["case"].format(frame=frame)} for lang, t in PROMPT.items()},
            {"kind": "vorstellen"},
        )

    meaning_items = [
        (
            "akk-intro",
            "Ich möchte mich vorstellen.",
            "to introduce oneself",
            ["to imagine something", "to wash oneself", "to get dressed"],
        ),
        (
            "dat-imagine",
            "Ich kann mir das nicht vorstellen.",
            "to imagine something",
            ["to introduce oneself", "to fall in love", "to take care of"],
        ),
    ]
    for key, example, answer, wrong in meaning_items:
        add_q(
            f"{KEY_PREFIX}:vorstellen-mean:{key}",
            "reflexive_vorstellen",
            "vorstellen",
            answer,
            wrong,
            {lang: {"prompt": t["meaning"].format(frame=f"*{example}*")} for lang, t in PROMPT.items()},
            {"kind": "vorstellen_meaning"},
        )

    # 7) Word order — main clause
    order_main = [
        (
            "waescht-sich-heute",
            "Er wäscht sich heute.",
            ["Er sich wäscht heute.", "Sich wäscht er heute.", "Er heute wäscht sich."],
        ),
        (
            "heute-waescht-er",
            "Heute wäscht er sich.",
            ["Heute er wäscht sich.", "Heute sich wäscht er.", "Heute wäscht sich er."],
        ),
        (
            "ziehe-mich-an",
            "Ich ziehe mich an.",
            ["Ich mich ziehe an.", "Mich ziehe ich an.", "Ich ziehe an mich."],
        ),
        (
            "freue-mich",
            "Ich freue mich auf das Fest.",
            ["Ich mich freue auf das Fest.", "Mich freue ich auf das Fest.", "Ich freue auf das Fest mich."],
        ),
        (
            "aergern-wir",
            "Wir ärgern uns über den Lärm.",
            ["Wir uns ärgern über den Lärm.", "Uns ärgern wir über den Lärm.", "Wir ärgern über den Lärm uns."],
        ),
    ]
    for key, answer, wrong in order_main:
        wrong = [w for w in wrong if w != answer]
        add_q(
            f"{KEY_PREFIX}:order-main:{key}",
            "reflexive_word_order",
            key,
            answer,
            wrong,
            {
                lang: {"prompt": t["order"].format(frame=f"main clause — {key.replace('-', ' / ')}")}
                for lang, t in PROMPT.items()
            },
            {"kind": "order_main"},
        )

    order_sub = [
        (
            "weil-rasieren",
            "...weil er sich rasieren will.",
            ["...weil er rasieren sich will.", "...weil sich er rasieren will.", "...weil er will sich rasieren."],
        ),
        (
            "weil-schminkt",
            "...weil sie sich schminkt.",
            ["...weil sie schminkt sich.", "...weil sich sie schminkt.", "...weil sie schminkt will sich."],
        ),
        (
            "dass-gefreut",
            "...dass die Kinder sich über die Geschenke gefreut haben.",
            [
                "...dass die Kinder haben sich über die Geschenke gefreut.",
                "...dass sich die Kinder über die Geschenke gefreut haben.",
                "...dass die Kinder sich haben über die Geschenke gefreut.",
            ],
        ),
        (
            "weil-anmelden",
            "...weil ich mich anmelden muss.",
            ["...weil ich anmelden mich muss.", "...weil mich ich anmelden muss.", "...weil ich muss mich anmelden."],
        ),
        (
            "wenn-aergern",
            "...wenn ihr euch bei der Arbeit geärgert habt.",
            [
                "...wenn ihr habt euch bei der Arbeit geärgert.",
                "...wenn euch ihr bei der Arbeit geärgert habt.",
                "...wenn ihr euch habt bei der Arbeit geärgert.",
            ],
        ),
    ]
    for key, answer, wrong in order_sub:
        add_q(
            f"{KEY_PREFIX}:order-sub:{key}",
            "reflexive_word_order",
            key,
            answer,
            wrong,
            {
                lang: {"prompt": t["order"].format(frame=f"subordinate clause — {key.replace('-', ' / ')}")}
                for lang, t in PROMPT.items()
            },
            {"kind": "order_sub"},
        )

    # 8) Perfekt always haben
    for lemma, part in PERFECT_PARTS.items():
        for pronoun, aux in [
            ("ich", "habe"),
            ("du", "hast"),
            ("er", "hat"),
            ("wir", "haben"),
            ("ihr", "habt"),
            ("sie (they)", "haben"),
            ("Sie (formal)", "haben"),
        ]:
            refl = AKK[pronoun]
            if pronoun == "Sie (formal)":
                frame = f"___ Sie sich {part}? (sich {lemma}, Perfekt)"
            elif pronoun == "sie (they)":
                frame = f"Gestern ___ sie (they) sich {part}. (sich {lemma})"
            else:
                subj = SUBJECT[pronoun]
                # place refl correctly after aux conceptually; ask for aux only
                frame = f"Gestern ___ {subj} {refl} {part}. (sich {lemma})"
            wrong = ["bin", "bist", "ist", "sind", "seid", "war", "wurde"]
            add_q(
                f"{KEY_PREFIX}:perfekt-aux:{slug(lemma)}:{slug(pronoun)}",
                "reflexive_perfekt",
                lemma,
                aux,
                wrong,
                {lang: {"prompt": t["aux"].format(frame=frame)} for lang, t in PROMPT.items()},
                {"kind": "perfekt_haben", "pronoun": pronoun},
            )

    # 9) heiraten not reflexive + other yes/no
    for verb, answer in [
        ("heiraten", "No"),
        ("sich freuen", "Yes"),
        ("sich ärgern", "Yes"),
        ("sich waschen", "Yes"),
        ("arbeiten", "No"),
        ("sich verlieben", "Yes"),
        ("schlafen", "No"),
        ("sich anziehen", "Yes"),
    ]:
        add_q(
            f"{KEY_PREFIX}:is-refl:{slug(verb)}",
            "reflexive_concepts",
            verb,
            answer,
            ["Yes", "No", "Only in Perfekt", "Only in Präteritum"] if answer == "No" else ["No", "Only with sein", "Only in questions"],
            {lang: {"prompt": t["yesno"].format(verb=verb)} for lang, t in PROMPT.items()},
            {"kind": "is_reflexive"},
        )

    # Fix distractors for Yes answer - "Yes" can't be in distractors
    # distractors_for removes correct — for answer Yes, pool has No etc. Good.
    # for answer No, pool has Yes — Good.

    # 10) Volume: pronoun drills across markers for core verbs
    core_present = [
        ("waschen", "waschen"),
        ("freuen", "freuen"),
        ("ärgern", "ärgern"),
        ("rasieren", "rasieren"),
        ("interessieren", "interessieren"),
        ("kümmern", "kümmern"),
        ("beeilen", "beeilen"),
        ("fühlen", "fühlen"),
        ("erinnern", "erinnern"),
        ("entscheiden", "entscheiden"),
    ]
    after_map = {
        "waschen": "",
        "freuen": "auf das Wochenende",
        "ärgern": "über den Lärm",
        "rasieren": "",
        "interessieren": "für Sport",
        "kümmern": "um die Katze",
        "beeilen": "",
        "fühlen": "gut",
        "erinnern": "an den Termin",
        "entscheiden": "für den Job",
    }
    for lemma, conj_key in core_present:
        for marker in MARKERS:
            for pronoun in ("ich", "du", "er", "sie (she)", "wir", "ihr", "sie (they)", "man", "Sie (formal)"):
                answer = AKK[pronoun]
                finite = CONJ[conj_key][pronoun]
                after = after_map[lemma]
                tail = f" {after}" if after else ""
                if pronoun == "sie (she)":
                    frame = f"{marker}: Sie (she) {finite} ___{tail}. (sich {lemma})".replace("  ", " ")
                elif pronoun == "sie (they)":
                    frame = f"{marker}: Sie (they) {finite} ___{tail}. (sich {lemma})".replace("  ", " ")
                elif pronoun == "man":
                    frame = f"{marker}: Man {finite} ___{tail}. (sich {lemma})".replace("  ", " ")
                elif pronoun == "Sie (formal)":
                    frame = f"{marker}: {finite.capitalize()} Sie ___{tail}? (sich {lemma})".replace("  ", " ").replace(" ?", "?")
                else:
                    subj = SUBJECT[pronoun]
                    frame = f"{marker}: {subj[0].upper() + subj[1:]} {finite} ___{tail}. (sich {lemma})".replace("  ", " ")
                wrong = [AKK[p] for p in PRONOUNS if AKK[p] != answer] + [DAT[pronoun], "ihm", "ihn"]
                add_q(
                    f"{KEY_PREFIX}:vol:{slug(marker)}:{slug(lemma)}:{slug(pronoun)}",
                    "reflexive_pronouns",
                    lemma,
                    answer,
                    wrong,
                    {lang: {"prompt": t["pron"].format(frame=frame)} for lang, t in PROMPT.items()},
                    {"kind": "volume_akk", "pronoun": pronoun, "marker": marker},
                )

    # Extra volume: separable Akk across markers
    for lemma, stem, prefix, _vtype in SEPARABLE:
        for marker in MARKERS[:6]:
            for pronoun in ("ich", "du", "er", "wir", "ihr", "sie (they)", "Sie (formal)"):
                answer = AKK[pronoun]
                finite = separable_finite(stem, pronoun)
                if pronoun == "Sie (formal)":
                    frame = f"{marker}: {finite.capitalize()} Sie ___ {prefix}? (sich {lemma})"
                elif pronoun == "sie (they)":
                    frame = f"{marker}: Sie (they) {finite} ___ {prefix}. (sich {lemma})"
                else:
                    subj = SUBJECT[pronoun]
                    frame = f"{marker}: {subj[0].upper() + subj[1:]} {finite} ___ {prefix}. (sich {lemma})"
                wrong = [AKK[p] for p in PRONOUNS if AKK[p] != answer] + [DAT[pronoun], "ihm"]
                add_q(
                    f"{KEY_PREFIX}:vol-sep:{slug(marker)}:{slug(lemma)}:{slug(pronoun)}",
                    "reflexive_pronouns",
                    lemma,
                    answer,
                    wrong,
                    {lang: {"prompt": t["pron"].format(frame=frame)} for lang, t in PROMPT.items()},
                    {"kind": "volume_sep", "pronoun": pronoun, "marker": marker},
                )

    # 11) Rules
    rules = [
        (
            "sich-third",
            "The special 3rd-person reflexive form is sich.",
            [
                "The special 3rd-person reflexive form is ihn.",
                "ich uses sich in Accusative.",
                "Reflexive pronouns are only used in questions.",
            ],
        ),
        (
            "perfekt-haben",
            "Reflexive verbs form Perfekt with haben.",
            [
                "Reflexive verbs form Perfekt with sein.",
                "Reflexive verbs have no Perfekt.",
                "Only reciprocal verbs use haben.",
            ],
        ),
        (
            "dat-rule",
            "With an extra Accusative object, ich/du switch to mir/dir.",
            [
                "With an extra Accusative object, ich stays mich.",
                "All reflexive pronouns become ihm.",
                "Dative reflexives are only used in Nebensätze.",
            ],
        ),
        (
            "heiraten",
            "heiraten is not reflexive in German.",
            [
                "heiraten always needs sich.",
                "heiraten takes sein in Perfekt because it is reflexive.",
                "heiraten uses mir/dir.",
            ],
        ),
        (
            "nebensatz",
            "In a Nebensatz, the reflexive pronoun stays after the subject; the conjugated verb goes to the end.",
            [
                "In a Nebensatz, the reflexive pronoun goes to the end.",
                "In a Nebensatz, the conjugated verb stays in position 2.",
                "Nebensätze cannot use reflexive verbs.",
            ],
        ),
        (
            "vorstellen",
            "sich vorstellen + Akkusativ = introduce oneself; + Dativ = imagine something.",
            [
                "sich vorstellen always means to imagine.",
                "sich vorstellen never takes Dativ.",
                "sich vorstellen is not reflexive.",
            ],
        ),
    ]
    for key, answer, wrong in rules:
        add_q(
            f"{KEY_PREFIX}:rule:{key}",
            "reflexive_concepts",
            key,
            answer,
            wrong,
            {
                lang: {"prompt": f"{t['rule']} ({key.replace('-', ' / ')})"}
                for lang, t in PROMPT.items()
            },
            {"kind": "rule"},
        )

    # Quality gates
    by_key = {q["import_key"]: q for q in questions}
    expected = {
        f"{KEY_PREFIX}:akk:waschen:ich": "mich",
        f"{KEY_PREFIX}:akk:waschen:du": "dich",
        f"{KEY_PREFIX}:akk:waschen:er": "sich",
        f"{KEY_PREFIX}:akk:waschen:wir": "uns",
        f"{KEY_PREFIX}:akk:waschen:ihr": "euch",
        f"{KEY_PREFIX}:sep:anziehen:ich": "mich",
        f"{KEY_PREFIX}:dat-wash:ich:die-Haende": "mir",
        f"{KEY_PREFIX}:dat-wash:du:die-Haende": "dir",
        f"{KEY_PREFIX}:dat-an:ich:einen-Mantel": "mir",
        f"{KEY_PREFIX}:akk-vs-dat:waschen:ich:no-obj": "mich",
        f"{KEY_PREFIX}:akk-vs-dat:waschen:ich:with-obj": "mir",
        f"{KEY_PREFIX}:vorstellen:mich-intro": "mich",
        f"{KEY_PREFIX}:vorstellen:mir-imagine": "mir",
        f"{KEY_PREFIX}:perfekt-aux:freuen:ich": "habe",
        f"{KEY_PREFIX}:is-refl:heiraten": "No",
        f"{KEY_PREFIX}:is-refl:sich-freuen": "Yes",
        f"{KEY_PREFIX}:order-main:heute-waescht-er": "Heute wäscht er sich.",
        f"{KEY_PREFIX}:order-sub:weil-rasieren": "...weil er sich rasieren will.",
    }
    for key, answer in expected.items():
        actual = by_key.get(key, {}).get("answer")
        assert actual == answer, f"{key}: expected {answer!r}, got {actual!r}"

    for question in questions:
        assert question["answer"] not in question["distractors"], question["import_key"]
        assert len(question["distractors"]) == 3, question["import_key"]
        assert len(set(question["distractors"])) == 3, question["import_key"]
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
            "Tenth lesson",
            "Eleventh lesson",
        ):
            assert token not in blob, f"forbidden token in {question['import_key']}: {token}"
        # Never accept wrong reflexive for ich/du dat drills
        if (question.get("meta") or {}).get("kind") == "dat_body" and question["meta"].get("pronoun") == "ich":
            assert question["answer"] == "mir"
        if (question.get("meta") or {}).get("kind") == "akk_no_object" and question["meta"].get("pronoun") == "ich":
            assert question["answer"] == "mich"

    by_en_prompt: dict[str, set[str]] = {}
    for question in questions:
        prompt = question["translations"]["en"]["prompt"]
        by_en_prompt.setdefault(prompt, set()).add(question["answer"])
    ambiguous = {p: a for p, a in by_en_prompt.items() if len(a) > 1}
    assert not ambiguous, f"ambiguous prompts: {list(ambiguous.items())[:3]}"

    keys = [q["import_key"] for q in questions]
    assert len(keys) == len(set(keys)), "duplicate import_key"
    assert len(questions) >= 1000, f"need ≥1000 questions, got {len(questions)}"

    # Bad German guards
    for question in questions:
        blob = " ".join(t["prompt"] for t in question["translations"].values()) + " " + question["answer"]
        for snip in (
            "ich sich",
            "du sich",
            "WAIT",
            " wascht each",
            "Wascht each",
            "wasche ___ die Zähne",
            "wäschst ___ die Zähne",
            "Erinnern Sie ___ an dich",
            "erinnern ___ an dich",
        ):
            assert snip not in blob, f"bad snippet {snip!r} in {question['import_key']}"

    # Import-key rename: die-Zaehne removed — ensure no leftover expected keys needed
    assert not any("die-Zaehne" in q["import_key"] for q in questions)
    assert "ineinander" in by_key[f"{KEY_PREFIX}:type:verlieben"]["translations"]["en"]["prompt"]

    payload = {
        "meta": {
            "lesson_code": LESSON,
            "course_code": COURSE,
            "cefr_level": "A2",
            "min_count": 1000,
            "generated_count": len(questions),
            "translation_languages": ["en", "es", "fa", "ar", "tr"],
            "source_lesson": "german_grammar_lesson_11.md",
        },
        "questions": questions,
    }
    OUT_PATH.write_text(json.dumps(payload, ensure_ascii=False, indent=2), encoding="utf-8")
    print(f"wrote {OUT_PATH} count={len(questions)}")
    print(Counter(q["topic"] for q in questions))
    print(Counter((q.get("meta") or {}).get("kind") for q in questions))


if __name__ == "__main__":
    main()
