<?php

/*
Plugin Name: MBLA
Plugin URI: http://kamajole.dk/plugins/mbla/
Description: Use Gravatars and/or MyBlogLog avatars in your posts, comments and pingbacks.
Version: 0.20
Author: Jan Olsen
Author URI: http://kamajole.dk

History:
2007-01-10 - v0.2: Initial 'release'. Idea and credits for this release goes fully to <a target="_blank" href="http://www.napolux.com/2006/12/14/myavatars-a-wordpress-plugin-for-mybloglog/">Napolux</a>.

2007-01-12 - v0.4: A local file cache has been added
The whole plugin has been converted from hardcoded values to values that can be changed in Options -> MBLA

2007-01-13 - v0.5: Class options has been added
Fixed an issue with the size of avatars on trackbacks

2007-01-13 - v0.6: Fixed an issue where the ghost avatar didn't exist. Now it should use MBL's default avatar if the user?s avatar couldn't be found.

2007-01-14 - v0.7: The 'ghost avatar' seemed to rise some questions and since I have no other explanation of calling it a 'ghost avatar' besides my icon looking like a ghost, then I have renamed all occurrences of 'ghost' to 'anonymous'.
I have also added an option to see what the cache contains. On the 'Options' -> 'MBLA' page, there should be a '(show current content of cache)' which opens a new page along with the content of the cache. You can delete a single avatar, so it gets updated the next time it is requested on a post/comment or you can delete alle avatars.

2007-01-14 - v0.8: The plugin now check if there is a newer version of itself available. It's not automatically installing anything - it's just telling you if there is an update.

2007-01-15 - v0.9: Added a feature that lets you use a snapshot of your own site as the avatar of pingback comments (from yourself). In order to use this you have to type in your MyBloglog ID in the options page.

2007-01-17 - v0.10: Rewrote the method that avatars are being fetched. I now use PHP's curl-functions instead of file_get_contents().

2007-01-21 - v0.15: Added Gravatar support.
Choose which service you would look at first (Gravatar or MyBlogLog), grab the avatar from that service and if there isn't an avatar for the given user there, then try to grab one from the other service. If that service doesn't contain an avatar either, then return the anonymous avatar.
Made the plugin a little more intelligent. Now it automaticall downloads the anonymous avatar for each service and afterwards checks a users avatar against those anonymous avatars. This method makes the option "mbl anonymous avatar size" obsolete, so I have removed that.
The cache content has also been rewritten in order to handle both types of avatars.
Rewrote major parts of the plugin

2007-01-23 - v0.16: When I changed the fetch-method in v0.10 I forgot to also change the method where I check for the latest version. This has been fixed now. Thanks <a href="http://itsvista.com/">Joe<a>

2007-03-05 - v0.18: The way I find out if a post is a comment, pingback or an actual post was flawed. Thanks <a href="http://dennys.tiger2.net/">Dennys<a> for pointing this out.
I have also removed the custom class and style on the anonymous avatar selection list, so styles with i.e. "float: right;" doesn't mess it all up. Dennys also gets the credit for pointing this out.

2007-05-28 - v0.20: Widened the automatic cleanup of corrupted/invalid avatars
Added support for customizing which URL the avatar links to. There might come more options for this, but right now I can't find think of what more to add.

To-do:
- 2007-03-12: Prioritize which avatar to use and then use a fall back method if it doesn't exist
*/
$VERSION = '0.20';

add_action ('admin_menu', 'mbla_menu');
$mbla_options = get_option('mbla_options');

function mbla_menu() {
  global $mbla_options;
  add_options_page('MBLA Options', 'MBLA', 9, __FILE__, 'mbla_manage_options');
}

function mbla_message($text='Done', $type='updated') {
  if (empty($type)) { $type = 'updated'; }
  echo "<div id='message' class='{$type} fade'><p>{$text}</p></div>";
}

