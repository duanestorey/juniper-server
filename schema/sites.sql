DROP TABLE IF EXISTS "sites";
CREATE TABLE "sites"
(
    "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    "url" VARCHAR(256),
    "api_version" VARCHAR,
    "name" VARCHAR,
    "slug" VARCHAR,
    "bio" VARCHAR,
    "company" VARCHAR,
    "avatar_url" VARCHAR,
    "github_url" VARCHAR,
    "blog_url" VARCHAR,
    "twitter" VARCHAR,
    "num_failures" INTEGER,
    "updated_at" TIMESTAMP,
    "first_added" TIMESTAMP

);
CREATE INDEX created_at_index ON content( created_at );
CREATE INDEX url_index ON content( url );
