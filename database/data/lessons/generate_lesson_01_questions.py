#!/usr/bin/env python3
"""Regenerate german_grammar_lesson_01_questions.json from A1 vocabulary + lesson rules."""

from __future__ import annotations

import json
import re
from collections import Counter
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
VOCAB_PATH = ROOT / "vocabulary" / "a1_german_words.json"
OUT_PATH = Path(__file__).resolve().parent / "german_grammar_lesson_01_questions.json"

PRONOUNS = ["ich", "du", "er", "sie_sg", "es", "wir", "ihr", "sie_pl", "Sie"]
PRONOUN_DISPLAY = {
    "ich": "ich",
    "du": "du",
    "er": "er",
    "sie_sg": "sie",
    "es": "es",
    "wir": "wir",
    "ihr": "ihr",
    "sie_pl": "sie",
    "Sie": "Sie",
}

IRREGULAR = {
    "sein": {
        "ich": "bin",
        "du": "bist",
        "er": "ist",
        "sie_sg": "ist",
        "es": "ist",
        "wir": "sind",
        "ihr": "seid",
        "sie_pl": "sind",
        "Sie": "sind",
    },
    "haben": {
        "ich": "habe",
        "du": "hast",
        "er": "hat",
        "sie_sg": "hat",
        "es": "hat",
        "wir": "haben",
        "ihr": "habt",
        "sie_pl": "haben",
        "Sie": "haben",
    },
    "möchten": {
        "ich": "möchte",
        "du": "möchtest",
        "er": "möchte",
        "sie_sg": "möchte",
        "es": "möchte",
        "wir": "möchten",
        "ihr": "möchtet",
        "sie_pl": "möchten",
        "Sie": "möchten",
    },
    "mögen": {
        "ich": "mag",
        "du": "magst",
        "er": "mag",
        "sie_sg": "mag",
        "es": "mag",
        "wir": "mögen",
        "ihr": "mögt",
        "sie_pl": "mögen",
        "Sie": "mögen",
    },
    "wissen": {
        "ich": "weiß",
        "du": "weißt",
        "er": "weiß",
        "sie_sg": "weiß",
        "es": "weiß",
        "wir": "wissen",
        "ihr": "wisst",
        "sie_pl": "wissen",
        "Sie": "wissen",
    },
    "tun": {
        "ich": "tue",
        "du": "tust",
        "er": "tut",
        "sie_sg": "tut",
        "es": "tut",
        "wir": "tun",
        "ihr": "tut",
        "sie_pl": "tun",
        "Sie": "tun",
    },
}

