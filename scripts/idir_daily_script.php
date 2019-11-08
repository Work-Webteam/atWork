#!/opt/rh/rh-php56/root/usr/bin/php
<?php
echo "\n Memory Consumption is   ";
echo round(memory_get_usage()/1048576,2).''.' MB';
print_r("\n");
// From http://www.codechewing.com/library/read-csv-file-data-insert-into-database-php/
// Open the file, wherever it may be
// Open the file, wherever it may be
date_default_timezone_set('America/Los_Angeles');
$time_stamp= date('Ymd');

//$fh = fopen('/home/twerdal/idir/idir/idir_' . $time_stamp . '.tsv', 'r');
//$fh = fopen('/home/twerdal/idir/idir/idir_20160721.tsv', 'r');
$fh = fopen('idir_20190529.tsv', 'r');
// set HTTP_HOST or drupal will refuse to bootstrap
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
// Set this to keep the spammy authcache messages out of our logs
$_SERVER['REQUEST_METHOD'] = null;

// Bootstrap drupal so we can use it
define('DRUPAL_ROOT', '/var/www/public/work');
require_once DRUPAL_ROOT . '/includes/bootstrap.inc';
require_once DRUPAL_ROOT . '/modules/user/user.admin.inc';
// bootstrap all drupal modules
drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);

print_r("drupal is bootstraped \n");
echo "\n Memory Consumption is   ";
echo round(memory_get_usage()/1048576,2).''.' MB';
print_r("\n");

