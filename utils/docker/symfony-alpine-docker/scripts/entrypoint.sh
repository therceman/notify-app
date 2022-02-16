#!/bin/sh

mkdir -p /run/apache2 && /usr/sbin/httpd -D FOREGROUND

exit "$@"