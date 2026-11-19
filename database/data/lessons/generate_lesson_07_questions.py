#!/usr/bin/env python3
"""Regenerate german_grammar_lesson_07_questions.json for possessives, welch-, dies- (A2)."""

from __future__ import annotations

import json
from collections import Counter
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
VOCAB_PATH = ROOT / "vocabulary" / "a1_german_words.json"
OUT_PATH = Path(__file__).resolve().parent / "german_grammar_lesson_07_questions.json"

COURSE = "de-grammar-a1"
LESSON = "possessives-welcher-dieser"
KEY_PREFIX = f"{COURSE}:l07"

PROMPT = {
    "en": {
        "stem": 'Which possessive stem belongs to "{owner}"?',
        "poss_form": 'Choose the correct possessive: {frame}',
        "euer": "Choose the correct euer-form: {frame}",
        "owner": "Choose the correct possessive for the owner: {frame}",
        "welch": "Choose the correct welch-form: {frame}",
        "dies": "Choose the correct dies-form: {frame}",
        "ending_meta": 'Which ending does {stem}- take for {article} {lemma} in {case}?',
        "standalone": "Choose the correct standalone form: {frame}",
    },
    "es": {
        "stem": '¿Qué raíz posesiva corresponde a "{owner}"?',
        "poss_form": "Elige el posesivo correcto: {frame}",
        "euer": "Elige la forma correcta de euer-: {frame}",
        "owner": "Elige el posesivo correcto según el dueño: {frame}",
        "welch": "Elige la forma correcta de welch-: {frame}",
        "dies": "Elige la forma correcta de dies-: {frame}",
        "ending_meta": "¿Qué terminación lleva {stem}- con {article} {lemma} en {case}?",
        "standalone": "Elige la forma independiente correcta: {frame}",
    },
    "fa": {
        "stem": 'کدام ریشهٔ ملکی به "{owner}" تعلق دارد؟',
        "poss_form": "شکل ملکی درست را انتخاب کنید: {frame}",
        "euer": "شکل درست euer- را انتخاب کنید: {frame}",
        "owner": "با توجه به مالک، ملکی درست را انتخاب کنید: {frame}",
        "welch": "شکل درست welch- را انتخاب کنید: {frame}",
        "dies": "شکل درست dies- را انتخاب کنید: {frame}",
        "ending_meta": "برای {stem}- با {article} {lemma} در {case} کدام پایان درست است؟",
        "standalone": "شکل مستقل درست را انتخاب کنید: {frame}",
    },
    "ar": {
        "stem": 'أي جذر ملكية يخص "{owner}"؟',
        "poss_form": "اختر صيغة الملكية الصحيحة: {frame}",
        "euer": "اختر صيغة euer- الصحيحة: {frame}",
        "owner": "اختر الملكية الصحيحة حسب المالك: {frame}",
        "welch": "اختر صيغة welch- الصحيحة: {frame}",
        "dies": "اختر صيغة dies- الصحيحة: {frame}",
        "ending_meta": "أي نهاية يأخذها {stem}- مع {article} {lemma} في {case}؟",
        "standalone": "اختر الصيغة المستقلة الصحيحة: {frame}",
    },
    "tr": {
        "stem": '"{owner}" için hangi iyelik gövdesi gelir?',
        "poss_form": "Doğru iyelik biçimini seçin: {frame}",
        "euer": "Doğru euer- biçimini seçin: {frame}",
        "owner": "Sahibe göre doğru iyelik biçimini seçin: {frame}",
        "welch": "Doğru welch- biçimini seçin: {frame}",
        "dies": "Doğru dies- biçimini seçin: {frame}",
        "ending_meta": "{stem}- ile {article} {lemma} için {case} halinde hangi ek gelir?",
        "standalone": "Doğru bağımsız biçimi seçin: {frame}",
    },
}