STEM_CHANGING = {
    "essen": {
        "ich": "esse",
        "du": "isst",
        "er": "isst",
        "sie_sg": "isst",
        "es": "isst",
        "wir": "essen",
        "ihr": "esst",
        "sie_pl": "essen",
        "Sie": "essen",
    },
    "geben": {
        "ich": "gebe",
        "du": "gibst",
        "er": "gibt",
        "sie_sg": "gibt",
        "es": "gibt",
        "wir": "geben",
        "ihr": "gebt",
        "sie_pl": "geben",
        "Sie": "geben",
    },
    "helfen": {
        "ich": "helfe",
        "du": "hilfst",
        "er": "hilft",
        "sie_sg": "hilft",
        "es": "hilft",
        "wir": "helfen",
        "ihr": "helft",
        "sie_pl": "helfen",
        "Sie": "helfen",
    },
    "lesen": {
        "ich": "lese",
        "du": "liest",
        "er": "liest",
        "sie_sg": "liest",
        "es": "liest",
        "wir": "lesen",
        "ihr": "lest",
        "sie_pl": "lesen",
        "Sie": "lesen",
    },
    "nehmen": {
        "ich": "nehme",
        "du": "nimmst",
        "er": "nimmt",
        "sie_sg": "nimmt",
        "es": "nimmt",
        "wir": "nehmen",
        "ihr": "nehmt",
        "sie_pl": "nehmen",
        "Sie": "nehmen",
    },
    "sehen": {
        "ich": "sehe",
        "du": "siehst",
        "er": "sieht",
        "sie_sg": "sieht",
        "es": "sieht",
        "wir": "sehen",
        "ihr": "seht",
        "sie_pl": "sehen",
        "Sie": "sehen",
    },
    "sprechen": {
        "ich": "spreche",
        "du": "sprichst",
        "er": "spricht",
        "sie_sg": "spricht",
        "es": "spricht",
        "wir": "sprechen",
        "ihr": "sprecht",
        "sie_pl": "sprechen",
        "Sie": "sprechen",
    },
    "vergessen": {
        "ich": "vergesse",
        "du": "vergisst",
        "er": "vergisst",
        "sie_sg": "vergisst",
        "es": "vergisst",
        "wir": "vergessen",
        "ihr": "vergesst",
        "sie_pl": "vergessen",
        "Sie": "vergessen",
    },
    "werden": {
        "ich": "werde",
        "du": "wirst",
        "er": "wird",
        "sie_sg": "wird",
        "es": "wird",
        "wir": "werden",
        "ihr": "werdet",
        "sie_pl": "werden",
        "Sie": "werden",
    },
    "fahren": {
        "ich": "fahre",
        "du": "fährst",
        "er": "fährt",
        "sie_sg": "fährt",
        "es": "fährt",
        "wir": "fahren",
        "ihr": "fahrt",
        "sie_pl": "fahren",
        "Sie": "fahren",
    },
    "laufen": {
        "ich": "laufe",
        "du": "läufst",
        "er": "läuft",
        "sie_sg": "läuft",
        "es": "läuft",
        "wir": "laufen",
        "ihr": "lauft",
        "sie_pl": "laufen",
        "Sie": "laufen",
    },
    "schlafen": {
        "ich": "schlafe",
        "du": "schläfst",
        "er": "schläft",
        "sie_sg": "schläft",
        "es": "schläft",
        "wir": "schlafen",
        "ihr": "schlaft",
        "sie_pl": "schlafen",
        "Sie": "schlafen",
    },
    "tragen": {
        "ich": "trage",
        "du": "trägst",
        "er": "trägt",
        "sie_sg": "trägt",
        "es": "trägt",
        "wir": "tragen",
        "ihr": "tragt",
        "sie_pl": "tragen",
        "Sie": "tragen",
    },
    "waschen": {
        "ich": "wasche",
        "du": "wäschst",
        "er": "wäscht",
        "sie_sg": "wäscht",
        "es": "wäscht",
        "wir": "waschen",
        "ihr": "wascht",
        "sie_pl": "waschen",
        "Sie": "waschen",
    },
    "gefallen": {
        "ich": "gefalle",
        "du": "gefällst",
        "er": "gefällt",
        "sie_sg": "gefällt",
        "es": "gefällt",
        "wir": "gefallen",
        "ihr": "gefallt",
        "sie_pl": "gefallen",
        "Sie": "gefallen",
    },
    "backen": {
        "ich": "backe",
        "du": "bäckst",
        "er": "bäckt",
        "sie_sg": "bäckt",
        "es": "bäckt",
        "wir": "backen",
        "ihr": "backt",
        "sie_pl": "backen",
        "Sie": "backen",
    },
    "halten": {
        "ich": "halte",
        "du": "hältst",
        "er": "hält",
        "sie_sg": "hält",
        "es": "hält",
        "wir": "halten",
        "ihr": "haltet",
        "sie_pl": "halten",
        "Sie": "halten",
    },
    "lassen": {
        "ich": "lasse",
        "du": "lässt",
        "er": "lässt",
        "sie_sg": "lässt",
        "es": "lässt",
        "wir": "lassen",
        "ihr": "lasst",
        "sie_pl": "lassen",
        "Sie": "lassen",
    },
    "fallen": {
        "ich": "falle",
        "du": "fällst",
        "er": "fällt",
        "sie_sg": "fällt",
        "es": "fällt",
        "wir": "fallen",
        "ihr": "fallt",
        "sie_pl": "fallen",
        "Sie": "fallen",
    },
    "brechen": {
        "ich": "breche",
        "du": "brichst",
        "er": "bricht",
        "sie_sg": "bricht",
        "es": "bricht",
        "wir": "brechen",
        "ihr": "brecht",
        "sie_pl": "brechen",
        "Sie": "brechen",
    },
    "treffen": {
        "ich": "treffe",
        "du": "triffst",
        "er": "trifft",
        "sie_sg": "trifft",
        "es": "trifft",
        "wir": "treffen",
        "ihr": "trefft",
        "sie_pl": "treffen",
        "Sie": "treffen",
    },
}

