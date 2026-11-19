#!/usr/bin/env python3
"""Add / refresh lesson 06 (prepositions time & place) in german_a1_grammar_course.json."""

from __future__ import annotations

import json
from pathlib import Path

DIR = Path(__file__).resolve().parent
COURSE_PATH = DIR / "german_a1_grammar_course.json"
EN_SOURCE = DIR / "german_grammar_lesson_06.md"

LESSON_CODE = "prepositions-time-and-place"

TITLES = {
    "en": "Prepositions: Time & Place",
    "es": "Preposiciones: tiempo y lugar",
    "fa": "حروف اضافه: زمان و مکان",
    "ar": "حروف الجر: الزمان والمكان",
    "tr": "Edatlar: zaman ve yer",
}

COURSE_DESCRIPTIONS = {
    "en": "Step-by-step A1 German grammar: pronouns, modals, questions, articles, cases, and prepositions of time and place.",
    "es": "Gramática alemana A1 paso a paso: pronombres, modales, preguntas, artículos, casos y preposiciones de tiempo y lugar.",
    "fa": "گرامر آلمانی A1 گام‌به‌گام: ضمایر، افعال کمکی، سؤال‌ها، حروف تعریف، حالت‌ها و حروف اضافهٔ زمان و مکان.",
    "ar": "قواعد ألمانية A1 خطوة بخطوة: الضمائر والأفعال المساعدة والأسئلة وأدوات التعريف والحالات وحروف جر الزمان والمكان.",
    "tr": "Adım adım A1 Almanca dilbilgisi: zamirler, modaller, sorular, artikeller, haller ve zaman-yer edatları.",
}

ES_BODY = """# Preposiciones: tiempo y lugar (A1/A2)

Aprende **preposiciones temporales**, **solo dativo**, **solo acusativo (FUDOG)**, **Wechselpräpositionen** y el sistema **Wohin? / Wo? / Woher?**.

---

## Paso 1: Tiempo

| Preposición | Uso | Ejemplos |
| --- | --- | --- |
| **im** | estaciones, meses | **im** Sommer, **im** Januar |
| **am** | días, partes del día (excepto noche), fechas | **am** Montag, **am** Morgen |
| **in der** | noche | **in der** Nacht |
| **um** | horas | **um** 9 Uhr |
| **seit** | desde / durante (+ Dat.) | **seit** zwei Jahren |
| **vor / nach** | antes / después (+ Dat.) | **nach** dem Essen |

Los años van **sin** preposición: *1997*, *2025*.

---

## Paso 2: Siempre dativo

**aus, bei, mit, nach, seit, von, zu**

Contracciones: **beim, vom, zum, zur**

* *mit dem Bus* · *zum Bahnhof* · *nach Berlin* (ciudad sin artículo)

---

## Paso 3: Siempre acusativo (FUDOG)

**für, um, durch, ohne, gegen**

* *für dich* · *durch den Park* · *ohne Zucker*

---

## Paso 4: Wechselpräpositionen

**in, an, auf, vor, hinter, über, unter, neben, zwischen**

* **Wo?** → **Dativo** (*auf dem Tisch*)
* **Wohin?** → **Acusativo** (*auf den Tisch*)

---

## Paso 5: Matriz local

| Tipo | Wohin? | Wo? | Woher? |
| --- | --- | --- | --- |
| Ciudad sin artículo | nach | in | aus |
| Lugar cerrado / país con artículo | in + Akk. | in + Dat. | aus + Dat. |
| Personas / empresas | zu | bei | von |

Casa: *nach Hause* · *zu Hause* · *von zu Hause*
"""

FA_BODY = """# حروف اضافه: زمان و مکان (A1/A2)

یاد بگیرید **حروف اضافهٔ زمانی**، **فقط Dativ**، **فقط Akkusativ (FUDOG)**، **Wechselpräpositionen** و سیستم **Wohin? / Wo? / Woher?**.

---

## گام ۱: زمان

| حرف اضافه | کاربرد | مثال |
| --- | --- | --- |
| **im** | فصل‌ها، ماه‌ها | **im** Sommer |
| **am** | روزها، اوقات روز (جز شب) | **am** Montag |
| **in der** | شب | **in der** Nacht |
| **um** | ساعت | **um** 9 Uhr |
| **seit** | از / به مدت (+ Dat.) | **seit** zwei Jahren |

سال‌ها **بدون** حرف اضافه: *2025*.

---

## گام ۲: همیشه Dativ

**aus, bei, mit, nach, seit, von, zu**

انقباض: **beim, vom, zum, zur**

---

## گام ۳: همیشه Akkusativ (FUDOG)

**für, um, durch, ohne, gegen**

---

## گام ۴: Wechselpräpositionen

* **Wo?** → **Dativ**
* **Wohin?** → **Akkusativ**

---

## گام ۵: ماتریس مکانی

شهر بدون حرف تعریف: *nach / in / aus* · خانه: *nach Hause / zu Hause / von zu Hause*
"""

AR_BODY = """# حروف الجر: الزمان والمكان (A1/A2)

تعلّم **حروف الجر الزمنية** و**الداتيف فقط** و**الأكوزاتيف فقط (FUDOG)** و**Wechselpräpositionen** ونظام **Wohin? / Wo? / Woher?**.

---

## الخطوة 1: الزمان

| حرف الجر | الاستخدام | أمثلة |
| --- | --- | --- |
| **im** | الفصول والأشهر | **im** Sommer |
| **am** | الأيام وأوقات النهار | **am** Montag |
| **in der** | الليل | **in der** Nacht |
| **um** | الساعة | **um** 9 Uhr |
| **seit** | منذ / لمدة (+ Dat.) | **seit** zwei Jahren |

السنوات **بدون** حرف جر: *2025*.

---

## الخطوة 2: دائمًا Dativ

**aus, bei, mit, nach, seit, von, zu** — اختصارات: **beim, vom, zum, zur**

---

## الخطوة 3: دائمًا Akkusativ (FUDOG)

**für, um, durch, ohne, gegen**

---

## الخطوة 4: Wechselpräpositionen

* **Wo?** → **Dativ**
* **Wohin?** → **Akkusativ**

---

## الخطوة 5: المصفوفة المكانية

مدينة بلا أداة: *nach / in / aus* · المنزل: *nach Hause / zu Hause / von zu Hause*
"""

TR_BODY = """# Edatlar: zaman ve yer (A1/A2)

**Zaman edatları**, **yalnızca datif**, **yalnızca akkuzatif (FUDOG)**, **Wechselpräpositionen** ve **Wohin? / Wo? / Woher?** sistemini öğrenin.

---

## Adım 1: Zaman

| Edat | Kullanım | Örnekler |
| --- | --- | --- |
| **im** | mevsimler, aylar | **im** Sommer |
| **am** | günler, günün saatleri | **am** Montag |
| **in der** | gece | **in der** Nacht |
| **um** | saat | **um** 9 Uhr |
| **seit** | -dir / -dır (+ Dat.) | **seit** zwei Jahren |

Yıllar **edatsız**: *2025*.

---

## Adım 2: Her zaman datif

**aus, bei, mit, nach, seit, von, zu** — kısaltmalar: **beim, vom, zum, zur**

---

## Adım 3: Her zaman akkuzatif (FUDOG)

**für, um, durch, ohne, gegen**

---

## Adım 4: Wechselpräpositionen

* **Wo?** → **Datif**
* **Wohin?** → **Akkuzatif**

---

## Adım 5: Yer matrisi

Artikelsiz şehir: *nach / in / aus* · ev: *nach Hause / zu Hause / von zu Hause*
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
        "position": 6,
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
