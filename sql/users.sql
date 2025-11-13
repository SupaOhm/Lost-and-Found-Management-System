CREATE USER admin_user IDENTIFIED BY 'admin_password';
GRANT ALL PRIVILEGES ON lost_found_db.* TO admin_user;
FLUSH PRIVILEGES;

CREATE USER staff_user IDENTIFIED BY 'staff_password';
GRANT SELECT, INSERT, UPDATE ON lost_found_db.* TO staff_user;
FLUSH PRIVILEGES;

CREATE USER user_user IDENTIFIED BY 'user_password';
GRANT SELECT ON lost_found_db.* TO user_user;
GRANT INSERT lost_found_db.claims TO user_user;
GRANT INSERT ON lost_found_db.lost_items TO user_user;
GRANT INSERT ON lost_found_db.found_items TO user_user;
FLUSH PRIVILEGES;