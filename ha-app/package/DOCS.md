# Sorted

Family chores, points and streaks, shown in Home Assistant's sidebar through the
**Sorted** webpage dashboard.

- Web page: `http://<your HA address>:8080/` (the Pi is `192.168.0.79`).
- Data: one SQLite file, `/data/database.sqlite`, plus the app key in `/data/app_key`.
  Both are kept when the app is restarted or updated, and are included in Home
  Assistant backups (the app stops for a few seconds while it's backed up).
- First start: an empty database is created, unless there's one to import.
- Nightly: at midnight UK time, open pages are told to reload so the new day's
  chores show.

## Importing a database

Put the database file in this app's config folder as `import.sqlite`, then restart
the app. With the Samba share app, that's the `app_configs` share, in the folder
ending in `_sorted`.

On start, the current database is moved to that folder as `before-import.sqlite`,
the import is loaded, and your file is renamed `imported.sqlite` so it only loads
once. The imported chores keep their dates, and each overdue one shows once.

## Logs

Everything (start-up, PHP errors, Laravel errors) appears in the app's **Log** tab.

## Updating

New versions appear in Home Assistant like any other app's update. The database is
untouched.

## Starting the data again

Uninstall the app, letting Home Assistant delete its data, then install it again.
An empty database is created on the next start.
