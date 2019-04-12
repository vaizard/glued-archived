# Glued

## Installation

Run

```
bash <(curl https://raw.githubusercontent.com/vaizard/glued/master/install.sh)
```

and follow the on-screen instructions. Once the installation succeeds, go to
https://example.com/glued/public/ 

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

## Contribute

Contributions welcomed, just fork Glued an make your first PR! Below a bunch of tips to help you start.

### UI 

Currently Glued relies a lot on

- bootstrap 4
- modular-admin-html (resp. our fork https://github.com/vaizard/modular-admin-glued)
- jquery
- rjsf

#### Hack modular-admin-html

```
git clone https://github.com/modularcode/modular-admin-html.git
cd modular-admin-html
npm install             # pull in dependencies
npm run build           # create the static build (content in /dist directory)
```

#### Hack rjsf & addons

```
git clone http://github.com/mozilla-services/react-jsonschema-form
cd react-jsonschema-form
npm install
npm run dist

git clone https://github.com/RxNT/react-jsonschema-form-extras
cd react-jsonschema-form-extras
npm install
npm run dist
```
