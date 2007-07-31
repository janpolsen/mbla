<?php
/*
Plugin Name: MBLA
Plugin URI: http://kamajole.dk/plugins/mbla/
Description: Use avatars from services like Gravatar and MyBlogLog in your posts, comments and pingbacks.
Version: 0.26
Author: Jan Olsen
Author URI: http://kamajole.dk
*/
add_action ('admin_menu', 'mbla_menu');
$mbla_options = get_option('mbla_options');
$mbla = array('services'            => array('mybloglog' => 'MyBlogLog',
                                             'gravatar'  => 'Gravatar',
                                             ),
              'urlcache'            => "http://{$_SERVER['HTTP_HOST']}{$mbla_options['cache_location']}",
              'filecache'           => "{$_SERVER['DOCUMENT_ROOT']}{$mbla_options['cache_location']}",
              'filecustom'          => "{$_SERVER['DOCUMENT_ROOT']}{$mbla_options['custom']}",
              'urlcustom'           => "http://{$_SERVER['HTTP_HOST']}{$mbla_options['custom']}",
              'anonymous_email'     => 'xxx@xxx.xxx',
              'anonymous_email_md5' => md5('xxx@xxx.xxx'),
              );

function mbla_menu() {
//  global $mbla_options;
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
      mbla_message("MBLA options restored to default values");
      mbla_default_options('default');
    } elseif ( isset($_POST['submit']) ) {
      // update values
      mbla_message("MBLA Options Updated");
      mbla_default_options('update', $_POST);
    } else {
      mbla_default_options();
    }
    $mbla_options = get_option('mbla_options');
  }
}

