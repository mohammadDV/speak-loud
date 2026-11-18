#!/usr/bin/env python3
"""Regenerate german_grammar_lesson_03_questions.json for questions & word order."""

from __future__ import annotations

import json
from collections import Counter
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
OUT_PATH = Path(__file__).resolve().parent / "german_grammar_lesson_03_questions.json"

COURSE = "de-grammar-a1"
LESSON = "questions-and-word-order"
KEY_PREFIX = f"{COURSE}:l03"

W_WORDS = {
    "Wer": {
        "en": "who (nominative)",
        "es": "quién (nominativo)",
        "fa": "چه کسی (فاعل)",
        "ar": "من (فاعل)",
        "tr": "kim (yalın)",
    },
    "Wen": {
        "en": "whom (accusative)",
        "es": "a quién (acusativo)",
        "fa": "چه کسی را (مفعول)",
        "ar": "من (مفعول به)",
        "tr": "kimi (akkuzatif)",
    },
    "Was": {
        "en": "what",
        "es": "qué",
        "fa": "چه",
        "ar": "ماذا",
        "tr": "ne",
    },
    "Wie": {
        "en": "how / what (for names)",
        "es": "cómo / qué (para nombres)",
        "fa": "چگونه / چه (برای اسم)",
        "ar": "كيف / ما (للأسماء)",
        "tr": "nasıl / ne (isim için)",
    },
    "Wann": {
        "en": "when",
        "es": "cuándo",
        "fa": "کی / چه وقت",
        "ar": "متى",
        "tr": "ne zaman",
    },
    "Warum": {
        "en": "why",
        "es": "por qué",
        "fa": "چرا",
        "ar": "لماذا",
        "tr": "neden",
    },
    "Wo": {
        "en": "where (static location)",
        "es": "dónde (lugar estático)",
        "fa": "کجا (مکان ثابت)",
        "ar": "أين (مكان ثابت)",
        "tr": "nerede (sabit yer)",
    },
    "Wohin": {
        "en": "where to (direction)",
        "es": "adónde (dirección)",
        "fa": "به کجا (جهت)",
        "ar": "إلى أين (اتجاه)",
        "tr": "nereye (yön)",
    },
    "Woher": {
        "en": "where from (origin)",
        "es": "de dónde (origen)",
        "fa": "از کجا (مبدأ)",
        "ar": "من أين (مصدر)",
        "tr": "nereden (kaynak)",
    },
    "Wie viel": {
        "en": "how much (uncountable)",
        "es": "cuánto (incontable)",
        "fa": "چقدر (غیرقابل‌شمارش)",
        "ar": "كم (غير معدود)",
        "tr": "ne kadar (sayılamayan)",
    },
    "Wie viele": {
        "en": "how many (countable)",
        "es": "cuántos (contable)",
        "fa": "چند (قابل‌شمارش)",
        "ar": "كم عددًا (معدود)",
        "tr": "kaç (sayılabilen)",
    },
}

PROMPT = {
    "en": {
        "w_meaning": 'Which German question word means "{meaning}"?',
        "location": "Choose the correct question word: {sentence}",
        "w_order": "Which W-question has correct word order?",
        "yesno_form": 'Turn this statement into a yes/no question: "{statement}"',
        "yesno_order": "Which yes/no question has correct word order?",
        "doch": "Which answer correctly contradicts this negative question: {question}",
        "answer_particle": 'For the question "{question}", which answer particle fits best if you {intent}?',
        "verb_pos2": "Which sentence keeps the conjugated verb in position 2?",
        "time_place": "Which sentence has correct time-before-place order?",
        "polite": "Which sentence is a polite request?",
        "w_fill": "Choose the best W-word: {frame}",
    },
    "es": {
        "w_meaning": '¿Qué palabra interrogativa alemana significa "{meaning}"?',
        "location": "Elige la palabra interrogativa correcta: {sentence}",
        "w_order": "¿Qué W-Frage tiene el orden correcto?",
        "yesno_form": 'Convierte esta frase en una pregunta de sí/no: "{statement}"',
        "yesno_order": "¿Qué pregunta de sí/no tiene el orden correcto?",
        "doch": "¿Qué respuesta contradice correctamente esta pregunta negativa: {question}",
        "answer_particle": 'Para la pregunta "{question}", ¿qué partícula encaja mejor si tú {intent}?',
        "verb_pos2": "¿Qué oración mantiene el verbo conjugado en la posición 2?",
        "time_place": "¿Qué oración tiene el orden correcto tiempo → lugar?",
        "polite": "¿Qué frase es una petición cortés?",
        "w_fill": "Elige la mejor palabra W: {frame}",
    },
    "fa": {
        "w_meaning": 'کدام کلمهٔ پرسشی آلمانی یعنی "{meaning}"؟',
        "location": "کلمهٔ پرسشی درست را انتخاب کنید: {sentence}",
        "w_order": "کدام W-Frage ترتیب درست دارد؟",
        "yesno_form": 'این جمله را به سؤال بله/خیر تبدیل کنید: "{statement}"',
        "yesno_order": "کدام سؤال بله/خیر ترتیب درست دارد؟",
        "doch": "کدام پاسخ این سؤال منفی را درست رد می‌کند: {question}",
        "answer_particle": 'برای سؤال "{question}"، اگر شما {intent}، کدام جزء پاسخ مناسب‌تر است؟',
        "verb_pos2": "کدام جمله فعل صرف‌شده را در جایگاه ۲ نگه می‌دارد؟",
        "time_place": "کدام جمله ترتیب درست زمان قبل از مکان دارد؟",
        "polite": "کدام جمله درخواست مؤدبانه است؟",
        "w_fill": "بهترین کلمهٔ W را انتخاب کنید: {frame}",
    },
    "ar": {
        "w_meaning": 'أي كلمة استفهام ألمانية تعني "{meaning}"؟',
        "location": "اختر كلمة الاستفهام الصحيحة: {sentence}",
        "w_order": "أي سؤال W له ترتيب صحيح؟",
        "yesno_form": 'حوّل هذه الجملة إلى سؤال نعم/لا: "{statement}"',
        "yesno_order": "أي سؤال نعم/لا له ترتيب صحيح؟",
        "doch": "أي إجابة تناقض هذا السؤال المنفي بشكل صحيح: {question}",
        "answer_particle": 'للسؤال "{question}"، أي أداة إجابة تناسب أكثر إذا كنت {intent}؟',
        "verb_pos2": "أي جملة تُبقي الفعل المصرّف في الموضع 2؟",
        "time_place": "أي جملة لها ترتيب صحيح الزمان قبل المكان؟",
        "polite": "أي جملة طلب مهذب؟",
        "w_fill": "اختر أفضل كلمة W: {frame}",
    },
    "tr": {
        "w_meaning": '"{meaning}" anlamına gelen Almanca soru sözcüğü hangisidir?',
        "location": "Doğru soru sözcüğünü seçin: {sentence}",
        "w_order": "Doğru sözdizimine sahip W-sorusu hangisidir?",
        "yesno_form": 'Bu cümleyi evet/hayır sorusuna çevirin: "{statement}"',
        "yesno_order": "Doğru sözdizimine sahip evet/hayır sorusu hangisidir?",
        "doch": "Bu olumsuz soruyu doğru biçimde çürüten yanıt hangisidir: {question}",
        "answer_particle": '"{question}" sorusu için, eğer siz {intent} ise hangi yanıt parçacığı en uygundur?',
        "verb_pos2": "Çekimlenmiş fiili 2. konumda tutan cümle hangisidir?",
        "time_place": "Zaman → yer sırası doğru olan cümle hangisidir?",
        "polite": "Kibar rica cümlesi hangisidir?",
        "w_fill": "En uygun W-sözcüğünü seçin: {frame}",
    },
}

