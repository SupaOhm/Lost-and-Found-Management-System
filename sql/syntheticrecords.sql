-- Synthetic records for Lost & Found Management System
use lost_found_db;
-- Users
INSERT INTO User (username, password, email, phone) VALUES
('alice', 'password1', 'alice@example.com', '1234567890'),
('bob', 'password2', 'bob@example.com', '2345678901'),
('charlie', 'password3', 'charlie@example.com', '3456789012');

-- Admins
INSERT INTO Admin (username, password, email) VALUES
('admin', '$2y$10$TzAzmz1sLVwY/eAbdKx2desbdQj.y2mCoJdAIm26jqttGZDZ8pvUe', 'admin1@example.com');

-- Lost Items
INSERT INTO LostItem (user_id, item_name, description, category, location, lost_date, status) VALUES
(1, 'Black Wallet', 'Lost near the library. Contains ID and cards.', 'Accessories', 'Library', '2025-11-01', 'pending'),
(2, 'Blue Backpack', 'Forgotten in the cafeteria.', 'Bags', 'Cafeteria', '2025-10-28', 'pending');

-- Found Items
INSERT INTO FoundItem (user_id, item_name, description, category, location, found_date, status) VALUES
(3, 'Red Umbrella', 'Found in the parking lot.', 'Accessories', 'Parking Lot', '2025-11-02', 'available'),
(1, 'Silver Watch', 'Discovered in the gym locker room.', 'Accessories', 'Gym', '2025-10-30', 'available');

-- Claim Requests
INSERT INTO ClaimRequest (found_id, user_id, description, status, claim_date, approved_by, approved_date) VALUES
(1, 2, 'I believe this umbrella is mine; it has a distinct sticker on the handle.', 'pending', '2025-11-03', NULL, NULL),
(2, 3, 'I think this watch belongs to me; it has an engraving on the back.', 'pending', '2025-11-05', NULL, NULL);