function mbla_default_options($action = '', $inarr = array()) {
  global $wpdb, $mbla_options, $mbla;
//  $admin_email     = get_settings('admin_email');
//  $admin_email_md5 = md5($admin_email);

  if ( 'default' == $action ) {
    $mbla_options = array('cache_days'          => 3,
                          'anonymous_service'   => implode('', array_keys(array_slice($mbla['services'], 0, 1))),
                          'prival'              => implode(',',array_keys($mbla['services'])).',custom,none'
                         );
    update_option('mbla_options', $mbla_options);
  } elseif ('update' == $action) {
    $mbla_options = $_POST;

    // make sure we have our anonymous avatars
    // we need to do this here, so we can catch our md5 values of the anonymous avatars and save them at the same time
    if ($mbla_options['cache_location']) {
      // first grap the admins avatar
      foreach (array_reverse(explode(',', $mbla_options['prival'])) AS $tabid) {
        $service = str_replace('_anon', '', $tabid);
        if (strpos($tabid, '_anon')) {
          $md5 = fetchAvatar($mbla['anonymous_email'], $service);
          $mbla_options['md5_anon'][str_replace('_anon', '', $tabid)] = $md5['file'];
        }
      }
    }

    update_option('mbla_options', $mbla_options);
  }

  $info = "The following keywords will be replaced (case sensitive):<br/>";
  $arr_keywords = array('URL'        => 'the URL from the commenter',
                        'NAME'       => 'the name of the commenter',
                        'MD5'        => 'the MD5(email) of the commenter',
                        'TITLE'      => 'the title of the post',
                        'AVATAR'     => 'the avatar of the commenter',
                       );
  $info .= "<table border='0' cellpadding='1' cellspacing='0'>";
  foreach ($arr_keywords AS $_keyword => $_title) {
    $info .= "<tr><td><tt>{{$_keyword}}</tt></td><td>{$_title}</td></tr>";
  }
  $info   .= "<tr><td colspan='2'>Sample usage:<pre>".
             "&lt;div style=&quot;float: left;&quot;&gt;
  &lt;a href=&quot;{URL}&quot; title=&quot;Visit {NAME}'s site&quot;&gt;
    &lt;img src=&quot;{AVATAR}&quot; alt=&quot;{NAME} commenting on {TITLE}&quot; style=&quot;height: 32px&quot; /&gt;
  &lt;/a&gt;
&lt;/div&gt;".
             "</pre></td></tr>";
  $info .= "</table>";
?>
<script type="text/javascript">
function displayTab(obj) {
  var tabs = document.getElementsByTagName('fieldset');
  for (var x = 0; x < tabs.length; x++) {
    tabs[x].style.display = (x == obj ? '' : 'none');
  }
}

if (!window.element) {
  function element(id) {
    if (document.getElementById != null) {
      return document.getElementById(id);
    }
    if (document.all != null) {
      return document.all[id];
    }
    if (document.layers != null) {
      return document.layers[id];
    }
  }
}

function switchPri(box1, box2) {
  var tmp = element(box1).innerHTML;
  element(box1).innerHTML = element(box2).innerHTML;
  element(box2).innerHTML = tmp;

  var pri = element('pri').getElementsByTagName('input');
  var tmp = '';
  for (var x = 0; x < pri.length; x++) {
    tmp += pri[x].value + ',';
  }
  element('prival').value = tmp.slice(0, -1);
}
</script>

<?php
  echo "<div class='wrap'>";

  echo   "<div style='float: right;'>".checkVersion()."</div>";
  echo "<h2>MBLA Options</h2>";

  if (is_writeable($mbla['filecache'])) {
    $tabcnt = 0;
    foreach (array('general' => 'General',
                   'html'    => 'HTML',
                   'help'    => 'Help',
                   ) AS $tabid => $tabtitle) {
      echo "<input type='button' class='button' value='{$tabtitle} &raquo;' onclick=\"displayTab({$tabcnt});\" />";
      $tabcnt++;
    }
  }
  echo "<br/>";

  echo "<form method='post' action='{$PHP_SELF}'>";

  echo "<fieldset>";

  echo "<table cellspacing='0' cellpadding='5' border='0' width='100%'>";

  echo "<tr>";
  echo   "<td>";
  echo     "Avatar cache location<br/>";
  echo     "<em>relative to document root</em>";
  echo   "</td>";
  echo   "<td colspan='2'>";
  echo     "<tt style='font-size: 10px;'>{$_SERVER['DOCUMENT_ROOT']}</tt>";
  echo     "<input type='text' name='cache_location' value='{$mbla_options['cache_location']}' style='width: 250px; font-family: monospace; font-size: 10px;' />";
  if ($mbla_options['cache_location'] && !is_writeable("{$_SERVER['DOCUMENT_ROOT']}{$mbla_options['cache_location']}")) {
    echo "<br/><i style='color: red;'>{$_SERVER['DOCUMENT_ROOT']}{$mbla_options['cache_location']} isn't writeable! (".substr(sprintf('%o', @fileperms($mbla['filecache'])), -4).")</i>";
  }
  echo   "</td>";
  echo "</tr>";

  echo "<tr>";
  echo   "<td valign='top' >";
  echo     "Custom avatar file<br/>";
  echo     "<em>relative to document root</em>";
  echo   "</td>";
  echo   "<td valign='top' colspan='2'>";
  echo     "<tt style='font-size: 10px;'>{$_SERVER['DOCUMENT_ROOT']}</tt>";
  echo     "<input type='text' name='custom' value='{$mbla_options['custom']}' style='width: 250px; font-family: monospace; font-size: 10px;' />";
  if ($mbla_options['custom'] && !file_exists("{$_SERVER['DOCUMENT_ROOT']}{$mbla_options['custom']}")) {
    echo  "<br/><i style='color: red;'>The custom avatar file doesn't exist!</i>";
  }
  echo   "</td>";
  echo "</tr>";

  echo "<tr>";
  echo   "<td valign='top' style='width: 300px'>";
  echo     "Avatar Fetch Cycle:<br/><i>Use &and; and &or; to change the order of the boxes. Place the 'None' box where you want the script to stop looking for other avatars.</i>";
  echo   "</td>";
  echo   "<td valign='top' >";
  $avail_services = array();
  foreach ($mbla['services'] AS $tabid => $tabtitle) {
    $avail_services[$tabid] = $tabtitle;
    $avail_services[$tabid.'_anon'] = $tabtitle.'\'s Anonymous Avatar';
  }
  if ($mbla_options['custom']) {
    $avail_services['custom'] = 'Custom Avatar';
  }
  $avail_services['none'] ='None';
  $choosen_services = explode(',', $mbla_options['prival']);
  $choosenplus_services = array_merge(array_intersect($choosen_services, array_keys($avail_services)), array_diff(array_keys($avail_services), $choosen_services));
  $invalid = $tabcnt = 0; $first  = true;
  echo           "<table id='pri' cellpadding='3' cellspacing='1' border='0' width='150px'>";
  foreach ($choosenplus_services AS $tabid) {
    if ($avail_services[$tabid]) {
      echo           "<tr>";
      echo             "<td id='pri{$tabcnt}' style='border: 1px solid #cccc99; background-color: #ffffcc; white-space: nowrap; text-align: center;'>";
      echo               "&nbsp;";
      echo               "{$avail_services[$tabid]}&nbsp;";
      echo               "<input type='hidden' id='prival{$tabcnt}' value='{$tabid}' />";
      echo             "</td>";
      if ($first) {
        echo           "<td>&nbsp;</td>";
      } else {
        echo           "<td style='cursor: pointer;' onclick=\"switchPri('pri{$tabcnt}', 'pri".($tabcnt-1)."');\"> &and; </td>";
      }
      if ($tabcnt < count($choosenplus_services) - 1 - $invalid) {
        echo           "<td style='cursor: pointer;' onclick=\"switchPri('pri{$tabcnt}', 'pri".($tabcnt+1)."');\"> &or; </td>";
      } else {
        echo           "<td>&nbsp;</td>";
      }
      echo           "</tr>";
      $first = false;
      $tabcnt++;
    } else {
      $invalid++;
    }
  }
  echo           "<input type='hidden' size='80' name='prival' id='prival' value='".implode(',',$choosenplus_services)."' />";
  echo     "</table>";
  echo   "</td>";
  echo   "<td>&nbsp;</td>";
  echo "</tr>";

  echo "<tr>";
  echo   "<td>";
  echo     "Check for updated avatar after <em>x</em> day(s)";
  echo   "</td>";
  echo   "<td>";
  echo     "<select name='cache_days' style='width: 50px;'>";
  foreach (array(1,2,3,4,5,6,7) AS $i) echo "<option value='{$i}' ".($i == $mbla_options['cache_days'] ? 'selected' : '').">{$i}</option>";
  echo     "</select>days";
  echo     " (<a href='{$_SERVER['REQUEST_URI']}&amp;showcachecontent'>show current content of cache</a>)";
  echo   "</td>";
  echo   "<td>&nbsp;</td>";
  echo "</tr>";
  echo "</table>";

  foreach ($mbla['services'] AS $tabid => $tabtitle) {
    echo  "<input type='hidden' name='md5_anon[{$tabid}]' value='".$mbla_options['md5_anon'][$tabid]."' /><br/>";
  }
  echo "</fieldset>"; // General


  echo "<fieldset style='display: none;'>";
  echo   "<table cellspacing='0' cellpadding='5' border='0' width='100%'>";
  echo     "<tr>";
  echo       "<td valign='bottom'>HTML code for </td>";
  echo       "<td><i>See available keywords at the bottom this page</i></td>";
  echo     "</tr>";

  foreach (array('post'         => 'posts',
                 'comment'      => 'comments',
                 'comment_anon' => 'comments (anonymous)',
                 'pingback'     => 'pingbacks',
                 'none'         => 'No avatar available') AS $typid => $typtitle) {
    echo   "<tr>";
    echo     "<td valign='top' style='white-space: nowrap;'><b>".ucfirst($typtitle)."</b></td>";
    echo     "<td width='100%'>";
    echo        "<textarea name='final_html_{$typid}' rows='5' cols='80' style='font-family: monospace; font-size: 10px;'>";
    echo          stripslashes($mbla_options["final_html_{$typid}"]);
    echo        "</textarea>";
    echo     "</td>";
    echo   "</tr>";
  }
  echo     "<tr>";
  echo       "<td>&nbsp;</td>";
  echo       "<td>{$info}</td>";
  echo     "</tr>";

  echo   "</table>";
  echo "</fieldset>"; // HTML

  echo "<fieldset style='display: none;'>";
  echo   "<p>This <a href='http://wordpress.org'>Wordpress</a> plugin is hosted at <a href='http://code.google.com/p/mbla/'>Google Code</a>, which means that everything about <a href='http://code.google.com/p/mbla/downloads/list'>download</a>, <a href='http://code.google.com/p/mbla/wiki/Installation'>installation</a>, <a href='http://code.google.com/p/mbla/issues/list'>issues</a> and <a href='http://code.google.com/p/mbla/wiki/Help'>help</a> can be found there.</p>";
  echo   "<p>";
  echo     "This plugin makes use of:";
  echo     "<ul>";
  echo       "<li><a href='http://mybloglog.com'>MyBlogLog avatars</a></li>";
  echo       "<li><a href='http://gravastars.com'>Gravatars</a></li>";
  echo       "<li><a href='http://googlepreview.com'>GooglePreview</a></li>";
  echo     "</ul>";
  echo   "</p>";
  echo "</fieldset>"; // Help

  echo "<table cellspacing='0' cellpadding='5' border='0' width='100%'>";
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

  echo "</div>";
  include (ABSPATH . 'wp-admin/admin-footer.php');
  die;
}