# Extra stem/base paradigms used by separable compounds.
STEM_CHANGING.update(
    {
        "laden": {
            "ich": "lade",
            "du": "lädst",
            "er": "lädt",
            "sie_sg": "lädt",
            "es": "lädt",
            "wir": "laden",
            "ihr": "ladet",
            "sie_pl": "laden",
            "Sie": "laden",
        },
        "fangen": {
            "ich": "fange",
            "du": "fängst",
            "er": "fängt",
            "sie_sg": "fängt",
            "es": "fängt",
            "wir": "fangen",
            "ihr": "fangt",
            "sie_pl": "fangen",
            "Sie": "fangen",
        },
    }
)

# Separable prefixes, longest first. Do NOT include short ambiguous "da".
SEPARABLE_PREFIXES = (
    "zurück",
    "zusammen",
    "vorbei",
    "weiter",
    "zurecht",
    "dazu",
    "statt",
    "fern",
    "fest",
    "heim",
    "frei",
    "los",
    "mit",
    "nach",
    "vor",
    "weg",
    "ab",
    "an",
    "auf",
    "aus",
    "bei",
    "ein",
    "her",
    "hin",
    "um",
    "zu",
)

# True inseparable / false-split lemmas.
NEVER_SEPARABLE = {
    "antworten",
    "untersuchen",
    "unterschreiben",
    "unterstreichen",
    "überweisen",
    "übernachten",
    "überlegen",
    "unternehmen",
    "lossein",
    "zuhaben",
    "danken",
    "dauern",
}

# Base verbs allowed even if they are not standalone A1 lemmas.
KNOWN_SEPARABLE_BASES = {
    "bereiten",
    "räumen",
    "hängen",
    "tauschen",
    "schicken",
    "steigen",
    "packen",
    "spielen",
    "finden",
    "kreuzen",
    "leihen",
    "ordnen",
    "rufen",
    "fragen",
    "stellen",
    "füllen",
    "wechseln",
    "fangen",
    "hören",
    "passen",
    "schreiben",
    "wählen",
    "sammeln",
    "drücken",
    "bringen",
    "gehören",
    "sehen",
    "nehmen",
    "laden",
    "kommen",
    "fahren",
    "machen",
    "gehen",
    "ziehen",
    "legen",
    "stehen",
    "schlafen",
    "kaufen",
    "haben",
    "sein",
    "geben",
    "lesen",
    "sprechen",
    "fallen",
    "holen",
    "rufen",
}

# Manual overrides when automatic split is insufficient.
SEPARABLE_OVERRIDES: dict[str, dict[str, str]] = {
    "dazugehören": {
        "ich": "gehöre dazu",
        "du": "gehörst dazu",
        "er": "gehört dazu",
        "sie_sg": "gehört dazu",
        "es": "gehört dazu",
        "wir": "gehören dazu",
        "ihr": "gehört dazu",
        "sie_pl": "gehören dazu",
        "Sie": "gehören dazu",
    },
}

# Populated in main() from vocabulary lemmas.
ALLOWED_SEPARABLE_BASES: set[str] = set()

