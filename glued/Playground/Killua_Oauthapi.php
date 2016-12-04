<?php

namespace Glued\Playground;
use Glued\Controllers\Controller;

class Killua_Oauthapi extends Controller
{
}

// Random info:
// https://alexbilbie.com/2013/02/developing-an-oauth2-authorization-server/
// https://bshaffer.github.io/oauth2-server-php-docs/cookbook/ (not our library, but sql tables)






//https://oauth2.thephpleague.com/installation/
// Installation
// openssl genrsa -out private.key 1024
// openssl rsa -in private.key -pubout -out public.key

//The private key must be kept secret (i.e. out of the web-root of the authorization server). The authorization server also requires the public key.

//If a passphrase has been used to generate private key it must be provided to the authorization server.

//The public key should be distributed to any services (for example resource servers) that validate access tokens.








// https://oauth2.thephpleague.com/resource-server/securing-your-api/


// Wherever you intialize your objects, initialize a new instance of 
// the resource server with the storage interfaces:

// Init our repositories
$accessTokenRepository = new AccessTokenRepository(); // instance of AccessTokenRepositoryInterface

// Path to authorization server's public key
$publicKey = '/var/www/html/glued/private/oauth/public.key';
        
// Setup the authorization server
$server = new \League\OAuth2\Server\ResourceServer(
    $accessTokenRepository,
    $publicKeyPath
);

// Then add the middleware to your stack:
new \League\OAuth2\Server\Middleware\ResourceServerMiddleware($server);