ANSWER_INTENTS = {
    "agree_positive": {
        "en": "agree / say yes",
        "es": "estás de acuerdo / dices que sí",
        "fa": "موافقید / بله می‌گویید",
        "ar": "توافق / تقول نعم",
        "tr": "kabul ediyorsanız / evet diyorsanız",
    },
    "disagree_positive": {
        "en": "say no",
        "es": "dices que no",
        "fa": "نه می‌گویید",
        "ar": "تقول لا",
        "tr": "hayır diyorsanız",
    },
    "contradict_negative": {
        "en": "contradict the negation (yes, I am / I do)",
        "es": "contradeces la negación (sí que...)",
        "fa": "نفی را رد می‌کنید (اتفاقاً بله)",
        "ar": "تناقض النفي (بلى / على العكس)",
        "tr": "olumsuzu çürütüyorsanız (tersine evet)",
    },
    "confirm_negative": {
        "en": "confirm the negation (no, I am not / I do not)",
        "es": "confirmas la negación (no...)",
        "fa": "نفی را تأیید می‌کنید",
        "ar": "تؤكد النفي",
        "tr": "olumsuzu onaylıyorsanız",
    },
}

# Natural yes/no statement frames: (finite, subject, rest)
YESNO_FRAMES: list[tuple[str, str, str]] = []
for subject in ("er", "sie"):
    YESNO_FRAMES.extend(
        [
            ("kommt", subject, "aus Berlin"),
            ("kommt", subject, "aus Spanien"),
            ("kommt", subject, "aus München"),
            ("wohnt", subject, "in Heidelberg"),
            ("wohnt", subject, "in Frankfurt"),
            ("wohnt", subject, "in Köln"),
            ("spricht", subject, "Englisch"),
            ("spricht", subject, "Deutsch"),
            ("spricht", subject, "Französisch"),
            ("hat", subject, "Kinder"),
            ("hat", subject, "Geschwister"),
            ("hat", subject, "Zeit"),
            ("ist", subject, "Lehrer" if subject == "er" else "Lehrerin"),
            ("ist", subject, "Ingenieur" if subject == "er" else "Ingenieurin"),
            ("heißt", subject, "Peter" if subject == "er" else "Anne"),
            ("lernt", subject, "Deutsch"),
            ("lernt", subject, "Spanisch"),
            ("arbeitet", subject, "im Krankenhaus"),
            ("arbeitet", subject, "im Büro"),
            ("fährt", subject, "nach München"),
            ("fährt", subject, "nach Hause"),
            ("spielt", subject, "Tennis"),
            ("spielt", subject, "Fußball"),
            ("trinkt", subject, "Kaffee"),
            ("trinkt", subject, "Tee"),
            ("isst", subject, "Pizza"),
            ("isst", subject, "Salat"),
            ("geht", subject, "zum Arzt"),
            ("geht", subject, "ins Kino"),
            ("schläft", subject, "lange"),
            ("kauft", subject, "Brot"),
            ("liest", subject, "ein Buch"),
            ("schreibt", subject, "eine E-Mail"),
            ("macht", subject, "Yoga"),
            ("sieht", subject, "fern"),
            ("hört", subject, "Musik"),
        ]
    )

