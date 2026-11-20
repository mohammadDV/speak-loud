#!/usr/bin/env python3
"""Add / refresh lesson 11 (reflexive verbs) in german_a1_grammar_course.json."""

from __future__ import annotations

import json
from pathlib import Path

DIR = Path(__file__).resolve().parent
COURSE_PATH = DIR / "german_a1_grammar_course.json"
EN_SOURCE = DIR / "german_grammar_lesson_11.md"

LESSON_CODE = "reflexive-verbs-and-pronouns"

TITLES = {
    "en": "Reflexive Verbs & Pronouns",
    "es": "Verbos y pronombres reflexivos",
    "fa": "افعال و ضمایر انعکاسی",
    "ar": "الأفعال والضمائر الانعكاسية",
    "tr": "Dönüşlü fiiller ve zamirler",
}

COURSE_DESCRIPTIONS = {
    "en": "Step-by-step German grammar A1–A2: pronouns, modals, questions, articles, cases, prepositions, possessives, dative verbs, simple past, Perfekt, and reflexive verbs.",
    "es": "Gramática alemana A1–A2 paso a paso: pronombres, modales, preguntas, artículos, casos, preposiciones, posesivos, verbos dativos, pretérito, Perfekt y verbos reflexivos.",
    "fa": "گرامر آلمانی A1–A2 گام‌به‌گام: ضمایر، افعال کمکی، سؤال‌ها، حروف تعریف، حالت‌ها، حروف اضافه، ملکی‌ها، افعال داتیو، گذشته ساده، Perfekt و افعال انعکاسی.",
    "ar": "قواعد ألمانية A1–A2 خطوة بخطوة: الضمائر والأفعال المساعدة والأسئلة وأدوات التعريف والحالات وحروف الجر وضمائر الملكية وأفعال الداتيف والماضي البسيط وPerfekt والأفعال الانعكاسية.",
    "tr": "Adım adım Almanca dilbilgisi A1–A2: zamirler, modaller, sorular, artikeller, haller, edatlar, iyelikler, datif fiiller, geçmiş zaman, Perfekt ve dönüşlü fiiller.",
}

ES_BODY = """# Verbos y pronombres reflexivos (*Reflexive Verben*) (A2)

Aprende cuándo un verbo es **reflexivo**, elige el **pronombre** correcto y distingue **acusativo** y **dativo**.

---

## Paso 1: ¿Qué es un verbo reflexivo?

El sujeto hace y recibe la acción.

| Sujeto | Reflexivo (Akk) | Ejemplo |
| --- | --- | --- |
| ich | **mich** | *Ich wasche **mich**.* |
| du | **dich** | *Du wäschst **dich**.* |
| er/sie/es | **sich** | *Er wäscht **sich**.* |
| wir | **uns** | *Wir waschen **uns**.* |
| ihr | **euch** | *Ihr wascht **euch**.* |
| sie/Sie | **sich** | *Sie waschen **sich**.* |

Tipos: acción sobre uno mismo (*sich rasieren*), recíproco (*sich küssen*), obligatorio (*sich freuen*).

---

## Paso 2: Orden y *Perfekt*

- Oración principal: reflexivo justo después del verbo conjugado.
- Oración subordinada: reflexivo después del sujeto; verbo conjugado al final.
- *Perfekt* siempre con **haben**.

*heiraten* **no** es reflexivo.

---

## Paso 3: Akkusativ vs. Dativ

Si hay **otro objeto acusativo**, el reflexivo pasa a **dativo** (*mich→mir*, *dich→dir*).

*Ich wasche **mich**.* / *Ich wasche **mir** die Hände.*

*sich vorstellen*: Akk = presentarse; Dat = imaginarse algo.
"""

