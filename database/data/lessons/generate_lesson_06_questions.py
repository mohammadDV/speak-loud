#!/usr/bin/env python3
"""Regenerate german_grammar_lesson_06_questions.json for prepositions (time & place)."""

from __future__ import annotations

import json
from collections import Counter
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
VOCAB_PATH = ROOT / "vocabulary" / "a1_german_words.json"
OUT_PATH = Path(__file__).resolve().parent / "german_grammar_lesson_06_questions.json"

COURSE = "de-grammar-a1"
LESSON = "prepositions-time-and-place"
KEY_PREFIX = f"{COURSE}:l06"

PROMPT = {
    "en": {
        "temp_month": 'Which temporal preposition fits: ___ {lemma}?',
        "temp_day": 'Which temporal preposition fits: ___ {lemma}?',
        "temp_season": 'Which temporal preposition fits: ___ {lemma}?',
        "temp_clock": "Which temporal preposition fits clock times?",
        "temp_night": "Which form is correct for night?",
        "temp_year": "Which option is correct for a calendar year (e.g. 1997)?",
        "temp_frame": "Choose the correct temporal preposition: {frame}",
        "mit_dat": 'Which dative article follows mit: mit ___ {lemma}?',
        "akk_after": 'Which accusative article follows {prep}: {prep} ___ {lemma}?',
        "zu_contr": 'Choose the correct form: Ich gehe ___ {place}.',
        "bei_contr": 'Choose the correct form: Ich bin ___ {place}.',
        "von_contr": 'Choose the correct form: Ich komme ___ {place}.',
        "aus_contr": 'Choose the correct form: Ich komme ___ {place}.',
        "nach_city": 'Choose the correct preposition: Ich fahre ___ {place}.',
        "fudog_prep": "Choose the correct FUDOG preposition: {frame}",
        "fudog_case": 'Which case does "{prep}" always take?',
        "dat_prep_case": 'Which case does "{prep}" always take?',
        "wechsel_case": "Choose the correct form: {frame}",
        "wo_wohin": "Does this ask Wo?, Wohin?, or Woher?: {frame}",
        "local_matrix": "Choose the correct local form: {frame}",
        "home": "Choose the correct home expression: {frame}",
        "place_wo": 'Wo? Complete: Ich bin ___ {lemma}.',
        "place_wohin": 'Wohin? Complete: Ich gehe ___ {lemma}.',
    },
    "es": {
        "temp_month": '¿Qué preposición temporal encaja: ___ {lemma}?',
        "temp_day": '¿Qué preposición temporal encaja: ___ {lemma}?',
        "temp_season": '¿Qué preposición temporal encaja: ___ {lemma}?',
        "temp_clock": "¿Qué preposición temporal usan las horas?",
        "temp_night": "¿Qué forma es correcta para la noche?",
        "temp_year": "¿Qué opción es correcta para un año (p. ej. 1997)?",
        "temp_frame": "Elige la preposición temporal correcta: {frame}",
        "mit_dat": '¿Qué artículo dativo sigue a mit: mit ___ {lemma}?',
        "akk_after": '¿Qué artículo acusativo sigue a {prep}: {prep} ___ {lemma}?',
        "zu_contr": 'Elige la forma correcta: Ich gehe ___ {place}.',
        "bei_contr": 'Elige la forma correcta: Ich bin ___ {place}.',
        "von_contr": 'Elige la forma correcta: Ich komme ___ {place}.',
        "aus_contr": 'Elige la forma correcta: Ich komme ___ {place}.',
        "nach_city": 'Elige la preposición correcta: Ich fahre ___ {place}.',
        "fudog_prep": "Elige la preposición FUDOG correcta: {frame}",
        "fudog_case": '¿Qué caso lleva siempre "{prep}"?',
        "dat_prep_case": '¿Qué caso lleva siempre "{prep}"?',
        "wechsel_case": "Elige la forma correcta: {frame}",
        "wo_wohin": "¿Pregunta Wo?, Wohin? o Woher?: {frame}",
        "local_matrix": "Elige la forma local correcta: {frame}",
        "home": "Elige la expresión de casa correcta: {frame}",
        "place_wo": '¿Wo? Completa: Ich bin ___ {lemma}.',
        "place_wohin": '¿Wohin? Completa: Ich gehe ___ {lemma}.',
    },
    "fa": {
        "temp_month": 'کدام حرف اضافهٔ زمانی درست است: ___ {lemma}؟',
        "temp_day": 'کدام حرف اضافهٔ زمانی درست است: ___ {lemma}؟',
        "temp_season": 'کدام حرف اضافهٔ زمانی درست است: ___ {lemma}؟',
        "temp_clock": "برای ساعت کدام حرف اضافه درست است؟",
        "temp_night": "برای شب کدام شکل درست است؟",
        "temp_year": "برای سال (مثل 1997) کدام گزینه درست است؟",
        "temp_frame": "حرف اضافهٔ زمانی درست را انتخاب کنید: {frame}",
        "mit_dat": 'بعد از mit کدام حرف تعریف Dativ می‌آید: mit ___ {lemma}؟',
        "akk_after": 'بعد از {prep} کدام حرف تعریف Akkusativ می‌آید: {prep} ___ {lemma}؟',
        "zu_contr": 'شکل درست را انتخاب کنید: Ich gehe ___ {place}.',
        "bei_contr": 'شکل درست را انتخاب کنید: Ich bin ___ {place}.',
        "von_contr": 'شکل درست را انتخاب کنید: Ich komme ___ {place}.',
        "aus_contr": 'شکل درست را انتخاب کنید: Ich komme ___ {place}.',
        "nach_city": 'حرف اضافهٔ درست را انتخاب کنید: Ich fahre ___ {place}.',
        "fudog_prep": "حرف اضافهٔ FUDOG درست را انتخاب کنید: {frame}",
        "fudog_case": '"{prep}" همیشه کدام حالت می‌گیرد؟',
        "dat_prep_case": '"{prep}" همیشه کدام حالت می‌گیرد؟',
        "wechsel_case": "شکل درست را انتخاب کنید: {frame}",
        "wo_wohin": "این سؤال Wo؟ است، Wohin؟ یا Woher؟: {frame}",
        "local_matrix": "شکل مکانی درست را انتخاب کنید: {frame}",
        "home": "عبارت درست خانه را انتخاب کنید: {frame}",
        "place_wo": 'Wo؟ کامل کنید: Ich bin ___ {lemma}.',
        "place_wohin": 'Wohin؟ کامل کنید: Ich gehe ___ {lemma}.',
    },
    "ar": {
        "temp_month": 'أي حرف جر زمني يناسب: ___ {lemma}؟',
        "temp_day": 'أي حرف جر زمني يناسب: ___ {lemma}؟',
        "temp_season": 'أي حرف جر زمني يناسب: ___ {lemma}؟',
        "temp_clock": "أي حرف جر زمني تستخدمه الساعات؟",
        "temp_night": "أي صيغة صحيحة لليل؟",
        "temp_year": "أي خيار صحيح لسنة (مثل 1997)؟",
        "temp_frame": "اختر حرف الجر الزمني الصحيح: {frame}",
        "mit_dat": 'أي أداة تعريف في Dativ بعد mit: mit ___ {lemma}؟',
        "akk_after": 'أي أداة تعريف في Akkusativ بعد {prep}: {prep} ___ {lemma}؟',
        "zu_contr": 'اختر الصيغة الصحيحة: Ich gehe ___ {place}.',
        "bei_contr": 'اختر الصيغة الصحيحة: Ich bin ___ {place}.',
        "von_contr": 'اختر الصيغة الصحيحة: Ich komme ___ {place}.',
        "aus_contr": 'اختر الصيغة الصحيحة: Ich komme ___ {place}.',
        "nach_city": 'اختر حرف الجر الصحيح: Ich fahre ___ {place}.',
        "fudog_prep": "اختر حرف الجر FUDOG الصحيح: {frame}",
        "fudog_case": 'أي حالة يأخذها "{prep}" دائمًا؟',
        "dat_prep_case": 'أي حالة يأخذها "{prep}" دائمًا؟',
        "wechsel_case": "اختر الصيغة الصحيحة: {frame}",
        "wo_wohin": "هل السؤال Wo؟ أم Wohin؟ أم Woher؟: {frame}",
        "local_matrix": "اختر الصيغة المكانية الصحيحة: {frame}",
        "home": "اختر تعبير المنزل الصحيح: {frame}",
        "place_wo": 'Wo؟ أكمل: Ich bin ___ {lemma}.',
        "place_wohin": 'Wohin؟ أكمل: Ich gehe ___ {lemma}.',
    },
    "tr": {
        "temp_month": 'Hangi zaman edatı uyar: ___ {lemma}?',
        "temp_day": 'Hangi zaman edatı uyar: ___ {lemma}?',
        "temp_season": 'Hangi zaman edatı uyar: ___ {lemma}?',
        "temp_clock": "Saatler için hangi zaman edatı kullanılır?",
        "temp_night": "Gece için doğru biçim hangisidir?",
        "temp_year": "Takvim yılı (örn. 1997) için doğru seçenek hangisidir?",
        "temp_frame": "Doğru zaman edatını seçin: {frame}",
        "mit_dat": 'mit sonrası hangi datif artikel gelir: mit ___ {lemma}?',
        "akk_after": '{prep} sonrası hangi akkuzatif artikel gelir: {prep} ___ {lemma}?',
        "zu_contr": 'Doğru biçimi seçin: Ich gehe ___ {place}.',
        "bei_contr": 'Doğru biçimi seçin: Ich bin ___ {place}.',
        "von_contr": 'Doğru biçimi seçin: Ich komme ___ {place}.',
        "aus_contr": 'Doğru biçimi seçin: Ich komme ___ {place}.',
        "nach_city": 'Doğru edatı seçin: Ich fahre ___ {place}.',
        "fudog_prep": "Doğru FUDOG edatını seçin: {frame}",
        "fudog_case": '"{prep}" her zaman hangi hali alır?',
        "dat_prep_case": '"{prep}" her zaman hangi hali alır?',
        "wechsel_case": "Doğru biçimi seçin: {frame}",
        "wo_wohin": "Bu soru Wo?, Wohin? yoksa Woher? mu: {frame}",
        "local_matrix": "Doğru yer biçimini seçin: {frame}",
        "home": "Doğru ev ifadesini seçin: {frame}",
        "place_wo": 'Wo? Tamamlayın: Ich bin ___ {lemma}.',
        "place_wohin": 'Wohin? Tamamlayın: Ich gehe ___ {lemma}.',
    },
}

