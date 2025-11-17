
-- Synthetic records for Lost & Found Management System
USE lost_found_db;

-- Users (passwords hashed, phones encrypted as hex)
INSERT INTO User (username, password, email, phone) VALUES
('alice', '$2y$10$t0PF6AJ5zjbeXUOI6th1B.XTDYNYFc0ysOowqSPd790/NemWts0d.', 'alice@mail.com', 'dWF2ZDdjcFZXWG0rMDZwdlJvSCtEUT09'),
('bob', '$2y$10$t0PF6AJ5zjbeXUOI6th1B.XTDYNYFc0ysOowqSPd790/NemWts0d.', 'bob@mail.com', 'dWF2ZDdjcFZXWG0rMDZwdlJvSCtEUT09'),
('charlie', '$2y$10$t0PF6AJ5zjbeXUOI6th1B.XTDYNYFc0ysOowqSPd790/NemWts0d.', 'charlie@mail.com', 'dWF2ZDdjcFZXWG0rMDZwdlJvSCtEUT09'),
('diana', '$2y$10$t0PF6AJ5zjbeXUOI6th1B.XTDYNYFc0ysOowqSPd790/NemWts0d.', 'diana@mail.com', 'dWF2ZDdjcFZXWG0rMDZwdlJvSCtEUT09'),
('edward', '$2y$10$t0PF6AJ5zjbeXUOI6th1B.XTDYNYFc0ysOowqSPd790/NemWts0d.', 'edward@mail.com', 'dWF2ZDdjcFZXWG0rMDZwdlJvSCtEUT09'),
('fiona', '$2y$10$t0PF6AJ5zjbeXUOI6th1B.XTDYNYFc0ysOowqSPd790/NemWts0d.', 'fiona@mail.com', 'dWF2ZDdjcFZXWG0rMDZwdlJvSCtEUT09');
-- Admins (password hashed)
INSERT INTO Admin (username, password, email) VALUES
('admin', '$2y$10$TzAzmz1sLVwY/eAbdKx2desbdQj.y2mCoJdAIm26jqttGZDZ8pvUe', 'admin1@example.com');

-- Staff (passwords hashed, phones encrypted as hex)
INSERT INTO Staff (username, password, email, full_name, phone) VALUES
('staff1', '$2y$10$588zfCVVCMrCTShDlFcmju8Rgfr.l4cWDvZNLvJ4h.FUfgH4lWHQu', 'staff1@mail.com', 'Staff One', 'dWF2ZDdjcFZXWG0rMDZwdlJvSCtEUT09'),
('staff2', '$2y$10$588zfCVVCMrCTShDlFcmju8Rgfr.l4cWDvZNLvJ4h.FUfgH4lWHQu', 'staff2@mail.com', 'Staff Two', 'dWF2ZDdjcFZXWG0rMDZwdlJvSCtEUT09'),
('staff3', '$2y$10$588zfCVVCMrCTShDlFcmju8Rgfr.l4cWDvZNLvJ4h.FUfgH4lWHQu', 'staff3@mail.com', 'Staff Three', 'dWF2ZDdjcFZXWG0rMDZwdlJvSCtEUT09'),
('staff4', '$2y$10$588zfCVVCMrCTShDlFcmju8Rgfr.l4cWDvZNLvJ4h.FUfgH4lWHQu', 'staff4@mail.com', 'Staff Four', 'dWF2ZDdjcFZXWG0rMDZwdlJvSCtEUT09'),
('staff5', '$2y$10$588zfCVVCMrCTShDlFcmju8Rgfr.l4cWDvZNLvJ4h.FUfgH4lWHQu', 'staff5@mail.com', 'Staff Five', 'dWF2ZDdjcFZXWG0rMDZwdlJvSCtEUT09'),
('staff6', '$2y$10$588zfCVVCMrCTShDlFcmju8Rgfr.l4cWDvZNLvJ4h.FUfgH4lWHQu', 'staff6@mail.com', 'Staff Six', 'dWF2ZDdjcFZXWG0rMDZwdlJvSCtEUT09');
-- Lost Items
INSERT INTO LostItem (user_id, item_name, description, category, location, lost_date, status) VALUES
(1, 'Black Wallet', 'Lost near the library. Contains ID and cards.', 'Accessories', 'Library', '2025-11-01', 'pending'),
(2, 'Blue Backpack', 'Forgotten in the cafeteria.', 'Bags', 'Cafeteria', '2025-10-28', 'pending'),
(3, 'Green Water Bottle', 'Left in classroom 101.', 'Accessories', 'Classroom 101', '2025-11-02', 'pending'),
(4, 'Red Notebook', 'Lost in the main hall.', 'Stationery', 'Main Hall', '2025-11-03', 'pending'),
(5, 'Yellow Scarf', 'Dropped near the bus stop.', 'Clothing', 'Bus Stop', '2025-11-04', 'pending'),
(6, 'Purple Pen', 'Lost in the library.', 'Stationery', 'Library', '2025-11-05', 'pending');

-- Found Items
INSERT INTO FoundItem (user_id, item_name, description, category, location, found_date, status) VALUES
(1, 'Silver Watch', 'Discovered in the gym locker room.', 'Accessories', 'Gym', '2025-10-30', 'available'),
(2, 'Red Umbrella', 'Found in the parking lot.', 'Accessories', 'Parking Lot', '2025-11-02', 'available'),
(3, 'Blue Cap', 'Found in the cafeteria.', 'Clothing', 'Cafeteria', '2025-11-06', 'available'),
(4, 'Green Bag', 'Found in classroom 101.', 'Bags', 'Classroom 101', '2025-11-07', 'available'),
(5, 'Black Glasses', 'Found in the main hall.', 'Accessories', 'Main Hall', '2025-11-08', 'available'),
(6, 'Yellow Book', 'Found near the bus stop.', 'Stationery', 'Bus Stop', '2025-11-09', 'available');

-- Claim Requests
INSERT INTO ClaimRequest (found_id, user_id, description, status, claim_date, approver_id, approver_type, approved_date) VALUES
(1, 2, 'I believe this watch is mine; it has a distinct scratch.', 'pending', '2025-11-03', NULL, NULL, NULL),
(2, 3, 'This umbrella belongs to me; it has a sticker.', 'pending', '2025-11-05', NULL, NULL, NULL),
(3, 4, 'I lost my cap in the cafeteria.', 'pending', '2025-11-07', NULL, NULL, NULL),
(4, 5, 'That green bag is mine.', 'pending', '2025-11-08', NULL, NULL, NULL),
(5, 6, 'Those glasses are mine.', 'pending', '2025-11-09', NULL, NULL, NULL),
(6, 1, 'I lost a yellow book near the bus stop.', 'pending', '2025-11-10', NULL, NULL, NULL);