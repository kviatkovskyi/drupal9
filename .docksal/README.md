# Docksal template for drupal projects.

This is my [docksal](http://docksal.readthedocs.io/en/master/) template for drupal sites. You can install it in a existing project by using the install file in this directory:
```
cd path/to/your/project *(in this directory you have $DOCROOT (docroot or web or html)*
curl -fsSL https://raw.githubusercontent.com/fjmk/docksal-template/master/install | bash

# if you want to use tagged branches, drupal7 or drupal8, you add a bash argument:
curl -fsSL https://raw.githubusercontent.com/fjmk/docksal-template/drupal8/install | bash -s drupal8
```


### Files in .docksal/
- **docksal.env** *# docksal enviroment file. Make some customizations, in this file or in .docksal-local.env:*
  - CLI_IMAGE='docksal/cli:1.3-php5'
  - VIRTUAL_HOST=your-project.docksal
  - MYSQL_BACKUP="${HOME}/backups/your-project.sql.gz"
- **docksal-local.yml**    *# Add phpmyadmin and mailhog containers ot this project*
- **example.settings.local.php**    *# Example settings.local.php file. Is copied in init command if not exists in $DOCROOT}/sites/default/*

### Custom commands:
- **env** *# show local environment as seen in fin command*
- **init** *# init drupal in ${DOC_ROOT} and load database*
- **mydb** *# load database, create quick snapshot, load quick snapshot*
- **phpcs** *# test your files with phpcs*
- **test** *# quick test if your drupal site is up and running*

### phpmyadmin configuration file in .docksal/etc/
- **phpadmin.config.php** *# work on default database without logging in*

### Customize mysql in .docksal/etc/mysql/
- **my.cnf**

### Customize php in .docksal/etc/etc/php
- php-cli.ini
- php.ini *# use mailhog for drupal e-mails*
