DROP TABLE IF EXISTS "releases";
CREATE TABLE "releases"
(
    "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    "addon_id" INTEGER,
    "release_tag" VARCHAR(32),
    "name" VARCHAR,
    "description" VARCHAR,
    "download_url" VARCHAR(256),
    "signed" INT(1),
    "release_date" TIMESTAMP

);
CREATE INDEX released_at_index ON content( release_date );
CREATE INDEX tag_index ON content( release_tag );