MONTHS = [
    "Januar", "Februar", "März", "April", "Mai", "Juni",
    "Juli", "August", "September", "Oktober", "November", "Dezember",
]
DAYS = ["Montag", "Dienstag", "Mittwoch", "Donnerstag", "Freitag", "Samstag", "Sonntag"]
SEASONS = ["Sommer", "Winter", "Frühling", "Herbst"]
DAY_PARTS_AM = ["Morgen", "Mittag", "Nachmittag", "Abend", "Wochenende"]

DATIVE_PREPS = ["aus", "bei", "mit", "nach", "seit", "von", "zu"]
FUDOG_PREPS = ["für", "um", "durch", "ohne", "gegen"]

TEMP_FRAMES = [
    ("seit-jahren", "Ich wohne ___ drei Jahren hier.", "seit", ["vor", "nach", "bis"]),
    ("seit-monaten", "Er lernt Deutsch ___ zwei Monaten.", "seit", ["vor", "nach", "ab"]),
    ("vor-arbeit", "___ der Arbeit trinke ich Kaffee.", "Vor", ["Nach", "Seit", "Bis"]),
    ("nach-essen", "___ dem Essen gehen wir spazieren.", "Nach", ["Vor", "Seit", "Ab"]),
    ("bis-morgen", "Wir bleiben ___ morgen.", "bis", ["seit", "ab", "um"]),
    ("bis-zum-abend", "Ich arbeite ___ Abend.", "bis zum", ["seit dem", "vor dem", "nach dem"]),
    ("ab-morgen", "___ morgen stehe ich früher auf.", "Ab", ["Seit", "Bis", "Um"]),
    ("von-bis", "Ich arbeite ___ Montag ___ Freitag.", "von … bis", ["seit … bis", "ab … bis", "um … bis"]),
    ("zwischen", "Der Kurs ist ___ 5 und 7 Uhr.", "zwischen", ["seit", "um", "bis"]),
    ("um-mitternacht", "Der Zug kommt ___ Mitternacht.", "um", ["am", "im", "seit"]),
]