PROMPT = {
    "en": {
        "conj": 'Conjugate "{lemma}" for {pronoun}: {pronoun} ___.',
        "pronoun_obj": 'Which pronoun replaces "{article} {noun}"?',
        "pronoun_people": 'Which German pronoun means "{meaning}"?',
    },
    "es": {
        "conj": 'Conjuga "{lemma}" para {pronoun}: {pronoun} ___.',
        "pronoun_obj": '¿Qué pronombre reemplaza a "{article} {noun}"?',
        "pronoun_people": '¿Qué pronombre alemán significa "{meaning}"?',
    },
    "fa": {
        "conj": 'فعل "{lemma}" را برای {pronoun} صرف کنید: {pronoun} ___.',
        "pronoun_obj": 'کدام ضمیر جایگزین "{article} {noun}" می‌شود؟',
        "pronoun_people": 'کدام ضمیر آلمانی یعنی "{meaning}"؟',
    },
    "ar": {
        "conj": 'صرّف الفعل "{lemma}" مع {pronoun}: {pronoun} ___.',
        "pronoun_obj": 'أي ضمير يحل محل "{article} {noun}"؟',
        "pronoun_people": 'أي ضمير ألماني يعني "{meaning}"؟',
    },
    "tr": {
        "conj": '"{lemma}" fiilini {pronoun} için çekimleyin: {pronoun} ___.',
        "pronoun_obj": '"{article} {noun}" ifadesinin yerini hangi zamir alır?',
        "pronoun_people": '"{meaning}" anlamına gelen Almanca zamir hangisidir?',
    },
}

PEOPLE_MEANINGS = {
    "ich": {"en": "I", "es": "yo", "fa": "من", "ar": "أنا", "tr": "ben"},
    "du": {
        "en": "you (informal singular)",
        "es": "tú (informal)",
        "fa": "تو (غیررسمی)",
        "ar": "أنت (غير رسمي)",
        "tr": "sen (tekil/samimi)",
    },
    "er": {"en": "he", "es": "él", "fa": "او (مذکر)", "ar": "هو", "tr": "o (erkek)"},
    "sie": {
        "en": "she / they",
        "es": "ella / ellos",
        "fa": "او (مؤنث) / آنها",
        "ar": "هي / هم",
        "tr": "o (kadın) / onlar",
    },
    "es": {
        "en": "it (neuter)",
        "es": "ello (neutro)",
        "fa": "آن (خنثی)",
        "ar": "هو/هي (محايد)",
        "tr": "o (nötr)",
    },
    "wir": {"en": "we", "es": "nosotros", "fa": "ما", "ar": "نحن", "tr": "biz"},
    "ihr": {
        "en": "you all (informal)",
        "es": "vosotros (informal)",
        "fa": "شما (جمع غیررسمی)",
        "ar": "أنتم (غير رسمي)",
        "tr": "siz (çoğul/samimi)",
    },
    "Sie": {
        "en": "you (formal)",
        "es": "usted / ustedes",
        "fa": "شما (رسمی)",
        "ar": "حضرتك",
        "tr": "siz (resmi)",
    },
}

ARTICLE_TO_PRONOUN = {"der": "er", "die": "sie", "das": "es"}


def get_stem(infinitive: str) -> str:
    if infinitive.endswith("en"):
        return infinitive[:-2]
    if infinitive.endswith("n"):
        return infinitive[:-1]
    return infinitive


def conjugate_regular(infinitive: str, pronoun: str) -> str | None:
    if " " in infinitive:
        return None
    stem = get_stem(infinitive)
    ends_td = stem.endswith(("t", "d")) or stem.endswith(("tm", "dm", "chn", "ffn", "gn", "dn"))
    ends_s_sound = bool(re.search(r"(s|ß|x|z|sch)$", stem))

    if pronoun == "ich":
        # -eln / -ern: ich wechsle, ich sammle (not wechsele / sammele)
        if stem.endswith(("el", "er")) and len(stem) > 2:
            return stem[:-2] + stem[-1] + "e"
        return stem + "e"
    if pronoun == "du":
        if ends_td:
            return stem + "est"
        if ends_s_sound:
            return stem + "t"
        return stem + "st"
    if pronoun in ("er", "sie_sg", "es"):
        if ends_td:
            return stem + "et"
        return stem + "t"
    if pronoun in ("wir", "sie_pl", "Sie"):
        return infinitive if infinitive.endswith("n") else stem + "en"
    if pronoun == "ihr":
        if ends_td:
            return stem + "et"
        return stem + "t"
    return None


