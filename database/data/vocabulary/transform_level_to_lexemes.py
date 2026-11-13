#!/usr/bin/env python3
"""Transform flat CEFR German word lists into dictionary import JSON (A1–C2)."""

from __future__ import annotations

import argparse
import json
import re
import unicodedata
from pathlib import Path

ROOT = Path(__file__).resolve().parent
LEVELS = ("A1", "A2", "B1", "B2", "C1", "C2")

# Category codes must match DictionaryReferenceSeeder (English catalog only).
CATEGORY_MAP = {
    "Familie & Menschen": ["family"],
    "Beziehungsleben & Liebe": ["family", "emotions"],
    "Zahlen & Zeit": ["time_and_numbers"],
    "Zeit & Alltag": ["time_and_numbers"],
    "Farben": ["colors"],
    "Essen & Trinken": ["food"],
    "Verkehr & Reisen": ["travel"],
    "Koerper & Gesundheit": ["body"],
    "Kleidung & Aussehen": ["clothing"],
    "Wohnen & Haushalt": ["home"],
    "Wohnungssuche & Umzug": ["home"],
    "Moebel": ["home"],
    "Arbeit & Beruf": ["work"],
    "Schule & Bildung": ["work"],
    "Natur & Wetter": ["nature"],
    "Tiere & Pflanzen": ["animals", "nature"],
    "Gefuehle & Charakter": ["emotions"],
    "Kommunikation & Sprache": ["greetings"],
    "Einkaufen & Geld": ["work"],
    "Freizeit & Sport": ["travel"],
    "Stadt & Orte": ["travel"],
    "Feste & Traditionen": ["family"],
    "Technik & Medien": ["work"],
    "Sicherheit & Notfaelle": ["body"],
    "Allgemein": ["general"],
    "Kunst & Unterhaltung": ["general"],
    "Aemter & Buerokratie": ["general"],
    "Gesellschaft & Umwelt": ["general"],
    "Recht & Politik": ["general"],
    "Wissenschaft & Medizin": ["body"],
}

DEFAULT_CATEGORY = "general"
PUBLISHED = "published"

CATEGORY_CATALOG = [
    {"code": "family", "display_name": "Family"},
    {"code": "time_and_numbers", "display_name": "Time and numbers"},
    {"code": "colors", "display_name": "Colors"},
    {"code": "food", "display_name": "Food"},
    {"code": "travel", "display_name": "Travel"},
    {"code": "body", "display_name": "Body"},
    {"code": "clothing", "display_name": "Clothing"},
    {"code": "home", "display_name": "Home"},
    {"code": "work", "display_name": "Work"},
    {"code": "nature", "display_name": "Nature"},
    {"code": "animals", "display_name": "Animals"},
    {"code": "emotions", "display_name": "Emotions"},
    {"code": "greetings", "display_name": "Greetings"},
    {"code": "general", "display_name": "General"},
]

GREETING_PHRASES = {
    "guten morgen",
    "guten tag",
    "guten abend",
    "gute nacht",
    "auf wiedersehen",
    "auf wiederhören",
    "bis bald",
    "bis dann",
    "bis später",
    "danke schön",
    "vielen dank",
    "grüß gott",
    "wie bitte",
    "gute besserung",
    "guten appetit",
}

ARTICLE_GENDER = {
    "der": "masculine",
    "die": "feminine",
    "das": "neuter",
}

INDEFINITE = {
    "der": "ein",
    "die": "eine",
    "das": "ein",
}

POSSESSIVE = {
    "der": "mein",
    "die": "meine",
    "das": "mein",
}

NUMBER_WORDS = {
    "zero",
    "one",
    "two",
    "three",
    "four",
    "five",
    "six",
    "seven",
    "eight",
    "nine",
    "ten",
    "eleven",
    "twelve",
    "thirteen",
    "fourteen",
    "fifteen",
    "sixteen",
    "seventeen",
    "eighteen",
    "nineteen",
    "twenty",
    "thirty",
    "forty",
    "fifty",
    "sixty",
    "seventy",
    "eighty",
    "ninety",
    "hundred",
    "thousand",
}


