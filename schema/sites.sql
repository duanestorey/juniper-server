DROP TABLE IF EXISTS "sites";
CREATE TABLE "sites"
(
    "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    "url" VARCHAR(256),
    "num_failures" INTEGER,
    "updated_at" TIMESTAMP,
    "first_added" TIMESTAMP

);
CREATE INDEX created_at_index ON content( created_at );
CREATE INDEX url_index ON content( url );
