DROP TABLE IF EXISTS "addons";
CREATE TABLE "addons"
(
    "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    "site_id" INTEGER,
    "type" VARCHAR(32),
    "name" VARCHAR,
    "slug" VARCHAR,
    "author_name" VARCHAR,
    "author_url" VARCHAR,
    "avatar_url" VARCHAR(255),
    "description" VARCHAR,
    "readme" VARCHAR,
    "stable_version" VARCHAR(32),
    "repo_version" VARCHAR(32),
    "banner_image_url" VARCHAR,
    "requires_php" VARCHAR(32),
    "requires_at_least" VARCHAR(32),
    "tested_up_to" VARCHAR(32),
    "signing_authority" VARCHAR,
    "open_issues_count" INTEGER,
    "stars_count" INTEGER,
    "watchers_count" INTEGER,
    "subscribers_count" INTEGER,
    "updated_at" TIMESTAMP,
    "created_at" TIMESTAMP

);
CREATE INDEX created_at_index ON content( created_at );
CREATE INDEX type_index ON content( type );
