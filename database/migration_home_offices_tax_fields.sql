ALTER TABLE home_offices
  ADD COLUMN tax_label VARCHAR(40) NULL AFTER registration_number,
  ADD COLUMN tax_number VARCHAR(120) NULL AFTER tax_label;