function mbla_show_cache_content() {
  global $mbla, $mbla_options;

  echo "<div class='wrap'>";

  echo "<form method='post' action='{$PHP_SELF}'>";

  echo "<table cellspacing='0' cellpadding='5' border='0' width='100%'>";
  echo "<tr>";
  echo   "<td colspan='9'><h2>MBLA Cache Content</h2></td>";
  echo "</tr>";

  if (!file_exists($mbla['filecache'])) {
    echo "<tr>";
    echo   "<td colspan='9'>Can't find avatar cache location at:</td>";
    echo "</tr>";
    echo "<tr>";
    echo   "<td colspan='9'><tt>{$mbla['filecache']}</tt></td>";
    echo "</tr>";
    echo "<tr>";
    echo   "<td colspan='9'>Please correct that problem under Options -> MBLA</td>";
    echo "</tr>";
  } else {
    parse_str($_SERVER['QUERY_STRING'], $url);
    if ( isset($_GET['delete']) ) {
      switch ($_GET['delete']) {
        case 'all':  foreach (glob($mbla['filecache']."/*") as $filename) {
                       @unlink($filename);
                       unset($url['delete']);
                     }
                     break;

        case 'anon': foreach (glob($mbla['filecache']."/*") as $filename) {
                       foreach ($mbla['services'] AS $tabid => $tabtitle) {

                         if (file_exists($filename) && in_array(md5_file($filename), $mbla_options['md5_anon'])) {
                           unlink($filename);
                         }
                       }
                       unset($url['delete']);
                     }
                     break;

         default   : @unlink($mbla['filecache'] .'/'.$_GET['delete']);
                     break;
      }
      unset($url['delete']);
    }
    $cache = glob($mbla['filecache'].'/*');
    $url = str_replace('&', '&amp;', http_build_query($url));
    echo "<tr>";
    echo   "<td colspan='9'>Content of <tt><a href='{$PHP_SELF}?{$url}'>{$mbla['filecache']}</a></tt> (click to reload)</td>";
    echo "</tr>";
    if ($cache) {
      echo "<tr>";
      echo   "<th>&nbsp;</th>";
      echo   "<th style='background-color: #eee; text-align: center;' colspan='4'>Avatar</th>";
      echo "</tr>";
      echo "<tr>";
      echo   "<th style='text-align: left;'>File name - <tt>md5(email)</tt></th>";
      echo   "<th style='background-color: #eee; text-align: right;'>Size</th>";
      echo   "<th style='background-color: #eee; text-align: center;'>Avatar</th>";
      echo   "<th style='background-color: #eee; text-align: center;'>Date</th>";
      echo   "<th style='background-color: #eee;'>&nbsp;</th>";
      echo "</tr>";
      $counter = $size = 0;
      $files = array();
      foreach ($cache as $fullfilename) {
        $fsize = filesize($fullfilename);
        if ($fsize <= 256) {
          unlink($fullfilename);
        } else {
          $basefilename = basename($fullfilename);
          echo "<tr>";
          echo   "<td style='text-align: left;'><tt>{$basefilename}</tt></td>";
          echo   "<td style='background-color: #eee; text-align: right;'>{$fsize}</td>";
          echo   "<td style='background-color: #eee; text-align: center;'><img class='{$mbla_options['regular_class']}' style='{$mbla_options['regular_style']}; height: 32px; width: 32px;' src='"."{$mbla['urlcache']}/{$basefilename}' alt='{$basefilename}' /></td>";
          echo   "<td style='background-color: #eee; text-align: center; white-space: nowrap;'>".date('Y-m-d H:i:s', filemtime($fullfilename))."</td>";
          echo   "<td style='background-color: #eee; text-align: center;'><a href='{$PHP_SELF}?{$url}&amp;delete={$basefilename}'>Delete</a></td>";
          echo "</tr>";
          $counter++;
          $size += $fsize;
        }
      }
      echo "<tr>";
      echo   "<th style='text-align: left;'>Total cache size</th>";
      echo   "<th style='background-color: #eee; text-align: right;'>{$size}</th>";
      echo   "<th style='background-color: #eee;' colspan='3'>&nbsp;</th>";
      echo "</tr>";

      echo "<tr>";
      echo   "<td>&nbsp;</td>";
      echo   "<td colspan='4' style='text-align: center;'><a href='{$PHP_SELF}?{$url}&amp;delete=anon'>Delete all anonymous avatars</a></td>";
      echo "</tr>";
      echo "<tr>";
      echo   "<td>&nbsp;</td>";
      echo   "<td colspan='4' style='text-align: center;'><a href='{$PHP_SELF}?{$url}&amp;delete=all'>Delete all avatars</a></td>";
      echo "</tr>";
    } else {
      echo "<tr>";
      echo   "<td colspan='5'>The avatar cache is empty. <a href='{$PHP_SELF}?{$url}'>Reload cache</a></td>";
      echo "</tr>";
    }
  }
  echo "</table>";

  echo "</form>";

  echo "</div>";
  include (ABSPATH . 'wp-admin/admin-footer.php');
  die;
}

