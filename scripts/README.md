# Migration Helpers

## Setup

1. Prepare a logging directory (later referenced as `LOG_DIR`), owned by the user who will be running the
   migration.
2. Copy `env.sample` to `$LOG_DIR/.env`.
3. Edit `$LOG_DIR/.env` appropriately, at minimum:
    * Add a `URI` entry for the target site.
    * Add the `MIGRATION_GROUP` to be processed.

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

## Usage

### Import

If additional parameters/options need to be passed to the `dgi-migrate:import`
call (see `drush dgi-migrate:import --help` for a list of what there is;
however not everything is supported, such as `feedback` (which should be
silently ignored)), they can be added to the end of the command:

```bash
sudo bash $($DRUSH dd dgi_migrate)/scripts/migration.sh $LOG_DIR
```

When finished, the output should look like:

```
[... lots of verbose output of which we do not care to discuss at the moment ...]
+ set +x
---
Import command (operation 14) terminated; see log files in:
- /tmp/asdf/14-import.log
- /tmp/asdf/14-run.log
and the JSON files representing the output messages in:
- /tmp/asdf/14-messages
```

... at the bottom of the given `*-import.log` file, you should see details
regarding the timing (and success) of the command, for example:

```
$ tail -n 23 /tmp/asdf/14-import.log
	Command being timed: "/opt/www/drupal/vendor/bin/drush dgi-migrate:import --root=/opt/www/drupal --uri=http://adam-i9manage-dev-imt-92.dev.dgi --user=1 --group=isi__alpha"
	User time (seconds): 9.57
	System time (seconds): 2.55
	Percent of CPU this job got: 78%
	Elapsed (wall clock) time (h:mm:ss or m:ss): 0:15.44
	Average shared text size (kbytes): 0
	Average unshared data size (kbytes): 0
	Average stack size (kbytes): 0
	Average total size (kbytes): 0
	Maximum resident set size (kbytes): 103556
	Average resident set size (kbytes): 0
	Major (requiring I/O) page faults: 0
	Minor (reclaiming a frame) page faults: 257301
	Voluntary context switches: 14258
	Involuntary context switches: 7368
	Swaps: 0
	File system inputs: 0
	File system outputs: 0
	Socket messages sent: 0
	Socket messages received: 0
	Signals delivered: 0
	Page size (bytes): 4096
	Exit status: 0
```

### Rollback

If additional parameters/options need to be passed to the `dgi-migrate:rollback`
call, (see `drush dgi-migrate:rollback --help` for a list of what there is;
however, not everything is necessarily supported) they can be added to the end of
the command. The example base command might look something like:

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

When finished, the output should look like:

```
+ set +x
---
Rollback command (operation 15) terminated; see log files in:
- /tmp/asdf/15-rollback.log
- /tmp/asdf/15-run.log
```

... at the bottom of the given `*-rollback.log` file, you should see details
regarding the timing (and success of the command, for example:

```
$ tail -n23 /tmp/asdf/15-rollback.log
	Command being timed: "/opt/www/drupal/vendor/bin/drush dgi-migrate:rollback --root=/opt/www/drupal --uri=http://adam-i9manage-dev-imt-92.dev.dgi --user=1 --group=isi__alpha"
	User time (seconds): 2.11
	System time (seconds): 0.92
	Percent of CPU this job got: 65%
	Elapsed (wall clock) time (h:mm:ss or m:ss): 0:04.65
	Average shared text size (kbytes): 0
	Average unshared data size (kbytes): 0
	Average stack size (kbytes): 0
	Average total size (kbytes): 0
	Maximum resident set size (kbytes): 99632
	Average resident set size (kbytes): 0
	Major (requiring I/O) page faults: 0
	Minor (reclaiming a frame) page faults: 23394
	Voluntary context switches: 11363
	Involuntary context switches: 2705
	Swaps: 0
	File system inputs: 0
	File system outputs: 0
	Socket messages sent: 0
	Socket messages received: 0
	Signals delivered: 0
	Page size (bytes): 4096
	Exit status: 0
```
