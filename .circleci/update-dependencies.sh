#!/bin/bash -ex

# Make sure the robofile is in the correct location.
cp web/modules/contrib/apigee_edge/.circleci/RoboFile.php ./

# Make sure the composer template is in the correct location.
rm ./composer.json
rm ./composer.lock
cp web/modules/contrib/apigee_edge/.circleci/composer.json.dist ./composer.json

robo setup:skeleton
robo add:modules $1
robo drupal:version $2
robo configure:module-dependencies
robo update:dependencies
robo do:extra $2

# Touch a flag so we know dependencies have been set. Otherwise, there is no
# easy way to know this step needs to be done when running circleci locally since
# it does not support workflows.
touch dependencies_updated