def add_umlaut(word: str) -> str:
    chars = list(word)
    for idx in range(len(chars) - 1, -1, -1):
        if idx + 1 < len(chars) and chars[idx].lower() == "a" and chars[idx + 1].lower() == "u":
            chars[idx] = "Ä" if chars[idx].isupper() else "ä"
            chars[idx + 1] = "u"
            return "".join(chars)
        mapping = {"a": "ä", "o": "ö", "u": "ü", "A": "Ä", "O": "Ö", "U": "Ü"}
        if chars[idx] in mapping:
            chars[idx] = mapping[chars[idx]]
            return "".join(chars)
    return word


def expand_plural(lemma: str, marker: str | None) -> str | None:
    if marker is None:
        return None
    marker = marker.strip()
    if not marker:
        return None

    if not marker.startswith("-") and "̈" not in marker[:1]:
        cleaned = re.sub(r"\s*\(=.*?\)\s*", "", marker).strip()
        if cleaned and cleaned != "-":
            return cleaned

    if marker.startswith("-") and "=" in marker:
        return lemma

    if "/" in marker:
        marker = marker.split("/")[0].strip()

    has_umlaut = "̈" in marker or "¨" in marker
    ending = marker.replace("̈", "").replace("¨", "").lstrip("-").strip()
    stem = add_umlaut(lemma) if has_umlaut else lemma
    return stem + ending


def normalize_lemma_display(raw: str) -> str:
    text = raw.strip()
    text = re.sub(r"^sich\s+", "", text)
    # Drop editorial parentheticals: (sich), (sein), (Sg. ohne Artikel), (= ca.), (2), ...
    text = re.sub(r"\s*\([^)]*\)\s*", " ", text)
    text = re.sub(r"\s+", " ", text).strip()
    if "," in text and not text.lower().startswith(("der", "die", "das")):
        first = text.split(",")[0].strip()
        if first:
            text = first
    if re.match(r"^[\wäöüÄÖÜß]+,\s*[\wäöüÄÖÜß]+$", text):
        text = text.split(",")[0].strip()
    return text.strip(" .")


def parse_german_entry(german: str, pos: str) -> dict:
    raw = german.strip()

    m = re.match(r"^(der|die|das|den|dem|des)\s*\(\d+\)\s*$", raw, flags=re.I)
    if m:
        return {
            "lemma": m.group(1).lower(),
            "article": None,
            "gender": None,
            "plural_form": None,
            "grammar_notes": "Definite article form.",
            "is_noun_like": False,
        }

    m = re.match(
        r"^(der|die|das)/(der|die|das)\s+([^,]+?)(?:,\s*(.*))?$",
        raw,
        flags=re.I,
    )
    if m:
        lemma = normalize_lemma_display(m.group(3))
        plural = expand_plural(lemma, m.group(4))
        return {
            "lemma": lemma,
            "article": None,
            "gender": None,
            "plural_form": plural,
            "grammar_notes": f"Article varies: {m.group(1).lower()}/{m.group(2).lower()}.",
            "is_noun_like": True,
        }

    m = re.match(r"^(der|die|das)\s+([^,]+?)(?:,\s*(.*))?$", raw, flags=re.I)
    if m:
        article = m.group(1).lower()
        lemma = normalize_lemma_display(m.group(2))
        plural_marker = m.group(3)
        notes = None
        if "ohne Artikel" in raw:
            notes = "Often used without article."
            plural_marker = None
        plural = expand_plural(lemma, plural_marker)
        return {
            "lemma": lemma,
            "article": article,
            "gender": ARTICLE_GENDER[article],
            "plural_form": plural,
            "grammar_notes": notes,
            "is_noun_like": True,
        }

    if "Sg. ohne Artikel" in raw or "ohne Artikel" in raw:
        lemma = normalize_lemma_display(raw)
        return {
            "lemma": lemma,
            "article": None,
            "gender": None,
            "plural_form": None,
            "grammar_notes": "Used without article (language name).",
            "is_noun_like": True,
        }

    lemma = normalize_lemma_display(raw)
    reflexive = bool(re.search(r"\bsich\b|\(sich\)", german, flags=re.I))
    notes = ["reflexive"] if reflexive else []

    return {
        "lemma": lemma,
        "article": None,
        "gender": None,
        "plural_form": None,
        "grammar_notes": "; ".join(notes) if notes else None,
        "is_noun_like": False,
        "is_reflexive": reflexive,
    }