function id2URL($service, $id, $md5id) {
  global $mbla, $mbla_options;
  if (strpos($id, '@')) {
    switch (strtolower($service)) {
      case 'gravatar'   : return "http://www.gravatar.com/avatar.php?gravatar_id={$md5id}&rating=X";
                          break;

      case 'mybloglog'  : return "http://pub.mybloglog.com/coiserv.php?href=mailto:{$id}";
                          break;

      case 'custom'     : return "{$mbla['urlcustom']}";
                          break;

      case 'anon'       : return "{$mbla['urlcache']}/{$mbla['anonymous_email_md5']}";
                          break;

      default           : return null; break;
    }
  } else {
    $tmp = parse_url($id);
    return "{$tmp['scheme']}://".$tmp['host']{0}.".googlepreview.com/preview?s={$tmp['scheme']}://{$tmp['host']}";
  }
}

function updateNeeded($id) {
  global $mbla, $mbla_options;
  $update = false;
  $avatar = "{$mbla['filecache']}/{$id}";
  if (file_exists($avatar)) {
    $cdate = date('Y-m-d H:i:s', filemtime($avatar));
    if (date('Y-m-d H:i:s', strtotime("{$cdate} +{$mbla_options['cache_days']} days")) < date('Y-m-d H:i:s')) {
      $update = true;
    } elseif (filesize($avatar) == 0) {
      $update = true;
    }
  } else {
    $update = true;
  }
  return $update;
}


