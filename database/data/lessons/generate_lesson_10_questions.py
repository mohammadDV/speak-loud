#!/usr/bin/env python3
"""Regenerate german_grammar_lesson_10_questions.json for Perfekt & participles (A2)."""

from __future__ import annotations

import json
from collections import Counter
from pathlib import Path

OUT_PATH = Path(__file__).resolve().parent / "german_grammar_lesson_10_questions.json"

COURSE = "de-grammar-a1"
LESSON = "perfekt-and-participles"
KEY_PREFIX = f"{COURSE}:l10"

PROMPT = {
    "en": {
        "aux": "Choose the correct auxiliary (*haben* / *sein*): {frame}",
        "part": "Choose the correct past participle: {frame}",
        "conj": "Choose the correct auxiliary form: {frame}",
        "tense": "Which tense do Germans usually prefer here? {frame}",
        "form": "Choose the correct Perfekt sentence: {frame}",
        "type": "Which participle rule fits “{verb}”?",
        "rule": "Which statement is true?",
        "blank": "Choose the correct form: {frame}",
    },
    "es": {
        "aux": "Elige el auxiliar correcto (*haben* / *sein*): {frame}",
        "part": "Elige el participio correcto: {frame}",
        "conj": "Elige la forma correcta del auxiliar: {frame}",
        "tense": "¿Qué tiempo suele preferirse aquí? {frame}",
        "form": "Elige la frase en Perfekt correcta: {frame}",
        "type": "¿Qué regla de participio corresponde a “{verb}”?",
        "rule": "¿Cuál afirmación es correcta?",
        "blank": "Elige la forma correcta: {frame}",
    },
    "fa": {
        "aux": "فعل کمکی درست را انتخاب کنید (*haben* / *sein*): {frame}",
        "part": "اسم مفعول درست را انتخاب کنید: {frame}",
        "conj": "شکل درست فعل کمکی را انتخاب کنید: {frame}",
        "tense": "اینجا معمولاً کدام زمان ترجیح داده می‌شود؟ {frame}",
        "form": "جملهٔ Perfekt درست را انتخاب کنید: {frame}",
        "type": "قاعدهٔ اسم مفعول برای «{verb}» کدام است؟",
        "rule": "کدام گزاره درست است؟",
        "blank": "شکل درست را انتخاب کنید: {frame}",
    },
    "ar": {
        "aux": "اختر الفعل المساعد الصحيح (*haben* / *sein*): {frame}",
        "part": "اختر اسم المفعول الصحيح: {frame}",
        "conj": "اختر صيغة الفعل المساعد الصحيحة: {frame}",
        "tense": "أي زمن يُفضَّل عادةً هنا؟ {frame}",
        "form": "اختر جملة Perfekt الصحيحة: {frame}",
        "type": "أي قاعدة لاسم المفعول تناسب “{verb}”؟",
        "rule": "أي عبارة صحيحة؟",
        "blank": "اختر الصيغة الصحيحة: {frame}",
    },
    "tr": {
        "aux": "Doğru yardımcı fiili seçin (*haben* / *sein*): {frame}",
        "part": "Doğru ortaç biçimini seçin: {frame}",
        "conj": "Doğru yardımcı fiil biçimini seçin: {frame}",
        "tense": "Burada Almanlar genelde hangi zamanı tercih eder? {frame}",
        "form": "Doğru Perfekt cümlesini seçin: {frame}",
        "type": "“{verb}” için hangi ortaç kuralı geçerli?",
        "rule": "Hangi ifade doğrudur?",
        "blank": "Doğru biçimi seçin: {frame}",
    },
}

TYPE_LABELS = {
    "en": {
        "regular": "Regular: ge- + stem + -t",
        "irregular": "Irregular: ge- + stem + -en",
        "separable": "Separable: prefix + ge + verb",
        "inseparable": "Inseparable prefix: no ge-",
        "ieren": "*-ieren*: no ge-, ends in -t",
        "mixed": "Mixed: vowel change + -t",
    },
    "es": {
        "regular": "Regular: ge- + raíz + -t",
        "irregular": "Irregular: ge- + raíz + -en",
        "separable": "Separable: prefijo + ge + verbo",
        "inseparable": "Inseparable: sin ge-",
        "ieren": "*-ieren*: sin ge-, termina en -t",
        "mixed": "Mixto: cambio de vocal + -t",
    },
    "fa": {
        "regular": "منظم: ge- + بن + -t",
        "irregular": "نامنظم: ge- + بن + -en",
        "separable": "جداشدنی: پیشوند + ge + فعل",
        "inseparable": "جدانشدنی: بدون ge-",
        "ieren": "*-ieren*: بدون ge-، پایان -t",
        "mixed": "مختلط: تغییر واکه + -t",
    },
    "ar": {
        "regular": "منتظم: ge- + جذر + -t",
        "irregular": "غير منتظم: ge- + جذر + -en",
        "separable": "منفصل: سابقة + ge + فعل",
        "inseparable": "غير منفصل: بدون ge-",
        "ieren": "*-ieren*: بدون ge- وينتهي بـ -t",
        "mixed": "مختلط: تغيّر حرف علة + -t",
    },
    "tr": {
        "regular": "Düzenli: ge- + gövde + -t",
        "irregular": "Düzensiz: ge- + gövde + -en",
        "separable": "Ayrılabilen: önek + ge + fiil",
        "inseparable": "Ayrılamayan: ge- yok",
        "ieren": "*-ieren*: ge- yok, -t ile biter",
        "mixed": "Karma: ünlü değişimi + -t",
    },
}