function mbla_manage_options() {
  global $wpdb, $mbla_options;

  if (! function_exists('curl_init')) {
    echo "<div class='wrap'>";
    echo   "<div style='color: red;'>This plugin only works with <a href='http://php.net/curl' target='_blank'>curl</a> activated in PHP</div>";
    echo "</div>";
    include (ABSPATH . 'wp-admin/admin-footer.php');
    die();
  }

  if ( isset($_GET['showcachecontent']) ) {
    mbla_show_cache_content();
  } else {
    if ( isset($_POST['default']) ) {
      // reset to default values
      mbla_message("MBLA Options Defaulted");
      mbla_default_options('default');
      $mbla_options = get_option('mbla_options');
    } elseif ( isset($_POST['submit']) ) {
      // update values
      mbla_message("MBLA Options Updated");
      mbla_default_options('update',
                           $_POST['anonymous'],
                           $_POST['anonymous_style'],
                           $_POST['anonymous_class'],
                           $_POST['mbl_anonymous_size'],
                           $_POST['regular_style'],
                           $_POST['regular_class'],
                           $_POST['comment_avatar_size'],
                           $_POST['post_avatar_size'],
                           $_POST['cache_location'],
                           $_POST['cache_days'],
                           $_POST['mbl_id'],
                           $_POST['service'],
                           $_POST['anonymous_service'],
                           $_POST['url_target_valid'],
                           $_POST['url_target_anonymous']
                           );
      $mbla_options = get_option('mbla_options');
    } else {
      mbla_default_options();
      $mbla_options = get_option('mbla_options');
    }
  }
}

