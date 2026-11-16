#!/usr/bin/env python3
"""Fill spanish/persian/arabic/turkish (+ example_*) fields on flat CEFR JSON via Argos Translate."""

from __future__ import annotations

import argparse
import json
import re
import sys
import time
from pathlib import Path

from argostranslate import translate

ROOT = Path(__file__).resolve().parent
LEVELS = ("A1", "A2", "B1", "B2", "C1", "C2")

GLOSS_FIELDS = {
    "es": "spanish",
    "fa": "persian",
    "ar": "arabic",
    "tr": "turkish",
}
EXAMPLE_FIELDS = {
    "es": "example_es",
    "fa": "example_fa",
    "ar": "example_ar",
    "tr": "example_tr",
}


def log(msg: str) -> None:
    print(msg, flush=True)


def clean_translation(text: str) -> str:
    text = (text or "").strip()
    if not text:
        return text

    tokens = text.split()
    if len(tokens) >= 2 and len({t.lower() for t in tokens}) == 1:
        text = tokens[0]

    return re.sub(r"\s+", " ", text).strip()


def build_translators() -> dict[str, object]:
    installed = translate.get_installed_languages()
    by_code = {lang.code: lang for lang in installed}
    if "en" not in by_code:
        raise SystemExit("Argos English language package is not installed")

    source = by_code["en"]
    translators: dict[str, object] = {}
    for code in GLOSS_FIELDS:
        target = by_code.get(code)
        if target is None:
            raise SystemExit(f"Argos package en->{code} is not installed")
        translators[code] = source.get_translation(target)
        if translators[code] is None:
            raise SystemExit(f"Could not create Argos translator en->{code}")
    return translators


def translate_cached(
    cache: dict[tuple[str, str], str],
    translators: dict[str, object],
    text: str,
    target: str,
) -> str:
    key = (target, text)
    if key in cache:
        return cache[key]

    if not text.strip():
        cache[key] = ""
        return ""

    try:
        out = translators[target].translate(text)
    except Exception as exc:  # noqa: BLE001
        log(f"  warn: failed en->{target} for [{text[:60]}]: {exc}")
        out = text

    out = clean_translation(out) or text
    cache[key] = out
    return out


def reorder_word(word: dict) -> dict:
    preferred = [
        "german",
        "english",
        "spanish",
        "persian",
        "arabic",
        "turkish",
        "part_of_speech",
        "irregular_verb",
        "reflexive_verb",
        "topic",
        "topic_en",
        "example_de",
        "example_en",
        "example_es",
        "example_fa",
        "example_ar",
        "example_tr",
    ]
    ordered: dict = {}
    for key in preferred:
        if key in word:
            ordered[key] = word[key]
    for key, value in word.items():
        if key not in ordered:
            ordered[key] = value
    return ordered


def fill_level(
    level: str,
    cache: dict[tuple[str, str], str],
    translators: dict[str, object],
    force: bool = False,
) -> Path:
    level = level.upper()
    path = ROOT / f"{level.lower()}_german_words.flat.json"
    data = json.loads(path.read_text(encoding="utf-8"))
    words = data["words"]
    total = len(words)
    started = time.time()

    for i, word in enumerate(words, start=1):
        english = (word.get("english") or "").strip()
        example_en = (word.get("example_en") or "").strip()

        for code, field in GLOSS_FIELDS.items():
            current = str(word.get(field) or "").strip()
            # Treat English placeholders from failed runs as missing.
            needs = force or not current or current == english
            if needs:
                word[field] = translate_cached(cache, translators, english, code)

        for code, field in EXAMPLE_FIELDS.items():
            current = str(word.get(field) or "").strip()
            source = example_en or english
            needs = force or not current or current == source or current == example_en
            if needs:
                word[field] = translate_cached(cache, translators, source, code)

        words[i - 1] = reorder_word(word)

        if i % 50 == 0 or i == total:
            elapsed = time.time() - started
            rate = i / elapsed if elapsed else 0
            log(f"  {level}: {i}/{total} ({elapsed:.1f}s, {rate:.1f} w/s, cache={len(cache)})")

        # Checkpoint so a mid-level crash does not lose progress.
        if i % 200 == 0:
            data["words"] = words
            path.write_text(json.dumps(data, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
            log(f"  checkpoint {path.name} @ {i}")

    data["words"] = words
    path.write_text(json.dumps(data, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
    log(f"Wrote {path}")
    return path


def main() -> None:
    parser = argparse.ArgumentParser(description="Fill flat vocabulary with es/fa/ar/tr translations")
    parser.add_argument("levels", nargs="*", default=list(LEVELS), help="CEFR levels (default: all)")
    parser.add_argument("--force", action="store_true", help="Overwrite existing translation fields")
    args = parser.parse_args()

    log("Loading Argos translators…")
    translators = build_translators()
    log(f"Ready: {', '.join(sorted(translators))}")

    cache: dict[tuple[str, str], str] = {}
    for level in args.levels:
        log(f"Filling {level.upper()}…")
        fill_level(level.upper(), cache, translators, force=args.force)

    log(f"Done. Unique translations cached: {len(cache)}")


if __name__ == "__main__":
    main()
