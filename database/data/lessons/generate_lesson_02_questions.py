#!/usr/bin/env python3
"""Regenerate german_grammar_lesson_02_questions.json for modal verbs."""

from __future__ import annotations

import json
from collections import Counter
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
VOCAB_PATH = ROOT / "vocabulary" / "a1_german_words.json"
OUT_PATH = Path(__file__).resolve().parent / "german_grammar_lesson_02_questions.json"

COURSE = "de-grammar-a1"
LESSON = "modal-verbs"
KEY_PREFIX = f"{COURSE}:l02"

PRONOUNS = ["ich", "du", "er", "sie_sg", "es", "wir", "ihr", "sie_pl", "Sie"]

# Disambiguate sie (she) vs sie (they) in every UI language.
PRONOUN_DISPLAY = {
    "ich": {
        "en": "ich",
        "es": "ich",
        "fa": "ich",
        "ar": "ich",
        "tr": "ich",
    },
    "du": {
        "en": "du",
        "es": "du",
        "fa": "du",
        "ar": "du",
        "tr": "du",
    },
    "er": {
        "en": "er",
        "es": "er",
        "fa": "er",
        "ar": "er",
        "tr": "er",
    },
    "sie_sg": {
        "en": "sie (she)",
        "es": "sie (ella)",
        "fa": "sie (او مؤنث)",
        "ar": "sie (هي)",
        "tr": "sie (o / kadın)",
    },
    "es": {
        "en": "es",
        "es": "es",
        "fa": "es",
        "ar": "es",
        "tr": "es",
    },
    "wir": {
        "en": "wir",
        "es": "wir",
        "fa": "wir",
        "ar": "wir",
        "tr": "wir",
    },
    "ihr": {
        "en": "ihr",
        "es": "ihr",
        "fa": "ihr",
        "ar": "ihr",
        "tr": "ihr",
    },
    "sie_pl": {
        "en": "sie (they)",
        "es": "sie (ellos/ellas)",
        "fa": "sie (آن‌ها)",
        "ar": "sie (هم/هن)",
        "tr": "sie (onlar)",
    },
    "Sie": {
        "en": "Sie (formal you)",
        "es": "Sie (usted)",
        "fa": "Sie (رسمی)",
        "ar": "Sie (رسمي)",
        "tr": "Sie (resmi siz)",
    },
}

MODALS: dict[str, dict[str, str]] = {
    "können": {
        "ich": "kann",
        "du": "kannst",
        "er": "kann",
        "sie_sg": "kann",
        "es": "kann",
        "wir": "können",
        "ihr": "könnt",
        "sie_pl": "können",
        "Sie": "können",
    },
    "müssen": {
        "ich": "muss",
        "du": "musst",
        "er": "muss",
        "sie_sg": "muss",
        "es": "muss",
        "wir": "müssen",
        "ihr": "müsst",
        "sie_pl": "müssen",
        "Sie": "müssen",
    },
    "dürfen": {
        "ich": "darf",
        "du": "darfst",
        "er": "darf",
        "sie_sg": "darf",
        "es": "darf",
        "wir": "dürfen",
        "ihr": "dürft",
        "sie_pl": "dürfen",
        "Sie": "dürfen",
    },
    "wollen": {
        "ich": "will",
        "du": "willst",
        "er": "will",
        "sie_sg": "will",
        "es": "will",
        "wir": "wollen",
        "ihr": "wollt",
        "sie_pl": "wollen",
        "Sie": "wollen",
    },
    "sollen": {
        "ich": "soll",
        "du": "sollst",
        "er": "soll",
        "sie_sg": "soll",
        "es": "soll",
        "wir": "sollen",
        "ihr": "sollt",
        "sie_pl": "sollen",
        "Sie": "sollen",
    },
    "möchten": {
        "ich": "möchte",
        "du": "möchtest",
        "er": "möchte",
        "sie_sg": "möchte",
        "es": "möchte",
        "wir": "möchten",
        "ihr": "möchtet",
        "sie_pl": "möchten",
        "Sie": "möchten",
    },
}

# Plausible wrong forms learners often produce.
COMMON_WRONG_FORMS = {
    "können": ["kanne", "könntst", "kannten", "könne"],
    "müssen": ["musse", "müsstst", "müßt", "müssenst"],
    "dürfen": ["dürfe", "darfen", "dürfst", "darfenst"],
    "wollen": ["wolle", "willt", "wolltst", "wollenst"],
    "sollen": ["solle", "sollest", "solltst", "sollenst"],
    "möchten": ["möchtee", "möcht", "möchtenst", "möchteet"],
}

