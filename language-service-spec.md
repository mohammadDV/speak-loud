# Multilingual Dictionary & Learning Platform

## Product and Technical Specification (`spec.md`)

**Document status:** Draft for technical planning\
**Version:** 1.0\
**Target backend:** Laravel\
**Primary database:** MySQL\
**Initial content focus:** German vocabulary\
**Long-term scope:** Any supported source language and target language

------------------------------------------------------------------------

## 1. Purpose

Build a multilingual dictionary and language-learning platform. A user
should be able to learn any supported target language through any
supported interface or explanation language.

Examples:

-   A Persian-speaking user learns German.
-   A German-speaking user learns Persian.
-   An Iranian Persian-speaking user learns French.
-   A Turkish-speaking user learns Spanish.

The platform must not be designed as a collection of separate
dictionaries for each language pair. It must use a shared,
language-independent dictionary model.

The central design rule is:

> Store a vocabulary item in its own language, store each distinct
> meaning separately, and attach translations, grammar, examples,
> collocations, and learning-level information to the appropriate
> meaning or linguistic record.

This avoids incorrect translations when a word has multiple meanings and
makes it possible to add languages without redesigning the database.

## 2. Product vision and goals

### 2.1 Goals

1.  Support a configurable list of languages.
2.  Allow users to select their known language(s) and target
    language(s).
3.  Provide dictionary search in any supported language.
4.  Support multiple meanings for a single written word.
5.  Classify meanings by part of speech.
6.  Support more than one part of speech for the same spelling when
    linguistically appropriate.
7.  Store translations per meaning and target language.
8.  Store examples and their translations.
9.  Store common collocations, fixed expressions, and idioms.
10. Store CEFR learning levels (A1--C2) without assuming that every
    meaning has the same level.
11. Classify lexemes into one or more shared vocabulary categories
    (topics) such as Family, Colors, or Time and numbers.
12. Provide German-specific grammatical information, especially `der`,
    `die`, and `das`, plus plural forms.
13. Provide an admin workflow for creating, importing, reviewing,
    publishing, and revising content.
14. Support AI-assisted content creation with validation and human
    approval.
15. Support user vocabulary lists, practice, and progress tracking as
    later learning features.

### 2.2 Non-goals for the first release

The first release does not need:

-   Speech recognition or pronunciation scoring.
-   Live AI conversation tutors.
-   Automatic speech synthesis for every language.
-   A full linguistic morphology engine for every language.
-   Microservices.
-   Real-time collaborative editing.
-   A guarantee that every possible collocation in a language is
    covered.

These may be added after the core dictionary is reliable.

## 3. Product principles and terminology

### 3.1 Language

A language record identifies a language or a supported regional variant,
for example German (`de`), Persian (`fa`), Arabic (`ar`), or Brazilian
Portuguese (`pt-BR`).

Use BCP 47-compatible language tags where regional or script variants
are needed. Store the canonical tag consistently. Do not assume every
language has a two-letter ISO 639-1 code.

### 3.2 Lexeme

A **lexeme** is a dictionary vocabulary item in one language, usually
represented by its lemma or base form.

Examples:

-   German: `gehen`
-   German: `Bank`
-   English: `bank`

A lexeme belongs to exactly one language. Different languages have
separate lexeme records, even if their spelling is identical.

### 3.3 Lexeme form

A **lexeme form** is an inflected or variant form that points to a
lexeme.

Examples:

-   `gehen` → `geht`, `ging`, `gegangen`
-   `Haus` → `Häuser`

A user searching for an inflected form should be able to reach its base
lexeme.

### 3.4 Sense

A **sense** is one distinct meaning or usage of a lexeme.

For example, German `Bank` has at least these senses:

-   Financial institution.
-   Bench or seating place.

Translations and examples must be attached to the correct sense, not
merely to the spelling.

A sense may have a part of speech, domain, register, usage labels, and
one or more learning-level assessments.

### 3.5 Part of speech

A grammatical category such as noun, verb, adjective, adverb,
preposition, pronoun, conjunction, determiner, particle, numeral, or
interjection.

Part of speech is associated with the sense or usage, not assumed to be
a single property of the spelling. This supports words that function as
different parts of speech.

### 3.6 Translation

A translation is an equivalent or explanatory rendering of a particular
sense in a target language. One sense may have multiple translations or
translation variants.

### 3.7 CEFR level

A learning-level estimate such as A1, A2, B1, B2, C1, or C2. A level is
an editorial or sourced assessment, not an inherent universal property
of a spelling. Different senses and usages may have different levels.

### 3.8 Collocation

A common, natural combination of words, such as German
`eine Entscheidung treffen`. Collocations are separate learning content
and may have translations, examples, patterns, and level assessments.

### 3.9 Category

A **category** is a controlled vocabulary topic used for browsing and
filtering, for example Family, Time and numbers, or Colors.

Categories are assigned to lexemes. A lexeme may belong to multiple
categories. Categories are a shared, flat catalog (not hierarchical and
not language-specific).

Categories are distinct from:

-   **Part of speech** --- grammatical classification of a sense.
-   **Sense `domain`** --- optional free-text editorial or linguistic
    note on a sense (kept independently; not migrated into categories).

## 4. Users and permissions

### 4.1 Roles

-   **Guest:** Search and view public, published dictionary content
    subject to product access rules.
-   **Registered learner:** Manage language preferences, save
    vocabulary, study, and track progress.