// If we have a file, then open a db connection
if(isset($fh) && $fh) {
    try {
      $pdo = new PDO(
        'mysql:host=localhost;dbname=db7r',
        'root',
        'root'
        );
    } catch (PDOException $e) {
      print "Error!: " . $e->getMessage() . "<br/>";
    }

    // Build a full array of drupal users and fields to check
    if(isset($pdo)){
      $sql = "
      SELECT
        atwork_atwork_auth.uid,
        atwork_atwork_auth.guid,
        atwork_users.init,
        atwork_field_data_field_display_name.field_display_name_value,
        atwork_users.mail,
        atwork_field_data_field_gal_first_name.field_gal_first_name_value,
        atwork_field_data_field_gal_last_name.field_gal_last_name_value,
        atwork_field_data_field_gal_phone_number.field_gal_phone_number_value,
        atwork_field_data_field_gal_title.field_gal_title_value,
        atwork_field_data_field_gal_branch.field_gal_branch_value,
        atwork_field_data_field_gal_work_group.field_gal_work_group_value,
        atwork_field_data_field_gal_ministry_name.field_gal_ministry_name_value,
        atwork_field_data_field_gal_address.field_gal_address_value,
        atwork_field_data_field_gal_city.field_gal_city_value,
        atwork_field_data_field_gal_postal_code.field_gal_postal_code_value
      FROM atwork_atwork_auth
        LEFT JOIN atwork_users ON atwork_users.uid = atwork_atwork_auth.uid
        LEFT JOIN atwork_field_data_field_display_name ON atwork_users.uid = atwork_field_data_field_display_name.entity_id
        LEFT JOIN atwork_field_data_field_gal_first_name ON atwork_users.uid = atwork_field_data_field_gal_first_name.entity_id
        LEFT JOIN atwork_field_data_field_gal_last_name ON atwork_users.uid = atwork_field_data_field_gal_last_name.entity_id
        LEFT JOIN atwork_field_data_field_gal_phone_number ON atwork_users.uid = atwork_field_data_field_gal_phone_number.entity_id
        LEFT JOIN atwork_field_data_field_gal_title ON atwork_users.uid = atwork_field_data_field_gal_title.entity_id
        LEFT JOIN atwork_field_data_field_gal_branch ON atwork_users.uid = atwork_field_data_field_gal_branch.entity_id
        LEFT JOIN atwork_field_data_field_gal_work_group ON atwork_users.uid = atwork_field_data_field_gal_work_group.entity_id
        LEFT JOIN atwork_field_data_field_gal_ministry_name ON atwork_users.uid = atwork_field_data_field_gal_ministry_name.entity_id
        LEFT JOIN atwork_field_data_field_gal_address ON atwork_users.uid = atwork_field_data_field_gal_address.entity_id
        LEFT JOIN atwork_field_data_field_gal_city ON atwork_users.uid = atwork_field_data_field_gal_city.entity_id
        LEFT JOIN atwork_field_data_field_gal_postal_code ON atwork_users.uid = atwork_field_data_field_gal_postal_code.entity_id
      ";
      $stm = $pdo->prepare($sql);
      $stm->execute();
      $user_records = $stm->fetchAll(PDO::FETCH_ASSOC);
      // Fields available in $user_records array
      //
      //[uid] => 61203
      //[guid] => 80ACEF4CEBBA42BB882FB3FE8D47B711
      //[init] => kpaulson
      //[field_display_name_value] => Paulson, Ken OGC:IN
      //[mail] => Ken.Paulson@bcogc.ca
      //[field_gal_first_name_value] => Ken
      //[field_gal_last_name_value] => Paulson
      //[field_gal_phone_number_value] => 250 419-4404
      //[field_gal_title_value] => Chief Operating Officer
      //[field_gal_branch_value] => Operations Division
      //[field_gal_work_group_value] => Victoria
      //[field_gal_ministry_name_value] => BC Oil and Gas Commission
      //[field_gal_address_value] => 300, 398 Harbour Rd
      //[field_gal_city_value] => Victoria
      //[field_gal_postal_code_value] => V9A 0B7

     }
  // Close connection
  $pdo = null;

  $i = 0;
  while ( ($row = fgetcsv($fh, '', "\t")) !== false) {
    // Build our .csv array
    if($row[0] == 'TransactionType'){
      continue;
    }
    $record[$i] = $row;
    // Fields Available in $records array
    // [0] - Type of record - add, delete, modify
    // [1] = GUID
    // [2] - idir
    // [3] - Full name [last, first department short]
    // [4] - email
    // [5] - Givenname
    // [6] - Surname
    // [7] - Phone
    // [8] - Title
    // [9] - Department
    // [10] - Office
    // [11] - OrganizationCode ()
    // [12] - Company
    // [13] - Street
    // [14] - City
    // [15] - PostalCode
    // [16] - UserAccountControl [Bitwise code to show their status]

    $i ++;
  }
  // Initialize all arrays
  // Users who are in teh tsv that we do not have a record of
  $add_to_drupal = array();

  // Users who are not currently active
  $delete_in_drupal = array();

  // Users whose fields do not match are added to the "Update" list
  $update_in_drupal = array();

  $y=1;
  // First of all, remove all records from drupal that have no GUID - they won't be any help
  foreach($user_records as $key => &$value){
     // Don't want to mess with these two users.
    if($value['uid'] === 0){
      echo("Removing Anon User" . "\n");
      unset($user_records[$key]);
    }
    if($value['uid'] === 10){
      echo("Removing Employee News User" . "\n");
      unset($user_records[$key]);
    }
     // Right off the bat - no GUID taken care of in the remove script - this should occur on first run only
    if(strlen($value['guid']) < 1){
      //echo("No GUID, Removing" . "\n");
      // These were all removed in previous script (removal_script.php), no sense dragging them into our future checks (no guid to match)
      $user_records[$key] = null;
      unset($user_records[$key]);
    } else {
      //echo("User has guid" . "\n");
    }
    //echo("checking record " . $y . "\n");
    $y++;
    gc_collect_cycles();
  }

  // Now all records marked inactive should be added to removal list
  foreach($record as $key => &$value){
    if($value[17] & 2){
      //print_r("This account is to be DISABLED.  \n");
      $delete_in_drupal[$key] = $value;
     // remove from other arrays (no reason to keep it there.)
     $record[$key] = null;
     unset($record[$key]);
    }
  }

  // Now, lets finish splitting up the records list:
  foreach($record as $key => &$value){
    //echo($value[0] . "\n");
    switch(true){
      // Everything marked as an "add"
      case ($value[0] == "Add"):
        $add_to_drupal[$key] = $value;
        $record[$key] = null;
        unset ($record[$key]);
        break;
      // Everything marked as a "Modify"
      case ($value[0] == "Modify"):
        $update_in_drupal[$key] = $value;
        $record[$key] = null;
        unset ($record[$key]);
        break;
      // Everything marked as an "Delete"
      case($value[0] == "Delete"):
        $delete_in_drupal[$key] = $value;
        $record[$key] = null;
        unset ($record[$key]);
        break;
    }
    //echo( "Records Remaining in sort: " . count($record) . "\n");
  }

  // This array should now be empty - if it is not we have a problem
  if(count($record) >= 1){
    trigger_error("Not all records were parsed", E_USER_ERROR);
    die();
  } else {
    // We are done with this array - clear space
    $record = null;
    unset($record);
  }

  // Should handle deletions first, seeing as an idir could potentially be reused.
  foreach($delete_in_drupal as $key=>&$value){
    // Check against our drupal records
   // If they have never existed, no reason to put them in our system
   $result = db_select('atwork_auth', 'a')
   ->fields('a')
   ->condition('guid', $value[1], '=')
   ->execute()
   ->fetchAssoc();
   if(empty($result)){
    echo("User is not in our system, and does not need to be deleted \n");
    $delete_in_drupal[$key] = null;
    unset($delete_in_drupal[$key]);
    continue;
   }

    foreach($user_records as $key_2=>&$value_2){
      if($value_2['guid'] == $value[1]){
        // Check if they are already gone in our system, no sense doing this again
        if(strpos($value_2['init'], '_old') !== false){
          echo("User previously deleted \n");
          $delete_in_drupal[$key] = null;
          unset($delete_in_drupal);
          $user_records[$key_2] = null;
          unset($user_records[$key_2]);
          break;
        }
      }
    }
    echo "\n Memory Consumption is   ";
    echo round(memory_get_usage()/1048576,2).''."MB \n";
  }

  // Now lets check that all updates are necessary
  foreach($update_in_drupal as $key => &$value){
    foreach($user_records as $key_2 => &$value_2){
      if($value_2['guid'] === $value[1]){
        switch(false){
          // idirs (come through .tsv as all caps)
          case trim(strtolower($value[2])) === trim($value_2['init']):
            print_r("Idirs do not match \n");
            break;
          // repeat for all fields
          // Display name
          case trim($value[3]) === trim($value_2['field_display_name_value']):
            print_r("Full name does not match \n");
            break;
          // Email address
          case trim($value[4]) === trim($value_2['mail']):
            print_r("email does not match \n");
            break;
          // First name
          case trim($value[5]) === trim($value_2['field_gal_first_name_value']):
            print_r("First name does not match \n");
            break;
          // Last name
          case trim($value[6]) === trim($value_2['field_gal_last_name_value']):
            print_r("Last name does not match \n");
            break;
          // Phone
          case $value[7] === $value_2['field_gal_phone_number_value']:
            print_r("Phone does not match \n");
            break;
          // Job title
          case trim($value[8]) === trim($value_2['field_gal_title_value']):
            print_r("title does not match \n");
            break;
          // Department
          case trim($value[9]) === trim($value_2['field_gal_branch_value']):
            print_r("Branch does not match \n");
            break;
          // Office
          case trim($value[10]) === trim($value_2['field_gal_work_group_value']):
            print_r("Work group does not match \n");
            break;
          // Company/Ministry
          case trim($value[12]) === trim($value_2['field_gal_ministry_name_value']):
            print_r("Ministry does not match \n");
            break;
          // Street Address
          case trim($value[13]) === trim($value_2['field_gal_address_value']):
            print_r("Street address does not match \n");
            break;
          // City
          case trim($value[14]) === trim($value_2['field_gal_city_value']):
            print_r("city does not match \n");
            break;
          // Postal Code
          case trim($value[16]) === trim($value_2['field_gal_postal_code_value']):
            print_r("Postal Code does not match \n");
            break;
          //
          default:
            // Exact match, so we can wipe it off both lists, no updates needed.
            print_r("perfect match \n");
            // Null these first to remove data and free up mem
            $update_in_drupal[$key] = null;
            $user_records[$key_2] = null;
            unset($update_in_drupal[$key]);
            unset($user_records[$key_2]);
        }
        break;
      }
    }
  }

  // No longer require $user_records
  //$user_records = null;
  //unset($user_records);


  // Send the rest to be removed
  echo("beginning Delete script \n");
  delete_records_from_drupal($delete_in_drupal);
  echo("Finished delete script \n");
  // Done, get rid of the array
  $delete_in_drupal = null;
  unset($delete_in_drupal);

  // Now lets do the updates
  // First we will grab the necessary records from our drupal list
  echo("Beginning update script \n");
  update_records_in_drupal($update_in_drupal);
  echo("finished update script \n");
  $update_in_drupal = null;
  unset($update_in_drupal);

  //Finally add new records
  echo("Beginning add script \n");
  add_new_user_to_drupal($add_to_drupal);
  echo("finished add to script \n");
  $add_to_drupal = null;
  unset($add_to_drupal);


  echo("Script complete \n");
}




  /**
 * Helper function used to remove fields from inactive / old / unsused profiles.
 */
