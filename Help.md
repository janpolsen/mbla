## Contents ##
  * [Avatar cache location](Help#Avatar_cache_location.md)
  * [Custom avatar file](Help#Custom_avatar_file.md)
  * [Avatar Fetch Cycle](Help#Avatar_Fetch_Cycle.md)
  * [Check for updated avatar after x days](Help#Check_for_updated_avatar_after_x_days.md)
  * [Gravatar rating](Help#Gravatar_rating.md)
  * [Debug key](Help#Debug_key.md)
  * [Wordpress hook to use](Help#Wordpress_hook_to_use.md)
  * [HTML code for](Help#HTML_code_for.md)
    * [Posts](Help#Posts.md)
    * [Comments](Help#Comments.md)
    * [Comments (anonymous)](Help#Comments_anonymous.md)
    * [Pingbacks](Help#Pingbacks.md)
    * [No avatar available](Help#No_avatar_available.md)


### Avatar cache location ###

This setting is essential in order to make this plugin work. The setting should point to a writable folder anywhere under the document root.

Sample setting: `/blog/wp-cache`

### Custom avatar file ###

This is an optional setting that adds an additional "box" to the "Avatar Fetch Cycle". The setting should point to a smallish image, which can be used as a custom avatar file.

Sample setting: `/images/anonymous.png`

### Avatar Fetch Cycle ###
This is the fetch cycle of which avatar that is going to be used in comments, posts, etc. The box at the top, is the service that will be queried for an avatar first. If that service can't give a valid avatar, then the next service will be queried.

If the "None" box is queried, then no avatar is returned. This can be used if you for some reason only want to show i.e. [Gravatar](http://gravatar.com) and no avatar at all if the poster doesn't have a [Gravatar](http://gravatar.com).

The various "Anonymous Avatar" boxes always return an avatar, that being either one of the services' avatar or the custom avatar (if configured).

The fetch cycle will in other words never pass the "None" box and the "Anonymous Avatar" boxes.

As of version 0.42 it is possible to choose a default "fall back" avatar for [Gravatar](http://gravatar.com). If this is anything but "None", then the cycle will stop here and not go to the next box.

### Check for updated avatar after x days ###
When is the fetched avatar considered outdated? Theoretically then it would be best to not use this setting at all, which would mean that a new avatar is fetched every time a person loads a web page using this plugin. Unfortunately this means much longer load times for each page and it's considered bad behavior to do such a thing against those services that hosts the avatars in the first place.

Personally I have the setting set to 3 days.

### Gravatar rating ###
This is the max [rating](http://site.gravatar.com/site/implement) you will allow for [Gravatars](http://gravatar.com)

Sample setting: `X`

### Debug key ###
A secret key used to run the plugin in debug mode. You can add `?debug=xxx` or `&debug=xxx` where `xxx` is the debug key to the URL of any page. This will result in MBLA writing a log file called `mbla_xxx.log` to the avatar cache directory. Here the ´xxx´ is the name of the page which are currently loaded.

Following is a few examples of how to add the debug parameter to an URL:
If your URL follows the pattern:

  * `http://example.com/blog/plugins/mbla/`
    * add `?debug=xxx`
      * result: `http://example.com/blog/plugins/mbla/?debug=xxx`

  * `http://example.com/blog/plugins/mbla/comment-page-1/#comments`
    * add `?debug=xxx` BEFORE the `#comments`
      * result: `http://example.com/blog/plugins/mbla/comment-page-1/?debug=xxx#comments`

  * `http://example.com/blog/wp-admin/options-general.php?page=mbla/mbla.php`
    * add `&debug=xxx` instead of `?debug=xxx`
      * result: `http://example.com/blog/wp-admin/options-general.php?page=mbla/mbla.php&debug=xxx`

The log file is useful to find out what happens during the rendering of a page and also to locate a possible bottleneck.

**NOTE: The log file will contain email addresses as well as full path names of your system, so have that in mind before you send or publish that log file to anyone.**

Sample setting: `myleetpassword42`

### Wordpress hook to use ###
Here you can indicate whether you want the plugin to automatically hook into a specific Wordpress function which are often used on comments. Sadly not all themes use the same functions to display comments, so there isn't a single correct function to hook into.

The plugin has a few hard coded function names which I have stumbled upon while looking at different themes. If your theme doesn't use any of these, then you can either select "None" and add the PHP code manually into the theme's comment file (this is how it was done up until v0.38) or you can select "Other" and write the function name you want to hook into.

If you need help, then feel free to write a comment here, but remember to tell which theme you use (and preferably a link to where it can be downloaded).

### HTML code for ###
This is the HTML code that is returned every time the main function is called. In other words then the HTML code you put under i.e. "Comments" is the HTML code that will be shown on every comment. There are some keywords that can be used, which will be replaced at runtime.

Those keywords are:
  * {URL} will be replaced by the URL from the commenter
  * {NAME} will be replaced by the name of the commenter
  * {MD5} will be replaced by the MD5(email) of the commenter
  * {TITLE} will be replaced by the title of the post
  * {AVATAR} will be replaced by the avatar of the commenter
  * {GRAVATAR\_RATING} will be replaced by the chosen max gravatar rating

Sample usage:
```
<div style="float: left;">
  <a href="{URL}" title="{NAME} commenting on {TITLE}">
    <img src="{AVATAR}" alt="" style="height: 32px" />
  </a>
</div>
```
#### Posts ####
This is the HTML code that is used for posts.

#### Comments ####
This is the HTML code that is used for comments.

#### Comments anonymous ####
This is the HTML code that is used for comments AND if the commenter is anonymous (meaning that an avatar can't be found).

#### Pingbacks ####
This is the HTML code that is used for pingbacks.

#### No avatar available ####
This is the HTML code that is used whenever the fetch cycle "hits" the "None" box.