OWNER_STEMS = [
    ("ich", "mein-", ["dein-", "sein-", "ihr-"]),
    ("du", "dein-", ["mein-", "sein-", "ihr-"]),
    ("er", "sein-", ["ihr-", "mein-", "dein-"]),
    ("es", "sein-", ["ihr-", "mein-", "dein-"]),
    ("sie (she)", "ihr-", ["sein-", "mein-", "Ihr-"]),
    ("wir", "unser-", ["euer-", "mein-", "ihr-"]),
    ("ihr (you pl.)", "euer-", ["unser-", "ihr-", "dein-"]),
    ("sie (they)", "ihr-", ["sein-", "unser-", "Ihr-"]),
    ("Sie (formal)", "Ihr-", ["ihr-", "sein-", "euer-"]),
]

# (key, frame, answer, wrong)
OWNER_FRAMES = [
    ("joerg-tasche", "Das ist Jörg und ___ Tasche. (die Tasche)", "seine", ["ihr", "ihre", "sein"]),
    ("jasmin-laptop", "Das ist Jasmin und ___ Laptop. (der Laptop)", "ihr", ["seine", "sein", "ihre"]),
    ("joerg-kind", "Das ist Jörg und ___ Kind. (das Kind)", "sein", ["ihr", "seine", "ihre"]),
    ("jasmin-freundin", "Das ist Jasmin und ___ Freundin. (die Freundin)", "ihre", ["seine", "sein", "ihr"]),
    ("paar-auto", "Das sind Tim und Tom und ___ Auto. (das Auto)", "ihr", ["seine", "sein", "Ihre"]),
    ("frau-dorn", "Frau Dorn, wo ist ___ Auto? (formal)", "Ihr", ["ihr", "sein", "euer"]),
    ("lisa-grosseltern", "Lisa besucht ___ Großeltern.", "ihre", ["seine", "Ihre", "eure"]),
]

EUER_FRAMES = [
    ("euer-auto", "Ist das ___ Auto? (ihr / you pl., Nominativ neuter)", "euer", ["eure", "euren", "eurem"]),
    ("eure-kinder", "Wo sind ___ Kinder? (ihr / you pl., Nominativ plural)", "eure", ["euer", "euren", "eurem"]),
    ("euren-vater", "Ich sehe ___ Vater. (ihr / you pl., Akkusativ masculine)", "euren", ["euer", "eure", "eurem"]),
    ("eurem-kind", "Ich helfe ___ Kind. (ihr / you pl., Dativ neuter)", "eurem", ["euren", "eure", "euer"]),
    ("eurer-mutter", "Ich helfe ___ Mutter. (ihr / you pl., Dativ feminine)", "eurer", ["eurem", "euren", "eure"]),
    ("euren-eltern", "Ich helfe ___ Eltern. (ihr / you pl., Dativ plural)", "euren", ["eure", "eurem", "eurer"]),
]

WELCH_FRAMES = [
    ("welch-nom-m", "___ Mantel gefällt dir? (der Mantel, Nominativ)", "Welcher", ["Welches", "Welche", "Welchen"]),
    ("welch-nom-n", "___ Kleid gefällt dir? (das Kleid, Nominativ)", "Welches", ["Welcher", "Welche", "Welchen"]),
    ("welch-nom-f", "___ Tasche gefällt dir? (die Tasche, Nominativ)", "Welche", ["Welcher", "Welches", "Welchen"]),
    ("welch-nom-pl", "___ Schuhe gefallen dir? (die Schuhe, Nominativ)", "Welche", ["Welcher", "Welches", "Welchen"]),
    ("welch-akk-m", "___ Mantel kaufst du? (der Mantel, Akkusativ)", "Welchen", ["Welcher", "Welches", "Welche"]),
    ("welch-akk-n", "___ Buch liest du? (das Buch, Akkusativ)", "Welches", ["Welcher", "Welchen", "Welche"]),
    ("welch-akk-f", "___ Jacke magst du? (die Jacke, Akkusativ)", "Welche", ["Welcher", "Welches", "Welchen"]),
    ("welch-dat-m", "Mit ___ Freund gehst du? (der Freund, Dativ)", "welchem", ["welcher", "welchen", "welche"]),
    ("welch-dat-n", "Zu ___ Kind gehörst du? (das Kind, Dativ)", "welchem", ["welcher", "welchen", "welche"]),
    ("welch-dat-f", "Mit ___ Freundin gehst du? (die Freundin, Dativ)", "welcher", ["welchem", "welchen", "welche"]),
    ("welch-dat-pl", "Von ___ Freunden kommt das? (die Freunde, Dativ)", "welchen", ["welchem", "welcher", "welche"]),
    ("welch-arzt", "Zu ___ Arzt gehst du? (der Arzt, Dativ)", "welchem", ["welcher", "welchen", "welche"]),
]