-   **Editor:** Create and edit draft linguistic content.
-   **Reviewer:** Review content and approve or reject it.
-   **Administrator:** Manage languages, users, roles, content settings,
    imports, and publication permissions.

A user may hold more than one role if the permission system supports it.

### 4.2 User language settings

A user can have multiple known languages and multiple learning courses.

A learning course represents a direction, for example Persian → German.
It should store:

-   User.
-   Source/support language.
-   Target language.
-   Current estimated level.
-   Desired level (optional).
-   Course status.
-   Optional learning preferences.

Do not assume that a user has exactly one native language or exactly one
target language.

## 5. Functional requirements

Priority definitions:

-   **P0:** Required for the first usable dictionary release.
-   **P1:** Important next-stage feature.
-   **P2:** Later enhancement.

### 5.1 Language management

**FR-LANG-01 (P0):** Administrators can add, edit, enable, and disable
languages without code changes.

**FR-LANG-02 (P0):** Each language stores a canonical language tag,
display name, native name, script, text direction, and active status.

**FR-LANG-03 (P0):** The system supports left-to-right and right-to-left
display.

**FR-LANG-04 (P1):** Regional variants such as `pt-BR` and `pt-PT` can
be represented independently when content differences justify it.

**FR-LANG-05 (P0):** Dictionary content is independent of learning
language pairs. A language pair is a user/course configuration, not a
separate dictionary dataset.

### 5.2 Dictionary and vocabulary

**FR-DICT-01 (P0):** Editors can create and update lexemes.

**FR-DICT-02 (P0):** Every lexeme belongs to exactly one language.

**FR-DICT-03 (P0):** A lexeme can have multiple senses.

**FR-DICT-04 (P0):** Each sense can have a part of speech, definition,
usage labels, domain, and ordering.

**FR-DICT-05 (P0):** A lexeme can have multiple inflected forms and
spelling variants.

**FR-DICT-06 (P0):** Searching for a known inflected form can resolve to
its base lexeme.

**FR-DICT-07 (P1):** The system supports synonyms, antonyms,
derivatives, compounds, and related-word relationships.

**FR-DICT-08 (P0):** Content has draft, review, published, and archived
states (or an equivalent controlled lifecycle).

**FR-DICT-09 (P0):** A lexeme can be assigned zero or more categories
from a shared category catalog.

**FR-DICT-10 (P0):** Administrators can create, edit, enable, and disable
categories without code changes.

**FR-DICT-11 (P0):** Dictionary search and browse can filter by category
code.

### 5.3 Grammar

**FR-GRAM-01 (P0):** A sense can be classified as one or more
grammatical categories where the data model supports separate usages.

**FR-GRAM-02 (P0):** German noun records can store grammatical gender,
definite article (`der`, `die`, `das`), and plural form.

**FR-GRAM-03 (P0):** German article data must be validated against the
permitted values.

**FR-GRAM-04 (P1):** Language-specific grammar extensions can be added
without adding language-specific columns to the core lexeme table.

**FR-GRAM-05 (P1):** The model can later represent features such as
French/Spanish gender, verb conjugations, Arabic roots and patterns, and
Persian/Turkish morphology.

Do not force all languages into one universal grammar JSON structure.
Use dedicated extension tables or well-defined typed feature records
where language-specific query and validation are required.

### 5.4 Translations

**FR-TRANS-01 (P0):** Each sense can have translations into multiple
target languages.

**FR-TRANS-02 (P0):** Each sense can have multiple translation
equivalents in the same target language.

**FR-TRANS-03 (P0):** Translation records can include context, notes,
register, and translation type.

**FR-TRANS-04 (P0):** A missing translation in one language must not
prevent the sense from being used in other languages.

**FR-TRANS-05 (P0):** The API returns translations for the target
language selected by the user.

**FR-TRANS-06 (P1):** Editors can see translation completeness by
language and identify missing content.

### 5.5 Examples

**FR-EX-01 (P0):** A sense can have multiple example sentences in its
source language.

**FR-EX-02 (P0):** An example can have translations into multiple
languages.

**FR-EX-03 (P1):** Examples may have a difficulty level, source
attribution, and usage note.

**FR-EX-04 (P0):** Examples must be associated with the relevant sense
to avoid illustrating the wrong meaning.

### 5.6 Collocations and expressions

**FR-COL-01 (P0):** Editors can add common collocations to a sense.

**FR-COL-02 (P0):** A collocation can store its phrase, pattern type,
explanation, frequency/priority, and status.

**FR-COL-03 (P0):** Collocations can have translations into multiple
languages.

**FR-COL-04 (P1):** Collocations can have one or more example sentences.

**FR-COL-05 (P1):** The system can distinguish ordinary collocations,
fixed expressions, idioms, and other phrase types.

**FR-COL-06 (P0):** The product must not claim exhaustive collocation
coverage. Editorial coverage should prioritize frequent and useful
expressions.

### 5.7 Learning levels

**FR-LEVEL-01 (P0):** The system supports CEFR levels A1, A2, B1, B2,
C1, and C2.

**FR-LEVEL-02 (P0):** A sense can have a level assignment independent of
other senses of the same lexeme.

**FR-LEVEL-03 (P1):** Examples and collocations may also have level
assignments.

**FR-LEVEL-04 (P0):** Level and frequency are separate attributes.

