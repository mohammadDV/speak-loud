#!/usr/bin/env python3
"""Add / refresh lesson 04 (plurals, articles, negation) in german_a1_grammar_course.json."""

from __future__ import annotations

import json
from pathlib import Path

DIR = Path(__file__).resolve().parent
COURSE_PATH = DIR / "german_a1_grammar_course.json"
EN_SOURCE = DIR / "german_grammar_lesson_04.md"

LESSON_CODE = "plurals-articles-negation"

TITLES = {
    "en": "Plurals, Articles & Negation",
    "es": "Plurales, artículos y negación",
    "fa": "جمع، حروف تعریف و نفی",
    "ar": "الجمع وأدوات التعريف والنفي",
    "tr": "Çoğullar, artikel ve olumsuzluk",
}

COURSE_DESCRIPTIONS = {
    "en": "Step-by-step A1 German grammar: pronouns, modals, questions, plurals, articles, and negation.",
    "es": "Gramática alemana A1 paso a paso: pronombres, modales, preguntas, plurales, artículos y negación.",
    "fa": "گرامر آلمانی A1 گام‌به‌گام: ضمایر، افعال کمکی، سؤال‌ها، جمع، حروف تعریف و نفی.",
    "ar": "قواعد ألمانية A1 خطوة بخطوة: الضمائر والأسئلة والجمع وأدوات التعريف والنفي.",
    "tr": "Adım adım A1 Almanca dilbilgisi: zamirler, modallar, sorular, çoğullar, artikel ve olumsuzluk.",
}

ES_BODY = """# Plurales, artículos y negación (A1/A2)

Aprende **formas de plural**, **artículos definidos e indefinidos** (nominativo, acusativo, dativo) y la negación con ***nicht* vs. *kein***.

---

## Paso 1: Plurales

En plural, el artículo definido es **die** (nominativo/acusativo). Hay **cinco terminaciones** principales, a veces con **umlaut**.

| Terminación | Tipico | Ejemplos |
| --- | --- | --- |
| **-(e)n** | *-ung, -heit, -e, -in→-innen* | die Frau → die Frauen |
| **-er** | neutros cortos; a menudo umlaut | das Kind → die Kinder; das Buch → die Bücher |
| **-e** | muchos sustantivos cortos | der Stuhl → die Stühle |
| **–** | *-er, -en, -el, -chen* | der Vater → die Väter; das Brötchen → die Brötchen |
| **-s** | *-a/-i/-o/-y*, préstamos | das Taxi → die Taxis |

---

## Paso 2: Artículos

| Género | Indefinido Nom/Akk/Dat | Definido Nom/Akk/Dat |
| --- | --- | --- |
| Masculino | ein / **einen** / einem | der / **den** / dem |
| Neutro | ein / ein / einem | das / das / dem |
| Femenino | eine / eine / einer | die / die / der |
| Plural | – / – / – | die / die / den |

Solo el **masculino** cambia en acusativo (*der→den*, *ein→einen*).

* **ein/eine:** primera mención
* **der/das/die:** segunda mención / conocido
* **Sin artículo:** incontables, plural indefinido, profesiones con *sein*

---

## Paso 3: *nicht* vs. *kein*

* **kein-** delante de un sustantivo con *ein/eine* o sin artículo: *keine Kinder, keinen Hunger, kein Auto*
* **nicht** para verbos, adjetivos, artículos definidos/posesivos y complementos preposicionales: *nicht verheiratet, nicht in Köln, den Apfel nicht*
"""

FA_BODY = """# جمع، حروف تعریف و نفی (A1/A2)

یاد بگیرید **جمع اسم‌ها**، **حروف تعریف معین و نامعین** (Nominativ / Akkusativ / Dativ) و نفی با ***nicht* در برابر *kein***.

---

## گام ۱: جمع

در جمع، حرف تعریف معین **die** است. پنج پایان اصلی وجود دارد؛ گاهی با اوملاوت.

| پایان | نمونه | مثال |
| --- | --- | --- |
| **-(e)n** | *-ung, -e, -innen* | die Frau → die Frauen |
| **-er** | خنثی کوتاه | das Kind → die Kinder |
| **-e** | اسم‌های کوتاه | der Stuhl → die Stühle |
| **–** | *-er/-en/-el/-chen* | der Vater → die Väter |
| **-s** | وام‌واژه‌ها | das Taxi → die Taxis |

---

## گام ۲: حروف تعریف

| جنس | نامعین Nom/Akk/Dat | معین Nom/Akk/Dat |
| --- | --- | --- |
| مذکر | ein / **einen** / einem | der / **den** / dem |
| خنثی | ein / ein / einem | das / das / dem |
| مؤنث | eine / eine / einer | die / die / der |
| جمع | – | die / die / den |

فقط **مذکر** در Akkusativ عوض می‌شود.

---

## گام ۳: *nicht* و *kein*

* **kein-** قبل از اسم با *ein* یا بدون حرف تعریف
* **nicht** برای فعل، صفت، حرف تعریف معین/ملکی و عبارت حرف اضافه‌ای
"""