ZU_ITEMS = [
    ("arzt", "Arzt (der)", "zum", ["zur", "beim", "nach"]),
    ("bahnhof", "Bahnhof (der)", "zum", ["zur", "ins", "nach"]),
    ("supermarkt", "Supermarkt (der)", "zum", ["zur", "ins", "nach"]),
    ("friseur", "Friseur (der)", "zum", ["zur", "beim", "nach"]),
    ("schule", "Schule (die)", "zur", ["zum", "in die", "nach"]),
    ("arbeit", "Arbeit (die)", "zur", ["zum", "bei der", "nach"]),
    ("post", "Post (die)", "zur", ["zum", "in die", "nach"]),
    ("eltern", "Eltern (die Pl.)", "zu den", ["zum", "bei den", "nach den"]),
]
BEI_ITEMS = [
    ("arzt", "Arzt (der)", "beim", ["zum", "vom", "am"]),
    ("freund", "Freund (der)", "beim", ["zum", "vom", "mit dem"]),
    ("friseur", "Friseur (der)", "beim", ["zum", "vom", "am"]),
    ("arbeit", "Arbeit (die)", "bei der", ["zur", "von der", "an der"]),
    ("oma", "Oma (die)", "bei der", ["zur", "von der", "nach"]),
]
# Woher with von: people / activities / institutions (not enclosed rooms → those use aus)
VON_ITEMS = [
    ("arzt", "Arzt (der)", "vom", ["zum", "beim", "aus dem"]),
    ("friseur", "Friseur (der)", "vom", ["zum", "beim", "aus dem"]),
    ("markt", "Markt (der)", "vom", ["zum", "auf dem", "aus dem"]),
    ("arbeit", "Arbeit (die)", "von der", ["zur", "bei der", "aus der"]),
    ("party", "Party (die)", "von der", ["zur", "bei der", "aus der"]),
]
# Woher with aus: enclosed places / countries
AUS_ITEMS = [
    ("haus", "Haus (das)", "aus dem", ["vom", "im", "ins"]),
    ("kino", "Kino (das)", "aus dem", ["vom", "im", "zum"]),
    ("schule", "Schule (die)", "aus der", ["von der", "zur", "in der"]),
    ("wohnung", "Wohnung (die)", "aus der", ["von der", "zur", "in der"]),
    ("schweiz", "Schweiz (die)", "aus der", ["von der", "nach", "in der"]),
    ("garten", "Garten (der)", "aus dem", ["vom", "im", "zum"]),
]
NACH_CITIES = [
    ("berlin", "Berlin", "nach", ["in", "zu", "an"]),
    ("hamburg", "Hamburg", "nach", ["in", "zu", "auf"]),
    ("muenchen", "München", "nach", ["in", "zu", "bei"]),
    ("koeln", "Köln", "nach", ["in", "zu", "an"]),
    ("italien", "Italien", "nach", ["in die", "zu", "auf"]),
    ("spanien", "Spanien", "nach", ["in die", "zu", "an"]),
    ("frankreich", "Frankreich", "nach", ["in die", "zu", "bei"]),
    ("oesterreich", "Österreich", "nach", ["in die", "zu", "auf"]),
]

FUDOG_FRAMES = [
    ("fuer-dich", "Der Tee ist ___ dich.", "für", ["um", "durch", "ohne"]),
    ("durch-park", "Wir gehen ___ den Park.", "durch", ["für", "um", "gegen"]),
    ("um-tisch", "Die Katze läuft ___ den Tisch.", "um", ["durch", "für", "ohne"]),
    ("ohne-zucker", "Ich trinke Kaffee ___ Zucker.", "ohne", ["für", "gegen", "durch"]),
    ("gegen-baum", "Das Auto fährt ___ den Baum.", "gegen", ["durch", "für", "um"]),
    ("fuer-freund", "Das Geschenk ist ___ meinen Freund.", "für", ["gegen", "ohne", "um"]),
    ("durch-fenster", "Der Vogel fliegt ___ das Fenster.", "durch", ["um", "gegen", "für"]),
    ("ohne-mantel", "Im Sommer gehe ich ___ Mantel.", "ohne", ["für", "durch", "gegen"]),
    ("fuer-kinder", "Die Spiele sind ___ die Kinder.", "für", ["ohne", "gegen", "um"]),
    ("gegen-kopfschmerzen", "Ich nehme ein Medikament ___ Kopfschmerzen.", "gegen", ["für", "durch", "ohne"]),
    ("um-ecke", "Das Café ist gleich ___ die Ecke.", "um", ["durch", "für", "gegen"]),
    ("durch-tunnel", "Der Zug fährt ___ den Tunnel.", "durch", ["um", "gegen", "für"]),
]