def simple_paradigm(lemma: str) -> dict[str, str] | None:
    """Conjugate a non-separable lemma (irregular / stem-changing / regular)."""
    if lemma in IRREGULAR:
        return dict(IRREGULAR[lemma])
    if lemma in STEM_CHANGING:
        return dict(STEM_CHANGING[lemma])
    forms: dict[str, str] = {}
    for pronoun in PRONOUNS:
        form = conjugate_regular(lemma, pronoun)
        if form is None:
            return None
        forms[pronoun] = form
    return forms


def split_separable(lemma: str) -> tuple[str, str] | None:
    """Return (prefix, base) only when the base is a real conjugatable verb."""
    if lemma in NEVER_SEPARABLE or lemma in IRREGULAR or lemma in STEM_CHANGING:
        return None
    if lemma in SEPARABLE_OVERRIDES:
        return None

    for prefix in SEPARABLE_PREFIXES:
        if not lemma.startswith(prefix) or len(lemma) <= len(prefix) + 2:
            continue
        base = lemma[len(prefix) :]
        if not base.endswith(("en", "ern", "eln")) or len(base) < 3:
            continue
        # Reject junk splits like danken -> da + nken.
        if base not in ALLOWED_SEPARABLE_BASES and base not in IRREGULAR and base not in STEM_CHANGING:
            continue
        if simple_paradigm(base) is None:
            continue
        return prefix, base
    return None


def paradigm_for(lemma: str) -> dict[str, str] | None:
    if lemma in SEPARABLE_OVERRIDES:
        return dict(SEPARABLE_OVERRIDES[lemma])

    split = split_separable(lemma)
    if split is not None:
        prefix, base = split
        base_forms = simple_paradigm(base)
        if base_forms is None:
            return None
        return {pronoun: f"{base_forms[pronoun]} {prefix}" for pronoun in PRONOUNS}

    return simple_paradigm(lemma)


def is_separable_lemma(lemma: str) -> bool:
    return lemma in SEPARABLE_OVERRIDES or split_separable(lemma) is not None


def expected_separable_answers() -> dict[str, dict[str, str]]:
    """Canonical answers used for regression checks."""
    return {
        "aussehen": {
            "ich": "sehe aus",
            "du": "siehst aus",
            "er": "sieht aus",
            "ihr": "seht aus",
        },
        "freihaben": {
            "ich": "habe frei",
            "du": "hast frei",
            "er": "hat frei",
            "wir": "haben frei",
            "ihr": "habt frei",
        },
        "anfangen": {
            "ich": "fange an",
            "du": "fängst an",
            "er": "fängt an",
        },
        "einladen": {
            "du": "lädst ein",
            "er": "lädt ein",
        },
        "mitnehmen": {
            "du": "nimmst mit",
            "er": "nimmt mit",
        },
        "aufstehen": {
            "ich": "stehe auf",
            "du": "stehst auf",
            "er": "steht auf",
        },
        "einkaufen": {
            "ich": "kaufe ein",
            "er": "kauft ein",
        },
        "stattfinden": {
            "es": "findet statt",
            "sie_pl": "finden statt",
        },
        "fernsehen": {
            "ich": "sehe fern",
            "du": "siehst fern",
        },
        "anrufen": {
            "ich": "rufe an",
            "er": "ruft an",
        },
        "zurückkommen": {
            "ich": "komme zurück",
            "du": "kommst zurück",
        },
        "zusammenpassen": {
            "wir": "passen zusammen",
            "sie_pl": "passen zusammen",
        },
        "dazugehören": {
            "ich": "gehöre dazu",
            "er": "gehört dazu",
        },
        "antworten": {
            "ich": "antworte",
            "du": "antwortest",
            "er": "antwortet",
        },
        "danken": {
            "ich": "danke",
            "du": "dankst",
            "er": "dankt",
        },
        "abwechseln": {
            "ich": "wechsle ab",
            "du": "wechselst ab",
            "er": "wechselt ab",
        },
        "einsammeln": {
            "ich": "sammle ein",
            "du": "sammelst ein",
            "er": "sammelt ein",
        },
    }


