-- postgresql.istanbul schema — multilingual version

CREATE TABLE IF NOT EXISTS users (
    id          SERIAL PRIMARY KEY,
    username    VARCHAR(64) NOT NULL UNIQUE,
    password    VARCHAR(255) NOT NULL,
    created_at  TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS languages (
    id          SERIAL PRIMARY KEY,
    code        VARCHAR(10)  NOT NULL UNIQUE,  -- e.g. 'en', 'tr', 'de'
    label       VARCHAR(64)  NOT NULL,          -- e.g. 'English', 'Türkçe'
    is_default  BOOLEAN      NOT NULL DEFAULT FALSE,
    is_active   BOOLEAN      NOT NULL DEFAULT TRUE,
    sort_order  INTEGER      NOT NULL DEFAULT 0,
    created_at  TIMESTAMPTZ  NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS meetups (
    id               SERIAL PRIMARY KEY,
    status           VARCHAR(16) NOT NULL DEFAULT 'draft'
                         CHECK (status IN ('draft','published','past')),
    event_date       TIMESTAMPTZ,
    event_end        TIMESTAMPTZ,
    venue_address    TEXT,
    venue_map_url    TEXT,
    registration_url TEXT,
    created_at       TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at       TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS meetup_translations (
    id         SERIAL PRIMARY KEY,
    meetup_id  INTEGER     NOT NULL REFERENCES meetups(id) ON DELETE CASCADE,
    lang       VARCHAR(10) NOT NULL REFERENCES languages(code) ON DELETE CASCADE ON UPDATE CASCADE,
    field      VARCHAR(64) NOT NULL,  -- 'meetup_title', 'notes'
    value      TEXT,
    UNIQUE (meetup_id, lang, field)
);

CREATE TABLE IF NOT EXISTS talks (
    id                SERIAL PRIMARY KEY,
    meetup_id         INTEGER NOT NULL REFERENCES meetups(id) ON DELETE CASCADE,
    sort_order        INTEGER NOT NULL DEFAULT 0,
    speaker_name      VARCHAR(255),
    speaker_photo_url TEXT,
    talk_duration_min INTEGER
);

CREATE TABLE IF NOT EXISTS talk_translations (
    id       SERIAL PRIMARY KEY,
    talk_id  INTEGER     NOT NULL REFERENCES talks(id) ON DELETE CASCADE,
    lang     VARCHAR(10) NOT NULL REFERENCES languages(code) ON DELETE CASCADE ON UPDATE CASCADE,
    field    VARCHAR(64) NOT NULL,  -- 'talk_title', 'talk_abstract', 'speaker_bio'
    value    TEXT,
    UNIQUE (talk_id, lang, field)
);

-- Auto-update updated_at
CREATE OR REPLACE FUNCTION set_updated_at()
RETURNS TRIGGER LANGUAGE plpgsql AS $$
BEGIN NEW.updated_at = NOW(); RETURN NEW; END;
$$;

DROP TRIGGER IF EXISTS meetups_updated_at ON meetups;
CREATE TRIGGER meetups_updated_at
    BEFORE UPDATE ON meetups
    FOR EACH ROW EXECUTE FUNCTION set_updated_at();

-- Auto-archive past meetups
CREATE OR REPLACE FUNCTION auto_archive_meetups() RETURNS void
LANGUAGE plpgsql AS $$
BEGIN
    UPDATE meetups
    SET status = 'past'
    WHERE status = 'published'
      AND event_date < NOW() - INTERVAL '6 hours';
END;
$$;

-- Seed languages
INSERT INTO languages (code, label, is_default, is_active, sort_order) VALUES
    ('en', 'English', TRUE,  TRUE, 1),
    ('tr', 'Türkçe',  FALSE, TRUE, 2)
ON CONFLICT (code) DO NOTHING;

-- Seed admin user (password: changeme — CHANGE THIS)
INSERT INTO users (username, password)
VALUES ('admin', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi')
ON CONFLICT DO NOTHING;

-- Migration: add event_end column and rename venue_name → meetup_title
-- Run on existing installations:
ALTER TABLE meetups ADD COLUMN IF NOT EXISTS event_end TIMESTAMPTZ;
UPDATE meetup_translations SET field = 'meetup_title' WHERE field = 'venue_name';
