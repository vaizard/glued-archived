#!/usr/bin/env bash

httpduser="www-data"
pkeyowner="root"

eexit() {
  echo "${1}"
  exit
}

echo ""
echo "GLUED CONFIGURATION SCRIPT"
echo ""

printf "changing ownership of ./logs/ to ${httpuser} ... " ; chown ${httpduser} ./logs/ && echo "[done] 1" || eexit "[fail] 1"
printf "changing ownership of ./private/ to ${httpuser} ... " ; chown ${httpduser} ./private/ && echo "[done] 2" || eexit "[fail] 2"

if [ ! -f ./private/oauth/private.key ]; then
   printf "generating private key (this may take a while) ... " && openssl genrsa -out ./private/oauth/private.key 4096 && echo "[done] 3" || eexit "[fail] 3"
fi

if [ ! -f ./private/oauth/public.key ]; then
   printf "generating public key  ... " && openssl rsa -in  ./private/oauth/private.key -pubout -out  ./private/oauth/public.key && echo "[done] 4" || eexit "[fail] 4"
fi

printf "Restricting access to private.key (chmod 600) ... " && chmod 600 ./private/oauth/private.key && echo "[done] 5" || eexit "[fail] 5"
printf "Restricting access to private.key (chown ${pkeyowner}) ... " && chown ${pkeyowner}:${pkeyowner} ./private/oauth/private.key && echo "[done] 6" || eexit "[fail] 6"

echo ""
echo "GLUED successfully configured."
