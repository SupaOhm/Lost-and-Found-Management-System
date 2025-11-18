delimiter $$

-- =============================================
-- USER MANAGEMENT PROCEDURES
-- =============================================

-- Get User by ID
CREATE PROCEDURE GetUserById(
    IN p_user_id BIGINT
)
BEGIN
    SELECT user_id, username, email, phone, created_at
    FROM User 
    WHERE user_id = p_user_id;
END$$

-- Register New User
CREATE PROCEDURE RegisterUser(
    IN p_username VARCHAR(50),
    IN p_email VARCHAR(100),
    IN p_password VARBINARY(255),
    IN p_phone VARBINARY(255)
)
BEGIN
    DECLARE user_id BIGINT;
    
    INSERT INTO User (username, email, password, phone)
    VALUES (p_username, p_email, p_password, p_phone);
    
    SET user_id = LAST_INSERT_ID();
    SELECT user_id;
END$$

-- Check if email exists
CREATE PROCEDURE CheckEmailExists(
    IN p_email VARCHAR(100)
)
BEGIN
    SELECT user_id FROM User WHERE email = p_email;
END$$

-- Get User by Email
CREATE PROCEDURE GetUserByEmail(
    IN p_email VARCHAR(100)
)
BEGIN
    SELECT user_id, username, email, password, phone, created_at
    FROM User 
    WHERE email = p_email
    LIMIT 1;
END$$

-- Update User Profile
CREATE PROCEDURE UpdateUserProfile(
    IN p_user_id BIGINT,
    IN p_username VARCHAR(50),
    IN p_email VARCHAR(100),
    IN p_phone VARBINARY(255)
)
BEGIN
    UPDATE User 
    SET username = p_username,
        email = p_email,
        phone = p_phone
    WHERE user_id = p_user_id;
END$$

-- =============================================
-- LOST & FOUND ITEMS PROCEDURES
-- =============================================

-- Report Lost Item
CREATE PROCEDURE ReportLostItem(
    IN p_user_id BIGINT,
    IN p_item_name VARCHAR(100),
    IN p_description TEXT,
    IN p_category VARCHAR(50),
    IN p_location VARCHAR(100),
    IN p_lost_date DATE
)
BEGIN
    INSERT INTO LostItem (user_id, item_name, description, category, location, lost_date)
    VALUES (p_user_id, p_item_name, p_description, p_category, p_location, p_lost_date);
END$$

-- Report Found Item
CREATE PROCEDURE ReportFoundItem(
    IN p_user_id BIGINT,
    IN p_item_name VARCHAR(100),
    IN p_description TEXT,
    IN p_category VARCHAR(50),
    IN p_location VARCHAR(100),
    IN p_found_date DATE
)
BEGIN
    INSERT INTO FoundItem (user_id, item_name, description, category, location, found_date)
    VALUES (p_user_id, p_item_name, p_description, p_category, p_location, p_found_date);
END$$

-- Get User's Lost Items Count
CREATE PROCEDURE GetUserLostItemsCount(
    IN p_user_id BIGINT
)
BEGIN
    SELECT COUNT(*) as total FROM LostItem WHERE user_id = p_user_id;
END$$

-- Get User's Found Items Count
CREATE PROCEDURE GetUserFoundItemsCount(
    IN p_user_id BIGINT
)
BEGIN
    SELECT COUNT(*) as total FROM FoundItem WHERE user_id = p_user_id;
END$$

-- Get User's Lost Items
CREATE PROCEDURE GetUserLostItems(
    IN p_user_id BIGINT
)
BEGIN
    SELECT lost_id AS item_id, item_name, description, category, location, status, lost_date, created_at
    FROM LostItem
    WHERE user_id = p_user_id
    ORDER BY created_at DESC;
END$$

-- Get User's Found Items
CREATE PROCEDURE GetUserFoundItems(
    IN p_user_id BIGINT
)
BEGIN
    SELECT found_id AS item_id, item_name, description, category, location, status, found_date, created_at
    FROM FoundItem
    WHERE user_id = p_user_id
    ORDER BY created_at DESC;
END$$

-- =============================================
-- CLAIM MANAGEMENT PROCEDURES
-- =============================================

