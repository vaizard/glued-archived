# Glued

## Installation

- clone this repo so that its accessible under https://yourserver.com/glued/
- example nginx default site conf is provided. handle with care so that you don't break other things on your server
- database dump is provided, expects mysql 5.7.15 or newer.
- copy glued/config-example.php to glued/config.php, modify the latter.
- modify glued/routes.php (the part where db1 route is defined)
- look into https://yourserver.com/glued/public/ 

## Structure

- vendor (composer pulled dependencies)
- glued (the app)
  - bootstrap.php (the main file)
  - config.php (configuration included by bootstrap.php)
  - routes.php (definition of all routes)
  - Models/* (psr4-loaded models)
  - Views/* (twig-based views)
- public (document root for app users)
  - index.php (just includes glued/bootstrap.php)
  - all public files (css, images, js, etc.)
- extras (nginx configuration and db dump)
- logs (make sure its writable by php)