function fetchAvatar($INemail = null, $INservice = null) {
  global $mbla, $comment, $authordata, $mbla_options;

  if ($INemail) {
    $id  = $INemail;
  } elseif ($comment->comment_author_email) {
    $id  = $comment->comment_author_email;
  } elseif ($comment->comment_author_url) {
    $tmp = explode('/', $comment->comment_author_url);
    $id  = "{$tmp[0]}//{$tmp[2]}";
  } elseif ($authordata->user_email) {
    $id  = $authordata->user_email;
  }
  $md5id = md5($id);

  if (updateNeeded($md5id) || $INservice) {
    // an update is needed
    $services = ($INservice ? array($INservice) : explode(',', $mbla_options['prival']));
    while ($service = array_shift($services)) {
      if ('none' == $service) {
        // always bail out on the "none" service
        return null;
      } else {
        // cycle through the services

          switch ($service) {
            case 'gravatar'      : $remoteFileURL = id2URL('gravatar' , $id, $md5id); break;
            case 'mybloglog'     : $remoteFileURL = id2URL('mybloglog', $id, $md5id); break;
            case 'custom'        : $remoteFileURL = id2URL('custom'   , $id, $md5id); break;
            case 'mybloglog_anon':
            case 'gravatar_anon' : $remoteFileURL = id2URL('anon'     , $id, $md5id); $INservice = 'localanon'; break;
            case 'none'          :
            default              : return null;
          }

          $ret = downloadURL($remoteFileURL, $md5id, $INservice);

          if (!isAnon($ret) || $INservice) {
            return array('file' => $ret,
                         'id'   => $md5id);
          }
      }
    }
  } else {
    // no update is needed so just return the md5id
    return array('file' => null,
                 'id'   => $md5id);
  }
}