# (lemma, participle, aux, type)
VERBS: list[tuple[str, str, str, str]] = [
    ("arbeiten", "gearbeitet", "haben", "regular"),
    ("kaufen", "gekauft", "haben", "regular"),
    ("spielen", "gespielt", "haben", "regular"),
    ("machen", "gemacht", "haben", "regular"),
    ("lernen", "gelernt", "haben", "regular"),
    ("wohnen", "gewohnt", "haben", "regular"),
    ("kochen", "gekocht", "haben", "regular"),
    ("hören", "gehört", "haben", "regular"),
    ("sagen", "gesagt", "haben", "regular"),
    ("fragen", "gefragt", "haben", "regular"),
    ("holen", "geholt", "haben", "regular"),
    ("suchen", "gesucht", "haben", "regular"),
    ("warten", "gewartet", "haben", "regular"),
    ("öffnen", "geöffnet", "haben", "regular"),
    ("schließen", "geschlossen", "haben", "irregular"),
    ("tanzen", "getanzt", "haben", "regular"),
    ("putzen", "geputzt", "haben", "regular"),
    ("rauchen", "geraucht", "haben", "regular"),
    ("baden", "gebadet", "haben", "regular"),
    ("duschen", "geduscht", "haben", "regular"),
    ("trinken", "getrunken", "haben", "irregular"),
    ("essen", "gegessen", "haben", "irregular"),
    ("sehen", "gesehen", "haben", "irregular"),
    ("lesen", "gelesen", "haben", "irregular"),
    ("schreiben", "geschrieben", "haben", "irregular"),
    ("schlafen", "geschlafen", "haben", "irregular"),
    ("nehmen", "genommen", "haben", "irregular"),
    ("geben", "gegeben", "haben", "irregular"),
    ("helfen", "geholfen", "haben", "irregular"),
    ("sprechen", "gesprochen", "haben", "irregular"),
    ("finden", "gefunden", "haben", "irregular"),
    ("treffen", "getroffen", "haben", "irregular"),
    ("beginnen", "begonnen", "haben", "inseparable"),
    ("singen", "gesungen", "haben", "irregular"),
    ("schwimmen", "geschwommen", "sein", "irregular"),
    ("waschen", "gewaschen", "haben", "irregular"),
    ("tragen", "getragen", "haben", "irregular"),
    ("fahren", "gefahren", "sein", "irregular"),
    ("fliegen", "geflogen", "sein", "irregular"),
    ("laufen", "gelaufen", "sein", "irregular"),
    ("gehen", "gegangen", "sein", "irregular"),
    ("kommen", "gekommen", "sein", "irregular"),
    ("bleiben", "geblieben", "sein", "irregular"),
    ("sein", "gewesen", "sein", "irregular"),
    ("fallen", "gefallen", "sein", "irregular"),
    ("aufstehen", "aufgestanden", "sein", "separable"),
    ("einschlafen", "eingeschlafen", "sein", "separable"),
    ("aufwachen", "aufgewacht", "sein", "separable"),
    ("einsteigen", "eingestiegen", "sein", "separable"),
    ("aussteigen", "ausgestiegen", "sein", "separable"),
    ("zurückkommen", "zurückgekommen", "sein", "separable"),
    ("werden", "geworden", "sein", "irregular"),
    ("passieren", "passiert", "sein", "ieren"),
    ("sterben", "gestorben", "sein", "irregular"),
    ("wachsen", "gewachsen", "sein", "irregular"),
    ("einkaufen", "eingekauft", "haben", "separable"),
    ("aufräumen", "aufgeräumt", "haben", "separable"),
    ("anrufen", "angerufen", "haben", "separable"),
    ("fernsehen", "ferngesehen", "haben", "separable"),
    ("mitbringen", "mitgebracht", "haben", "separable"),
    ("zumachen", "zugemacht", "haben", "separable"),
    ("aufmachen", "aufgemacht", "haben", "separable"),
    ("vorbereiten", "vorbereitet", "haben", "separable"),
    ("abholen", "abgeholt", "haben", "separable"),
    ("verstehen", "verstanden", "haben", "inseparable"),
    ("besuchen", "besucht", "haben", "inseparable"),
    ("erklären", "erklärt", "haben", "inseparable"),
    ("erzählen", "erzählt", "haben", "inseparable"),
    ("besichtigen", "besichtigt", "haben", "inseparable"),
    ("bekommen", "bekommen", "haben", "inseparable"),
    ("vergessen", "vergessen", "haben", "inseparable"),
    ("verlieren", "verloren", "haben", "inseparable"),
    ("vermissen", "vermisst", "haben", "inseparable"),
    ("empfehlen", "empfohlen", "haben", "inseparable"),
    ("entschuldigen", "entschuldigt", "haben", "inseparable"),
    ("gehören", "gehört", "haben", "inseparable"),
    ("zerstören", "zerstört", "haben", "inseparable"),
    ("telefonieren", "telefoniert", "haben", "ieren"),
    ("studieren", "studiert", "haben", "ieren"),
    ("probieren", "probiert", "haben", "ieren"),
    ("reservieren", "reserviert", "haben", "ieren"),
    ("organisieren", "organisiert", "haben", "ieren"),
    ("fotografieren", "fotografiert", "haben", "ieren"),
    ("diskutieren", "diskutiert", "haben", "ieren"),
    ("informieren", "informiert", "haben", "ieren"),
    ("reparieren", "repariert", "haben", "ieren"),
    ("präsentieren", "präsentiert", "haben", "ieren"),
    ("denken", "gedacht", "haben", "mixed"),
    ("bringen", "gebracht", "haben", "mixed"),
    ("kennen", "gekannt", "haben", "mixed"),
    ("wissen", "gewusst", "haben", "mixed"),
    ("nennen", "genannt", "haben", "mixed"),
    ("rennen", "gerannt", "sein", "mixed"),
]