function delete_records_from_drupal(&$delete_in_drupal){
  $start_memory = memory_get_usage();
  gc_enable();
  gc_collect_cycles();
  // First create a db connection
  $servername = "localhost";
  $username = "root";
  $password = "root";
  $dbname = "db7r";
  $sql = "";
  try{
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    // set PDO error mode
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    foreach($delete_in_drupal as $key=>&$value){
        // Actual record has been passed from drupal, no check required
        if(isset($value['uid'])){
          $uid = $value['uid'];
        } else {
          // Not a drupal record, extract GUID to get a uid
          if(isset($value[1])){
            $array_guid = $value[1];
          } elseif(isset($value['GUID'])){
            $array_guid = $value['GUID'];
          } else {
            // Don't want this to hit on our records that have a null/blank guid
            $array_guid = 'none';
          }
            // Grab the uid
            $uid_sql = "SELECT atwork_atwork_auth.uid FROM atwork_atwork_auth WHERE atwork_atwork_auth.guid = '" . $array_guid . "';";

            $stmt = $conn->prepare($uid_sql);
            $stmt->execute();
            while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
              $uid = $row['uid'];
            }
        }
        // unset the var's above before our next iteration
        $uid_sql = null;
        $stmt = null;
        $row = null;
        unset($uid_sql);

        // Make sure we have a UID, we need this as most fields rely on entity id (which is the same unique id number)
      if(isset($uid)){
        $user = user_load($uid);
        //print_r($user);
        // If we have already removed this user, drop out
        if((isset($user->init) && strpos($user->init, '_old_deactivated') !== false) && (isset($user->name) && strpos($user->name, '_old_deactivated') !== false)  ){
          echo("This user has already been deleted in our system. \n");
          $delete_in_drupal[$key] = null;
          unset($delete_in_drupal[$key]);
          continue;
        }
        // Keep UID, GUID, Full name, First name and Last name,
        // Idir is altered in case we make this user a new record later
        if(isset($user->init)){
          echo("retired " .  $user->name . "'s Idir \n");
          // $user->name is a varchar 60, need to make sure we do not go over.
          $new_idir = $user->init ."_old_deactivated";
          $j = 0;
          // Need to make sure we have not retired this idir before - or we could have a mysql exception
          DO {
            $result = db_select('users', 'u')
              ->fields('u')
              ->condition('init', $new_idir, '=')
              ->execute()
              ->fetchAssoc();
              // This name is already in the system
              if(!empty($result)){
                // Strip out any numbers we may have already used
                $new_idir = preg_replace('/[0-9]+/', '', $new_idir);
                $j++;
                // Increment the appended number adn check again
                $new_idir = $new_idir . $j;
                if (strlen($new_idir) >= 59){
                  // We use 59 because varchar can store other articles in its final spot.
                  $new_idir = mb_substr($new_idir, -59);
                }
              }
            // Continue until we have a unique idir
          } while(!empty($result));

          $user->init = $new_idir;
          $user->name = $new_idir;
          $new_idir = null;
          unset($new_idir);
        }
        // Update email
        if(isset($user->mail)){
          // mail gets altered to unix time stamp with a 4 digit random appended (to ensure we do not get  a dupe)
          $pseudo_email = time() . rand(1000,9999) . "@gov.old.ca";
          $user->mail = $pseudo_email;
          echo("Reset " .  $user->name . "'s Email \n");
        }

        // Delete phone
        if(isset($user->field_gal_phone_number['und'][0]['value'])){
          $user->field_gal_phone_number['und'][0]['value'] = '';
          $user->field_gal_phone_number['und'][0]['safe_value'] = '';
          echo("Removed " .  $user->name . "'s phone number \n");

        }

        // Delete title
        if(isset($user->field_gal_title['und'][0]['value'])){
          $user->field_gal_title['und'][0]['value'] = '';
          $user->field_gal_title['und'][0]['safe_value'] = '';
          echo("Removed " .  $user->name . "'s title \n");
        }

        // Delete branch
        if(isset($user->field_gal_branch['und'][0]['value'])){
          $user->field_gal_branch['und'][0]['value'] = '';
          $user->field_gal_branch['und'][0]['safe_value'] = '';
          echo("Removed " .  $user->name . "'s branch \n");
        }

        // Delete Work group
        if(isset($user->field_gal_work_group['und'][0]['value'])){
          $user->field_gal_work_group['und'][0]['value'] = '';
          $user->field_gal_work_group['und'][0]['safe_value'] = '';
          echo("Removed " .  $user->name . "'s work group \n");
        }
        // Delete Ministry
        if(isset($user->field_gal_ministry_name['und'][0]['value'])){
          $user->field_gal_ministry_name['und'][0]['value'] = '';
          $user->field_gal_ministry_name['und'][0]['safe_value'] = '';
          echo("Removed " .  $user->name . "'s ministry \n");
        }
        // Delete Address
        if(isset($user->field_gal_address['und'][0]['value'])){
          $user->field_gal_address['und'][0]['value'] = '';
          $user->field_gal_address['und'][0]['safe_value'] = '';
          echo("Removed " .  $user->name . "'s address \n");
        }
        // Delete City
        if(isset($user->field_gal_city['und'][0]['value'])){
          $user->field_gal_city['und'][0]['value'] = '';
          $user->field_gal_city['und'][0]['safe_value'] = '';
          echo("Removed " .  $user->name . "'s City \n");
        }
        // Delete Postal Code
        if(isset($user->field_gal_postal_code['und'][0]['value'])){
          $user->field_gal_postal_code['und'][0]['value'] = '';
          $user->field_gal_postal_code['und'][0]['safe_value'] = '';
          echo("Removed " .  $user->name . "'s Postal Code \n");
        }
        // if it is published, unpublish this profile
        if(isset($user->field_public_profile['und'][0]['value']) && $user->field_public_profile['und'][0]['value'] == '1'){
          $user->field_public_profile['und'][0]['value'] = 0;
        }
        // Save this $user
        user_save($user);
        echo($user->name . " has successfully been removed from the system \n");
        // Prepare for next user
        $user = null;
        $psudo_email = null;
        unset($user);
        unset($pseudo_email);
        $delete_in_drupal[$key] = null;
        unset($delete_in_drupal[$key]);
      } else {
        echo("User has no uid" . "/n");
        print_r($value);
      }
    gc_collect_cycles();
    echo ("\n Total memory buildup is " . round((memory_get_usage() - $start_memory)/1048576,2) . " MB");
    echo "\n Memory Consumption is   ";
    echo round(memory_get_usage()/1048576,2).''.' MB';
    print_r("\n");
    echo ("\n" . count($delete_in_drupal) . " deletion records left" . "\n");
    }
   } catch(PDOException $e) {
    echo $sql . "<br>" . $e->getMessage();
    echo "\n";
  }

  $conn = null;
}


