#!/usr/bin/env python3
"""Add / refresh lesson 08 (dative verbs & wer/wen/wem) in german_a1_grammar_course.json."""

from __future__ import annotations

import json
from pathlib import Path

DIR = Path(__file__).resolve().parent
COURSE_PATH = DIR / "german_a1_grammar_course.json"
EN_SOURCE = DIR / "german_grammar_lesson_08.md"

LESSON_CODE = "dative-verbs-wer-wen-wem"

TITLES = {
    "en": "Dative Verbs & wer/wen/wem",
    "es": "Verbos dativos y wer/wen/wem",
    "fa": "افعال داتیو و wer/wen/wem",
    "ar": "أفعال الداتيف و wer/wen/wem",
    "tr": "Datif fiiller ve wer/wen/wem",
}

COURSE_DESCRIPTIONS = {
    "en": "Step-by-step German grammar A1–A2: pronouns, modals, questions, articles, cases, prepositions, possessives, and dative verbs.",
    "es": "Gramática alemana A1–A2 paso a paso: pronombres, modales, preguntas, artículos, casos, preposiciones, posesivos y verbos dativos.",
    "fa": "گرامر آلمانی A1–A2 گام‌به‌گام: ضمایر، افعال کمکی، سؤال‌ها، حروف تعریف، حالت‌ها، حروف اضافه، ملکی‌ها و افعال داتیو.",
    "ar": "قواعد ألمانية A1–A2 خطوة بخطوة: الضمائر والأفعال المساعدة والأسئلة وأدوات التعريف والحالات وحروف الجر وضمائر الملكية وأفعال الداتيف.",
    "tr": "Adım adım Almanca dilbilgisi A1–A2: zamirler, modaller, sorular, artikeller, haller, edatlar, iyelikler ve datif fiiller.",
}

ES_BODY = """# Verbos dativos y wer/wen/wem (A2)

Aprende verbos con objeto **dativo**, expresiones fijas y las palabras interrogativas **wer / was / wen / wem**.

---

## Paso 1: Verbos con dativo

### Grupo A: persona → persona (Dativ)

*antworten, gratulieren, helfen, zuhören, glauben, danken*

*Das Kind hilft **dem Mann**.* · *Ich glaube **dir**.*

*helfen* → er/sie/es **hilft**.

### Grupo B: cosa (Nominativ) → persona (Dativ)

*schmecken, gefallen, gehören, passen, stehen, wehtun*

*Das Auto gehört **dem Chef**.* · *Der Kopf tut **der Frau** weh.*

### Grupo C: expresiones fijas

*Es tut **mir** leid.* · *Wie geht es **Ihnen**?*

---

## Paso 2: wer / was / wen / wem

| Caso | Personas | Cosas |
| --- | --- | --- |
| Nominativ | **Wer?** | **Was?** |
| Akkusativ | **Wen?** | **Was?** |
| Dativ | **Wem?** | — |

*mit/bei/von/zu* + **wem** · *für/ohne* + **wen**
"""

FA_BODY = """# افعال داتیو و wer/wen/wem (A2)

یاد بگیرید افعالی که مفعول **داتیو** می‌گیرند، عبارت‌های ثابت، و واژه‌های پرسشی **wer / was / wen / wem**.

---

## گام ۱: افعال با داتیو

### گروه A: شخص → شخص (داتیو)

*antworten, gratulieren, helfen, zuhören, glauben, danken*

*Das Kind hilft **dem Mann**.* · *Ich glaube **dir**.*

*helfen* → er/sie/es **hilft**.

### گروه B: چیز (Nominativ) → شخص (داتیو)

*schmecken, gefallen, gehören, passen, stehen, wehtun*

*Das Auto gehört **dem Chef**.* · *Der Kopf tut **der Frau** weh.*

### گروه C: عبارت‌های ثابت

*Es tut **mir** leid.* · *Wie geht es **Ihnen**?*

---

## گام ۲: wer / was / wen / wem

| حالت | افراد | چیزها |
| --- | --- | --- |
| Nominativ | **Wer?** | **Was?** |
| Akkusativ | **Wen?** | **Was?** |
| Dativ | **Wem?** | — |

*mit/bei/von/zu* + **wem** · *für/ohne* + **wen**
"""

AR_BODY = """# أفعال الداتيف و wer/wen/wem (A2)

تعلّم الأفعال التي تأخذ مفعولاً في **الداتيف**، والتعابير الثابتة، وأسئلة **wer / was / wen / wem**.

---

## الخطوة 1: أفعال مع الداتيف

### المجموعة A: شخص → شخص (داتيف)

*antworten, gratulieren, helfen, zuhören, glauben, danken*

*Das Kind hilft **dem Mann**.* · *Ich glaube **dir**.*

*helfen* → er/sie/es **hilft**.

### المجموعة B: شيء (Nominativ) → شخص (داتيف)

*schmecken, gefallen, gehören, passen, stehen, wehtun*

*Das Auto gehört **dem Chef**.* · *Der Kopf tut **der Frau** weh.*

### المجموعة C: تعابير ثابتة

*Es tut **mir** leid.* · *Wie geht es **Ihnen**?*

---

## الخطوة 2: wer / was / wen / wem

| الحالة | الأشخاص | الأشياء |
| --- | --- | --- |
| Nominativ | **Wer?** | **Was?** |
| Akkusativ | **Wen?** | **Was?** |
| Dativ | **Wem?** | — |

*mit/bei/von/zu* + **wem** · *für/ohne* + **wen**
"""

TR_BODY = """# Datif fiiller ve wer/wen/wem (A2)

**Datif** nesne alan fiilleri, kalıp ifadeleri ve **wer / was / wen / wem** soru sözcüklerini öğrenin.

---

## Adım 1: Datif alan fiiller

### Grup A: kişi → kişi (Datif)

*antworten, gratulieren, helfen, zuhören, glauben, danken*

*Das Kind hilft **dem Mann**.* · *Ich glaube **dir**.*

*helfen* → er/sie/es **hilft**.

### Grup B: şey (Nominativ) → kişi (Datif)

*schmecken, gefallen, gehören, passen, stehen, wehtun*

*Das Auto gehört **dem Chef**.* · *Der Kopf tut **der Frau** weh.*

### Grup C: kalıp ifadeler

*Es tut **mir** leid.* · *Wie geht es **Ihnen**?*

---

## Adım 2: wer / was / wen / wem

| Hâl | Kişiler | Şeyler |
| --- | --- | --- |
| Nominativ | **Wer?** | **Was?** |
| Akkusativ | **Wen?** | **Was?** |
| Dativ | **Wem?** | — |

*mit/bei/von/zu* + **wem** · *für/ohne* + **wen**
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
        "position": 8,
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