WECHSEL_FRAMES = [
    ("auf-dem-tisch", "Das Buch liegt ___ Tisch. (Wo?)", "auf dem", ["auf den", "auf das", "auf der"]),
    ("auf-den-tisch", "Ich lege das Buch ___ Tisch. (Wohin?)", "auf den", ["auf dem", "auf der", "auf das"]),
    ("in-der-tasche", "Der Schlüssel ist ___ Tasche. (Wo?)", "in der", ["in die", "in dem", "in das"]),
    ("in-die-tasche", "Ich stecke den Schlüssel ___ Tasche. (Wohin?)", "in die", ["in der", "in dem", "ins"]),
    ("im-haus", "Wir sind ___ Haus. (Wo?)", "im", ["ins", "am", "zum"]),
    ("ins-haus", "Wir gehen ___ Haus. (Wohin?)", "ins", ["im", "am", "zum"]),
    ("am-fenster", "Die Lampe steht ___ Fenster. (Wo?)", "am", ["ans", "im", "aufs"]),
    ("ans-fenster", "Komm ___ Fenster! (Wohin?)", "ans", ["am", "ins", "aufs"]),
    ("auf-der-strasse", "Die Kinder spielen ___ Straße. (Wo?)", "auf der", ["auf die", "auf dem", "an der"]),
    ("auf-die-strasse", "Wir gehen ___ Straße. (Wohin?)", "auf die", ["auf der", "auf den", "an die"]),
    ("unter-dem-bett", "Die Schuhe sind ___ Bett. (Wo?)", "unter dem", ["unter das", "unter den", "unter der"]),
    ("unter-das-bett", "Die Katze kriecht ___ Bett. (Wohin?)", "unter das", ["unter dem", "unter den", "unter der"]),
    ("neben-dem-sofa", "Der Tisch steht ___ Sofa. (Wo?)", "neben dem", ["neben das", "neben den", "neben der"]),
    ("neben-das-sofa", "Stell den Tisch ___ Sofa! (Wohin?)", "neben das", ["neben dem", "neben den", "neben der"]),
    ("hinter-dem-haus", "Das Auto steht ___ Haus. (Wo?)", "hinter dem", ["hinter das", "hinter den", "hinter der"]),
    ("hinter-das-haus", "Fahr ___ Haus! (Wohin?)", "hinter das", ["hinter dem", "hinter den", "hinter der"]),
    ("ueber-dem-tisch", "Die Lampe hängt ___ Tisch. (Wo?)", "über dem", ["über den", "über das", "über der"]),
    ("ueber-den-tisch", "Häng die Lampe ___ Tisch! (Wohin?)", "über den", ["über dem", "über das", "über der"]),
    ("vor-dem-haus", "Wir warten ___ Haus. (Wo?)", "vor dem", ["vor das", "vor den", "vor der"]),
    ("vor-das-haus", "Komm ___ Haus! (Wohin?)", "vor das", ["vor dem", "vor den", "vor der"]),
]

WO_WOHIN_ITEMS = [
    ("liegen", "Wo liegt das Buch?", "Wo?", ["Wohin?", "Woher?", "Wann?"]),
    ("legen", "Wohin legst du das Buch?", "Wohin?", ["Wo?", "Woher?", "Wann?"]),
    ("stehen", "Wo steht die Vase?", "Wo?", ["Wohin?", "Woher?", "Warum?"]),
    ("stellen", "Wohin stellst du die Vase?", "Wohin?", ["Wo?", "Woher?", "Warum?"]),
    ("sitzen", "Wo sitzt du?", "Wo?", ["Wohin?", "Woher?", "Wie?"]),
    ("setzen", "Wohin setzt du dich?", "Wohin?", ["Wo?", "Woher?", "Wie?"]),
    ("sein", "Wo bist du?", "Wo?", ["Wohin?", "Woher?", "Wer?"]),
    ("gehen", "Wohin gehst du?", "Wohin?", ["Wo?", "Woher?", "Wer?"]),
    ("wohnen", "Wo wohnst du?", "Wo?", ["Wohin?", "Woher?", "Wann?"]),
    ("fahren", "Wohin fährst du?", "Wohin?", ["Wo?", "Woher?", "Wann?"]),
    ("kommen", "Woher kommst du?", "Woher?", ["Wo?", "Wohin?", "Wann?"]),
]

LOCAL_ITEMS = [
    ("berlin-wohin", "Ich fahre ___ Berlin.", "nach", ["in", "zu", "an"]),
    ("berlin-wo", "Ich wohne ___ Berlin.", "in", ["nach", "zu", "bei"]),
    ("berlin-woher", "Ich komme ___ Berlin.", "aus", ["von", "nach", "zu"]),
    ("italien-wohin", "Wir fliegen ___ Italien.", "nach", ["in die", "zu", "auf"]),
    ("italien-wo", "Wir sind ___ Italien.", "in", ["nach", "zu", "bei"]),
    ("italien-woher", "Wir kommen ___ Italien.", "aus", ["von", "nach", "zu"]),
    ("schweiz-wohin", "Wir fahren ___ Schweiz.", "in die", ["nach", "zur", "auf die"]),
    ("schweiz-wo", "Wir sind ___ Schweiz.", "in der", ["in die", "nach", "bei der"]),
    ("schweiz-woher", "Wir kommen ___ Schweiz.", "aus der", ["von der", "in der", "nach"]),
    ("tuerkei-wohin", "Sie fliegt ___ Türkei.", "in die", ["nach", "zur", "auf die"]),
    ("tuerkei-wo", "Sie ist ___ Türkei.", "in der", ["in die", "nach", "bei der"]),
    ("tuerkei-woher", "Sie kommt ___ Türkei.", "aus der", ["von der", "nach", "in der"]),
    ("usa-wohin", "Er fliegt ___ USA.", "in die", ["nach", "zu den", "auf die"]),
    ("usa-wo", "Er lebt ___ USA.", "in den", ["in die", "nach", "bei den"]),
    ("usa-woher", "Er kommt ___ USA.", "aus den", ["von den", "nach", "in den"]),
    ("kino-wohin", "Ich gehe ___ Kino.", "ins", ["im", "zum", "nach"]),
    ("kino-wo", "Ich bin ___ Kino.", "im", ["ins", "zum", "am"]),
    ("kino-woher", "Ich komme ___ Kino.", "aus dem", ["vom", "im", "ins"]),
    ("park-wohin", "Wir gehen ___ Park.", "in den", ["im", "auf den", "zum"]),
    ("park-wo", "Wir sind ___ Park.", "im", ["in den", "auf dem", "am"]),
    ("park-woher", "Wir kommen ___ Park.", "aus dem", ["vom", "im", "in den"]),
    ("strand-wohin", "Wir gehen ___ Strand.", "an den", ["am", "auf den", "zum"]),
    ("strand-wo", "Wir sind ___ Strand.", "am", ["an den", "auf dem", "beim"]),
    ("strand-woher", "Wir kommen ___ Strand.", "vom", ["aus dem", "am", "an den"]),
    ("meer-wohin", "Wir fahren ___ Meer.", "ans", ["am", "aufs", "zum"]),
    ("meer-wo", "Wir sind ___ Meer.", "am", ["ans", "auf dem", "im"]),
    ("meer-woher", "Wir kommen ___ Meer.", "vom", ["aus dem", "am", "ans"]),
    ("arzt-wohin", "Ich gehe ___ Arzt.", "zum", ["beim", "vom", "nach"]),
    ("arzt-wo", "Ich bin ___ Arzt.", "beim", ["zum", "vom", "am"]),
    ("arzt-woher", "Ich komme ___ Arzt.", "vom", ["zum", "beim", "aus dem"]),
    ("ikea-wohin", "Wir gehen ___ Ikea.", "zu", ["bei", "nach", "in"]),
    ("ikea-wo", "Wir sind ___ Ikea.", "bei", ["zu", "nach", "in"]),
    ("ikea-woher", "Wir kommen ___ Ikea.", "von", ["zu", "bei", "aus"]),
    ("berg-wohin", "Wir gehen ___ Berg.", "auf den", ["auf dem", "an den", "zum"]),
    ("berg-wo", "Wir sind ___ Berg.", "auf dem", ["auf den", "am", "beim"]),
    ("berg-woher", "Wir kommen ___ Berg.", "vom", ["aus dem", "auf dem", "auf den"]),
    ("markt-wohin", "Ich gehe ___ Markt.", "auf den", ["auf dem", "an den", "zum"]),
    ("markt-wo", "Ich bin ___ Markt.", "auf dem", ["auf den", "am", "beim"]),
    ("markt-woher", "Ich komme ___ Markt.", "vom", ["aus dem", "auf dem", "auf den"]),
]