/**
 * Helper function used to updated fields for inaccurate records
 * Uses direct SQL calls
 */
function update_records_in_drupal(&$update_in_drupal){
  $start_memory = memory_get_usage();
  // First create a db connection
  $servername = "localhost";
  $username = "root";
  $password = "root";
  $dbname = "db7r";
  $sql = "";

  try{
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    // set PDO error mode
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Keep UID, GUID, Full name, First name and Last name,
    foreach($update_in_drupal as $key=>&$value){
      // Grab the uid
      $uid_sql = "SELECT atwork_atwork_auth.uid FROM atwork_atwork_auth WHERE atwork_atwork_auth.guid = '" . $value[1] . "';";
      $stmt = $conn->prepare($uid_sql);
      $stmt->execute();
      while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
        $uid = $row['uid'];
      }
      // unset the var's above before our next iteration
      $uid_sql = null;
      $stmt = null;
      $row = null;
      unset($uid_sql);
      // Make sure we have a UID, we need this as most fields rely on entity id (which is the same unique id number)
      if(isset($uid)){

        $user = user_load($uid);
        //check idir
        if(trim($user->init) !== trim(strtolower($value[2]))){
          // The idir has changed, need to make sure that we do not already have it in our system
          $result = db_select('users', 'u')
            ->fields('u')
            ->condition('init', $value[2], '=')
            ->execute()
            ->fetchAssoc();
          // If we already have a record with that idir, we need to delete that record - as only one user may have an idir at one time, and this is the current list.
          if(!empty($result)){
            // make sure that delete_records_from_drupal can recognize and handle this array
            $current_result[] = $result;
            delete_records_from_drupal($current_result);
            $result = null;
            $current_result = null;
            unset($current_result);
          }
          unset($result);
          // Check name as well - has given trouble on some runs:
          $result = db_select('users', 'u')
            ->fields('u')
            ->condition('name', $value[2], '=')
            ->execute()
            ->fetchAssoc();
          // If we already have a record with that idir, we need to delete that record - as only one user may have an idir at one time, and this is the current list.
          if(!empty($result)){
            // make sure that delete_records_from_drupal can recognize and handle this array
            $current_result[] = $result;
            delete_records_from_drupal($current_result);
            $result = null;
            $current_result = null;
          }
          // This name is unique, and has been assigned to this record, so lets commit it.
          $user->name = trim(strtolower($value[2]));
          $user->init = trim(strtolower($value[2]));
          echo($user->name . "'s idir has been updated \n");
        }

        // check email
        if(!isset($user->mail) || $user->mail !== $value[4]){
          $user->mail = $value[4];
          echo($user->name . "'s email has been updated \n");
        }

        // Check full name
        if(!isset($user->field_display_name['und'][0]['value']) || $user->field_display_name['und'][0]['value'] !== $value[3]){
          $user->field_display_name['und'][0]['value'] = $value[3];
          $user->field_display_name['und'][0]['safe_value'] = $value[3];

          echo($user->name . "'s full name has been updated \n");
        }

        // check given-name
        if(!isset($user->field_gal_first_name['und'][0]['value']) || $user->field_gal_first_name['und'][0]['value'] !== $value[5]){
          $user->field_gal_first_name['und'][0]['value'] = $value[5];
          $user->field_gal_first_name['und'][0]['safe_value'] = $value[5];

          echo($user->name . "'s first name has been updated \n");
        }

        // check surname
        if(!isset($user->field_gal_last_name['und'][0]['value']) || $user->field_gal_last_name['und'][0]['value'] !== $value[6]){
          $user->field_gal_last_name['und'][0]['value'] = $value[6];
          $user->field_gal_last_name['und'][0]['safe_value'] = $value[6];

          echo($user->name . "'s last name has been updated \n");
        }

        // check phone
        if(!isset($user->field_gal_phone_number['und'][0]['value']) || $user->field_gal_phone_number['und'][0]['value'] !== $value[7]){
          $user->field_gal_phone_number['und'][0]['value'] = $value[7];
          $user->field_gal_phone_number['und'][0]['safe_value'] = $value[7];

          echo($user->name . "'s phone has been updated \n");
        }

        // check title
        if(!isset($user->field_gal_title['und'][0]['value']) || $user->field_gal_title['und'][0]['value'] !== $value[8]){
          $user->field_gal_title['und'][0]['value'] = $value[8];
          $user->field_gal_title['und'][0]['safe_value'] = $value[8];

          echo($user->name . "'s title has been updated \n");
        }

        // check Branch
        if(!isset($user->field_gal_branch['und'][0]['value']) || $user->field_gal_branch['und'][0]['value'] !== $value[9]){
          $user->field_gal_branch['und'][0]['value'] = $value[9];
          $user->field_gal_branch['und'][0]['safe_value'] = $value[9];

          echo($user->name . "'s branch has been updated \n");
        }

        // check work group
        if(!isset($user->field_gal_work_group['und'][0]['value']) || $user->field_gal_work_group['und'][0]['value'] !== $value[10]){
          $user->field_gal_work_group['und'][0]['value'] = $value[10];
          $user->field_gal_work_group['und'][0]['safe_value'] = $value[10];

          echo($user->name . "'s Work group has been updated \n");
        }

        // check Ministy
        if(!isset($user->field_gal_ministry_name['und'][0]['value']) || $user->field_gal_ministry_name['und'][0]['value'] !== $value[12]){
          $user->field_gal_ministry_name['und'][0]['value'] = $value[12];
          $user->field_gal_ministry_name['und'][0]['safe_value'] = $value[12];

          echo($user->name . "'s Ministry has been updated \n");
        }

        // check street address
        if(!isset($user->field_gal_address['und'][0]['value']) || $user->field_gal_address['und'][0]['value'] !== $value[13]){
          $user->field_gal_address['und'][0]['value'] = $value[13];
          $user->field_gal_address['und'][0]['safe_value'] = $value[13];

          echo($user->name . "'s Address has been updated \n");
        }

        // check postal code
        if(!isset($user->field_gal_postal_code['und'][0]['value']) || $user->field_gal_postal_code['und'][0]['value'] !== $value[16]){
          $user->field_gal_postal_code['und'][0]['value'] = $value[16];
          $user->field_gal_postal_code['und'][0]['safe_value'] = $value[16];

          echo($user->name . "'s postal has been updated \n");
        }

        // check city
        if(!isset($user->field_gal_city['und'][0]['value']) || $user->field_gal_city['und'][0]['value'] !== $value[14]){
          $user->field_gal_city['und'][0]['value'] = $value[14];
          $user->field_gal_city['und'][0]['safe_value'] = $value[14];

          echo($user->name . "'s city has been updated \n");
        }

        // Save this $user
        user_save($user);
        echo(strtolower($user->name) . " has successfully been updated in the system \n");
        $user = null;
        $uid = null;
        unset($uid);
        //unset($idir);
        //unset($email);
        //unset($new_first_name);
        //unset($new_full_name);
        unset($user);
      } else {
        print_r("No uid found for record \n");
        // They are not in our system yet, we should try to add them.
        $unfound_user[$key] = $value;
        add_new_user_to_drupal($unfound_user);
        $unfound_user = null;
        unset($unfound_user);
      }
      $update_in_drupal[$key] = null;
      unset($update_in_drupal[$key]);
      echo ("\n Total memory buildup is " . round((memory_get_usage() - $start_memory)/1048576,2) . " MB");
      echo "\n Memory Consumption is   ";
      echo round(memory_get_usage()/1048576,2).''.' MB';
      print_r("\n");
      echo ("\n" . count($update_in_drupal) . " update records left");
    }

  } catch(PDOException $e) {
    echo $sql . "<br>" . $e->getMessage();
    echo "\n";
  }

  $conn = null;
}


