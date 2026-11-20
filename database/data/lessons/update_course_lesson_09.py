#!/usr/bin/env python3
"""Add / refresh lesson 09 (Präteritum sein/haben/modals) in german_a1_grammar_course.json."""

from __future__ import annotations

import json
from pathlib import Path

DIR = Path(__file__).resolve().parent
COURSE_PATH = DIR / "german_a1_grammar_course.json"
EN_SOURCE = DIR / "german_grammar_lesson_09.md"

LESSON_CODE = "praeteritum-sein-haben-modals"

TITLES = {
    "en": "Simple Past: sein, haben & Modals",
    "es": "Pretérito: sein, haben y modales",
    "fa": "گذشته ساده: sein، haben و افعال کمکی",
    "ar": "الماضي البسيط: sein و haben والأفعال المساعدة",
    "tr": "Geçmiş zaman: sein, haben ve modaller",
}

COURSE_DESCRIPTIONS = {
    "en": "Step-by-step German grammar A1–A2: pronouns, modals, questions, articles, cases, prepositions, possessives, dative verbs, and simple past.",
    "es": "Gramática alemana A1–A2 paso a paso: pronombres, modales, preguntas, artículos, casos, preposiciones, posesivos, verbos dativos y pretérito.",
    "fa": "گرامر آلمانی A1–A2 گام‌به‌گام: ضمایر، افعال کمکی، سؤال‌ها، حروف تعریف، حالت‌ها، حروف اضافه، ملکی‌ها، افعال داتیو و گذشته ساده.",
    "ar": "قواعد ألمانية A1–A2 خطوة بخطوة: الضمائر والأفعال المساعدة والأسئلة وأدوات التعريف والحالات وحروف الجر وضمائر الملكية وأفعال الداتيف والماضي البسيط.",
    "tr": "Adım adım Almanca dilbilgisi A1–A2: zamirler, modaller, sorular, artikeller, haller, edatlar, iyelikler, datif fiiller ve geçmiş zaman.",
}

ES_BODY = """# Pretérito (*Präteritum*): *sein*, *haben* y modales (A2)

Aprende cuándo usar el **Präteritum**, conjuga ***sein*** y ***haben***, y forma el pasado de los **modales**.

---

## Paso 1: ¿Cuándo usar *Präteritum*?

Con **sein**, **haben** y los **modales**, el alemán suele usar el *Präteritum* (no el *Perfekt*).

| Pasado | Presente |
| --- | --- |
| *gestern*, *früher*, *letztes Jahr* | *heute*, *jetzt* |

---

## Paso 2: *sein* y *haben*

**ich** = **er/sie/es** (sin desinencia extra).

| Pronombre | **sein** | **haben** |
| --- | --- | --- |
| ich / er | **war** | **hatte** |
| du | **warst** | **hattest** |
| wir / sie / Sie | **waren** | **hatten** |
| ihr | **wart** | **hattet** |

---

## Paso 3: Modales en *Präteritum*

1. **Sin umlaut:** *können* → **konnte**, *müssen* → **musste**, *dürfen* → **durfte**.
2. Desinencias: **-te / -test / -ten / -tet**.
3. No hay *Präteritum* de *möchten* → usa **wollte**.

Modal conjugado en **posición 2** + infinitivo al **final**: *Ich **musste** früher nie **kochen**.*
"""

FA_BODY = """# گذشته ساده (*Präteritum*): *sein*، *haben* و افعال کمکی (A2)

یاد بگیرید کی **Präteritum** به کار می‌رود، صرف ***sein*** و ***haben***، و گذشتهٔ **افعال کمکی**.

---

## گام ۱: کی *Präteritum*؟

با **sein**، **haben** و **افعال کمکی** معمولاً *Präteritum* می‌گویند (نه *Perfekt*).

| گذشته | حال |
| --- | --- |
| *gestern*، *früher*، *letztes Jahr* | *heute*، *jetzt* |

---

## گام ۲: *sein* و *haben*

**ich** = **er/sie/es**.

| ضمیر | **sein** | **haben** |
| --- | --- | --- |
| ich / er | **war** | **hatte** |
| du | **warst** | **hattest** |
| wir / sie / Sie | **waren** | **hatten** |
| ihr | **wart** | **hattet** |

---

## گام ۳: افعال کمکی در *Präteritum*

1. **بدون اوملاوت:** *können* → **konnte**، *müssen* → **musste**.
2. پایان‌ها: **-te / -test / -ten / -tet**.
3. برای گذشتهٔ خواستن: **wollte** (نه *möchte*).

فعل کمکی در **جایگاه ۲** + مصدر در **پایان**: *Ich **musste** früher nie **kochen**.*
"""

AR_BODY = """# الماضي البسيط (*Präteritum*): *sein* و *haben* والأفعال المساعدة (A2)

تعلّم متى يُستخدم **Präteritum**، وتصريف ***sein*** و ***haben***، وماضي **الأفعال المساعدة**.

---

## الخطوة 1: متى *Präteritum*؟

مع **sein** و **haben** و**الأفعال المساعدة** يُفضَّل *Präteritum* (لا *Perfekt*).

| ماضٍ | حاضر |
| --- | --- |
| *gestern*، *früher*، *letztes Jahr* | *heute*، *jetzt* |

---

## الخطوة 2: *sein* و *haben*

**ich** = **er/sie/es**.

| الضمير | **sein** | **haben** |
| --- | --- | --- |
| ich / er | **war** | **hatte** |
| du | **warst** | **hattest** |
| wir / sie / Sie | **waren** | **hatten** |
| ihr | **wart** | **hattet** |

---

## الخطوة 3: الأفعال المساعدة في *Präteritum*

1. **بدون أملاوت:** *können* → **konnte**، *müssen* → **musste**.
2. النهايات: **-te / -test / -ten / -tet**.
3. للماضي من الرغبة: **wollte** (وليس *möchte*).

الفعل المساعد في **الموضع 2** والمصدر في **النهاية**: *Ich **musste** früher nie **kochen**.*
"""

TR_BODY = """# Geçmiş zaman (*Präteritum*): *sein*, *haben* ve modaller (A2)

**Präteritum** ne zaman kullanılır, ***sein*** / ***haben*** nasıl çekilir ve **modallerin** geçmişi nasıl yapılır?

---

## Adım 1: Ne zaman *Präteritum*?

**sein**, **haben** ve **modaller** için Almanlar genelde *Präteritum* kullanır (*Perfekt* değil).

| Geçmiş | Şimdiki |
| --- | --- |
| *gestern*, *früher*, *letztes Jahr* | *heute*, *jetzt* |

---

## Adım 2: *sein* ve *haben*

**ich** = **er/sie/es**.

| Zamir | **sein** | **haben** |
| --- | --- | --- |
| ich / er | **war** | **hatte** |
| du | **warst** | **hattest** |
| wir / sie / Sie | **waren** | **hatten** |
| ihr | **wart** | **hattet** |

---

## Adım 3: Modallerin *Präteritum*u

1. **Umlaut yok:** *können* → **konnte**, *müssen* → **musste**.
2. Ekler: **-te / -test / -ten / -tet**.
3. *möchten* için geçmiş yok → **wollte**.

Modal **2. konumda**, fiil **sonsuz** sonda: *Ich **musste** früher nie **kochen**.*
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
        "possessives-welcher-dieser",
        "dative-verbs-wer-wen-wem",
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
        "position": 9,
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
