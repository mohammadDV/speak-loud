let cachedVoices = [];

function normalizeLang(lang) {
    return String(lang || '')
        .trim()
        .replace('_', '-');
}

function languagePrefix(lang) {
    return normalizeLang(lang).toLowerCase().split('-')[0];
}

function refreshVoices() {
    if (! window.speechSynthesis || typeof window.speechSynthesis.getVoices !== 'function') {
        cachedVoices = [];

        return cachedVoices;
    }

    const voices = window.speechSynthesis.getVoices();
    if (voices.length) {
        cachedVoices = voices;
    }

    return cachedVoices;
}

function waitForVoices() {
    const existing = refreshVoices();
    if (existing.length) {
        return Promise.resolve(existing);
    }

    return new Promise((resolve) => {
        let settled = false;

        const finish = () => {
            if (settled) {
                return;
            }

            settled = true;
            window.speechSynthesis?.removeEventListener?.('voiceschanged', onChange);
            resolve(refreshVoices());
        };

        const onChange = () => {
            if (refreshVoices().length) {
                finish();
            }
        };

        window.speechSynthesis?.addEventListener?.('voiceschanged', onChange);
        // Chrome often needs a second getVoices() after a user gesture.
        refreshVoices();
        setTimeout(finish, 400);
    });
}

function scoreVoice(voice, requestedLang) {
    const requested = normalizeLang(requestedLang).toLowerCase();
    const prefix = languagePrefix(requested);
    const voiceLang = normalizeLang(voice.lang).toLowerCase();
    const name = String(voice.name || '').toLowerCase();

    let score = 0;

    if (voiceLang === requested) {
        score += 100;
    } else if (voiceLang.startsWith(`${prefix}-`) || voiceLang === prefix) {
        score += 70;
    } else if (voiceLang.startsWith(prefix)) {
        score += 40;
    } else {
        return -1;
    }

    // Prefer named German/local voices when several match.
    if (prefix === 'de' && (name.includes('german') || name.includes('deutsch'))) {
        score += 15;
    }

    if (voice.localService) {
        score += 5;
    }

    return score;
}

function pickVoice(voices, lang) {
    let best = null;
    let bestScore = -1;

    for (const voice of voices) {
        const score = scoreVoice(voice, lang);
        if (score > bestScore) {
            best = voice;
            bestScore = score;
        }
    }

    return best;
}

function speakNow(text, lang, voices) {
    window.speechSynthesis.cancel();

    const utterance = new SpeechSynthesisUtterance(text);
    const locale = normalizeLang(lang) || 'en-US';
    utterance.lang = locale;
    utterance.rate = 0.95;

    const voice = pickVoice(voices, locale);
    if (voice) {
        utterance.voice = voice;
        // Keep utterance.lang aligned with the chosen voice (Safari is picky).
        utterance.lang = normalizeLang(voice.lang) || locale;
    }

    // Chrome sometimes drops the first utterance after cancel(); microtask helps.
    setTimeout(() => {
        window.speechSynthesis.speak(utterance);
    }, 0);
}

export function speakWord({ text, lang } = {}) {
    const utteranceText = String(text || '').trim();
    if (! utteranceText) {
        return;
    }

    if (typeof window === 'undefined' || ! window.speechSynthesis || typeof SpeechSynthesisUtterance === 'undefined') {
        return;
    }

    waitForVoices().then((voices) => {
        speakNow(utteranceText, lang, voices);
    });
}

window.SpeakLoudSpeak = speakWord;

if (typeof window !== 'undefined' && window.speechSynthesis) {
    refreshVoices();
    window.speechSynthesis.addEventListener?.('voiceschanged', refreshVoices);
}
