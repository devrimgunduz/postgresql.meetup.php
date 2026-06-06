-- Migration: EN/TR columns → generic translation tables
-- Run this ONCE on an existing installation that has data.
-- Safe to skip if starting fresh (schema.sql already has the new structure).

-- 1. Create new tables if not present
CREATE TABLE IF NOT EXISTS languages (
    id         SERIAL PRIMARY KEY,
    code       VARCHAR(10)  NOT NULL UNIQUE,
    label      VARCHAR(64)  NOT NULL,
    is_default BOOLEAN      NOT NULL DEFAULT FALSE,
    is_active  BOOLEAN      NOT NULL DEFAULT TRUE,
    sort_order INTEGER      NOT NULL DEFAULT 0,
    created_at TIMESTAMPTZ  NOT NULL DEFAULT NOW()
);

INSERT INTO languages (code, label, is_default, is_active, sort_order) VALUES
    ('en', 'English', TRUE,  TRUE, 1),
    ('tr', 'Türkçe',  FALSE, TRUE, 2)
ON CONFLICT (code) DO NOTHING;

CREATE TABLE IF NOT EXISTS meetup_translations (
    id        SERIAL PRIMARY KEY,
    meetup_id INTEGER     NOT NULL REFERENCES meetups(id) ON DELETE CASCADE,
    lang      VARCHAR(10) NOT NULL REFERENCES languages(code) ON DELETE CASCADE ON UPDATE CASCADE,
    field     VARCHAR(64) NOT NULL,
    value     TEXT,
    UNIQUE (meetup_id, lang, field)
);

CREATE TABLE IF NOT EXISTS talk_translations (
    id      SERIAL PRIMARY KEY,
    talk_id INTEGER     NOT NULL REFERENCES talks(id) ON DELETE CASCADE,
    lang    VARCHAR(10) NOT NULL REFERENCES languages(code) ON DELETE CASCADE ON UPDATE CASCADE,
    field   VARCHAR(64) NOT NULL,
    value   TEXT,
    UNIQUE (talk_id, lang, field)
);

-- 2. Migrate meetup data
INSERT INTO meetup_translations (meetup_id, lang, field, value)
SELECT id, 'en', 'venue_name', venue_name_en FROM meetups WHERE venue_name_en IS NOT NULL
ON CONFLICT DO NOTHING;
INSERT INTO meetup_translations (meetup_id, lang, field, value)
SELECT id, 'tr', 'venue_name', venue_name_tr FROM meetups WHERE venue_name_tr IS NOT NULL
ON CONFLICT DO NOTHING;
INSERT INTO meetup_translations (meetup_id, lang, field, value)
SELECT id, 'en', 'notes', notes_en FROM meetups WHERE notes_en IS NOT NULL
ON CONFLICT DO NOTHING;
INSERT INTO meetup_translations (meetup_id, lang, field, value)
SELECT id, 'tr', 'notes', notes_tr FROM meetups WHERE notes_tr IS NOT NULL
ON CONFLICT DO NOTHING;

-- 3. Migrate talk data
INSERT INTO talk_translations (talk_id, lang, field, value)
SELECT id, 'en', 'talk_title', talk_title_en FROM talks WHERE talk_title_en IS NOT NULL
ON CONFLICT DO NOTHING;
INSERT INTO talk_translations (talk_id, lang, field, value)
SELECT id, 'tr', 'talk_title', talk_title_tr FROM talks WHERE talk_title_tr IS NOT NULL
ON CONFLICT DO NOTHING;
INSERT INTO talk_translations (talk_id, lang, field, value)
SELECT id, 'en', 'talk_abstract', talk_abstract_en FROM talks WHERE talk_abstract_en IS NOT NULL
ON CONFLICT DO NOTHING;
INSERT INTO talk_translations (talk_id, lang, field, value)
SELECT id, 'tr', 'talk_abstract', talk_abstract_tr FROM talks WHERE talk_abstract_tr IS NOT NULL
ON CONFLICT DO NOTHING;
INSERT INTO talk_translations (talk_id, lang, field, value)
SELECT id, 'en', 'speaker_bio', speaker_bio_en FROM talks WHERE speaker_bio_en IS NOT NULL
ON CONFLICT DO NOTHING;
INSERT INTO talk_translations (talk_id, lang, field, value)
SELECT id, 'tr', 'speaker_bio', speaker_bio_tr FROM talks WHERE speaker_bio_tr IS NOT NULL
ON CONFLICT DO NOTHING;

-- 4. Drop old columns from meetups
ALTER TABLE meetups
    DROP COLUMN IF EXISTS venue_name_en,
    DROP COLUMN IF EXISTS venue_name_tr,
    DROP COLUMN IF EXISTS notes_en,
    DROP COLUMN IF EXISTS notes_tr;

-- 5. Drop old columns from talks
ALTER TABLE talks
    DROP COLUMN IF EXISTS speaker_bio_en,
    DROP COLUMN IF EXISTS speaker_bio_tr,
    DROP COLUMN IF EXISTS talk_title_en,
    DROP COLUMN IF EXISTS talk_title_tr,
    DROP COLUMN IF EXISTS talk_abstract_en,
    DROP COLUMN IF EXISTS talk_abstract_tr;