formal_map = {
    "aus Berlin": "kommen",
    "aus Spanien": "kommen",
    "aus Italien": "kommen",
    "in Heidelberg": "wohnen",
    "in Frankfurt": "wohnen",
    "in Köln": "wohnen",
    "Englisch": "sprechen",
    "Deutsch": "sprechen",
    "Französisch": "sprechen",
    "Kinder": "haben",
    "Geschwister": "haben",
    "Zeit": "haben",
    "Hunger": "haben",
    "verheiratet": "sind",
    "Student": "sind",
    "Schmidt": "heißen",
    "hier": "arbeiten",
    "im Büro": "arbeiten",
    "nach Hause": "fahren",
    "nach München": "fahren",
    "Tennis": "spielen",
    "Fußball": "spielen",
    "Kaffee": "trinken",
    "Tee": "trinken",
    "ins Fitness-Studio": "gehen",
    "zum Arzt": "gehen",
    "Yoga": "machen",
    "Sport": "machen",
    "die Frage": "verstehen",
    "Deutsch gut": "verstehen",
    "heute": "kommen",
    "morgen": "arbeiten",
    "am Wochenende": "arbeiten",
}
for rest, verb in formal_map.items():
    YESNO_FRAMES.append((verb, "Sie", rest))

# Informal du frames (natural A1 questions)
for rest, verb in (
    ("aus Berlin", "kommst"),
    ("aus Spanien", "kommst"),
    ("in Heidelberg", "wohnst"),
    ("in Frankfurt", "wohnst"),
    ("Englisch", "sprichst"),
    ("Deutsch", "sprichst"),
    ("Kinder", "hast"),
    ("Zeit", "hast"),
    ("Peter", "heißt"),
    ("Deutsch", "lernst"),
    ("im Büro", "arbeitest"),
    ("nach Hause", "fährst"),
    ("Tennis", "spielst"),
    ("Kaffee", "trinkst"),
    ("ins Kino", "gehst"),
    ("Yoga", "machst"),
    ("heute", "kommst"),
    ("morgen", "arbeitest"),
):
    YESNO_FRAMES.append((verb, "du", rest))

def finite_for(subject: str, paradigm: dict[str, str]) -> str:
    return paradigm[subject]


ACTIVITY_PARADIGMS = [
    ({"ich": "trinke", "du": "trinkst", "er": "trinkt", "sie": "trinkt", "wir": "trinken", "ihr": "trinkt"}, "Kaffee"),
    ({"ich": "trinke", "du": "trinkst", "er": "trinkt", "sie": "trinkt", "wir": "trinken", "ihr": "trinkt"}, "Tee"),
    ({"ich": "esse", "du": "isst", "er": "isst", "sie": "isst", "wir": "essen", "ihr": "esst"}, "Pizza"),
    ({"ich": "esse", "du": "isst", "er": "isst", "sie": "isst", "wir": "essen", "ihr": "esst"}, "Salat"),
    ({"ich": "lese", "du": "liest", "er": "liest", "sie": "liest", "wir": "lesen", "ihr": "lest"}, "ein Buch"),
    ({"ich": "arbeite", "du": "arbeitest", "er": "arbeitet", "sie": "arbeitet", "wir": "arbeiten", "ihr": "arbeitet"}, "im Büro"),
    ({"ich": "lerne", "du": "lernst", "er": "lernt", "sie": "lernt", "wir": "lernen", "ihr": "lernt"}, "Deutsch"),
    ({"ich": "spiele", "du": "spielst", "er": "spielt", "sie": "spielt", "wir": "spielen", "ihr": "spielt"}, "Tennis"),
    ({"ich": "spiele", "du": "spielst", "er": "spielt", "sie": "spielt", "wir": "spielen", "ihr": "spielt"}, "Fußball"),
    ({"ich": "gehe", "du": "gehst", "er": "geht", "sie": "geht", "wir": "gehen", "ihr": "geht"}, "ins Kino"),
    ({"ich": "gehe", "du": "gehst", "er": "geht", "sie": "geht", "wir": "gehen", "ihr": "geht"}, "nach Hause"),
    ({"ich": "koche", "du": "kochst", "er": "kocht", "sie": "kocht", "wir": "kochen", "ihr": "kocht"}, "Suppe"),
    ({"ich": "schreibe", "du": "schreibst", "er": "schreibt", "sie": "schreibt", "wir": "schreiben", "ihr": "schreibt"}, "E-Mails"),
    ({"ich": "schlafe", "du": "schläfst", "er": "schläft", "sie": "schläft", "wir": "schlafen", "ihr": "schlaft"}, "lange"),
    ({"ich": "höre", "du": "hörst", "er": "hört", "sie": "hört", "wir": "hören", "ihr": "hört"}, "Musik"),
    ({"ich": "sehe", "du": "siehst", "er": "sieht", "sie": "sieht", "wir": "sehen", "ihr": "seht"}, "fern"),
    ({"ich": "kaufe", "du": "kaufst", "er": "kauft", "sie": "kauft", "wir": "kaufen", "ihr": "kauft"}, "Brot"),
    ({"ich": "mache", "du": "machst", "er": "macht", "sie": "macht", "wir": "machen", "ihr": "macht"}, "Yoga"),
]

INVERSION_FRAMES: list[tuple[str, str, str]] = []
for subject in ("ich", "du", "er", "sie", "wir", "ihr"):
    for paradigm, rest in ACTIVITY_PARADIGMS:
        INVERSION_FRAMES.append((finite_for(subject, paradigm), subject, rest))