function mbla_default_options($action              = '',
                              $anonymous           = '',
                              $anonymous_style     = '',
                              $anonymous_class     = '',
                              $mbl_anonymous_size  = '',
                              $regular_style       = '',
                              $regular_class       = '',
                              $comment_avatar_size = '',
                              $post_avatar_size    = '',
                              $cache_location      = '',
                              $cache_days          = '',
                              $mbl_id              = '',
                              $service             = '',
                              $anonymous_service   = '',
                              $url_target_valid    = '',
                              $url_target_anonymous= ''
                             ) {
  global $wpdb, $mbla_options, $HTTP_HOST, $VERSION;

  if ( 'default' == $action ) {
    $mbla_options = array('mbla_anonymous'           => '',
                          'mbla_anonymous_style'     => 'border: 0;',
                          'mbla_anonymous_class'     => '',
                          'mbla_mbl_anonymous_size'  => '721',
                          'mbla_regular_style'       => 'border: 1px solid #E1D6C6',
                          'mbla_regular_class'       => '',
                          'mbla_comment_avatar_size' => '32',
                          'mbla_post_avatar_size'    => '48',
                          'mbla_cache_location'      => '',
                          'mbla_cache_days'          => 1,
                          'mbla_mbl_id'              => '',
                          'mbla_service'             => 'mybloglog',
                          'mbla_anonymous_service'   => 'mybloglog',
                          'mbla_url_target_valid'    => 'http://www.mybloglog.com/buzz/members/{NAME}',
                          'mbla_url_target_anonymous'=> 'http://www.mybloglog.com/buzz/join/',
                         );
    update_option('mbla_options', $mbla_options);
  } elseif ('update' == $action) {
    $mbla_options = array('mbla_anonymous'           => $anonymous,
                          'mbla_anonymous_style'     => $anonymous_style,
                          'mbla_anonymous_class'     => $anonymous_class,
                          'mbla_mbl_anonymous_size'  => $mbl_anonymous_size,
                          'mbla_regular_style'       => $regular_style,
                          'mbla_regular_class'       => $regular_class,
                          'mbla_comment_avatar_size' => $comment_avatar_size,
                          'mbla_post_avatar_size'    => $post_avatar_size,
                          'mbla_cache_location'      => $cache_location,
                          'mbla_cache_days'          => $cache_days,
                          'mbla_mbl_id'              => $mbl_id,
                          'mbla_service'             => $service,
                          'mbla_anonymous_service'   => $anonymous_service,
                          'mbla_url_target_valid'    => $url_target_valid,
                          'mbla_url_target_anonymous'=> $url_target_anonymous,
                         );
    update_option('mbla_options', $mbla_options);
  }
  $admin_email     = get_settings('admin_email');
  $admin_email_md5 = md5($admin_email);
  $anonymous_email = 'xxx@xxx.xxx';
  $anonymous_email_md5 = md5($anonymous_email);
  if (!file_exists($_SERVER['DOCUMENT_ROOT'] . $mbla_options['mbla_cache_location'].'/'.$admin_email_md5))     MyAvatars($admin_email,'', 'never');
  if (!file_exists($_SERVER['DOCUMENT_ROOT'] . $mbla_options['mbla_cache_location'].'/'.$anonymous_email_md5)) MyAvatars($anonymous_email, 'gravatar', 'never');
  if (!file_exists($_SERVER['DOCUMENT_ROOT'] . $mbla_options['mbla_cache_location'].'/'.$anonymous_email_md5)) MyAvatars($anonymous_email, 'mybloglog', 'never');

  echo "<div class='wrap'>";

  echo "<fieldset class='options'>";

  echo "<form method='post' action='{$PHP_SELF}'>";

  echo "<table cellspacing='0' cellpadding='5' border='0' width='100%'>";
  echo "<tr>";
  echo   "<td colspan='3'>";
  echo     "<div style='float: right;'>";
  $latest  = 'http://kamajole.dk/files/?wordpress/plugins/mbla.php';
  $tmpfile = str_replace(array('<br />','&nbsp;'), 
                         array(chr(10).chr(13),' '),
                         curlGet($latest));
  preg_match_all('/Version: (.*)/', $tmpfile, $matches);
  $latest_version = $matches[1][0];
  if (trim($latest_version) != $VERSION) {
    echo   "<div style='color: red;'>You are running version {$VERSION} - there is a <a href='{$latest}'>newer version available {$latest_version}</a></div>";
  } else {
    echo   "<div style='color: green;'>You are running the latest version {$VERSION}</div>";
  }
  echo        "</div>";
  echo     "<h2>MBLA Options</h2>";
  echo   "</td>";
  echo "</tr>";

  echo "<tr>";
  echo   "<td valign='top' style='width: 300px'>";
  echo     "Prioritize which service first?";
  echo   "</td>";
  echo   "<td valign='top' >";
  echo     "<label><input type='radio' name='service' value='gravatar'  ".($mbla_options['mbla_service'] == 'gravatar' || $mbla_options['mbla_service'] == '' ? "checked='{$mbla_options['mbla_service']}'" : '')." /> Gravatar</label><br/>";
  echo     "<label><input type='radio' name='service' value='mybloglog' ".($mbla_options['mbla_service'] == 'mybloglog' ? "checked='{$mbla_options['mbla_service']}'" : '')." /> MyBlogLog</label><br/>";
  echo   "</td>";
  echo   "<td>&nbsp;</td>";
  echo "</tr>";

  $pstsize  = ($mbla_options['mbla_post_avatar_size'] ? "height: {$mbla_options['mbla_post_avatar_size']}px; width: {$mbla_options['mbla_post_avatar_size']}px;" : '');
  echo "<tr>";
  echo   "<td valign='top' >";
  echo     "Avatar size on main posts<br/>";
  echo   "</td>";
  echo   "<td valign='top' ><input type='text' style='text-align: right; width: 50px;' name='post_avatar_size' value='" . $mbla_options['mbla_post_avatar_size'] . "' />px</td>";
  echo   "<td><img style='vertical-align: middle; {$mbla_options['mbla_regular_style']}; {$pstsize}' src='http://{$_SERVER['HTTP_HOST']}{$mbla_options['mbla_cache_location']}/{$mbla_options['mbla_service']}_{$admin_email_md5}' alt='' /></td>";
  echo "</tr>";

  $cmtsize  = ($mbla_options['mbla_comment_avatar_size'] ? "height: {$mbla_options['mbla_comment_avatar_size']}px; width: {$mbla_options['mbla_comment_avatar_size']}px;" : '');
  echo "<tr>";
  echo   "<td valign='top' >";
  echo     "Avatar size on comments<br/>";
  echo   "</td>";
  echo   "<td valign='top' ><input type='text' style='text-align: right; width: 50px;' name='comment_avatar_size' value='" . $mbla_options['mbla_comment_avatar_size'] . "'  />px</td>";
  echo   "<td><img style='vertical-align: middle; {$mbla_options['mbla_regular_style']}; {$cmtsize}' src='http://{$_SERVER['HTTP_HOST']}{$mbla_options['mbla_cache_location']}/{$mbla_options['mbla_service']}_{$admin_email_md5}' alt='' /></td>";
  echo "</tr>";

  echo "<tr>";
  echo   "<td valign='top' >";
  echo     "Check for updated avatar after <em>x</em> day(s)";
  echo   "</td>";
  echo   "<td valign='top' >";
  echo     "<select name='cache_days' style='width: 50px;'>";
  foreach (array(1,2,3,4,5,6,7) AS $i) echo "<option value='{$i}' ".($i == $mbla_options['mbla_cache_days'] ? 'selected' : '').">{$i}</option>";
  echo     "</select>days";
  echo     " (<a href='{$_SERVER['REQUEST_URI']}&amp;showcachecontent'>show current content of cache</a>)";
  echo   "</td>";
  echo   "<td>&nbsp;</td>";
  echo "</tr>";

  echo "<tr>";
  echo   "<td valign='top' >";
  echo     "Avatar cache location<br/>";
  echo     "<em>relative to document root</em>";
  echo   "</td>";
  echo   "<td valign='top' colspan='2'>";
  echo     "<tt style='font-size: 10px;'>{$_SERVER['DOCUMENT_ROOT']}</tt>";
  echo     "<input type='text' name='cache_location' value='" . $mbla_options['mbla_cache_location'] . "' style='width: 250px; font-family: monospace; font-size: 10px;' />";
  echo     (!is_writeable($_SERVER['DOCUMENT_ROOT'] . $mbla_options['mbla_cache_location']) ? "<br/><div style='color: red;'>The cache location isn't writeable!</div>" : '');
  echo   "</td>";
  echo "</tr>";

  echo "<tr>";
  echo   "<td valign='top' >";
  echo     "Custom anonymous avatar file<br/>";
  echo     "<em>relative to document root</em>";
  echo   "</td>";
  echo   "<td valign='top' colspan='2'>";
  echo     "<tt style='font-size: 10px;'>{$_SERVER['DOCUMENT_ROOT']}</tt>";
  echo     "<input type='text' name='anonymous' value='{$mbla_options['mbla_anonymous']}' style='width: 250px; font-family: monospace; font-size: 10px;' />";
  echo     (!file_exists($_SERVER['DOCUMENT_ROOT'] . $mbla_options['mbla_anonymous']) ? "<br/><div style='color: red;'>The anonymous avatar file doesn't exist!</div>" : '');
  echo   "</td>";
  echo "</tr>";

  echo "<tr>";
  echo   "<td valign='top' >";
  echo     "Anonymous avatar file to use<br/>";
  echo   "</td>";
  echo   "<td valign='top' >";
  if ($mbla_options['mbla_anonymous']) {
    echo   "<label><input type='radio' name='anonymous_service' value='custom'    ".($mbla_options['mbla_anonymous_service'] == 'custom' ? "checked='{$mbla_options['mbla_anonymous_service']}'" : '')."/> <img style='{$cmtsize}' src='http://{$_SERVER['HTTP_HOST']}{$mbla_options['mbla_anonymous']}' alt='You own custom anonymous avatar' /></label>";
    echo   str_repeat('&nbsp;', 5);
  }
  echo     "<label><input type='radio' name='anonymous_service' value='gravatar'  ".($mbla_options['mbla_anonymous_service'] == 'gravatar' || $mbla_options['mbla_anonymous_service'] == '' ? "checked='{$mbla_options['mbla_anonymous_service']}'" : '')."/> <img style='{$cmtsize}' src='http://{$_SERVER['HTTP_HOST']}{$mbla_options['mbla_cache_location']}/gravatar_{$anonymous_email_md5}' alt='Gravatar anonymous avatar' /></label>";
  echo     str_repeat('&nbsp;', 5);
  echo     "<label><input type='radio' name='anonymous_service' value='mybloglog' ".($mbla_options['mbla_anonymous_service'] == 'mybloglog' ? "checked='{$mbla_options['mbla_anonymous_service']}'" : '')." /> <img style='{$cmtsize}' src='http://{$_SERVER['HTTP_HOST']}{$mbla_options['mbla_cache_location']}/mybloglog_{$anonymous_email_md5}' alt='MyBlogLog anonymous avatar' /></label>";
  echo   "</td>";
  echo   "<td>&nbsp;</td>";
  echo "</tr>";

  if ( !empty($mbla_options['mbla_mbl_id']) ) {
    $source_url  = "http://s3.amazonaws.com/buzz_sh/{$mbla_options['mbla_mbl_id']}_sh.jpg";
    $target_file = "mybloglog_".md5($mbla_options['mbla_mbl_id']);
    $target_url = downloadURL($source_url, $target_file);
  }
  echo "<tr>";
  echo   "<td valign='top' >";
  echo     "Your MBL ID<br/>";
  echo     "<em>Go to 'MBL' -> 'My Home' -> 'Edit Settings' and see the id in the URL</em>";
  echo   "</td>";
  echo   "<td valign='top' ><input type='text' style='width: 250px;' name='mbl_id' value='{$mbla_options['mbla_mbl_id']}' /></td>";
  if ( !empty($mbla_options['mbla_mbl_id']) ) {
    echo "<td><img class='{$mbla_options['mbla_regular_class']}' style='vertical-align: middle; {$mbla_options['mbla_regular_style']}; ".($mbla_options['mbla_comment_avatar_size'] ? "height: {$mbla_options['mbla_comment_avatar_size']}px; width: {$mbla_options['mbla_comment_avatar_size']}px;" : '')."' src='{$target_url}' alt='' /></td>";
  } else {
    echo "<td>&nbsp;</td>";
  }
  echo "</tr>";

  echo "<tr>";
  echo   "<td valign='top' >Style and class for the anonymous avatar image</td>";
  echo   "<td valign='top' style='white-space: nowrap;'>";
  echo     "<tt>";
  echo       "&lt;img class='<input type='text' name='anonymous_class' value='{$mbla_options['mbla_anonymous_class']}' style='width: 250px; font-family: monospace; font-size: 10px;' />'&gt;<br/>";
  echo       "&lt;img style='<input type='text' name='anonymous_style' value='{$mbla_options['mbla_anonymous_style']}' style='width: 250px; font-family: monospace; font-size: 10px;' />'&gt;";
  echo     "</tt>";
  echo   "</td>";
  echo   "<td><img class='{$mbla_options['mbla_anonymous_class']}' style='{$mbla_options['mbla_anonymous_style']}; {$cmtsize}' src='http://{$_SERVER['HTTP_HOST']}{$mbla_options['mbla_anonymous']}' alt='' /></td>";
  echo "</tr>";

  echo "<tr>";
  echo   "<td valign='top' >Style and class for the regular avatar image</td>";
  echo   "<td valign='top'  style='white-space: nowrap;'>";
  echo     "<tt>";
  echo       "&lt;img class='<input type='text' name='regular_class' value='{$mbla_options['mbla_regular_class']}' style='width: 250px; font-family: monospace; font-size: 10px;' />'&gt;<br/>";
  echo       "&lt;img style='<input type='text' name='regular_style' value='{$mbla_options['mbla_regular_style']}' style='width: 250px; font-family: monospace; font-size: 10px;' />'&gt;";
  echo     "</tt>";
  echo   "</td>";
  echo   "<td><img class='{$mbla_options['mbla_regular_class']}' style='{$mbla_options['mbla_regular_style']}; {$pstsize}' src='http://{$_SERVER['HTTP_HOST']}{$mbla_options['mbla_cache_location']}/{$mbla_options['mbla_service']}_{$admin_email_md5}' alt='' /></td>";
  echo "</tr>";

  echo "<tr>";
  echo   "<td valign='top' >URL which the avatar links to:</td>";
  echo "</tr>";
  echo "<tr>";
  echo   "<td valign='top' style='white-space: nowrap;'>Valid avatars</td>";
  echo   "<td colspan='2'><input type='text' name='url_target_valid' value='{$mbla_options['mbla_url_target_valid']}' style='width: 400px; font-family: monospace; font-size: 10px;' /></td>";
  echo "</tr>";
  echo "<tr>";
  echo   "<td valign='top' style='white-space: nowrap;'>Anonymous avatars</td>";
  echo   "<td colspan='2'><input type='text' name='url_target_anonymous' value='{$mbla_options['mbla_url_target_anonymous']}' style='width: 400px; font-family: monospace; font-size: 10px;' /></td>";
  echo "</tr>";
  echo "<tr>";
  echo   "<td>&nbsp;</td>";
  echo   "<td valign='top' colspan='2'>";
  echo     "The following tags will be replaced (case sensitive):<br/>";
  echo     "<table border='0' cellpadding='1' cellspacing='0'>";
  echo       "<tr><td><tt>{URL}</tt></td><td>the URL from the commenter</td></tr>";
  echo       "<tr><td><tt>{NAME}</tt></td><td>the name of the commenter</td></tr>";
  echo       "<tr><td><tt>{MD5}</tt></td><td>the MD5(email) of the commenter</td></tr>";
  echo       "<tr><td colspan='2'>i.e.: <tt>http://www.mybloglog.com/buzz/members/{NAME}</tt></td></tr>";
  echo     "</table>";
  echo   "</td>";
  echo "</tr>";

  echo "<tr>";
  echo   "<td align='left'>";
  echo     "<input type='submit' name='submit'  style='background-color: #ccffcc;' value=' Update values ' />";
  echo   "</td>";
  echo   "<td align='right' colspan='2'>";
  echo     "<input type='submit' name='default' style='background-color: #ffcccc;' value=' Reset to default values ' />";
  echo   "</td>";
  echo "</tr>";

  echo "</table>";

  echo "</form>";

  echo "</fieldset>";

  echo "</div>";
  include (ABSPATH . 'wp-admin/admin-footer.php');
  die;
}