def map_pos(word: dict, parsed: dict) -> str | None:
    pos = word["part_of_speech"]
    german = word["german"].strip()
    english = word["english"].lower()

    if pos == "proverb":
        return "interjection"

    if pos == "phrase":
        key = normalize_lemma_display(german).lower()
        if key in GREETING_PHRASES or (
            word["topic_en"] in {"Communication & Language", "Time & Daily Life"}
            and key.startswith(("guten", "gute ", "auf wieder", "bis ", "danke", "vielen"))
        ):
            return "interjection"
        if "ohne Artikel" in german:
            return "noun"
        if key in {"ein", "kein", "euer", "welche", "welcher", "ein bisschen", "ein paar"}:
            return "determiner"
        if any(key.startswith(p) for p in ("zu ", "auf ", "nach ", "von", "bis ", "am ", "als ")):
            return "adverb"
        if word["english"].lower().startswith("to "):
            return "verb"
        return "adverb"

    if pos == "adjective":
        first = english.split(",")[0].strip()
        if first in NUMBER_WORDS or re.fullmatch(r"\d+", first):
            return "numeral"
        if re.fullmatch(
            r"(one|two|three|four|five|six|seven|eight|nine|ten|"
            r"eleven|twelve|thirteen|fourteen|fifteen|sixteen|"
            r"seventeen|eighteen|nineteen|twenty|thirty|forty|"
            r"fifty|sixty|seventy|eighty|ninety|hundred|thousand)"
            r"(-\w+)?",
            first,
        ):
            return "numeral"

    if re.match(r"^(der|die|das|den|dem|des)\s*\(\d+\)\s*$", german, flags=re.I):
        return "determiner"

    return pos


def primary_english(english: str) -> str:
    text = english.split(";")[0].strip()
    text = text.split(",")[0].strip()
    text = re.sub(r"\s*\(.*?\)\s*", "", text).strip()
    return text or english.strip()


def english_infinitive(english: str) -> str:
    text = primary_english(english)
    if text.lower().startswith("to "):
        return text[3:].strip()
    return text


def make_definition(pos: str | None, english: str) -> str:
    gloss = primary_english(english)
    if pos == "verb":
        return f"To {english_infinitive(english)}."
    if pos == "numeral":
        return f"The number {gloss}."
    return f"{gloss[0].upper()}{gloss[1:]}." if gloss else english


def build_example(pos: str | None, lemma: str, article: str | None, english: str, level: str) -> dict:
    gloss = primary_english(english)
    verb = english_infinitive(english)

    if pos == "noun" and article:
        poss = POSSESSIVE[article]
        sentence = f"{poss[0].upper()}{poss[1:]} {lemma} ist hier."
        en = f"My {gloss} is here."
    elif pos == "noun":
        sentence = f"Ich lerne {lemma}."
        en = f"I am learning {gloss}."
    elif pos == "verb":
        sentence = f"Ich will {lemma}."
        en = f"I want to {verb}."
    elif pos == "adjective":
        sentence = f"Das Wetter ist {lemma}."
        en = f"The weather is {gloss}."
    elif pos == "adverb":
        sentence = f"Ich komme {lemma}."
        en = f"I am coming {gloss}."
    elif pos == "preposition":
        if lemma in {"in", "an", "auf", "unter", "neben", "zwischen", "über", "vor", "hinter"}:
            sentence = f"Das Buch liegt {lemma} dem Tisch."
            en = f"The book is {gloss} the table."
        else:
            sentence = f"Ich bin {lemma} der Schule."
            en = f"I am {gloss} school."
    elif pos == "pronoun":
        sentence = f"{lemma.capitalize()} ist hier."
        en = f"{gloss.capitalize()} is here."
    elif pos == "conjunction":
        sentence = f"Ich trinke Tee {lemma} Kaffee."
        en = f"I drink tea {gloss} coffee."
    elif pos == "determiner":
        sentence = f"{lemma.capitalize()} Kind lacht."
        en = f"{gloss.capitalize()} child is laughing."
    elif pos == "numeral":
        sentence = f"Ich habe {lemma} Äpfel."
        en = f"I have {gloss} apples."
    elif pos == "interjection":
        sentence = f"{lemma[0].upper()}{lemma[1:]}!"
        en = f"{gloss[0].upper()}{gloss[1:]}!"
    else:
        sentence = f"Ich lerne das Wort „{lemma}“."
        en = f"I am learning the word “{gloss}”."

    return {
        "language": "de",
        "sentence": sentence,
        "difficulty_level": level,
        "status": PUBLISHED,
        "translations": [
            {
                "language": "en",
                "translation": en,
                "status": PUBLISHED,
            }
        ],
    }


