delimiter $$


-- PROCEDURE: Register New User
CREATE PROCEDURE RegisterUser(
    IN p_username VARCHAR(50),
    IN p_password VARCHAR(255),
    IN p_email VARCHAR(100),
    IN p_phone VARCHAR(20)
)
BEGIN
    INSERT INTO User (username, password, email, phone)
    VALUES (p_username, p_password, p_email, p_phone);
END$$

-- PROCEDURE: User Login
CREATE PROCEDURE LoginUser(
    IN p_username VARCHAR(50),
    IN p_password VARCHAR(255)
)
BEGIN
    SELECT user_id, username, email
    FROM User
    WHERE username = p_username AND password = p_password;
END$$


-- PROCEDURE: Report Lost Item
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

-- PROCEDURE: Report Found Item
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

-- PROCEDURE: View Lost Items
CREATE PROCEDURE ViewLostItems()
BEGIN
    SELECT * FROM LostItem ORDER BY created_at DESC;
END$$

-- PROCEDURE: View Found Items
CREATE PROCEDURE ViewFoundItems()
BEGIN
    SELECT * FROM FoundItem ORDER BY created_at DESC;
END$$


-- PROCEDURE: Submit Claim Request
CREATE PROCEDURE SubmitClaimRequest(
    IN p_lost_id INT,
    IN p_found_id INT,
    IN p_user_id INT
)
BEGIN
    INSERT INTO ClaimRequest (lost_id, found_id, user_id)
    VALUES (p_lost_id, p_found_id, p_user_id);
END$$

-- PROCEDURE: View Claim Requests by User
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

-- PROCEDURE: View Pending Claims (Admin)
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

-- PROCEDURE: Approve Claim (Admin)
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

-- PROCEDURE: Reject Claim (Admin)
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

-- TRIGGER: Auto Update Item Status on Claim Approval
CREATE TRIGGER AfterClaimApproved
AFTER UPDATE ON ClaimRequest
FOR EACH ROW
BEGIN
    IF NEW.status = 'approved' THEN
        UPDATE LostItem SET status = 'claimed' WHERE lost_id = NEW.lost_id;
        UPDATE FoundItem SET status = 'returned' WHERE found_id = NEW.found_id;
    END IF;
END$$

-- TRIGGER: Reset Item Status if Claim Rejected
CREATE TRIGGER AfterClaimRejected
AFTER UPDATE ON ClaimRequest
FOR EACH ROW
BEGIN
    IF NEW.status = 'rejected' THEN
        UPDATE LostItem SET status = 'pending' WHERE lost_id = NEW.lost_id;
        UPDATE FoundItem SET status = 'available' WHERE found_id = NEW.found_id;
    END IF;
END$$

delimiter ;
