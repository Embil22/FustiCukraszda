-- Meglevo adatbazis frissitese az uj termekkategoriakhoz.
-- Az ALTER TABLE megorzi a mar rogzitett termekeket.
USE fusti_cukraszda;

ALTER TABLE termekek
  MODIFY COLUMN kategoria ENUM(
    'Szeletes sütemények',
    'Alkalmi Torták',
    'Aprósütemények',
    'Fagylalt',
    'Sütifree',
    'Tejszínes torták',
    'Főzött krémes torták',
    'Különleges torták',
    'Kreatív díszítésű torták',
    'Pohárkrém'
  ) NOT NULL;
