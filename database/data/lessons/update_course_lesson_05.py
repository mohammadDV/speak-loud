#!/usr/bin/env python3
"""Add / refresh lesson 05 (cases, pronouns, objects) in german_a1_grammar_course.json."""

from __future__ import annotations

import json
from pathlib import Path

DIR = Path(__file__).resolve().parent
COURSE_PATH = DIR / "german_a1_grammar_course.json"
EN_SOURCE = DIR / "german_grammar_lesson_05.md"

LESSON_CODE = "cases-pronouns-objects"

TITLES = {
    "en": "Cases, Pronouns & Objects",
    "es": "Casos, pronombres y objetos",
    "fa": "حالت‌ها، ضمایر و مفعول‌ها",
    "ar": "الحالات والضمائر والمفاعيل",
    "tr": "Hallar, zamirler ve nesneler",
}

COURSE_DESCRIPTIONS = {
    "en": "Step-by-step A1 German grammar: from pronouns and modals to cases, articles, and object word order.",
    "es": "Gramática alemana A1 paso a paso: de pronombres y modales a casos, artículos y orden de objetos.",
    "fa": "گرامر آلمانی A1 گام‌به‌گام: از ضمایر و افعال کمکی تا حالت‌ها، حروف تعریف و ترتیب مفعول‌ها.",
    "ar": "قواعد ألمانية A1 خطوة بخطوة: من الضمائر والأفعال المساعدة إلى الحالات وأدوات التعريف وترتيب المفاعيل.",
    "tr": "Adım adım A1 Almanca dilbilgisi: zamirlerden ve modallardan hallere, artikellere ve nesne sırasına.",
}

ES_BODY = """# Casos, pronombres y objetos (A1/A2)

Aprende **acusativo**, **dativo**, **pronombres personales** y el **orden con dos objetos**.

---

## Paso 1: Acusativo

Solo el **masculino** cambia: *der/ein → den/einen*. Neutro, femenino y plural no cambian.

| Género | Nominativo | Acusativo |
| --- | --- | --- |
| Masculino | der / ein Mann | **den / einen** Mann |
| Neutro | das / ein Kind | das / ein Kind |
| Femenino | die / eine Frau | die / eine Frau |
| Plural | die / – Leute | die / – Leute |

Preguntas: **Wen?** (personas), **Was?** (cosas).

---

## Paso 2: Dativo

* Masculino/neutro: **dem / einem**
* Femenino: **der / einer**
* Plural: **den** + suele añadir **-n** al sustantivo (*den Kindern*)

Preposiciones frecuentes: *mit, zu, bei, nach*. Pregunta: **Wem?**

---

## Paso 3: Pronombres

| Nom. | Akk. | Dat. |
| --- | --- | --- |
| ich | mich | mir |
| du | dich | dir |
| er | ihn | ihm |
| sie | sie | ihr |
| es | es | ihm |
| wir | uns | uns |
| ihr | euch | euch |
| sie | sie | ihnen |
| Sie | Sie | Ihnen |

Verbos dativos: *helfen, antworten, gefallen, passen*; *Wie geht es Ihnen?*

---

## Paso 4: Orden

Con dos objetos nominales: **dativo (persona) antes que acusativo (cosa)**.
* *Der Vater kauft dem Sohn einen Computer.*
"""

FA_BODY = """# حالت‌ها، ضمایر و مفعول‌ها (A1/A2)

یاد بگیرید **Akkusativ**، **Dativ**، **ضمایر شخصی** و **ترتیب دو مفعول**.

---

## گام ۱: Akkusativ

فقط **مذکر** عوض می‌شود: *der/ein → den/einen*.

| جنس | Nominativ | Akkusativ |
| --- | --- | --- |
| مذکر | der / ein Mann | **den / einen** Mann |
| خنثی | das / ein Kind | das / ein Kind |
| مؤنث | die / eine Frau | die / eine Frau |
| جمع | die / – Leute | die / – Leute |

سؤال: **Wen?** / **Was?**

---

## گام ۲: Dativ

* مذکر/خنثی: **dem / einem**
* مؤنث: **der / einer**
* جمع: **den** + معمولاً **-n** روی اسم (*den Kindern*)

حروف اضافه: *mit, zu, bei, nach*. سؤال: **Wem?**

---

## گام ۳: ضمایر

| Nom. | Akk. | Dat. |
| --- | --- | --- |
| ich | mich | mir |
| du | dich | dir |
| er | ihn | ihm |
| sie | sie | ihr |
| es | es | ihm |
| wir | uns | uns |
| ihr | euch | euch |
| sie | sie | ihnen |
| Sie | Sie | Ihnen |

افعال Dativ: *helfen, antworten, gefallen, passen*

---

## گام ۴: ترتیب

دو مفعول اسمی: **Dativ (شخص) قبل از Akkusativ (چیز)**.
"""