function mbla_show_cache_content() {
  global $mbla_options;

  echo "<div class='wrap'>";

  echo "<fieldset class='options'>";

  echo "<form method='post' action='{$PHP_SELF}'>";

  echo "<table cellspacing='0' cellpadding='5' border='0' width='100%'>";
  echo "<tr>";
  echo   "<td colspan='9'><h2>MBLA Cache Content</h2></td>";
  echo "</tr>";

  $path = $_SERVER['DOCUMENT_ROOT'].$mbla_options['mbla_cache_location'];
  if (!file_exists($path)) {
    echo "<tr>";
    echo   "<td colspan='9'>Can't find avatar cache location at:</td>";
    echo "</tr>";
    echo "<tr>";
    echo   "<td colspan='9'><tt>{$path}</tt></td>";
    echo "</tr>";
    echo "<tr>";
    echo   "<td colspan='9'>Please correct that problem under Options -> MBLA</td>";
    echo "</tr>";
  } else {
    parse_str($_SERVER['QUERY_STRING'], $url);
    if ( isset($_GET['delete']) ) {
      @unlink($path .'/'.$_GET['delete']);
      unset($url['delete']);
    } elseif ( isset($_GET['deleteall']) ) {
      foreach (glob($path."/{$_GET['deleteall']}*") as $filename) {
        @unlink($filename);
        unset($url['deleteall']);
      }
    }
    $url = str_replace('&', '&amp;', http_build_query($url));
    $resize  = ($mbla_options['mbla_comment_avatar_size'] ? "height: {$mbla_options['mbla_comment_avatar_size']}px; width: {$mbla_options['mbla_comment_avatar_size']}px;" : '');
    echo "<tr>";
    echo   "<td colspan='9'>Content of <tt><a href='{$PHP_SELF}?{$url}'>{$path}</a></tt> (click to reload)</td>";
    echo "</tr>";
    echo "<tr>";
    echo   "<th>&nbsp;</th>";
    echo   "<th style='background-color: #eeeeee; text-align: center;' colspan='4'>Gravatar</th>";
    echo   "<th style='background-color: #dddddd; text-align: center;' colspan='4'>MyBlogLog</th>";
    echo "</tr>";
    echo "<tr>";
    echo   "<th style='text-align: left;'>File name - <tt>md5(email)</tt></th>";
    echo   "<th style='background-color: #eeeeee; text-align: right;'>Size</th>";
    echo   "<th style='background-color: #eeeeee; text-align: center;'>Avatar</th>";
    echo   "<th style='background-color: #eeeeee; text-align: center;'>Date</th>";
    echo   "<th style='background-color: #eeeeee;'>&nbsp;</th>";
    echo   "<th style='background-color: #dddddd; text-align: right;'>Size</th>";
    echo   "<th style='background-color: #dddddd; text-align: center;'>Avatar</th>";
    echo   "<th style='background-color: #dddddd; text-align: center;'>Date</th>";
    echo   "<th style='background-color: #dddddd;'>&nbsp;</th>";
    echo "</tr>";
    $counter = 0;
    $files = array();
    foreach (glob($path.'/*') as $fullfilename) {
      list($service, $basefilename) = explode('_', basename($fullfilename));
      $fsize = filesize($fullfilename);
      if ($fsize <= 256) {
        unlink($fullfilename);
      } else {
        if ($basefilename) {
          $files[$basefilename][$service] = array('fullname' => $fullfilename,
                                                  'filename' => basename($fullfilename),
                                                  'size'     => $fsize,
                                                  'time'     => date('Y-m-d H:i:s', filemtime($fullfilename)),
                                                  'url'      => "http://{$_SERVER['HTTP_HOST']}{$mbla_options['mbla_cache_location']}/".basename($fullfilename),
                                                 );
          $counter++;
        }
      }
    }

    if ($counter) {
      foreach ($files AS $basefilename => $values) {
        echo "<tr>";
        echo   "<td style='text-align: left;'><tt>{$basefilename}</tt></td>";
        foreach (array('gravatar','mybloglog') AS $service) {
          $bg = ($service == 'gravatar' ? 'eeeeee' : 'dddddd');
          if (file_exists($values[$service]['fullname'])) {
            echo   "<td style='background-color: #{$bg}; text-align: right;'>{$values[$service]['size']}</td>";
            echo   "<td style='background-color: #{$bg}; text-align: center;'><img class='{$mbla_options['mbla_regular_class']}' style='{$mbla_options['mbla_regular_style']}; {$resize}' src='{$values[$service]['url']}' alt='{$values[$service]['filename']}' /></td>";
            echo   "<td style='background-color: #{$bg}; text-align: center; white-space: nowrap;'>{$values[$service]['time']}</td>";
            echo   "<td style='background-color: #{$bg}; text-align: center;'><a href='{$PHP_SELF}?{$url}&amp;delete={$values[$service]['filename']}'>Delete</a></td>";
          } else {
            echo "<td style='background-color: #{$bg};' colspan='4'>&nbsp;</td>";
          }
        }
        echo "</tr>";
      }
      echo "<tr>";
      echo   "<td>&nbsp;</td>";
      echo   "<td colspan='4' style='text-align: center;'><a href='{$PHP_SELF}?{$url}&amp;deleteall=gravatar'>Delete all gravatars</a></td>";
      echo   "<td colspan='4' style='text-align: center;'><a href='{$PHP_SELF}?{$url}&amp;deleteall=mybloglog'>Delete all MBL avatars</a></td>";
      echo "</tr>";
    } else {
      echo "<tr>";
      echo   "<td colspan='9'>The avatar cache is empty. <a href='{$PHP_SELF}?{$url}'>Reload cache</a></td>";
      echo "</tr>";
    }
  }
  echo "</table>";

  echo "</form>";

  echo "</fieldset>";

  echo "</div>";
  include (ABSPATH . 'wp-admin/admin-footer.php');
  die;
}