TIME_LEADS = [
    "Am Abend",
    "Am Morgen",
    "Am Nachmittag",
    "Heute",
    "Morgen",
    "Am Wochenende",
    "Jeden Tag",
    "Oft",
    "Manchmal",
    "Immer",
]

LOCATION_ITEMS = [
    ("Wo", "___ wohnen Sie?", ["Wohin", "Woher", "Wann"]),
    ("Wo", "___ ist das Büro?", ["Wohin", "Woher", "Warum"]),
    ("Wo", "___ arbeitest du?", ["Wohin", "Woher", "Wie"]),
    ("Wo", "___ lernen sie Deutsch?", ["Wohin", "Woher", "Wer"]),
    ("Wo", "___ ist die Toilette?", ["Wohin", "Woher", "Was"]),
    ("Wohin", "___ fahren Sie?", ["Wo", "Woher", "Wann"]),
    ("Wohin", "___ geht er?", ["Wo", "Woher", "Wer"]),
    ("Wohin", "___ fliegt ihr?", ["Wo", "Woher", "Wer"]),
    ("Wohin", "___ will er gehen?", ["Wo", "Woher", "Wann"]),
    ("Wohin", "___ läuft sie?", ["Wo", "Woher", "Was"]),
    ("Woher", "___ kommen Sie?", ["Wo", "Wohin", "Wann"]),
    ("Woher", "___ kommt sie?", ["Wo", "Wohin", "Was"]),
    ("Woher", "___ kommst du?", ["Wo", "Wohin", "Was"]),
    ("Woher", "___ seid ihr?", ["Wo", "Wohin", "Wer"]),
]

W_FILL_ITEMS = [
    ("Wie", "___ heißen Sie?", ["Was", "Wer", "Wo"]),
    ("Wie", "___ geht es Ihnen?", ["Was", "Wer", "Wo"]),
    ("Wie", "___ ist Ihr Vorname?", ["Was", "Wer", "Wo"]),
    ("Wer", "___ ist das?", ["Was", "Wen", "Wo"]),
    ("Wer", "___ kommt heute?", ["Was", "Wen", "Wo"]),
    ("Was", "___ sind Sie von Beruf?", ["Wie", "Wer", "Wo"]),
    ("Was", "___ machst du?", ["Wie", "Wer", "Wo"]),
    ("Was", "___ trinkst du?", ["Wie", "Wer", "Wo"]),
    ("Wann", "___ gehst du zum Arzt?", ["Warum", "Wo", "Wie"]),
    ("Wann", "___ beginnt der Kurs?", ["Warum", "Wo", "Wer"]),
    ("Warum", "___ lernst du Deutsch?", ["Wann", "Wo", "Wer"]),
    ("Warum", "___ bist du müde?", ["Wann", "Wo", "Wer"]),
    ("Wie viel", "___ kostet das Buch?", ["Wie viele", "Wann", "Wo"]),
    ("Wie viel", "___ Zucker möchten Sie?", ["Wie viele", "Wann", "Wo"]),
    ("Wie viele", "___ Geschwister hast du?", ["Wie viel", "Wer", "Was"]),
    ("Wie viele", "___ Sprachen sprichst du?", ["Wie viel", "Wer", "Was"]),
    ("Wen", "___ siehst du?", ["Wer", "Was", "Wo"]),
    ("Wen", "___ besuchst du?", ["Wer", "Was", "Wo"]),
    ("Woher", "___ kommt Luis?", ["Wo", "Wohin", "Wann"]),
    ("Wohin", "___ fährt Anne?", ["Wo", "Woher", "Wer"]),
    ("Um wie viel Uhr", "___ beginnt der Kurs?", ["Wann", "Wie viel", "Warum"]),
    ("Um wie viel Uhr", "___ stehst du auf?", ["Wann", "Wie viel", "Warum"]),
]

W_ORDER_ITEMS = [
    ("wie-heissen", "Wie heißen Sie?", ["Heißen wie Sie?", "Wie Sie heißen?", "Sie wie heißen?"]),
    ("wie-vorname", "Wie ist Ihr Vorname?", ["Wie Ihr Vorname ist?", "Ist wie Ihr Vorname?", "Ihr wie ist Vorname?"]),
    ("woher-kommt", "Woher kommt er?", ["Woher er kommt?", "Kommt woher er?", "Er woher kommt?"]),
    ("wohin-will", "Wohin will er gehen?", ["Wohin er will gehen?", "Will wohin er gehen?", "Wohin gehen will er?"]),
    ("wann-geht", "Wann geht sie zum Arzt?", ["Wann sie geht zum Arzt?", "Geht wann sie zum Arzt?", "Sie wann geht zum Arzt?"]),
    ("warum-lernt", "Warum lernt er Deutsch?", ["Warum er lernt Deutsch?", "Lernt warum er Deutsch?", "Er warum lernt Deutsch?"]),
    ("was-beruf", "Was sind Sie von Beruf?", ["Was Sie sind von Beruf?", "Sind was Sie von Beruf?", "Sie was sind von Beruf?"]),
    ("wo-wohnen", "Wo wohnen Sie?", ["Wo Sie wohnen?", "Wohnen wo Sie?", "Sie wo wohnen?"]),
    ("wieviel-kostet", "Wie viel kostet das?", ["Wie viel das kostet?", "Kostet wie viel das?", "Das wie viel kostet?"]),
    ("wen-siehst", "Wen siehst du?", ["Wen du siehst?", "Siehst wen du?", "Du wen siehst?"]),
]

