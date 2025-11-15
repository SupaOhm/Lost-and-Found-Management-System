-- =============================================
-- POTENTIAL MATCH FEATURE - ADD TO DATABASE
-- Run this script to add the matching functionality
-- =============================================

USE lost_found_db;

DELIMITER $$

-- Find potential matches for user's lost items
DROP PROCEDURE IF EXISTS FindPotentialMatches$$
CREATE PROCEDURE FindPotentialMatches(
    IN p_user_id BIGINT
)
BEGIN
    SELECT 
        l.lost_id,
        l.item_name AS lost_item_name,
        l.description AS lost_description,
        l.category AS lost_category,
        l.location AS lost_location,
        l.lost_date,
        f.found_id,
        f.item_name AS found_item_name,
        f.description AS found_description,
        f.category AS found_category,
        f.location AS found_location,
        f.found_date,
        u.username AS finder_username,
        -- Calculate match score based on various factors
        (
            (CASE WHEN l.category = f.category THEN 3 ELSE 0 END) +
            (CASE WHEN l.location = f.location THEN 2 ELSE 0 END) +
            (CASE WHEN DATEDIFF(f.found_date, l.lost_date) BETWEEN 0 AND 7 THEN 2 ELSE 0 END) +
            (CASE WHEN l.item_name LIKE CONCAT('%', f.item_name, '%') OR f.item_name LIKE CONCAT('%', l.item_name, '%') THEN 2 ELSE 0 END)
        ) AS match_score
    FROM LostItem l
    INNER JOIN FoundItem f ON (
        l.category = f.category
        OR l.location = f.location
        OR l.item_name LIKE CONCAT('%', f.item_name, '%')
        OR f.item_name LIKE CONCAT('%', l.item_name, '%')
    )
    LEFT JOIN User u ON f.user_id = u.user_id
    WHERE l.user_id = p_user_id
    AND l.status = 'pending'
    AND f.status = 'available'
    AND l.lost_date <= f.found_date
    HAVING match_score >= 3
    ORDER BY match_score DESC, f.found_date DESC
    LIMIT 10;
END$$

-- Get match count for user
DROP PROCEDURE IF EXISTS GetUserMatchCount$$
CREATE PROCEDURE GetUserMatchCount(
    IN p_user_id BIGINT
)
BEGIN
    SELECT COUNT(*) as match_count
    FROM (
        SELECT 
            l.lost_id,
            (
                (CASE WHEN l.category = f.category THEN 3 ELSE 0 END) +
                (CASE WHEN l.location = f.location THEN 2 ELSE 0 END) +
                (CASE WHEN DATEDIFF(f.found_date, l.lost_date) BETWEEN 0 AND 7 THEN 2 ELSE 0 END) +
                (CASE WHEN l.item_name LIKE CONCAT('%', f.item_name, '%') OR f.item_name LIKE CONCAT('%', l.item_name, '%') THEN 2 ELSE 0 END)
            ) AS match_score
        FROM LostItem l
        INNER JOIN FoundItem f ON (
            l.category = f.category
            OR l.location = f.location
            OR l.item_name LIKE CONCAT('%', f.item_name, '%')
            OR f.item_name LIKE CONCAT('%', l.item_name, '%')
        )
        WHERE l.user_id = p_user_id
        AND l.status = 'pending'
        AND f.status = 'available'
        AND l.lost_date <= f.found_date
        HAVING match_score >= 3
    ) as matches;
END$$

-- Get detailed match information
DROP PROCEDURE IF EXISTS GetMatchDetails$$
CREATE PROCEDURE GetMatchDetails(
    IN p_lost_id BIGINT,
    IN p_found_id BIGINT
)
BEGIN
    SELECT 
        l.lost_id,
        l.item_name AS lost_item_name,
        l.description AS lost_description,
        l.category AS lost_category,
        l.location AS lost_location,
        l.lost_date,
        l.user_id AS lost_by_user_id,
        lu.username AS lost_by_username,
        lu.email AS lost_by_email,
        f.found_id,
        f.item_name AS found_item_name,
        f.description AS found_description,
        f.category AS found_category,
        f.location AS found_location,
        f.found_date,
        f.user_id AS found_by_user_id,
        fu.username AS found_by_username,
        fu.email AS found_by_email,
        (
            (CASE WHEN l.category = f.category THEN 3 ELSE 0 END) +
            (CASE WHEN l.location = f.location THEN 2 ELSE 0 END) +
            (CASE WHEN DATEDIFF(f.found_date, l.lost_date) BETWEEN 0 AND 7 THEN 2 ELSE 0 END) +
            (CASE WHEN l.item_name LIKE CONCAT('%', f.item_name, '%') OR f.item_name LIKE CONCAT('%', l.item_name, '%') THEN 2 ELSE 0 END)
        ) AS match_score
    FROM LostItem l
    INNER JOIN FoundItem f ON l.lost_id = p_lost_id AND f.found_id = p_found_id
    LEFT JOIN User lu ON l.user_id = lu.user_id
    LEFT JOIN User fu ON f.user_id = fu.user_id
    WHERE l.status = 'pending'
    AND f.status = 'available';
END$$

DELIMITER ;

-- Verification: Test the procedures
-- SELECT 'Stored procedures for potential matching feature have been created successfully!' AS Status;
