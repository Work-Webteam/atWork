# @Work Drupal Project
The @Work project contains the future site of the @Work website. 

## Composer
This project is maintained with composer. The main Drupal core and contrib modules as well as most of they Symphony scaffolding is not included in our repository, but rather is maintained in the composer.json file. When moving or importing this repository for the first time, you can install these files with ```composer install```. 

## Git
Git contains our composer files, custom code, libraries and a few other pieces that we require to maintain the site. 

## Settings.php
Currently the settings.php file is part of the git repo, and is set up to accept environmental variables. If you do not plan on using a settings.local.php (see below) you will need to modify the settings.php on your local install or set up your own environmental variables. 

The accepted variables are as follows:

### Database Variables
```
**ATWORK_DB** = The database name - if this variable is set, all other database variables are assumed to be present.
**ATWORK_DRIVER** = The database type you are using. This is optional - if the variable is not set then it defaults to mysql.
**ATWORK_DB_USER** = The Drupal user that has permissions to the database.
**ATWORK_DB_PASSWORD** = The password for the database.
**ATWORK_DB_HOST** = The host location of the database (i.e.localhost, an IP or a pod).
**ATWORK_DB_PORT** = The port to use in order to connect to the database - traditionally 3306.
```

### Other Variables - Optional
```
**ATWORK_BASE_URL** = The base URL for the install - if not set this remains blank.
**ATWORK_CONFIGS** = The current config directory - if not set, this defaults to '..configs/sync' - the location of our default configs in the repo.
```

### Other Variables - Required
```
**ATWORK_HASH_SALT** = The hash salt for the install - this is used for security. Generally you would want this to match what the DB is expecting - but it is not necessary. If this variable is not set, hashsalt will be set to '', and youwill have to enter it manually before you can use the install. 
```
### TODO:
We may want to consider adding environmental variables for the following options - depending on requirements:
```php
 $_SERVER['HTTPS'] = '';
 $_SERVER['SERVER_PORT'] = '';
 $settings['reverse_proxy'] = '';
 $settings['reverse_proxy_addresses'] = array();
 $settings['reverse_proxy_header'] = '';
 $settings['file_public_base_url'] = ''; 
 $settings['file_public_path'] = '';
 $settings['file_private_path'] = '';
 $settings['file_temp_path'] = '/tmp';
```

## settings.local.php
You may want to create your own settings.local.php file in order to override anything set in the settings.php (in this case most things should be blank if the environmental variables do not exist). This is not part of the repo, and must be set up on your local install. 

Go to your drupal install, and navigate to web/sites/default. After you are in the default directory run the command:

```shell
cp ../example.settings.local.php ./settings.local.php
```

After that you can open the settings.local.php file with your editor of choice and add in your database, base_url, hash_salt and config path variables as you require. (NOTE: Base path will have been set to the expected config dir already, it is recommended you do not change this, or push a new config directory to the master branch). For convenience, the related variables are added below. 

```php
$databases['default']['default'] = array (
  'database' => '',
  'username' => '',
  'password' => '',
  'prefix' => '',
  'host' => '',
  'port' => '',
  'driver' => '',
);

#$settings['config_sync_directory'] = '';
$settings['hash_salt'] = '';
$base_url = '';

```

## Database
Currently the database is not included with the install, this should be ported between environments in a secure manner and imported via drush. ```shell drush sql-cli < $database_name```

## Drush and Drupal Console
Drush and Drupal Console are both included in the install. After you have downloaded and installed all required files with Git and Composer, you can use either Drush or Drupal Console 

## File structure
