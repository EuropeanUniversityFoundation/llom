#!/bin/bash

# Generate a backup of a Drupal site
echo -e "\nGenerate a backup of a Drupal site."

SCRIPT_PATH=$(dirname $(realpath $0))
# Current script is located at ROOT/scripts
ROOT=`dirname ${SCRIPT_PATH}`
cd ${ROOT}

# Backup retention policy reset
KEEP_PREVIOUS_HOURLY=
KEEP_PREVIOUS_DAILY=
KEEP_PREVIOUS_WEEKLY=
KEEP_PREVIOUS_MONTHLY=

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

if [[ ${PROJECT_BASE_URL} == "" ]]; then
  echo -e ">> PROJECT_BASE_URL is missing! Exiting..."
  exit 1
fi

## Enforce a backup directory
BACKUP_DIR="backup"
BACKUP_PATH=${ROOT}/${BACKUP_DIR}
if [[ -d ${BACKUP_PATH} ]]; then
  mkdir -p ${BACKUP_PATH}
fi

## Set backup retention policy with fallback
if [[ ${KEEP_PREVIOUS_HOURLY} == "" ]]; then
  KEEP_PREVIOUS_HOURLY=3
fi

if [[ ${KEEP_PREVIOUS_DAILY} == "" ]]; then
  KEEP_PREVIOUS_DAILY=2
fi

if [[ ${KEEP_PREVIOUS_WEEKLY} == "" ]]; then
  KEEP_PREVIOUS_WEEKLY=1
fi

if [[ ${KEEP_PREVIOUS_MONTHLY} == "" ]]; then
  KEEP_PREVIOUS_MONTHLY=0
fi

# Default values for the backup process
FREQUENCY="manual"
KEEP_PREVIOUS=-1

# Command flag handling
while getopts ":hdwm" opt; do
  case ${opt} in
    h )
      # Specifies hourly backup
      FREQUENCY="hourly"
      KEEP_PREVIOUS=${KEEP_PREVIOUS_HOURLY}+1
      ;;
    d )
      # Specifies daily backup
      FREQUENCY="daily"
      KEEP_PREVIOUS=${KEEP_PREVIOUS_DAILY}+1
      ;;
    w )
      # Specifies weekly backup
      FREQUENCY="weekly"
      KEEP_PREVIOUS=${KEEP_PREVIOUS_WEEKLY}+1
      ;;
    m )
      # Specifies monthly backup
      FREQUENCY="monthly"
      KEEP_PREVIOUS=${KEEP_PREVIOUS_MONTHLY}+1
      ;;
    * )
      # Defaults to manual backup
      FREQUENCY="manual"
      KEEP_PREVIOUS=-1
      ;;
  esac
done

# Date and time formats
DATE=`date +"%Y%m%d"`
TIME=`date +"%H%M%S"`

# Filename syntax: NAME_FREQUENCY_DATE_TIME_COMPONENT.EXTENSION
FILENAME_COMMON=${PROJECT_NAME}
FILENAME_COMMON+="_"${FREQUENCY}
FILENAME_COMMON+="_"${DATE}
FILENAME_COMMON+="_"${TIME}

# Tarball containing the Drupal public filesystem
FILENAME_UPLOADS=${FILENAME_COMMON}
FILENAME_UPLOADS_KEY="files"
FILENAME_UPLOADS+="_"${FILENAME_UPLOADS_KEY}
FILENAME_UPLOADS_EXT="tar.gz"
FILENAME_UPLOADS+="."${FILENAME_UPLOADS_EXT}

# Tarball containing the Drupal private filesystem
FILENAME_PRIVATE=${FILENAME_COMMON}
FILENAME_PRIVATE_KEY="private"
FILENAME_PRIVATE+="_"${FILENAME_PRIVATE_KEY}
FILENAME_PRIVATE_EXT="tar.gz"
FILENAME_PRIVATE+="."${FILENAME_PRIVATE_EXT}

