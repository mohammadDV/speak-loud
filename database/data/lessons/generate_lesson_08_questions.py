#!/usr/bin/env python3
"""Regenerate german_grammar_lesson_08_questions.json for dative verbs & wer/wen/wem (A2)."""

from __future__ import annotations

import json
from collections import Counter
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
VOCAB_PATH = ROOT / "vocabulary" / "a1_german_words.json"
OUT_PATH = Path(__file__).resolve().parent / "german_grammar_lesson_08_questions.json"

COURSE = "de-grammar-a1"
LESSON = "dative-verbs-wer-wen-wem"
KEY_PREFIX = f"{COURSE}:l08"

PROMPT = {
    "en": {
        "case": 'Which case does "{verb}" take for its (person) object?',
        "group": 'Which group does "{verb}" belong to?',
        "dat_pron": "Choose the correct dative pronoun: {frame}",
        "dat_art": "Choose the correct dative article: {frame}",
        "conj": "Choose the correct verb form: {frame}",
        "separable": "Choose the correct separable-verb sentence: {frame}",
        "qw": "Choose the correct question word: {frame}",
        "prep_qw": "Choose the correct question phrase: {frame}",
        "contrast": "Which sentence uses the dative verb correctly?",
        "leid": "Choose the correct form: {frame}",
        "plural_n": "Choose the correct dative plural: {frame}",
    },
    "es": {
        "case": '¿Qué caso exige "{verb}" para su objeto (persona)?',
        "group": '¿A qué grupo pertenece "{verb}"?',
        "dat_pron": "Elige el pronombre dativo correcto: {frame}",
        "dat_art": "Elige el artículo dativo correcto: {frame}",
        "conj": "Elige la forma verbal correcta: {frame}",
        "separable": "Elige la frase correcta con verbo separable: {frame}",
        "qw": "Elige la palabra interrogativa correcta: {frame}",
        "prep_qw": "Elige la pregunta correcta: {frame}",
        "contrast": "¿Qué frase usa correctamente el verbo dativo?",
        "leid": "Elige la forma correcta: {frame}",
        "plural_n": "Elige el plural dativo correcto: {frame}",
    },
    "fa": {
        "case": 'فعل "{verb}" برای مفعول (شخص) کدام حالت را می‌خواهد؟',
        "group": 'فعل "{verb}" به کدام گروه تعلق دارد؟',
        "dat_pron": "ضمیر داتیو درست را انتخاب کنید: {frame}",
        "dat_art": "حرف تعریف داتیو درست را انتخاب کنید: {frame}",
        "conj": "شکل درست فعل را انتخاب کنید: {frame}",
        "separable": "جملهٔ درست با فعل جداشدنی را انتخاب کنید: {frame}",
        "qw": "واژهٔ پرسشی درست را انتخاب کنید: {frame}",
        "prep_qw": "عبارت پرسشی درست را انتخاب کنید: {frame}",
        "contrast": "کدام جمله فعل داتیو را درست به کار می‌برد؟",
        "leid": "شکل درست را انتخاب کنید: {frame}",
        "plural_n": "جمع داتیو درست را انتخاب کنید: {frame}",
    },
    "ar": {
        "case": 'أي حالة يطلبها الفعل "{verb}" لمفعوله (الشخص)؟',
        "group": 'إلى أي مجموعة ينتمي "{verb}"؟',
        "dat_pron": "اختر ضمير الداتيف الصحيح: {frame}",
        "dat_art": "اختر أداة التعريف في الداتيف الصحيحة: {frame}",
        "conj": "اختر صيغة الفعل الصحيحة: {frame}",
        "separable": "اختر الجملة الصحيحة مع الفعل المنفصل: {frame}",
        "qw": "اختر كلمة الاستفهام الصحيحة: {frame}",
        "prep_qw": "اختر عبارة السؤال الصحيحة: {frame}",
        "contrast": "أي جملة تستخدم فعل الداتيف بشكل صحيح؟",
        "leid": "اختر الصيغة الصحيحة: {frame}",
        "plural_n": "اختر جمع الداتيف الصحيح: {frame}",
    },
    "tr": {
        "case": '"{verb}" (kişi) nesnesi için hangi hâli ister?',
        "group": '"{verb}" hangi gruba aittir?',
        "dat_pron": "Doğru datif zamiri seçin: {frame}",
        "dat_art": "Doğru datif artikeli seçin: {frame}",
        "conj": "Doğru fiil biçimini seçin: {frame}",
        "separable": "Ayrılabilen fiilli doğru cümleyi seçin: {frame}",
        "qw": "Doğru soru sözcüğünü seçin: {frame}",
        "prep_qw": "Doğru soru ifadesini seçin: {frame}",
        "contrast": "Datif fiili doğru kullanan cümle hangisi?",
        "leid": "Doğru biçimi seçin: {frame}",
        "plural_n": "Doğru datif çoğulu seçin: {frame}",
    },
}