MODAL_MEANINGS = {
    "können": {
        "en": "can / to be able to",
        "es": "poder / ser capaz de",
        "fa": "توانستن",
        "ar": "يستطيع / يمكن",
        "tr": "ebilmek / yapabilmek",
    },
    "müssen": {
        "en": "must / have to (necessity)",
        "es": "tener que / ser necesario",
        "fa": "مجبور بودن / ضرورت",
        "ar": "يجب / عليه أن (ضرورة)",
        "tr": "zorunda olmak",
    },
    "dürfen": {
        "en": "may / to be allowed to",
        "es": "tener permiso / estar permitido",
        "fa": "اجازه داشتن",
        "ar": "يُسمح / يجوز",
        "tr": "izinli olmak",
    },
    "wollen": {
        "en": "want to (strong / direct wish)",
        "es": "querer (deseo fuerte / directo)",
        "fa": "خواستن (خواستهٔ قوی و مستقیم)",
        "ar": "يريد (رغبة قوية مباشرة)",
        "tr": "istemek (güçlü / doğrudan)",
    },
    "sollen": {
        "en": "should / be supposed to (advice or someone else's instruction)",
        "es": "deber por consejo o por lo que te han dicho",
        "fa": "باید به‌خاطر توصیه یا گفتهٔ دیگران",
        "ar": "ينبغي / المفروض (نصيحة أو أمر منقول)",
        "tr": "gerekmek / -meli (tavsiye veya aktarılan talimat)",
    },
    "möchten": {
        "en": "would like to (polite wish)",
        "es": "querría / desearía (cortés)",
        "fa": "مایلم (خواستهٔ مؤدبانه)",
        "ar": "أودّ / أرغب (مهذب)",
        "tr": "isterim (kibar istek)",
    },
}

PROMPT = {
    "en": {
        "conj": 'Conjugate the modal "{modal}" for {pronoun}: ___',
        "sentence": 'Choose the correct form of "{modal}": {pronoun_short} ___ {infinitive}.',
        "meaning": 'Which modal verb means "{meaning}"?',
        "usage_polite": 'In a restaurant, which sentence is more polite?',
        "usage_forbidden": 'Which pattern means "not allowed to / must not"?',
        "usage_optional": 'Which pattern means "do not have to" (optional)?',
        "usage_suggestion": 'Which sentence is a suggestion with "sollen"?',
        "usage_offer": 'Which sentence offers help with "sollen"?',
        "word_order": 'Which sentence has correct modal word order?',
        "infinitive_form": 'Complete with the infinitive of "{lemma}": {lead} {modal_form} wir lange ___.',
    },
    "es": {
        "conj": 'Conjuga el modal "{modal}" para {pronoun}: ___',
        "sentence": 'Elige la forma correcta de "{modal}": {pronoun_short} ___ {infinitive}.',
        "meaning": '¿Qué verbo modal significa "{meaning}"?',
        "usage_polite": 'En un restaurante, ¿qué frase es más educada?',
        "usage_forbidden": '¿Qué patrón significa "no está permitido / no se debe"?',
        "usage_optional": '¿Qué patrón significa "no es necesario" (opcional)?',
        "usage_suggestion": '¿Qué frase es una sugerencia con "sollen"?',
        "usage_offer": '¿Qué frase ofrece ayuda con "sollen"?',
        "word_order": '¿Qué oración tiene el orden correcto con un modal?',
        "infinitive_form": 'Completa con el infinitivo de "{lemma}": {lead} {modal_form} wir lange ___.',
    },
    "fa": {
        "conj": 'وجه کمکی "{modal}" را برای {pronoun} صرف کنید: ___',
        "sentence": 'شکل درست "{modal}" را انتخاب کنید: {pronoun_short} ___ {infinitive}.',
        "meaning": 'کدام فعل کمکی یعنی "{meaning}"؟',
        "usage_polite": 'در رستوران، کدام جمله مؤدبانه‌تر است؟',
        "usage_forbidden": 'کدام الگو یعنی «اجازه نیست / ممنوع است»؟',
        "usage_optional": 'کدام الگو یعنی «لازم نیست» (اختیاری)؟',
        "usage_suggestion": 'کدام جمله پیشنهاد با "sollen" است؟',
        "usage_offer": 'کدام جمله پیشنهاد کمک با "sollen" است؟',
        "word_order": 'کدام جمله ترتیب درست با فعل کمکی دارد؟',
        "infinitive_form": 'با مصدر "{lemma}" کامل کنید: {lead} {modal_form} wir lange ___.',
    },
    "ar": {
        "conj": 'صرّف الفعل المساعد "{modal}" مع {pronoun}: ___',
        "sentence": 'اختر الصيغة الصحيحة لـ "{modal}": {pronoun_short} ___ {infinitive}.',
        "meaning": 'أي فعل مساعد يعني "{meaning}"؟',
        "usage_polite": 'في المطعم، أي جملة أكثر تهذيبًا؟',
        "usage_forbidden": 'أي نمط يعني "غير مسموح / يُمنع"؟',
        "usage_optional": 'أي نمط يعني "ليس ضروريًا" (اختياري)؟',
        "usage_suggestion": 'أي جملة اقتراح باستخدام "sollen"؟',
        "usage_offer": 'أي جملة تعرض المساعدة باستخدام "sollen"؟',
        "word_order": 'أي جملة لها ترتيب صحيح مع الفعل المساعد؟',
        "infinitive_form": 'أكمل بمصدر "{lemma}": {lead} {modal_form} wir lange ___.',
    },
    "tr": {
        "conj": '"{modal}" modal fiilini {pronoun} için çekimleyin: ___',
        "sentence": '"{modal}" için doğru biçimi seçin: {pronoun_short} ___ {infinitive}.',
        "meaning": '"{meaning}" anlamına gelen modal fiil hangisidir?',
        "usage_polite": 'Restoranda hangi cümle daha kibardır?',
        "usage_forbidden": '"izin yok / yasak" anlamındaki kalıp hangisidir?',
        "usage_optional": '"gerek yok" (isteğe bağlı) anlamındaki kalıp hangisidir?',
        "usage_suggestion": '"sollen" ile öneri cümlesi hangisidir?',
        "usage_offer": '"sollen" ile yardım teklifi hangisidir?',
        "word_order": 'Modal ile doğru sözdizimine sahip cümle hangisidir?',
        "infinitive_form": '"{lemma}" mastarıyla tamamlayın: {lead} {modal_form} wir lange ___.',
    },
}

