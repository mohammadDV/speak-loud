#!/usr/bin/env python3
"""Add / refresh lesson 02 (modal verbs) in german_a1_grammar_course.json."""

from __future__ import annotations

import json
from pathlib import Path

DIR = Path(__file__).resolve().parent
COURSE_PATH = DIR / "german_a1_grammar_course.json"
EN_SOURCE = DIR / "german_grammar_lesson_02.md"

LESSON_CODE = "modal-verbs"

TITLES = {
    "en": "Modal Verbs",
    "es": "Verbos modales",
    "fa": "افعال کمکی وجهی",
    "ar": "الأفعال المساعدة",
    "tr": "Modal fiiller",
}

COURSE_DESCRIPTIONS = {
    "en": "Step-by-step A1 German grammar: pronouns, present tense, and modal verbs.",
    "es": "Gramática alemana A1 paso a paso: pronombres, presente y verbos modales.",
    "fa": "گرامر آلمانی A1 گام‌به‌گام: ضمایر، زمان حال و افعال کمکی وجهی.",
    "ar": "قواعد ألمانية A1 خطوة بخطوة: الضمائر والمضارع والأفعال المساعدة.",
    "tr": "Adım adım A1 Almanca dilbilgisi: zamirler, geniş zaman ve modal fiiller.",
}

ES_BODY = """# Verbos modales alemanes: guía paso a paso (A1/A2)

Los seis verbos modales: **können**, **müssen**, **dürfen**, **wollen**, **sollen** y **möchten**.

---

## Paso 1: Qué son y cómo se conjugan

Los modales cambian el significado de otro verbo (*puedo nadar*, *debo trabajar*).

### Reglas clave
1. **Cambio vocálico en singular:** en *können, müssen, dürfen, wollen* cambia la vocal en *ich / du / er*.
2. **Sin desinencia en *ich* y *er*:** las formas de 1.ª y 3.ª persona del singular son iguales.
3. **Plural regular:** *wir / ihr / sie/Sie* mantienen la vocal original.

### Tabla de conjugación

| Pronombre | können | müssen | dürfen | wollen | sollen | möchten |
| --- | --- | --- | --- | --- | --- | --- |
| **ich** | kann | muss | darf | will | soll | möchte |
| **du** | kannst | musst | darfst | willst | sollst | möchtest |
| **er / sie / es** | kann | muss | darf | will | soll | möchte |
| **wir** | können | müssen | dürfen | wollen | sollen | möchten |
| **ihr** | könnt | müsst | dürft | wollt | sollt | möchtet |
| **sie / Sie** | können | müssen | dürfen | wollen | sollen | möchten |

---

## Paso 2: Orden de la frase

- **Posición 2:** el modal conjugado.
- **Final:** el verbo principal en **infinitivo**.

Ejemplos:
- *Am Sonntag **können** wir lange **schlafen**.*
- *Ich **muss** nicht **arbeiten**.*
- *Meine Tochter **will** viel Eis **essen**.*

---

## Paso 3: *können, wollen, möchten*

### können
1. **Habilidad:** *Ich kann Englisch (sprechen).*
2. **Posibilidad:** *Hier kann man essen.*
3. **Permiso informal:** *Hier kann man parken.*

### wollen vs möchten
- **wollen:** deseo fuerte / intención (*Ich will einen Kaffee*) — puede sonar poco educado al pedir.
- **möchten:** deseo cortés (*Ich möchte einen Kaffee, bitte*).

---

## Paso 4: *müssen, dürfen, sollen*

| Forma | Significado |
| --- | --- |
| **müssen** | obligación / necesidad |
| **nicht müssen** | no es necesario (opcional) |
| **dürfen** | permiso |
| **nicht dürfen** | prohibición |
| **sollen** | consejo / lo que te han dicho / sugerencia |

Ejemplos con *sollen*:
- *Der Arzt sagt, ich soll die Tabletten nehmen.*
- *Sollen wir morgen ins Kino gehen?*
- *Soll ich dir einen Kaffee machen?*
"""

