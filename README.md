#MineREST

RESTfull API for Minecraft servers in PHP

##What is this API for?

This API allows you to control and interact with a Minecraft server
on a website.
The API doesn't have to be installed on the same server as your website.

##Requirements

The API is only supported on PHP 5.3 and up.

 * Init script:

The API interact with the server via an init script created by Ahtenus:
https://github.com/Ahtenus/minecraft-init

Setup instructions for the init script [here](https://github.com/Ahtenus/minecraft-init/blob/master/readme.markdown).

 * Apache with the url rewriting module

##Installation

To install the API, you can fork this project or copy/paste the files
on your server.

###1. You don't have a domain name or can't create a subdomain:

####Solution 1:

 * considering your web files are located in `/home/username/www`
 * copy/paste API files in `/home/username`
 * copy the content of the API `web` directory in `/home/username/www` (and delete the `web` directory)

####Solution 2:

 * considering your web files are located in `/home/username/www`
 * copy the API files in `/home/username/www`
 * rename the `web` directory: `api`

###2. You can create subdomains:

####Solution 1: You have access to the Apache configuration:

 * create a subdomain (for example `subdomain.example.com`)
 * copy the API files on your server
 * in Apache: the domain `subdomain.example.com` has to point on the `web` directory

####Solution 2: You don't have access to the Apache configuration:
 * create a subdomain (for example `subdomain.example.com`)
 * repeat steps Installation > 1 with the directory `/home/username/subdomain`

##Configuration

Now you have to configure the API. If the `config.yml` file doesn't exist,
go to your API url (`http://www.example.com/api`, `http://api.example.com/`, etc.)
depending on your installation and an empty config file will be created.

 * security.ip: the IP adress of the api client (wich isn't finished yet)
 * server:
  * jar: the name of your server jar (`craftbukkit.jar` by default)
  * path: the root directory of your Minecraft server (`/home/minecraft/minecraft` by default)
  * script: the path of the init script (`/etc/init.d/minecraft` by default)
 * database: not implemented yet

## Contributions

Contributions to this API are welcome via pull requests.

## License

The API was created by [Nathan Boiron](http://mopolo.fr) and released under the MIT License.

Feel free to fork this project.