HOME_ITEMS = [
    ("wohin", "Wohin gehst du? — ___ Hause.", "nach", ["zu", "von", "in"]),
    ("wo", "Wo bist du? — ___ Hause.", "zu", ["nach", "von", "in"]),
    ("woher", "Woher kommst du? — ___ zu Hause.", "von", ["nach", "zu", "aus"]),
]

# Natural location prepositions for A1 places (lemma -> prep)
PLACE_PREP: dict[str, str] = {
    # in + enclosed / rooms / buildings
    "Haus": "in",
    "Wohnung": "in",
    "Kino": "in",
    "Museum": "in",
    "Restaurant": "in",
    "Café": "in",
    "Hotel": "in",
    "Büro": "in",
    "Krankenhaus": "in",
    "Schwimmbad": "in",
    "Supermarkt": "in",
    "Park": "in",
    "Garten": "in",
    "Wald": "in",
    "Kirche": "in",
    "Schule": "in",
    "Bibliothek": "in",
    "Küche": "in",
    "Bad": "in",
    "Zimmer": "in",
    "Wohnzimmer": "in",
    "Schlafzimmer": "in",
    "Garage": "in",
    "Bank": "in",  # bank building (also "an die Bank" edge sense — A1 enclosed OK)
    "Apotheke": "in",
    "Bäckerei": "in",
    "Oper": "in",
    "Theater": "in",
    "Stadion": "in",
    "Universität": "in",
    "Dorf": "in",
    "Stadt": "in",
    # an + edge / water / vertical
    "Strand": "an",
    "Meer": "an",
    "See": "an",
    "Fluss": "an",
    "Fenster": "an",
    "Wand": "an",
    "Haltestelle": "an",
    "Computer": "an",
    "Tisch": "an",  # an den Tisch (sit down at)
    # auf + surface / open square / mountain
    "Berg": "auf",
    "Markt": "auf",
    "Platz": "auf",
    "Straße": "auf",
    "Sofa": "auf",
    "Balkon": "auf",
    "Terrasse": "auf",
    "Insel": "auf",
    "Boden": "auf",
    # edge / point-of-arrival (A1: am Bahnhof / am Flughafen)
    "Bahnhof": "an",
    "Flughafen": "an",
}

SKIP_LEMMAS = {
    "Beisl",
    "Bekannte",
    "Erwachsene",
}


def is_plurale_tantum(lexeme: dict) -> bool:
    source = lexeme.get("source_german") or ""
    return "(Pl" in source or "Pl.)" in source


def distractors_for(correct: str, pool: list[str], n: int = 3) -> list[str]:
    opts: list[str] = []
    for candidate in pool:
        if candidate != correct and candidate not in opts and str(candidate).strip():
            opts.append(candidate)
        if len(opts) >= n:
            break
    i = 0
    while len(opts) < n:
        cand = f"{correct}-{i + 1}"
        if cand not in opts:
            opts.append(cand)
        i += 1
        if i > 20:
            raise RuntimeError(f"Could not build distractors for {correct!r}")
    return opts[:n]


def dat_def(article: str) -> str:
    return {"der": "dem", "das": "dem", "die": "der"}[article]


def akk_def(article: str) -> str:
    return {"der": "den", "das": "das", "die": "die"}[article]


def place_form(prep: str, article: str, case: str) -> str:
    """Return preferred A1 form including common contractions."""
    if case == "dat":
        if prep == "in" and article in {"der", "das"}:
            return "im"
        if prep == "an" and article in {"der", "das"}:
            return "am"
        if prep == "auf" and article == "das":
            return "auf dem"
        return f"{prep} {dat_def(article)}"
    if case == "akk":
        if prep == "in" and article == "das":
            return "ins"
        if prep == "an" and article == "das":
            return "ans"
        if prep == "auf" and article == "das":
            return "aufs"
        return f"{prep} {akk_def(article)}"
    raise ValueError(case)


