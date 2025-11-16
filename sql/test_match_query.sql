-- Test query to see if matches exist (run this in MySQL to debug)
USE lost_found_db;

-- Check raw lost items
SELECT 'Lost Items:' as info, lost_id, user_id, item_name, category, location, lost_date, status FROM LostItem;

-- Check raw found items  
SELECT 'Found Items:' as info, found_id, user_id, item_name, category, location, found_date, status FROM FoundItem;

-- Check potential matches with scoring details
SELECT 
    l.lost_id,
    l.user_id as lost_user_id,
    l.item_name AS lost_name,
    l.category AS lost_cat,
    l.location AS lost_loc,
    l.lost_date,
    l.status as lost_status,
    f.found_id,
    f.user_id as found_user_id,
    f.item_name AS found_name,
    f.category AS found_cat,
    f.location AS found_loc,
    f.found_date,
    f.status as found_status,
    (CASE WHEN l.category = f.category THEN 3 ELSE 0 END) as cat_score,
    (CASE WHEN l.location = f.location THEN 2 ELSE 0 END) as loc_score,
    (CASE WHEN DATEDIFF(f.found_date, l.lost_date) BETWEEN 0 AND 7 THEN 2 ELSE 0 END) as date_score,
    (CASE WHEN l.item_name LIKE CONCAT('%', f.item_name, '%') OR f.item_name LIKE CONCAT('%', l.item_name, '%') THEN 2 ELSE 0 END) as name_score,
    (
        (CASE WHEN l.category = f.category THEN 3 ELSE 0 END) +
        (CASE WHEN l.location = f.location THEN 2 ELSE 0 END) +
        (CASE WHEN DATEDIFF(f.found_date, l.lost_date) BETWEEN 0 AND 7 THEN 2 ELSE 0 END) +
        (CASE WHEN l.item_name LIKE CONCAT('%', f.item_name, '%') OR f.item_name LIKE CONCAT('%', l.item_name, '%') THEN 2 ELSE 0 END)
    ) AS total_score,
    DATEDIFF(f.found_date, l.lost_date) as date_diff
FROM LostItem l
INNER JOIN FoundItem f ON (
    l.category = f.category
    OR l.location = f.location
    OR l.item_name LIKE CONCAT('%', f.item_name, '%')
    OR f.item_name LIKE CONCAT('%', l.item_name, '%')
)
WHERE l.status = 'pending'
AND f.status = 'available'
ORDER BY total_score DESC;