AR_BODY = """# الجمع وأدوات التعريف والنفي (A1/A2)

تعلّم **جمع الأسماء** و**أدوات التعريف والنكرة** (Nominativ / Akkusativ / Dativ) والنفي بـ ***nicht* مقابل *kein***.

---

## الخطوة 1: الجمع

في الجمع تكون أداة التعريف **die**. هناك خمس نهايات رئيسية، وأحيانًا مع أملاوت.

| النهاية | أمثلة |
| --- | --- |
| **-(e)n** | die Frau → die Frauen |
| **-er** | das Kind → die Kinder |
| **-e** | der Stuhl → die Stühle |
| **–** | der Vater → die Väter |
| **-s** | das Taxi → die Taxis |

---

## الخطوة 2: الأدوات

| الجنس | نكرة Nom/Akk/Dat | معرفة Nom/Akk/Dat |
| --- | --- | --- |
| مذكر | ein / **einen** / einem | der / **den** / dem |
| محايد | ein / ein / einem | das / das / dem |
| مؤنث | eine / eine / einer | die / die / der |
| جمع | – | die / die / den |

يتغير المذكر فقط في المفعول به.

---

## الخطوة 3: *nicht* و *kein*

* **kein-** قبل الاسم مع *ein* أو بلا أداة
* **nicht** للأفعال والصفات وأدوات المعرفة والعبارات الجرّية
"""

TR_BODY = """# Çoğullar, artikel ve olumsuzluk (A1/A2)

**Çoğul biçimler**, **belirli/belirsiz artikel** (Nominativ / Akkusativ / Dativ) ve ***nicht* / *kein*** olumsuzluğunu öğrenin.

---

## Adım 1: Çoğullar

Çoğulda belirli artikel **die**’dir. Beş ana çoğul eki vardır; bazen ümlaut gelir.

| Ek | Örnekler |
| --- | --- |
| **-(e)n** | die Frau → die Frauen |
| **-er** | das Kind → die Kinder |
| **-e** | der Stuhl → die Stühle |
| **–** | der Vater → die Väter |
| **-s** | das Taxi → die Taxis |

---

## Adım 2: Artikel

| Cins | Belirsiz Nom/Akk/Dat | Belirli Nom/Akk/Dat |
| --- | --- | --- |
| Eril | ein / **einen** / einem | der / **den** / dem |
| Nötr | ein / ein / einem | das / das / dem |
| Dişil | eine / eine / einer | die / die / der |
| Çoğul | – | die / die / den |

Yalnızca **eril** akkuzatifte değişir.

---

## Adım 3: *nicht* ve *kein*

* **kein-**: *ein*’li veya artikelsiz isimden önce
* **nicht**: fiil, sıfat, belirli/iyelik artikel, edat öbeği
"""


def main() -> None:
    course = json.loads(COURSE_PATH.read_text(encoding="utf-8"))
    en_body = EN_SOURCE.read_text(encoding="utf-8").strip() + "\n"

    ordered = [
        "pronouns-and-present-tense",
        "modal-verbs",
        "questions-and-word-order",
        LESSON_CODE,
    ]
    existing = [
        item.get("code")
        for item in (course.get("lessons") or [])
        if isinstance(item, dict) and item.get("code")
    ]
    lesson_codes = list(dict.fromkeys([*ordered, *[c for c in existing if c not in ordered]]))

    course["meta"] = {
        "course_code": "de-grammar-a1",
        "lesson_codes": lesson_codes,
        "translation_languages": ["en", "es", "fa", "ar", "tr"],
    }

    for translation in course["course"]["translations"]:
        lang = translation["language_code"]
        if lang in COURSE_DESCRIPTIONS:
            translation["description"] = COURSE_DESCRIPTIONS[lang]

    bodies = {
        "en": en_body,
        "es": ES_BODY.strip() + "\n",
        "fa": FA_BODY.strip() + "\n",
        "ar": AR_BODY.strip() + "\n",
        "tr": TR_BODY.strip() + "\n",
    }

    lesson = {
        "code": LESSON_CODE,
        "cefr_level": "A1",
        "status": "published",
        "position": 4,
        "translations": [
            {
                "language_code": lang,
                "title": TITLES[lang],
                "body_markdown": bodies[lang],
            }
            for lang in ("en", "es", "fa", "ar", "tr")
        ],
    }

    lessons = [item for item in (course.get("lessons") or []) if item.get("code") != LESSON_CODE]
    lessons.append(lesson)
    lessons.sort(key=lambda item: int(item.get("position") or 0))
    course["lessons"] = lessons

    COURSE_PATH.write_text(json.dumps(course, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
    print(f"updated {COURSE_PATH} lessons={[item['code'] for item in course['lessons']]}")


if __name__ == "__main__":
    main()
