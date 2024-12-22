DROP TABLE IF EXISTS "issues";
CREATE TABLE "issues"
(
    "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    "addon_id" INTEGER,
    "url" VARCHAR,
    "title" VARCHAR,
    "body" VARCHAR, 
    "user" VARCHAR,
    "user_url" VARCHAR,
    "user_avatar_url" VARCHAR,
    "updated_at_date" TIMESTAMP
);
CREATE INDEX released_at_index ON issues( updated_at_date );
CREATE INDEX add_on_index ON issues( addon_id );
CREATE INDEX id_index ON issues( id );

