
CREATE USER admin_user IDENTIFIED BY 'admin_password';
GRANT ALL PRIVILEGES ON lost_found_db.* TO admin_user;
FLUSH PRIVILEGES;

CREATE USER staff_user IDENTIFIED BY 'staff_password';
GRANT SELECT ON lost_found_db.* TO staff_user;
GRANT ALL PRIVILEGES ON lost_found_db.LostItem TO staff_user;
GRANT ALL PRIVILEGES ON lost_found_db.FoundItem TO staff_user;
GRANT ALL PRIVILEGES ON lost_found_db.ClaimRequest TO staff_user;
FLUSH PRIVILEGES;

CREATE USER user_user IDENTIFIED BY 'user_password';
GRANT SELECT ON lost_found_db.* TO user_user;
GRANT INSERT, DELETE, UPDATE ON lost_found_db.LostItem TO user_user;
GRANT INSERT, DELETE, UPDATE ON lost_found_db.FoundItem TO user_user;
GRANT INSERT, DELETE ON lost_found_db.ClaimRequest TO user_user;
GRANT UPDATE ON lost_found_db.User TO user_user;
FLUSH PRIVILEGES;

GRANT EXECUTE ON lost_found_db.* TO user_user;
GRANT EXECUTE ON lost_found_db.* TO staff_user;
GRANT EXECUTE ON lost_found_db.* TO admin_user;
FLUSH PRIVILEGES;