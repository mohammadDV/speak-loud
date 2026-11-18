#!/usr/bin/env python3
"""Add / refresh lesson 03 (questions & word order) in german_a1_grammar_course.json."""

from __future__ import annotations

import json
from pathlib import Path

DIR = Path(__file__).resolve().parent
COURSE_PATH = DIR / "german_a1_grammar_course.json"
EN_SOURCE = DIR / "german_grammar_lesson_03.md"

LESSON_CODE = "questions-and-word-order"

TITLES = {
    "en": "Questions & Word Order",
    "es": "Preguntas y orden de la frase",
    "fa": "سؤال‌ها و ترتیب جمله",
    "ar": "الأسئلة وترتيب الجملة",
    "tr": "Sorular ve sözdizimi",
}

COURSE_DESCRIPTIONS = {
    "en": "Step-by-step A1 German grammar: pronouns, present tense, modal verbs, questions, and word order.",
    "es": "Gramática alemana A1 paso a paso: pronombres, presente, modales, preguntas y orden de la frase.",
    "fa": "گرامر آلمانی A1 گام‌به‌گام: ضمایر، زمان حال، افعال کمکی، سؤال‌ها و ترتیب جمله.",
    "ar": "قواعد ألمانية A1 خطوة بخطوة: الضمائر والمضارع والأفعال المساعدة والأسئلة وترتيب الجملة.",
    "tr": "Adım adım A1 Almanca dilbilgisi: zamirler, geniş zaman, modal fiiller, sorular ve sözdizimi.",
}

ES_BODY = """# Preguntas y estructura de la frase (A1/A2)

Aprende **preguntas con W**, **preguntas de sí/no**, **ja / nein / doch**, **peticiones corteses** y la **posición del verbo**.

---

## Paso 1: W-Fragen

Las preguntas abiertas empiezan con una palabra interrogativa en **W**.

### Orden
1. Palabra W  
2. Verbo conjugado  
3. Sujeto  

| Pos. 1 | Pos. 2 | Pos. 3 | Resto | Significado |
| --- | --- | --- | --- | --- |
| **Wie** | heißen | Sie? | | ¿Cómo se llama? |
| **Wer** | ist | das? | | ¿Quién es? |
| **Woher** | kommt | er? | | ¿De dónde viene? |
| **Wohin** | will | er | gehen? | ¿Adónde quiere ir? |

### Palabras W esenciales
* **Wer?** — ¿Quién?
* **Wen?** — ¿A quién?
* **Was?** — ¿Qué?
* **Wie?** — ¿Cómo? / ¿Cuál? (*Wie heißen Sie?*)
* **Wie viel / Wie viele?** — ¿Cuánto / cuántos?
* **Wann?** — ¿Cuándo?
* **Warum?** — ¿Por qué?

### *Wo? / Wohin? / Woher?*
* **Wo?** — lugar estático (*Wo wohnen Sie?*)
* **Wohin?** — dirección (*Wohin fahren Sie?*)
* **Woher?** — origen (*Woher kommen Sie?*)

---

## Paso 2: Preguntas de sí/no y *doch*

* Afirmación: *Er **kommt** aus Berlin.*
* Pregunta: ***Kommt** er aus Berlin?*

* Pregunta positiva → **ja** / **nein**
* Pregunta negativa (*nicht/kein*) → **doch** contradice; **nein** confirma la negación

### Peticiones corteses
* *Könnten Sie mir bitte ein Glas Wasser **geben**?*
* *Würden Sie mir bitte ein Glas Wasser **geben**?*

---

## Paso 3: Posición del verbo e inversión

* Enunciados y W-Fragen: verbo en **posición 2**
* Elemento en pos. 1 → verbo sigue en 2, sujeto en 3 (*Am Abend **essen** wir Pizza.*)
* Preguntas sí/no e imperativo: verbo en **posición 1**

### Tiempo y lugar
El **tiempo** va más a la izquierda; el **lugar**, más a la derecha.
* *Ich wohne **seit zwei Monaten** **in Deutschland**.*
"""