**FR-LEVEL-05 (P1):** Level assignments can record source, confidence,
reviewer, and assessment date.

### 5.8 Admin content management

**FR-ADMIN-01 (P0):** Editors can manage lexemes, categories, forms,
senses, grammar, translations, examples, collocations, and levels from a
unified editor.

**FR-ADMIN-02 (P0):** Editors can save incomplete content as a draft.

**FR-ADMIN-03 (P0):** Reviewers can approve, reject, or request changes.

**FR-ADMIN-04 (P0):** Published content is protected by role-based
permissions.

**FR-ADMIN-05 (P0):** Important content changes are recorded in revision
history.

**FR-ADMIN-06 (P0):** Admins can identify missing translations and
incomplete records.

**FR-ADMIN-07 (P1):** Admins can import and export CSV/XLSX data using
validated templates.

**FR-ADMIN-08 (P1):** The system detects likely duplicate lexemes and
forms.

**FR-ADMIN-09 (P1):** Bulk operations must show a preview and validation
errors before applying changes.

### 5.9 AI-assisted content

**FR-AI-01 (P1):** The system can request structured suggestions for
meanings, grammar, translations, examples, collocations, and levels.

**FR-AI-02 (P1):** AI provider integration is behind an application
interface so providers can be replaced.

**FR-AI-03 (P1):** AI output is validated before persistence.

**FR-AI-04 (P1):** AI-generated content is marked as machine-generated
and remains draft or pending review until approved.

**FR-AI-05 (P1):** Each AI generation stores provider/model, request
context, timestamp, validation result, and review status, subject to
privacy and retention policy.

**FR-AI-06 (P1):** Failed or invalid generation jobs can be retried
safely without creating duplicate content.

**FR-AI-07 (P0):** AI must not be treated as an authoritative source for
German articles, translations, CEFR levels, or collocation naturalness.

### 5.10 Learner features

**FR-LEARN-01 (P1):** Users can save a sense to a personal vocabulary
list.

**FR-LEARN-02 (P1):** Users can create collections and study vocabulary
by language, level, or category/topic.

**FR-LEARN-03 (P1):** Users can review saved vocabulary with flashcards
or quizzes.

**FR-LEARN-04 (P1):** The system records review history and learning
progress.

**FR-LEARN-05 (P2):** The system supports spaced repetition, for example
using an established algorithm such as FSRS.

**FR-LEARN-06 (P1):** User progress is scoped to the user's learning
course and the relevant sense, not only the written word.

## 6. Recommended data model

The following is the logical schema. Exact column types, naming
conventions, nullable rules, and foreign-key names should be finalized
during migration design.

### 6.1 Core tables

#### `languages`

Suggested fields:

-   `id`
-   `code` --- canonical language tag, unique (e.g. `de`, `fa`, `pt-BR`)
-   `name`
-   `native_name`
-   `script` --- e.g. Latin, Arabic
-   `direction` --- `ltr` or `rtl`
-   `is_active`
-   `created_at`, `updated_at`

#### `lexemes`

Suggested fields:

-   `id`
-   `language_id` --- foreign key to `languages`
-   `lemma` --- canonical dictionary form
-   `normalized_lemma` --- language-aware normalized search form
-   `slug` --- optional public URL identifier
-   `pronunciation` --- optional display transcription
-   `status`
-   `created_by`, `updated_by`
-   timestamps

Recommended uniqueness rule: a language-aware unique constraint for the
canonical lemma/normalization strategy, while allowing homographs to be
represented correctly. Do not blindly enforce one row per normalized
spelling if the editorial model needs separate homograph entries. Decide
whether homographs share one lexeme with multiple senses or use separate
lexemes based on linguistic editorial policy.

#### `lexeme_forms`

Suggested fields:

-   `id`
-   `lexeme_id`
-   `form`
-   `normalized_form`
-   `form_type` --- inflected, alternative spelling, transliteration,
    etc.
-   `grammatical_features` --- optional JSON for attributes that do not
    need relational querying
-   `is_primary`
-   timestamps

Index normalized forms with the language context available through the
related lexeme. Consider denormalizing `language_id` only if measured
search performance justifies it and consistency is enforced.

#### `parts_of_speech`

Suggested fields:

-   `id`
-   `code` --- unique stable code such as `noun`, `verb`, `adjective`
-   `display_name`
-   `description`
-   `sort_order`

#### `categories`

Suggested fields:

-   `id`
-   `code` --- unique stable code such as `family`, `colors`,
    `time_and_numbers`
-   `display_name`
-   `description` --- optional
-   `sort_order`
-   `is_active`
-   timestamps

Categories are a flat shared catalog across all source languages.

#### `category_lexeme`

Pivot linking lexemes to categories:

-   `id` --- optional
-   `category_id`
-   `lexeme_id`
-   unique (`category_id`, `lexeme_id`)
-   indexes on both foreign keys

#### `senses`

Suggested fields:

-   `id`
-   `lexeme_id`
-   `part_of_speech_id` --- nullable only if the sense is not yet
    classified
-   `definition`
-   `definition_language_id`
-   `usage_label` --- optional controlled label or relation
-   `domain` --- optional free-text editorial or linguistic note; not a
    substitute for lexeme categories
-   `frequency_rank` --- optional
-   `sort_order`
-   `status`
-   timestamps

If a spelling has two grammatical functions, create separate sense/usage
records as appropriate. Avoid assigning multiple unrelated parts of
speech to one undifferentiated sense.