DIES_FRAMES = [
    ("dies-nom-m", "___ Mantel ist schön. (der Mantel, Nominativ)", "Dieser", ["Dieses", "Diese", "Diesen"]),
    ("dies-nom-n", "___ Kleid ist schön. (das Kleid, Nominativ)", "Dieses", ["Dieser", "Diese", "Diesen"]),
    ("dies-nom-f", "___ Jacke ist schön. (die Jacke, Nominativ)", "Diese", ["Dieser", "Dieses", "Diesen"]),
    ("dies-akk-m", "Ich mag ___ Film. (der Film, Akkusativ)", "diesen", ["dieser", "dieses", "diese"]),
    ("dies-akk-n", "Ich lese ___ Buch. (das Buch, Akkusativ)", "dieses", ["diesen", "dieser", "diese"]),
    ("dies-akk-f", "Ich finde ___ Tasche schöner. (die Tasche, Akkusativ)", "diese", ["dieses", "diesen", "dieser"]),
    ("dies-dat-m", "Mit ___ Mann spreche ich. (der Mann, Dativ)", "diesem", ["dieser", "diesen", "diese"]),
    ("dies-dat-f", "Mit ___ Frau spreche ich. (die Frau, Dativ)", "dieser", ["diesem", "diesen", "diese"]),
    ("dies-dat-pl", "Bei ___ Leuten bleibe ich. (die Leute, Dativ)", "diesen", ["diesem", "dieser", "diese"]),
    ("dies-wo", "Auf ___ Platz bleiben wir. (der Platz, Wo?/Dativ)", "diesem", ["diesen", "dieser", "dieses"]),
]

STANDALONE_FRAMES = [
    ("mantel", "Welcher Mantel? — ___!", "Dieser", ["Dieses", "Diese", "Diesen"]),
    ("kleid", "Welches Kleid? — ___!", "Dieses", ["Dieser", "Diese", "Diesen"]),
    ("tasche", "Welche Tasche? — ___!", "Diese", ["Dieser", "Dieses", "Diesen"]),
    ("schuhe", "Welche Schuhe? — ___!", "Diese", ["Dieser", "Dieses", "Diesen"]),
]

POSS_STEMS = ["mein", "dein", "sein", "ihr", "unser", "Ihr"]

SKIP_LEMMAS = {
    "Beisl",
    "Bekannte",
    "Erwachsene",
    "Angehörige",
    # metalanguage / odd possessive targets
    "Akkusativ",
    "Adjektiv",
    "Artikel",
    "Artikelbild",
    "Aussagesatz",
    "Anrede",
    "Anredeformel",
    "Alphabet",
    "Absender",
    "Abschnitt",
    "Achtung",
    "Assoziation",
    "Angabe",
    "Aussprache",
    "Aussage",
    "Anweisung",
    "Aufforderung",
    "Nominativ",
    "Dativ",
    "Genitiv",
    "Plural",
    "Singular",
    "April",
    "August",
    "März",
    "Mai",
    "Juni",
    "Juli",
    "September",
    "Oktober",
    "November",
    "Dezember",
    "Januar",
    "Februar",
}

# Everyday A2 nouns only (no alphabetical filler from metalanguage lemmas).
EVERYDAY_NOUNS = {
    "Vater", "Mutter", "Kind", "Freund", "Freundin", "Mann", "Frau", "Auto",
    "Haus", "Wohnung", "Tasche", "Jacke", "Mantel", "Kleid", "Buch", "Film",
    "Laptop", "Handy", "Hund", "Katze", "Lehrer", "Lehrerin", "Arzt", "Bruder",
    "Schwester", "Sohn", "Tochter", "Familie", "Tisch", "Stuhl", "Park",
    "Schule", "Arbeit", "Stadt", "Land", "Platz", "Anzug", "Hemd", "Hose",
    "Apfel", "Apfelsaft", "Bäckerei", "Café", "Computer", "Fahrrad",
    "Fenster", "Flugzeug", "Garten", "Geld", "Geschäft", "Glas", "Hotel",
    "Kaffee", "Kamera", "Kino", "Küche", "Laden", "Lampe", "Mittagessen",
    "Name", "Papier", "Problem", "Radio", "Restaurant", "Schlüssel", "Sofa",
    "Straße", "Student", "Studentin", "Telefon", "Ticket", "Uhr", "Urlaub",
    "Wohnzimmer", "Zimmer", "Zug", "Bus", "Bahnsteig", "Brief", "Zeitung",
}

