#!/usr/bin/env python3
"""Add / refresh lesson 07 (possessives, welch-, dies-) in german_a1_grammar_course.json."""

from __future__ import annotations

import json
from pathlib import Path

DIR = Path(__file__).resolve().parent
COURSE_PATH = DIR / "german_a1_grammar_course.json"
EN_SOURCE = DIR / "german_grammar_lesson_07.md"

LESSON_CODE = "possessives-welcher-dieser"

TITLES = {
    "en": "Possessives, welcher & dieser",
    "es": "Posesivos, welcher y dieser",
    "fa": "ملکی، welcher و dieser",
    "ar": "ضمائر الملكية و welcher و dieser",
    "tr": "İyelikler, welcher ve dieser",
}

COURSE_DESCRIPTIONS = {
    "en": "Step-by-step German grammar A1–A2: pronouns, modals, questions, articles, cases, prepositions, and possessives.",
    "es": "Gramática alemana A1–A2 paso a paso: pronombres, modales, preguntas, artículos, casos, preposiciones y posesivos.",
    "fa": "گرامر آلمانی A1–A2 گام‌به‌گام: ضمایر، افعال کمکی، سؤال‌ها، حروف تعریف، حالت‌ها، حروف اضافه و ملکی‌ها.",
    "ar": "قواعد ألمانية A1–A2 خطوة بخطوة: الضمائر والأفعال المساعدة والأسئلة وأدوات التعريف والحالات وحروف الجر وضمائر الملكية.",
    "tr": "Adım adım Almanca dilbilgisi A1–A2: zamirler, modaller, sorular, artikeller, haller, edatlar ve iyelikler.",
}

ES_BODY = """# Posesivos, welcher y dieser (A2)

Aprende **artículos posesivos** (*mein, dein, sein…*), **welch-** y **dies-** en Nominativ, Akkusativ y Dativ.

---

## Paso 1: Posesivos

| Dueño | Raíz |
| --- | --- |
| ich | mein- |
| du | dein- |
| er / es | sein- |
| sie (ella) | ihr- |
| wir | unser- |
| ihr (vosotros) | euer- / eur- |
| sie (ellos) | ihr- |
| Sie | Ihr- |

*euer* pierde la **-e-** interior con terminación: *eure, euren, eurem, eurer*.

Las terminaciones son como **kein-**.

---

## Paso 2: welch- / dies-

Terminaciones como **der / das / die**.

| Caso | Masc. | Neut. | Fem. | Pl. |
| --- | --- | --- | --- | --- |
| Nom. | -er | -es | -e | -e |
| Akk. | -en | -es | -e | -e |
| Dat. | -em | -em | -er | -en |
"""

FA_BODY = """# ملکی، welcher و dieser (A2)

یاد بگیرید **حروف تعریف ملکی** (*mein, dein, sein…*)، **welch-** و **dies-** در Nominativ، Akkusativ و Dativ.

---

## گام ۱: ملکی‌ها

| مالک | ریشه |
| --- | --- |
| ich | mein- |
| du | dein- |
| er / es | sein- |
| sie (او مؤنث) | ihr- |
| wir | unser- |
| ihr (شما جمع) | euer- / eur- |
| sie (آن‌ها) | ihr- |
| Sie | Ihr- |

*euer* با پایان **-e-** میانی را از دست می‌دهد: *eure, euren…*

پایان‌ها مانند **kein-**.

---

## گام ۲: welch- / dies-

پایان‌ها مانند **der / das / die**.
"""

AR_BODY = """# ضمائر الملكية و welcher و dieser (A2)

تعلّم **أدوات التعريف الملكية** (*mein, dein, sein…*) و**welch-** و**dies-** في Nominativ و Akkusativ و Dativ.

---

## الخطوة 1: الملكية

| المالك | الجذر |
| --- | --- |
| ich | mein- |
| du | dein- |
| er / es | sein- |
| sie (هي) | ihr- |
| wir | unser- |
| ihr (أنتم) | euer- / eur- |
| sie (هم) | ihr- |
| Sie | Ihr- |

*euer* تسقط **-e-** الداخلية مع النهاية: *eure, euren…*

النهايات مثل **kein-**.

---

## الخطوة 2: welch- / dies-

النهايات مثل **der / das / die**.
"""

TR_BODY = """# İyelikler, welcher ve dieser (A2)

**İyelik artikelleri** (*mein, dein, sein…*), **welch-** ve **dies-** biçimlerini Nominativ, Akkuzatif ve Datifte öğrenin.

---

## Adım 1: İyelikler

| Sahip | Gövde |
| --- | --- |
| ich | mein- |
| du | dein- |
| er / es | sein- |
| sie (o, dişil) | ihr- |
| wir | unser- |
| ihr (siz, çoğul) | euer- / eur- |
| sie (onlar) | ihr- |
| Sie | Ihr- |

*euer* ek alınca iç **-e-** düşer: *eure, euren…*

Ekler **kein-** ile aynıdır.

---

## Adım 2: welch- / dies-

Ekler **der / das / die** gibidir.
"""


def main() -> None:
    course = json.loads(COURSE_PATH.read_text(encoding="utf-8"))
    en_body = EN_SOURCE.read_text(encoding="utf-8").strip() + "\n"

    ordered = [
        "pronouns-and-present-tense",
        "modal-verbs",
        "questions-and-word-order",
        "plurals-articles-negation",
        "cases-pronouns-objects",
        "prepositions-time-and-place",
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
        "cefr_level": "A2",
        "status": "published",
        "position": 7,
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