GROUP_LABELS = {
    "en": {
        "A": "Group A (person → person, Dative)",
        "B": "Group B (thing subject → person Dative)",
        "C": "Group C (fixed dative expression)",
    },
    "es": {
        "A": "Grupo A (persona → persona, Dativo)",
        "B": "Grupo B (cosa sujeto → persona Dativo)",
        "C": "Grupo C (expresión dativa fija)",
    },
    "fa": {
        "A": "گروه A (شخص → شخص، داتیو)",
        "B": "گروه B (چیز فاعل → شخص داتیو)",
        "C": "گروه C (عبارت ثابت داتیو)",
    },
    "ar": {
        "A": "المجموعة A (شخص → شخص، داتيف)",
        "B": "المجموعة B (شيء فاعل → شخص داتيف)",
        "C": "المجموعة C (تعبير داتيف ثابت)",
    },
    "tr": {
        "A": "Grup A (kişi → kişi, Datif)",
        "B": "Grup B (şey özne → kişi Datif)",
        "C": "Grup C (kalıp datif ifade)",
    },
}

CASE_LABELS = {
    "en": {"dat": "Dative", "akk": "Accusative", "nom": "Nominative"},
    "es": {"dat": "Dativo", "akk": "Acusativo", "nom": "Nominativo"},
    "fa": {"dat": "داتیو", "akk": "آکوزاتیو", "nom": "nominativ"},
    "ar": {"dat": "الداتيف", "akk": "الأكوزاتيف", "nom": "النوميناتيف"},
    "tr": {"dat": "Datif", "akk": "Akkuzatif", "nom": "Nominatif"},
}

GROUP_A = ["antworten", "gratulieren", "helfen", "zuhören", "glauben", "danken"]
GROUP_B = ["schmecken", "gefallen", "gehören", "passen", "stehen", "wehtun"]
GROUP_C = ["Es tut … leid", "Wie geht es …?"]

DAT_VERB_PRON = [
    ("helfen-dir", "Ich helfe ___. (du)", "dir", ["dich", "du", "ihn"]),
    ("helfen-ihm", "Ich helfe ___. (der Mann)", "ihm", ["ihn", "er", "ihr"]),
    ("helfen-ihr", "Ich helfe ___. (die Frau)", "ihr", ["sie", "ihn", "ihm"]),
    ("helfen-ihnen", "Wir helfen ___. (sie / they)", "ihnen", ["sie", "ihr", "ihm"]),
    ("helfen-ihnen-formal", "Können Sie ___ helfen? (formal you)", "Ihnen", ["Sie", "ihr", "ihnen"]),
    ("helfen-mir", "Kannst du ___ helfen? (ich)", "mir", ["mich", "ich", "dir"]),
    ("antworten-mir", "Bitte antworte ___. (ich)", "mir", ["mich", "ich", "dir"]),
    ("antworten-dir", "Ich antworte ___. (du)", "dir", ["dich", "du", "mir"]),
    ("antworten-ihm", "Ich antworte ___. (er)", "ihm", ["ihn", "er", "ihr"]),
    ("antworten-ihr", "Ich antworte ___. (sie / she)", "ihr", ["sie", "ihm", "ihn"]),
    ("danken-dir", "Ich danke ___. (du)", "dir", ["dich", "du", "mir"]),
    ("danken-ihnen", "Ich danke ___. (formal you)", "Ihnen", ["Sie", "ihr", "ihnen"]),
    ("danken-ihm", "Wir danken ___. (der Lehrer)", "ihm", ["ihn", "er", "ihr"]),
    ("glauben-dir", "Ich glaube ___. (du)", "dir", ["dich", "du", "mir"]),
    ("glauben-ihr", "Ich glaube ___. (sie / she)", "ihr", ["sie", "ihm", "ihn"]),
    ("glauben-ihnen", "Ich glaube ___. (sie / they)", "ihnen", ["sie", "ihr", "ihm"]),
    ("gratulieren-ihr", "Wir gratulieren ___. (die Tochter)", "ihr", ["sie", "ihm", "ihn"]),
    ("gratulieren-ihm", "Ich gratuliere ___. (der Sohn)", "ihm", ["ihn", "er", "ihr"]),
    ("zuhören-ihr", "Die Kinder hören ___ zu. (die Oma)", "ihr", ["sie", "ihm", "ihn"]),
    ("zuhören-ihm", "Ich höre ___ zu. (der Lehrer)", "ihm", ["ihn", "er", "ihr"]),
    ("gefallen-mir", "Das Haus gefällt ___. (ich)", "mir", ["mich", "ich", "dir"]),
    ("gefallen-uns", "Frankfurt gefällt ___. (wir)", "uns", ["wir", "euch", "ihnen"]),
    ("gefallen-ihr", "Der Film gefällt ___. (sie / she)", "ihr", ["sie", "ihm", "ihn"]),
    ("schmecken-mir", "Die Suppe schmeckt ___. (ich)", "mir", ["mich", "ich", "dir"]),
    ("schmecken-ihr", "Der Wein schmeckt ___ nicht. (die Frau)", "ihr", ["sie", "ihm", "ihn"]),
    ("gehoeren-mir", "Die Uhr gehört ___ nicht. (ich)", "mir", ["mich", "ich", "dir"]),
    ("gehoeren-ihm", "Das Auto gehört ___. (der Chef)", "ihm", ["ihn", "er", "ihr"]),
    ("passen-mir", "Die Hose passt ___ nicht. (ich)", "mir", ["mich", "ich", "dir"]),
    ("passen-ihm", "Die Schuhe passen ___ gut. (der Mann)", "ihm", ["ihn", "er", "ihr"]),
    ("stehen-dir", "Dein Hut steht ___ toll! (du)", "dir", ["dich", "du", "mir"]),
    ("stehen-ihr", "Die Jacke steht ___ nicht. (sie / she)", "ihr", ["sie", "ihm", "ihn"]),
    ("wehtun-mir", "Mein Hals tut ___ weh. (ich)", "mir", ["mich", "ich", "dir"]),
    ("wehtun-ihm", "Der Fuß tut ___ weh. (der Mann)", "ihm", ["ihn", "er", "ihr"]),
    ("wehtun-ihr", "Der Kopf tut ___ weh. (die Frau)", "ihr", ["sie", "ihm", "ihn"]),
]

