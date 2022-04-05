#!/bin/bash

SCRIPT_DIR=$(dirname $(readlink -f $0))

. "$SCRIPT_DIR/util.in"

init_vars ${1:-/opt/staging/dgi_migrate}

set -x
declare NUM=$(get_op_number)
set +x

{
  set -x
  # The base import
  wwwdo $TIME --verbose $DRUSH dgi-migrate:import "--root=$DRUPAL_ROOT" "--uri=$URI" "--user=$DRUPAL_USER" "--group=$MIGRATION_GROUP" "${@:2}" |& wwwdo tee "$LOG_DIR/$NUM-import.log" > /dev/null
  wwwdo mkdir -p "$LOG_DIR/$NUM-messages"
  wwwdo $DRUSH migrate:status --root=$DRUPAL_ROOT --uri=$URI --group=$MIGRATION_GROUP --field=id --format=string | \
  while read NAME ; do
    wwwdo $DRUSH migrate:messages --root=$DRUPAL_ROOT --uri=$URI --format=json $NAME | wwwdo tee "$LOG_DIR/$NUM-messages/$NAME.json" > /dev/null
  done
  set +x
} |& wwwdo tee "$LOG_DIR/$NUM-run.log"