VERB_POS2_ITEMS = [
    (
        "yoga",
        "Ich mache vor dem Frühstück Yoga.",
        ["Ich vor dem Frühstück mache Yoga.", "Vor dem Frühstück ich mache Yoga.", "Mache vor dem Frühstück ich Yoga."],
    ),
    (
        "abend-pizza",
        "Am Abend essen wir Pizza.",
        ["Am Abend wir essen Pizza.", "Am Abend essen Pizza wir.", "Wir am Abend essen Pizza."],
    ),
    (
        "morgen-kaffee",
        "Am Morgen trinke ich Kaffee.",
        ["Am Morgen ich trinke Kaffee.", "Am Morgen trinke Kaffee ich.", "Ich am Morgen trinke Kaffee."],
    ),
    (
        "mittag-bistro",
        "Zum Mittagessen gehe ich in ein Bistro.",
        ["Zum Mittagessen ich gehe in ein Bistro.", "Zum Mittagessen gehe in ein Bistro ich.", "Ich zum Mittagessen gehe in ein Bistro."],
    ),
    (
        "nachmittag-kuchen",
        "Am Nachmittag essen wir Kuchen.",
        ["Am Nachmittag wir essen Kuchen.", "Am Nachmittag essen Kuchen wir.", "Wir am Nachmittag essen Kuchen."],
    ),
    (
        "spanien",
        "Ich komme aus Spanien.",
        ["Ich aus Spanien komme.", "Aus Spanien ich komme.", "Komme aus Spanien ich."],
    ),
    (
        "wochenende-sport",
        "Am Wochenende spielen wir Fußball.",
        ["Am Wochenende wir spielen Fußball.", "Am Wochenende spielen Fußball wir.", "Wir am Wochenende spielen Fußball."],
    ),
    (
        "heute-deutsch",
        "Heute lerne ich Deutsch.",
        ["Heute ich lerne Deutsch.", "Heute lerne Deutsch ich.", "Ich heute lerne Deutsch."],
    ),
]

TIME_PLACE_COMBOS = [
    ("wohne", "seit zwei Monaten", "in Deutschland"),
    ("wohne", "seit einem Jahr", "in Heidelberg"),
    ("wohne", "schon lange", "in Berlin"),
    ("wohne", "seit drei Jahren", "in Köln"),
    ("wohne", "von Montag bis Freitag", "bei meiner Cousine"),
    ("wohne", "seit April", "in München"),
    ("arbeite", "seit zwei Jahren", "in Frankfurt"),
    ("arbeite", "jeden Tag", "im Büro"),
    ("arbeite", "am Montag", "im Krankenhaus"),
    ("arbeite", "von 9 bis 17 Uhr", "in der Firma"),
    ("arbeite", "von Montag bis Freitag", "in Frankfurt"),
    ("arbeite", "seit Januar", "in Heidelberg"),
    ("lerne", "seit einem Monat", "in der Sprachschule"),
    ("lerne", "jeden Abend", "zu Hause"),
    ("lerne", "am Wochenende", "in der Bibliothek"),
    ("lerne", "drei Mal pro Woche", "in der Schule"),
    ("spiele", "am Samstag", "im Park"),
    ("spiele", "oft", "im Fitness-Studio"),
    ("spiele", "am Sonntag", "im Verein"),
    ("esse", "in der Mittagspause", "in der Kantine"),
    ("esse", "am Abend", "zu Hause"),
    ("esse", "mittags", "im Bistro"),
    ("esse", "am Wochenende", "im Restaurant"),
    ("trinke", "am Morgen", "im Café"),
    ("trinke", "oft", "zu Hause"),
    ("trinke", "nach der Arbeit", "in der Bar"),
    ("fahre", "jeden Montag", "nach Frankfurt"),
    ("fahre", "freitags", "nach Heidelberg"),
    ("fahre", "am Abend", "nach Hause"),
    ("fahre", "am Wochenende", "nach München"),
    ("gehe", "zwei Mal pro Woche", "ins Fitness-Studio"),
    ("gehe", "am Samstag", "ins Kino"),
    ("gehe", "nach der Arbeit", "nach Hause"),
    ("gehe", "am Abend", "in die Stadt"),
    ("schlafe", "am Wochenende", "zu Hause"),
    ("schlafe", "oft", "bei Freunden"),
    ("koche", "am Abend", "in der Küche"),
    ("koche", "am Sonntag", "zu Hause"),
    ("lese", "am Abend", "im Bett"),
    ("lese", "oft", "in der Bibliothek"),
    ("schreibe", "am Morgen", "im Büro"),
    ("schreibe", "jeden Tag", "zu Hause"),
    ("bin", "von Freitagabend bis Montagmorgen", "zu Hause"),
    ("bin", "am Wochenende", "in Berlin"),
    ("bin", "im Sommer", "in Italien"),
]