def build_collocations(
    pos: str | None,
    lemma: str,
    article: str | None,
    english: str,
    is_reflexive: bool,
) -> list[dict]:
    gloss = primary_english(english)
    verb = english_infinitive(english)
    items: list[dict] = []

    if pos == "noun" and article:
        poss = POSSESSIVE[article]
        indef = INDEFINITE[article]
        items.append(
            {
                "phrase": f"{poss} {lemma}",
                "pattern_type": "determiner+noun",
                "phrase_type": "collocation",
                "translations": [{"language": "en", "translation": f"my {gloss}"}],
            }
        )
        items.append(
            {
                "phrase": f"{indef} {lemma}",
                "pattern_type": "determiner+noun",
                "phrase_type": "collocation",
                "translations": [{"language": "en", "translation": f"a {gloss}"}],
            }
        )
    elif pos == "noun":
        items.append(
            {
                "phrase": f"ohne {lemma}",
                "pattern_type": "preposition+noun",
                "phrase_type": "collocation",
                "translations": [{"language": "en", "translation": f"without {gloss}"}],
            }
        )
    elif pos == "verb":
        if is_reflexive:
            items.append(
                {
                    "phrase": f"sich {lemma}",
                    "pattern_type": "pronoun+verb",
                    "phrase_type": "collocation",
                    "translations": [{"language": "en", "translation": f"to {verb} (oneself)"}],
                }
            )
        items.append(
            {
                "phrase": f"{lemma} können",
                "pattern_type": "verb+verb",
                "phrase_type": "collocation",
                "translations": [{"language": "en", "translation": f"be able to {verb}"}],
            }
        )
        items.append(
            {
                "phrase": f"jeden Tag {lemma}",
                "pattern_type": "adverbial+verb",
                "phrase_type": "collocation",
                "translations": [{"language": "en", "translation": f"{verb} every day"}],
            }
        )
    elif pos == "adjective":
        items.append(
            {
                "phrase": f"sehr {lemma}",
                "pattern_type": "adverb+adjective",
                "phrase_type": "collocation",
                "translations": [{"language": "en", "translation": f"very {gloss}"}],
            }
        )
        items.append(
            {
                "phrase": f"nicht {lemma}",
                "pattern_type": "particle+adjective",
                "phrase_type": "collocation",
                "translations": [{"language": "en", "translation": f"not {gloss}"}],
            }
        )
    elif pos == "adverb":
        items.append(
            {
                "phrase": f"ganz {lemma}",
                "pattern_type": "adverb+adverb",
                "phrase_type": "collocation",
                "translations": [{"language": "en", "translation": f"quite {gloss}"}],
            }
        )
    elif pos == "preposition":
        items.append(
            {
                "phrase": f"{lemma} dem Haus",
                "pattern_type": "preposition+noun",
                "phrase_type": "collocation",
                "translations": [{"language": "en", "translation": f"{gloss} the house"}],
            }
        )
    elif pos == "interjection":
        items.append(
            {
                "phrase": lemma,
                "pattern_type": "fixed",
                "phrase_type": "fixed_expression",
                "translations": [{"language": "en", "translation": gloss}],
            }
        )
    elif pos == "numeral":
        items.append(
            {
                "phrase": f"{lemma} Mal",
                "pattern_type": "numeral+noun",
                "phrase_type": "collocation",
                "translations": [{"language": "en", "translation": f"{gloss} times"}],
            }
        )
    else:
        items.append(
            {
                "phrase": lemma,
                "pattern_type": "fixed",
                "phrase_type": "fixed_expression",
                "translations": [{"language": "en", "translation": gloss}],
            }
        )

    result = items[:2]
    for item in result:
        item["status"] = PUBLISHED
        for translation in item.get("translations", []):
            translation["status"] = PUBLISHED
    return result