/**
 * Helper function used to add new user
 * Uses drupal bootstrap and drush commands
 */
function add_new_user_to_drupal(&$add_to_drupal){
  $start_memory = memory_get_usage();

  // Make sure we don't have a user with this guid or name in the system yet.
  // First create a db connection
  $servername = "localhost";
  $username = "root";
  $password = "root";
  $dbname = "db7r";
  $sql = "";

  echo("in add to drupal");
  try{
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    // set PDO error mode
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    foreach($add_to_drupal as $key=>&$value){
      //This will generate a random password, you could set your own here
      $password = user_password(8);

      // Grab the uid
      $uid_sql = "SELECT atwork_atwork_auth.uid FROM atwork_atwork_auth WHERE atwork_atwork_auth.guid = '" . $value[1] . "';";
      $stmt = $conn->prepare($uid_sql);
      $stmt->execute();
      while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
        $uid = $row['uid'];
      }
      // reset unused vars
      $uid_sql = null;
      $stmt = null;
      unset($uid_sql);
      unset($stmt);
      // Make sure we have a UID, we need this as most fields rely on entity id (which is the same unique id number)
      if(isset($uid)){
        $user = user_load($uid);
        // This user is already in the system, we shouldn't be here - so lets send to update.
        $single_update[$key] = $value;
        echo("\n GUID found, sending user to update \n");
        $add_to_drupal[$key] = null;
        unset($add_to_drupal[$key]);
        update_records_in_drupal($single_update);
        // null these first to get them out of memory completely
        $uid = null;
        $user = null;
        $single_update = null;
        unset($uid);
        unset($user);
        unset($single_update);
        continue;
      } else {
        // Make sure that the user name is free - can only be assigned to one user at a time.
        /*
        $name_uid = db_select('users', 'u')
        ->fields('u')
        ->condition('name', $value[2], '=')
        ->execute()
        ->fetchAssoc();

        if(isset($name_uid) && $name_uid !== false){
          print_r("**************************************\n");
          var_dump($name_uid);
          print_r(" is still in database, it will be sent to remove.\n");
          print_r("**************************************\n");
          //TODO: Send this $value to remove.
        }
        */
        // First lets make sure that this idir is not currently active in the system
        // The idir has changed, need to make sure that we do not already have it in our system
        $result = db_select('users', 'u')
          ->fields('u')
          ->condition('init', $value[2], '=')
          ->execute()
          ->fetchAssoc();
        // If we already have a record with that idir, we need to delete that record - as only one user may have an idir at one time, and this is the current list.
        if(!empty($result)){
          // make sure that delete_records_from_drupal can recognize and handle this array
          $current_result[] = $result;
          delete_records_from_drupal($current_result);
          unset($current_result);
        }
        unset($result);
        // Check name as well - has given trouble on some runs:
        $result = db_select('users', 'u')
          ->fields('u')
          ->condition('name', $value[2], '=')
          ->execute()
          ->fetchAssoc();
        // If we already have a record with that idir, we need to delete that record - as only one user may have an idir at one time, and this is the current list.
        if(!empty($result)){
          // make sure that delete_records_from_drupal can recognize and handle this array
          $current_result[] = $result;
          delete_records_from_drupal($current_result);
          $result = null;
          $current_result = null;
        }
        // Now that we know this idir is not currently activ in our system, we can go ahead and create a new user.
        //set up the user fields
        $fields = array(
          'name' => strtolower($value[2]),
          'mail' => $value[4],
          'pass' => $password,
          'status' => 1,
          'init' => strtolower($value[2]),
          'roles' => array(
            DRUPAL_AUTHENTICATED_RID => 'authenticated user',
          ),
        );
        //the first parameter is left blank so a new user is created
        $account = user_save('', $fields);
        $user = user_load($account->uid);
        //print_r($user);
        // We didn't have the GUID, so we need to add it to our auth table

        db_insert('atwork_auth')
          ->fields(array(
            'uid' => $user->uid,
            'guid' => $value[1],
            'timestamp' => REQUEST_TIME,
          ))
          ->execute();



         // update authmap with brand new user
        db_update('authmap')
        ->fields(array(
          'authname' => $user->name,
        ))
        ->condition('uid', $user->uid)
        ->execute();

        // Now we have a user, so lets update all fields
        echo("New user created $user->name \n");
        $new_user_to_update[$key] = $value;
        //print_r($account);
        // We have added this user, so let's update the rest of their fields.
        update_records_in_drupal($new_user_to_update);
        $new_user_to_update = null;
        unset($new_user_to_update[$key]);
        $add_to_drupal[$key] = null;
        unset($add_to_drupal[$key]);
        $account = null;
        unset($account);
        $fields = null;
        unset($fields);
        $password = null;
        unset($password);
        $user = null;
        unset($user);
      }

      echo ("\n Total memory buildup is " . round((memory_get_usage() - $start_memory)/1048576,2) . " MB");
      echo "\n Memory Consumption is   ";
      echo round(memory_get_usage()/1048576,2).''.' MB';
      print_r("\n");
      echo ("\n" . count($add_to_drupal) . " records left to add");

    }
  } catch(PDOException $e) {
    echo $sql . "<br>" . $e->getMessage();
    echo "n";
  }

  $conn = null;

}


?>
