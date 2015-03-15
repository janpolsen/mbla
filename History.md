### 2007-01-10 - v0.2 ###
![http://famfamfam.googlecode.com/svn/wiki/images/star.png](http://famfamfam.googlecode.com/svn/wiki/images/star.png) Initial 'release'. Idea and credits for this release goes fully to [Napolux](http://www.napolux.com/2006/12/14/myavatars-a-wordpress-plugin-for-mybloglog).

### 2007-01-12 - v0.4 ###
![http://famfamfam.googlecode.com/svn/wiki/images/plugin.png](http://famfamfam.googlecode.com/svn/wiki/images/plugin.png) A local file cache has been added

![http://famfamfam.googlecode.com/svn/wiki/images/script_code.png](http://famfamfam.googlecode.com/svn/wiki/images/script_code.png) The whole plugin has been converted from hardcoded values to values that can be changed in Options -> MBLA

### 2007-01-13 - v0.5 ###
![http://famfamfam.googlecode.com/svn/wiki/images/plugin.png](http://famfamfam.googlecode.com/svn/wiki/images/plugin.png)  Class options has been added

![http://famfamfam.googlecode.com/svn/wiki/images/bug.png](http://famfamfam.googlecode.com/svn/wiki/images/bug.png) Fixed an issue with the size of avatars on trackbacks

### 2007-01-13 - v0.6 ###
![http://famfamfam.googlecode.com/svn/wiki/images/bug.png](http://famfamfam.googlecode.com/svn/wiki/images/bug.png) Fixed an issue where the ghost avatar didn't exist. Now it should use [MyBlogLog](http://mybloglog.com)'s default avatar if the user's avatar couldn't be found.

### 2007-01-14 - v0.7 ###
![http://famfamfam.googlecode.com/svn/wiki/images/script_code.png](http://famfamfam.googlecode.com/svn/wiki/images/script_code.png) The 'ghost avatar' seemed to rise some questions and since I have no other explanation of calling it a 'ghost avatar' besides my icon looking like a ghost, then I have renamed all occurrences of 'ghost' to 'anonymous'.

![http://famfamfam.googlecode.com/svn/wiki/images/plugin.png](http://famfamfam.googlecode.com/svn/wiki/images/plugin.png) I have also added an option to see what the cache contains. On the 'Options' -> 'MBLA' page, there should be a '(show current content of cache)' which opens a new page along with the content of the cache. You can delete a single avatar, so it gets updated the next time it is requested on a post/comment or you can delete alle avatars.

### 2007-01-14 - v0.8 ###
![http://famfamfam.googlecode.com/svn/wiki/images/script_code.png](http://famfamfam.googlecode.com/svn/wiki/images/script_code.png) The plugin now check if there is a newer version of itself available. It's not automatically installing anything - it's just telling you if there is an update.

### 2007-01-15 - v0.9 ###
![http://famfamfam.googlecode.com/svn/wiki/images/plugin.png](http://famfamfam.googlecode.com/svn/wiki/images/plugin.png) Added a feature that lets you use a snapshot of your own site as the avatar of pingback comments (from yourself). In order to use this you have to type in your [MyBlogLog](http://mybloglog.com) ID in the options page.

### 2007-01-17 - v0.10 ###
![http://famfamfam.googlecode.com/svn/wiki/images/script_code.png](http://famfamfam.googlecode.com/svn/wiki/images/script_code.png) Rewrote the method that avatars are being fetched. I now use PHP's curl-functions instead of file\_get\_contents().

### 2007-01-21 - v0.15 ###
![http://famfamfam.googlecode.com/svn/wiki/images/plugin.png](http://famfamfam.googlecode.com/svn/wiki/images/plugin.png) Added [Gravatar](http://gravatar.com) support. Choose which service you would look at first ([Gravatar](http://gravatar.com) or [MyBlogLog](http://mybloglog.com)), grab the avatar from that service and if there isn't an avatar for the given user there, then try to grab one from the other service. If that service doesn't contain an avatar either, then return the anonymous avatar.

![http://famfamfam.googlecode.com/svn/wiki/images/script_code.png](http://famfamfam.googlecode.com/svn/wiki/images/script_code.png) Made the plugin a little more intelligent. Now it automaticall downloads the anonymous avatar for each service and afterwards checks a users avatar against those anonymous avatars. This method makes the option "mbl anonymous avatar size" obsolete, so I have removed that.

![http://famfamfam.googlecode.com/svn/wiki/images/script_code.png](http://famfamfam.googlecode.com/svn/wiki/images/script_code.png) The cache content has also been rewritten in order to handle both types of avatars.
Rewrote major parts of the plugin

### 2007-01-23 - v0.16 ###
![http://famfamfam.googlecode.com/svn/wiki/images/bug.png](http://famfamfam.googlecode.com/svn/wiki/images/bug.png) When I changed the fetch-method in v0.10 I forgot to also change the method where I check for the latest version. This has been fixed now. Thanks [Joe](http://itsvista.com)

### 2007-03-05 - v0.18 ###
![http://famfamfam.googlecode.com/svn/wiki/images/bug.png](http://famfamfam.googlecode.com/svn/wiki/images/bug.png) The way I find out if a post is a comment, pingback or an actual post was flawed. Thanks [Dennys](http://dennys.tiger2.net) for pointing this out.

![http://famfamfam.googlecode.com/svn/wiki/images/script_code.png](http://famfamfam.googlecode.com/svn/wiki/images/script_code.png) I have also removed the custom class and style on the anonymous avatar selection list, so styles with i.e. `float: right;` doesn't mess it all up. Dennys also gets the credit for pointing this out.

### 2007-05-28 - v0.20 ###
![http://famfamfam.googlecode.com/svn/wiki/images/script_code.png](http://famfamfam.googlecode.com/svn/wiki/images/script_code.png) Widened the automatic cleanup of corrupted/invalid avatars

![http://famfamfam.googlecode.com/svn/wiki/images/script_code.png](http://famfamfam.googlecode.com/svn/wiki/images/script_code.png) Added support for customizing which URL the avatar links to. There might come more options for this, but right now I can't find think of what more to add.

### 2007-07-31 - v0.24 ###
![http://famfamfam.googlecode.com/svn/wiki/images/script_code.png](http://famfamfam.googlecode.com/svn/wiki/images/script_code.png) Major parts of the plugin has been rewritten, including the parts that handle which services it's possible to use. This means that when/if i.e. [Facebook](http://facebook.com) decides to add a way of querying avatars, then it should be rather simple to add that to the list of services.

![http://famfamfam.googlecode.com/svn/wiki/images/script_code.png](http://famfamfam.googlecode.com/svn/wiki/images/script_code.png) The "Avatar Fetch Cycle" has been introduced

![http://famfamfam.googlecode.com/svn/wiki/images/script_code.png](http://famfamfam.googlecode.com/svn/wiki/images/script_code.png) The available options has been cut down to the most essential, but instead of cutting down on the customization then it's now possible to customize the generated HTML code 100%.

![http://famfamfam.googlecode.com/svn/wiki/images/plugin.png](http://famfamfam.googlecode.com/svn/wiki/images/plugin.png) Added [GooglePreview](http://googlepreview.com)'s avatars which rendered [MyBlogLog](http://mybloglog.com) site avatars obsolete. This also means that there is no use for [MyBlogLog](http://mybloglog.com) ID anymore.

### 2007-07-31 - v0.26 ###
![http://famfamfam.googlecode.com/svn/wiki/images/script_code.png](http://famfamfam.googlecode.com/svn/wiki/images/script_code.png) Rewrote the "Custom Anonymous Avatar" to work as a static service instead of a special anonymous avatar. It doesn't really have to have anything to do with anonymousity.

![http://famfamfam.googlecode.com/svn/wiki/images/bug.png](http://famfamfam.googlecode.com/svn/wiki/images/bug.png) Fixed the "Avatar Fetch Cycle" so all boxes work... finally :)

### 2007-07-31 - v0.27b ###
![http://famfamfam.googlecode.com/svn/wiki/images/bug.png](http://famfamfam.googlecode.com/svn/wiki/images/bug.png) Fixed [issue #3](http://code.google.com/p/mbla/issues/detail?id=3)

### 2007-10-18 - v0.29 ###
![http://famfamfam.googlecode.com/svn/wiki/images/plugin.png](http://famfamfam.googlecode.com/svn/wiki/images/plugin.png) Added PapyGeek's suggestion as written in [issue #6](http://code.google.com/p/mbla/issues/detail?id=6)

![http://famfamfam.googlecode.com/svn/wiki/images/script_code.png](http://famfamfam.googlecode.com/svn/wiki/images/script_code.png) Fixed a few things so the code is 100% html compliant

### 2007-11-02 - v0.29debug\_version ###
![http://famfamfam.googlecode.com/svn/wiki/images/plugin.png](http://famfamfam.googlecode.com/svn/wiki/images/plugin.png) Added a debug feature so it's possible to get a readout with timestamps

### 2007-11-03 - v0.36 ###
![http://famfamfam.googlecode.com/svn/wiki/images/plugin.png](http://famfamfam.googlecode.com/svn/wiki/images/plugin.png) Added the debug key to the MBLA option page. The debug feature will only be activated if that debug key is added to the URL like `?debug=xxx` og `&debug=xxx`, where `xxx` is the debug key

![http://famfamfam.googlecode.com/svn/wiki/images/script.png](http://famfamfam.googlecode.com/svn/wiki/images/script.png) Changed the way the anonymous avatar files are fetched, since MyBlogLog has fubar'ed the way that has been handled so far

![http://famfamfam.googlecode.com/svn/wiki/images/plugin.png](http://famfamfam.googlecode.com/svn/wiki/images/plugin.png) Added Gravatar ratings to the MBLA option page

![http://famfamfam.googlecode.com/svn/wiki/images/script_code.png](http://famfamfam.googlecode.com/svn/wiki/images/script_code.png) Some functions has gotten a major overhaul which has resulted in lesser function calls and hopefully faster execution time

![http://famfamfam.googlecode.com/svn/wiki/images/plugin.png](http://famfamfam.googlecode.com/svn/wiki/images/plugin.png) Added some minor error handling statements if any of the function names are already defined which include conflicts with other plugins

![http://famfamfam.googlecode.com/svn/wiki/images/script.png](http://famfamfam.googlecode.com/svn/wiki/images/script.png) Changed the way log files are created. Now a log file will be created for each page that has been called with the debug key. The log files can be viewed from the MBLA Cache Content

### 2007-11-06 - v0.38 ###
![http://famfamfam.googlecode.com/svn/wiki/images/plugin.png](http://famfamfam.googlecode.com/svn/wiki/images/plugin.png) Added a filter hook so the plugin is one step closer to work by just activating it. Big thanks to [Alex](http://afrison.com) for suggesting this.

![http://famfamfam.googlecode.com/svn/wiki/images/script.png](http://famfamfam.googlecode.com/svn/wiki/images/script.png) Created default values for the HTML options which brings the plugin yet another step closer to work by just activating it. Big thanks to [Alex](http://afrison.com) for suggesting this.

### 2007-11-10 - v0.39 ###
![http://famfamfam.googlecode.com/svn/wiki/images/plugin.png](http://famfamfam.googlecode.com/svn/wiki/images/plugin.png) Added a selection list of Wordpress hooks to use. Alternatively it's possible to manually write a wordpress function name or to "inject" the PHP code into the theme's comment page. Sadly this is necessary because not all themes use the same function calls to build the comment page ![http://famfamfam.googlecode.com/svn/wiki/images/emoticon_unhappy.png](http://famfamfam.googlecode.com/svn/wiki/images/emoticon_unhappy.png)

### 2008-05-07 - v0.40 ###
![http://famfamfam.googlecode.com/svn/wiki/images/bug.png](http://famfamfam.googlecode.com/svn/wiki/images/bug.png) Fixed [issue #9](http://code.google.com/p/mbla/issues/detail?id=9)

### 2008-05-19 - v0.42 ###
![http://famfamfam.googlecode.com/svn/wiki/images/plugin.png](http://famfamfam.googlecode.com/svn/wiki/images/plugin.png) Added an option to use Wavatars, MonsterIDs and Identicons through [Gravatar](http://gravatar.com) as described [here](http://blog.gravatar.com/2008/04/22/identicons-monsterids-and-wavatars-oh-my)

![http://famfamfam.googlecode.com/svn/wiki/images/bug.png](http://famfamfam.googlecode.com/svn/wiki/images/bug.png) Fixed Some issues with deleting anonymous avatars.