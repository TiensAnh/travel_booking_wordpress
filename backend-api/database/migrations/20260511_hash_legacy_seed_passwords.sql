-- Hash legacy plaintext demo passwords before deploying the login code
-- that only accepts bcrypt password hashes.

UPDATE admins
SET password = CASE
  WHEN email = 'admin@adntravel.vn'
    THEN '$2y$10$GUNe3V00b660g9qp7rs.bOoE9i2YYMauQHK8LVFg1/cQcGJV4GIke'
  WHEN email = 'ops@adntravel.vn'
    THEN '$2y$10$EUSk4zUCNN3lPz.Y.g7s9.W2eIaMgJZQk.H/p8/50.kg/pFOAtkXu'
  ELSE password
END
WHERE email IN ('admin@adntravel.vn', 'ops@adntravel.vn')
  AND password NOT LIKE '$2a$%'
  AND password NOT LIKE '$2b$%'
  AND password NOT LIKE '$2y$%';

UPDATE users
SET password = CASE email
  WHEN 'staff1@gmail.com'
    THEN '$2y$10$6AhqTu1LlFVaE4blMP/JeOZioBpkQ7Oo6RzAOc.5oHNhbERLSf0ai'
  WHEN 'staff@gmail.com'
    THEN '$2y$10$wuU77k50P0WrkBABdBiia.bABNH3SSM/BtElSPK6BmK5kjxjGafOG'
  WHEN 'user1@gmail.com'
    THEN '$2y$10$BqAMnB9YUUbOv673ZinoCucFTsoY6PgMGCosLlyUA/YCb/NrP/MaW'
  WHEN 'user2@gmail.com'
    THEN '$2y$10$OvDdYwi81alQgR.YmxeBZeaHhmlq/fNdoNSZb1AgalTlo08yhkZL.'
  WHEN 'user3@gmail.com'
    THEN '$2y$10$nEAgBZ.CD7eejEXPOuVXVuF66pZr/.dlc7V0SZlUxcjGlOujSFRZK'
  WHEN 'user4@gmail.com'
    THEN '$2y$10$ES5Yf77RVS75Uv2p9GLapebmN5YyiElJ7sPuEDTZ8w5KsWHke5XGS'
  WHEN 'user5@gmail.com'
    THEN '$2y$10$m1H5r3crWa2G5nJrrisq/.8IPhq3POLsp1l7.O9XteMdN7/.ve6vS'
  WHEN 'user6@gmail.com'
    THEN '$2y$10$pp9n0tAAKOYAuLtd2P8O1.5JC.4wOBeX7IE5zp3P6NFgDmRR451yy'
  ELSE password
END
WHERE email IN (
  'staff1@gmail.com',
  'staff@gmail.com',
  'user1@gmail.com',
  'user2@gmail.com',
  'user3@gmail.com',
  'user4@gmail.com',
  'user5@gmail.com',
  'user6@gmail.com'
)
AND password NOT LIKE '$2a$%'
AND password NOT LIKE '$2b$%'
AND password NOT LIKE '$2y$%';