AR_BODY = """# الحالات والضمائر والمفاعيل (A1/A2)

تعلّم **Akkusativ** و**Dativ** و**الضمائر الشخصية** و**ترتيب المفعولين**.

---

## الخطوة 1: Akkusativ

يتغير المذكر فقط: *der/ein → den/einen*.

| الجنس | Nominativ | Akkusativ |
| --- | --- | --- |
| مذكر | der / ein Mann | **den / einen** Mann |
| محايد | das / ein Kind | das / ein Kind |
| مؤنث | die / eine Frau | die / eine Frau |
| جمع | die / – Leute | die / – Leute |

السؤال: **Wen?** / **Was?**

---

## الخطوة 2: Dativ

* مذكر/محايد: **dem / einem**
* مؤنث: **der / einer**
* جمع: **den** وغالبًا **-n** للاسم (*den Kindern*)

حروف الجر: *mit, zu, bei, nach*. السؤال: **Wem?**

---

## الخطوة 3: الضمائر

| Nom. | Akk. | Dat. |
| --- | --- | --- |
| ich | mich | mir |
| du | dich | dir |
| er | ihn | ihm |
| sie | sie | ihr |
| es | es | ihm |
| wir | uns | uns |
| ihr | euch | euch |
| sie | sie | ihnen |
| Sie | Sie | Ihnen |

أفعال داتيف: *helfen, antworten, gefallen, passen*

---

## الخطوة 4: الترتيب

مع مفعولين اسميين: **Dativ (شخص) قبل Akkusativ (شيء)**.
"""

TR_BODY = """# Hallar, zamirler ve nesneler (A1/A2)

**Akkuzatif**, **datif**, **kişi zamirleri** ve **iki nesneli sözdizimini** öğrenin.

---

## Adım 1: Akkuzatif

Yalnızca **eril** değişir: *der/ein → den/einen*.

| Cins | Nominativ | Akkuzatif |
| --- | --- | --- |
| Eril | der / ein Mann | **den / einen** Mann |
| Nötr | das / ein Kind | das / ein Kind |
| Dişil | die / eine Frau | die / eine Frau |
| Çoğul | die / – Leute | die / – Leute |

Soru: **Wen?** / **Was?**

---

## Adım 2: Datif

* Eril/nötr: **dem / einem**
* Dişil: **der / einer**
* Çoğul: **den** + genelde isme **-n** (*den Kindern*)

Edatlar: *mit, zu, bei, nach*. Soru: **Wem?**

---

## Adım 3: Zamirler

| Nom. | Akk. | Dat. |
| --- | --- | --- |
| ich | mich | mir |
| du | dich | dir |
| er | ihn | ihm |
| sie | sie | ihr |
| es | es | ihm |
| wir | uns | uns |
| ihr | euch | euch |
| sie | sie | ihnen |
| Sie | Sie | Ihnen |

Datif fiiller: *helfen, antworten, gefallen, passen*

---

## Adım 4: Sıra

İki isim nesnesinde: **datif (kişi) akkuzatiften (nesne) önce**.
"""


def main() -> None:
    course = json.loads(COURSE_PATH.read_text(encoding="utf-8"))
    en_body = EN_SOURCE.read_text(encoding="utf-8").strip() + "\n"

    ordered = [
        "pronouns-and-present-tense",
        "modal-verbs",
        "questions-and-word-order",
        "plurals-articles-negation",
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
        "position": 5,
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
