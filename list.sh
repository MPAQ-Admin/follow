#!/bin/bash

###########################################################################################
# This not my work origanly. All credit for this script belongs to:
# https://fabulous.systems/posts/2023/06/fetch-own-followers-from-mastodon-api/
# felsqualle@manitu.social
# https://manitu.social/@felsqualle
###########################################################################################

if [ "$#" -ne 2 ]; then
	echo "Usage: $0 [username] [instance]"
	exit 1
else
	username=$1
	instance=$2
fi
userid="$(curl --silent "${instance}api/v1/accounts/lookup?acct=${username}" | jq -r .id)"
my="mysql -h host -uuser -ppassword"
echo "DROP TABLE IF EXISTS ${userid}_following;
CREATE TABLE ${userid}_following (
  id_code int(25) DEFAULT NULL,
  name varchar(50) DEFAULT NULL,
  idex int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
ALTER TABLE ${userid}_following
  ADD PRIMARY KEY (idex),
  ADD UNIQUE KEY id_code (id_code),
  MODIFY idex int(11) NOT NULL AUTO_INCREMENT;

DROP TABLE IF EXISTS ${userid}_followers;
CREATE TABLE ${userid}_followers (
  id_code int(25) DEFAULT NULL,
  name varchar(50) DEFAULT NULL,
  idex int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
ALTER TABLE ${userid}_followers
  ADD PRIMARY KEY (idex),
  ADD UNIQUE KEY id_code (id_code),
  MODIFY idex int(11) NOT NULL AUTO_INCREMENT;
" >/tmp/${userid}_temp.sql
$my mastodon < /tmp/${userid}_temp.sql
#echo "Found account ID: $userid for $username"
initial_url="${instance}api/v1/accounts/${userid}/following"
#echo "Checking URL <A HREF='${initial_url}' TARGET='_blank'>Account</A>..."
json_output=$(curl --silent "${initial_url}")
nextstep=$(curl --head --silent "${initial_url}" | grep -i "next" | cut -d ";" -f1 | sed -n 's/.*<\(.*\)>.*/\1/p')
while [ "${nextstep}" ]; do
	json_output+=$(curl --silent "${nextstep}")
	nextstep=$(curl --head --silent "${nextstep}"  | grep -i "next" | cut -d ";" -f1 | sed -n 's/.*<\(.*\)>.*/\1/p')
done
touch /tmp/${userid}_following.csv
echo "${json_output}" | jq -r '.[].acct'>> /tmp/${userid}_following.csv
while IFS= read -r line; do 
	ARRAY1+=($line)
done < "/tmp/${userid}_following.csv"
for record1 in "${ARRAY1[@]}"; do
	echo "INSERT INTO ${userid}_following (name) VALUES ('$record1');" >> /tmp/${userid}_following.sql
done
sort /tmp/${userid}_following.sql >/tmp/${userid}_following1.sql
mv /tmp/${userid}_following1.sql /tmp/${userid}_following.sql
$my mastodon < /tmp/${userid}_following.sql
initial_url="${instance}api/v1/accounts/${userid}/followers"
json_output=$(curl --silent "${initial_url}")
nextstep=$(curl --head --silent "${initial_url}" | grep -i "next" | cut -d ";" -f1 | sed -n 's/.*<\(.*\)>.*/\1/p')
while [ "${nextstep}" ]; do
	json_output+=$(curl --silent "${nextstep}")
	nextstep=$(curl --head --silent "${nextstep}"  | grep -i "next" | cut -d ";" -f1 | sed -n 's/.*<\(.*\)>.*/\1/p')
done
touch /tmp/${userid}_followers.csv
echo "${json_output}" | jq -r '.[].acct'>> /tmp/${userid}_followers.csv
while IFS= read -r line; do 
	ARRAY2+=($line)
done < "/tmp/${userid}_followers.csv"
for record2 in "${ARRAY2[@]}"; do
	echo "INSERT INTO ${userid}_followers (name) VALUES ('$record2');" >> /tmp/${userid}_followers.sql
done
sort /tmp/${userid}_followers.sql >/tmp/${userid}_followers1.sql
mv /tmp/${userid}_followers1.sql /tmp/${userid}_followers.sql
$my mastodon < /tmp/${userid}_followers.sql
rm /tmp/${userid}_temp.sql
rm /tmp/${userid}_following.csv
rm /tmp/${userid}_following.sql
rm /tmp/${userid}_followers.csv
rm /tmp/${userid}_followers.sql

