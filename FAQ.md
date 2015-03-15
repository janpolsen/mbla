### Do I need to install a Gravatar plugin in order to use MBLA's Gravatar features? ###
No, MBLA is a stand alone plugin, which doesn't require any additional plugins to run.

### I have created/uploaded a cache folder, but it's empty and no avatars are showing up ###
Does the webserver have write access to the cache folder? This can usually be checked (and changed) with a FTP program. If you have created a cache folder specifically to MBLA, then you can safely change the folder permissions to 777 (`rwxrwxrwx`)

### The avatar does show up on my comments page, but the page looks very messed up ###
Make sure that you use well formed HTML on the HTML option page. Remember to close all HTML tags you use.

### Can I add these avatars to a page as well as comments? ###
Yes, of course. While you don't need to add any code to the comment template, then you need to do it to the page template. The following is what you should add where you want the page HTML code to show up:
```
<?php if(function_exists('MyAvatarsNew')) MyAvatarsNew(); ?>
```

### How do I use the debug function ###
Navigate to the page you wish to debug and finally add `?debug=xxx` or `&debug=xxx` where `xxx` is your secret debug key to the URL.

This should load the page in debug mode and place a file called `mbla_xxx.log` (where the `xxx` is the name of the current script file you are debugging) in your MBLA cache directory.

### How do I remove the link tags on the avatar image? ###
Just remove the link tags you see in the HTML code from something like this:
```
<div style="float: left;">
  <a href="{URL}" title="{NAME} commenting on {TITLE}">
    <img src="{AVATAR}" alt="" style="height: 32px" />
  </a>
</div>
```

... to something like this:
```
<div style="float: left;">
  <img src="{AVATAR}" alt="" style="height: 32px" />
</div>
```