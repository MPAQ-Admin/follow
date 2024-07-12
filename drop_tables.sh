#!/bin/bash
DB_NAME="mastodon"
DB_USER="mastodon"
DB_PASSWORD="EndlessJourney"
mysql -u $DB_USER -p$DB_PASSWORD -Nse "SHOW TABLES" $DB_NAME | while read table; do
mysql -u $DB_USER -p$DB_PASSWORD -e "DROP TABLE IF EXISTS $table" $DB_NAME
done