function MyAvatars($INemail = '', $INservice = '', $update_method = 'rules') {
  global $comment, $authordata, $mbla_options;
  $url = false;

  // first find an id which we use to identify the person with
  if ($INemail) {
    $id  = $INemail;
  } elseif ($comment->comment_author_email) {
    $id  = $comment->comment_author_email;
  } elseif ($comment->comment_author_url) {
    $tmp = explode('/', $comment->comment_author_url);
    $id  = "{$tmp[0]}//{$tmp[2]}";
    $url = true;
  } elseif ($authordata->user_email) {
    $id  = $authordata->user_email;
  }
  $md5_id = md5($id);

  // next find out which service we want to identify against
  if ($INservice == 'gravatar' || ($INservice != 'mybloglog' && $mbla_options['mbla_service'] == 'gravatar')) {
    $source_url   = "http://www.gravatar.com/avatar.php?gravatar_id={$md5_id}&rating=X";
  } else {
    if ($url) {
      $source_url = "http://pub.mybloglog.com/coiserv.php?href={$id}";
    } else {
      $source_url = "http://pub.mybloglog.com/coiserv.php?href=mailto:{$id}";
    }
  }

  $target_file = ($INservice ? $INservice : $mbla_options['mbla_service']).'_'.$md5_id;
  $cfile = $_SERVER['DOCUMENT_ROOT'].$mbla_options['mbla_cache_location'].'/'.$target_file;

  $update = false;
  if (file_exists($cfile)) {
    if ($update_method == 'never') {
      $update = false;
    } elseif ($update_method == 'always') {
      $update = true;
    } elseif ($update_method == 'rules') {
      $cdate = date('Y-m-d H:i:s', filemtime($cfile));
      if (date('Y-m-d H:i:s', strtotime("{$cdate} +{$mbla_options['mbla_cache_days']} days")) < date('Y-m-d H:i:s')) {
        $update = true;
      } elseif (filesize($cfile) == 0) {
        $update = true;
      }
    }
  } else {
    $update = true;
  }

  if ($update) {
    // we are updating the avatar before showing it
    $target_url = downloadURL($source_url, $target_file);
  } else {
    // we don't need to update the avatar so everything should be ready
    $target_url = "http://{$_SERVER['HTTP_HOST']}{$mbla_options['mbla_cache_location']}/{$target_file}";
  }
//  echo "ID:{$id}, source_url:{$source_url}, EMAIL:{$INemail}, SERVICE:{$INservice}, UPDATE:{$update}, TARGET_FILE:{$target_file}, TARGET_URL:{$target_url}<br/>";

  $mysite  = "http://".$_SERVER['HTTP_HOST'];

  // $comment               === NULL         -> post
  // $comment->comment_type  == 'pingback'   -> pingback
  // $comment->comment_type  == ''           -> comment
  if ($comment === NULL) {
    $_class = $mbla_options['mbla_regular_class'];
    $_style = $mbla_options['mbla_regular_style'];
    $_size  = "height: {$mbla_options['mbla_post_avatar_size']}px; width: {$mbla_options['mbla_post_avatar_size']}px;";

    $_linkto = '';
  } elseif ($comment->comment_type == 'pingback') {
    if (!empty($mbla_options['mbla_mbl_id']) && (substr($_linkto, 0, strlen($mysite)) == $mysite) ) {
      $target_url = "http://{$_SERVER['HTTP_HOST']}{$mbla_options['mbla_cache_location']}/mybloglog_".md5($mbla_options['mbla_mbl_id']);
    }
    $_class = $mbla_options['mbla_regular_class'];
    $_style = $mbla_options['mbla_regular_style'];
    $_size  = "height: {$mbla_options['mbla_comment_avatar_size']}px; width: {$mbla_options['mbla_comment_avatar_size']}px;";

    $_linkto = $comment->comment_author_url;
  } elseif ($comment->comment_type == '' || $comment->comment_type == 'comment') {
    $_class = $mbla_options['mbla_regular_class'];
    $_style = $mbla_options['mbla_regular_style'];
    $_size  = "height: {$mbla_options['mbla_comment_avatar_size']}px; width: {$mbla_options['mbla_comment_avatar_size']}px;";

    if (strpos($target_url, 'mybloglog_') > 0 ||
        strpos($target_url, 'gravatar_') > 0) {
      $_tmp = $mbla_options['mbla_url_target_valid'];
    } else {
      $_tmp = $mbla_options['mbla_url_target_anonymous'];
    }

    $_linkto = str_replace(array('{URL}',
                                 '{NAME}',
                                 '{MD5}'),
                           array($comment->comment_author_url,
                                 $comment->comment_author,
                                 md5($comment->comment_author_email)),
                           $_tmp
                           );
  }

  if ($INemail == '') {
    $avatar  = "<a href='{$_linkto}' target='_blank'>";
    $avatar .=   "<img class='{$_class}' style='{$_style}; {$_size}' src='{$target_url}' alt='{$_linkto}' title='{$_linkto}' />";
    $avatar .= "</a>";

    echo $avatar;
  }
}