LEID_ITEMS = [
    ("leid-mir", "Es tut ___ leid. (ich)", "mir", ["mich", "ich", "dir"]),
    ("leid-ihm", "Es tut ___ leid. (er)", "ihm", ["ihn", "er", "ihr"]),
    ("leid-ihr", "Es tut ___ leid. (sie / she)", "ihr", ["sie", "ihm", "ihn"]),
    ("leid-ihnen", "Es tut ___ leid. (sie / they)", "ihnen", ["sie", "ihr", "ihm"]),
    ("leid-dir", "Es tut ___ leid. (du)", "dir", ["dich", "du", "mir"]),
    ("leid-uns", "Es tut ___ leid. (wir)", "uns", ["wir", "euch", "ihnen"]),
    ("leid-euch", "Es tut ___ leid. (ihr / you pl.)", "euch", ["ihr", "uns", "ihnen"]),
    ("leid-ihnen-formal", "Es tut ___ leid. (formal you)", "Ihnen", ["Sie", "ihr", "ihnen"]),
    ("geht-ihnen", "Wie geht es ___? (formal you)", "Ihnen", ["Sie", "ihr", "ihnen"]),
    ("geht-ihr", "Es geht ___ schlecht. (sie / she)", "ihr", ["sie", "ihm", "ihn"]),
    ("geht-mir", "Es geht ___ gut. (ich)", "mir", ["mich", "ich", "dir"]),
    ("geht-uns", "Es geht ___ gut. (wir)", "uns", ["wir", "euch", "ihnen"]),
]

QW_ITEMS = [
    ("wer-nom", "___ hilft dem Mann? (asking for the subject / person)", "Wer", ["Wen", "Wem", "Was"]),
    ("was-nom", "___ schmeckt der Frau? (asking for the thing subject)", "Was", ["Wer", "Wen", "Wem"]),
    ("wen-akk", "___ kennst du? (asking for the direct object / person)", "Wen", ["Wer", "Wem", "Was"]),
    ("was-akk", "___ kaufst du? (asking for the direct object / thing)", "Was", ["Wer", "Wen", "Wem"]),
    ("wem-dat", "___ hilfst du? — Meiner Schwester.", "Wem", ["Wen", "Was", "Wer"]),
    ("wem-gefallen", "___ gefällt Frankfurt gut?", "Wem", ["Wen", "Was", "Wer"]),
    ("wem-antwort", "___ antwortet der Mann?", "Wem", ["Wen", "Was", "Wer"]),
    ("wer-liebt", "___ liebt Lisa? (subject)", "Wer", ["Wen", "Wem", "Was"]),
    ("wen-liebt", "___ liebt Lennard? (object)", "Wen", ["Wer", "Wem", "Was"]),
    ("was-trinkt", "___ trinkt er gerne?", "Was", ["Wer", "Wen", "Wem"]),
    ("wer-trinkt", "___ trinkt gerne Espresso?", "Wer", ["Wen", "Wem", "Was"]),
    ("wem-jacke", "___ kaufst du eine Jacke? (meiner Schwester = Dative)", "Wem", ["Wen", "Was", "Wer"]),
    ("was-schwester", "___ kaufst du deiner Schwester?", "Was", ["Wer", "Wen", "Wem"]),
]

PREP_QW = [
    ("mit-wem", "___ gehst du ins Kino? (with whom)", "Mit wem", ["Mit wen", "Für wen", "Bei wen"]),
    ("bei-wem", "___ wohnst du? (with whom / at whose place)", "Bei wem", ["Bei wen", "Mit wen", "Zu wen"]),
    ("von-wem", "___ ist die Mail? (from whom)", "Von wem", ["Von wen", "Für wen", "Mit wen"]),
    ("zu-wem", "___ gehst du? (to whom)", "Zu wem", ["Zu wen", "Für wen", "Bei wen"]),
    ("fuer-wen", "___ kauft ihr das Auto? (for whom)", "Für wen", ["Für wem", "Mit wem", "Ohne wem"]),
    ("ohne-wen", "___ willst du nicht Urlaub machen? (without whom)", "Ohne wen", ["Ohne wem", "Mit wem", "Für wem"]),
    ("mit-wem-urlaub", "___ fährt sie in Urlaub?", "Mit wem", ["Mit wen", "Für wen", "Zu wen"]),
    ("fuer-wen-geschenk", "___ kaufst du das Geschenk?", "Für wen", ["Für wem", "Mit wem", "Bei wem"]),
]

