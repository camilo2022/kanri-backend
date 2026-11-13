CREATE PROCEDURE generate_username(IN person_id BIGINT UNSIGNED)
BEGIN

    DECLARE given_names VARCHAR(255);
    DECLARE family_names VARCHAR(255);
    DECLARE initials VARCHAR(50) DEFAULT '';
    DECLARE base_username VARCHAR(255);
    DECLARE last_suffix INT;

    SELECT LOWER(names), LOWER(TRIM(REGEXP_REPLACE(last_names, '[[:space:]]+', ' ')))
    INTO given_names, family_names
    FROM people
    WHERE id = person_id;

    SET given_names = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(given_names, 'á', 'a'), 'é', 'e'), 'í', 'i'), 'ó', 'o'), 'ú', 'u'), 'ü', 'u'), 'ñ', 'n');
    SET family_names = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(family_names, 'á', 'a'), 'é', 'e'), 'í', 'i'), 'ó', 'o'), 'ú', 'u'), 'ü', 'u'), 'ñ', 'n');

    WHILE family_names <> '' DO
        SET initials = CONCAT(initials, LEFT(family_names, 1));
        SET family_names = IF(LOCATE(' ', family_names) = 0, '', SUBSTRING(family_names, LOCATE(' ', family_names) + 1));
    END WHILE;

    SET base_username = CONCAT(REGEXP_REPLACE(given_names, '[^a-z0-9]', ''), REGEXP_REPLACE(initials, '[^a-z0-9]', ''));

    IF NOT EXISTS (SELECT 1 FROM users WHERE username = base_username) THEN
        SELECT base_username AS username;
    ELSE
        SELECT MAX(CAST(SUBSTRING(username, CHAR_LENGTH(base_username) + 1) AS UNSIGNED))
        INTO last_suffix
        FROM users
        WHERE username REGEXP CONCAT('^', base_username, '[0-9]+$');

        SELECT CONCAT(base_username, GREATEST(IFNULL(last_suffix, 1), 1) + 1) AS username;
    END IF;

END