def slugify(lemma: str) -> str:
    text = lemma.lower().replace("ä", "ae").replace("ö", "oe").replace("ü", "ue").replace("ß", "ss")
    text = unicodedata.normalize("NFKD", text)
    text = "".join(ch for ch in text if not unicodedata.combining(ch))
    text = re.sub(r"[^a-z0-9]+", "-", text).strip("-")
    return text or "lexeme"


def make_import_key(*parts: str) -> str:
    cleaned = []
    for part in parts:
        if not part:
            cleaned.append("item")
        elif ":" in part:
            cleaned.append(part)
        else:
            cleaned.append(slugify(part))
    return ":".join(cleaned)


def transform_word(
    word: dict,
    index: int,
    lemma_occurrence: int,
    lemma_total: int,
    level: str,
) -> dict:
    parsed = parse_german_entry(word["german"], word["part_of_speech"])
    pos = map_pos(word, parsed)
    lemma = parsed["lemma"]
    is_reflexive = bool(word.get("reflexive_verb")) or bool(parsed.get("is_reflexive"))
    is_irregular = bool(word.get("irregular_verb"))

    categories = list(CATEGORY_MAP.get(word["topic"], []))
    if pos == "interjection" and normalize_lemma_display(word["german"]).lower() in GREETING_PHRASES:
        if "greetings" not in categories:
            categories = ["greetings"]
    if not categories:
        categories = [DEFAULT_CATEGORY]

    forms: list[dict] = []
    if parsed.get("plural_form") and parsed["plural_form"] != lemma:
        forms.append(
            {
                "form": parsed["plural_form"],
                "form_type": "inflected",
                "is_primary": False,
                "grammatical_features": {"number": "plural"},
            }
        )

    german_noun = None
    if pos == "noun" and (parsed.get("article") or parsed.get("plural_form") or parsed.get("grammar_notes")):
        german_noun = {
            "article": parsed.get("article"),
            "grammatical_gender": parsed.get("gender"),
            "plural_form": parsed.get("plural_form"),
        }
        if not any(german_noun.values()):
            german_noun = None

    grammar_notes = parsed.get("grammar_notes")
    verb_details = None
    if pos == "verb":
        verb_details = {
            "is_irregular": is_irregular,
            "is_reflexive": is_reflexive,
        }
        if is_reflexive:
            grammar_notes = "Reflexive verb." if not grammar_notes else f"{grammar_notes}; reflexive verb."
        if is_irregular:
            grammar_notes = (
                "Irregular verb."
                if not grammar_notes
                else f"{grammar_notes}; irregular verb."
            )

    translations = [
        {
            "language": "en",
            "text": word["english"].strip(),
            "type": "equivalent",
            "sort_order": 0,
            "status": PUBLISHED,
        }
    ]

    disambiguation = None
    if lemma_total > 1:
        disambiguation = f"{pos or 'item'}-{lemma_occurrence}"

    if disambiguation:
        lexeme_import_key = make_import_key("de", lemma, disambiguation)
    else:
        lexeme_import_key = make_import_key("de", lemma)

    # Level-scoped sense keys so A1–C2 entries do not overwrite each other.
    sense_import_key = make_import_key(lexeme_import_key, "sense", level.lower(), "1")

    examples = [build_example(pos, lemma, parsed.get("article"), word["english"], level)]
    for i, example in enumerate(examples, start=1):
        example["import_key"] = make_import_key(sense_import_key, "example", str(i))

    collocations = build_collocations(
        pos,
        lemma,
        parsed.get("article"),
        word["english"],
        is_reflexive,
    )
    for i, collocation in enumerate(collocations, start=1):
        collocation["import_key"] = make_import_key(sense_import_key, "collocation", str(i))

    sense = {
        "import_key": sense_import_key,
        "part_of_speech": pos,
        "definition": make_definition(pos, word["english"]),
        "definition_language": "en",
        "domain": word["topic_en"],
        "usage_label": None,
        "level": level,
        "sort_order": 0,
        "status": PUBLISHED,
        "translations": translations,
        "examples": examples,
        "collocations": collocations,
    }

    if german_noun is not None:
        sense["german_noun"] = german_noun
    if verb_details is not None:
        sense["verb_details"] = verb_details
    if grammar_notes:
        sense["grammar_notes"] = grammar_notes

    return {
        "import_key": lexeme_import_key,
        "language": "de",
        "lemma": lemma,
        "slug": lexeme_import_key.replace(":", "-"),
        "disambiguation": disambiguation,
        "categories": categories,
        "source_topic": word["topic"],
        "source_topic_en": word["topic_en"],
        "source_german": word["german"],
        "status": PUBLISHED,
        "forms": forms,
        "senses": [sense],
    }


