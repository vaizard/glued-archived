# Glued

## Installation

- clone this repo so that its accessible under https://yourserver.com/glued/
- example nginx default site conf is provided. handle with care so that you don't break other things on your server
- database dump is provided, expects mysql 5.7.15 or newer.
- copy glued/settings.php.example to glued/settings.php, modify the latter.
- look into https://yourserver.com/glued/public/ 

## Structure

- vendor (composer pulled dependencies)
- glued (the app)
  - bootstrap.php (main file)
  - dependencies.php (the DIC definition)
  - middleware.php (registers all middleware)
  - routes.php (registers all routes)
  - settings.php (cofiguration of your glued instance)
  - Classes/* (glued's classes)
  - Controllers/* (glued's controllers)
  - Middleware/* (glued's middleware except that wich is loaded via DIC/composer)
  - Views/* (twig-based views)
- public (document root for app users)
  - index.php (just includes glued/bootstrap.php)
  - all public files (css, images, js, etc.)
- extras (nginx configuration and db dump)
- logs (make sure its writable by php)
