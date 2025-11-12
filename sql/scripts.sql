delimiter $$

-- =============================================
-- USER MANAGEMENT PROCEDURES
-- =============================================

-- Get User by ID
CREATE PROCEDURE GetUserById(
    IN p_user_id INT
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
    IN p_password VARCHAR(255),
    IN p_phone VARCHAR(20)
)
BEGIN
    DECLARE user_id INT;
    
    INSERT INTO User (username, email, password, phone)
    VALUES (p_username, p_email, p_password, p_phone);
    
    SET user_id = LAST_INSERT_ID();
    SELECT user_id;
END$$

-- User Login
CREATE PROCEDURE LoginUser(
    IN p_username VARCHAR(50),
    IN p_password VARCHAR(255)
)
BEGIN
    SELECT user_id, username, email
    FROM User
    WHERE username = p_username AND password = p_password;
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
    IN p_user_id INT,
    IN p_username VARCHAR(50),
    IN p_email VARCHAR(100),
    IN p_phone VARCHAR(20)
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
    IN p_user_id INT,
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
    IN p_user_id INT,
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

-- View Lost Items
CREATE PROCEDURE ViewLostItems()
BEGIN
    SELECT * FROM LostItem ORDER BY created_at DESC;
END$$

-- View Found Items
CREATE PROCEDURE ViewFoundItems()
BEGIN
    SELECT * FROM FoundItem ORDER BY created_at DESC;
END$$

-- Get User's Lost Items Count
CREATE PROCEDURE GetUserLostItemsCount(
    IN p_user_id INT
)
BEGIN
    SELECT COUNT(*) as total FROM LostItem WHERE user_id = p_user_id;
END$$

-- Get User's Found Items Count
CREATE PROCEDURE GetUserFoundItemsCount(
    IN p_user_id INT
)
BEGIN
    SELECT COUNT(*) as total FROM FoundItem WHERE user_id = p_user_id;
END$$

-- =============================================
-- CLAIM MANAGEMENT PROCEDURES
-- =============================================

-- Submit Claim Request
CREATE PROCEDURE SubmitClaimRequest(
    IN p_lost_id INT,
    IN p_found_id INT,
    IN p_user_id INT
)
BEGIN
    INSERT INTO ClaimRequest (lost_id, found_id, user_id)
    VALUES (p_lost_id, p_found_id, p_user_id);
END$$

-- View Claim Requests by User
CREATE PROCEDURE ViewUserClaims(
    IN p_user_id INT
)
BEGIN
    SELECT c.claim_id, l.item_name AS lost_item, f.item_name AS found_item, c.status, c.claim_date
    FROM ClaimRequest c
    JOIN LostItem l ON c.lost_id = l.lost_id
    JOIN FoundItem f ON c.found_id = f.found_id
    WHERE c.user_id = p_user_id
    ORDER BY c.claim_date DESC;
END$$

-- View Pending Claims (Admin)
CREATE PROCEDURE ViewPendingClaims()
BEGIN
    SELECT c.claim_id, u.username AS requester, l.item_name AS lost_item, f.item_name AS found_item, c.status, c.claim_date
    FROM ClaimRequest c
    JOIN User u ON c.user_id = u.user_id
    JOIN LostItem l ON c.lost_id = l.lost_id
    JOIN FoundItem f ON c.found_id = f.found_id
    WHERE c.status = 'pending'
    ORDER BY c.claim_date DESC;
END$$

-- Approve Claim (Admin)
CREATE PROCEDURE ApproveClaim(
    IN p_claim_id INT,
    IN p_admin_id INT
)
BEGIN
    UPDATE ClaimRequest
    SET status = 'approved',
        approved_by = p_admin_id,
        approved_date = NOW()
    WHERE claim_id = p_claim_id;
END$$

-- Reject Claim (Admin)
CREATE PROCEDURE RejectClaim(
    IN p_claim_id INT,
    IN p_admin_id INT
)
BEGIN
    UPDATE ClaimRequest
    SET status = 'rejected',
        approved_by = p_admin_id,
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
-- ADMIN MANAGEMENT PROCEDURES
-- =============================================

-- Check if admin username exists
CREATE PROCEDURE CheckAdminUsernameExists(
    IN p_username VARCHAR(50)
)
BEGIN
    SELECT admin_id FROM Admin WHERE username = p_username;
END$$

-- Register new admin
CREATE PROCEDURE RegisterAdmin(
    IN p_username VARCHAR(50),
    IN p_password VARCHAR(255),
    IN p_email VARCHAR(100)
)
BEGIN
    INSERT INTO Admin (username, password, email)
    VALUES (p_username, p_password, p_email);
    
    SELECT LAST_INSERT_ID() as admin_id;
END$$

-- =============================================
-- USER-RELATED STORED PROCEDURES
-- =============================================

-- Get count of claims made by a user
CREATE PROCEDURE GetUserClaimsCount(
    IN p_user_id INT
)
BEGIN
    SELECT COUNT(*) as total 
    FROM ClaimRequest 
    WHERE user_id = p_user_id;
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
        UPDATE LostItem SET status = 'claimed' WHERE lost_id = NEW.lost_id;
        UPDATE FoundItem SET status = 'returned' WHERE found_id = NEW.found_id;
    END IF;
END$$