CONJ_ITEMS = [
    ("hilft", "Das Kind ___ dem Mann. (helfen)", "hilft", ["helfen", "helft", "hilfe"]),
    ("antwortet", "Der Mann ___ der Frau. (antworten)", "antwortet", ["antworten", "antwort", "antwortest"]),
    ("gefaellt", "Das Haus ___ den Leuten. (gefallen)", "gefällt", ["gefallen", "gefälltst", "gefälle"]),
    ("gehoert", "Das Auto ___ dem Chef. (gehören)", "gehört", ["gehören", "gehörst", "gehöre"]),
    ("schmeckt", "Die Torte ___ der Frau. (schmecken)", "schmeckt", ["schmecken", "schmeckst", "schmecke"]),
    ("passen", "Die Schuhe ___ dem Mann gut. (passen, plural subject)", "passen", ["passt", "passe", "passtt"]),
    ("steht", "Die Jacke ___ dem Mädchen nicht gut. (stehen)", "steht", ["stehen", "stehst", "stehe"]),
    ("tut-weh", "Der Kopf ___ der Frau weh. (wehtun)", "tut", ["tun", "tust", "tue"]),
    ("hoeren-zu", "Die Kinder ___ der Oma zu. (zuhören)", "hören", ["hört", "höre", "zuhören"]),
    ("danke", "Ich ___ Ihnen. (danken)", "danke", ["dankt", "danken", "dankst"]),
]

SEPARABLE = [
    (
        "zuhoeren",
        "Die Kinder ___",
        "Die Kinder hören der Oma zu.",
        [
            "Die Kinder zuhören der Oma.",
            "Die Kinder hören zu der Oma.",
            "Die Kinder der Oma zuhören.",
        ],
    ),
    (
        "wehtun",
        "Der Kopf ___",
        "Der Kopf tut der Frau weh.",
        [
            "Der Kopf wehtun der Frau.",
            "Der Kopf tut weh der Frau.",
            "Der Kopf der Frau tut wehtun.",
        ],
    ),
]

CONTRAST = [
    (
        "antworten-vs-fragen",
        "Die Assistentin antwortet dem Chef.",
        [
            "Die Assistentin antwortet den Chef.",
            "Die Assistentin fragt dem Chef.",
            "Die Assistentin antwortet der Chef.",
        ],
    ),
    (
        "gefallen-vs-schenken",
        "Die Blumen gefallen der Frau.",
        [
            "Die Blumen gefallen die Frau.",
            "Die Blumen gefallen den Frau.",
            "Die Blumen gefällt der Frau.",
        ],
    ),
    (
        "schmecken-gaeste",
        "Das Essen schmeckt den Gästen.",
        [
            "Das Essen schmeckt die Gäste.",
            "Das Essen schmeckt der Gästen.",
            "Das Essen schmeckt dem Gäste.",
        ],
    ),
    (
        "gehoeren-sohn",
        "Das Auto gehört dem Sohn.",
        [
            "Das Auto gehört den Sohn.",
            "Das Auto gehört der Sohn.",
            "Das Auto gehört die Sohn.",
        ],
    ),
]

PLURAL_N = [
    ("leute", "Das Haus gefällt ___.", "den Leuten", ["die Leute", "der Leute", "dem Leuten"]),
    ("freunde", "Der Film gefällt ___.", "den Freunden", ["die Freunde", "der Freunde", "dem Freunden"]),
    ("gaeste", "Das Essen schmeckt ___.", "den Gästen", ["die Gäste", "der Gäste", "dem Gästen"]),
    ("eltern", "Ich helfe ___.", "den Eltern", ["die Eltern", "der Eltern", "dem Eltern"]),
    ("kinder", "Die Jacken passen ___.", "den Kindern", ["die Kinder", "der Kinder", "dem Kindern"]),
]

PERSON_NOUNS = [
    ("Mann", "der", "dem Mann", "den Mann", "der Mann"),
    ("Frau", "die", "der Frau", "die Frau", "die Frau"),
    ("Kind", "das", "dem Kind", "das Kind", "das Kind"),
    ("Lehrer", "der", "dem Lehrer", "den Lehrer", "der Lehrer"),
    ("Lehrerin", "die", "der Lehrerin", "die Lehrerin", "die Lehrerin"),
    ("Freund", "der", "dem Freund", "den Freund", "der Freund"),
    ("Freundin", "die", "der Freundin", "die Freundin", "die Freundin"),
    ("Arzt", "der", "dem Arzt", "den Arzt", "der Arzt"),
    ("Mutter", "die", "der Mutter", "die Mutter", "die Mutter"),
    ("Vater", "der", "dem Vater", "den Vater", "der Vater"),
    ("Tochter", "die", "der Tochter", "die Tochter", "die Tochter"),
    ("Sohn", "der", "dem Sohn", "den Sohn", "der Sohn"),
    ("Gast", "der", "dem Gast", "den Gast", "der Gast"),
    ("Chef", "der", "dem Chef", "den Chef", "der Chef"),
    ("Mädchen", "das", "dem Mädchen", "das Mädchen", "das Mädchen"),
]