FA_BODY = """# افعال کمکی وجهی آلمانی: راهنمای گام‌به‌گام (A1/A2)

شش فعل: **können**، **müssen**، **dürfen**، **wollen**، **sollen** و **möchten**.

---

## گام ۱: تعریف و صرف

افعال وجهی معنی فعل دیگر را تغییر می‌دهند.

### قواعد مهم
1. در *können, müssen, dürfen, wollen* واکه در شکل مفرد عوض می‌شود.
2. شکل‌های **ich** و **er/sie/es** بدون پایان هستند و یکسان‌اند.
3. جمع واکه اصلی را نگه می‌دارد.

### جدول صرف

| ضمیر | können | müssen | dürfen | wollen | sollen | möchten |
| --- | --- | --- | --- | --- | --- | --- |
| **ich** | kann | muss | darf | will | soll | möchte |
| **du** | kannst | musst | darfst | willst | sollst | möchtest |
| **er / sie / es** | kann | muss | darf | will | soll | möchte |
| **wir** | können | müssen | dürfen | wollen | sollen | möchten |
| **ihr** | könnt | müsst | dürft | wollt | sollt | möchtet |
| **sie / Sie** | können | müssen | dürfen | wollen | sollen | möchten |

---

## گام ۲: ترتیب جمله

- **جایگاه ۲:** فعل وجهی صرف‌شده
- **پایان جمله:** مصدر فعل اصلی

مثال‌ها:
- *Am Sonntag **können** wir lange **schlafen**.*
- *Ich **muss** nicht **arbeiten**.*
- *Meine Tochter **will** viel Eis **essen**.*

---

## گام ۳: *können, wollen, möchten*

### können
1. **مهارت:** *Ich kann Englisch.*
2. **امکان:** *Hier kann man essen.*
3. **اجازه غیررسمی:** *Hier kann man parken.*

### wollen در برابر möchten
- **wollen:** خواست قوی (*Ich will einen Kaffee*) — هنگام سفارش اغلب بی‌ادبانه است.
- **möchten:** خواست مؤدبانه (*Ich möchte einen Kaffee, bitte*).

---

## گام ۴: *müssen, dürfen, sollen*

| شکل | معنی |
| --- | --- |
| **müssen** | اجبار / ضرورت |
| **nicht müssen** | لازم نیست (اختیاری) |
| **dürfen** | اجازه |
| **nicht dürfen** | ممنوع |
| **sollen** | توصیه / گفتهٔ دیگران / پیشنهاد |

مثال‌ها:
- *Der Arzt sagt, ich soll die Tabletten nehmen.*
- *Sollen wir morgen ins Kino gehen?*
- *Soll ich dir einen Kaffee machen?*
"""

AR_BODY = """# الأفعال المساعدة الألمانية: دليل خطوة بخطوة (A1/A2)

الأفعال الستة: **können** و **müssen** و **dürfen** و **wollen** و **sollen** و **möchten**.

---

## الخطوة 1: التعريف والتصريف

تغير الأفعال المساعدة معنى فعل آخر.

### قواعد أساسية
1. في *können, müssen, dürfen, wollen* يتغير حرف العلة في المفرد.
2. صيغتا **ich** و **er/sie/es** بلا نهاية وهما متطابقتان.
3. الجمع يحافظ على حرف العلة الأصلي.

### جدول التصريف

| الضمير | können | müssen | dürfen | wollen | sollen | möchten |
| --- | --- | --- | --- | --- | --- | --- |
| **ich** | kann | muss | darf | will | soll | möchte |
| **du** | kannst | musst | darfst | willst | sollst | möchtest |
| **er / sie / es** | kann | muss | darf | will | soll | möchte |
| **wir** | können | müssen | dürfen | wollen | sollen | möchten |
| **ihr** | könnt | müsst | dürft | wollt | sollt | möchtet |
| **sie / Sie** | können | müssen | dürfen | wollen | sollen | möchten |

---

## الخطوة 2: ترتيب الجملة

- **الموضع 2:** الفعل المساعد المصرّف
- **نهاية الجملة:** المصدر للفعل الرئيسي

أمثلة:
- *Am Sonntag **können** wir lange **schlafen**.*
- *Ich **muss** nicht **arbeiten**.*
- *Meine Tochter **will** viel Eis **essen**.*

---

## الخطوة 3: *können, wollen, möchten*

### können
1. **مهارة:** *Ich kann Englisch.*
2. **إمكانية:** *Hier kann man essen.*
3. **إذن غير رسمي:** *Hier kann man parken.*

### wollen مقابل möchten
- **wollen:** رغبة قوية (*Ich will einen Kaffee*) — قد تبدو غير مهذبة عند الطلب.
- **möchten:** رغبة مهذبة (*Ich möchte einen Kaffee, bitte*).

---

## الخطوة 4: *müssen, dürfen, sollen*

| الصيغة | المعنى |
| --- | --- |
| **müssen** | وجوب / ضرورة |
| **nicht müssen** | ليس ضروريًا (اختياري) |
| **dürfen** | إذن |
| **nicht dürfen** | ممنوع |
| **sollen** | نصيحة / ما قيل لك / اقتراح |

أمثلة:
- *Der Arzt sagt, ich soll die Tabletten nehmen.*
- *Sollen wir morgen ins Kino gehen?*
- *Soll ich dir einen Kaffee machen?*
"""

