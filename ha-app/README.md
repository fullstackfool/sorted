# Sorted as a Home Assistant app

Packages Sorted (Laravel + Vue, SQLite) as a local Home Assistant app (add-on) that
runs on the Pi next to Home Assistant, on port 8080.

```
ha-app/
  build.sh      Builds the app into dist/sorted (run on the Mac)
  package/      The hand-written app files (edit these, not dist/)
    config.yaml   Name, version, port 8080, watchdog, cold backups
    Dockerfile    Alpine 3.22 + PHP 8.3 (FPM) + nginx; copies the built app in
    DOCS.md       Shown in the app's Documentation tab in HA
    rootfs/       Files copied into the image: run.sh, nginx, PHP-FPM, PHP, cron
  dist/sorted/  The finished app folder: this is what goes onto the Pi
  .build/       Scratch space for build.sh (safe to delete)
```

## How it runs

- nginx serves `public/` and passes PHP to PHP-FPM. PHP workers start on demand
  (max 3) and stop after a minute idle. Measured: about 35 MB idle, about 60 MB
  while in use.
- `/data` is the app's own storage on the Pi. It holds `database.sqlite`, `app_key`
  and `sessions/`. It survives restarts, updates and rebuilds, and is included in
  HA backups (the app is stopped for a few seconds while it's backed up, so the
  database is never copied half-written).
- On first start, `/data` is empty, so the starter database (`seed/database.sqlite`,
  a copy of `database/database.sqlite` from the Mac at build time) is copied in.
  Its open chores are moved to today, once.
- Every start: write `.env`, run migrations, cache config/routes/views, generate any
  missing pending tasks, then start cron, PHP-FPM and nginx.
- Cron runs `php artisan schedule:run` every minute; `tasks:generate` fires at
  00:00 UTC.
- Logs (start-up, nginx errors, PHP and Laravel errors) go to the app's Log tab.

## Building

On the Mac (needs php 8.2+, composer, npm, rsync):

```
~/sorted/ha-app/build.sh
```

The front end has the app's address baked in (Ziggy reads `APP_URL` at build
time). The default is `http://192.168.0.79:8080`. If the Pi's address changes:

```
APP_URL=http://NEW-ADDRESS:8080 ~/sorted/ha-app/build.sh
```

## Installing (first time)

1. In HA: Settings → Apps → App store, install **Samba share**. In its Configuration
   tab set a strong password and set **enabled_shares** to just `local_apps`. Turn
   off Start on boot, start it, and stop it again once the copy is done.
2. On the Mac: Finder → Go → Connect to Server → `smb://192.168.0.79`, open the
   local apps share (`local_apps`; older Samba versions called it `addons`), and copy
   `ha-app/dist/sorted` into it (so the share contains `sorted/config.yaml`).
3. In HA: Settings → Apps → App store → ⋮ → **Check for updates**. Sorted appears
   under **Local apps**. Open it and press **Install**. The Pi builds the image;
   this takes a few minutes.
4. Turn on **Start on boot** and **Watchdog**, then **Start**. Check the **Log** tab
   ends with "Starting nginx on port 8080".
5. Change the Sorted dashboard's URL to `http://192.168.0.79:8080/`.
6. On the tablet, complete a chore to check it saves.
7. Give the Pi a fixed address on the router (DHCP reservation for 192.168.0.79),
   and check HA's automatic backups include apps.

## Updating

1. Change the code in `~/sorted` as usual.
2. Raise `version` in `ha-app/package/config.yaml`.
3. Run `build.sh`, start Samba share, and copy `dist/sorted` over the old folder in
   the `local_apps` share.
4. In HA, open the app and press **Rebuild**. The database in `/data` is untouched.

## Known quirks (in Sorted itself, not the packaging)

- `config/app.php` sets the timezone to UTC, so "today" and the midnight job run
  on UTC: an hour out from UK time in summer.
- A chore's next occurrence is dated from the previous one's date, not from when it
  was completed. If a daily chore is left for several days, completing it brings
  back the next missed day rather than today.
