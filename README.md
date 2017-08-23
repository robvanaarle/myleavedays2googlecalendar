# myleavedays2googlecalendar
Sync myleavedays.com (verlofdagen.nl) to Google Calendar

Simple set of scripts to sync the leave days on myleavedays.com (verlofdagen.nl) of a group or preference list to one (shared) Google Calendar account. An organization using both tools can view leave days more quickly this way.

## Usage
* Edit config.ini to add database and myleavedays.com credentials. Also add the group or preference list id and Google Developer id.
* Run the install.php script to create tables and an authentication token
* Create cronjobs for the scripts in /tasks

## Notes
As myleavedays.com has no API, this script parses the website to fetch the nessecary information. The first script syncs myleavedays.com to a database, the second script syncs the database to a Google Calendar account 