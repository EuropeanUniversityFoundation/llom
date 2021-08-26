#Docker-for-mac
For docker to work on macOS, you first need to install the <a href="https://docs.docker.com/docker-for-mac/install/">Docker-for-mac</a> desktop app.

#Mutagen
Some performance issues will happen while using docker-for-mac, however, you can improve those consequently by using the synchronisation with Mutagen.

The core idea of this project is to use an external volume that will sync your files with a file synchronizer tool.

##Install mutagen
First, you will need to install mutagen if it is not already present on your macos:

`brew install mutagen-io/mutagen/mutagen`

##Override docker's config
Then, you have to override the config of docker to enable mutagen on your project.
If you do not already have a _docker-compose.override.yml_ file at the root of your project, you can create it and copy the content of _macos.docker-compose.override.yml_ into it:

`cp ./macos.docker-compose.override.yml ./docker-compose.override.yml`

If the file already exists, you will have to add the content of _macos.docker-compose.override.yml_ to it (pay attention to the service keys that might already be present in your existing _docker-compose.override.yml_)

##Start the mutagen container
Launch the mutagen container and the other containers:

`make mutagen`

`make up`

##Terminate mutagen when not needed anymore
`mutagen project terminate`


#More details
You can find more details on the <a href="https://wodby.com/docs/1.0/stacks/drupal/local/#usage">wodby documentation</a>.