Do not migrate sense `domain` into the category catalog. Categories are
lexeme-level topics; `domain` remains optional free text on the sense.

#### `sense_translations`

Suggested fields:

-   `id`
-   `sense_id`
-   `target_language_id`
-   `translation_text`
-   `translation_type` --- equivalent, explanatory, literal, contextual,
    etc.
-   `notes`
-   `register`
-   `sort_order`
-   `status`
-   timestamps

Allow multiple rows for one sense and target language. Add uniqueness
only if the editorial rules define a reliable duplicate key.

#### `difficulty_levels`

Suggested fields:

-   `id`
-   `code` --- A1 to C2, unique
-   `name`
-   `sort_order`

#### `sense_levels`

Suggested fields:

-   `id`
-   `sense_id`
-   `difficulty_level_id`
-   `is_primary`
-   `confidence` --- optional
-   `source_id` --- optional
-   `reviewed_by` --- optional
-   `assessed_at` --- optional
-   timestamps

Do not assume every sense must have a level. Support unknown or
unassessed status.

### 6.2 Grammar tables

#### `sense_grammar`

Suggested fields:

-   `id`
-   `sense_id`
-   `grammatical_gender` --- nullable, controlled values
-   `article` --- nullable; German values `der`, `die`, `das`
-   `plural_form` --- nullable
-   `grammar_notes`
-   timestamps

For a more general long-term implementation, use a language-specific
extension model, for example:

-   `german_noun_details`
-   `verb_forms` or `conjugations`
-   `language_grammar_features`

Do not place German article columns on every lexeme. The German noun
details should be created only for applicable German noun
senses/lexemes. Validate article and gender consistency in the
application and editorial workflow.

#### Future grammar extension examples

-   German noun gender, article, plural, separable-verb information.
-   French/Spanish gender and conjugation data.
-   Arabic roots and morphology.
-   Persian verb stems and affixes.
-   Turkish suffix and vowel-harmony information.

Build these extensions only when supported by real product requirements
and reviewed linguistic specifications.

### 6.3 Examples

#### `examples`

Suggested fields:

-   `id`
-   `sense_id`
-   `language_id`
-   `sentence`
-   `usage_note`
-   `difficulty_level_id` --- nullable
-   `source_id` --- nullable
-   `status`
-   `sort_order`
-   timestamps

#### `example_translations`

Suggested fields:

-   `id`
-   `example_id`
-   `language_id`
-   `translation`
-   `notes`
-   `status`
-   timestamps

### 6.4 Collocations

#### `collocations`

Suggested fields:

-   `id`
-   `sense_id`
-   `phrase`
-   `pattern_type` --- e.g. verb+noun, adjective+noun
-   `phrase_type` --- collocation, fixed_expression, idiom
-   `explanation`
-   `frequency_label` or a documented frequency score
-   `difficulty_level_id` --- nullable
-   `status`
-   `sort_order`
-   timestamps

#### `collocation_translations`

Suggested fields:

-   `id`
-   `collocation_id`
-   `language_id`
-   `translation`
-   `notes`
-   `status`
-   timestamps

#### `collocation_examples`

Use this linking table if collocations reuse the central example
records. Otherwise, add collocation-specific example records. Decide one
approach and document it to avoid duplicated example content.

### 6.5 User learning tables

#### `user_languages`

-   `id`
-   `user_id`
-   `language_id`
-   `proficiency_level` --- optional
-   `is_primary`
-   timestamps

#### `user_learning_courses`

-   `id`
-   `user_id`
-   `source_language_id`
-   `target_language_id`
-   `current_level_id` --- optional
-   `target_level_id` --- optional
-   `status`
-   timestamps

#### `user_saved_senses`

-   `id`
-   `user_id`
-   `course_id` --- optional
-   `sense_id`
-   `collection_id` --- optional
-   `saved_at`

#### `user_vocabulary_progress`

-   `id`
-   `user_id`
-   `course_id`
-   `sense_id`
-   `learning_state`
-   `due_at`
-   `last_reviewed_at`
-   `review_count`
-   `lapse_count`
-   algorithm-specific scheduling data
-   timestamps

#### `user_reviews`

-   `id`
-   `user_id`
-   `course_id`
-   `sense_id`
-   `rating`
-   `reviewed_at`
-   `response_time_ms` --- optional
-   `scheduled_interval` --- optional

#### `learning_sessions`

-   `id`
-   `user_id`
-   `course_id`
-   `started_at`
-   `completed_at`
-   `session_type`
-   `summary` --- optional structured data

### 6.6 Content operations tables

Recommended tables:

-   `content_sources` --- source name, URL/reference, license,
    attribution, access date, and usage notes.
-   `content_revisions` --- entity type, entity ID, version,
    previous/current snapshots or structured diffs, editor, timestamp.
-   `content_reviews` --- entity, reviewer, decision, comments,
    timestamp.
-   `import_jobs` --- file, import type, status, counts, errors,
    initiated by.
-   `translation_jobs` --- source entity, target language, provider,
    status, result metadata.
-   `ai_generation_runs` --- provider/model, prompt version,
    input/output references, validation status, review status.

Use a consistent audit strategy. If using polymorphic entity references,
document integrity limitations and ensure application-level validation.
For critical records, explicit foreign keys or entity-specific revision
tables may be preferable.