function MyAvatars($INemail = '', $INservice = '', $update_method = 'rules') {
  global $mbla, $comment, $authordata, $mbla_options;

//  $update_method= 'always';
  $md5 = fetchAvatar();

  // $comment->comment_ID   === NULL         -> post
  // $comment->comment_type  == 'pingback'   -> pingback
  // $comment->comment_type  == ''           -> comment

  if ($md5 === null) {
    $based_on = 'final_html_none';
  } elseif ($comment->comment_ID === NULL) {
    $based_on = 'final_html_post';
  } elseif ($comment->comment_type == 'pingback') {
    $based_on = 'final_html_pingback';
  } elseif ($comment->comment_type == '' || $comment->comment_type == 'comment') {
    $based_on = 'final_html_comment';
    if (isAnon($md5['file'])) {
      $based_on .= '_anon';
    }
  }
  $avatar = str_replace(array('{URL}',
                              '{NAME}',
                              '{MD5}',
                              '{TITLE}',
                              '{AVATAR}',
                              '{MBLID}',
                             ),
                        array($comment->comment_author_url,
                              $comment->comment_author,
                              md5($comment->comment_author_email),
                              the_title('', '', false),
                              "{$mbla['urlcache']}/{$md5['id']}",
                              $mbla_options['mbl_id'],
                             ),
                        stripslashes($mbla_options[$based_on])
                        );

  if ($INemail == '') {
    echo $avatar;
  }
}

function isAnon($INmd5) {
  global $mbla_options;
  return in_array($INmd5, (array)$mbla_options['md5_anon']);
}


function downloadURL($URL, $NEWNAME, $forced) {
  global $mbla_options, $mbla;

  $tmp = curlGet($URL);
  $tmp_md5 = md5($tmp);

  if (!isAnon($tmp_md5) || $forced) {
    file_put("{$mbla['filecache']}/{$NEWNAME}", $tmp);
  }
  return $tmp_md5;
}

if (!function_exists('file_put')) {
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
}

if(!function_exists('curlGet')) {
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
}

if (!function_exists('checkVersion')) {
  function checkVersion() {
    $latest  = "http://".strtr(basename($_GET['page'], '.php'), '_', '-').".googlecode.com/svn/trunk/{$_GET['page']}";
    $tmpfile = str_replace(array('<br />','&nbsp;'),
                           array(chr(10).chr(13),' '),
                           curlGet($latest));
    preg_match_all('/Version: (.*)/', $tmpfile, $matches);
    $latest_version = $matches[1][0];
    $_tmp = file(dirname(__FILE__).'/'.$_GET['page']);
    list($dummy, $this_version) = explode(' ', $_tmp[5]);
    if (trim($latest_version) != trim($this_version)) {
      return "<div style='color: red;'>You are running version {$this_version} - there is a <a href='{$latest}'>newer version {$latest_version} available</a></div>";
    } else {
      return "<div style='color: green;'>You are running the latest version {$this_version}</div>";
    }
  }
}

?>