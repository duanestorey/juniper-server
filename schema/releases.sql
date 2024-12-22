DROP TABLE IF EXISTS "releases";
CREATE TABLE "releases"
(
    "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    "addon_id" INTEGER,
    "release_tag" VARCHAR(32),
    "url", VARCHAR,
    "name" VARCHAR,
    "description" VARCHAR,
    "download_url" VARCHAR(256),
    "signed" INT(1),
    "release_date" TIMESTAMP

);
CREATE INDEX released_at_index ON releases( release_date );
CREATE INDEX tag_index ON releases( release_tag );
CREATE INDEX addon_index ON releases( addon_id );
CREATE INDEX id_index ON releases( id );
