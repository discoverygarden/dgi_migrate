#!/bin/bash

SCRIPT_DIR=$(dirname $(readlink -f $0))

. "$SCRIPT_DIR/util.in"

init_vars ${1:-/opt/staging/dgi_migrate}

set -x
declare NUM=$(get_op_number)
set +x

{
  set -x
  # The base rollback.
  wwwdo $TIME --verbose $DRUSH dgi-migrate:rollback --root=$DRUPAL_ROOT --uri=$URI --user=$DRUPAL_USER --group=$MIGRATION_GROUP "${@:2}" |& wwwdo tee "$LOG_DIR/$NUM-rollback.log" > /dev/null
  set +x
} |& wwwdo tee "$LOG_DIR/$NUM-run.log"
