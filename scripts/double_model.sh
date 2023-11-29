#!/bin/bash

if [ "$#" -ne 1 ]; then
    echo "Usage: $0 <directory_path>"
    exit 1
fi

dir_path="$1"

find "$dir_path" -type f | while IFS= read -r file_path; do
    highest_rels_ext=$(grep -o 'RELS-EXT.[0-9]*' "$file_path" | sort -t. -k2 -n | tail -n1)

    if [[ ! -z "$highest_rels_ext" ]]; then
        count=$(awk -v rels="$highest_rels_ext" 'BEGIN{c=0} $0 ~ rels {p=1} p && /<fedora-model:hasModel/ && /FedoraObject-3.0/{c++} END{print c+0}' "$file_path")
        if [ "$count" -ge 2 ]; then
            echo "$file_path has >1 models with at least one FedoraObject-3.0 after $highest_rels_ext"
        fi
    else
        echo "$file_path has no RELS-EXT.N line"
    fi
done
