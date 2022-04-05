#!/bin/bash

SCRIPT_DIR=$(dirname $(readlink -f $0))

. "$SCRIPT_DIR/util.in"

init_vars ${1:-/opt/staging/dgi_migrate}

NUM=$(get_op_number)

# The base import
sudo -u $WEB_USER -- $TIME --verbose $DRUSH dgi-migrate:import --root=$DRUPAL_ROOT --uri=$URI --user=$DRUPAL_USER --group=$MIGRATION_GROUP |& tee "$LOG_DIR/$NUM-import.log"
sudo -u $WEB_USER -- mkdir -p "$LOG_DIR/$NUM-messages"
sudo -u $WEB_USER -- $DRUSH migrate:status --root=$DRUPAL_ROOT --uri=$URI --group=$MIGRATION_GROUP --field=id --format=string | \
while read NAME ; do
  sudo -u $WEB_USER -- $DRUSH migrate:messages --root=$DRUPAL_ROOT --uri=$URI --format=json $NAME > "$LOG_DIR/$NUM-messages/$NAME.json";
done
