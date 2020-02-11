#@Work Drupal Project
The @Work project contains the future site of the @Work website. 

## Composer
This project is maintained with composer. The main Drupal core and contrib modules as well as most of they Symphony scaffolding is not included in our repository, but rather is maintained in the composer.json file. When moving or importing this repository for the first time, you can install these files with ```composer install```. 

## Git
Git contains our composer files, custom code, libraries and a few other pieces that we require to maintain the site. 

## Settings.php
Currently the settings.php file is not part of the repo. After you have imported all files for Drupal make sure to ```cp default.settings.php ./settings.php``` in the web/sites/default directory. Once you have a clean copy of settings.php you need to update the file with database and config file settings. Currently our config file sits in the top directory - for settings this is set as:

```$settings['config_sync_directory'] = '../config/sync';'''

## Database
Currently the database is not included with the install, this should be ported between environments in a secure manner and imported via drush. ```drush sql-cli < $database_name```

## Drush and Drupal Console
Drush and Drupal Console are both included in the install. After you have downloaded and installed all required files with Git and Composer, you can use either Drush or Drupal Console 

## File structure
