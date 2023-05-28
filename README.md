to install : 
 - composer install
 
 After the database was dumped :
 - drush cim -y 
 
Events module :
  - new block on content type "event" who display 3 related events ( by taxonomy type )
  - cron hook to unpublish events 
  - to run the drupal cron : drush cron