def place_distractors(prep: str, article: str, case: str, correct: str) -> list[str]:
    pool: list[str] = []
    for other_case in ("dat", "akk"):
        form = place_form(prep, article, other_case)
        if form != correct:
            pool.append(form)
    # rival prepositions with same case
    for other_prep in ("in", "an", "auf", "zu"):
        if other_prep == prep:
            continue
        if other_prep == "zu":
            if article == "der":
                pool.append("zum" if case == "dat" else "zu dem")
            elif article == "die":
                pool.append("zur" if case == "dat" else "zu der")
            else:
                pool.append("zum")
            continue
        try:
            pool.append(place_form(other_prep, article, case))
        except Exception:
            continue
    # raw wrong articles
    pool.extend(
        [
            f"{prep} {dat_def(article)}" if case == "akk" else f"{prep} {akk_def(article)}",
            "nach",
            "bei",
        ]
    )
    return distractors_for(correct, pool, n=3)


def main() -> None:
    vocab = json.loads(VOCAB_PATH.read_text(encoding="utf-8"))
    nouns: list[dict] = []
    by_lemma: dict[str, dict] = {}
    seen: set[str] = set()
    for lexeme in vocab["lexemes"]:
        lemma = lexeme["lemma"]
        if lemma in seen or lemma in SKIP_LEMMAS or " " in lemma or "-" in lemma:
            continue
        if is_plurale_tantum(lexeme):
            continue
        for sense in lexeme.get("senses", []):
            if sense.get("part_of_speech") != "noun":
                continue
            detail = sense.get("german_noun") or {}
            article = detail.get("article")
            gender = detail.get("grammatical_gender")
            if article not in {"der", "die", "das"}:
                continue
            if gender not in {"masculine", "feminine", "neuter"}:
                continue
            item = {
                "lemma": lemma,
                "article": article,
                "import_key": sense.get("import_key"),
            }
            nouns.append(item)
            by_lemma[lemma] = item
            seen.add(lemma)
            break

    priority = {
        "Bus", "Zug", "Auto", "Fahrrad", "Freund", "Freundin", "Haus", "Wohnung",
        "Tisch", "Stuhl", "Park", "Garten", "Schule", "Arbeit", "Arzt", "Lehrer",
        "Lehrerin", "Kind", "Mann", "Frau", "Hund", "Katze", "Buch", "Stift",
        "Tasche", "Fenster", "Tür", "Bett", "Sofa", "Markt", "Supermarkt",
        "Bahnhof", "Flughafen", "Hotel", "Restaurant", "Kino", "Museum", "Stadt",
        "Dorf", "Berg", "See", "Meer", "Strand", "Platz", "Straße", "Brücke",
        "Kirche", "Bank", "Café", "Büro", "Computer", "Bleistift", "Apfel",
        "Brot", "Kaffee", "Tee", "Film", "Handy", "Pass", "Wagen", "Taxi",
    }
    nouns_sorted = sorted(nouns, key=lambda n: (0 if n["lemma"] in priority else 1, n["lemma"]))
    # Concrete-ish nouns for morphology drills (avoid ultra-abstract first)
    case_nouns = nouns_sorted[:240]

    questions: list[dict] = []

    def add_q(
        import_key: str,
        topic: str,
        lemma: str,
        answer: str,
        distractors: list[str],
        translations: dict,
        meta: dict | None = None,
        sense_key: str | None = None,
    ) -> None:
        clean = distractors_for(answer, distractors, n=3)
        questions.append(
            {
                "import_key": import_key,
                "topic": topic,
                "source_lemma": lemma,
                "sense_import_key": sense_key,
                "answer": answer,
                "distractors": clean,
                "blank_token": "___",
                "meta": meta or {},
                "translations": translations,
            }
        )

    # 1) Temporal
    for month in MONTHS:
        add_q(
            f"{KEY_PREFIX}:temp-month:{month}",
            "temporal",
            month,
            "im",
            ["am", "um", "in", "an"],
            {lang: {"prompt": t["temp_month"].format(lemma=month)} for lang, t in PROMPT.items()},
            {"kind": "temp_month"},
        )
    for day in DAYS:
        add_q(
            f"{KEY_PREFIX}:temp-day:{day}",
            "temporal",
            day,
            "am",
            ["im", "um", "in", "an"],
            {lang: {"prompt": t["temp_day"].format(lemma=day)} for lang, t in PROMPT.items()},
            {"kind": "temp_day"},
        )
    for part in DAY_PARTS_AM:
        add_q(
            f"{KEY_PREFIX}:temp-daypart:{part}",
            "temporal",
            part,
            "am",
            ["im", "um", "in der", "seit"],
            {lang: {"prompt": t["temp_day"].format(lemma=part)} for lang, t in PROMPT.items()},
            {"kind": "temp_daypart"},
        )
    for season in SEASONS:
        add_q(
            f"{KEY_PREFIX}:temp-season:{season}",
            "temporal",
            season,
            "im",
            ["am", "um", "in der", "seit"],
            {lang: {"prompt": t["temp_season"].format(lemma=season)} for lang, t in PROMPT.items()},
            {"kind": "temp_season"},
        )
    add_q(
        f"{KEY_PREFIX}:temp-clock",
        "temporal",
        "Uhrzeit",
        "um",
        ["am", "im", "seit", "bis"],
        {lang: {"prompt": t["temp_clock"]} for lang, t in PROMPT.items()},
        {"kind": "temp_clock"},
    )
    add_q(
        f"{KEY_PREFIX}:temp-night",
        "temporal",
        "Nacht",
        "in der",
        ["am", "im", "um", "an der"],
        {lang: {"prompt": t["temp_night"]} for lang, t in PROMPT.items()},
        {"kind": "temp_night"},
    )
    add_q(
        f"{KEY_PREFIX}:temp-year",
        "temporal",
        "Jahr",
        "— (no preposition)",
        ["im", "am", "um", "in"],
        {lang: {"prompt": t["temp_year"]} for lang, t in PROMPT.items()},
        {"kind": "temp_year"},
    )
    for key, frame, answer, wrong in TEMP_FRAMES:
        add_q(
            f"{KEY_PREFIX}:temp-frame:{key}",
            "temporal",
            key,
            answer,
            wrong,
            {lang: {"prompt": t["temp_frame"].format(frame=frame)} for lang, t in PROMPT.items()},
            {"kind": "temp_frame"},
        )

    # 2) mit + dative article (morphology)
    for noun in case_nouns:
        lemma = noun["lemma"]
        article = noun["article"]
        answer = dat_def(article)
        add_q(
            f"{KEY_PREFIX}:mit:{lemma}",
            "dative_prepositions",
            lemma,
            answer,
            [a for a in ("dem", "der", "den", "das", "die", "ein") if a != answer],
            {lang: {"prompt": t["mit_dat"].format(lemma=lemma)} for lang, t in PROMPT.items()},
            {"kind": "mit_dat", "article": article},
            noun.get("import_key"),
        )

    # 3) FUDOG morphology: accusative article after für/durch/gegen/um
    for noun in case_nouns:
        lemma = noun["lemma"]
        article = noun["article"]
        answer = akk_def(article)
        for prep in ("durch", "für", "gegen", "um"):
            add_q(
                f"{KEY_PREFIX}:{prep}:{lemma}",
                "accusative_prepositions",
                lemma,
                answer,
                [a for a in ("den", "das", "die", "dem", "der") if a != answer],
                {
                    lang: {"prompt": t["akk_after"].format(prep=prep, lemma=lemma)}
                    for lang, t in PROMPT.items()
                },
                {"kind": f"{prep}_akk", "article": article},
                noun.get("import_key"),
            )

    # 4) Contractions & aus/nach
    for key, place, answer, wrong in ZU_ITEMS:
        add_q(
            f"{KEY_PREFIX}:zu:{key}",
            "dative_prepositions",
            key,
            answer,
            wrong,
            {lang: {"prompt": t["zu_contr"].format(place=place)} for lang, t in PROMPT.items()},
            {"kind": "zu_contraction"},
        )
    for key, place, answer, wrong in BEI_ITEMS:
        add_q(
            f"{KEY_PREFIX}:bei:{key}",
            "dative_prepositions",
            key,
            answer,
            wrong,
            {lang: {"prompt": t["bei_contr"].format(place=place)} for lang, t in PROMPT.items()},
            {"kind": "bei_contraction"},
        )
    for key, place, answer, wrong in VON_ITEMS:
        add_q(
            f"{KEY_PREFIX}:von:{key}",
            "dative_prepositions",
            key,
            answer,
            wrong,
            {lang: {"prompt": t["von_contr"].format(place=place)} for lang, t in PROMPT.items()},
            {"kind": "von_contraction"},
        )
    for key, place, answer, wrong in AUS_ITEMS:
        add_q(
            f"{KEY_PREFIX}:aus:{key}",
            "dative_prepositions",
            key,
            answer,
            wrong,
            {lang: {"prompt": t["aus_contr"].format(place=place)} for lang, t in PROMPT.items()},
            {"kind": "aus_contraction"},
        )
    for key, place, answer, wrong in NACH_CITIES:
        add_q(
            f"{KEY_PREFIX}:nach:{key}",
            "local_matrix",
            key,
            answer,
            wrong,
            {lang: {"prompt": t["nach_city"].format(place=place)} for lang, t in PROMPT.items()},
            {"kind": "nach_city"},
        )

    # 5) Always-dative / always-accusative case meta
    for prep in DATIVE_PREPS:
        add_q(
            f"{KEY_PREFIX}:case-dat:{prep}",
            "dative_prepositions",
            prep,
            "Dative",
            ["Accusative", "Nominative", "Genitive"],
            {lang: {"prompt": t["dat_prep_case"].format(prep=prep)} for lang, t in PROMPT.items()},
            {"kind": "dat_prep_case"},
        )
    for prep in FUDOG_PREPS:
        add_q(
            f"{KEY_PREFIX}:case-akk:{prep}",
            "accusative_prepositions",
            prep,
            "Accusative",
            ["Dative", "Nominative", "Genitive"],
            {lang: {"prompt": t["fudog_case"].format(prep=prep)} for lang, t in PROMPT.items()},
            {"kind": "fudog_case"},
        )

    # 6) Curated frames
    for key, frame, answer, wrong in FUDOG_FRAMES:
        add_q(
            f"{KEY_PREFIX}:fudog:{key}",
            "accusative_prepositions",
            answer,
            answer,
            wrong,
            {lang: {"prompt": t["fudog_prep"].format(frame=frame)} for lang, t in PROMPT.items()},
            {"kind": "fudog_frame"},
        )
    for key, frame, answer, wrong in WECHSEL_FRAMES:
        add_q(
            f"{KEY_PREFIX}:wechsel:{key}",
            "two_way_prepositions",
            key,
            answer,
            wrong,
            {lang: {"prompt": t["wechsel_case"].format(frame=frame)} for lang, t in PROMPT.items()},
            {"kind": "wechsel_form"},
        )
    for key, frame, answer, wrong in WO_WOHIN_ITEMS:
        add_q(
            f"{KEY_PREFIX}:wo-wohin:{key}",
            "two_way_prepositions",
            key,
            answer,
            wrong,
            {lang: {"prompt": t["wo_wohin"].format(frame=frame)} for lang, t in PROMPT.items()},
            {"kind": "wo_wohin"},
        )
    for key, frame, answer, wrong in LOCAL_ITEMS:
        add_q(
            f"{KEY_PREFIX}:local:{key}",
            "local_matrix",
            key,
            answer,
            wrong,
            {lang: {"prompt": t["local_matrix"].format(frame=frame)} for lang, t in PROMPT.items()},
            {"kind": "local_matrix"},
        )
    for key, frame, answer, wrong in HOME_ITEMS:
        add_q(
            f"{KEY_PREFIX}:home:{key}",
            "local_matrix",
            key,
            answer,
            wrong,
            {lang: {"prompt": t["home"].format(frame=frame)} for lang, t in PROMPT.items()},
            {"kind": "home"},
        )

    # 7) Natural place Wo?/Wohin? with correct prep collocation
    missing_places: list[str] = []
    for lemma, prep in sorted(PLACE_PREP.items()):
        noun = by_lemma.get(lemma)
        if not noun:
            missing_places.append(lemma)
            continue
        article = noun["article"]
        wo = place_form(prep, article, "dat")
        wohin = place_form(prep, article, "akk")
        add_q(
            f"{KEY_PREFIX}:place-wo:{lemma}",
            "two_way_prepositions",
            lemma,
            wo,
            place_distractors(prep, article, "dat", wo),
            {lang: {"prompt": t["place_wo"].format(lemma=lemma)} for lang, t in PROMPT.items()},
            {"kind": "place_wo", "prep": prep, "article": article},
            noun.get("import_key"),
        )
        add_q(
            f"{KEY_PREFIX}:place-wohin:{lemma}",
            "two_way_prepositions",
            lemma,
            wohin,
            place_distractors(prep, article, "akk", wohin),
            {lang: {"prompt": t["place_wohin"].format(lemma=lemma)} for lang, t in PROMPT.items()},
            {"kind": "place_wohin", "prep": prep, "article": article},
            noun.get("import_key"),
        )

    if missing_places:
        # Soft warning only — some lemmas may be absent from vocab
        print(f"note: place lemmas missing from vocab: {missing_places}")

    by_key = {q["import_key"]: q for q in questions}
    expected = {
        f"{KEY_PREFIX}:temp-month:Januar": "im",
        f"{KEY_PREFIX}:temp-day:Montag": "am",
        f"{KEY_PREFIX}:temp-season:Sommer": "im",
        f"{KEY_PREFIX}:temp-clock": "um",
        f"{KEY_PREFIX}:temp-night": "in der",
        f"{KEY_PREFIX}:temp-year": "— (no preposition)",
        f"{KEY_PREFIX}:temp-frame:seit-jahren": "seit",
        f"{KEY_PREFIX}:mit:Bus": "dem",
        f"{KEY_PREFIX}:durch:Park": "den",
        f"{KEY_PREFIX}:für:Frau": "die",
        f"{KEY_PREFIX}:zu:arzt": "zum",
        f"{KEY_PREFIX}:bei:arzt": "beim",
        f"{KEY_PREFIX}:von:arzt": "vom",
        f"{KEY_PREFIX}:aus:schule": "aus der",
        f"{KEY_PREFIX}:nach:berlin": "nach",
        f"{KEY_PREFIX}:case-dat:mit": "Dative",
        f"{KEY_PREFIX}:case-akk:für": "Accusative",
        f"{KEY_PREFIX}:fudog:durch-park": "durch",
        f"{KEY_PREFIX}:wechsel:auf-dem-tisch": "auf dem",
        f"{KEY_PREFIX}:wechsel:auf-den-tisch": "auf den",
        f"{KEY_PREFIX}:wo-wohin:liegen": "Wo?",
        f"{KEY_PREFIX}:wo-wohin:kommen": "Woher?",
        f"{KEY_PREFIX}:local:berlin-wohin": "nach",
        f"{KEY_PREFIX}:local:kino-wo": "im",
        f"{KEY_PREFIX}:local:schweiz-wohin": "in die",
        f"{KEY_PREFIX}:local:usa-wo": "in den",
        f"{KEY_PREFIX}:home:wohin": "nach",
        f"{KEY_PREFIX}:home:wo": "zu",
        f"{KEY_PREFIX}:home:woher": "von",
        f"{KEY_PREFIX}:place-wo:Kino": "im",
        f"{KEY_PREFIX}:place-wohin:Kino": "ins",
        f"{KEY_PREFIX}:place-wo:Strand": "am",
        f"{KEY_PREFIX}:place-wohin:Strand": "an den",
        f"{KEY_PREFIX}:place-wo:Berg": "auf dem",
        f"{KEY_PREFIX}:place-wohin:Berg": "auf den",
        f"{KEY_PREFIX}:place-wo:Schule": "in der",
        f"{KEY_PREFIX}:place-wohin:Schule": "in die",
    }
    for key, answer in expected.items():
        actual = by_key.get(key, {}).get("answer")
        assert actual == answer, f"{key}: expected {answer!r}, got {actual!r}"

    # No nonsense motion frames with random nouns
    for question in questions:
        prompt = question["translations"]["en"]["prompt"]
        kind = question["meta"].get("kind")
        if kind in {"place_wo", "place_wohin"}:
            assert question["source_lemma"] in PLACE_PREP
        if kind in {"wo_article", "wohin_article"}:
            raise AssertionError("legacy nonsense kinds must not be generated")
        assert "Ich gehe auf ___ Baby" not in prompt

    for question in questions:
        assert question["answer"] not in question["distractors"]
        assert len(question["distractors"]) == 3
        assert len(set(question["distractors"])) == 3
        for lang in ("en", "es", "fa", "ar", "tr"):
            assert question["translations"][lang]["prompt"].strip()
        blob = " ".join(t["prompt"] for t in question["translations"].values())
        for token in ("PDF", "Page ", "Section ", "eleventh lesson", "handwritten"):
            assert token not in blob, f"forbidden token in {question['import_key']}: {token}"

    keys = [q["import_key"] for q in questions]
    assert len(keys) == len(set(keys)), "duplicate import_key"
    assert len(questions) >= 1000, f"need ≥1000 questions, got {len(questions)}"

    payload = {
        "meta": {
            "lesson_code": LESSON,
            "course_code": COURSE,
            "min_count": 1000,
            "generated_count": len(questions),
            "translation_languages": ["en", "es", "fa", "ar", "tr"],
            "source_vocabulary": "a1_german_words.json",
            "source_lesson": "german_grammar_lesson_06.md",
            "nouns_used": len(case_nouns),
            "place_lemmas": len(PLACE_PREP),
        },
        "questions": questions,
    }
    OUT_PATH.write_text(json.dumps(payload, ensure_ascii=False, indent=2), encoding="utf-8")
    print(f"wrote {OUT_PATH} count={len(questions)}")
    print(Counter(q["topic"] for q in questions))
    print(Counter((q.get("meta") or {}).get("kind") for q in questions))


if __name__ == "__main__":
    main()
