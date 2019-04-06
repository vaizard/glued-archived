#!/usr/bin/env bash

eexit() {
  echo -e "\e[91;1m!!! ${1}"
  echo -e ""
  echo -e "***********************"
  echo -e "*** INSTALL FAILED ****"
  echo -e "***********************\e[0m"
  exit
}

eecho() {
  echo -e "\e[34;1m${1}\e[0m"
}

eprintf() {
  printf "\e[93;1m${1}\e[0m"
}


eecho "***********************"
eecho "*** GLUED INSTALLER ***"
eecho "***********************"
eecho ""

eprintf "*** Install glued? (y/n): "
read resp

if [ "$resp" != "y" ] ; then 
  eexit "exitting"
fi

if [ -d "glued" ] ; then
  eecho "--- Skipping git pull, glued dir exists"
else
  eecho "--- Pulling from github"
  git clone https://github.com/vaizard/glued.git
fi

pushd glued > /dev/null


eecho "--- Ensuring private and public keys are present"
if [ ! -f ./private/oauth/private.key ]; then
   openssl genrsa -out ./private/oauth/private.key 2048
fi

if [ ! -f ./private/oauth/public.key ]; then
   openssl rsa -in  ./private/oauth/private.key -pubout -out  ./private/oauth/public.key
fi

eecho "--- Ensuring propper ownership and access rights of files and directories"
chmod 777 ./logs/
chmod 777 -R ./private/
chmod 600 ./private/oauth/private.key

eecho "--- Ensuring fresh dependencies"
rm -rf ./vendor/*
composer install

eecho "--- Testing if glued/settings.php is set up"
if [ ! -f ./glued/settings.php ]; then
  eexit "! settings.php not present. Copy settings.example.php and set it up."
else
  pushd glued > /dev/null
  eecho "\$config = require '_preflight.php';" | php -a
  [[ $(echo "\$config = require '_preflight.php';" | php -a | grep 'ERROR') ]] && eexit "settings.php misconfigured"
  popd > /dev/null
fi

eecho "--- Testing if phinx.yml production environment is set up"
[[ $(php vendor/bin/phinx test -e production | grep 'success') ]] || eexit "prc"

eecho "--- Setting up the database"
php vendor/bin/phinx migrate -e production
popd > /dev/null

eecho ""
eecho "***********************"
eecho ""
eecho "DONE!"