## 7. Important relationship rules

1.  One language has many lexemes.
2.  One lexeme has many forms.
3.  One lexeme has many senses.
4.  One lexeme has many categories; one category has many lexemes.
5.  One sense has one primary part of speech in the initial model;
    different grammatical usages should be represented as separate
    senses or usage records.
6.  One sense has many translations.
7.  One sense has many examples.
8.  One example has many translations.
9.  One sense has many collocations.
10. One sense can have multiple level assessments over time, with one
    current primary assessment if desired.
11. Sense `domain` is independent of lexeme categories.
12. One user can have many learning courses.
13. One learning course has a source language and target language.
14. User progress should refer to a sense and a course.

## 8. Example data model walkthrough

Use German `Bank` as a test case.

### Lexeme

-   Language: German (`de`)
-   Lemma: `Bank`
-   Categories: `work`, `home` (multiple categories allowed when useful)

### Sense 1

-   Part of speech: noun
-   Definition: financial institution
-   Article: `die`
-   Gender: feminine
-   Plural: `Banken`
-   Level: A1

Translations:

-   English: bank
-   Persian: بانک
-   French: banque
-   Turkish: banka

Example:

-   German: `Ich gehe zur Bank.`
-   English: `I am going to the bank.`
-   Persian: `من به بانک می‌روم.`

Possible collocations:

-   `ein Konto eröffnen`
-   `Geld abheben`
-   `Geld einzahlen`

### Sense 2

-   Part of speech: noun
-   Definition: a bench or seating place
-   Article/gender/plural: record the verified German grammatical data
-   Level: assign based on editorial assessment

Translations:

-   English: bench
-   Persian: نیمکت
-   French: banc

Example:

-   German: `Wir sitzen auf der Bank.`
-   English: `We are sitting on the bench.`

This example demonstrates why translations, examples, and levels must be
linked to senses rather than only to the word spelling.

## 9. Application architecture

Use a **modular monolith** in the existing Laravel application. Do not
begin with microservices.

Suggested domain boundaries:

``` text
app/
  Domain/
    Languages/
    Dictionary/
    Grammar/
    Translations/
    Examples/
    Collocations/
    Learning/
    ContentManagement/
    Search/
    Integrations/
  Http/
    Controllers/
      Api/
      Admin/
    Requests/
    Resources/
  Jobs/
  Policies/
  Events/
  Listeners/
```

The exact folder organization can follow the existing project's
conventions. The important requirement is separation of
responsibilities, not a particular folder naming scheme.

### 9.1 Laravel implementation standards

-   Use Eloquent models and explicit relationships.
-   Use Form Request classes for request validation.
-   Use API Resources for stable response structures.
-   Use Actions or Services for business operations.
-   Use Policies and gates for authorization.
-   Use database transactions for multi-record publishing operations.
-   Use queues for bulk imports and AI generation.
-   Use events/listeners for asynchronous indexing or cache
    invalidation.
-   Use factories and seeders for repeatable test data.
-   Use migrations for all schema changes.
-   Avoid putting complex business logic in controllers or model
    observers.

### 9.2 Suggested service boundaries

-   `LanguageRegistryService`
-   `DictionaryLookupService`
-   `LexemeManagementService`
-   `SenseManagementService`
-   `TranslationManagementService`
-   `ContentPublishingService`
-   `VocabularyImportService`
-   `DictionarySearchService`
-   `AiContentGenerationService`
-   `LearningProgressService`
-   `ReviewSchedulingService`

These are examples; do not create a class for every trivial operation.

## 10. API outline

Use versioned API routes, for example `/api/v1`.

### Public dictionary endpoints

``` http
GET /api/v1/languages
GET /api/v1/categories
GET /api/v1/dictionary/search
GET /api/v1/lexemes/{id}
GET /api/v1/lexemes/{id}/senses
GET /api/v1/senses/{id}
```

Search parameters may include:

-   `q` --- query string
-   `source_language` --- language tag
-   `target_language` --- requested translation language
-   `level` --- CEFR level
-   `part_of_speech` --- grammatical category
-   `category` --- vocabulary category code
-   `page` and `per_page`

Example:

``` http
GET /api/v1/dictionary/search?q=Bank&source_language=de&target_language=fa
```

A response should include the lexeme, its categories, its senses,
grammatical information, translations for the requested target language,
examples, collocations, and levels, subject to pagination and
response-size limits.

### Admin endpoints

``` http
POST   /api/v1/admin/lexemes
PUT    /api/v1/admin/lexemes/{id}
POST   /api/v1/admin/lexemes/{id}/senses
PUT    /api/v1/admin/senses/{id}
POST   /api/v1/admin/senses/{id}/translations
POST   /api/v1/admin/senses/{id}/examples
POST   /api/v1/admin/senses/{id}/collocations
POST   /api/v1/admin/content/{type}/{id}/submit-review
POST   /api/v1/admin/content/{type}/{id}/approve
POST   /api/v1/admin/imports
GET    /api/v1/admin/content-completeness
```

Admin routes must require authentication and permission checks. The
route outline is not a final API contract; finalize payloads and
response schemas before frontend implementation.

### Learner endpoints (P1)

``` http
GET    /api/v1/me/courses
POST   /api/v1/me/courses
POST   /api/v1/me/saved-senses
DELETE /api/v1/me/saved-senses/{id}
GET    /api/v1/me/vocabulary
POST   /api/v1/me/reviews
GET    /api/v1/me/progress
```

