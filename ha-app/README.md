# Sorted as a Home Assistant app

Packages Sorted (Laravel + Vue, SQLite) as a Home Assistant app (add-on) that runs
on the Pi next to Home Assistant, on port 8080. This GitHub repository doubles as the
app's Home Assistant repository: Home Assistant reads `repository.yaml` and
`ha-app/package/config.yaml` from it, and downloads the image GitHub Actions builds.

```
repository.yaml                 Tells Home Assistant this repository holds an app
.github/workflows/ha-app.yml    Builds the image and pushes it to ghcr.io when
                                `version` changes
ha-app/
  build.sh      Builds the app into dist/sorted, the image's build context
  package/      The hand-written app files
    config.yaml   Name, version, image, port 8080, watchdog, cold backups, config folder
    Dockerfile    Alpine 3.22 + PHP 8.3 (FPM) + nginx; copies the built app in
    DOCS.md       Shown in the app's Documentation tab in HA
    rootfs/       Files copied into the image: run.sh, nginx, PHP-FPM, PHP, cron
  dist/sorted/  Build output (not committed)
  .build/       Scratch space for build.sh (safe to delete)
```

## How it runs

- nginx serves `public/` and passes PHP to PHP-FPM. PHP workers start on demand
  (max 3) and stop after a minute idle. Measured: about 35 MB idle, about 60 MB
  while in use.
- `/data` is the app's own storage on the Pi. It holds `database.sqlite`, `app_key`
  and `sessions/`. It survives restarts and updates, and is included in HA backups
  (the app is stopped for a few seconds while it's backed up, so the database is
  never copied half-written).
- `/config` is the app's config folder, reachable through the Samba share app's
  `app_configs` share. It's how data gets in: the image is public, so it never
  contains a database. See [Importing data](#importing-data).
- On first start with nothing to import, an empty database is created.
- Every start: load any import, write `.env`, run migrations, cache
  config/routes/views, then start cron, PHP-FPM and nginx.
- Cron runs `php artisan schedule:run` every minute; at midnight UK time (the app's
  timezone is Europe/London) it tells open pages to reload so the new day shows.
- Logs (start-up, nginx errors, PHP and Laravel errors) go to the app's Log tab.

## Releasing

1. Change the code as usual.
2. Raise `version` in `ha-app/package/config.yaml` and push to `main`.
3. GitHub Actions builds the image for that version (Actions tab, "Home Assistant
   app"). Wait for it to finish: Home Assistant sees the new version as soon as
   it's on `main`, but can't download it until the image is pushed.
4. In HA, the update appears under Settings → Updates (or press ⋮ → **Check for
   updates** in the app store). Press **Update**. The database in `/data` is
   untouched.

Each version is built once. Pushing again without raising `version` builds nothing.

The front end has the app's address baked in (Ziggy reads `APP_URL` at build
time). The default is `http://192.168.0.79:8080`. If the Pi's address changes, set a
repository variable `APP_URL` in GitHub (Settings → Secrets and variables → Actions
→ Variables) and release a new version.

To check a build locally (needs php 8.2+, composer, npm, rsync), run `ha-app/build.sh`
from the project folder.

## Installing (first time)

1. After the first image is pushed, make it public: on GitHub, open the
   `sorted-aarch64` package (your profile → Packages), then Package settings →
   Change visibility → Public. New packages start private, and Home Assistant can't
   download a private one.
2. In HA: Settings → Apps → App store → ⋮ → **Repositories**, and add
   `https://github.com/fullstackfool/sorted`.
3. Sorted appears in the app store. Open it and press **Install**.
4. Turn on **Start on boot** and **Watchdog**, then **Start**. Check the **Log** tab
   ends with "Starting nginx on port 8080".
5. Point the Sorted dashboard at `http://192.168.0.79:8080/`.
6. On the tablet, complete a chore to check it saves.
7. Give the Pi a fixed address on the router (DHCP reservation for 192.168.0.79),
   and check HA's automatic backups include apps.

## Importing data

1. In HA, open the **Samba share** app. In its Configuration tab, make sure
   **enabled_shares** includes `app_configs`, then start it.
2. On the Mac: Finder → Go → Connect to Server → `smb://192.168.0.79`, open
   `app_configs`, then the folder ending in `_sorted`. Copy the database in and name
   it `import.sqlite`.
3. Restart Sorted. The log shows "Importing import.sqlite". The previous database is
   kept in the same folder as `before-import.sqlite`, and the file you copied is
   renamed `imported.sqlite` so it only loads once. The imported chores keep their
   dates, and each overdue one shows once.
4. Stop the Samba share app.