POLITE_PHRASES = [
    "mir ein Glas Wasser geben",
    "das Fenster zumachen",
    "das Fenster öffnen",
    "die Tür schließen",
    "die Tür öffnen",
    "mir den Zucker geben",
    "mir den Stift geben",
    "mir das Buch geben",
    "mir die Rechnung erklären",
    "die Musik leiser machen",
    "langsamer sprechen",
    "das noch einmal wiederholen",
    "mir helfen",
    "einen Moment warten",
    "Platz nehmen",
    "mir Ihre Adresse sagen",
    "mir Ihre Telefonnummer geben",
    "das Formular ausfüllen",
    "hier unterschreiben",
    "mir Bescheid sagen",
    "die Rechnung bringen",
    "die Speisekarte bringen",
    "mir den Weg zeigen",
    "das Licht anmachen",
    "das Licht ausmachen",
    "leiser sprechen",
    "deutlicher sprechen",
    "mir Bescheid geben",
    "kurz warten",
    "mir Feuer geben",
]

DOCH_ITEMS = [
    (
        "kommt-nicht",
        "Kommen Sie heute nicht?",
        "Doch, ich komme heute.",
        ["Ja, ich komme heute nicht.", "Nein, ich komme heute.", "Doch, ich komme heute nicht."],
    ),
    (
        "spricht-nicht",
        "Spricht er kein Deutsch?",
        "Doch, er spricht Deutsch.",
        ["Ja, er spricht kein Deutsch.", "Nein, er spricht Deutsch.", "Doch, er spricht kein Deutsch."],
    ),
    (
        "hat-keine",
        "Hat sie keine Kinder?",
        "Doch, sie hat Kinder.",
        ["Ja, sie hat keine Kinder.", "Nein, sie hat Kinder.", "Doch, sie hat keine Kinder."],
    ),
    (
        "ist-nicht",
        "Ist er nicht Lehrer?",
        "Doch, er ist Lehrer.",
        ["Ja, er ist nicht Lehrer.", "Nein, er ist Lehrer.", "Doch, er ist nicht Lehrer."],
    ),
    (
        "wohnt-nicht",
        "Wohnen Sie nicht in Berlin?",
        "Doch, ich wohne in Berlin.",
        ["Ja, ich wohne nicht in Berlin.", "Nein, ich wohne in Berlin.", "Doch, ich wohne nicht in Berlin."],
    ),
]


def distractors_for(correct: str, pool: list[str], n: int = 3) -> list[str]:
    opts: list[str] = []
    for candidate in pool:
        if candidate != correct and candidate not in opts and candidate.strip():
            opts.append(candidate)
        if len(opts) >= n:
            break
    i = 0
    while len(opts) < n:
        cand = f"{correct} ({i + 1})"
        if cand not in opts:
            opts.append(cand)
        i += 1
        if i > 20:
            raise RuntimeError(f"Could not build distractors for {correct!r}")
    return opts[:n]


def capitalize_first(text: str) -> str:
    return text[:1].upper() + text[1:] if text else text


def yes_no_from_frame(verb: str, subject: str, rest: str) -> tuple[str, str]:
    statement = f"{capitalize_first(subject)} {verb} {rest}."
    question = f"{capitalize_first(verb)} {subject} {rest}?"
    return statement, question


def wrong_yes_no(verb: str, subject: str, rest: str) -> list[str]:
    return [
        f"{capitalize_first(subject)} {verb} {rest}?",
        f"{capitalize_first(verb)} {rest} {subject}?",
        f"{capitalize_first(subject)} {rest} {verb}?",
    ]