FA_BODY = """# افعال و ضمایر انعکاسی (*Reflexive Verben*) (A2)

یاد بگیرید کی فعل **انعکاسی** است، **ضمیر** درست را انتخاب کنید و فرق **Akkusativ** و **Dativ** را بدانید.

---

## گام ۱: فعل انعکاسی چیست؟

فاعل هم انجام‌دهنده و هم گیرندۀ عمل است.

| فاعل | ضمیر انعکاسی (Akk) | مثال |
| --- | --- | --- |
| ich | **mich** | *Ich wasche **mich**.* |
| du | **dich** | *Du wäschst **dich**.* |
| er/sie/es | **sich** | *Er wäscht **sich**.* |
| wir | **uns** | *Wir waschen **uns**.* |
| ihr | **euch** | *Ihr wascht **euch**.* |
| sie/Sie | **sich** | *Sie waschen **sich**.* |

انواع: عمل روی خود (*sich rasieren*)، دوطرفه (*sich küssen*)، اجباری (*sich freuen*).

---

## گام ۲: ترتیب و *Perfekt*

- جملهٔ اصلی: ضمیر انعکاسی درست بعد از فعل صرف‌شده.
- جملهٔ پیرو: بعد از فاعل؛ فعل صرف‌شده در پایان.
- *Perfekt* همیشه با **haben**.

*heiraten* انعکاسی **نیست**.

---

## گام ۳: Akkusativ در برابر Dativ

اگر **مفعول اتهامی دیگری** باشد، ضمیر انعکاسی **داتیو** می‌شود (*mich→mir*، *dich→dir*).

*Ich wasche **mich**.* / *Ich wasche **mir** die Hände.*

*sich vorstellen*: Akk = معرفی خود؛ Dat = تصور کردن چیزی.
"""

AR_BODY = """# الأفعال والضمائر الانعكاسية (*Reflexive Verben*) (A2)

تعلّم متى يكون الفعل **انعكاسياً**، واختر **الضمير** الصحيح، وفرّق بين **Akkusativ** و **Dativ**.

---

## الخطوة 1: ما الفعل الانعكاسي؟

الفاعل يقوم بالفعل ويتلقاه أيضاً.

| الفاعل | الضمير (Akk) | مثال |
| --- | --- | --- |
| ich | **mich** | *Ich wasche **mich**.* |
| du | **dich** | *Du wäschst **dich**.* |
| er/sie/es | **sich** | *Er wäscht **sich**.* |
| wir | **uns** | *Wir waschen **uns**.* |
| ihr | **euch** | *Ihr wascht **euch**.* |
| sie/Sie | **sich** | *Sie waschen **sich**.* |

أنواع: على النفس (*sich rasieren*)، متبادل (*sich küssen*)، إلزامي (*sich freuen*).

---

## الخطوة 2: الترتيب و *Perfekt*

- الجملة الرئيسية: الضمير بعد الفعل المصرف مباشرة.
- الجملة التابعة: بعد الفاعل؛ الفعل المصرف في النهاية.
- *Perfekt* دائماً مع **haben**.

*heiraten* **ليس** انعكاسياً.

---

## الخطوة 3: Akkusativ مقابل Dativ

إذا وُجد **مفعول اتهامي آخر**، يصبح الضمير الانعكاسي في **الداتيف** (*mich→mir*، *dich→dir*).

*Ich wasche **mich**.* / *Ich wasche **mir** die Hände.*

*sich vorstellen*: Akk = تقديم النفس؛ Dat = تخيّل شيء.
"""

TR_BODY = """# Dönüşlü fiiller ve zamirler (*Reflexive Verben*) (A2)

Ne zaman fiil **dönüşlü**dür, doğru **zamiri** nasıl seçersiniz ve **Akkusativ / Dativ** farkı nedir?

---

## Adım 1: Dönüşlü fiil nedir?

Özne hem eylemi yapar hem de eylemi alır.

| Özne | Dönüşlü (Akk) | Örnek |
| --- | --- | --- |
| ich | **mich** | *Ich wasche **mich**.* |
| du | **dich** | *Du wäschst **dich**.* |
| er/sie/es | **sich** | *Er wäscht **sich**.* |
| wir | **uns** | *Wir waschen **uns**.* |
| ihr | **euch** | *Ihr wascht **euch**.* |
| sie/Sie | **sich** | *Sie waschen **sich**.* |

Türler: kendine (*sich rasieren*), karşılıklı (*sich küssen*), zorunlu (*sich freuen*).

---

## Adım 2: Sıra ve *Perfekt*

- Ana cümle: dönüşlü zamir çekimli fiilden hemen sonra.
- Yan cümle: özneden sonra; çekimli fiil sonda.
- *Perfekt* her zaman **haben** ile.

*heiraten* dönüşlü **değildir**.

---

## Adım 3: Akkusativ vs. Dativ

Başka bir **akkusatif nesne** varsa dönüşlü zamir **datif** olur (*mich→mir*, *dich→dir*).

*Ich wasche **mich**.* / *Ich wasche **mir** die Hände.*

*sich vorstellen*: Akk = kendini tanıtmak; Dat = bir şeyi hayal etmek.
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
        "praeteritum-sein-haben-modals",
        "perfekt-and-participles",
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
        "position": 11,
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