GROUP_A_FRAMES = [
    ("antworten", "Der Mann antwortet ___."),
    ("helfen", "Das Kind hilft ___."),
    ("danken", "Ich danke ___."),
    ("glauben", "Ich glaube ___."),
    ("gratulieren", "Die Eltern gratulieren ___."),
]

GROUP_B_FRAMES = [
    ("gefallen", "Das Haus gefällt ___."),
    ("schmecken", "Die Torte schmeckt ___."),
    ("gehören", "Das Auto gehört ___."),
    ("passen", "Die Schuhe passen ___ gut."),
    ("stehen", "Die Jacke steht ___ nicht gut."),
]

SKIP_LEMMAS = {
    "Beisl",
    "Bekannte",
    "Erwachsene",
    "Angehörige",
    "Akkusativ",
    "Adjektiv",
    "Artikel",
    "Alphabet",
    "Absender",
    "Abschnitt",
    "Achtung",
    "Angabe",
    "Anrede",
    "Anredeformel",
    "Anweisung",
    "Aussagesatz",
    "Nominativ",
    "Dativ",
    "Genitiv",
}

# People / living beings only — dative person objects.
PERSON_LEMMAS = {
    "Mann", "Frau", "Kind", "Freund", "Freundin", "Lehrer", "Lehrerin", "Arzt",
    "Vater", "Mutter", "Sohn", "Tochter", "Chef", "Gast", "Mädchen", "Oma",
    "Opa", "Kollege", "Kollegin", "Student", "Studentin", "Kunde", "Kundin",
    "Bruder", "Schwester", "Hund", "Katze", "Baby", "Person", "Nachbar",
    "Nachbarin", "Partner", "Partnerin", "Cousin", "Cousine", "Onkel", "Tante",
    "Enkel", "Enkelin", "Chef", "Direktor", "Direktorin", "Polizist", "Polizistin",
}

PREP_FRAMES = {
    "mit": "___ gehst du ins Kino?",
    "bei": "___ wohnst du?",
    "von": "___ ist die Nachricht?",
    "zu": "___ gehst du nach der Schule?",
    "für": "___ kaufst du das Geschenk?",
    "ohne": "___ willst du nicht Urlaub machen?",
}


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
        if text.endswith(("-1", "-2", "-3")):
            continue
        opts.append(text)
        seen.add(key)
        if len(opts) >= n:
            break
    if len(opts) < n:
        raise RuntimeError(f"Could not build distractors for {correct!r} from {pool!r}")
    return opts[:n]


def dat_article(article: str) -> str:
    return {"der": "dem", "das": "dem", "die": "der"}[article]


def akk_article(article: str) -> str:
    return {"der": "den", "das": "das", "die": "die"}[article]


