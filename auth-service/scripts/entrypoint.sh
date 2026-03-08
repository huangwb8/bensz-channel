#!/bin/sh

set -eu

npm run migrate
exec node src/server.js
