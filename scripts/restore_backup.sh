#!/bin/bash

# Restore a Drupal site from a backup
echo -e "\nRestore a Drupal site from a backup."

SCRIPT_PATH=$(dirname $(realpath $0))
# Current script is located at ROOT/scripts
ROOT=`dirname ${SCRIPT_PATH}`
cd ${ROOT}

## Check for a local drush binary
DRUSH_BIN="vendor/bin/drush"
DRUSH_PATH=${ROOT}/${DRUSH_BIN}
if [[ -f ${DRUSH_PATH} ]]; then
  echo -e ">> Drush found:              "${DRUSH_PATH}
else
  echo -e ">> Drush is missing! Exiting..."
  exit 1
fi

## Check for .env in the current directory
ENV_FILE=".env"
ENV_PATH=${ROOT}/${ENV_FILE}
if [[ -f ${ENV_PATH} ]]; then
  echo -e ">> .env found:               "${ENV_PATH}
  source ${ENV_PATH}
else
  echo -e ">> .env is missing! Exiting..."
  exit 1
fi

## Check environment variables
if [[ ${PROJECT_NAME} == "" ]]; then
  echo -e ">> PROJECT_NAME is missing! Exiting..."
  exit 1
fi

## Check for a backup directory
BACKUP_DIR="backup"
BACKUP_PATH=${ROOT}/${BACKUP_DIR}
if [[ -d ${BACKUP_PATH} ]]; then
  echo -e ">> Backup directory found:   "${BACKUP_PATH}
else
  echo -e ">> Backup directory is missing! Exiting..."
  exit 1
fi

# Tarball containing the Drupal public filesystem
UPLOADS_KEY="files"
UPLOADS_EXT="tar.gz"

# Tarball containing the Drupal private filesystem
PRIVATE_KEY="private"
PRIVATE_EXT="tar.gz"

# Dump of the Drupal database
DATABASE_KEY="dump"
DATABASE_EXT="sql"

# Text file containing MD5 checksums
CHECKSUM_KEY="md5"
CHECKSUM_EXT="txt"

cd ${BACKUP_PATH}
echo

# Target all checksums
CHECKSUMS=(${PROJECT_NAME}*${CHECKSUM_KEY}*)

# Check how many total backups there are
echo -e "Total backups:" ${#CHECKSUMS[@]}

CHECKSUMS_VALID=()
for item in ${CHECKSUMS[@]}
do
  md5sum -c ${item}
  if [ $? -eq 0 ]
  then
    CHECKSUMS_VALID+=(${item})
  fi
done

# Check how many valid backups there are
echo -e "Valid backups:" ${#CHECKSUMS_VALID[@]}

# Get a reverse sorted list, i.e. newer files first
# Split the filename into components and use the date for sorting
IFS=$'\n'
CHECKSUMS_SORTED=$(sort -r -k3 -t "_" <<< "${CHECKSUMS_VALID[*]}")
unset IFS

# Build options from the common filename parts of each valid backup
BACKUP_OPTIONS=()
for item in ${CHECKSUMS_SORTED[@]}
do
  # echo -e ${item}
  IFS="_"
  read -ra parts <<< ${item}
  unset IFS
  BACKUP_OPTIONS+=(${parts[0]}"_"${parts[1]}"_"${parts[2]}"_"${parts[3]})
done

# https://askubuntu.com/a/682146
shopt -s extglob

STR="@(${BACKUP_OPTIONS[0]}"
for((i=1;i<${#BACKUP_OPTIONS[@]};i++))
do
  STR+="|${BACKUP_OPTIONS[$i]}"
done
STR+=")"

BACKUP_SELECTED=""

if [[ ${#BACKUP_OPTIONS[@]} -gt 0 ]]; then
  echo -e "\nSelect a backup to restore:\n"
  select option in "${BACKUP_OPTIONS[@]}" "QUIT"
  do
    case ${option} in
      ${STR})
        BACKUP_SELECTED=${option}
        break;
        ;;

      "QUIT")
        exit 0
        ;;
      *)
        option=""
        echo "Please choose a number from 1 to $((${#BACKUP_OPTIONS[@]}+1))"
        ;;
    esac
  done
fi

if [[ ${BACKUP_SELECTED} != "" ]]; then
  echo -e ${BACKUP_SELECTED}

  FILENAME_UPLOADS=${BACKUP_SELECTED}"_"${UPLOADS_KEY}"."${UPLOADS_EXT}
  BACKUP_UPLOADS=${BACKUP_PATH}/${FILENAME_UPLOADS}

  FILENAME_PRIVATE=${BACKUP_SELECTED}"_"${PRIVATE_KEY}"."${PRIVATE_EXT}
  BACKUP_PRIVATE=${BACKUP_PATH}/${FILENAME_PRIVATE}

  FILENAME_DATABASE=${BACKUP_SELECTED}"_"${DATABASE_KEY}"."${DATABASE_EXT}
  BACKUP_DATABASE=${BACKUP_PATH}/${FILENAME_DATABASE}

  # Prompt to proceed
  echo
  while true; do
    echo -e "WARNING: this process will delete your database and files!"
    read -p "Do you wish to restore the site? [Y/N] " yn
    case $yn in
        [Yy]* ) echo -e "\nRestoring the backup..."; break;;
        [Nn]* ) exit 0;;
        * ) echo "Please answer yes or no.";;
    esac
  done

  cd ${ROOT}
  SITES_DIR="web/sites/default/"

  # Delete files directory and restore from backup
  rm -rf ${SITES_DIR}files
  tar -xzvf ${BACKUP_UPLOADS} -C ${SITES_DIR} --skip-old-files

  # Drop all tables in the database and restore from backup
  ${DRUSH_PATH} sql:drop -y
  ${DRUSH_PATH} sql:cli < ${BACKUP_DATABASE}

  # Delete private files directory and restore from backup
  if [[ -f ${BACKUP_PRIVATE} ]]; then
    rm -rf private
    tar -xzvf ${BACKUP_PRIVATE}
  fi

  # Execute post-restore commands
  ${DRUSH_PATH} sset system.maintenance_mode 0
  ${DRUSH_PATH} cr

  # Issue reminder
  echo -e "You may have to run the following commands in a local environment:"
  echo -e "chmod a+w web/sites/default/files/ -R"
  echo -e "chmod a+w private/ -R"

else
  echo -e "\nNo valid backups to restore"
  exit 1
fi

exit 0