-- View Pending Claims (Admin)
CREATE PROCEDURE ViewPendingClaimsWithFoundDetails()
BEGIN
    SELECT c.claim_id,
           u.username AS requester,
           c.description AS claim_description,
           c.claim_date,
           f.found_id,
           f.item_name,
           f.description,
           f.category,
           f.location,
           f.found_date,
           c.status
    FROM ClaimRequest c
    LEFT JOIN User u ON c.user_id = u.user_id
    LEFT JOIN FoundItem f ON c.found_id = f.found_id
    WHERE c.status = 'pending'
    ORDER BY c.claim_date DESC;
END$$

-- View Processed Claims (Admin/Staff)
CREATE PROCEDURE ViewProcessedClaimsWithDetails()
BEGIN
    SELECT c.claim_id,
           u.username AS requester,
           c.description AS claim_description,
           c.claim_date,
           c.status,
           c.approved_date,
           c.approver_id,
           c.approver_type,
           f.found_id,
           f.item_name,
           f.description,
           f.category,
           f.location,
           f.found_date,
           CASE 
               WHEN c.approver_type = 'admin' THEN a.username
               WHEN c.approver_type = 'staff' THEN s.username
               ELSE 'Unknown'
           END AS approver_name
    FROM ClaimRequest c
    LEFT JOIN User u ON c.user_id = u.user_id
    LEFT JOIN FoundItem f ON c.found_id = f.found_id
    LEFT JOIN Admin a ON c.approver_id = a.admin_id AND c.approver_type = 'admin'
    LEFT JOIN Staff s ON c.approver_id = s.staff_id AND c.approver_type = 'staff'
    WHERE c.status IN ('approved', 'rejected')
    ORDER BY c.approved_date DESC;
END$$

-- View Processed Claims by Staff (Staff specific)
CREATE PROCEDURE ViewStaffProcessedClaims(
    IN p_staff_id BIGINT
)
BEGIN
    SELECT c.claim_id,
           u.username AS requester,
           c.description AS claim_description,
           c.claim_date,
           c.status,
           c.approved_date,
           c.approver_id,
           c.approver_type,
           f.found_id,
           f.item_name,
           f.description,
           f.category,
           f.location,
           f.found_date,
           s.username AS approver_name
    FROM ClaimRequest c
    LEFT JOIN User u ON c.user_id = u.user_id
    LEFT JOIN FoundItem f ON c.found_id = f.found_id
    LEFT JOIN Staff s ON c.approver_id = s.staff_id AND c.approver_type = 'staff'
    WHERE c.status IN ('approved', 'rejected')
    AND c.approver_type = 'staff'
    AND c.approver_id = p_staff_id
    ORDER BY c.approved_date DESC;
END$$

-- Approve Claim (Admin or Staff)
CREATE PROCEDURE ApproveClaim(
    IN p_claim_id BIGINT,
    IN p_approver_id BIGINT,
    IN p_approver_type ENUM('admin', 'staff')
)
BEGIN
    UPDATE ClaimRequest
    SET status = 'approved',
        approver_id = p_approver_id,
        approver_type = p_approver_type,
        approved_date = NOW()
    WHERE claim_id = p_claim_id;
END$$

-- Reject Claim (Admin or Staff)
CREATE PROCEDURE RejectClaim(
    IN p_claim_id BIGINT,
    IN p_approver_id BIGINT,
    IN p_approver_type ENUM('admin', 'staff')
)
BEGIN
    UPDATE ClaimRequest
    SET status = 'rejected',
        approver_id = p_approver_id,
        approver_type = p_approver_type,
        approved_date = NOW()
    WHERE claim_id = p_claim_id;
END$$

-- =============================================
-- SEARCH FUNCTIONALITY PROCEDURES
-- =============================================

-- Get all active items (both lost and found)
CREATE PROCEDURE GetAllActiveItems()
BEGIN
    SELECT 
        'lost' as type,
        lost_id as id,
        item_name,
        description,
        category,
        location,
        lost_date as item_date,
        created_at
    FROM LostItem
    WHERE status = 'pending'
    
    UNION ALL
    
    SELECT 
        'found' as type,
        found_id as id,
        item_name,
        description,
        category,
        location,
        found_date as item_date,
        created_at
    FROM FoundItem
    WHERE status = 'available'
    
    ORDER BY created_at DESC;
END$$

