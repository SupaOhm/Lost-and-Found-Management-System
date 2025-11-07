
-- TRIGGER 1: AUTO UPDATE FOUND ITEM WHEN CLAIM APPROVED
DELIMITER $$
CREATE TRIGGER trg_claim_approved
AFTER UPDATE ON claims
FOR EACH ROW
BEGIN
    IF NEW.claim_status = 'approved' THEN
        UPDATE found_items
        SET status = 'claimed'
        WHERE found_id = NEW.found_id;
    END IF;
END$$
DELIMITER ;



-- TRIGGER 2: AUTO UPDATE FOUND ITEM WHEN CLAIM REJECTED
DELIMITER $$
CREATE TRIGGER trg_claim_rejected
AFTER UPDATE ON claims
FOR EACH ROW
BEGIN
    IF NEW.claim_status = 'rejected' THEN
        UPDATE found_items
        SET status = 'available'
        WHERE found_id = NEW.found_id;
    END IF;
END$$
DELIMITER ;



-- PROCEDURE 1: ADD LOST ITEM
DELIMITER $$
CREATE PROCEDURE add_lost_item(
    IN p_user_id INT,
    IN p_item_name VARCHAR(100),
    IN p_description TEXT,
    IN p_category VARCHAR(50),
    IN p_lost_date DATE,
    IN p_location VARCHAR(255)
)
BEGIN
    INSERT INTO lost_items (user_id, item_name, description, category, lost_date, location, status)
    VALUES (p_user_id, p_item_name, p_description, p_category, p_lost_date, p_location, 'lost');
END$$
DELIMITER ;



-- PROCEDURE 2: ADD FOUND ITEM
DELIMITER $$
CREATE PROCEDURE add_found_item(
    IN p_user_id INT,
    IN p_item_name VARCHAR(100),
    IN p_description TEXT,
    IN p_category VARCHAR(50),
    IN p_found_date DATE,
    IN p_location VARCHAR(255)
)
BEGIN
    INSERT INTO found_items (user_id, item_name, description, category, found_date, location, status)
    VALUES (p_user_id, p_item_name, p_description, p_category, p_found_date, p_location, 'available');
END$$
DELIMITER ;



-- PROCEDURE 3: SUBMIT CLAIM
DELIMITER $$
CREATE PROCEDURE submit_claim(
    IN p_user_id INT,
    IN p_found_id INT,
    IN p_reason TEXT
)
BEGIN
    INSERT INTO claims (user_id, found_id, reason, claim_status, claim_date)
    VALUES (p_user_id, p_found_id, p_reason, 'pending', NOW());
END$$
DELIMITER ;



-- PROCEDURE 4: APPROVE CLAIM
DELIMITER $$
CREATE PROCEDURE approve_claim(IN p_claim_id INT)
BEGIN
    UPDATE claims
    SET claim_status = 'approved'
    WHERE claim_id = p_claim_id;
END$$
DELIMITER ;



-- PROCEDURE 5: REJECT CLAIM
DELIMITER $$
CREATE PROCEDURE reject_claim(IN p_claim_id INT)
BEGIN
    UPDATE claims
    SET claim_status = 'rejected'
    WHERE claim_id = p_claim_id;
END$$
DELIMITER ;



-- VIEW 1: ALL ITEMS (LOST + FOUND)
CREATE VIEW all_items_view AS
SELECT 
    'Lost' AS type,
    lost_id AS item_id,
    item_name,
    description,
    category,
    location,
    lost_date AS date,
    status,
    user_id
FROM lost_items
UNION
SELECT 
    'Found' AS type,
    found_id AS item_id,
    item_name,
    description,
    category,
    location,
    found_date AS date,
    status,
    user_id
FROM found_items;



-- VIEW 2: CLAIMS OVERVIEW
CREATE VIEW claims_overview AS
SELECT 
    c.claim_id,
    c.claim_status,
    c.claim_date,
    u.full_name AS claimant_name,
    f.item_name AS found_item,
    f.status AS item_status
FROM claims c
JOIN users u ON c.user_id = u.user_id
JOIN found_items f ON c.found_id = f.found_id;