# Dump of the Drupal database
FILENAME_DATABASE=${FILENAME_COMMON}
FILENAME_DATABASE_KEY="dump"
FILENAME_DATABASE+="_"${FILENAME_DATABASE_KEY}
FILENAME_DATABASE_EXT="sql"
FILENAME_DATABASE+="."${FILENAME_DATABASE_EXT}

# Text file containing MD5 checksums
FILENAME_CHECKSUM=${FILENAME_COMMON}
FILENAME_CHECKSUM_KEY="md5"
FILENAME_CHECKSUM+="_"${FILENAME_CHECKSUM_KEY}
FILENAME_CHECKSUM_EXT="txt"
FILENAME_CHECKSUM+="."${FILENAME_CHECKSUM_EXT}

## Pre-backup commands
${DRUSH_PATH} sset system.maintenance_mode 1
${DRUSH_PATH} cr

## Create backup files

# Create a tarball relative to the files directory
# Exclude any directories or their content
# Enumerate what to include; MUST evaluate if matching wildcard
# Declare the path for the resulting file
FILES_DIR="files"
TAR_PATH="web/sites/default"

tar -czv -C ${ROOT}/${TAR_PATH} \
--exclude=${FILES_DIR}/"css/*" \
--exclude=${FILES_DIR}/"js/*" \
--exclude=${FILES_DIR}/"php/*" \
${FILES_DIR} $(cd ${ROOT}/${TAR_PATH} ; echo settings*) \
-f ${BACKUP_PATH}/${FILENAME_UPLOADS}

# IF a private filesystem is present at the project root, make another tarball
PRIVATE_DIR="private"
if [[ -d ${ROOT}/${PRIVATE_DIR} ]]; then
  tar -czv -C ${ROOT} ${PRIVATE_DIR} -f ${BACKUP_PATH}/${FILENAME_PRIVATE}
fi

# Dump the database using Drush
${DRUSH_PATH} sql:dump \
--result-file=${BACKUP_PATH}/${FILENAME_DATABASE}

## Record checksum
cd ${BACKUP_PATH}
md5sum ${FILENAME_UPLOADS} >> ${FILENAME_CHECKSUM}
if [[ -f ${FILENAME_PRIVATE} ]]; then
  md5sum ${FILENAME_PRIVATE} >> ${FILENAME_CHECKSUM}
fi
md5sum ${FILENAME_DATABASE} >> ${FILENAME_CHECKSUM}
cd ${ROOT}

## Execute post-backup commands
${DRUSH_PATH} sset system.maintenance_mode 0
${DRUSH_PATH} cr
curl -sL ${PROJECT_BASE_URL} > /dev/null

## Clean up old backups

if [[ ${KEEP_PREVIOUS} -gt 0 ]]; then
  cd ${BACKUP_PATH}

  # Target all checksums of the revelant frequency
  CHECKSUMS=(${PROJECT_NAME}"_"${FREQUENCY}*${FILENAME_CHECKSUM_KEY}*)

  # Get a reverse sorted list, i.e. newer files first
  IFS=$'\n'
  CHECKSUMS_SORTED=$(sort -r <<< "${CHECKSUMS[*]}")
  unset IFS

  # Get the date and time from the filename
  CHECKSUMS_DATETIME=()
  for item in ${CHECKSUMS_SORTED}
  do
    IFS="_"
    read -ra parts <<< ${item}
    unset IFS
    CHECKSUMS_DATETIME+=(${parts[2]}"_"${parts[3]})
  done

  # Check how many valid backups there are in total
  TOTAL_ITEMS=${#CHECKSUMS_DATETIME[@]}

  # Remove excess backups
  for (( i = ${KEEP_PREVIOUS}; i < ${TOTAL_ITEMS}; i++ ))
  do
    REMOVAL_TARGET=${PROJECT_NAME}"_"${FREQUENCY}"_"${CHECKSUMS_DATETIME[i]}
    rm -f ${REMOVAL_TARGET}*
  done

  ls -hAl ${BACKUP_PATH} | grep ${PROJECT_NAME}"_"${FREQUENCY}

  cd ${ROOT}
fi

exit 0