## 11. Search and text handling

### 11.1 Initial search

Start with MySQL indexes and well-defined lookup rules:

-   Exact lemma match.
-   Normalized lemma match.
-   Inflected-form match.
-   Prefix/autocomplete match.
-   Optional full-text search across definitions and examples.

Do not rely on a single generic full-text index for every language and
script.

### 11.2 Future search engine

When search quality or traffic justifies it, evaluate Meilisearch or
OpenSearch for:

-   Typo tolerance.
-   Fuzzy matching.
-   Ranking.
-   Autocomplete.
-   Multilingual indexing.
-   Search analytics.

Keep MySQL as the source of truth. Search indexes must be rebuildable
from database records.

### 11.3 Language-aware normalization

Normalization must be language-aware and preserve the original display
text.

Pay particular attention to:

-   Persian and Arabic letter variants.
-   Diacritics and optional vowel marks.
-   Zero-width non-joiners and joining behavior.
-   Arabic versus Persian forms of yeh and kaf.
-   Turkish dotted and dotless I.
-   Accents in French, Spanish, and Portuguese.
-   Unicode normalization.

Do not destructively rewrite the user's original query or stored lemma.
Store normalized search keys separately and test normalization with
native-language examples.

## 12. AI content generation workflow

AI is an assistant for editors, not the authority or publisher of
dictionary content.

### 12.1 Workflow

1.  Editor selects a language and enters a lemma or imports a batch.
2.  System checks for likely duplicates.
3.  A queued job requests structured analysis.
4.  AI suggests senses and parts of speech.
5.  AI suggests translations for selected target languages.
6.  AI suggests examples and collocations.
7.  AI suggests CEFR levels with reasoning or confidence metadata.
8.  Application validates output against schemas and business rules.
9.  System stores results as draft suggestions.
10. Editor reviews, corrects, and approves.
11. Approved content is published.
12. Search indexes and relevant caches are updated.

### 12.2 Provider abstraction

Define an application interface such as:

``` php
interface DictionaryContentGenerator
{
    public function analyzeWord(
        string $word,
        string $language
    ): array;

    public function generateTranslations(
        array $sense,
        array $targetLanguages
    ): array;

    public function generateExamples(
        array $sense,
        string $language
    ): array;

    public function generateCollocations(
        array $sense
    ): array;
}
```

Implement provider-specific clients behind this interface. The rest of
the application should not depend directly on a provider's SDK.

### 12.3 Validation

Validate:

-   Required keys and data types.
-   Supported language tags.
-   Supported part-of-speech codes.
-   German article values.
-   CEFR values.
-   Text length limits.
-   Sense/translation associations.
-   Duplicate suggestions.
-   Whether examples use the intended sense.
-   Whether output includes unsupported or invented source claims.

Store provider/model and prompt-template version for traceability. Do
not expose private prompts, API keys, or sensitive user data in admin
logs.

### 12.4 Editorial and licensing safeguards

Before importing any third-party dictionary, corpus, or generated
dataset:

-   Verify its license.
-   Verify commercial-use permission.
-   Record attribution requirements.
-   Record source and import date.
-   Avoid copying proprietary dictionary definitions without permission.
-   Keep a way to remove or correct disputed content.

## 13. Content quality and publishing lifecycle

Recommended content states:

-   `draft`
-   `pending_review`
-   `approved`
-   `published`
-   `rejected`
-   `archived`

A typical workflow:

``` text
Draft
  |
  v
Pending review
  |
  +---- rejected / changes requested
  |
  v
Approved
  |
  v
Published
  |
  v
Revised or archived
```

Rules:

1.  Only authorized roles can publish.
2.  AI-generated content cannot bypass review in the initial release.
3.  Important edits to published content create a revision.
4.  Deletion should normally be soft deletion or archival where
    references and audit history must remain.
5.  Publication should be transactional when several related records
    change together.
6.  Content completeness and content correctness must be measured
    separately.

Suggested quality indicators:

-   Definition present.
-   Part of speech assigned.
-   German noun article present where applicable.
-   Plural form present where required.
-   Translation coverage for enabled target languages.
-   Example coverage.
-   Collocation coverage.
-   CEFR assessment.
-   Editorial approval.
-   Source/license metadata.

A completeness score is a workflow aid, not proof of accuracy.

## 14. Security and privacy

-   Require authentication for learner-specific data and admin
    operations.
-   Enforce authorization through policies and permissions.
-   Validate and limit all imported files.
-   Escape user-generated content in rendered output.
-   Rate-limit public search and expensive AI endpoints.
-   Keep AI credentials in environment secrets or a secrets manager.
-   Do not store secrets in the database as ordinary text.
-   Audit administrative actions.
-   Apply least-privilege access to database and infrastructure.
-   Define account deletion and user-data retention policies.
-   Avoid sending unnecessary personal data to AI providers.
-   Use HTTPS in production.
-   Protect import/export endpoints against unauthorized access.

## 15. Performance and operations

### 15.1 Database

-   Add indexes for common lookup and relationship paths.
-   Use foreign keys and appropriate deletion rules.
-   Use pagination for large lists.
-   Eager-load related records to avoid N+1 queries.
-   Inspect slow queries before adding denormalization.
-   Keep large imports out of synchronous web requests.

Suggested indexes include:

