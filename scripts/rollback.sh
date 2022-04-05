#!/bin/bash

SCRIPT_DIR=$(dirname $(readlink -f $0))

. "$SCRIPT_DIR/util.in"

init_vars ${1:-/opt/staging/dgi_migrate}

NUM=$(get_op_number)

# The base rollback.
sudo -u $WEB_USER -- $TIME --verbose $DRUSH dgi-migrate:rollback --root=$DRUPAL_ROOT --uri=$URI --user=$DRUPAL_USER --group=$MIGRATION_GROUP |& tee "$LOG_DIR/$NUM-rollback.log"
