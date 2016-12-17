# Indexing on a JSON array

# Create a table
CREATE TABLE jsarr (
     c JSON,
     g VARCHAR(255) GENERATED ALWAYS AS (c->"$[2]"),
     INDEX i (g));

# Insert test values into json array 
INSERT INTO `jsarr` (`c`)
VALUES (
  '["JavaScript", "ES2015", "JSONlove"]'
);

# See column `g` for expected result "JSONlove"
SELECT * from `jsarr`;