Hashtag! Ur It!!!
========================

Welcome to Hashtag! Ur It!!! - An example of an application developed from the ground up using Symfony 2.8, Bootstrap 3.3.6 , and jQuery 2.2.2..  

It uses setter and dependancy injection to keep configurable things in the config files where they belong.

Install:
--------

### Clone this repo to the location of your choice:

`git clone ggit@github.com:perlnerd/hashtag-ur-it.git`

### Run composer

`cd bpe-clinton && composer install`

  * Serve `hashtag-ur-it/web` with your [favourite http server](http://symfony.com/doc/current/cookbook/configuration/web_server_configuration.html) OR run the built in web server:

`php app/console server:run` 

  * Visit `localhost:8000/hashtag` if using the built in webserver.  Otherwise visit http://YOURDOMAIN.com/hashtag

What's it do?
--------------
  
  * Not a lot, but enough to show that I know my way around PHP and Symfony and write clean, concise, configurable, and reusable code.

  * Displays 100 tweets containing the hashtag #Toronto.

What else can it do?
--------------------
  
  * You can change the hashtag by appending a different one to the URL! Try `/hashtag/JustinBieber`

  * Try a bogus hashtag like `/hashtag/Justingngnght876Bieber` and you'll get a friendly error message.

  * There's also a handy little form that lets you select a date range to search within.

What doesn't it do?
-------------------

  * Pagination.  This is just a demo.  I may add pagination in the future.

  * There should be a bit of form validation but I haven't done that yet.

  * Old IE support is not there.  
  