-   `languages(code)` unique.
-   `lexemes(language_id, normalized_lemma)`.
-   `lexeme_forms(lexeme_id, normalized_form)`.
-   `categories(code)` unique.
-   `category_lexeme(category_id, lexeme_id)` unique.
-   `senses(lexeme_id, sort_order)`.
-   `sense_translations(sense_id, target_language_id)`.
-   `sense_levels(sense_id, difficulty_level_id)`.
-   `examples(sense_id, language_id)`.
-   `collocations(sense_id, phrase_type)`.

The exact indexes must be validated against actual query patterns and
the chosen homograph/duplicate policy.

### 15.2 Caching

Cache frequently accessed published dictionary entries and language
metadata. Invalidate or version relevant cache keys when content is
updated or published.

Redis is a suitable option for caching and queues if it fits the current
deployment.

### 15.3 Queues

Use Laravel queues for:

-   Bulk imports.
-   AI generation.
-   Translation generation.
-   Search-index updates.
-   Large exports.
-   Recalculation of content completeness.

Jobs must be retry-safe and idempotent where possible.

### 15.4 Backups and monitoring

-   Schedule database backups.
-   Test restoration, not just backup creation.
-   Monitor queue failures.
-   Monitor slow database queries.
-   Monitor search latency and error rates.
-   Log content publication and import outcomes.
-   Alert on repeated job failures.

## 16. Testing strategy

### 16.1 Unit tests

Test:

-   Language-tag validation.
-   German article validation.
-   Level validation.
-   Language-aware normalization.
-   Translation selection.
-   Sense-level grammar.
-   Content status transitions.
-   Duplicate detection rules.

### 16.2 Feature tests

Test:

-   Create a lexeme.
-   Add multiple senses.
-   Add translations in several languages.
-   Add examples and example translations.
-   Add collocations and translations.
-   Search a base word and an inflected form.
-   Retrieve the correct translation for a selected target language.
-   Submit content for review.
-   Approve and publish content.
-   Verify role permissions.
-   Import valid and invalid files.
-   Ensure draft content is not exposed through public endpoints.

### 16.3 Essential test dataset

Before bulk importing content, create a small curated test dataset of
approximately 20--30 German lexemes containing:

-   Nouns with `der`, `die`, and `das`.
-   Nouns with irregular plurals.
-   Verbs with several forms.
-   Adjectives.
-   A spelling with multiple meanings.
-   A spelling used as more than one part of speech.
-   Words with different CEFR levels by sense.
-   Several collocations.
-   Persian, English, French, and Turkish translations.

This dataset should be used in automated tests and as an acceptance-test
fixture.

## 17. Non-functional requirements

The following are initial targets to validate and refine after measuring
the existing platform.

### Reliability

-   Failed background jobs are observable and retryable.
-   Database backups are scheduled and restoration is tested.
-   Published dictionary content is not lost during ordinary edits.

### Performance

-   Common dictionary lookups should be optimized for responsive
    interactive use.
-   Use pagination and response-size limits.
-   Set measurable API latency targets after establishing baseline
    infrastructure and expected traffic.
-   Large imports and AI generation must not block ordinary web
    requests.

### Scalability

-   Adding a language must not require new language-pair tables.
-   Content must be independently manageable per language and sense.
-   Search indexes must be rebuildable.
-   Background processing must support batches.

### Maintainability

-   Domain logic is separated from HTTP controllers.
-   Schema changes are migration-managed.
-   APIs are versioned.
-   Automated tests cover core linguistic rules.
-   AI provider integrations are replaceable.

### Accessibility and internationalization

-   Support RTL layouts.
-   Preserve Unicode correctly.
-   Use locale-aware formatting.
-   Do not assume all scripts are Latin.
-   Ensure admin and learner interfaces can be localized independently
    of dictionary content.

## 18. Delivery roadmap

The timeline below is an initial planning estimate for a small team
already familiar with the existing Laravel application. It is not a
fixed delivery commitment.

### Phase 1 --- Architecture and core database (Weeks 1--2)

-   Review the current application and database.
-   Confirm domain terminology and duplicate/homograph policy.
-   Design and review migrations.
-   Implement languages, lexemes, forms, senses, parts of speech,
    categories, and levels.
-   Add factories, seeders, and core tests.

**Exit criteria:** A tested database can represent multiple languages,
forms, senses, categories, and levels.

### Phase 2 --- Dictionary content and admin editor (Weeks 3--4)

-   Implement grammar and German noun details.
-   Implement translations.
-   Implement examples and example translations.
-   Implement collocations and their translations.
-   Build the vocabulary editor.
-   Implement draft/review/publish workflow and revision history.

**Exit criteria:** An editor can create and publish a complete
multilingual entry without developer intervention.

### Phase 3 --- Public dictionary API and user interface (Weeks 5--6)

-   Implement language selection.
-   Implement dictionary search and detail endpoints.
-   Display meanings, categories, grammar, translations, examples, and
    collocations.
-   Implement inflected-form lookup and category filter.
-   Add pagination and API tests.

**Exit criteria:** A user can search a German word and see the correct
meaning-specific translation in their selected language, and can filter
results by category.

### Phase 4 --- CEFR and vocabulary collections (Weeks 7--8)

-   Implement sense-level CEFR assignments.
-   Build level and category/topic filters.
-   Implement saved senses and collections.
-   Add content completeness reports.

**Exit criteria:** Users can discover and save vocabulary by level and
topic.