-- Search items with filters
CREATE PROCEDURE SearchItems(
    IN p_search_term TEXT,
    IN p_category VARCHAR(50),
    IN p_location VARCHAR(100),
    IN p_type VARCHAR(10) -- 'lost', 'found', or NULL for both
)
BEGIN
    SELECT 
        'lost' as type,
        lost_id as id,
        item_name,
        description,
        category,
        location,
        lost_date as item_date,
        created_at
    FROM LostItem
    WHERE status = 'pending'
    AND (p_type IS NULL OR p_type = 'lost')
    AND (p_search_term IS NULL 
         OR item_name LIKE p_search_term 
         OR description LIKE p_search_term
         OR category LIKE p_search_term
         OR location LIKE p_search_term)
    AND (p_category IS NULL OR category = p_category)
    AND (p_location IS NULL OR location LIKE p_location)
    
    UNION ALL
    
    SELECT 
        'found' as type,
        found_id as id,
        item_name,
        description,
        category,
        location,
        found_date as item_date,
        created_at
    FROM FoundItem
    WHERE status = 'available'
    AND (p_type IS NULL OR p_type = 'found')
    AND (p_search_term IS NULL 
         OR item_name LIKE p_search_term 
         OR description LIKE p_search_term
         OR category LIKE p_search_term
         OR location LIKE p_search_term)
    AND (p_category IS NULL OR category = p_category)
    AND (p_location IS NULL OR location LIKE p_location)
    
    ORDER BY created_at DESC;
END$$

-- Get items by type (lost or found)
CREATE PROCEDURE GetItemsByType(
    IN p_type VARCHAR(10) -- 'lost' or 'found'
)
BEGIN
    IF p_type = 'lost' THEN
        SELECT 
            'lost' as type,
            lost_id as id,
            item_name,
            description,
            category,
            location,
            lost_date as item_date,
            created_at
        FROM LostItem
        WHERE status = 'pending'
        ORDER BY created_at DESC;
    ELSE
        SELECT 
            'found' as type,
            found_id as id,
            item_name,
            description,
            category,
            location,
            found_date as item_date,
            created_at
        FROM FoundItem
        WHERE status = 'available'
        ORDER BY created_at DESC;
    END IF;
END$$

-- Get distinct categories for filter
CREATE PROCEDURE GetItemCategories()
BEGIN
    SELECT DISTINCT category 
    FROM (
        SELECT category FROM LostItem WHERE category IS NOT NULL
        UNION 
        SELECT category FROM FoundItem WHERE category IS NOT NULL
    ) AS categories
    ORDER BY category;
END$$

-- =============================================
-- USER-RELATED STORED PROCEDURES
-- =============================================

-- Get count of claims made by a user
CREATE PROCEDURE GetUserClaimsCount(
    IN p_user_id BIGINT
)
BEGIN
    SELECT COUNT(*) as total 
    FROM ClaimRequest 
    WHERE user_id = p_user_id;
END$$

-- =============================================
-- CLAIM MANAGEMENT PROCEDURES
-- =============================================

-- Submit a new claim
CREATE PROCEDURE SubmitClaim(
    IN p_found_id BIGINT,
    IN p_user_id BIGINT,
    IN p_description TEXT
)
BEGIN
    DECLARE v_status VARCHAR(20);
    -- Check if found item exists and is claimable
    SELECT status INTO v_status FROM FoundItem WHERE found_id = p_found_id;
    IF v_status != 'available' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'This found item is not available for claiming';
    END IF;
    -- Insert the claim referencing the found item
    INSERT INTO ClaimRequest (found_id, user_id, description, status, claim_date)
    VALUES (p_found_id, p_user_id, p_description, 'pending', NOW());
    SELECT LAST_INSERT_ID() as claim_id;
END$$

-- Get claims by user
CREATE PROCEDURE GetUserClaims(
    IN p_user_id BIGINT
)
BEGIN
    SELECT 
        cr.claim_id,
        cr.claim_date,
        cr.status,
        cr.approved_date,
        fi.item_name as item_name,
        fi.description as item_description,
        fi.location as location,
        fi.found_date as item_date,
        u.username as reported_by
    FROM ClaimRequest cr
    LEFT JOIN FoundItem fi ON cr.found_id = fi.found_id
    LEFT JOIN User u ON fi.user_id = u.user_id
    WHERE cr.user_id = p_user_id
    ORDER BY cr.claim_date DESC;
END$$

CREATE PROCEDURE VerifyAdminLogin(IN p_username VARCHAR(50))
BEGIN
    SELECT admin_id, username, password FROM Admin WHERE username = p_username LIMIT 1;
END$$

