DROP VIEW IF EXISTS kc_vw_user_role;

CREATE VIEW kc_vw_user_role AS
    SELECT uar.*, r.privileges, a.status account_status
        FROM kc_user_role uar
        LEFT JOIN kc_role r ON uar.role_id = r.id
        LEFT JOIN kc_account a ON uar.scope = 'ACCOUNT' AND uar.scope_id = a.account_id;