# Prefer clear everyday A1 action verbs for sentence frames.
PREFERRED_INFINITIVES = [
    "schlafen",
    "arbeiten",
    "essen",
    "trinken",
    "gehen",
    "kommen",
    "machen",
    "lernen",
    "sprechen",
    "lesen",
    "schreiben",
    "fahren",
    "sehen",
    "hören",
    "kaufen",
    "spielen",
    "helfen",
    "bleiben",
    "nehmen",
    "geben",
    "finden",
    "wohnen",
    "kochen",
    "schwimmen",
    "parken",
    "rauchen",
    "besuchen",
    "öffnen",
    "warten",
    "fragen",
    "antworten",
    "verstehen",
    "studieren",
    "reisen",
    "fliegen",
    "aufstehen",
    "einkaufen",
    "fernsehen",
    "mitnehmen",
    "anrufen",
    "aufräumen",
    "waschen",
    "tragen",
    "backen",
    "duschen",
    "frühstücken",
    "bestellen",
    "bezahlen",
    "verkaufen",
    "kochen",
]

SKIP_INFINITIVES = {
    "zuhaben",
    "nummerieren",
    "inhalieren",
    "kreisen",
    "hochfahren",
    "schälen",
    "schütten",
    "shoppen",
    "mischen",
    "verabreden",
    "vereinbaren",
    "wiegen",
}


def pronoun_short(pronoun: str) -> str:
    if pronoun == "sie_sg":
        return "sie"
    if pronoun == "sie_pl":
        return "sie"
    return pronoun


def distractors_for(correct: str, pool: list[str], n: int = 3) -> list[str]:
    opts: list[str] = []
    for candidate in pool:
        if candidate != correct and candidate not in opts and candidate.strip():
            opts.append(candidate)
        if len(opts) >= n:
            break
    i = 0
    suffixes = ["e", "t", "st", "en", "est"]
    while len(opts) < n:
        cand = f"{correct}{suffixes[i % len(suffixes)]}"
        if cand != correct and cand not in opts:
            opts.append(cand)
        i += 1
        if i > 20:
            raise RuntimeError(f"Could not build distractors for {correct!r}")
    return opts[:n]


def all_modal_forms() -> list[str]:
    forms: list[str] = []
    seen: set[str] = set()
    for paradigm in MODALS.values():
        for form in paradigm.values():
            if form not in seen:
                seen.add(form)
                forms.append(form)
    return forms