-- Stored procedure to get admin by ID
CREATE PROCEDURE GetAdminById(IN p_admin_id BIGINT)
BEGIN
    SELECT admin_id, username, email, created_at FROM Admin WHERE admin_id = p_admin_id LIMIT 1;
END$$

-- =============================================
-- STAFF MANAGEMENT PROCEDURES
-- =============================================

-- Stored procedure to verify staff login
CREATE PROCEDURE VerifyStaffLogin(IN p_username VARCHAR(50))
BEGIN
    SELECT staff_id, username, password FROM Staff WHERE username = p_username LIMIT 1;
END$$

-- Stored procedure to get staff by ID
CREATE PROCEDURE GetStaffById(IN p_staff_id BIGINT)
BEGIN
    SELECT staff_id, username, email, full_name, phone, created_at FROM Staff WHERE staff_id = p_staff_id LIMIT 1;
END$$

-- =============================================
-- POTENTIAL MATCH PROCEDURES
-- =============================================

-- Find potential matches for user's lost items
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
            (CASE WHEN l.category = f.category THEN 2 ELSE 0 END) +
            (CASE WHEN l.location = f.location THEN 2 ELSE 0 END) +
            (CASE WHEN ABS(DATEDIFF(f.found_date, l.lost_date)) <= 7 THEN 2 ELSE 0 END) +
            (CASE WHEN l.item_name LIKE CONCAT('%', f.item_name, '%') OR f.item_name LIKE CONCAT('%', l.item_name, '%') THEN 4 ELSE 0 END)
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
    HAVING match_score >= 2
    ORDER BY match_score DESC, f.found_date DESC
END$$

-- Get match count for user
CREATE PROCEDURE GetUserMatchCount(
    IN p_user_id BIGINT
)
BEGIN
    SELECT COUNT(*) as match_count
    FROM (
        SELECT 
            l.lost_id,
            (
                (CASE WHEN l.category = f.category THEN 2 ELSE 0 END) +
                (CASE WHEN l.location = f.location THEN 2 ELSE 0 END) +
                (CASE WHEN ABS(DATEDIFF(f.found_date, l.lost_date)) <= 30 THEN 2 ELSE 0 END) +
                (CASE WHEN l.item_name LIKE CONCAT('%', f.item_name, '%') OR f.item_name LIKE CONCAT('%', l.item_name, '%') THEN 4 ELSE 0 END)
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
        HAVING match_score >= 2
    ) as matches;
END$$

-- Get detailed match information
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
            (CASE WHEN l.category = f.category THEN 2 ELSE 0 END) +
            (CASE WHEN l.location = f.location THEN 2 ELSE 0 END) +
            (CASE WHEN ABS(DATEDIFF(f.found_date, l.lost_date)) <= 7 THEN 2 ELSE 0 END) +
            (CASE WHEN l.item_name LIKE CONCAT('%', f.item_name, '%') OR f.item_name LIKE CONCAT('%', l.item_name, '%') THEN 4 ELSE 0 END)
        ) AS match_score
    FROM LostItem l
    INNER JOIN FoundItem f ON l.lost_id = p_lost_id AND f.found_id c= p_found_id
    LEFT JOIN User lu ON l.user_id = lu.user_id
    LEFT JOIN User fu ON f.user_id = fu.user_id
    WHERE l.status = 'pending'
    AND f.status = 'available';
END$$



-- =============================================
-- TRIGGERS
-- =============================================

-- Auto Update Item Status on Claim Approval
CREATE TRIGGER AfterClaimApproved
AFTER UPDATE ON ClaimRequest
FOR EACH ROW
BEGIN
    IF NEW.status = 'approved' THEN
        IF NEW.found_id IS NOT NULL THEN
            -- When a claim on a found item is approved, mark the found item as returned
            UPDATE FoundItem SET status = 'returned' WHERE found_id = NEW.found_id;
        END IF;
    END IF;
END$$


-- before admin delete user
CREATE TRIGGER BeforeUserDelete
BEFORE DELETE ON User
FOR EACH ROW
BEGIN
    DELETE FROM ClaimRequest 
    WHERE found_id IN (SELECT found_id FROM FoundItem WHERE user_id = OLD.user_id);
    DELETE FROM ClaimRequest WHERE user_id = OLD.user_id;
    DELETE FROM FoundItem WHERE user_id = OLD.user_id;
    DELETE FROM LostItem WHERE user_id = OLD.user_id;
END$$

delimiter ;
