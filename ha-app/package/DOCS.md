# Sorted

Family chores, points and streaks, shown in Home Assistant's sidebar through the
**Sorted** webpage dashboard.

- Web page: `http://<your HA address>:8080/` (the Pi is `192.168.0.79`).
- Data: one SQLite file, `/data/database.sqlite`, plus the app key in `/data/app_key`.
  Both are kept when the app is restarted, updated or rebuilt, and are included in
  Home Assistant backups (the app stops for a few seconds while it's backed up).
- First start: if `/data` has no database yet, the starter database built into the
  app is copied in (the people, labels and chore templates from the Mac).
- Nightly: tasks for the next occurrence of each chore are generated at 00:00 UTC
  (01:00 UK time in summer). They're also generated every time the app starts.

## Logs

Everything (start-up, PHP errors, Laravel errors) appears in the app's **Log** tab.

## Updating

Build a new version on the Mac with `ha-app/build.sh`, raise `version` in
`config.yaml`, copy the `dist/sorted` folder over the one in the `local_apps` share,
then press **Rebuild** on the app's page. The database is untouched.

## Starting the data again

Stop the app and delete `database.sqlite` from the app's data (or uninstall and
reinstall the app). The starter database is copied in on the next start.