function curlGet($URL) {
  $ch = curl_init();
  $timeout = 3;
  curl_setopt($ch, CURLOPT_URL, $URL);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
  $tmp = curl_exec($ch);
  curl_close($ch);
  return $tmp;
}

function downloadURL($URL, $NEWNAME = '') {
  global $mbla_options;
  $anonymous_email = 'xxx@xxx.xxx';
  $anonymous_email_md5 = md5($anonymous_email);

  $tmp = curlGet($URL);

  $ret = '';
  if ($NEWNAME) {
    // which anonymous avatar file do we want to end up with?
    switch ($mbla_options['mbla_anonymous_service']) {
      case 'custom'    : $anonymous_avatar = "http://{$_SERVER['HTTP_HOST']}{$mbla_options['mbla_anonymous']}";
                         break;
      case 'mybloglog' : $anonymous_avatar = "http://{$_SERVER['HTTP_HOST']}{$mbla_options['mbla_cache_location']}/mybloglog_{$anonymous_email_md5}";
                         break;
      case 'gravatar'  :
      default          : $anonymous_avatar = "http://{$_SERVER['HTTP_HOST']}{$mbla_options['mbla_cache_location']}/gravatar_{$anonymous_email_md5}";
                         break;
    }

    if (strlen($tmp) < 128) {
      $ret = $anonymous_avatar ;
    } else {
      $gravatar_file  = "{$_SERVER['DOCUMENT_ROOT']}{$mbla_options['mbla_cache_location']}/gravatar_{$anonymous_email_md5}";
      $mybloglog_file = "{$_SERVER['DOCUMENT_ROOT']}{$mbla_options['mbla_cache_location']}/mybloglog_{$anonymous_email_md5}";
      if ((file_exists($gravatar_file)  && md5($tmp) == md5_file($gravatar_file)) ||
          (file_exists($mybloglog_file) && md5($tmp) == md5_file($mybloglog_file))) {
        // the user doesn't have an avatar at the wanted service so show the one we have decided on
        $ret = $anonymous_avatar ;
      } else {
        $ret = "http://{$_SERVER['HTTP_HOST']}{$mbla_options['mbla_cache_location']}/{$NEWNAME}";
        file_put($_SERVER['DOCUMENT_ROOT'].$mbla_options['mbla_cache_location'].'/'.$NEWNAME, $tmp);
      }
    }
  }
  return $ret;
}


function file_put($n, $d) {
  $f = @fopen($n,"w");
  if (!$f) {
   return false;
  } else {
   fwrite($f,$d);
   fclose($f);
   return true;
  }
}

?>