FA_BODY = """# سؤال‌ها و ساختار جمله (A1/A2)

یاد بگیرید **سؤال‌های W**، **سؤال بله/خیر**، **ja / nein / doch**، **درخواست مؤدبانه** و **جای فعل**.

---

## گام ۱: W-Fragen

سؤال‌های باز با کلمهٔ پرسشی **W** شروع می‌شوند.

### ترتیب
1. کلمهٔ W  
2. فعل صرف‌شده  
3. فاعل  

| جایگاه ۱ | جایگاه ۲ | جایگاه ۳ | ادامه | معنی |
| --- | --- | --- | --- | --- |
| **Wie** | heißen | Sie? | | اسم شما چیست؟ |
| **Wer** | ist | das? | | این کیست؟ |
| **Woher** | kommt | er? | | او اهل کجاست؟ |
| **Wohin** | will | er | gehen? | می‌خواهد کجا برود؟ |

### کلمات مهم
* **Wer?** — چه کسی؟
* **Wen?** — چه کسی را؟
* **Was?** — چه؟
* **Wie?** — چگونه؟ / چه؟ (*Wie heißen Sie?*)
* **Wie viel / Wie viele?** — چقدر / چند؟
* **Wann?** — کی؟
* **Warum?** — چرا؟

### *Wo? / Wohin? / Woher?*
* **Wo?** — مکان ثابت
* **Wohin?** — جهت حرکت
* **Woher?** — مبدأ / اهل کجا

---

## گام ۲: سؤال بله/خیر و *doch*

* خبری: *Er **kommt** aus Berlin.*
* سؤالی: ***Kommt** er aus Berlin?*

* سؤال مثبت → **ja** / **nein**
* سؤال منفی → **doch** برای رد نفی؛ **nein** برای تأیید نفی

### درخواست مؤدبانه
* *Könnten Sie mir bitte ein Glas Wasser **geben**?*
* *Würden Sie mir bitte ein Glas Wasser **geben**?*

---

## گام ۳: جایگاه فعل و جابه‌جایی

* جملهٔ خبری و W-Frage: فعل در **جایگاه ۲**
* عنصر در جایگاه ۱ → فعل در ۲ می‌ماند، فاعل به ۳ می‌رود
* سؤال بله/خیر و امری: فعل در **جایگاه ۱**

### زمان و مکان
**زمان** سمت چپ؛ **مکان** سمت راست.
* *Ich wohne **seit zwei Monaten** **in Deutschland**.*
"""

AR_BODY = """# الأسئلة وترتيب الجملة (A1/A2)

تعلّم **أسئلة W** و**أسئلة نعم/لا** و**ja / nein / doch** و**الطلبات المهذبة** و**موضع الفعل**.

---

## الخطوة 1: W-Fragen

تبدأ الأسئلة المفتوحة بكلمة استفهام بحرف **W**.

### الترتيب
1. كلمة W  
2. الفعل المصرّف  
3. الفاعل  

| الموضع 1 | الموضع 2 | الموضع 3 | الباقي | المعنى |
| --- | --- | --- | --- | --- |
| **Wie** | heißen | Sie? | | ما اسمك؟ |
| **Wer** | ist | das? | | من هذا؟ |
| **Woher** | kommt | er? | | من أين يأتي؟ |
| **Wohin** | will | er | gehen? | إلى أين يريد أن يذهب؟ |

### كلمات أساسية
* **Wer?** — من؟
* **Wen?** — من (مفعول)؟
* **Was?** — ماذا؟
* **Wie?** — كيف؟ / ما؟ (*Wie heißen Sie?*)
* **Wie viel / Wie viele?** — كم؟
* **Wann?** — متى؟
* **Warum?** — لماذا؟

### *Wo? / Wohin? / Woher?*
* **Wo?** — مكان ثابت
* **Wohin?** — اتجاه
* **Woher?** — مصدر / من أين

---

## الخطوة 2: أسئلة نعم/لا و*doch*

* جملة: *Er **kommt** aus Berlin.*
* سؤال: ***Kommt** er aus Berlin?*

* سؤال إيجابي → **ja** / **nein**
* سؤال منفي → **doch** للنفي المضاد؛ **nein** لتأكيد النفي

### طلب مهذب
* *Könnten Sie mir bitte ein Glas Wasser **geben**?*
* *Würden Sie mir bitte ein Glas Wasser **geben**?*

---

## الخطوة 3: موضع الفعل والقلب

* الجمل وأسئلة W: الفعل في **الموضع 2**
* عنصر في الموضع 1 → الفعل يبقى 2 والفاعل إلى 3
* أسئلة نعم/لا والأمر: الفعل في **الموضع 1**

### الزمان والمكان
**الزمان** إلى اليسار؛ **المكان** إلى اليمين.
* *Ich wohne **seit zwei Monaten** **in Deutschland**.*
"""