PLURAL_LEMMAS = ["Eltern", "Kinder", "Freunde", "Bücher", "Schuhe", "Leute"]


def is_plurale_tantum(lexeme: dict) -> bool:
    source = lexeme.get("source_german") or ""
    return "(Pl" in source or "Pl.)" in source


def distractors_for(correct: str, pool: list[str], n: int = 3, *, ignore_case: bool = True) -> list[str]:
    opts: list[str] = []
    seen: set[str] = {correct.casefold() if ignore_case else correct}
    for candidate in pool:
        text = str(candidate).strip()
        if not text:
            continue
        key = text.casefold() if ignore_case else text
        if key in seen:
            continue
        # reject synthetic numeric junk early if somehow present
        if text.endswith(("-1", "-2", "-3")):
            continue
        opts.append(text)
        seen.add(key)
        if len(opts) >= n:
            break
    if len(opts) < n:
        raise RuntimeError(f"Could not build distractors for {correct!r} from {pool!r}")
    return opts[:n]


def kein_ending(article: str, case: str, *, plural: bool = False) -> str:
    """Return ending attached to kein-/mein- style stems ('' means bare stem)."""
    if plural:
        return {"nom": "e", "akk": "e", "dat": "en"}[case]
    table = {
        "nom": {"der": "", "das": "", "die": "e"},
        "akk": {"der": "en", "das": "", "die": "e"},
        "dat": {"der": "em", "das": "em", "die": "er"},
    }
    return table[case][article]


def poss_form(stem: str, article: str, case: str, *, plural: bool = False) -> str:
    ending = kein_ending(article, case, plural=plural)
    return f"{stem}{ending}" if ending else stem


def euer_form(article: str, case: str, *, plural: bool = False) -> str:
    """euer drops inner -e- before endings: eure/euren/eurem/eurer."""
    ending = kein_ending(article, case, plural=plural)
    if not ending:
        return "euer"
    return f"eur{ending}"


def welch_dies_form(base: str, article: str, case: str, *, capitalize: bool = False, plural: bool = False) -> str:
    """base is 'welch' or 'dies'. Endings mirror definite articles."""
    if plural:
        endings = {"nom": "e", "akk": "e", "dat": "en"}
        form = f"{base}{endings[case]}"
    else:
        endings = {
            "nom": {"der": "er", "das": "es", "die": "e"},
            "akk": {"der": "en", "das": "es", "die": "e"},
            "dat": {"der": "em", "das": "em", "die": "er"},
        }
        form = f"{base}{endings[case][article]}"
    return form[:1].upper() + form[1:] if capitalize else form


def welch_dies_frame(lemma: str, article: str, case: str, *, plural: bool = False) -> tuple[str, bool]:
    """Return (frame, capitalize). Blank is sentence-initial only in Nominativ."""
    label = {"nom": "Nominativ", "akk": "Akkusativ", "dat": "Dativ"}[case]
    noun_hint = f"die {lemma} (Plural)" if plural else f"{article} {lemma}"
    if case == "nom":
        return f"___ {lemma} gefällt mir. ({noun_hint}, {label})", True
    if case == "akk":
        return f"Ich mag ___ {lemma}. ({noun_hint}, {label})", False
    return f"Mit ___ {lemma} plane ich. ({noun_hint}, {label})", False


