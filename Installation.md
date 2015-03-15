There isn't any difference between installing this plugin for the first time and upgrading the plugin.

  * Download the latest featured release from the [Downloads](http://code.google.com/p/mbla/downloads/list) tab
  * Save that file as `mbla.php` in your plugin-directory (usually `/wp-content/plugins`)
  * Activate the plugin called MBLA from the [Wordpress](http://wordpress.org) plugins adminitration interface
> [![](http://kamajole.dk/cp/albums/Blandet/thumb_20070810_175200_activate.png)](http://kamajole.dk/cp/displayimage.php?pos=-15632)
  * Reconfigure the options to match your needs
> [![](http://kamajole.dk/cp/albums/Blandet/thumb_20070810_175200_mbla_options_general.png)](http://kamajole.dk/cp/displayimage.php?pos=-15634)
  * Remember to add some HTML code on MBLA's HTML option page
> [![](http://kamajole.dk/cp/albums/Blandet/thumb_20070810_175200_mbla_options_html.png)](http://kamajole.dk/cp/displayimage.php?pos=-15633)
  * If you want avatars on your posts/pages as well, then you need to add the following line to wherever you want the avatar to show up:
```
<?php if(function_exists('MyAvatarsNew')) MyAvatarsNew(); ?>
```

**If at any point something is messed up, then just click the "Reset to default values" from the Option page.**