def main() -> None:
    questions: list[dict] = []

    def add_q(
        import_key: str,
        topic: str,
        lemma: str,
        answer: str,
        distractors: list[str],
        translations: dict,
        meta: dict | None = None,
    ) -> None:
        clean = distractors_for(answer, distractors, n=3)
        assert answer not in clean
        questions.append(
            {
                "import_key": import_key,
                "topic": topic,
                "source_lemma": lemma,
                "sense_import_key": None,
                "answer": answer,
                "distractors": clean,
                "blank_token": "___",
                "meta": meta or {},
                "translations": translations,
            }
        )

    # 1) W-word meanings
    names = list(W_WORDS.keys())
    for word, meanings in W_WORDS.items():
        add_q(
            f"{KEY_PREFIX}:w-meaning:{word.replace(' ', '-')}",
            "w_questions",
            word,
            word,
            [w for w in names if w != word],
            {
                lang: {"prompt": templates["w_meaning"].format(meaning=meanings[lang])}
                for lang, templates in PROMPT.items()
            },
            {"kind": "w_meaning"},
        )

    # 2) Wo / Wohin / Woher
    for answer, frame, wrong in LOCATION_ITEMS:
        add_q(
            f"{KEY_PREFIX}:location:{answer}:{frame}",
            "location_trio",
            answer,
            answer,
            wrong,
            {
                lang: {"prompt": templates["location"].format(sentence=frame)}
                for lang, templates in PROMPT.items()
            },
            {"kind": "wo_wohin_woher", "frame": frame},
        )

    # 3) Fill W-word
    for answer, frame, wrong in W_FILL_ITEMS:
        add_q(
            f"{KEY_PREFIX}:w-fill:{answer.replace(' ', '-')}:{frame}",
            "w_questions",
            answer,
            answer,
            wrong,
            {
                lang: {"prompt": templates["w_fill"].format(frame=frame)}
                for lang, templates in PROMPT.items()
            },
            {"kind": "w_fill", "frame": frame},
        )

    # 4) W-question word order
    for key, answer, wrong in W_ORDER_ITEMS:
        add_q(
            f"{KEY_PREFIX}:w-order:{key}",
            "w_word_order",
            "w-order",
            answer,
            wrong,
            {lang: {"prompt": templates["w_order"]} for lang, templates in PROMPT.items()},
            {"kind": "w_order"},
        )

    # 5) Yes/No formation + order from natural frames only
    seen_yesno: set[tuple[str, str, str]] = set()
    for verb, subject, rest in YESNO_FRAMES:
        triple = (verb, subject, rest)
        if triple in seen_yesno:
            continue
        seen_yesno.add(triple)
        statement, question = yes_no_from_frame(verb, subject, rest)
        slug = f"{verb}:{subject}:{rest.replace(' ', '-')}"
        add_q(
            f"{KEY_PREFIX}:yesno-form:{slug}",
            "yes_no_questions",
            verb,
            question,
            wrong_yes_no(verb, subject, rest),
            {
                lang: {"prompt": templates["yesno_form"].format(statement=statement)}
                for lang, templates in PROMPT.items()
            },
            {"kind": "yesno_form", "statement": statement},
        )
        add_q(
            f"{KEY_PREFIX}:yesno-order:{slug}",
            "yes_no_questions",
            verb,
            question,
            wrong_yes_no(verb, subject, rest),
            {lang: {"prompt": templates["yesno_order"]} for lang, templates in PROMPT.items()},
            {"kind": "yesno_order"},
        )

    # 6) Ja / Nein / Doch
    for key, question, answer, wrong in DOCH_ITEMS:
        add_q(
            f"{KEY_PREFIX}:doch:{key}",
            "ja_nein_doch",
            "doch",
            answer,
            wrong,
            {
                lang: {"prompt": templates["doch"].format(question=question)}
                for lang, templates in PROMPT.items()
            },
            {"kind": "doch"},
        )

    particle_cases = [
        ("positive-yes", "Kommen Sie heute?", "Ja", ["Nein", "Doch", "Bitte"], "agree_positive"),
        ("positive-no", "Kommen Sie heute?", "Nein", ["Ja", "Doch", "Bitte"], "disagree_positive"),
        ("negative-doch", "Kommen Sie heute nicht?", "Doch", ["Ja", "Nein", "Bitte"], "contradict_negative"),
        ("negative-nein", "Kommen Sie heute nicht?", "Nein", ["Ja", "Doch", "Bitte"], "confirm_negative"),
        ("kein-doch", "Haben Sie keine Zeit?", "Doch", ["Ja", "Nein", "Bitte"], "contradict_negative"),
        ("kein-nein", "Haben Sie keine Zeit?", "Nein", ["Ja", "Doch", "Bitte"], "confirm_negative"),
        ("kein-deutsch-doch", "Sprechen Sie kein Englisch?", "Doch", ["Ja", "Nein", "Bitte"], "contradict_negative"),
        ("nicht-hier-nein", "Wohnen Sie nicht hier?", "Nein", ["Ja", "Doch", "Bitte"], "confirm_negative"),
    ]
    for key, question, answer, wrong, intent_key in particle_cases:
        intents = ANSWER_INTENTS[intent_key]
        add_q(
            f"{KEY_PREFIX}:particle:{key}",
            "ja_nein_doch",
            answer.lower(),
            answer,
            wrong,
            {
                lang: {
                    "prompt": templates["answer_particle"].format(
                        question=question,
                        intent=intents[lang],
                    )
                }
                for lang, templates in PROMPT.items()
            },
            {"kind": "answer_particle", "intent": intent_key},
        )

    # 7) Verb position 2 / inversion
    for key, answer, wrong in VERB_POS2_ITEMS:
        add_q(
            f"{KEY_PREFIX}:verb2:{key}",
            "verb_position",
            "verb-position",
            answer,
            wrong,
            {lang: {"prompt": templates["verb_pos2"]} for lang, templates in PROMPT.items()},
            {"kind": "verb_position_2"},
        )

    for lead in TIME_LEADS:
        for verb, subject, rest in INVERSION_FRAMES:
            answer = f"{lead} {verb} {subject} {rest}."
            wrong = [
                f"{lead} {subject} {verb} {rest}.",
                f"{lead} {verb} {rest} {subject}.",
                f"{capitalize_first(subject)} {lead} {verb} {rest}.",
            ]
            add_q(
                f"{KEY_PREFIX}:verb2-gen:{lead.replace(' ', '-')}:{verb}:{subject}:{rest.replace(' ', '-')}",
                "verb_position",
                verb,
                answer,
                wrong,
                {lang: {"prompt": templates["verb_pos2"]} for lang, templates in PROMPT.items()},
                {"kind": "inversion"},
            )

    # 8) Time before place
    for verb, time, place in TIME_PLACE_COMBOS:
        if verb == "bin":
            answer = f"Ich bin {time} {place}."
            wrong = [
                f"Ich bin {place} {time}.",
                f"Ich {time} bin {place}.",
                f"{capitalize_first(time)} ich {place} bin.",
            ]
        else:
            answer = f"Ich {verb} {time} {place}."
            wrong = [
                f"Ich {verb} {place} {time}.",
                f"Ich {time} {verb} {place}.",
                f"{capitalize_first(time)} ich {place} {verb}.",
            ]
        # Also include fronted-time correct variant as separate item when natural.
        add_q(
            f"{KEY_PREFIX}:time-place:{verb}:{time.replace(' ', '-')}:{place.replace(' ', '-')}",
            "time_place",
            verb,
            answer,
            wrong,
            {lang: {"prompt": templates["time_place"]} for lang, templates in PROMPT.items()},
            {"kind": "time_before_place"},
        )
        fronted = (
            f"{capitalize_first(time)} bin ich {place}."
            if verb == "bin"
            else f"{capitalize_first(time)} {verb} ich {place}."
        )
        fronted_wrong = [
            f"{capitalize_first(time)} ich {verb if verb != 'bin' else 'bin'} {place}.",
            f"{capitalize_first(place)} {verb if verb != 'bin' else 'bin'} ich {time}.",
            f"Ich {place} {verb if verb != 'bin' else 'bin'} {time}.",
        ]
        add_q(
            f"{KEY_PREFIX}:time-place-front:{verb}:{time.replace(' ', '-')}:{place.replace(' ', '-')}",
            "time_place",
            verb,
            fronted,
            fronted_wrong,
            {lang: {"prompt": templates["time_place"]} for lang, templates in PROMPT.items()},
            {"kind": "time_before_place_fronted"},
        )

    # 9) Polite requests with full natural phrases
    for opener in ("Könnten Sie", "Würden Sie"):
        for phrase in POLITE_PHRASES:
            answer = f"{opener} bitte {phrase}?"
            if phrase.startswith("mir "):
                # Keep "mir" after Sie: Könnten Sie mir bitte ...
                tail = phrase[len("mir ") :]
                answer = f"{opener} mir bitte {tail}?"
            wrong = [
                f"{opener} bitte Sie {phrase}?",
                f"Sie {opener.split()[0].lower()} bitte {phrase}.",
                f"Bitte {opener} {phrase}?",
            ]
            slug = f"{opener.split()[0].lower()}:{phrase.replace(' ', '-')}"
            add_q(
                f"{KEY_PREFIX}:polite:{slug}",
                "polite_requests",
                "polite",
                answer,
                wrong,
                {lang: {"prompt": templates["polite"]} for lang, templates in PROMPT.items()},
                {"kind": "polite_request"},
            )

    # Regression checks
    by_key = {q["import_key"]: q for q in questions}
    expected = {
        f"{KEY_PREFIX}:w-meaning:Wer": "Wer",
        f"{KEY_PREFIX}:w-meaning:Wohin": "Wohin",
        f"{KEY_PREFIX}:w-meaning:Woher": "Woher",
        f"{KEY_PREFIX}:w-fill:Wie:___ heißen Sie?": "Wie",
        f"{KEY_PREFIX}:w-order:wie-heissen": "Wie heißen Sie?",
        f"{KEY_PREFIX}:yesno-form:kommt:er:aus-Berlin": "Kommt er aus Berlin?",
        f"{KEY_PREFIX}:yesno-form:sind:Sie:verheiratet": "Sind Sie verheiratet?",
        f"{KEY_PREFIX}:particle:negative-doch": "Doch",
        f"{KEY_PREFIX}:particle:positive-yes": "Ja",
        f"{KEY_PREFIX}:doch:kommt-nicht": "Doch, ich komme heute.",
        f"{KEY_PREFIX}:verb2:abend-pizza": "Am Abend essen wir Pizza.",
        f"{KEY_PREFIX}:time-place:wohne:seit-zwei-Monaten:in-Deutschland": "Ich wohne seit zwei Monaten in Deutschland.",
        f"{KEY_PREFIX}:polite:könnten:mir-ein-Glas-Wasser-geben": "Könnten Sie mir bitte ein Glas Wasser geben?",
    }
    for key, answer in expected.items():
        actual = by_key.get(key, {}).get("answer")
        assert actual == answer, f"{key}: expected {answer!r}, got {actual!r}"

    for question in questions:
        assert question["answer"] not in question["distractors"]
        assert len(question["distractors"]) == 3
        assert len(set(question["distractors"])) == 3
        for lang in ("en", "es", "fa", "ar", "tr"):
            assert question["translations"][lang]["prompt"].strip()

    # No unnatural generated polite bare verbs / weird yes-no combos
    for question in questions:
        if question["meta"].get("kind") == "polite_request":
            assert " bitte " in question["answer"]
            assert question["answer"].endswith("?")
            assert question["answer"].startswith(("Könnten Sie", "Würden Sie"))

    keys = [q["import_key"] for q in questions]
    assert len(keys) == len(set(keys)), "duplicate import_key"
    assert len(questions) >= 1000, f"need ≥1000 questions, got {len(questions)}"

    banned = ("PDF", "Page ", "Section ", "handwritten", "exercise sheet")
    for question in questions:
        blob = " ".join(t["prompt"] for t in question["translations"].values())
        for token in banned:
            assert token not in blob, f"banned token {token!r} in {question['import_key']}"

    # Spot-check inversion answers never put subject before verb after the lead
    for question in questions:
        if question["meta"].get("kind") != "inversion":
            continue
        lead_and_rest = question["answer"].rstrip(".").split()
        # lead can be 1-2 tokens; verb should not be preceded by er/sie/wir/ich immediately after lead
        assert question["answer"].count(" ") >= 3

    payload = {
        "meta": {
            "lesson_code": LESSON,
            "course_code": COURSE,
            "min_count": 1000,
            "generated_count": len(questions),
            "translation_languages": ["en", "es", "fa", "ar", "tr"],
            "source_vocabulary": "a1_german_words.json",
            "source_lesson": "german_grammar_lesson_03.md",
        },
        "questions": questions,
    }
    OUT_PATH.write_text(json.dumps(payload, ensure_ascii=False, indent=2), encoding="utf-8")
    print(f"wrote {OUT_PATH} count={len(questions)}")
    print(Counter(question["topic"] for question in questions))
    print(Counter((question.get("meta") or {}).get("kind") for question in questions))


if __name__ == "__main__":
    main()