def fused_wrong_form(answer: str) -> str | None:
    """Turn 'sehe aus' into the common learner mistake 'aussehe'."""
    parts = answer.split(" ", 1)
    if len(parts) != 2:
        return None
    finite, prefix = parts
    return f"{prefix}{finite}"


def distractors_for(correct: str, pool: list[str], n: int = 3) -> list[str]:
    opts: list[str] = []
    wrong_fused = fused_wrong_form(correct)
    if wrong_fused and wrong_fused != correct:
        opts.append(wrong_fused)
    for candidate in pool:
        if candidate != correct and candidate not in opts:
            opts.append(candidate)
        if len(opts) >= n:
            break
    i = 0
    while len(opts) < n:
        cand = correct + ("e" if i % 2 == 0 else "t")
        if cand != correct and cand not in opts:
            opts.append(cand)
        i += 1
        if i > 10:
            opts.append(f"x{i}")
    return opts[:n]


def main() -> None:
    vocab = json.loads(VOCAB_PATH.read_text(encoding="utf-8"))
    verbs: list[dict] = []
    nouns: list[dict] = []
    for lexeme in vocab["lexemes"]:
        lemma = lexeme["lemma"]
        for sense in lexeme.get("senses", []):
            pos = sense.get("part_of_speech")
            if pos == "verb" and " " not in lemma and "-" not in lemma:
                verbs.append(
                    {
                        "lemma": lemma,
                        "import_key": sense.get("import_key"),
                    }
                )
            elif pos == "noun":
                german_noun = sense.get("german_noun") or {}
                if german_noun.get("article") in ("der", "die", "das"):
                    nouns.append(
                        {
                            "lemma": lemma,
                            "article": german_noun["article"],
                            "import_key": sense.get("import_key"),
                        }
                    )

    seen: set[str] = set()
    uniq_verbs: list[dict] = []
    for verb in verbs:
        if verb["lemma"] not in seen:
            seen.add(verb["lemma"])
            uniq_verbs.append(verb)

    global ALLOWED_SEPARABLE_BASES
    ALLOWED_SEPARABLE_BASES = {
        *(verb["lemma"] for verb in uniq_verbs),
        *KNOWN_SEPARABLE_BASES,
        *IRREGULAR.keys(),
        *STEM_CHANGING.keys(),
    }

    questions: list[dict] = []

    def add_q(
        import_key: str,
        topic: str,
        lemma: str,
        answer: str,
        distractors: list[str],
        translations: dict,
        sense_key: str | None = None,
        meta: dict | None = None,
    ) -> None:
        questions.append(
            {
                "import_key": import_key,
                "topic": topic,
                "source_lemma": lemma,
                "sense_import_key": sense_key,
                "answer": answer,
                "distractors": distractors,
                "blank_token": "___",
                "meta": meta or {},
                "translations": translations,
            }
        )

    for pronoun, meanings in PEOPLE_MEANINGS.items():
        pool = ["ich", "du", "er", "sie", "es", "wir", "ihr", "Sie"]
        distractors = distractors_for(pronoun, [p for p in pool if p != pronoun])
        translations = {
            lang: {"prompt": templates["pronoun_people"].format(meaning=meanings[lang])}
            for lang, templates in PROMPT.items()
        }
        add_q(
            f"de-grammar-a1:l01:pronoun:people:{pronoun}",
            "pronouns",
            pronoun,
            pronoun,
            distractors,
            translations,
            meta={"kind": "people_pronoun"},
        )

    for noun in nouns[:400]:
        article = noun["article"]
        answer = ARTICLE_TO_PRONOUN[article]
        distractors = distractors_for(answer, ["er", "sie", "es", "wir"])
        translations = {
            lang: {
                "prompt": templates["pronoun_obj"].format(article=article, noun=noun["lemma"])
            }
            for lang, templates in PROMPT.items()
        }
        add_q(
            f"de-grammar-a1:l01:pronoun:object:{noun['lemma']}",
            "pronouns",
            noun["lemma"],
            answer,
            distractors,
            translations,
            noun.get("import_key"),
            {"article": article, "kind": "object_pronoun"},
        )

    for verb in uniq_verbs:
        lemma = verb["lemma"]
        paradigm = paradigm_for(lemma)
        if not paradigm:
            continue
        split = split_separable(lemma)
        separable = lemma in SEPARABLE_OVERRIDES or split is not None
        base = split[1] if split is not None else lemma
        if lemma in IRREGULAR:
            topic = "irregular_verbs"
        elif lemma in STEM_CHANGING or base in STEM_CHANGING:
            topic = "stem_changing"
        elif separable:
            topic = "separable_verbs"
        else:
            topic = "regular_conjugation"
        forms_list = list(dict.fromkeys(paradigm.values()))
        for pronoun in PRONOUNS:
            answer = paradigm[pronoun]
            display = PRONOUN_DISPLAY[pronoun]
            distractors = distractors_for(
                answer,
                [paradigm[other] for other in PRONOUNS if other != pronoun] + forms_list,
            )
            translations = {
                lang: {"prompt": templates["conj"].format(lemma=lemma, pronoun=display)}
                for lang, templates in PROMPT.items()
            }
            add_q(
                f"de-grammar-a1:l01:conj:{lemma}:{pronoun}",
                topic,
                lemma,
                answer,
                distractors,
                translations,
                verb.get("import_key"),
                {
                    "pronoun": pronoun,
                    "separable": separable,
                },
            )

    # Step-by-step regression checks for separable / non-separable edge cases.
    by_key = {q["import_key"]: q["answer"] for q in questions}
    for lemma, expected in expected_separable_answers().items():
        for pronoun, answer in expected.items():
            key = f"de-grammar-a1:l01:conj:{lemma}:{pronoun}"
            actual = by_key.get(key)
            assert actual == answer, f"{key}: expected {answer!r}, got {actual!r}"

    # No fused separable answers like "aussehe" / "freihabt" for known separable verbs.
    for q in questions:
        if not (q.get("meta") or {}).get("separable"):
            continue
        answer = q["answer"]
        assert " " in answer, f"separable answer missing particle split: {q['import_key']}={answer!r}"
        fused = answer.replace(" ", "")
        assert answer != fused

    # Spot-check that false splits never produced particle answers.
    for lemma in ("antworten", "danken", "dauern", "untersuchen"):
        for pronoun in ("ich", "du", "er"):
            key = f"de-grammar-a1:l01:conj:{lemma}:{pronoun}"
            if key in by_key:
                assert " " not in by_key[key], f"{key} should not be separable: {by_key[key]!r}"

    payload = {
        "meta": {
            "lesson_code": "pronouns-and-present-tense",
            "course_code": "de-grammar-a1",
            "min_count": 1000,
            "generated_count": len(questions),
            "translation_languages": ["en", "es", "fa", "ar", "tr"],
            "source_vocabulary": "a1_german_words.json",
            "source_lesson": "german_grammar_lesson_01.md",
        },
        "questions": questions,
    }
    OUT_PATH.write_text(json.dumps(payload, ensure_ascii=False, indent=2), encoding="utf-8")
    print(f"wrote {OUT_PATH} count={len(questions)}")
    print(Counter(question["topic"] for question in questions))


if __name__ == "__main__":
    main()