-- Reset Item Status if Claim Rejected
CREATE TRIGGER AfterClaimRejected
AFTER UPDATE ON ClaimRequest
FOR EACH ROW
BEGIN
    IF NEW.status = 'rejected' THEN
        UPDATE LostItem SET status = 'pending' WHERE lost_id = NEW.lost_id;
        UPDATE FoundItem SET status = 'available' WHERE found_id = NEW.found_id;
    END IF;
END$$


-- =============================================
-- CLAIM MANAGEMENT PROCEDURES
-- =============================================

-- Submit a new claim
CREATE PROCEDURE SubmitClaim(
    IN p_item_type VARCHAR(10),  -- 'lost' or 'found'
    IN p_item_id INT,
    IN p_user_id INT,
    IN p_description TEXT,
    IN p_proof_details TEXT
)
BEGIN
    DECLARE v_lost_id INT DEFAULT NULL;
    DECLARE v_found_id INT DEFAULT NULL;
    DECLARE v_status VARCHAR(20);
    
    -- Set the appropriate ID based on item type
    IF p_item_type = 'lost' THEN
        SET v_lost_id = p_item_id;
        -- Check if item exists and is claimable
        SELECT status INTO v_status FROM LostItem WHERE lost_id = p_item_id;
        IF v_status != 'pending' THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'This lost item is not available for claiming';
        END IF;
    ELSEIF p_item_type = 'found' THEN
        SET v_found_id = p_item_id;
        -- Check if item exists and is claimable
        SELECT status INTO v_status FROM FoundItem WHERE found_id = p_item_id;
        IF v_status != 'available' THEN
            SIGNAL SQLSTATE '45001' SET MESSAGE_TEXT = 'This found item is not available for claiming';
        END IF;
    ELSE
        SIGNAL SQLSTATE '45002' SET MESSAGE_TEXT = 'Invalid item type';
    END IF;
    
    -- Insert the claim
    INSERT INTO ClaimRequest (
        lost_id,
        found_id,
        user_id,
        claim_description,
        proof_details,
        status,
        claim_date
    ) VALUES (
        v_lost_id,
        v_found_id,
        p_user_id,
        p_description,
        p_proof_details,
        'pending',
        NOW()
    );
    
    -- Update item status
    IF p_item_type = 'lost' THEN
        UPDATE LostItem SET status = 'pending_verification' WHERE lost_id = p_item_id;
    ELSE
        UPDATE FoundItem SET status = 'pending_claim' WHERE found_id = p_item_id;
    END IF;
    
    SELECT LAST_INSERT_ID() as claim_id;
END$$

-- Get claims by user
CREATE PROCEDURE GetUserClaims(
    IN p_user_id INT
)
BEGIN
    SELECT 
        cr.claim_id,
        cr.claim_date,
        cr.status,
        cr.approved_date,
        CASE 
            WHEN cr.lost_id IS NOT NULL THEN 'lost'
            ELSE 'found'
        END as item_type,
        COALESCE(li.item_name, fi.item_name) as item_name,
        COALESCE(li.description, fi.description) as description,
        COALESCE(li.location, fi.location) as location,
        COALESCE(li.lost_date, fi.found_date) as item_date,
        u.username as reported_by
    FROM ClaimRequest cr
    LEFT JOIN LostItem li ON cr.lost_id = li.lost_id
    LEFT JOIN FoundItem fi ON cr.found_id = fi.found_id
    LEFT JOIN User u ON COALESCE(li.user_id, fi.user_id) = u.user_id
    WHERE cr.user_id = p_user_id
    ORDER BY cr.claim_date DESC;
END$$

-- Get claim details
CREATE PROCEDURE GetClaimDetails(
    IN p_claim_id INT,
    IN p_user_id INT
)
BEGIN
    SELECT 
        cr.claim_id,
        cr.claim_date,
        cr.status,
        cr.claim_description,
        cr.proof_details,
        cr.approved_date,
        cr.admin_notes,
        CASE 
            WHEN cr.lost_id IS NOT NULL THEN 'lost'
            ELSE 'found'
        END as item_type,
        COALESCE(li.item_name, fi.item_name) as item_name,
        COALESCE(li.description, fi.description) as description,
        COALESCE(li.category, fi.category) as category,
        COALESCE(li.location, fi.location) as location,
        COALESCE(li.lost_date, fi.found_date) as item_date,
        u.username as reported_by,
        u.email as reporter_email,
        u.phone as reporter_phone
    FROM ClaimRequest cr
    LEFT JOIN LostItem li ON cr.lost_id = li.lost_id
    LEFT JOIN FoundItem fi ON cr.found_id = fi.found_id
    LEFT JOIN User u ON COALESCE(li.user_id, fi.user_id) = u.user_id
    WHERE cr.claim_id = p_claim_id 
    AND (cr.user_id = p_user_id OR p_user_id IS NULL);
END$$

CREATE PROCEDURE VerifyAdminLogin(IN p_username VARCHAR(50))
BEGIN
    SELECT admin_id, username, password FROM Admin WHERE username = p_username LIMIT 1;
END$$

-- Stored procedure to get admin by ID
CREATE PROCEDURE GetAdminById(IN p_admin_id INT)
BEGIN
    SELECT admin_id, username, email, created_at FROM Admin WHERE admin_id = p_admin_id LIMIT 1;
END$$

delimiter ;