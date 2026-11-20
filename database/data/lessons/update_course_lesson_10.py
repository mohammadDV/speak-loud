#!/usr/bin/env python3
"""Add / refresh lesson 10 (Perfekt & participles) in german_a1_grammar_course.json."""

from __future__ import annotations

import json
from pathlib import Path

DIR = Path(__file__).resolve().parent
COURSE_PATH = DIR / "german_a1_grammar_course.json"
EN_SOURCE = DIR / "german_grammar_lesson_10.md"

LESSON_CODE = "perfekt-and-participles"

TITLES = {
    "en": "Talking About the Past: Perfekt",
    "es": "Hablar del pasado: Perfekt",
    "fa": "صحبت دربارهٔ گذشته: Perfekt",
    "ar": "الحديث عن الماضي: Perfekt",
    "tr": "Geçmişten bahsetmek: Perfekt",
}

COURSE_DESCRIPTIONS = {
    "en": "Step-by-step German grammar A1–A2: pronouns, modals, questions, articles, cases, prepositions, possessives, dative verbs, simple past, and Perfekt.",
    "es": "Gramática alemana A1–A2 paso a paso: pronombres, modales, preguntas, artículos, casos, preposiciones, posesivos, verbos dativos, pretérito y Perfekt.",
    "fa": "گرامر آلمانی A1–A2 گام‌به‌گام: ضمایر، افعال کمکی، سؤال‌ها، حروف تعریف، حالت‌ها، حروف اضافه، ملکی‌ها، افعال داتیو، گذشته ساده و Perfekt.",
    "ar": "قواعد ألمانية A1–A2 خطوة بخطوة: الضمائر والأفعال المساعدة والأسئلة وأدوات التعريف والحالات وحروف الجر وضمائر الملكية وأفعال الداتيف والماضي البسيط وPerfekt.",
    "tr": "Adım adım Almanca dilbilgisi A1–A2: zamirler, modaller, sorular, artikeller, haller, edatlar, iyelikler, datif fiiller, geçmiş zaman ve Perfekt.",
}

ES_BODY = """# Hablar del pasado (*Perfekt* y *Präteritum*) (A2)

Aprende a formar el **Perfekt**, elegir **haben** o **sein**, construir el **Partizip Perfekt** y saber cuándo preferir el **Präteritum**.

---

## Paso 1: Introducción al *Perfekt*

En alemán hablado, el **Perfekt** es la forma más habitual del pasado.

Necesitas dos partes:

1. **Verbo auxiliar (posición 2):** **haben** o **sein** conjugado
2. **Partizip Perfekt (al final):** el participio del verbo principal

*Ich **habe** gearbeitet.* / *Ich **bin** gegangen.*

---

## Paso 2: *haben* vs. *sein*

La mayoría usa **haben**. Usa **sein** con:

1. **Cambio de lugar** (*gehen, fahren, kommen, fliegen…*)
2. **Cambio de estado** (*aufwachen, einschlafen, werden, sterben…*)
3. **Excepciones:** *sein* → **gewesen**, *bleiben* → **geblieben**

---

## Paso 3: Formar el *Partizip*

| Tipo | Regla | Ejemplo |
| --- | --- | --- |
| Regular | ge- + raíz + **-t** | *kaufen* → **gekauft** |
| Irregular | ge- + raíz + **-en** | *trinken* → **getrunken** |
| Separable | prefijo + **ge** + verbo | *einkaufen* → **eingekauft** |
| Inseparable | **sin ge-** | *verstehen* → **verstanden** |
| *-ieren* | **sin ge-**, acaba en **-t** | *studieren* → **studiert** |
| Mixtos | cambio de vocal + **-t** | *denken* → **gedacht** |

---

## Paso 4: *Perfekt* vs. *Präteritum*

Con **sein**, **haben** y **modales**, el alemán suele preferir el **Präteritum** (*war, hatte, musste, konnte…*).

Para casi todos los demás verbos en la lengua hablada: **Perfekt**.
"""

FA_BODY = """# صحبت دربارهٔ گذشته (*Perfekt* و *Präteritum*) (A2)

یاد بگیرید چگونه **Perfekt** بسازید، **haben** یا **sein** را انتخاب کنید، **Partizip Perfekt** را بسازید و کی **Präteritum** ترجیح داده می‌شود.

---

## گام ۱: معرفی *Perfekt*

در آلمانی گفتاری، **Perfekt** رایج‌ترین شکل گذشته است.

دو بخش لازم است:

1. **فعل کمکی (جایگاه ۲):** صرف‌شدهٔ **haben** یا **sein**
2. **Partizip Perfekt (پایان جمله):** اسم مفعول فعل اصلی

*Ich **habe** gearbeitet.* / *Ich **bin** gegangen.*

---

## گام ۲: *haben* در برابر *sein*

بیشتر افعال با **haben**. با **sein** وقتی:

1. **تغییر مکان** (*gehen, fahren, kommen…*)
2. **تغییر حالت** (*aufwachen, einschlafen, werden…*)
3. **استثنا:** *sein* → **gewesen**، *bleiben* → **geblieben**

---

## گام ۳: ساختن *Partizip*

| نوع | قاعده | مثال |
| --- | --- | --- |
| منظم | ge- + بن + **-t** | *kaufen* → **gekauft** |
| نامنظم | ge- + بن + **-en** | *trinken* → **getrunken** |
| جداشدنی | پیشوند + **ge** + فعل | *einkaufen* → **eingekauft** |
| جدانشدنی | **بدون ge-** | *verstehen* → **verstanden** |
| *-ieren* | **بدون ge-**، پایان **-t** | *studieren* → **studiert** |
| مختلط | تغییر واکه + **-t** | *denken* → **gedacht** |

---

## گام ۴: *Perfekt* در برابر *Präteritum*

با **sein**، **haben** و **افعال کمکی وجهی** معمولاً **Präteritum** (*war, hatte, musste…*).

برای تقریباً همهٔ افعال دیگر در گفتار: **Perfekt**.
"""

