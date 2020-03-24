# Homepage Notes:

Installation:

1.- Pull master

2.- Import the database provided

3.- Run composer Install/Update

4.- Added the following patch https://www.drupal.org/files/issues/2020-01-08/3100712-8-wsod-on-config-page.patch

3.- Disable Siteminder - As I needed to be able to log into the site.

4.- Disable and delete the contrib module User Alerts - if it exist

6.- Export configuration - to sync the configuration on the db with the config files.

7.- Added and enabled contrib modules SHS and DraggableViews

8.- Enabled custom module Content Segmentation.

Note: Maybe It is gonna show an error about existing configuration, take note of that file(s), and delete it(them) run: drush config-delete [file]
If the above happens, try to reinstall the module after deleting the configuration.

9.- Export configuration - so it is on the config folder.

10.- Populate the segment/segments taxonomy
Note: Each segment starts with the number of the Job Function code followed by space and description:
1 BARGAINING UNIT,
0 EXCLUDED,
04 MANAGEMENT (MJEP),
...

11.- Add the field segment (field_emp - respect the name) to the user account, it is an entity reference to the segment/segments taxonomy.
And set the widget to SimpleHirarchicallySelect on the manage form display section.

12.- Assign segments to some/all users.
The file content_segmentation contains the following function: content_segmentation_entity_presave
with functionality to populate the Job Function Code base on the value set on the Segment Select, or populate the Segment select base on the Job Function Code. You can comment/uncomment the one you need to be the master depending your needs.

IMPORTANT: Job Function Code fields is wrong as it doesn’t accept leading zeros and by your codes it should accept them.


——

Dashboard General:

1.- Create a Homepage Dashboard

2.- Add views to the dashboard

3.- Add the homepage dashboard block to the content section, restrict it to be set only to the <front>



——

User Alerts:

Alert displayed base on active content, segment and user cookie. Once that a user closes the alert then it won’t show up for that user again.

1.- Create messages nodes

2.- Go to Segmentation: Messages, and add some records

3.- Make sure to click Save Order.

4.- Add the block "User Alerts" to the correspondent section and configuration depending where the alerts should appear (the entire website, only homepage, or only certain sections).


Note: If you go to the UserAlert controller (content_segmentation/src/Controller), on the displayAlert function there is code to consider the segment to be hierarchically instead of 1 to 1, comment/uncomment depends on your needs.


——

Corporate News:
Display 5 corporate news depending on the user segment; if there are less than 5, then the remaining spots are filled with the newest corporate news.

1.- Create some articles of the type corporate news

2.-Go to Segmentation: Corporate News, and add some records.

3.- Make sure to click Save Order.

4.- Add the block "Corporate News" to the homepage dashboard

Important: Don’t remove this condition Content: ID (= 0) from the Corporate News [block] view.

Note: If you go to the UserAlert controller (content_segmentation/src/Controller), on the displayAlert function there is code to consider the segment to be hierarchically instead of 1 to 1, comment/uncomment depends on your needs.

——

My Groups:
Block that show articles from the groups the current user belongs to.

1.- Add the block "My Groups" to the homepage dashboard

Important: Don’t remove this condition "(Content group content) Group content: Parent group (= 0)" from the Group Content [blocks] view.

——

Community News:
Block that show articles from the groups the current user doesn’t belong to.

1.- Add the block "Community News" to the homepage dashboard

Important: Don’t remove this condition "(Content group content) Group content: Parent group (= 0)" from the Group Content [blocks] view.

3:32PM