def transform_level(level: str) -> Path:
    level = level.upper()
    if level not in LEVELS:
        raise SystemExit(f"Unsupported level [{level}]")

    code = level.lower()
    source = ROOT / f"{code}_german_words.flat.json"
    output = ROOT / f"{code}_german_words.json"

    if not source.exists():
        raise SystemExit(f"Missing flat source: {source}")

    data = json.loads(source.read_text(encoding="utf-8"))

    lemma_counts: dict[str, int] = {}
    for word in data["words"]:
        parsed = parse_german_entry(word["german"], word["part_of_speech"])
        key = parsed["lemma"].lower()
        lemma_counts[key] = lemma_counts.get(key, 0) + 1

    lemma_seen: dict[str, int] = {}
    lexemes = []
    for i, word in enumerate(data["words"]):
        parsed = parse_german_entry(word["german"], word["part_of_speech"])
        key = parsed["lemma"].lower()
        lemma_seen[key] = lemma_seen.get(key, 0) + 1
        lexemes.append(
            transform_word(
                word,
                i + 1,
                lemma_seen[key],
                lemma_counts[key],
                level,
            )
        )

    # Guarantee unique import keys even when lemmas normalize identically.
    seen_keys: dict[str, int] = {}
    for lex in lexemes:
        key = lex["import_key"]
        seen_keys[key] = seen_keys.get(key, 0) + 1
        if seen_keys[key] > 1:
            suffix = str(seen_keys[key])
            lex["import_key"] = f"{key}:{suffix}"
            lex["slug"] = lex["import_key"].replace(":", "-")
            if not lex.get("disambiguation"):
                lex["disambiguation"] = f"variant-{suffix}"
            # Keep nested sense/example/collocation keys aligned with the lexeme key.
            for sense in lex["senses"]:
                old_sense_key = sense["import_key"]
                new_sense_key = make_import_key(lex["import_key"], "sense", level.lower(), "1")
                sense["import_key"] = new_sense_key
                for example in sense.get("examples", []):
                    example["import_key"] = example["import_key"].replace(old_sense_key, new_sense_key, 1)
                for collocation in sense.get("collocations", []):
                    collocation["import_key"] = collocation["import_key"].replace(old_sense_key, new_sense_key, 1)

    keys = [lex["import_key"] for lex in lexemes]
    if len(keys) != len(set(keys)):
        raise SystemExit(f"Duplicate import_key values generated for {level}")

    payload = {
        "meta": {
            "level": level,
            "source_language": "de",
            "definition_language": "en",
            "translation_languages": ["en"],
            "source": data.get("source"),
            "original_count": data.get("count"),
            "lexeme_count": len(lexemes),
            "structure": "lexeme > forms + categories + senses > translations/examples/collocations/german_noun/verb_details/levels",
            "notes": [
                "Ready for import into the dictionary data model (see language-service-spec.md).",
                f"Each entry is one German lexeme with at least one {level} sense.",
                f"Examples and collocations were regenerated as simple {level} learning content.",
                "Persian translations are omitted; English is provided for all senses/examples/collocations.",
                "Categories use English catalog codes only (family, food, general, ...).",
                "Unmapped topics fall back to category code general.",
                "All content statuses are published.",
                "Sense import_key includes CEFR level so levels do not overwrite each other.",
                "Original topic is kept as sense.domain and source_topic metadata.",
            ],
        },
        "category_catalog": CATEGORY_CATALOG,
        "lexemes": lexemes,
    }

    output.write_text(json.dumps(payload, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
    print(f"Wrote {len(lexemes)} lexemes to {output}")
    return output


def main() -> None:
    parser = argparse.ArgumentParser(description="Transform flat CEFR vocabulary JSON into import format")
    parser.add_argument(
        "levels",
        nargs="*",
        default=list(LEVELS),
        help="Levels to transform (default: all A1–C2)",
    )
    args = parser.parse_args()

    selected = [level.upper() for level in args.levels]
    for level in selected:
        transform_level(level)


if __name__ == "__main__":
    main()
