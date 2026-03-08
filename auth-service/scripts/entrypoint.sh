#!/bin/sh

set -eu

node src/wait-for-database.js
npm run migrate
exec node src/server.js