TR_BODY = """# Sorular ve cümle yapısı (A1/A2)

**W-soruları**, **evet/hayır soruları**, **ja / nein / doch**, **kibar riceler** ve **fiil konumu**nu öğrenin.

---

## Adım 1: W-Fragen

Açık uçlu sorular **W** ile başlayan soru sözcüğüyle kurulur.

### Sıra
1. W-sözcüğü  
2. Çekimli fiil  
3. Özne  

| Konum 1 | Konum 2 | Konum 3 | Devamı | Anlam |
| --- | --- | --- | --- | --- |
| **Wie** | heißen | Sie? | | Adınız ne? |
| **Wer** | ist | das? | | Bu kim? |
| **Woher** | kommt | er? | | Nereden geliyor? |
| **Wohin** | will | er | gehen? | Nereye gitmek istiyor? |

### Temel W-sözcükleri
* **Wer?** — Kim?
* **Wen?** — Kimi?
* **Was?** — Ne?
* **Wie?** — Nasıl? / Ne? (*Wie heißen Sie?*)
* **Wie viel / Wie viele?** — Ne kadar / kaç?
* **Wann?** — Ne zaman?
* **Warum?** — Neden?

### *Wo? / Wohin? / Woher?*
* **Wo?** — sabit yer
* **Wohin?** — yön
* **Woher?** — kaynak / nereden

---

## Adım 2: Evet/hayır soruları ve *doch*

* Cümle: *Er **kommt** aus Berlin.*
* Soru: ***Kommt** er aus Berlin?*

* Olumlu soru → **ja** / **nein**
* Olumsuz soru → **doch** çürütür; **nein** olumsuzu onaylar

### Kibar rica
* *Könnten Sie mir bitte ein Glas Wasser **geben**?*
* *Würden Sie mir bitte ein Glas Wasser **geben**?*

---

## Adım 3: Fiil konumu ve tersine çevirme

* Bildirme ve W-soruları: fiil **2. konumda**
* 1. konumda başka öğe → fiil 2’de kalır, özne 3’e geçer
* Evet/hayır ve emir: fiil **1. konumda**

### Zaman ve yer
**Zaman** solda; **yer** sağda.
* *Ich wohne **seit zwei Monaten** **in Deutschland**.*
"""


def main() -> None:
    course = json.loads(COURSE_PATH.read_text(encoding="utf-8"))
    en_body = EN_SOURCE.read_text(encoding="utf-8").strip() + "\n"

    existing_codes = [
        item.get("code")
        for item in (course.get("lessons") or [])
        if isinstance(item, dict)
    ]
    lesson_codes = []
    for code in (
        "pronouns-and-present-tense",
        "modal-verbs",
        LESSON_CODE,
        *[code for code in existing_codes if code not in {
            "pronouns-and-present-tense",
            "modal-verbs",
            LESSON_CODE,
        }],
    ):
        if code and code not in lesson_codes:
            lesson_codes.append(code)

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
        "position": 3,
        "translations": [
            {
                "language_code": lang,
                "title": TITLES[lang],
                "body_markdown": bodies[lang],
            }
            for lang in ("en", "es", "fa", "ar", "tr")
        ],
    }

    lessons = course.get("lessons") or []
    remaining = [item for item in lessons if item.get("code") != LESSON_CODE]
    remaining.append(lesson)
    remaining.sort(key=lambda item: int(item.get("position") or 0))
    course["lessons"] = remaining

    COURSE_PATH.write_text(
        json.dumps(course, ensure_ascii=False, indent=2) + "\n",
        encoding="utf-8",
    )
    print(
        f"updated {COURSE_PATH} lessons={[item['code'] for item in course['lessons']]}"
    )


if __name__ == "__main__":
    main()
