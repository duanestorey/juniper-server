# Juniper/Server

Juniper/Server is a light-weight, mostly statically-generated repository of themes and plugins for WordPress. It works in tandem with the Juniper/Author plugin to build a self-mirroring repository system. Juniper/Author sites can ping Server sites to indicate they are hosting plugins or themes.  These add-ons then get automatically added to the repository.

## Requirements

Juniper/Server requires the following:

1) A simple Apache or NGINX server with PHP
2) Shell access to execute the build process via 'php build.php'
3) The ability to setup a CRON job to execute the build process periodically, i.e. every 15 minutes

## Installation

To install Juniper/Server:

1) Clone the repo onto your web host
2) Set the virtual host home directory to be the _public directory in the Juniper/Server clone
3) Run 'composer install' to install all the dependencies
4) Copy the config/site.yaml file to the main directory, /, and modify it to suit the mirror site
5) To set up a mirror of a public Juniper/Server site, change thes site.yaml 'producer: 1' line to 'producer: 0'. The consumer_source line indicates the source of the mirror
6) Run 'php build.php' to generate the website

## Further Notes

Juniper/Author has a configurable mirror URL for propagation changes - these can be configured to communicate with a mirror. 