### Phase 5 --- AI-assisted content tools (Weeks 9--10)

-   Implement provider abstraction.
-   Implement queued generation jobs.
-   Add structured output validation.
-   Add draft suggestion and editorial approval.
-   Add usage, cost, and failure monitoring.

**Exit criteria:** Editors can generate suggestions and review them
before publication.

### Phase 6 --- Learning and review (Weeks 11--12)

-   Implement learner courses.
-   Implement flashcards and quizzes.
-   Record review history.
-   Implement progress reporting.
-   Add a spaced-repetition scheduler if in scope.

**Exit criteria:** Users can study and track saved vocabulary.

### Phase 7 --- Content expansion and search improvements (Weeks 13--14)

-   Import approved datasets.
-   Expand language coverage.
-   Improve text normalization.
-   Evaluate dedicated search infrastructure.
-   Improve duplicate detection and content reports.

**Exit criteria:** Additional languages can be added through data and
configuration rather than database redesign.

### Phase 8 --- Hardening and release (Weeks 15--16)

-   Security and permission review.
-   Load and query testing.
-   Backup/restore testing.
-   Queue and monitoring review.
-   Editorial acceptance testing.
-   Production deployment.

**Exit criteria:** The release meets agreed quality, performance,
security, and operational criteria.

## 19. First-release acceptance criteria

The first dictionary release is acceptable when all of the following are
true:

1.  An administrator can add a language without a code change.
2.  An editor can create a lexeme in any active language.
3.  A lexeme can contain multiple meanings.
4.  Each meaning can have its own part of speech.
5.  A lexeme can be assigned multiple categories, and search can filter
    by category.
6.  A German noun can store and display its correct article and plural
    form.
7.  A sense can have multiple translations into different languages.
8.  A sense can have multiple examples, each with translations.
9.  A sense can have multiple collocations with translations.
10. A sense can be assigned a CEFR level independently of other senses.
11. Search can find a lemma and a configured inflected form.
12. A learner can select a target translation language.
13. The returned translation belongs to the correct sense.
14. Editors can save drafts and submit content for review.
15. Only authorized reviewers/admins can approve and publish.
16. Published content is distinguishable from draft content.
17. Important edits have revision history.
18. Core workflows have automated tests.
19. A new language can be introduced without creating a new
    language-pair schema.

## 20. Key product and technical decisions to confirm

These decisions should be confirmed before finalizing migrations:

1.  **Homographs:** Should identical spellings with unrelated origins be
    one lexeme with multiple senses, or separate lexeme records?
    Recommended: use an editorially defined homograph policy and support
    separate entries where needed.
2.  **Definitions:** Will definitions initially be written in the source
    language, or also stored in multiple explanation languages?
    Recommended: store a definition language on each definition/sense
    record and allow later localized definitions.
3.  **Translation variants:** Can a sense have multiple valid
    translations in the same language? Recommended: yes.
4.  **Level source:** Will CEFR levels be manually curated, imported
    from licensed lists, or AI-suggested and reviewed? Recommended:
    support all three with provenance.
5.  **German grammar:** Is article/plural data required for every German
    noun before publication? Recommended: yes, unless the noun is
    genuinely exceptional or the data is not yet verified; keep
    incomplete records in draft.
6.  **Initial language coverage:** Which languages need complete
    translations in the first release? Recommended: begin with German
    source vocabulary and Persian/English translations, then add
    French/Turkish and other targets in controlled batches.
7.  **Content licensing:** Which dictionary datasets and corpora can
    legally be used commercially? This must be resolved before importing
    large datasets.
8.  **Existing platform integration:** Which current Laravel models,
    users, authentication, admin panel, and APIs must be preserved?
    Review the existing codebase before implementing migrations.

## 21. Recommended implementation order

Do not start by generating thousands of words with AI.

Implement in this order:

1.  Review the existing Laravel application and database.
2.  Confirm this specification's terminology and unresolved decisions.
3.  Design migrations and indexes.
4.  Build the core dictionary models and relationships.
5.  Seed the 20--30-word German test dataset.
6.  Build the admin editor and publication workflow.
7.  Build and test dictionary search and API responses.
8.  Add translations, examples, collocations, and levels.
9.  Add import/export.
10. Add AI-assisted content generation.
11. Add learner courses and study tools.
12. Expand languages and content volume.

The first technical milestone should be a complete, manually curated
German dictionary entry that has multiple meanings, correct German
grammar, Persian and English translations, examples, collocations, and a
CEFR level. The same data model should then be tested with a second
language before bulk content work begins.

------------------------------------------------------------------------

## 22. Final architectural recommendation

Build one multilingual dictionary engine with a shared relational data
model.

-   Languages own lexemes.
-   Lexemes own forms and senses and may belong to many categories.
-   Senses own grammatical usage, translations, examples, collocations,
    and learning-level assessments.
-   Users own learning courses, saved vocabulary, and review progress.
-   Editors own the content workflow, validation, and publication
    process.
-   AI generates suggestions, but reviewed content is the source of
    truth.

Keep Laravel and MySQL for the initial implementation. Use a modular
monolith, queues for long-running tasks, and a dedicated search engine
only when actual search requirements justify it.

The most important success criterion is not the number of languages
initially supported. It is whether the data model can correctly
represent the linguistic structure of each language and each meaning
without forcing the application into language-pair-specific code.
