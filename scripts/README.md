# Migration Helpers

## Setup

1. Prepare a logging directory (later referenced as `LOG_DIR`), owned by the user who will be running the
   migration.
2. Copy `env.sample` to `$LOG_DIR/.env`.
3. Edit `$LOG_DIR/.env` appropriately, at minimum:
    * Add a `URI` entry for the target site.

### Tracking down drush

For convenience, we use `drush` to find the path of the module under which the
scripts are installed, but so need to know where `drush` is such that it can be
called. There may not always be a nice way to do this... if on the `PATH`, you
should be able to refer to it with something like:

```bash
DRUSH=$(which drush)
```

... however, if _not_ on the `PATH`, it might have to be explicitly provided in
a manner much the same as it is calculated and used _inside_ of our scripts,
with a reference relative to Drupal's root directory.

```bash
# Usual default location.
DRUPAL_ROOT=/opt/www/drupal
DRUSH="$DRUPAL_ROOT/vendor/bin/drush"
```

## Import

If additional parameters/options need to be passed to the `dgi-migrate:import`
call, they can be added to the end of the command:

```bash
sudo bash $($DRUSH dd dgi_migrate)/scripts/migration.sh $LOG_DIR
```

## Rollback

If additional parameters/options need to be passed to the `dgi-migrate:rollback`
call, they can be added to the end of the command:

```bash
sudo bash $($DRUSH dd dgi_migrate)/scripts/rollback.sh $LOG_DIR
```

... of particular interest here might be the `--statuses` options, which can be:

> An optional set of row statuses, comma-separated, to which to constrain the
rollback. Valid states are: "imported", "needs_update", "ignored", and "failed".

... to be able to rollback things that are in the `ignored` and `failed` states,
fix up whatever was causing them to fail/be ignored, and then kick off the
migration again is a very useful pattern of execution to iterate towards
convergence.