def conjugated_lookalikes(lemma: str) -> list[str]:
    """Wrong non-infinitive forms used as distractors for infinitive-at-end items."""
    stem = lemma[:-2] if lemma.endswith("en") else lemma[:-1]
    return [
        f"{stem}t",
        f"{stem}st",
        f"{stem}e",
        f"{stem}en wir",
        f"zu {lemma}",
    ]


def main() -> None:
    vocab = json.loads(VOCAB_PATH.read_text(encoding="utf-8"))
    verbs_from_vocab: list[str] = []
    seen: set[str] = set()
    for lexeme in vocab["lexemes"]:
        lemma = lexeme["lemma"]
        if " " in lemma or "-" in lemma or lemma in MODALS or lemma in SKIP_INFINITIVES:
            continue
        for sense in lexeme.get("senses", []):
            if sense.get("part_of_speech") != "verb":
                continue
            if not lemma.endswith(("en", "eln", "ern")):
                continue
            if lemma not in seen:
                seen.add(lemma)
                verbs_from_vocab.append(lemma)
            break

    preferred = [verb for verb in PREFERRED_INFINITIVES if verb not in MODALS]
    extras = [verb for verb in verbs_from_vocab if verb not in preferred and verb not in SKIP_INFINITIVES]
    infinitives = list(dict.fromkeys(preferred + extras))[:70]
    form_pool = all_modal_forms()
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
        clean_distractors = distractors_for(answer, distractors, n=3)
        assert answer not in clean_distractors
        assert len(clean_distractors) == 3
        questions.append(
            {
                "import_key": import_key,
                "topic": topic,
                "source_lemma": lemma,
                "sense_import_key": None,
                "answer": answer,
                "distractors": clean_distractors,
                "blank_token": "___",
                "meta": meta or {},
                "translations": translations,
            }
        )

    # 1) Pure modal conjugation
    for modal, paradigm in MODALS.items():
        forms = list(dict.fromkeys(paradigm.values()))
        for pronoun in PRONOUNS:
            answer = paradigm[pronoun]
            distractors = distractors_for(
                answer,
                [paradigm[other] for other in PRONOUNS if other != pronoun]
                + COMMON_WRONG_FORMS[modal]
                + forms
                + [f for f in form_pool if f != answer],
            )
            translations = {
                lang: {
                    "prompt": templates["conj"].format(
                        modal=modal,
                        pronoun=PRONOUN_DISPLAY[pronoun][lang],
                    )
                }
                for lang, templates in PROMPT.items()
            }
            add_q(
                f"{KEY_PREFIX}:conj:{modal}:{pronoun}",
                "modal_conjugation",
                modal,
                answer,
                distractors,
                translations,
                {"pronoun": pronoun, "kind": "conjugation"},
            )

    # 2) Modal + infinitive sentence frames
    focus_pronouns = ["ich", "du", "er", "wir", "ihr"]
    for infinitive in infinitives:
        for modal, paradigm in MODALS.items():
            for pronoun in focus_pronouns:
                answer = paradigm[pronoun]
                distractors = distractors_for(
                    answer,
                    [paradigm[other] for other in PRONOUNS if other != pronoun]
                    + COMMON_WRONG_FORMS[modal]
                    + list(dict.fromkeys(paradigm.values()))
                    + form_pool,
                )
                translations = {
                    lang: {
                        "prompt": templates["sentence"].format(
                            modal=modal,
                            pronoun_short=pronoun_short(pronoun),
                            infinitive=infinitive,
                        )
                    }
                    for lang, templates in PROMPT.items()
                }
                add_q(
                    f"{KEY_PREFIX}:sent:{modal}:{pronoun}:{infinitive}",
                    "modal_sentence",
                    modal,
                    answer,
                    distractors,
                    translations,
                    {
                        "pronoun": pronoun,
                        "infinitive": infinitive,
                        "kind": "modal_plus_infinitive",
                    },
                )

    # 3) Meaning identification
    modal_names = list(MODALS.keys())
    for modal, meanings in MODAL_MEANINGS.items():
        distractors = distractors_for(modal, [name for name in modal_names if name != modal])
        translations = {
            lang: {"prompt": templates["meaning"].format(meaning=meanings[lang])}
            for lang, templates in PROMPT.items()
        }
        add_q(
            f"{KEY_PREFIX}:meaning:{modal}",
            "modal_meaning",
            modal,
            modal,
            distractors,
            translations,
            {"kind": "meaning"},
        )

    # 4) Usage items with unique correct answers
    add_q(
        f"{KEY_PREFIX}:usage:polite-coffee",
        "modal_usage",
        "möchten",
        "Ich möchte einen Kaffee.",
        [
            "Ich will einen Kaffee.",
            "Ich kann einen Kaffee.",
            "Ich muss einen Kaffee.",
        ],
        {lang: {"prompt": templates["usage_polite"]} for lang, templates in PROMPT.items()},
        {"kind": "polite_wish"},
    )
    add_q(
        f"{KEY_PREFIX}:usage:forbidden",
        "modal_usage",
        "dürfen",
        "nicht dürfen",
        ["nicht müssen", "nicht können", "nicht wollen"],
        {lang: {"prompt": templates["usage_forbidden"]} for lang, templates in PROMPT.items()},
        {"kind": "prohibition"},
    )
    add_q(
        f"{KEY_PREFIX}:usage:optional",
        "modal_usage",
        "müssen",
        "nicht müssen",
        ["nicht dürfen", "nicht können", "nicht sollen"],
        {lang: {"prompt": templates["usage_optional"]} for lang, templates in PROMPT.items()},
        {"kind": "no_necessity"},
    )
    add_q(
        f"{KEY_PREFIX}:usage:suggestion-kino",
        "modal_usage",
        "sollen",
        "Sollen wir morgen ins Kino gehen?",
        [
            "Können wir morgen ins Kino gehen?",
            "Müssen wir morgen ins Kino gehen?",
            "Wollen wir morgen ins Kino gehen müssen?",
        ],
        {lang: {"prompt": templates["usage_suggestion"]} for lang, templates in PROMPT.items()},
        {"kind": "suggestion"},
    )
    add_q(
        f"{KEY_PREFIX}:usage:offer-coffee",
        "modal_usage",
        "sollen",
        "Soll ich dir einen Kaffee machen?",
        [
            "Kann ich dir einen Kaffee machen?",
            "Muss ich dir einen Kaffee machen?",
            "Will ich dir einen Kaffee machen?",
        ],
        {lang: {"prompt": templates["usage_offer"]} for lang, templates in PROMPT.items()},
        {"kind": "offer_help"},
    )

    # 5) Word order: modal in position 2, infinitive at end
    word_order_items = [
        (
            "sonntag-schlafen",
            "Am Sonntag können wir lange schlafen.",
            [
                "Am Sonntag wir können lange schlafen.",
                "Am Sonntag können schlafen wir lange.",
                "Am Sonntag wir lange schlafen können.",
            ],
        ),
        (
            "muss-arbeiten",
            "Ich muss nicht arbeiten.",
            [
                "Ich nicht muss arbeiten.",
                "Ich muss arbeiten nicht.",
                "Ich nicht arbeiten muss.",
            ],
        ),
        (
            "will-essen",
            "Meine Tochter will viel Eis essen.",
            [
                "Meine Tochter viel Eis will essen.",
                "Meine Tochter will essen viel Eis.",
                "Meine Tochter essen will viel Eis.",
            ],
        ),
        (
            "moechte-sehen",
            "Mein Mann möchte Fußball sehen.",
            [
                "Mein Mann Fußball möchte sehen.",
                "Mein Mann möchte sehen Fußball.",
                "Mein Mann sehen möchte Fußball.",
            ],
        ),
        (
            "duerfen-rauchen",
            "Hier dürfen Sie nicht rauchen.",
            [
                "Hier Sie dürfen nicht rauchen.",
                "Hier dürfen rauchen Sie nicht.",
                "Hier Sie nicht rauchen dürfen.",
            ],
        ),
        (
            "moechte-mitnehmen",
            "Kann ich meinen Hund mitnehmen?",
            [
                "Kann mitnehmen ich meinen Hund?",
                "Ich kann meinen Hund mitnehmen kann?",
                "Kann ich mitnehmen meinen Hund?",
            ],
        ),
    ]
    for key, answer, wrong in word_order_items:
        add_q(
            f"{KEY_PREFIX}:order:{key}",
            "modal_word_order",
            "word-order",
            answer,
            wrong,
            {lang: {"prompt": templates["word_order"]} for lang, templates in PROMPT.items()},
            {"kind": "word_order"},
        )

    # 6) Infinitive form at the end — lemma is stated so the answer is unique
    for lemma in infinitives[:60]:
        for modal_form, lead in (("können", "Am Sonntag"), ("müssen", "Heute"), ("wollen", "Morgen")):
            answer = lemma
            distractors = distractors_for(
                answer,
                conjugated_lookalikes(lemma) + [form for form in form_pool if form != answer],
            )
            translations = {
                lang: {
                    "prompt": templates["infinitive_form"].format(
                        lemma=lemma,
                        lead=lead,
                        modal_form=modal_form,
                    )
                }
                for lang, templates in PROMPT.items()
            }
            add_q(
                f"{KEY_PREFIX}:inf-end:{modal_form}:{lead.replace(' ', '-').lower()}:{lemma}",
                "modal_word_order",
                lemma,
                answer,
                distractors,
                translations,
                {"kind": "infinitive_at_end", "modal_form": modal_form, "lemma": lemma},
            )

    # Regression checks
    by_key = {q["import_key"]: q for q in questions}
    expected_answers = {
        f"{KEY_PREFIX}:conj:können:ich": "kann",
        f"{KEY_PREFIX}:conj:können:du": "kannst",
        f"{KEY_PREFIX}:conj:können:sie_sg": "kann",
        f"{KEY_PREFIX}:conj:können:sie_pl": "können",
        f"{KEY_PREFIX}:conj:müssen:er": "muss",
        f"{KEY_PREFIX}:conj:dürfen:wir": "dürfen",
        f"{KEY_PREFIX}:conj:wollen:ihr": "wollt",
        f"{KEY_PREFIX}:conj:sollen:Sie": "sollen",
        f"{KEY_PREFIX}:conj:möchten:ich": "möchte",
        f"{KEY_PREFIX}:conj:möchten:du": "möchtest",
        f"{KEY_PREFIX}:usage:forbidden": "nicht dürfen",
        f"{KEY_PREFIX}:usage:optional": "nicht müssen",
        f"{KEY_PREFIX}:usage:polite-coffee": "Ich möchte einen Kaffee.",
        f"{KEY_PREFIX}:usage:suggestion-kino": "Sollen wir morgen ins Kino gehen?",
        f"{KEY_PREFIX}:usage:offer-coffee": "Soll ich dir einen Kaffee machen?",
        f"{KEY_PREFIX}:order:sonntag-schlafen": "Am Sonntag können wir lange schlafen.",
        f"{KEY_PREFIX}:inf-end:können:am-sonntag:schlafen": "schlafen",
    }
    for key, answer in expected_answers.items():
        actual = by_key.get(key, {}).get("answer")
        assert actual == answer, f"{key}: expected {answer!r}, got {actual!r}"

    for modal in MODALS:
        assert (
            by_key[f"{KEY_PREFIX}:conj:{modal}:ich"]["answer"]
            == by_key[f"{KEY_PREFIX}:conj:{modal}:er"]["answer"]
        ), f"{modal}: ich/er forms must match"
        sie_sg_prompt = by_key[f"{KEY_PREFIX}:conj:{modal}:sie_sg"]["translations"]["en"]["prompt"]
        sie_pl_prompt = by_key[f"{KEY_PREFIX}:conj:{modal}:sie_pl"]["translations"]["en"]["prompt"]
        assert sie_sg_prompt != sie_pl_prompt, f"{modal}: sie prompts must differ"
        assert "she" in sie_sg_prompt and "they" in sie_pl_prompt

    # Every infinitive-end prompt must uniquely determine its answer.
    inf_prompts: dict[str, set[str]] = {}
    for question in questions:
        if question["meta"].get("kind") != "infinitive_at_end":
            continue
        prompt = question["translations"]["en"]["prompt"]
        inf_prompts.setdefault(prompt, set()).add(question["answer"])
    for prompt, answers in inf_prompts.items():
        assert len(answers) == 1, f"ambiguous infinitive prompt {prompt!r} -> {answers!r}"

    for question in questions:
        assert question["answer"] not in question["distractors"]
        assert len(question["distractors"]) == 3
        assert len(set(question["distractors"])) == 3
        for lang in ("en", "es", "fa", "ar", "tr"):
            assert question["translations"][lang]["prompt"].strip()

    keys = [question["import_key"] for question in questions]
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
            "source_lesson": "german_grammar_lesson_02.md",
        },
        "questions": questions,
    }
    OUT_PATH.write_text(json.dumps(payload, ensure_ascii=False, indent=2), encoding="utf-8")
    print(f"wrote {OUT_PATH} count={len(questions)}")
    print(Counter(question["topic"] for question in questions))


if __name__ == "__main__":
    main()
