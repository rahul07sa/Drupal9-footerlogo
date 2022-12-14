#!/bin/sh
#
# The post-code-deploy hook is run whenever you use the Workflow page to
# deploy new code to an environment, either via drag-drop or by selecting
# an existing branch or tag from the Code drop-down list. See
# ../README.md for details.
#
# Usage: post-code-deploy site target-env source-branch deployed-tag repo-url
#                         repo-type

subscription="$1"
target_env="$2"
source_branch="$3"
deployed_tag="$4"
repo_url="$5"
repo_type="$6"

cd `drush9 sa @$subscription.$target_env | grep "^  root" | cut -d" " -f4`

./../hooks/utilities/new-relic.sh $target_env "deploy" $deployed_tag