def main() -> None:
    vocab = json.loads(VOCAB_PATH.read_text(encoding="utf-8"))
    nouns: list[dict] = []
    seen: set[str] = set()
    for lexeme in vocab["lexemes"]:
        lemma = lexeme["lemma"]
        if lemma in seen or lemma in SKIP_LEMMAS or " " in lemma or "-" in lemma:
            continue
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

    by_lemma = {n["lemma"]: n for n in nouns}
    case_nouns = [by_lemma[lemma] for lemma in sorted(EVERYDAY_NOUNS) if lemma in by_lemma]
    if len(case_nouns) < 40:
        raise RuntimeError(f"Need ≥40 everyday nouns in vocab, got {len(case_nouns)}")

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
        *,
        allow_case_variants: bool = False,
    ) -> None:
        clean = distractors_for(answer, distractors, n=3, ignore_case=not allow_case_variants)
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

    # 1) Owner → stem
    for owner, stem, wrong in OWNER_STEMS:
        key = owner.replace(" ", "-").replace("(", "").replace(")", "").replace(".", "")
        add_q(
            f"{KEY_PREFIX}:stem:{key}",
            "possessives",
            stem,
            stem,
            wrong,
            {lang: {"prompt": t["stem"].format(owner=owner)} for lang, t in PROMPT.items()},
            {"kind": "poss_stem"},
            allow_case_variants=True,
        )

    # 2) Curated owner / euer / welch / dies / standalone
    for key, frame, answer, wrong in OWNER_FRAMES:
        add_q(
            f"{KEY_PREFIX}:owner:{key}",
            "possessives",
            key,
            answer,
            wrong,
            {lang: {"prompt": t["owner"].format(frame=frame)} for lang, t in PROMPT.items()},
            {"kind": "owner_frame"},
            allow_case_variants=True,
        )
    for key, frame, answer, wrong in EUER_FRAMES:
        add_q(
            f"{KEY_PREFIX}:euer:{key}",
            "possessives",
            key,
            answer,
            wrong,
            {lang: {"prompt": t["euer"].format(frame=frame)} for lang, t in PROMPT.items()},
            {"kind": "euer_form"},
        )
    for key, frame, answer, wrong in WELCH_FRAMES:
        add_q(
            f"{KEY_PREFIX}:welch:{key}",
            "welch_dies",
            key,
            answer,
            wrong,
            {lang: {"prompt": t["welch"].format(frame=frame)} for lang, t in PROMPT.items()},
            {"kind": "welch_frame"},
        )
    for key, frame, answer, wrong in DIES_FRAMES:
        add_q(
            f"{KEY_PREFIX}:dies:{key}",
            "welch_dies",
            key,
            answer,
            wrong,
            {lang: {"prompt": t["dies"].format(frame=frame)} for lang, t in PROMPT.items()},
            {"kind": "dies_frame"},
        )
    for key, frame, answer, wrong in STANDALONE_FRAMES:
        add_q(
            f"{KEY_PREFIX}:alone:{key}",
            "welch_dies",
            key,
            answer,
            wrong,
            {lang: {"prompt": t["standalone"].format(frame=frame)} for lang, t in PROMPT.items()},
            {"kind": "standalone"},
        )

    # 3) Possessive morphology from vocab: stem × case × noun (same-stem distractors only)
    cases = ("nom", "akk", "dat")
    case_label = {"nom": "Nominativ", "akk": "Akkusativ", "dat": "Dativ"}
    rival_articles = ("der", "das", "die")

    def same_stem_poss_pool(stem: str, article: str, case: str, *, plural: bool = False) -> list[str]:
        pool = [
            poss_form(stem, article, other, plural=plural)
            for other in cases
            if other != case
        ]
        for other_article in rival_articles:
            if other_article == article and not plural:
                continue
            pool.append(poss_form(stem, other_article, case, plural=False))
        if plural:
            pool.append(poss_form(stem, article, case, plural=False))
        pool.extend([f"{stem}en", f"{stem}em", f"{stem}er", f"{stem}e", stem])
        return pool

    for noun in case_nouns:
        lemma = noun["lemma"]
        article = noun["article"]
        for stem in POSS_STEMS:
            for case in cases:
                answer = poss_form(stem, article, case)
                pool = same_stem_poss_pool(stem, article, case)
                frame = (
                    f"___ {lemma} ({article} {lemma}, {case_label[case]}, "
                    f"form of {stem}-)"
                )
                add_q(
                    f"{KEY_PREFIX}:poss:{stem}:{case}:{lemma}",
                    "possessives",
                    lemma,
                    answer,
                    pool,
                    {
                        lang: {"prompt": t["poss_form"].format(frame=frame)}
                        for lang, t in PROMPT.items()
                    },
                    {"kind": "poss_morph", "stem": stem, "case": case, "article": article},
                    noun.get("import_key"),
                )

    # Plural possessive endings (mein/dein/… + e / e / en)
    for lemma in PLURAL_LEMMAS:
        for stem in POSS_STEMS:
            for case in cases:
                answer = poss_form(stem, "die", case, plural=True)
                pool = same_stem_poss_pool(stem, "die", case, plural=True)
                frame = f"___ {lemma} (die {lemma}, Plural, {case_label[case]}, form of {stem}-)"
                add_q(
                    f"{KEY_PREFIX}:poss:{stem}:{case}:pl:{lemma}",
                    "possessives",
                    lemma,
                    answer,
                    pool,
                    {
                        lang: {"prompt": t["poss_form"].format(frame=frame)}
                        for lang, t in PROMPT.items()
                    },
                    {"kind": "poss_plural", "stem": stem, "case": case, "plural": True},
                )

    # euer morphology (eur- before endings)
    for noun in case_nouns:
        lemma = noun["lemma"]
        article = noun["article"]
        for case in cases:
            answer = euer_form(article, case)
            pool = [
                euer_form(article, other)
                for other in cases
                if other != case
            ]
            for other_article in rival_articles:
                if other_article != article:
                    pool.append(euer_form(other_article, case))
            pool.extend(["euer", "eure", "euren", "eurem", "eurer", "euere", "euern"])
            frame = f"___ {lemma} ({article} {lemma}, {case_label[case]}, ihr / you pl.)"
            add_q(
                f"{KEY_PREFIX}:euer:{case}:{lemma}",
                "possessives",
                lemma,
                answer,
                pool,
                {
                    lang: {"prompt": t["euer"].format(frame=frame)}
                    for lang, t in PROMPT.items()
                },
                {"kind": "euer_morph", "case": case, "article": article},
                noun.get("import_key"),
            )

    # 4) welch-/dies- morphology with mid-sentence frames (no Welcher/welcher collisions)
    def wd_pool(base: str, article: str, case: str, *, capitalize: bool, plural: bool = False) -> list[str]:
        pool = [
            welch_dies_form(base, article, other, capitalize=capitalize, plural=plural)
            for other in cases
            if other != case
        ]
        for other_article in rival_articles:
            if plural or other_article == article:
                continue
            pool.append(welch_dies_form(base, other_article, case, capitalize=capitalize))
        if plural:
            pool.append(welch_dies_form(base, "die", case, capitalize=capitalize, plural=False))
        # same casing as the answer — never mix sentence-case rivals
        raw = [f"{base}er", f"{base}es", f"{base}en", f"{base}em", f"{base}e"]
        pool.extend([(r[:1].upper() + r[1:]) if capitalize else r for r in raw])
        return pool

    for noun in case_nouns:
        lemma = noun["lemma"]
        article = noun["article"]
        for base in ("welch", "dies"):
            for case in cases:
                frame, capitalize = welch_dies_frame(lemma, article, case)
                answer = welch_dies_form(base, article, case, capitalize=capitalize)
                pool = wd_pool(base, article, case, capitalize=capitalize)
                prompt_key = "welch" if base == "welch" else "dies"
                add_q(
                    f"{KEY_PREFIX}:{base}:{case}:{lemma}",
                    "welch_dies",
                    lemma,
                    answer,
                    pool,
                    {
                        lang: {"prompt": t[prompt_key].format(frame=frame)}
                        for lang, t in PROMPT.items()
                    },
                    {
                        "kind": f"{base}_morph",
                        "case": case,
                        "article": article,
                        "capitalize": capitalize,
                    },
                    noun.get("import_key"),
                )

    for lemma in PLURAL_LEMMAS:
        for base in ("welch", "dies"):
            for case in cases:
                frame, capitalize = welch_dies_frame(lemma, "die", case, plural=True)
                answer = welch_dies_form(base, "die", case, capitalize=capitalize, plural=True)
                pool = wd_pool(base, "die", case, capitalize=capitalize, plural=True)
                prompt_key = "welch" if base == "welch" else "dies"
                add_q(
                    f"{KEY_PREFIX}:{base}:{case}:pl:{lemma}",
                    "welch_dies",
                    lemma,
                    answer,
                    pool,
                    {
                        lang: {"prompt": t[prompt_key].format(frame=frame)}
                        for lang, t in PROMPT.items()
                    },
                    {
                        "kind": f"{base}_plural",
                        "case": case,
                        "plural": True,
                        "capitalize": capitalize,
                    },
                )

    by_key = {q["import_key"]: q for q in questions}
    expected = {
        f"{KEY_PREFIX}:stem:ich": "mein-",
        f"{KEY_PREFIX}:stem:Sie-formal": "Ihr-",
        f"{KEY_PREFIX}:owner:joerg-tasche": "seine",
        f"{KEY_PREFIX}:owner:jasmin-laptop": "ihr",
        f"{KEY_PREFIX}:euer:eure-kinder": "eure",
        f"{KEY_PREFIX}:euer:euren-vater": "euren",
        f"{KEY_PREFIX}:welch:welch-nom-m": "Welcher",
        f"{KEY_PREFIX}:welch:welch-dat-f": "welcher",
        f"{KEY_PREFIX}:dies:dies-akk-m": "diesen",
        f"{KEY_PREFIX}:alone:mantel": "Dieser",
        f"{KEY_PREFIX}:poss:mein:akk:Vater": "meinen",
        f"{KEY_PREFIX}:poss:mein:dat:Mutter": "meiner",
        f"{KEY_PREFIX}:poss:mein:nom:Kind": "mein",
        f"{KEY_PREFIX}:poss:mein:dat:pl:Eltern": "meinen",
        f"{KEY_PREFIX}:euer:akk:Vater": "euren",
        f"{KEY_PREFIX}:euer:nom:Kind": "euer",
        f"{KEY_PREFIX}:welch:nom:Mantel": "Welcher",
        f"{KEY_PREFIX}:welch:akk:Mantel": "welchen",
        f"{KEY_PREFIX}:dies:akk:Film": "diesen",
        f"{KEY_PREFIX}:dies:dat:Frau": "dieser",
        f"{KEY_PREFIX}:welch:nom:pl:Schuhe": "Welche",
    }
    for key, answer in expected.items():
        actual = by_key.get(key, {}).get("answer")
        assert actual == answer, f"{key}: expected {answer!r}, got {actual!r}"

    for question in questions:
        assert question["answer"] not in question["distractors"]
        assert len(question["distractors"]) == 3
        assert len(set(question["distractors"])) == 3
        meta = question.get("meta") or {}
        # ihr vs Ihr is intentional in stem/owner drills; morph must never collide by case only.
        if meta.get("kind") not in {"poss_stem", "owner_frame"}:
            lowers = [question["answer"].casefold()] + [d.casefold() for d in question["distractors"]]
            assert len(lowers) == len(set(lowers)), f"case-only option collision: {question['import_key']}"
        for lang in ("en", "es", "fa", "ar", "tr"):
            assert question["translations"][lang]["prompt"].strip()
        blob = " ".join(t["prompt"] for t in question["translations"].values())
        for token in ("PDF", "Page ", "Section ", "Sixth lesson", "handwritten", "Exercise ", "Seite "):
            assert token not in blob, f"forbidden token in {question['import_key']}: {token}"
        if meta.get("kind") == "poss_morph":
            stem = meta["stem"]
            for option in [question["answer"], *question["distractors"]]:
                assert option.casefold().startswith(stem.casefold()), (
                    f"rival-stem distractor in {question['import_key']}: {option}"
                )
        if meta.get("kind") in {"welch_morph", "dies_morph", "welch_plural", "dies_plural"}:
            if meta.get("case") == "nom":
                assert question["answer"][:1].isupper(), question["import_key"]
            else:
                assert question["answer"][:1].islower(), question["import_key"]

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
            "source_lesson": "german_grammar_lesson_07.md",
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