TR_BODY = """# Almanca modal fiiller: adım adım rehber (A1/A2)

Altı modal: **können**, **müssen**, **dürfen**, **wollen**, **sollen** ve **möchten**.

---

## Adım 1: Tanım ve çekim

Modal fiiller başka bir fiilin anlamını değiştirir.

### Temel kurallar
1. *können, müssen, dürfen, wollen* tekilde ünlü değiştirir.
2. **ich** ve **er/sie/es** ek almaz; bu iki biçim aynıdır.
3. Çoğul özgün ünlüyü korur.

### Çekim tablosu

| Zamir | können | müssen | dürfen | wollen | sollen | möchten |
| --- | --- | --- | --- | --- | --- | --- |
| **ich** | kann | muss | darf | will | soll | möchte |
| **du** | kannst | musst | darfst | willst | sollst | möchtest |
| **er / sie / es** | kann | muss | darf | will | soll | möchte |
| **wir** | können | müssen | dürfen | wollen | sollen | möchten |
| **ihr** | könnt | müsst | dürft | wollt | sollt | möchtet |
| **sie / Sie** | können | müssen | dürfen | wollen | sollen | möchten |

---

## Adım 2: Sözdizimi

- **2. konum:** çekimli modal
- **Cümle sonu:** asıl fiilin **mastarı**

Örnekler:
- *Am Sonntag **können** wir lange **schlafen**.*
- *Ich **muss** nicht **arbeiten**.*
- *Meine Tochter **will** viel Eis **essen**.*

---

## Adım 3: *können, wollen, möchten*

### können
1. **Beceri:** *Ich kann Englisch.*
2. **Olanak:** *Hier kann man essen.*
3. **Gayri resmi izin:** *Hier kann man parken.*

### wollen ve möchten
- **wollen:** güçlü istek (*Ich will einen Kaffee*) — siparişte kaba gelebilir.
- **möchten:** kibar istek (*Ich möchte einen Kaffee, bitte*).

---

## Adım 4: *müssen, dürfen, sollen*

| Biçim | Anlam |
| --- | --- |
| **müssen** | zorunluluk |
| **nicht müssen** | gerek yok (isteğe bağlı) |
| **dürfen** | izin |
| **nicht dürfen** | yasak |
| **sollen** | tavsiye / söylenen / öneri |

Örnekler:
- *Der Arzt sagt, ich soll die Tabletten nehmen.*
- *Sollen wir morgen ins Kino gehen?*
- *Soll ich dir einen Kaffee machen?*
"""


def main() -> None:
    course = json.loads(COURSE_PATH.read_text(encoding="utf-8"))
    en_body = EN_SOURCE.read_text(encoding="utf-8").strip() + "\n"

    course["meta"] = {
        "course_code": "de-grammar-a1",
        "lesson_codes": ["pronouns-and-present-tense", LESSON_CODE],
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
        "position": 2,
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