def main() -> None:
    vocab = json.loads(VOCAB_PATH.read_text(encoding="utf-8"))
    by_lemma: dict[str, dict] = {}
    for lexeme in vocab["lexemes"]:
        lemma = lexeme["lemma"]
        if lemma in by_lemma or lemma in SKIP_LEMMAS or " " in lemma or "-" in lemma:
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
            by_lemma[lemma] = {
                "lemma": lemma,
                "article": article,
                "import_key": sense.get("import_key"),
            }
            break

    personish = [by_lemma[lemma] for lemma in sorted(PERSON_LEMMAS) if lemma in by_lemma]
    if len(personish) < 20:
        raise RuntimeError(f"Need ≥20 person nouns in vocab, got {len(personish)}")

    # Everyday things for Was? drills only
    thing_lemmas = {
        "Auto", "Haus", "Buch", "Film", "Tasche", "Jacke", "Brot", "Kaffee",
        "Ticket", "Handy", "Laptop", "Geschenk", "Fahrrad", "Suppe", "Wein",
        "Hemd", "Hose", "Mantel", "Kleid", "Tisch", "Stuhl",
    }
    thing_nouns = [by_lemma[lemma] for lemma in sorted(thing_lemmas) if lemma in by_lemma]

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

    # 1) Verb → case
    for verb in [*GROUP_A, *GROUP_B]:
        add_q(
            f"{KEY_PREFIX}:case:{verb}",
            "dative_verbs",
            verb,
            CASE_LABELS["en"]["dat"],
            [CASE_LABELS["en"]["akk"], CASE_LABELS["en"]["nom"], "Genitive"],
            {
                lang: {"prompt": t["case"].format(verb=verb)}
                for lang, t in PROMPT.items()
            },
            {"kind": "verb_case"},
        )

    # 2) Verb → group (stable English MCQ labels)
    for verb, group in [*((v, "A") for v in GROUP_A), *((v, "B") for v in GROUP_B)]:
        answer = GROUP_LABELS["en"][group]
        wrong = [GROUP_LABELS["en"][g] for g in ("A", "B", "C") if g != group] + [
            "Accusative object verb",
        ]
        add_q(
            f"{KEY_PREFIX}:group:{verb}",
            "dative_verbs",
            verb,
            answer,
            wrong,
            {lang: {"prompt": t["group"].format(verb=verb)} for lang, t in PROMPT.items()},
            {"kind": "verb_group", "group": group},
        )

    for expr, key in [("Es tut … leid", "leid"), ("Wie geht es …?", "geht")]:
        add_q(
            f"{KEY_PREFIX}:group:{key}",
            "dative_verbs",
            key,
            GROUP_LABELS["en"]["C"],
            [GROUP_LABELS["en"]["A"], GROUP_LABELS["en"]["B"], "Accusative verb"],
            {lang: {"prompt": t["group"].format(verb=expr)} for lang, t in PROMPT.items()},
            {"kind": "verb_group", "group": "C"},
        )

    # 3) Curated pronoun / leid / qw / prep / conj / separable / contrast / plural
    for key, frame, answer, wrong in DAT_VERB_PRON:
        add_q(
            f"{KEY_PREFIX}:pron:{key}",
            "dative_verbs",
            key,
            answer,
            wrong,
            {lang: {"prompt": t["dat_pron"].format(frame=frame)} for lang, t in PROMPT.items()},
            {"kind": "dat_pron"},
            allow_case_variants=answer in {"Ihnen", "Sie"} or "formal" in key,
        )
    for key, frame, answer, wrong in LEID_ITEMS:
        add_q(
            f"{KEY_PREFIX}:leid:{key}",
            "dative_verbs",
            key,
            answer,
            wrong,
            {lang: {"prompt": t["leid"].format(frame=frame)} for lang, t in PROMPT.items()},
            {"kind": "leid_geht"},
            allow_case_variants=answer in {"Ihnen", "Sie"} or "formal" in key,
        )
    for key, frame, answer, wrong in QW_ITEMS:
        add_q(
            f"{KEY_PREFIX}:qw:{key}",
            "case_questions",
            key,
            answer,
            wrong,
            {lang: {"prompt": t["qw"].format(frame=frame)} for lang, t in PROMPT.items()},
            {"kind": "question_word"},
        )
    for key, frame, answer, wrong in PREP_QW:
        add_q(
            f"{KEY_PREFIX}:prep:{key}",
            "case_questions",
            key,
            answer,
            wrong,
            {lang: {"prompt": t["prep_qw"].format(frame=frame)} for lang, t in PROMPT.items()},
            {"kind": "prep_question"},
        )
    for key, frame, answer, wrong in CONJ_ITEMS:
        add_q(
            f"{KEY_PREFIX}:conj:{key}",
            "dative_verbs",
            key,
            answer,
            wrong,
            {lang: {"prompt": t["conj"].format(frame=frame)} for lang, t in PROMPT.items()},
            {"kind": "conjugation"},
        )
    for key, hint, answer, wrong in SEPARABLE:
        add_q(
            f"{KEY_PREFIX}:sep:{key}",
            "dative_verbs",
            key,
            answer,
            wrong,
            {lang: {"prompt": t["separable"].format(frame=hint)} for lang, t in PROMPT.items()},
            {"kind": "separable"},
        )
    for key, answer, wrong in CONTRAST:
        add_q(
            f"{KEY_PREFIX}:contrast:{key}",
            "dative_verbs",
            key,
            answer,
            wrong,
            {lang: {"prompt": t["contrast"]} for lang, t in PROMPT.items()},
            {"kind": "contrast"},
        )
    for key, frame, answer, wrong in PLURAL_N:
        add_q(
            f"{KEY_PREFIX}:pl:{key}",
            "dative_verbs",
            key,
            answer,
            wrong,
            {lang: {"prompt": t["plural_n"].format(frame=frame)} for lang, t in PROMPT.items()},
            {"kind": "plural_dat_n"},
        )

    def article_distractors(article: str, lemma: str) -> list[str]:
        dat = f"{dat_article(article)} {lemma}"
        pool = [
            f"{akk_article(article)} {lemma}",
            f"{article} {lemma}",
            f"dem {lemma}",
            f"der {lemma}",
            f"den {lemma}",
            f"die {lemma}",
            f"das {lemma}",
        ]
        return [p for p in pool if p != dat]

    # 4) Article morph: Group A/B frames × person nouns
    # Blank replaces the whole dative NP: "Ich helfe ___. (der Arzt)" → "dem Arzt"
    for verb, frame_tmpl in GROUP_A_FRAMES + GROUP_B_FRAMES:
        for lemma, article, dat, _akk, _nom in PERSON_NOUNS:
            # Avoid awkward same-lemma subject/object (Der Mann antwortet dem Mann)
            if lemma in frame_tmpl:
                continue
            frame = f"{frame_tmpl} ({article} {lemma})"
            add_q(
                f"{KEY_PREFIX}:art:{verb}:{lemma}",
                "dative_verbs",
                lemma,
                dat,
                article_distractors(article, lemma),
                {lang: {"prompt": t["dat_art"].format(frame=frame)} for lang, t in PROMPT.items()},
                {"kind": "dat_article", "verb": verb, "article": article},
            )

    # 5) More article drills from vocab person nouns × core verbs
    core_verbs = [
        ("helfen", "Ich helfe ___."),
        ("antworten", "Ich antworte ___."),
        ("danken", "Ich danke ___."),
        ("glauben", "Ich glaube ___."),
        ("gratulieren", "Wir gratulieren ___."),
        ("gefallen", "Das gefällt ___."),
        ("gehören", "Das gehört ___."),
        ("schmecken", "Das schmeckt ___."),
        ("passen", "Die Schuhe passen ___ gut."),
        ("stehen", "Die Jacke steht ___ gut."),
    ]
    for noun in personish:
        lemma = noun["lemma"]
        article = noun["article"]
        dat = f"{dat_article(article)} {lemma}"
        for verb, tmpl in core_verbs:
            frame = f"{tmpl} ({article} {lemma})"
            add_q(
                f"{KEY_PREFIX}:art2:{verb}:{lemma}",
                "dative_verbs",
                lemma,
                dat,
                article_distractors(article, lemma),
                {lang: {"prompt": t["dat_art"].format(frame=frame)} for lang, t in PROMPT.items()},
                {"kind": "dat_article_vocab", "verb": verb, "article": article},
                noun.get("import_key"),
            )

    # Pronoun paradigm recall (Nom → Dat)
    pron_map = [
        ("ich", "mir", ["mich", "ich", "dir"]),
        ("du", "dir", ["dich", "du", "mir"]),
        ("er", "ihm", ["ihn", "er", "ihr"]),
        ("sie (she)", "ihr", ["sie", "ihm", "ihn"]),
        ("es", "ihm", ["es", "ihn", "ihr"]),
        ("wir", "uns", ["wir", "euch", "ihnen"]),
        ("ihr (you pl.)", "euch", ["ihr", "uns", "ihnen"]),
        ("sie (they)", "ihnen", ["sie", "ihr", "ihm"]),
        ("Sie (formal)", "Ihnen", ["Sie", "ihr", "ihnen"]),
    ]
    for owner, answer, wrong in pron_map:
        key = owner.replace(" ", "-").replace("(", "").replace(")", "").replace(".", "")
        add_q(
            f"{KEY_PREFIX}:paradigm:{key}",
            "dative_verbs",
            key,
            answer,
            wrong,
            {
                lang: {
                    "prompt": t["dat_pron"].format(
                        frame=f"Nominativ \"{owner}\" → Dativ ___?"
                    )
                }
                for lang, t in PROMPT.items()
            },
            {"kind": "pron_paradigm"},
            allow_case_variants=answer in {"Ihnen", "Sie"},
        )

    # 6) Question-word morph with clear role cues
    for noun in personish:
        lemma = noun["lemma"]
        article = noun["article"]
        add_q(
            f"{KEY_PREFIX}:qw-morph:wer:{lemma}",
            "case_questions",
            lemma,
            "Wer",
            ["Wen", "Wem", "Was"],
            {
                lang: {
                    "prompt": t["qw"].format(
                        frame=f"___ hilft dem Lehrer? (asking for Nominativ person: {article} {lemma})"
                    )
                }
                for lang, t in PROMPT.items()
            },
            {"kind": "qw_morph", "case": "nom", "target": "person"},
            noun.get("import_key"),
        )
        add_q(
            f"{KEY_PREFIX}:qw-morph:wen:{lemma}",
            "case_questions",
            lemma,
            "Wen",
            ["Wer", "Wem", "Was"],
            {
                lang: {
                    "prompt": t["qw"].format(
                        frame=(
                            f"Ich sehe {akk_article(article)} {lemma}. "
                            f"→ ___ siehst du? (Akkusativ person)"
                        )
                    )
                }
                for lang, t in PROMPT.items()
            },
            {"kind": "qw_morph", "case": "akk", "target": "person"},
            noun.get("import_key"),
        )
        # Don't put the answer "Wem" while also showing the full dative NP as freebie only — OK
        add_q(
            f"{KEY_PREFIX}:qw-morph:wem:{lemma}",
            "case_questions",
            lemma,
            "Wem",
            ["Wer", "Wen", "Was"],
            {
                lang: {
                    "prompt": t["qw"].format(
                        frame=f"Ich helfe {dat_article(article)} {lemma}. → ___ hilfst du?"
                    )
                }
                for lang, t in PROMPT.items()
            },
            {"kind": "qw_morph", "case": "dat", "target": "person"},
            noun.get("import_key"),
        )

    for noun in thing_nouns:
        lemma = noun["lemma"]
        article = noun["article"]
        add_q(
            f"{KEY_PREFIX}:qw-morph:was-nom:{lemma}",
            "case_questions",
            lemma,
            "Was",
            ["Wer", "Wen", "Wem"],
            {
                lang: {
                    "prompt": t["qw"].format(
                        frame=f"___ gefällt dir? (thing subject: {article} {lemma})"
                    )
                }
                for lang, t in PROMPT.items()
            },
            {"kind": "qw_morph", "case": "nom", "target": "thing"},
            noun.get("import_key"),
        )
        add_q(
            f"{KEY_PREFIX}:qw-morph:was-akk:{lemma}",
            "case_questions",
            lemma,
            "Was",
            ["Wer", "Wen", "Wem"],
            {
                lang: {
                    "prompt": t["qw"].format(
                        frame=f"Ich kaufe {akk_article(article)} {lemma}. → ___ kaufst du?"
                    )
                }
                for lang, t in PROMPT.items()
            },
            {"kind": "qw_morph", "case": "akk", "target": "thing"},
            noun.get("import_key"),
        )

    # Prep morph: real frames; answer is prep+wem/wen (noun only varies the import key for volume)
    dat_preps = [("mit", "Mit wem"), ("bei", "Bei wem"), ("von", "Von wem"), ("zu", "Zu wem")]
    akk_preps = [("für", "Für wen"), ("ohne", "Ohne wen")]
    prep_wrong = {
        "mit": ["Mit wen", "Für wen", "Bei wem"],
        "bei": ["Bei wen", "Mit wen", "Für wen"],
        "von": ["Von wen", "Für wen", "Mit wen"],
        "zu": ["Zu wen", "Für wen", "Mit wen"],
        "für": ["Für wem", "Mit wem", "Ohne wem"],
        "ohne": ["Ohne wem", "Mit wem", "Für wem"],
    }
    for prep, answer in dat_preps + akk_preps:
        wrong = prep_wrong[prep]
        frame = PREP_FRAMES[prep]
        for noun in personish:
            lemma = noun["lemma"]
            add_q(
                f"{KEY_PREFIX}:prep-morph:{prep}:{lemma}",
                "case_questions",
                lemma,
                answer,
                wrong,
                {
                    lang: {
                        "prompt": t["prep_qw"].format(
                            frame=f"{frame} (about {noun['article']} {lemma})"
                        )
                    }
                    for lang, t in PROMPT.items()
                },
                {"kind": "prep_morph", "prep": prep},
                noun.get("import_key"),
            )

    by_key = {q["import_key"]: q for q in questions}
    expected = {
        f"{KEY_PREFIX}:case:helfen": "Dative",
        f"{KEY_PREFIX}:group:gefallen": GROUP_LABELS["en"]["B"],
        f"{KEY_PREFIX}:pron:helfen-dir": "dir",
        f"{KEY_PREFIX}:leid:leid-ihm": "ihm",
        f"{KEY_PREFIX}:qw:wem-dat": "Wem",
        f"{KEY_PREFIX}:prep:mit-wem": "Mit wem",
        f"{KEY_PREFIX}:conj:hilft": "hilft",
        f"{KEY_PREFIX}:sep:zuhoeren": "Die Kinder hören der Oma zu.",
        f"{KEY_PREFIX}:pl:leute": "den Leuten",
        f"{KEY_PREFIX}:art:helfen:Mann": "dem Mann",
        f"{KEY_PREFIX}:art:gefallen:Frau": "der Frau",
        f"{KEY_PREFIX}:art2:helfen:Arzt": "dem Arzt",
        f"{KEY_PREFIX}:prep-morph:mit:Freund": "Mit wem",
    }

    # Frame quality: never "___ Lemma" when answer is "dem Lemma"
    for question in questions:
        meta = question.get("meta") or {}
        if meta.get("kind") in {"dat_article", "dat_article_vocab"}:
            lemma = question["source_lemma"]
            prompt = question["translations"]["en"]["prompt"]
            assert f"___ {lemma}" not in prompt, f"double-noun frame: {question['import_key']}"
            assert question["answer"].endswith(lemma)
            assert "Akkusativ" not in question["source_lemma"]
            assert question["source_lemma"] in PERSON_LEMMAS
    for key, answer in expected.items():
        actual = by_key.get(key, {}).get("answer")
        assert actual == answer, f"{key}: expected {answer!r}, got {actual!r}"

    for question in questions:
        assert question["answer"] not in question["distractors"]
        assert len(question["distractors"]) == 3
        assert len(set(question["distractors"])) == 3
        meta = question.get("meta") or {}
        opts = [question["answer"], *question["distractors"]]
        allows_case = any(o in {"Ihnen", "ihnen", "Sie", "sie"} for o in opts) and (
            "formal" in question["import_key"] or "Ihnen" in opts
        )
        if not allows_case:
            lowers = [o.casefold() for o in opts]
            assert len(lowers) == len(set(lowers)), f"case-only option collision: {question['import_key']}"
        for lang in ("en", "es", "fa", "ar", "tr"):
            assert question["translations"][lang]["prompt"].strip()
        blob = " ".join(t["prompt"] for t in question["translations"].values())
        for token in ("PDF", "Page ", "Section ", "handwritten", "Exercise ", "Seite ", "Seventh lesson"):
            assert token not in blob, f"forbidden token in {question['import_key']}: {token}"
        if meta.get("kind") in {"dat_pron", "leid_geht"}:
            assert "(" in question["translations"]["en"]["prompt"], (
                f"ambiguous pronoun prompt: {question['import_key']}"
            )

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
            "source_lesson": "german_grammar_lesson_08.md",
        },
        "questions": questions,
    }
    OUT_PATH.write_text(json.dumps(payload, ensure_ascii=False, indent=2), encoding="utf-8")
    print(f"wrote {OUT_PATH} count={len(questions)}")
    print(Counter(q["topic"] for q in questions))
    print(Counter((q.get("meta") or {}).get("kind") for q in questions))


if __name__ == "__main__":
    main()
