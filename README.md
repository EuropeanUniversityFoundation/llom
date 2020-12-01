# EUF base profile for Drupal projects

This is a base profile for Drupal 8 projects to be used within the EUF. This template is built upon the [drupal-composer/drupal-project](https://github.com/drupal-composer/drupal-project) and [docker4drupal](https://github.com/wodby/docker4drupal) - refer to the respective documentations whenever necessary.

## Quick start with Docker

In order to test this profile with Docker, you need `docker`, `docker-compose` and `make` installed on your system. If your system meets the requirements, follow these steps:

    git clone git@github.com:EuropeanUniversityFoundation/euf-base.git
    cd euf-base
    cp .env.example .env      # The .env file is ignored by version control
    nano .env                 # Edit the environment variables if necessary
    make up                   # Create and start the Docker containers
    make shell                # Access a shell in the PHP container
    composer install          # Install the necessary packages
    bash env-install.sh       # Quick command line installation

## User guide

1. [docker4drupal](#docker4drupal)
  1. [Basic setup](#basic-setup)
  2. [ENV variables for Docker](#env-variables-for-docker)
2. [LAMP stack](#lamp-stack)
  1. [Apache Virtualhost](#apache-virtualhost)
  2. [ENV variables for LAMP](#env-variables-for-lamp)
3. [Installing Drupal](#installing-drupal)
  1. [Fresh install](#fresh-install)
  2. [From existing configuration](#from-existing-configuration)
4. [Troubleshooting](#troubleshooting)


## docker4drupal

This project includes [docker4drupal](https://github.com/wodby/docker4drupal) with some particular changes to provide an easy and consistent way to launch projects across different development environments. This approach is not without its downsides but it works consistently and is recommended for development.

**docker4drupal** is built around a set of variables that are specific to each development environment, so this project includes a `.env.example` file that should be copied to `.env` and adapted according to specific needs. The `.env` file is ignored in version control to guarantee that the project is not "polluted" with environment specific code.

    cp .env.example .env      # The .env file is ignored by version control
    nano .env                 # Edit the environment variables if necessary

These environment variables are injected into the `docker-compose.yml` file to define all the Docker containers and their particular configuration. This is accomplished with the `Makefile` present at the root of the project, containing the necessary aliases to create / start, interact with, stop and delete Docker containers.

    make up                   # Create and start the Docker containers
    make start                # Start existing Docker containers
    make stop                 # Stop all running Docker containers
    make down                 # Same as make stop
    make prune                # Delete the Docker containers
    make shell                # Access a shell in the PHP container (default)

[Back to the User Guide](#user-guide)

### Basic setup

Out of the box, this modified version of **docker4drupal** includes the following containers:

    *Traefik* as a reverse proxy / load balancer;
    *NginX* as a web server;
    *PHP* and *crond* as the runtime environment (with `composer` and `drush`)
    *MariaDB* as the database engine;
    *Mailhog* to handle emails.

The **docker4drupal** stack can include many more components, but it is recommended that any additional components be include via a `docker-composer.override.yml` file, which will be ignored by version control, to keep the base project simple and fast. Some examples are included:

#### github.docker-compose.override.yml

Use this to modify the *PHP* container and add a Github authentication token to Composer. Some dependencies are pulled directly from Github repositories. During development, every `composer` operation will hit the Github API, which can lead to too many anonymous calls and getting locked out. To avoid this, generate a Personal Access Token and include it in the `.env` file; the token will then be loaded by the Composer configuration inside the PHP container.

#### node.docker-compose.override.yml

Use this to add a container with *NodeJS* for custom theme development.

#### pma.docker-compose.override.yml

Use this to add a container with *phpMyAdmin* for easier access to the database.

[Back to the User Guide](#user-guide)

### ENV variables for Docker

Inside the `.env` file there are many variables that impact the Docker setup, and most are set to sensible defaults. Others require some attention:

    `PROJECT_NAME` is used to prefix the container names;
    `PROJECT_BASE_URL` is used to define the URLs for all containers;
    `HTTP_PORT` can be leveraged to start multiple Docker setups (see below);
    `DB_` variables are used for the database container and whatever connects to it;
    `COMPOSER_AUTH` can be used with the Github override described above.

#### HTTP_PORT

By default the Traefik container will bind to port 8000; in order to use multiple setups at the same time, change the port number on your local environment to another number.  **Warning:** port 8025 is used by Mailhog.

[Back to the User Guide](#user-guide)

## LAMP stack

When using a regular **LAMP** stack (*Linux* + *Apache* + *MySQL* + *PHP*), be sure to have **Composer** on your system to be able to install dependencies. Optionally, install **Drush launcher** to facilitate using the local **Drush** which comes installed with this project, otherwise any `drush <command>` must be executed as `vendor/bin/drush <command>` (assuming it is run from the project root).

[Back to the User Guide](#user-guide)

### Apache Virtualhost

For the **Apache** web server, there are some additional rules that must be included in the *Virtualhost* definition:

    <VirtualHost *:80>
        ServerName DOMAIN
        DocumentRoot "/var/www/vhosts/DOMAIN/PROJECTROOT/web"
        <Directory "/var/www/vhosts/DOMAIN/PROJECTROOT/web">
            Options Includes FollowSymLinks
            AllowOverride All
            Order allow,deny
            Allow from all
        </Directory>
    </VirtualHost>

[Back to the User Guide](#user-guide)

### ENV variables for LAMP

Some variables inside the `.env` file will have no impact, while others do, since this setup is different from the Docker setup:

    `PROJECT_NAME` is *not* currently used in a LAMP setup;
    `PROJECT_BASE_URL` may be used in Drupal settings, so it **should be reviewed**;
    `HTTP_PORT` is *not* currently used in a LAMP setup;
    `DB_` variables are used for the stack database so they **should be reviewed**;
    `COMPOSER_AUTH` is *not* currently used in a LAMP setup;

Adding a Github authentication token to **Composer** should be done manually, and it only needs to be done once per system.

[Back to the User Guide](#user-guide)

## Installing Drupal

Installing Drupal for the first time can be done via the web GUI using your browser, or it can be done via the command line with the help of **Drush** or **Drupal Console**. This project includes shell scripts to leverage the variables set in the `.env` file and perform the installation quickly via the command line. Installing via the web GUI is also possible, although it requires more attention to details, especially in a Docker setup.

### Fresh install

To perform a first time installation, make sure you get the latest version of the required packages by running these commands:

    rm composer.lock          # contains exact versions, might be outdated
    composer install          # installs packages as defined in composer.json

Use the bundled shell script to perform a quick install from the `.env` variables:

    chmod u+x env-install.sh  # ensure the script is executable
    bash env-install.sh       # run the script using bash (safest)

After a first time installation, change the `settings.php` file to use `settings.local.php` instead:

    chmod u+w web/sites/default/settings.php
    nano web/sites/default/settings.php

Comment out the database settings block and add the following lines to the end of the file:

    if (file_exists($app_root . '/' . $site_path . '/settings.local.php')) {
      include $app_root . '/' . $site_path . '/settings.local.php';
    }

This allows `settings.local.php` to use the variables defined in the `.env` file. Finally, clear the cache:

    vendor/bin/drush cr       # or use `drush cr` if your setup allows

[Back to the User Guide](#user-guide)

### From existing configuration

If you want to reinstall your project using the exported configuration in the `config/sync` directory, install the exact packages versions and configuration by running these commands:

    composer install
    chmod u+x config-install.sh
    bash config-install.sh

If necessary, perform the same changes to `settings.php` as described above and clear the cache.

[Back to the User Guide](#user-guide)

## Troubleshooting

Some common issues and quick solutions...

### Scaffolding permissions issue

Composer require or update may fail because the scaffold plugin cannot replace files in `web/sites/default`; fix with `chmod u+w web/sites/default`.

[Back to the User Guide](#user-guide)

---

## What does the template do?

When installing the given `composer.json` some tasks are taken care of:

* Drupal will be installed in the `web`-directory.
* Autoloader is implemented to use the generated composer autoloader in `vendor/autoload.php`,
  instead of the one provided by Drupal (`web/vendor/autoload.php`).
* Modules (packages of type `drupal-module`) will be placed in `web/modules/contrib/`
* Theme (packages of type `drupal-theme`) will be placed in `web/themes/contrib/`
* Profiles (packages of type `drupal-profile`) will be placed in `web/profiles/contrib/`
* Creates default writable versions of `settings.php` and `services.yml`.
* Creates `web/sites/default/files`-directory.
* Latest version of drush is installed locally for use at `vendor/bin/drush`.
* Latest version of DrupalConsole is installed locally for use at `vendor/bin/drupal`.
* Creates environment variables based on your .env file. See [.env.example](.env.example).

## Updating Drupal Core

This project will attempt to keep all of your Drupal Core files up-to-date; the
project [drupal/core-composer-scaffold](https://github.com/drupal/core-composer-scaffold)
is used to ensure that your scaffold files are updated every time drupal/core is
updated. If you customize any of the "scaffolding" files (commonly .htaccess),
you may need to merge conflicts if any of your modified files are updated in a
new release of Drupal core.

Follow the steps below to update your core files.

1. Run `composer update drupal/core drupal/core-dev --with-dependencies` to update Drupal Core and its dependencies.
2. Run `git diff` to determine if any of the scaffolding files have changed.
   Review the files for any changes and restore any customizations to
  `.htaccess` or `robots.txt`.
1. Commit everything all together in a single commit, so `web` will remain in
   sync with the `core` when checking out branches or running `git bisect`.
1. In the event that there are non-trivial conflicts in step 2, you may wish
   to perform these steps on a branch, and use `git merge` to combine the
   updated core files with your customized files. This facilitates the use
   of a [three-way merge tool such as kdiff3](http://www.gitshah.com/2010/12/how-to-setup-kdiff-as-diff-tool-for-git.html). This setup is not necessary if your changes are simple;
   keeping all of your modifications at the beginning or end of the file is a
   good strategy to keep merges easy.

## Generate composer.json from existing project

With using [the "Composer Generate" drush extension](https://www.drupal.org/project/composer_generate)
you can now generate a basic `composer.json` file from an existing project. Note
that the generated `composer.json` might differ from this project's file.


## FAQ

### Should I commit the contrib modules I download?

Composer recommends **no**. They provide [argumentation against but also
workrounds if a project decides to do it anyway](https://getcomposer.org/doc/faqs/should-i-commit-the-dependencies-in-my-vendor-directory.md).

### Should I commit the scaffolding files?

The [Drupal Composer Scaffold](https://github.com/drupal/core-composer-scaffold) plugin can download the scaffold files (like
index.php, update.php, …) to the web/ directory of your project. If you have not customized those files you could choose
to not check them into your version control system (e.g. git). If that is the case for your project it might be
convenient to automatically run the drupal-scaffold plugin after every install or update of your project. You can
achieve that by registering `@composer drupal:scaffold` as post-install and post-update command in your composer.json:

```json
"scripts": {
    "post-install-cmd": [
        "@composer drupal:scaffold",
        "..."
    ],
    "post-update-cmd": [
        "@composer drupal:scaffold",
        "..."
    ]
},
```
### How can I apply patches to downloaded modules?

If you need to apply patches (depending on the project being modified, a pull
request is often a better solution), you can do so with the
[composer-patches](https://github.com/cweagans/composer-patches) plugin.

To add a patch to drupal module foobar insert the patches section in the extra
section of composer.json:
```json
"extra": {
    "patches": {
        "drupal/foobar": {
            "Patch description": "URL or local path to patch"
        }
    }
}
```
### How do I switch from packagist.drupal-composer.org to packages.drupal.org?

Follow the instructions in the [documentation on drupal.org](https://www.drupal.org/docs/develop/using-composer/using-packagesdrupalorg).

### How do I specify a PHP version ?

This project supports PHP 7.0 as minimum version (see [Drupal 8 PHP requirements](https://www.drupal.org/docs/8/system-requirements/drupal-8-php-requirements)), however it's possible that a `composer update` will upgrade some package that will then require PHP 7+.

To prevent this you can add this code to specify the PHP version you want to use in the `config` section of `composer.json`:
```json
"config": {
    "sort-packages": true,
    "platform": {
        "php": "7.0.33"
    }
},
```