AR_BODY = """# الحديث عن الماضي (*Perfekt* و *Präteritum*) (A2)

تعلّم تكوين **Perfekt**، واختيار **haben** أو **sein**، وبناء **Partizip Perfekt**، ومتى يُفضَّل **Präteritum**.

---

## الخطوة 1: مقدمة إلى *Perfekt*

في الألمانية المحكية، **Perfekt** هو أشهر شكل للماضي.

تحتاج إلى جزأين:

1. **فعل مساعد (الموضع 2):** تصريف **haben** أو **sein**
2. **Partizip Perfekt (نهاية الجملة):** اسم المفعول للفعل الرئيسي

*Ich **habe** gearbeitet.* / *Ich **bin** gegangen.*

---

## الخطوة 2: *haben* مقابل *sein*

معظم الأفعال مع **haben**. استخدم **sein** مع:

1. **تغيّر المكان** (*gehen, fahren, kommen…*)
2. **تغيّر الحالة** (*aufwachen, einschlafen, werden…*)
3. **استثناءان:** *sein* → **gewesen**، *bleiben* → **geblieben**

---

## الخطوة 3: تكوين *Partizip*

| النوع | القاعدة | مثال |
| --- | --- | --- |
| منتظم | ge- + الجذر + **-t** | *kaufen* → **gekauft** |
| غير منتظم | ge- + الجذر + **-en** | *trinken* → **getrunken** |
| منفصل | السابقة + **ge** + الفعل | *einkaufen* → **eingekauft** |
| غير منفصل | **بدون ge-** | *verstehen* → **verstanden** |
| *-ieren* | **بدون ge-** وينتهي بـ **-t** | *studieren* → **studiert** |
| مختلط | تغيّر حرف علة + **-t** | *denken* → **gedacht** |

---

## الخطوة 4: *Perfekt* مقابل *Präteritum*

مع **sein** و **haben** و**الأفعال المساعدة** يُفضَّل **Präteritum** (*war, hatte, musste…*).

لمعظم الأفعال الأخرى في الكلام: **Perfekt**.
"""

TR_BODY = """# Geçmişten bahsetmek (*Perfekt* ve *Präteritum*) (A2)

**Perfekt** nasıl yapılır, **haben** / **sein** nasıl seçilir, **Partizip Perfekt** nasıl kurulur ve ne zaman **Präteritum** tercih edilir?

---

## Adım 1: *Perfekt*e giriş

Konuşma dilinde geçmiş için en yaygın biçim **Perfekt**tir.

İki parça gerekir:

1. **Yardımcı fiil (2. konum):** çekimli **haben** veya **sein**
2. **Partizip Perfekt (cümle sonu):** ana fiilin ortacı

*Ich **habe** gearbeitet.* / *Ich **bin** gegangen.*

---

## Adım 2: *haben* vs. *sein*

Çoğu fiil **haben** alır. **sein** şunlarla:

1. **Yer değişimi** (*gehen, fahren, kommen…*)
2. **Durum değişimi** (*aufwachen, einschlafen, werden…*)
3. **İstisnalar:** *sein* → **gewesen**, *bleiben* → **geblieben**

---

## Adım 3: *Partizip* kurmak

| Tür | Kural | Örnek |
| --- | --- | --- |
| Düzenli | ge- + gövde + **-t** | *kaufen* → **gekauft** |
| Düzensiz | ge- + gövde + **-en** | *trinken* → **getrunken** |
| Ayrılabilen | önek + **ge** + fiil | *einkaufen* → **eingekauft** |
| Ayrılamayan | **ge- yok** | *verstehen* → **verstanden** |
| *-ieren* | **ge- yok**, **-t** ile biter | *studieren* → **studiert** |
| Karma | ünlü değişimi + **-t** | *denken* → **gedacht** |

---

## Adım 4: *Perfekt* vs. *Präteritum*

**sein**, **haben** ve **modaller** için Almanlar genelde **Präteritum** kullanır (*war, hatte, musste…*).

Neredeyse diğer tüm fiillerde konuşmada: **Perfekt**.
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
        "position": 10,
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
