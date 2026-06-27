ALTER TABLE home_offices
  ADD COLUMN registration_label VARCHAR(40) NULL AFTER phone,
  ADD COLUMN registration_number VARCHAR(120) NULL AFTER registration_label;