_seen: set[str] = set()
UNIQUE_VERBS: list[tuple[str, str, str, str]] = []
for row in VERBS:
    if row[0] in _seen:
        continue
    _seen.add(row[0])
    UNIQUE_VERBS.append(row)

HABEN_CONJ = {
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
}

SEIN_CONJ = {
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
}

PRONOUNS = list(HABEN_CONJ.keys())

SUBJECT = {
    "ich": "ich",
    "du": "du",
    "er": "er",
    "sie (she)": "sie (she)",
    "es": "das Kind",
    "wir": "wir",
    "ihr": "ihr",
    "sie (they)": "sie (they)",
    "Sie (formal)": "Sie (formal)",
    "man": "man",
}

MARKERS = ["Gestern", "Vor einer Stunde", "Letztes Jahr", "Heute Morgen", "Am Wochenende"]

# Material after the subject (before participle) — keeps frames natural & unambiguous.
# Special lemmas handled separately: passieren, gehören.
COMPLEMENT: dict[str, str] = {
    "arbeiten": "im Büro",
    "kaufen": "Brot",
    "spielen": "Tennis",
    "machen": "Hausaufgaben",
    "lernen": "Deutsch",
    "wohnen": "in Berlin",
    "kochen": "Spaghetti",
    "hören": "Musik",
    "sagen": "die Wahrheit",
    "fragen": "den Lehrer",
    "holen": "Brot",
    "suchen": "den Schlüssel",
    "warten": "auf den Bus",
    "öffnen": "das Fenster",
    "schließen": "die Tür",
    "tanzen": "die ganze Nacht",
    "putzen": "die Küche",
    "rauchen": "eine Zigarette",
    "baden": "im See",
    "duschen": "schnell",
    "trinken": "einen Kaffee",
    "essen": "Pizza",
    "sehen": "einen Film",
    "lesen": "ein Buch",
    "schreiben": "eine E-Mail",
    "schlafen": "lange",
    "nehmen": "ein Taxi",
    "geben": "ihm das Buch",
    "helfen": "dem Freund",
    "sprechen": "mit Anna",
    "finden": "den Schlüssel",
    "treffen": "einen Freund",
    "beginnen": "um acht Uhr",
    "singen": "ein Lied",
    "schwimmen": "im See",
    "waschen": "das Auto",
    "tragen": "eine Tasche",
    "fahren": "nach Berlin",
    "fliegen": "nach Spanien",
    "laufen": "zum Bahnhof",
    "gehen": "nach Hause",
    "kommen": "spät",
    "bleiben": "zu Hause",
    "sein": "krank",
    "fallen": "auf die Straße",
    "aufstehen": "früh",
    "einschlafen": "schnell",
    "aufwachen": "um sieben",
    "einsteigen": "in den Bus",
    "aussteigen": "am Bahnhof",
    "zurückkommen": "spät",
    "werden": "Arzt",
    "sterben": "im Jahr 1900",
    "wachsen": "schnell",
    "einkaufen": "im Supermarkt",
    "aufräumen": "das Zimmer",
    "anrufen": "die Mutter",
    "fernsehen": "den ganzen Abend",
    "mitbringen": "Kuchen",
    "zumachen": "das Fenster",
    "aufmachen": "die Tür",
    "vorbereiten": "das Essen",
    "abholen": "die Kinder",
    "verstehen": "die Frage",
    "besuchen": "die Oma",
    "erklären": "die Regel",
    "erzählen": "eine Geschichte",
    "besichtigen": "den Palast",
    "bekommen": "einen Brief",
    "vergessen": "den Schlüssel",
    "verlieren": "das Handy",
    "vermissen": "die Familie",
    "empfehlen": "das Restaurant",
    "zerstören": "das Haus",
    "telefonieren": "mit dem Chef",
    "studieren": "Medizin",
    "probieren": "die Suppe",
    "reservieren": "einen Tisch",
    "organisieren": "eine Party",
    "fotografieren": "die Stadt",
    "diskutieren": "das Problem",
    "informieren": "die Gäste",
    "reparieren": "das Fahrrad",
    "präsentieren": "das Projekt",
    "denken": "an dich",
    "bringen": "Blumen",
    "kennen": "den Mann",
    "wissen": "die Antwort",
    "nennen": "den Namen",
    "rennen": "zum Bus",
}

# Verbs that must not use a normal person-agent "ich habe X" pattern.
SPECIAL_LEMMAS = {"passieren", "gehören"}

