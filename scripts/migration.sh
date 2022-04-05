#!/bin/bash

SCRIPT_DIR=$(dirname $(readlink -f $0))

. "$SCRIPT_DIR/util.in"

init_vars ${1:-/opt/staging/dgi_migrate}

do_migration $(get_op_number) ${@:2}
