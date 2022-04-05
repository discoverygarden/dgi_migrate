# Migration Helpers

## Setup

1. Prepare a logging directory (later referenced as `LOG_DIR`), owned by the user who will be running the
   migration.
2. Copy `env.sample` to `$LOG_DIR/.env`.
3. Edit `$LOG_DIR/.env` appropriately, at minimum:
    * Add a `URI` entry for the target site.

## Import

If additional parameters/options need to be passed to the `dgi-migrate:import`
call, they can be added to the end of the command:

```bash
sudo bash $(drush dd dgi_migrate)/scripts/migration.sh $LOG_DIR [...]
```

## Rollback

If additional parameters/options need to be passed to the `dgi-migrate:rollback`
call, they can be added to the end of the command:

```bash
sudo bash $(drush dd dgi_migrate)/scripts/rollback.sh $LOG_DIR [...]
```

... of particular interest here might be the `--statuses` options, which can be:

> An optional set of row statuses, comma-separated, to which to constrain the
rollback. Valid states are: "imported", "needs_update", "ignored", and "failed".