PRAETERITUM_PREFERRED = [
    ("sein", "war", "bin gewesen"),
    ("haben", "hatte", "habe gehabt"),
    ("müssen", "musste", "habe gemusst"),
    ("können", "konnte", "habe gekonnt"),
    ("wollen", "wollte", "habe gewollt"),
    ("dürfen", "durfte", "habe gedurft"),
    ("sollen", "sollte", "habe gesollt"),
    ("wissen", "wusste", "habe gewusst"),
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


def wrong_participle(lemma: str, part: str, vtype: str) -> list[str]:
    stem = lemma
    if lemma.endswith("en"):
        stem = lemma[:-2]
    elif lemma.endswith("n"):
        stem = lemma[:-1]

    candidates = [
        f"ge{stem}t",
        f"ge{stem}en",
        f"{stem}t",
        f"{stem}en",
        part.replace("ge", "", 1) if part.startswith("ge") else f"ge{part}",
        f"ge{lemma}",
    ]
    if vtype == "separable":
        candidates.extend([f"ge{lemma}", lemma.replace("en", "t", 1)])
    if vtype == "ieren":
        candidates.extend([f"ge{stem}t", f"ge{lemma[:-2]}t", f"{stem}en"])
    if vtype == "inseparable":
        candidates.extend([f"ge{part}", f"ge{stem}t", f"ge{stem}en"])
    if vtype == "mixed":
        candidates.extend([f"ge{stem}en", f"ge{stem}t"])
    for _, other, _, _ in UNIQUE_VERBS:
        if other != part:
            candidates.append(other)
            if len(candidates) > 24:
                break
    return candidates


def subject_label(pronoun: str) -> str:
    if pronoun == "Sie (formal)":
        return "Sie"
    return SUBJECT[pronoun]


REFLEXIVE = {
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


def mid_phrase(lemma: str, pronoun: str = "ich") -> str:
    if lemma == "entschuldigen":
        return REFLEXIVE[pronoun]
    return COMPLEMENT.get(lemma, "")


def aux_blank_frame(marker: str, pronoun: str, lemma: str, part: str, question: bool = False) -> str:
    mid = mid_phrase(lemma, pronoun)
    subj = subject_label(pronoun)
    if pronoun == "Sie (formal)" and question:
        if mid:
            return f"{marker} ___ Sie {mid} {part}? ({lemma})"
        return f"{marker} ___ Sie {part}? ({lemma})"
    if mid:
        return f"{marker} ___ {subj} {mid} {part}. ({lemma})"
    return f"{marker} ___ {subj} {part}. ({lemma})"


def part_blank_frame(aux_form: str, pronoun: str, lemma: str) -> str:
    mid = mid_phrase(lemma, pronoun)
    subj = subject_label(pronoun)
    if mid:
        return f"Gestern {aux_form} {subj} {mid} ___. ({lemma})"
    return f"Gestern {aux_form} {subj} ___. ({lemma})"


def special_aux_frames(lemma: str, part: str) -> list[tuple[str, str, str, str]]:
    """Return list of (key_suffix, answer, frame, aux_name)."""
    if lemma == "passieren":
        return [
            ("was", "ist", f"Gestern ___ ein Unfall {part}. ({lemma})", "sein"),
            ("was-q", "ist", f"Was ___ gestern {part}? ({lemma})", "sein"),
            ("etwas", "ist", f"Vor einer Stunde ___ etwas Schlimmes {part}. ({lemma})", "sein"),
            ("party", "ist", f"Letztes Jahr ___ nichts {part}. ({lemma})", "sein"),
            ("heute", "ist", f"Heute Morgen ___ ein Fehler {part}. ({lemma})", "sein"),
            ("wochenende", "sind", f"Am Wochenende ___ zwei Unfälle {part}. ({lemma})", "sein"),
        ]
    if lemma == "gehören":
        return [
            ("auto-mir", "hat", f"Gestern ___ das Auto mir {part}. ({lemma})", "haben"),
            ("buch-ihm", "hat", f"Früher ___ das Buch dem Freund {part}. ({lemma})", "haben"),
            ("wohnung-uns", "hat", f"Letztes Jahr ___ die Wohnung uns {part}. ({lemma})", "haben"),
            ("schluessel", "hat", f"Der Schlüssel ___ dem Lehrer {part}. ({lemma})", "haben"),
            ("raeder", "haben", f"Die Fahrräder ___ den Kindern {part}. ({lemma})", "haben"),
            ("haus-ihr", "hat", f"Das Haus ___ der Familie {part}. ({lemma})", "haben"),
        ]
    return []


def special_part_frames(lemma: str, part: str, aux: str) -> list[tuple[str, str]]:
    if lemma == "passieren":
        return [
            ("unfall", f"Gestern ist ein Unfall ___. ({lemma})"),
            ("was", f"Was ist gestern ___? ({lemma})"),
            ("etwas", f"Vor einer Stunde ist etwas Schlimmes ___. ({lemma})"),
            ("nichts", f"Letztes Jahr ist nichts ___. ({lemma})"),
        ]
    if lemma == "gehören":
        return [
            ("auto", f"Gestern hat das Auto mir ___. ({lemma})"),
            ("buch", f"Früher hat das Buch dem Freund ___. ({lemma})"),
            ("wohnung", f"Letztes Jahr hat die Wohnung uns ___. ({lemma})"),
            ("schluessel", f"Der Schlüssel hat dem Lehrer ___. ({lemma})"),
        ]
    return []


def sentence_pair(lemma: str, part: str, aux: str) -> list[tuple[str, str, list[str]]]:
    """Curated correct sentence + clearly wrong distractors."""
    mid_ich = mid_phrase(lemma, "ich")
    mid_du = mid_phrase(lemma, "du")
    items: list[tuple[str, str, list[str]]] = []

    if lemma == "passieren":
        good = f"Gestern ist ein Unfall {part}."
        bad = [
            f"Gestern ein Unfall ist {part}.",
            f"Gestern hat ein Unfall {part}.",
            f"Gestern ist ein Unfall {lemma}.",
        ]
        items.append(("passieren-unfall", good, bad))
        good2 = f"Was ist gestern {part}?"
        bad2 = [
            f"Was hat gestern {part}?",
            f"Was gestern ist {part}?",
            f"Was ist gestern {lemma}?",
        ]
        items.append(("passieren-was", good2, bad2))
        return items

    if lemma == "gehören":
        good = f"Das Auto hat mir {part}."
        bad = [
            f"Das Auto mir hat {part}.",
            f"Das Auto ist mir {part}.",
            f"Ich habe das Auto {part}.",
        ]
        items.append(("gehoeren-auto", good, bad))
        good2 = f"Die Fahrräder haben den Kindern {part}."
        bad2 = [
            f"Die Fahrräder den Kindern haben {part}.",
            f"Die Fahrräder sind den Kindern {part}.",
            f"Die Kinder haben die Fahrräder {part}.",
        ]
        items.append(("gehoeren-raeder", good2, bad2))
        return items

    if aux == "haben":
        good = f"Gestern habe ich {mid_ich} {part}." if mid_ich else f"Gestern habe ich {part}."
        bad = [
            f"Gestern ich habe {mid_ich} {part}." if mid_ich else f"Gestern ich habe {part}.",
            f"Gestern habe ich {mid_ich} {lemma}." if mid_ich else f"Gestern habe ich {lemma}.",
            f"Gestern bin ich {mid_ich} {part}." if mid_ich else f"Gestern bin ich {part}.",
        ]
        good2 = f"Hast du auch {mid_du} {part}?" if mid_du else f"Hast du auch {part}?"
        bad2 = [
            f"Du hast auch {mid_du} {part}." if mid_du else f"Du hast auch {part}.",
            f"Hast du auch {mid_du} {lemma}?" if mid_du else f"Hast du auch {lemma}?",
            f"Bist du auch {mid_du} {part}?" if mid_du else f"Bist du auch {part}?",
        ]
    else:
        good = f"Gestern bin ich {mid_ich} {part}." if mid_ich else f"Gestern bin ich {part}."
        bad = [
            f"Gestern ich bin {mid_ich} {part}." if mid_ich else f"Gestern ich bin {part}.",
            f"Gestern bin ich {mid_ich} {lemma}." if mid_ich else f"Gestern bin ich {lemma}.",
            f"Gestern habe ich {mid_ich} {part}." if mid_ich else f"Gestern habe ich {part}.",
        ]
        # Avoid awkward 2nd-person questions for sterben
        if lemma == "sterben":
            good2 = f"Ist er im Jahr 1900 {part}?"
            bad2 = [
                f"Er ist im Jahr 1900 {part}.",
                f"Hat er im Jahr 1900 {part}?",
                f"Ist er im Jahr 1900 {lemma}?",
            ]
            items.append((f"{slug(lemma)}-ich", good, bad))
            items.append((f"{slug(lemma)}-er-q", good2, bad2))
            return items
        good2 = f"Bist du auch {mid_du} {part}?" if mid_du else f"Bist du auch {part}?"
        bad2 = [
            f"Du bist auch {mid_du} {part}." if mid_du else f"Du bist auch {part}.",
            f"Bist du auch {mid_du} {lemma}?" if mid_du else f"Bist du auch {lemma}?",
            f"Hast du auch {mid_du} {part}?" if mid_du else f"Hast du auch {part}?",
        ]

    bad = [b for b in bad if b != good]
    bad2 = [b for b in bad2 if b != good2]
    while len(bad) < 3:
        bad.append(f"Gestern {part} ich {mid_ich}.".strip() if mid_ich else f"Gestern {part} ich.")
    while len(bad2) < 3:
        bad2.append(f"Warst du auch {part}?")

    items.append((f"{slug(lemma)}-ich", good, bad))
    items.append((f"{slug(lemma)}-du-q", good2, bad2))
    return items


def main() -> None:
    questions: list[dict] = []

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

    # 1) Auxiliary choice
    for lemma, part, aux, vtype in UNIQUE_VERBS:
        if lemma in SPECIAL_LEMMAS:
            for key, answer, frame, aux_name in special_aux_frames(lemma, part):
                wrong = ["hat", "habe", "bin", "sind", "war", "hatte", "hast", "ist"]
                add_q(
                    f"{KEY_PREFIX}:aux:{slug(lemma)}:{key}",
                    "perfekt_auxiliary",
                    lemma,
                    answer,
                    wrong,
                    {lang: {"prompt": t["aux"].format(frame=frame)} for lang, t in PROMPT.items()},
                    {"kind": "aux_choice", "aux": aux_name, "type": vtype, "special": True},
                )
            continue

        for pronoun in ("ich", "du", "er", "wir", "sie (they)", "Sie (formal)"):
            answer = HABEN_CONJ[pronoun] if aux == "haben" else SEIN_CONJ[pronoun]
            wrong_aux = SEIN_CONJ[pronoun] if aux == "haben" else HABEN_CONJ[pronoun]
            marker = MARKERS[hash(lemma) % len(MARKERS)]
            ask = aux_blank_frame(
                marker,
                pronoun,
                lemma,
                part,
                question=(pronoun == "Sie (formal)"),
            )
            wrong = [
                wrong_aux,
                HABEN_CONJ["wir"] if answer != HABEN_CONJ["wir"] else HABEN_CONJ["ich"],
                SEIN_CONJ["wir"] if answer != SEIN_CONJ["wir"] else SEIN_CONJ["ich"],
                HABEN_CONJ["du"],
                SEIN_CONJ["du"],
                "hatte",
                "war",
            ]
            add_q(
                f"{KEY_PREFIX}:aux:{slug(lemma)}:{slug(pronoun)}",
                "perfekt_auxiliary",
                lemma,
                answer,
                wrong,
                {lang: {"prompt": t["aux"].format(frame=ask)} for lang, t in PROMPT.items()},
                {"kind": "aux_choice", "aux": aux, "pronoun": pronoun, "type": vtype},
            )

    # 2) Participle selection
    for lemma, part, aux, vtype in UNIQUE_VERBS:
        if lemma in SPECIAL_LEMMAS:
            for key, frame in special_part_frames(lemma, part, aux):
                add_q(
                    f"{KEY_PREFIX}:part:{slug(lemma)}:{key}",
                    "perfekt_participle",
                    lemma,
                    part,
                    wrong_participle(lemma, part, vtype),
                    {lang: {"prompt": t["part"].format(frame=frame)} for lang, t in PROMPT.items()},
                    {"kind": "participle", "type": vtype, "aux": aux, "special": True},
                )
            continue

        for pronoun in ("ich", "er", "wir", "du"):
            aux_form = HABEN_CONJ[pronoun] if aux == "haben" else SEIN_CONJ[pronoun]
            frame = part_blank_frame(aux_form, pronoun, lemma)
            add_q(
                f"{KEY_PREFIX}:part:{slug(lemma)}:{slug(pronoun)}",
                "perfekt_participle",
                lemma,
                part,
                wrong_participle(lemma, part, vtype),
                {lang: {"prompt": t["part"].format(frame=frame)} for lang, t in PROMPT.items()},
                {"kind": "participle", "type": vtype, "aux": aux},
            )

    # 3) Participle type / rule
    for lemma, part, aux, vtype in UNIQUE_VERBS:
        answer = TYPE_LABELS["en"][vtype]
        other_types = [k for k in TYPE_LABELS["en"] if k != vtype]
        wrong_en = [TYPE_LABELS["en"][k] for k in other_types]
        translations = {
            lang: {"prompt": PROMPT[lang]["type"].format(verb=lemma)}
            for lang in TYPE_LABELS
        }
        add_q(
            f"{KEY_PREFIX}:type:{slug(lemma)}",
            "perfekt_participle_type",
            lemma,
            answer,
            wrong_en,
            translations,
            {"kind": "part_type", "type": vtype, "participle": part},
        )

    # 4) Full sentence correctness
    for lemma, part, aux, vtype in UNIQUE_VERBS:
        for key, answer, wrong in sentence_pair(lemma, part, aux):
            add_q(
                f"{KEY_PREFIX}:sent:{key}",
                "perfekt_word_order",
                lemma,
                answer,
                wrong,
                {
                    lang: {
                        "prompt": t["form"].format(
                            frame=f"auxiliary in position 2 + participle at end — {key}"
                        )
                    }
                    for lang, t in PROMPT.items()
                },
                {"kind": "sentence"},
            )

    # 5) haben/sein conjugation drills
    drill_verbs = [
        ("spielen", "gespielt", "haben"),
        ("arbeiten", "gearbeitet", "haben"),
        ("trinken", "getrunken", "haben"),
        ("sehen", "gesehen", "haben"),
        ("kaufen", "gekauft", "haben"),
        ("gehen", "gegangen", "sein"),
        ("fahren", "gefahren", "sein"),
        ("kommen", "gekommen", "sein"),
        ("bleiben", "geblieben", "sein"),
        ("sein", "gewesen", "sein"),
        ("einkaufen", "eingekauft", "haben"),
        ("verstehen", "verstanden", "haben"),
        ("studieren", "studiert", "haben"),
        ("denken", "gedacht", "haben"),
        ("aufstehen", "aufgestanden", "sein"),
    ]
    for lemma, part, aux in drill_verbs:
        table = HABEN_CONJ if aux == "haben" else SEIN_CONJ
        other = SEIN_CONJ if aux == "haben" else HABEN_CONJ
        for pronoun in PRONOUNS:
            answer = table[pronoun]
            frame = aux_blank_frame(
                "Gestern",
                pronoun,
                lemma,
                part,
                question=(pronoun == "Sie (formal)"),
            )
            wrong = [table[p] for p in PRONOUNS if table[p] != answer] + [
                other[pronoun],
                "hatte",
                "war",
                "musste",
            ]
            add_q(
                f"{KEY_PREFIX}:conj:{slug(lemma)}:{slug(pronoun)}",
                "perfekt_auxiliary",
                lemma,
                answer,
                wrong,
                {lang: {"prompt": t["conj"].format(frame=frame)} for lang, t in PROMPT.items()},
                {"kind": "aux_conj", "aux": aux, "pronoun": pronoun},
            )

    # 6) Perfekt vs Präteritum preference
    for verb, praet, perf_like in PRAETERITUM_PREFERRED:
        frame = f"spoken German with *{verb}* (e.g. gestern …)"
        add_q(
            f"{KEY_PREFIX}:tense-pref:{verb}",
            "perfekt_vs_praeteritum",
            verb,
            "Präteritum",
            ["Perfekt", "Futur I", "Plusquamperfekt"],
            {lang: {"prompt": t["tense"].format(frame=frame)} for lang, t in PROMPT.items()},
            {"kind": "tense_pref", "prefer": "praeteritum"},
        )
        add_q(
            f"{KEY_PREFIX}:tense-form:{verb}",
            "perfekt_vs_praeteritum",
            verb,
            praet,
            [perf_like, f"bin {praet}", f"habe {praet}"],
            {
                lang: {
                    "prompt": t["blank"].format(
                        frame=f"Gestern ___ ich … (*{verb}*, preferred past)"
                    )
                }
                for lang, t in PROMPT.items()
            },
            {"kind": "praet_form", "verb": verb},
        )

    perfekt_verbs = [
        ("spielen", "habe gespielt", "spielte"),
        ("gehen", "bin gegangen", "ging"),
        ("arbeiten", "habe gearbeitet", "arbeitete"),
        ("trinken", "habe getrunken", "trank"),
        ("kaufen", "habe gekauft", "kaufte"),
        ("kommen", "bin gekommen", "kam"),
        ("verstehen", "habe verstanden", "verstand"),
        ("telefonieren", "habe telefoniert", "telefonierte"),
    ]
    for lemma, perf, pret in perfekt_verbs:
        frame = f"spoken German with *{lemma}* (e.g. gestern …)"
        add_q(
            f"{KEY_PREFIX}:tense-pref-perf:{slug(lemma)}",
            "perfekt_vs_praeteritum",
            lemma,
            "Perfekt",
            ["Präteritum", "Futur I", "Plusquamperfekt"],
            {lang: {"prompt": t["tense"].format(frame=frame)} for lang, t in PROMPT.items()},
            {"kind": "tense_pref", "prefer": "perfekt"},
        )
        add_q(
            f"{KEY_PREFIX}:tense-form-perf:{slug(lemma)}",
            "perfekt_vs_praeteritum",
            lemma,
            perf,
            [pret, f"war {lemma}", f"hatte {lemma}"],
            {
                lang: {
                    "prompt": t["blank"].format(
                        frame=f"Gestern ___ ich … (*{lemma}*, preferred spoken past)"
                    )
                }
                for lang, t in PROMPT.items()
            },
            {"kind": "perfekt_form", "verb": lemma},
        )

    # 7) Rules / concept checks
    rule_items = [
        (
            "sein-movement",
            "Verbs of movement from A to B often take sein in Perfekt.",
            [
                "All verbs take sein in Perfekt.",
                "Movement verbs always take haben.",
                "sein is never used as an auxiliary.",
            ],
        ),
        (
            "haben-default",
            "Most verbs form Perfekt with haben.",
            [
                "Most verbs form Perfekt with sein.",
                "haben is only used with modal verbs.",
                "Perfekt never uses an auxiliary.",
            ],
        ),
        (
            "bleiben-sein",
            "bleiben takes sein even though there is no movement.",
            [
                "bleiben takes haben because there is no movement.",
                "bleiben has no past participle.",
                "bleiben is only used in Präteritum.",
            ],
        ),
        (
            "gewesen",
            "The Partizip of sein is gewesen.",
            [
                "The Partizip of sein is gesein.",
                "The Partizip of sein is gesin.",
                "sein has no Partizip.",
            ],
        ),
        (
            "separable-ge",
            "In separable verbs, -ge- goes between the prefix and the stem (eingekauft).",
            [
                "Separable verbs never take ge-.",
                "Separable verbs put ge- before the whole verb (geeinkauft).",
                "Separable verbs put ge- after the stem only.",
            ],
        ),
        (
            "inseparable-no-ge",
            "Inseparable prefixes (be-, ver-, er-…) form participles without ge-.",
            [
                "Inseparable verbs always add ge- at the start.",
                "Inseparable verbs put ge- after the prefix.",
                "All prefixes are inseparable.",
            ],
        ),
        (
            "ieren-no-ge",
            "Verbs ending in -ieren get no ge- and end in -t (studiert).",
            [
                "*-ieren* verbs always start with ge-.",
                "*-ieren* participles end in -en.",
                "*-ieren* verbs always take sein.",
            ],
        ),
        (
            "mixed",
            "Mixed verbs change the vowel and end in -t (gedacht, gebracht).",
            [
                "Mixed verbs never change the vowel.",
                "Mixed verbs end in -en like strong verbs.",
                "Mixed verbs never take ge-.",
            ],
        ),
        (
            "word-order",
            "In Perfekt, the auxiliary is in position 2 and the participle is at the end.",
            [
                "In Perfekt, the participle is in position 2.",
                "Both parts stay together in position 2.",
                "The auxiliary goes to the end of the sentence.",
            ],
        ),
        (
            "modals-praet",
            "For sein, haben and modals, speakers usually prefer Präteritum.",
            [
                "For sein, haben and modals, speakers usually prefer Perfekt.",
                "Modal verbs have no past forms.",
                "Präteritum is never used in spoken German.",
            ],
        ),
    ]
    for key, answer, wrong in rule_items:
        add_q(
            f"{KEY_PREFIX}:rule:{key}",
            "perfekt_concepts",
            key,
            answer,
            wrong,
            {
                lang: {"prompt": f"{t['rule']} ({key.replace('-', ' / ')})"}
                for lang, t in PROMPT.items()
            },
            {"kind": "rule"},
        )

    # 8) Volume: marker + aux + complement for safe core verbs
    core = [
        ("machen", "gemacht", "haben"),
        ("spielen", "gespielt", "haben"),
        ("lernen", "gelernt", "haben"),
        ("kochen", "gekocht", "haben"),
        ("trinken", "getrunken", "haben"),
        ("essen", "gegessen", "haben"),
        ("sehen", "gesehen", "haben"),
        ("lesen", "gelesen", "haben"),
        ("schreiben", "geschrieben", "haben"),
        ("schlafen", "geschlafen", "haben"),
        ("arbeiten", "gearbeitet", "haben"),
        ("kaufen", "gekauft", "haben"),
        ("fragen", "gefragt", "haben"),
        ("sagen", "gesagt", "haben"),
        ("helfen", "geholfen", "haben"),
        ("treffen", "getroffen", "haben"),
        ("gehen", "gegangen", "sein"),
        ("fahren", "gefahren", "sein"),
        ("kommen", "gekommen", "sein"),
        ("fliegen", "geflogen", "sein"),
        ("laufen", "gelaufen", "sein"),
        ("bleiben", "geblieben", "sein"),
        ("sein", "gewesen", "sein"),
        ("aufstehen", "aufgestanden", "sein"),
        ("einkaufen", "eingekauft", "haben"),
        ("anrufen", "angerufen", "haben"),
        ("verstehen", "verstanden", "haben"),
        ("besuchen", "besucht", "haben"),
        ("telefonieren", "telefoniert", "haben"),
        ("studieren", "studiert", "haben"),
        ("denken", "gedacht", "haben"),
        ("bringen", "gebracht", "haben"),
        ("kennen", "gekannt", "haben"),
    ]
    for lemma, part, aux in core:
        for marker in MARKERS:
            for pronoun in ("ich", "du", "er", "wir", "ihr", "sie (they)"):
                answer = HABEN_CONJ[pronoun] if aux == "haben" else SEIN_CONJ[pronoun]
                frame = aux_blank_frame(marker, pronoun, lemma, part)
                wrong = [
                    SEIN_CONJ[pronoun] if aux == "haben" else HABEN_CONJ[pronoun],
                    HABEN_CONJ["wir"] if answer != HABEN_CONJ["wir"] else HABEN_CONJ["ich"],
                    SEIN_CONJ["wir"] if answer != SEIN_CONJ["wir"] else SEIN_CONJ["ich"],
                    "hatte",
                    "war",
                    "habt",
                    "seid",
                ]
                add_q(
                    f"{KEY_PREFIX}:mark:{slug(marker)}:{slug(lemma)}:{slug(pronoun)}",
                    "perfekt_auxiliary",
                    lemma,
                    answer,
                    wrong,
                    {lang: {"prompt": t["conj"].format(frame=frame)} for lang, t in PROMPT.items()},
                    {"kind": "marker_aux", "aux": aux, "marker": marker, "pronoun": pronoun},
                )

    # Quality gates
    by_key = {q["import_key"]: q for q in questions}
    expected = {
        f"{KEY_PREFIX}:aux:gehen:ich": "bin",
        f"{KEY_PREFIX}:aux:spielen:ich": "habe",
        f"{KEY_PREFIX}:aux:passieren:was": "ist",
        f"{KEY_PREFIX}:aux:gehoeren:auto-mir": "hat",
        f"{KEY_PREFIX}:part:trinken:ich": "getrunken",
        f"{KEY_PREFIX}:part:einkaufen:ich": "eingekauft",
        f"{KEY_PREFIX}:part:verstehen:ich": "verstanden",
        f"{KEY_PREFIX}:part:studieren:ich": "studiert",
        f"{KEY_PREFIX}:part:denken:ich": "gedacht",
        f"{KEY_PREFIX}:part:sein:ich": "gewesen",
        f"{KEY_PREFIX}:part:bleiben:ich": "geblieben",
        f"{KEY_PREFIX}:part:passieren:unfall": "passiert",
        f"{KEY_PREFIX}:part:gehoeren:auto": "gehört",
        f"{KEY_PREFIX}:tense-pref:sein": "Präteritum",
        f"{KEY_PREFIX}:tense-pref:wissen": "Präteritum",
        f"{KEY_PREFIX}:tense-pref-perf:spielen": "Perfekt",
        f"{KEY_PREFIX}:sent:gehen-ich": "Gestern bin ich nach Hause gegangen.",
        f"{KEY_PREFIX}:sent:spielen-ich": "Gestern habe ich Tennis gespielt.",
        f"{KEY_PREFIX}:sent:passieren-unfall": "Gestern ist ein Unfall passiert.",
        f"{KEY_PREFIX}:sent:gehoeren-auto": "Das Auto hat mir gehört.",
        f"{KEY_PREFIX}:sent:werden-ich": "Gestern bin ich Arzt geworden.",
        f"{KEY_PREFIX}:sent:entschuldigen-ich": "Gestern habe ich mich entschuldigt.",
        f"{KEY_PREFIX}:sent:fallen-ich": "Gestern bin ich auf die Straße gefallen.",
        f"{KEY_PREFIX}:conj:gehen:du": "bist",
        f"{KEY_PREFIX}:conj:spielen:du": "hast",
        f"{KEY_PREFIX}:rule:bleiben-sein": "bleiben takes sein even though there is no movement.",
    }
    for key, answer in expected.items():
        actual = by_key.get(key, {}).get("answer")
        assert actual == answer, f"{key}: expected {answer!r}, got {actual!r}"

    forbidden_snippets = [
        "ich passiert",
        "du passiert",
        "ich gehört.",  # agentive gehören
        "habe ich gehört. (gehören)",
        "bin ich geworden.",  # incomplete werden
        "habe ich genannt.",
        "Bist du auch gestorben",
        "bin ich passiert",
        "ich sich ",
        "du sich ",
        "wir sich ",
        "habe ich sich",
        "hast du sich",
    ]
    for question in questions:
        assert question["answer"] not in question["distractors"], question["import_key"]
        assert len(question["distractors"]) == 3, question["import_key"]
        assert len(set(question["distractors"])) == 3, question["import_key"]
        lowers = [o.casefold() for o in [question["answer"], *question["distractors"]]]
        assert len(lowers) == len(set(lowers)), f"case-only collision: {question['import_key']}"
        for lang in ("en", "es", "fa", "ar", "tr"):
            assert question["translations"][lang]["prompt"].strip()
        blob = " ".join(t["prompt"] for t in question["translations"].values())
        blob_ans = blob + " " + question["answer"]
        for token in (
            "PDF",
            "Page ",
            "Section ",
            "handwritten",
            "Exercise ",
            "Seite ",
            "Ninth lesson",
            "Tenth lesson",
        ):
            assert token not in blob, f"forbidden token in {question['import_key']}: {token}"
        for snip in forbidden_snippets:
            assert snip not in blob_ans, f"bad German in {question['import_key']}: {snip}"

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
            "source_vocabulary": "curated A1–A2 Perfekt verb table",
            "source_lesson": "german_grammar_lesson_10.md",
        },
        "questions": questions,
    }
    OUT_PATH.write_text(json.dumps(payload, ensure_ascii=False, indent=2), encoding="utf-8")
    print(f"wrote {OUT_PATH} count={len(questions)}")
    print(Counter(q["topic"] for q in questions))
    print(Counter((q.get("meta") or {}).get("kind") for q in questions))


if __name__ == "__main__":
    main()
