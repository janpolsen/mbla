<?php
/*
Plugin Name: MBLA
Plugin URI: http://mbla.googlecode.com
Description: Use avatars from services like Gravatar and MyBlogLog in your posts, comments and pingbacks. Remember to change options at <a href="options-general.php?page=mbla/mbla.php">Options -&gt; MBLA</a>.
Version: 0.42
Author: Jan Olsen
Author URI: http://kamajole.dk
*/
$mbla_options = get_option( 'mbla_options' );

add_action( 'admin_menu' , 'mbla_menu' );
if ($mbla_options['wphook']) {
    if ('other' == $mbla_options['wphook']) {
        add_filter($mbla_options['wphook_other'], 'mbla');
    } else {
        add_filter($mbla_options['wphook'], 'mbla');
    }
}

$mbla = array(
    'services' => array(
        'mybloglog' => 'MyBlogLog',
        'gravatar' => 'Gravatar' ),
    'urlcache' => "http://{$_SERVER['HTTP_HOST']}{$mbla_options['cache_location']}",
    'filecache' => "{$_SERVER['DOCUMENT_ROOT']}{$mbla_options['cache_location']}",
    'filecustom' => "{$_SERVER['DOCUMENT_ROOT']}{$mbla_options['custom']}",
    'urlcustom' => "http://{$_SERVER['HTTP_HOST']}{$mbla_options['custom']}",
    'anonymous_email' => '0x42@xxx.xxx' );
$mbla [ 'anonymous_file_md5' ] = @md5_file( $mbla [ 'filecustom' ] );
$mbla [ 'anonymous_email_md5' ] = md5( $mbla [ 'anonymous_email' ] );

if ( ! function_exists( 'file_put_contents' ) ) {
    define( 'FILE_APPEND' , 1 );

    /**
     * Support for PHP4
     * Write a string to a file
     *
     * @param unknown_type $n Full file name to write to
     * @param unknown_type $d String to write
     * @param unknown_type $flag Use FILE_APPEND for appending
     * @return unknown
     */
    function file_put_contents( $n, $d, $flag = false ) {
        $mode = ( $flag == FILE_APPEND || strtoupper( $flag ) == 'FILE_APPEND' ) ? 'a' : 'w';
        $f = @fopen( $n , $mode );
        if ( $f === false ) {
            return 0;
        } else {
            if ( is_array( $d ) )
                $d = implode( $d );
            $bytes_written = fwrite( $f , $d );
            fclose( $f );
            return $bytes_written;
        }
    }
}

if ( ! function_exists( 'logger' ) ) {
    if ( $_GET [ 'debug' ] == $mbla_options [ 'debug_key' ] ) {
        $logfile = "{$mbla['filecache']}/mbla_" . basename( $_SERVER [ 'SCRIPT_NAME' ] , '.php' ) . ".log";
        $loglevel = 0;

        /**
         * helper function to do the actual logging to the default log file IF we are in debug mode
         *
         * @param string $str string to log
         * @param int offset compared to previous offset
         */
        function logger( $str, $loglevel_offset = 0 ) {
            global $mbla, $mbla_options, $logfile, $loglevel;
            if ( $str ) {
                list( $usec , $sec ) = explode( " " , microtime() );
                if ( $loglevel_offset < 0 )
                    $loglevel += $loglevel_offset;
                file_put_contents( $logfile , sprintf( "%s%03s | %s %s\n" , date( "Y-m-d H:i:s." ) , floor( $usec * 1000 ) , str_repeat( '  ' , $loglevel ) , $str ) , FILE_APPEND );
                if ( $loglevel_offset > 0 )
                    $loglevel += $loglevel_offset;
            }
        }
        file_put_contents( $logfile , '' );
        logger( 'mbla[] = ' . var_export( $mbla , true ) );
        logger( 'mbla_options[] = ' . var_export( $mbla_options , true ) );
    } else {

        /**
         * dummy function for logger() when debug mode is deactivated
         *
         * @param string $dummy1 void
         * @param int $dummy2 void
         * @return boolean false
         */
        function logger( $dummy1 = '', $dummy2 = 0 ) {
            return false;
        }
    }
}

function mbla_menu() {
    add_options_page( 'MBLA Options' , 'MBLA' , 9 , __FILE__ , 'mbla_manage_options' );
}

function mbla_message( $text = 'Done', $type = 'updated' ) {
    if ( empty( $type ) ) {
        $type = 'updated';
    }
    echo "<div id='message' class='{$type} fade'><p>{$text}</p></div>";
}

function mbla_manage_options() {
    global $wpdb, $mbla_options;

    if ( ! function_exists( 'curl_init' ) ) {
        echo "<div class='wrap'>";
        echo "<div style='color: red;'>This plugin only works with <a href='http://php.net/curl' target='_blank'>curl</a> activated in PHP</div>";
        echo "</div>";
        include ( ABSPATH . 'wp-admin/admin-footer.php' );
        die();
    }

    if ( isset( $_GET [ 'showcachecontent' ] ) ) {
        mbla_show_cache_content();
    } else {
        if ( isset( $_POST [ 'default' ] ) ) {
            // reset to default values
            mbla_message( "MBLA options restored to default values" );
            mbla_default_options( 'default' );
        } elseif ( isset( $_POST [ 'submit' ] ) ) {
            // update values
            mbla_message( "MBLA Options Updated" );
            mbla_default_options( 'update' , $_POST );
        } else {
            mbla_default_options();
        }
        $mbla_options = get_option( 'mbla_options' );
    }
}

function mbla_default_options( $action = '', $inarr = array() ) {
    global $wpdb, $mbla_options, $mbla;

    if ( $action == 'default' ) {
        $mbla_options = array(
            'cache_days' => 3,
            'debug_key' => 'seeecret',
            'gravatar_rating' => 'X',
            'wp-hook' => 'get_comment_text',
            'gravatar_default' => 'wavatar',
            'final_html_comment' => "<div style='float: left;'>
  <a href='{URL}'' title='{NAME} commenting on {TITLE}'>
    <img src='{AVATAR}' alt='' style='margin: 2px 2px 2px 0; height: 32px' />
  </a>
</div>",
            'final_html_comment_anon' => "<div style='float: left;'>
  <a href='http://mybloglog.com' title='Get an avatar at MyBlogLog'>
    <img src='{AVATAR}' alt='' style='margin: 2px 2px 2px 0; height: 32px' />
  </a>
</div>",
            'final_html_post' => "<div style='float: left;'>
  <img src='{AVATAR}' alt='' style='margin: 3px 3px 3px 0; height: 48px' />
</div>",
            'final_html_pingback' => "<div style='float: left;'>
  <a href='{URL}' title='{TITLE} got a pingback from {NAME}'>
    <img src='{AVATAR}' alt='' style='margin: 2px 2px 2px 0; height: 16px' />
  </a>
</div>",
            'anonymous_service' => implode( '' , array_keys( array_slice( $mbla [ 'services' ] , 0 , 1 ) ) ),
            'prival' => implode( ',' , array_keys( $mbla [ 'services' ] ) ) . ',custom,none' );
        update_option( 'mbla_options' , $mbla_options );
    } elseif ( $action == 'update' ) {
        $mbla_options = $_POST;

        // make sure we have our anonymous avatars
        // we need to do this here, so we can catch our md5 values of the anonymous avatars and save them at the same time
        if ( $mbla_options [ 'cache_location' ] ) {
            // first grap the admins avatar
            foreach ( array_reverse( explode( ',' , $mbla_options [ 'prival' ] ) ) as $tabid ) {
                $service = str_replace( '_anon' , '' , $tabid );
                if ( strpos( $tabid , '_anon' ) ) {
                    $md5 = fetchAvatar( $mbla [ 'anonymous_email' ] , $service, false );
                    $mbla_options [ 'md5_anon' ] [ str_replace( '_anon' , '' , $tabid ) ] = $md5 [ 'md5_file' ];
                }
            }
        }

        update_option( 'mbla_options' , $mbla_options );
    }

    logger( "\$mbla_options[]: " . var_export( $mbla_options , true ) );
    $info = "The following keywords will be replaced (case sensitive):<br/>";
    $arr_keywords = array(
        'URL' => 'the URL from the commenter',
        'NAME' => 'the name of the commenter',
        'MD5' => 'the MD5(email) of the commenter',
        'TITLE' => 'the title of the post',
        'AVATAR' => 'the avatar of the commenter',
        'GRAVATAR_RATING' => 'the max gravatar rating to use' );
    $info .= "<table border='0' cellpadding='1' cellspacing='0'>";
    foreach ( $arr_keywords as $_keyword => $_title ) {
        $info .= "<tr><td><tt>{{$_keyword}}</tt></td><td>{$_title}</td></tr>";
    }
    $info .= "<tr><td colspan='2'>Sample usage:<pre>" . "&lt;div style=&quot;float: left;&quot;&gt;
  &lt;a href=&quot;{URL}&quot; title=&quot;{NAME} commenting on {TITLE}&quot;&gt;
    &lt;img src=&quot;{AVATAR}&quot; alt=&quot;&quot; style=&quot;height: 32px&quot; /&gt;
  &lt;/a&gt;
&lt;/div&gt;" . "</pre></td></tr>";
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

    //    echo "<div style='float: right;'>" . checkVersion() . "</div>" ;
    echo "<h2>MBLA Options</h2>";

//    if ( is_writeable( $mbla [ 'filecache' ] ) ) {
        $tabcnt = 0;
        foreach ( array(
            'general' => 'General',
            'html' => 'HTML',
            'help' => 'Help' ) as $tabid => $tabtitle ) {
            echo "<input type='button' class='button' value='{$tabtitle} &raquo;' onclick=\"displayTab({$tabcnt});\" />";
            $tabcnt++;
        }
//    }
    echo "<br/>";

    echo "<form method='post' action='{$PHP_SELF}'>";

    echo "<fieldset>";

    echo "<table cellspacing='0' cellpadding='5' border='0' width='100%'>";

    echo "<tr>";
    echo "<td>";
    echo "Avatar cache location (<a href='http://code.google.com/p/mbla/wiki/Help#Avatar_cache_location' title='MBLA Help' target='_blank'>?</a>)<br/>";
    echo "<i style='color: #aaa;'>relative to document root</i>";
    echo "</td>";
    echo "<td colspan='2'>";
    echo "<tt style='font-size: 10px;'>{$_SERVER['DOCUMENT_ROOT']}</tt>";
    echo "<input type='text' name='cache_location' value='{$mbla_options['cache_location']}' style='width: 250px; font-family: monospace; font-size: 10px;' />";
    if ( $mbla_options [ 'cache_location' ] && ! is_writeable( "{$_SERVER['DOCUMENT_ROOT']}{$mbla_options['cache_location']}" ) ) {
        echo "<br/><i style='color: red;'>{$_SERVER['DOCUMENT_ROOT']}{$mbla_options['cache_location']} isn't writeable! (" . substr( sprintf( '%o' , @fileperms( $mbla [ 'filecache' ] ) ) , - 4 ) . ")</i>";
    }
    echo "</td>";
    echo "</tr>";

    echo "<tr>";
    echo "<td valign='top' >";
    echo "Custom avatar file (<a href='http://code.google.com/p/mbla/wiki/Help#Custom_avatar_file' title='MBLA Help' target='_blank'>?</a>)<br/>";
    echo "<i style='color: #aaa;'>relative to document root</i>";
    echo "</td>";
    echo "<td valign='top' colspan='2'>";
    echo "<tt style='font-size: 10px;'>{$_SERVER['DOCUMENT_ROOT']}</tt>";
    echo "<input type='text' name='custom' value='{$mbla_options['custom']}' style='width: 250px; font-family: monospace; font-size: 10px;' />";
    if ( $mbla_options [ 'custom' ] && ! file_exists( "{$_SERVER['DOCUMENT_ROOT']}{$mbla_options['custom']}" ) ) {
        echo "<br/><i style='color: red;'>The custom avatar file doesn't exist!</i>";
    }
    echo "</td>";
    echo "</tr>";

    echo "<tr>";
    echo "<td valign='top' style='width: 300px'>";
    echo "Avatar Fetch Cycle (<a href='http://code.google.com/p/mbla/wiki/Help#Custom_avatar_file' title='MBLA Help' target='_blank'>?</a>)<br/><i style='color: #aaa;'>Use &and; and &or; to change the order of the boxes. Place the 'None' box where you want the script to stop looking for other avatars.</i>";
    echo "</td>";
    echo "<td valign='top' >";
    $avail_services = array( );
    foreach ( $mbla [ 'services' ] as $tabid => $tabtitle ) {
        $avail_services [ $tabid ] = $tabtitle;
        $avail_services [ $tabid . '_anon' ] = $tabtitle . '\'s Anonymous Avatar';
    }
    if ( $mbla_options [ 'custom' ] ) {
        $avail_services [ 'custom' ] = 'Custom Avatar';
    }
    $avail_services [ 'none' ] = 'None';
    $choosen_services = explode( ',' , $mbla_options [ 'prival' ] );
    $choosenplus_services = array_merge( array_intersect( $choosen_services , array_keys( $avail_services ) ) , array_diff( array_keys( $avail_services ) , $choosen_services ) );
    $invalid = $tabcnt = 0;
    $first = true;
    echo "<table id='pri' cellpadding='3' cellspacing='1' style='width: 150px; border: 0'>";
    foreach ( $choosenplus_services as $tabid ) {
        if ( $avail_services [ $tabid ] ) {
            echo "<tr>";
            echo "<td id='pri{$tabcnt}' style='border: 1px solid #cccc99; background-color: #ffffcc; white-space: nowrap; text-align: center;'>";
            echo "&nbsp;";
            echo "{$avail_services[$tabid]}&nbsp;";
            if ($avail_services[$tabid] == 'Gravatar') {
                echo "(defaults to <select name='gravatar_default' style='vertical-align: middle;'>";
                foreach (array('none'      => 'None, continue with the Fetch Cycle',
                               'wavatar'   => 'Wavatar',
                               'monsterid' => 'MonsterID',
                               'identicon' => 'Identicon') AS $gkey => $gval) {
                    echo   "<option value='{$gkey}' " . ( $gkey == $mbla_options [ 'gravatar_default' ] ? "selected='selected'" : '' ) . ">{$gval}</option>";

                }
                echo "</select> if no Gravatar is found)";
            }
            echo "<input type='hidden' id='prival{$tabcnt}' value='{$tabid}' />";
            echo "</td>";
            if ( $first ) {
                echo "<td>&nbsp;</td>";
            } else {
                echo "<td style='cursor: pointer;' onclick=\"switchPri('pri{$tabcnt}', 'pri" . ( $tabcnt - 1 ) . "');\"> &and; </td>";
            }
            if ( $tabcnt < count( $choosenplus_services ) - 1 - $invalid ) {
                echo "<td style='cursor: pointer;' onclick=\"switchPri('pri{$tabcnt}', 'pri" . ( $tabcnt + 1 ) . "');\"> &or; </td>";
            } else {
                echo "<td>&nbsp;</td>";
            }
            echo "</tr>";
            $first = false;
            $tabcnt++;
        } else {
            $invalid++;
        }
    }
    echo "</table>";
    echo "<input type='hidden' size='80' name='prival' id='prival' value='" . implode( ',' , $choosenplus_services ) . "' />";
    echo "</td>";
    echo "<td>&nbsp;</td>";
    echo "</tr>";

    echo "<tr>";
    echo "<td>";
    echo "Check for updated avatar after <em>x</em> days (<a href='http://code.google.com/p/mbla/wiki/Help#Check_for_updated_avatar_after_x_days' title='MBLA Help' target='_blank'>?</a>)";
    echo "</td>";
    echo "<td>";
    echo "<select name='cache_days' style='width: 50px;'>";
    foreach ( array(
        1,
        2,
        3,
        4,
        5,
        6,
        7 ) as $i ) {
        echo "<option value='{$i}' " . ( $i == $mbla_options [ 'cache_days' ] ? "selected='selected'" : '' ) . ">{$i}</option>";
    }
    echo "</select>days";
    echo " (<a href='{$_SERVER['REQUEST_URI']}&amp;showcachecontent'>show current content of cache</a>)";
    echo "</td>";
    echo "<td>&nbsp;</td>";
    echo "</tr>";

    echo "<tr>";
    echo "<td>";
    echo "Gravatar rating (<a href='http://code.google.com/p/mbla/wiki/Help#Gravatar_rating' title='MBLA Help' target='_blank'>?</a>)";
    echo "</td>";
    echo "<td>";
    echo "<select name='gravatar_rating' style='width: 50px;'>";
    foreach ( array(
        'G',
        'PG',
        'R',
        'X' ) as $i ) {
        echo "<option value='{$i}' " . ( $i == $mbla_options [ 'gravatar_rating' ] ? "selected='selected'" : '' ) . ">{$i}</option>";
    }
    echo "</select>";
    echo "</td>";
    echo "<td>&nbsp;</td>";
    echo "</tr>";

    echo "<tr>";
    echo "<td valign='top'>";
    echo "Wordpress hook to use (<a href='http://code.google.com/p/mbla/wiki/Help#Wordpress_hook_to_use' title='MBLA Help' target='_blank'>?</a>)<br/><i style='color: #aaa;'><a href='http://codex.wordpress.org/Plugin_API/Filter_Reference' target='_blank'>List available hooks</a></i>";
    echo "</td>";
    echo "<td>";
    echo "<div style='background-color: #ffc; border: 1px dashed gray; margin-bottom: 3px; padding: 3px;'>";
    echo "<label for='wphook{$wphook}'>";
    echo "<input type='radio' style='background-color: #ffc; border: 0;' name='wphook' id='wphook{$wphook}' value='' ".('' == $mbla_options['wphook'] ? "checked='checked'" : '')."/>&nbsp;";
    echo "None - I'll add the following PHP code myself manually (<a href='http://code.google.com/p/mbla/wiki/Installation' title='MBLA Installation' target='_blank'>?</a>)<br/>";
    echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<tt>&lt;?php if(function_exists('MyAvatars<b>New</b>')) MyAvatars<b>New</b>(); ?&gt;</tt>";
    echo "</label>";
    echo "</div>";
    $wphook++;

    foreach(array('get_comment_text'        => array('Navigation' => 'http://www.gpsgazette.com/navigation-wp-theme/'),
                  'get_comment_author_link' => array('Connection' => 'http://themes.wordpress.net/columns/2-columns/149/connections-reloaded-15/',
                                                     'K2' => 'http://getk2.com/'),
                  'get_comment_date' => NULL,
                  'get_comment_time' => NULL) AS $hook => $themes) {
        echo "<div style='background-color: #ffc; border: 1px dashed gray; margin-bottom: 3px; padding: 3px;'>";
        echo "<label for='wphook{$wphook}'>";
        echo "<input type='radio' style='background-color: #ffc; border: 0;' name='wphook' id='wphook{$wphook}' value='{$hook}' ".($hook == $mbla_options['wphook'] ? "checked='checked'" : '')." />&nbsp;";
        echo "<tt>{$hook}()</tt>";
        if (is_array($themes)) {
            echo "<br/>";
            echo str_repeat('&nbsp;', 6);
            echo "Works nice with: ";
            $_arr = array();
            foreach ($themes AS $title => $url) {
                $_arr[] = "<a href='{$url}' target='_blank' title='{$title}'>{$title}</a>";
            }
            echo implode(', ', $_arr);
        }
        echo "</label>";
        echo "</div>";
        $wphook++;
    }

    echo "<div style='background-color: #ffc; border: 1px dashed gray; margin-bottom: 3px; padding: 3px;'>";
    echo "<label for='wphook{$wphook}'>";
    echo "<input type='radio' style='background-color: #ffc; border: 0;' name='wphook' id='wphook{$wphook}' value='other' ".('other' == $mbla_options['wphook'] ? "checked='checked'" : '')." />&nbsp;";
    echo "Other: </label><input type='text' name='wphook_other' value='{$mbla_options['wphook_other']}' onkeydown=\"document.getElementById('wphook{$wphook}').checked = true;\" style='width: 150px; font-family: monospace; font-size: 10px; text-align: right;' /><tt>()</tt>";
    echo "</div>";
    $wphook++;

    echo "</td>";
    echo "<td>&nbsp;</td>";
    echo "</tr>";

    echo "<tr>";
    echo "<td valign='top' style='width: 300px'>";
    echo "Secret debug key (<a href='http://code.google.com/p/mbla/wiki/Help#Debug_key' title='MBLA Help' target='_blank'>?</a>)<br/>";
    echo "<i style='color: #aaa;'>Used to enable debug mode by adding <tt>?debug=xxx</tt> or <tt>&amp;debug=xxx</tt> to the URL where <tt>xxx</tt> is the secret debug key</i>";
    echo "</td>";
    echo "<td colspan='2' valign='top'>";
    echo "<input type='text' name='debug_key' value='{$mbla_options['debug_key']}' style='width: 250px; font-family: monospace; font-size: 10px;' />";
    echo "</td>";
    echo "</tr>";

    echo "</table>";

    foreach ( $mbla [ 'services' ] as $tabid => $tabtitle ) {
        echo "<input type='hidden' name='md5_anon[{$tabid}]' value='" . $mbla_options [ 'md5_anon' ] [ $tabid ] . "' /><br/>";
    }
    echo "</fieldset>"; // General


    echo "<fieldset style='display: none;'>";
    echo "<table cellspacing='0' cellpadding='5' border='0' width='100%'>";
    echo "<tr>";
    echo "<td valign='bottom'>HTML code for (<a href='http://code.google.com/p/mbla/wiki/Help#HTML_code_for' title='MBLA Help' target='_blank'>?</a>)</td>";
    echo "<td><i>See available keywords at the bottom this page</i></td>";
    echo "</tr>";

    foreach ( array(
        'post' => 'Posts',
        'comment' => 'Comments',
        'comment_anon' => 'Comments (anonymous)',
        'pingback' => 'Pingbacks',
        'none' => 'No avatar available' ) as $typid => $typtitle ) {
        echo "<tr>";
        echo "<td valign='top' style='white-space: nowrap;'><b>" . ucfirst( $typtitle ) . "</b> (<a href='http://code.google.com/p/mbla/wiki/Help#".str_replace(array('(',')',' '), array('','','_'), $typtitle)."' title='MBLA Help' target='_blank'>?</a>)</td>";
        echo "<td width='100%'>";
        echo "<textarea name='final_html_{$typid}' rows='5' cols='80' style='font-family: monospace; font-size: 10px;'>";
        echo htmlentities( stripslashes( $mbla_options [ "final_html_{$typid}" ] ) );
        echo "</textarea>";
        echo "</td>";
        echo "</tr>";
    }
    echo "<tr>";
    echo "<td>&nbsp;</td>";
    echo "<td>{$info}</td>";
    echo "</tr>";

    echo "</table>";
    echo "</fieldset>"; // HTML

    echo "<fieldset style='display: none;'>";
    echo "<p>This <a href='http://wordpress.org'>Wordpress</a> plugin is hosted at <a href='http://code.google.com/p/mbla/'>Google Code</a>, which means that everything about <a href='http://code.google.com/p/mbla/downloads/list'>download</a>, <a href='http://code.google.com/p/mbla/wiki/Installation'>installation</a>, <a href='http://code.google.com/p/mbla/issues/list'>issues</a> and <a href='http://code.google.com/p/mbla/wiki/Help'>help</a> can be found there.</p>";
    echo "<p>This plugin makes use of:</p>";
    echo "<ul>";
    echo "<li><a href='http://mybloglog.com'>MyBlogLog avatars</a></li>";
    echo "<li><a href='http://gravatar.com'>Gravatar</a></li>";
    echo "<li><a href='http://googlepreview.com'>GooglePreview</a></li>";
    echo "</ul>";
    echo "<p>A special thank goes out to:</p>";
    echo "<ul>";
    echo "<li><a href='http://www.napolux.com/2006/12/14/myavatars-a-wordpress-plugin-for-mybloglog'>Napolux</a> for making me start on this plugin</li>";
    echo "<li><a href='http://itsvista.com/'>Joseph Fieber</a>, <a href='http://dennys.tiger2.net/'>Dennys Hsieh</a>, <a href='http://www.onbezet.nl/'>Evert Jan</a>, <a href='http://www.papygeek.com/'>PapyGeek</a> and <a href='http://afrison.com/'>Alex Frison</a> for helping with bug reports, feedbacks, new features and for having patience while I implemented it :)</li>";
    echo "</ul>";
    echo "</fieldset>"; // Help


    echo "<table cellspacing='0' cellpadding='5' border='0' width='100%'>";
    echo "<tr>";
    echo "<td align='left'>";
    echo "<input type='submit' name='submit'  style='background-color: #ccffcc;' value=' Update values ' />";
    echo "</td>";
    echo "<td align='right' colspan='2'>";
    echo "<input type='submit' name='default' style='background-color: #ffcccc;' value=' Reset to default values ' />";
    echo "</td>";
    echo "</tr>";
    echo "</table>";

    echo "</form>";

    echo "</div>";
    include ( ABSPATH . 'wp-admin/admin-footer.php' );
    die();
}

function mbla_show_cache_content() {
    global $mbla, $mbla_options;

    echo "<div class='wrap'>";

    echo "<form method='post' action='{$PHP_SELF}'>";

    echo "<table cellspacing='0' cellpadding='5' border='0' width='100%'>";
    echo "<tr>";
    echo "<td colspan='9'><h2>MBLA Cache Content</h2></td>";
    echo "</tr>";

    if ( ! file_exists( $mbla [ 'filecache' ] ) ) {
        echo "<tr>";
        echo "<td colspan='9'>Can't find avatar cache location at:</td>";
        echo "</tr>";
        echo "<tr>";
        echo "<td colspan='9'><tt>{$mbla['filecache']}</tt></td>";
        echo "</tr>";
        echo "<tr>";
        echo "<td colspan='9'>Please correct that problem under Options -> MBLA</td>";
        echo "</tr>";
    } else {
        parse_str( $_SERVER [ 'QUERY_STRING' ] , $url );
        if ( isset( $_GET [ 'delete' ] ) ) {
            switch ( $_GET [ 'delete' ] ) {
                case 'all' :
                    foreach ( glob( $mbla [ 'filecache' ] . "/*" ) as $filename ) {
                        @unlink( $filename );
                        unset( $url [ 'delete' ] );
                    }
                    break;

                case 'anon' :
                    foreach ( glob( $mbla [ 'filecache' ] . "/*" ) as $filename ) {
//                        foreach ( $mbla [ 'services' ] as $tabid => $tabtitle ) {
                            $tmp = array_merge($mbla_options['md5_anon'], array($mbla['anonymous_file_md5']));
                            if ( file_exists( $filename ) && in_array( md5_file( $filename ) , $tmp ) ) {
                                @unlink( $filename );
                            }
//                        }
                        unset( $url [ 'delete' ] );
                    }
                    break;

                default :
                    @unlink( $mbla [ 'filecache' ] . '/' . $_GET [ 'delete' ] );
                    break;
            }
            unset( $url [ 'delete' ] );
        }
        $cache = glob( $mbla [ 'filecache' ] . '/*' );
        $url = str_replace( '&' , '&amp;' , http_build_query( $url ) );
        echo "<tr>";
        echo "<td colspan='9'>Content of <tt><a href='{$PHP_SELF}?{$url}'>{$mbla['filecache']}</a></tt> (click to reload)</td>";
        echo "</tr>";
        if ( $cache ) {
            echo "<tr>";
            echo "<th>&nbsp;</th>";
            echo "<th style='background-color: #eee; text-align: center;' colspan='4'>Avatar</th>";
            echo "</tr>";
            echo "<tr>";
            echo "<th style='text-align: left;'>File name - <tt>md5(email)</tt></th>";
            echo "<th style='background-color: #eee; text-align: right;'>Size</th>";
            echo "<th style='background-color: #eee; text-align: center;'>Avatar</th>";
            echo "<th style='background-color: #eee; text-align: center;'>Date</th>";
            echo "<th style='background-color: #eee;'>&nbsp;</th>";
            echo "</tr>";
            $counter = $size = 0;
            $files = array( );
            foreach ( $cache as $fullfilename ) {
                $fsize = filesize( $fullfilename );
                if ( $fsize < 256) {
                    @unlink( $fullfilename );
                }
                $basefilename = basename( $fullfilename );
                echo "<tr>";
                echo "<td style='text-align: left;'>";
                echo "<tt>";
                if ( 'log' == substr( $basefilename , - 3 ) ) {
                    echo "<a href='{$mbla['urlcache']}/{$basefilename}'>{$basefilename}</a>";
                } else {
                    echo $basefilename;
                }
                echo "</tt>";
                echo "</td>";
                echo "<td style='background-color: #eee; text-align: right;'>{$fsize}</td>";
                echo "<td style='background-color: #eee; text-align: center;'>";
                if ( 'log' == substr( $basefilename , - 3 ) ) {
                    echo "<a href='{$mbla['urlcache']}/{$basefilename}'>log file</a>";
                } else {
                    echo "<img class='{$mbla_options['regular_class']}' style='{$mbla_options['regular_style']}; height: 32px; width: 32px;' src='{$mbla['urlcache']}/{$basefilename}' alt='{$basefilename}' />";
                }
                echo "</td>";
                echo "<td style='background-color: #eee; text-align: center; white-space: nowrap;'>" . date( 'Y-m-d H:i:s' , filemtime( $fullfilename ) ) . "</td>";
                echo "<td style='background-color: #eee; text-align: center;'><a href='{$PHP_SELF}?{$url}&amp;delete={$basefilename}'>Delete</a></td>";
                echo "</tr>";
                $counter++;
                $size += $fsize;
            }
            echo "<tr>";
            echo "<th style='text-align: left;'>Total cache size</th>";
            echo "<th style='background-color: #eee; text-align: right;'>{$size}</th>";
            echo "<th style='background-color: #eee;' colspan='3'>&nbsp;</th>";
            echo "</tr>";

            echo "<tr>";
            echo "<td>&nbsp;</td>";
            echo "<td colspan='4' style='text-align: center;'><a href='{$PHP_SELF}?{$url}&amp;delete=anon'>Delete all anonymous avatars</a></td>";
            echo "</tr>";
            echo "<tr>";
            echo "<td>&nbsp;</td>";
            echo "<td colspan='4' style='text-align: center;'><a href='{$PHP_SELF}?{$url}&amp;delete=all'>Delete all avatars</a></td>";
            echo "</tr>";
        } else {
            echo "<tr>";
            echo "<td colspan='5'>The avatar cache is empty. <a href='{$PHP_SELF}?{$url}'>Reload cache</a></td>";
            echo "</tr>";
        }
    }
    echo "</table>";

    echo "</form>";

    echo "</div>";
    include ( ABSPATH . 'wp-admin/admin-footer.php' );
    die();
}

function identifier2URL( $service, $identifier, $md5_name, $use_gravatar_default = true ) {
    logger( "identifier2URL({$service}, {$identifier}, {$md5_name}) {" , + 1 );
    global $mbla, $mbla_options;
    if ( strpos( $identifier , '@' ) ) {
        switch ( strtolower( $service ) ) {
            case 'gravatar' :
                if ($use_gravatar_default) {
                    $gravatar_default = ($mbla_options['gravatar_default'] != 'none' ? "&default={$mbla_options['gravatar_default']}" : '');
                } else {
                    $gravatar_default = '';
                }
                $ret = "http://www.gravatar.com/avatar/{$md5_name}?rating={$mbla_options['gravatar_rating']}{$gravatar_default}";
                break;

            case 'mybloglog' :
                $ret = "http://pub.mybloglog.com/coiserv.php?href=mailto:{$identifier}";
                break;

            case 'custom' :
                $ret = "{$mbla['urlcustom']}";
                break;

            case 'anon' :
                $ret = "{$mbla['urlcache']}/{$mbla['anonymous_email_md5']}";
                break;

            default :
                $ret = null;
                break;
        }
    } else {
        $tmp = parse_url( $identifier );
        $ret = "{$tmp['scheme']}://" . $tmp [ 'host' ] { 0 } . ".googlepreview.com/preview?s={$tmp['scheme']}://{$tmp['host']}";
    }
    logger( "\$ret: {$ret}" );
    logger( "}" , - 1 );
    return $ret;
}

function updateNeeded( $md5_name ) {
    logger( "updateNeeded({$md5_name}) {" , + 1 );
    global $mbla, $mbla_options;
    $update = false;
    $avatar = "{$mbla['filecache']}/{$md5_name}";
    logger( "checking avatar {$mbla['filecache']}/{$md5_name}" );
    if ( file_exists( $avatar ) ) {
        $cdate = date( 'Y-m-d H:i:s' , filemtime( $avatar ) );
        logger( "avatar stamp {$cdate}" );
        if ( date( 'Y-m-d H:i:s' , strtotime( "{$cdate} +{$mbla_options['cache_days']} days" ) ) < date( 'Y-m-d H:i:s' ) ) {
            $update = true;
            logger( "avatar is older than {$mbla_options['cache_days']} days so tag for update" );
        } else {
            $update = false;
            logger( "avatar is younger than {$mbla_options['cache_days']} days so no update necessary" );
        }
        if ( 0 == filesize( $avatar ) ) {
            $update = true;
            logger( "avatar is invalid tag for update" );
        }
    } else {
        $update = true;
        logger( "avatar doesn't exist so tag for update" );
    }
    logger( "updateNeeded({$md5_name}): " . (int)$update );
    logger( "}" , - 1 );
    return $update;
}

/**
 * This function fetches the avatar
 *
 * @param string $INemail the email address for which the avatar should be fetched
 * @param string $INservice the service to use for the fetch
 * @param boolean $use_gravatar_default this boolean determins if the "&default=xxx" should be added to the gravatar URL. This should always be set to false when fetching anon avatars
 */
function fetchAvatar( $INemail = null, $INservice = null, $use_gravatar_default = true ) {
    logger( "fetchAvatar({$INemail}, {$INservice}) {" , + 1 );
    global $mbla, $comment, $authordata, $mbla_options;

    $retval = array( );

    if ( $INemail ) {
        $identifier = $INemail;
    } elseif ( $comment->comment_author_email ) {
        $identifier = $comment->comment_author_email;
    } elseif ( $comment->comment_author_url ) {
        $tmp = explode( '/' , $comment->comment_author_url );
        $identifier = "{$tmp[0]}//{$tmp[2]}";
    } elseif ( $authordata->user_email ) {
        $identifier = $authordata->user_email;
    }
    $md5_name = md5( $identifier );
    logger( "\$identifier: {$identifier}" );
    logger( "\$md5_name: {$md5_name}" );

    if ( updateNeeded( $md5_name ) || $INservice ) {
        // an update is needed
        $services = ( $INservice ? array(
            $INservice ) : explode( ',' , $mbla_options [ 'prival' ] ) );
        logger( "\$services: " . var_export( $services , true ) );
        while ( $service = array_shift( $services ) ) {
            logger( "querying {$service}:" , + 1 );

            if ( 'none' == $service ) {
                // always bail out on the "none" service
                $ret = null;
            } else {
                // cycle through the services
                switch ( $service ) {
                    case 'gravatar' :
                        $remoteFileURL = identifier2URL( 'gravatar' , $identifier , $md5_name, $use_gravatar_default );
                        break;
                    case 'mybloglog' :
                        $remoteFileURL = identifier2URL( 'mybloglog' , $identifier , $md5_name );
                        break;
                    case 'custom' :
                        $remoteFileURL = identifier2URL( 'custom' , $identifier , $md5_name );
                        break;
                    case 'mybloglog_anon' :
                    case 'gravatar_anon' :
                        $remoteFileURL = identifier2URL( 'anon' , $identifier , $md5_name, false );
                        $INservice = 'localanon';
                        break;
                    case 'none' :
                    default :
                        return null;
                }
                logger( "\$remoteFileURL: {$remoteFileURL}" );
                $retval = downloadURL( $remoteFileURL , $md5_name , $INservice );

                if ( true == $retval [ 'anon' ] ) {
                    logger( "anonmous avatar fetched - move on to next service" );
                }

                if ( ! $retval [ 'anon' ] || $INservice ) {
                    $retval [ 'md5_name' ] = $md5_name;
                    logger( "valid avatar fetched - bailing out of loop" );
                    logger( " " , - 1 );

                    break;
                }
                logger( " " , - 1 );
            }
        }
    } else {
        // no update is needed so just return the md5_name
        logger( "no update was needed, just return the avatar" );
        $retval [ 'md5_name' ] = $md5_name;
    }
    logger( "\$retval: " . var_export( $retval , true ) );
    logger( "}" , - 1 );
    return $retval;
}

function mbla($str) {
    if (strpos($_SERVER['REQUEST_URI'], 'wp-admin') === false) {
        return MyAvatarsNew('','','rules',false).$str;
    } else {
        return $str;
    }
}

if ( ! function_exists( 'MyAvatars' ) ) {
    function MyAvatars( $INemail = '', $INservice = '', $update_method = 'rules', $echo = true ) {
        return false;
    }
    /**
     * Main function which echo or returns an avatar
     *
     * @param string $INemail Email of which to find an avatar from
     * @param unknown_type $INservice Force a specific service to be used
     * @param unknown_type $update_method Update by rules or always?
     * @param boolean $echo if true then the avatar will be echo'ed, if false then it will be return'ed
     * @return string String containing the avatar if $echo is false
     */
    function MyAvatarsNew( $INemail = '', $INservice = '', $update_method = 'rules', $echo = true ) {
        global $mbla, $comment, $authordata, $mbla_options;
        logger( "MyAvatars({$INemail}, {$INservice}, {$update_method}, {$echo}) {" , + 1 );

        //  $update_method= 'always';
        $retval = fetchAvatar();

        // $comment->comment_ID   === NULL         -> post
        // $comment->comment_type  == 'pingback'   -> pingback
        // $comment->comment_type  == ''           -> comment
        if ( NULL === $retval ) {
            $based_on = 'final_html_none';
        } elseif ( NULL === $comment->comment_ID ) {
            $based_on = 'final_html_post';
        } elseif ( 'pingback' === $comment->comment_type ) {
            $based_on = 'final_html_pingback';
        } elseif ( '' == $comment->comment_type || 'comment' == $comment->comment_type ) {
            $based_on = 'final_html_comment';
            if ( true == $retval [ 'anon' ] ) {
                $based_on .= '_anon';
            }
        }
        logger( "HTML field to use: {$based_on}" );
        logger( "final avatar to use: {$mbla['urlcache']}/{$retval['md5_name']}" );
        logger( "result will be: " . ( $echo ? 'echoed' : 'returned' ) );

        $avatar = str_replace( array(
            '{URL}',
            '{NAME}',
            '{MD5}',
            '{TITLE}',
            '{AVATAR}',
            '{MBLID}',
            '{GRAVATAR_RATING}' ) , array(
            $comment->comment_author_url,
            $comment->comment_author,
            md5( $comment->comment_author_email ),
            the_title( '' , '' , false ),
            "{$mbla['urlcache']}/{$retval['md5_name']}",
            $mbla_options [ 'mbl_id' ] ,
            $mbla_options['gravatar_rating']) ,
             stripslashes( $mbla_options [ $based_on ] ) );
        logger( "$avatar" );
        logger( "}" , - 1 );
        if ( '' == $INemail ) {
            if ( $echo ) {
                echo $avatar;
            } else {
                return $avatar;
            }
        }
    }
} else {
    echo "The function MyAvatars() is already declared, most likely in another plugin. You must find and deactivate that plugin in order to activate MBLA.";
}

/**
 * Check if an avatar is an anonymous avatar
 *
 * @param string $INmd5_file md5 value of an avatar file
 * @return boolean true if the avatar is anonymous
 */
function isAnon( $INmd5_file ) {
    logger( "isAnon({$INmd5_file}) {" , + 1 );
    global $mbla_options, $mbla;
    $anon = ( $INmd5_file == $mbla [ 'anonymous_file_md5' ] );
    $anon_file = in_array( $INmd5_file , (array)$mbla_options [ 'md5_anon' ] );
    logger( "is this our custom anonymous avatar?: " . (int)$anon );
    logger( "is this an anonymous avatar?: " . (int)$anon_file );
    logger( "}" , - 1 );
    return $anon_file || $anon;
}

/**
 * downloads a file (avatar) and saves it
 *
 * @param string $URL is the URL of the file to download
 * @param string $NEWNAME is the name the file will get when saved
 * @param boolean $forced tells if the file should be written no matter what
 * @return string md5 checksum of the written file
 */
function downloadURL( $URL, $NEWNAME, $forced ) {
    logger( "downloadURL({$URL}, {$NEWNAME}, {$forced}) {" , + 1 );
    global $mbla_options, $mbla;

    $retval = array( );

    $tmp = curlGet( $URL );
    $retval [ 'md5_file' ] = md5( $tmp );
    $retval [ 'md5_name' ] = $NEWNAME;
    logger( "md5() of downloaded file: {$retval['md5_file']}" );

    if ( $mbla [ 'anonymous_file_md5' ] == $retval [ 'md5_file' ] ) {
        logger( 'this is our custom avatar - use it!' );
        $forced = true;
    } else {
        $retval [ 'anon' ] = isAnon( $retval [ 'md5_file' ] );
        if ( $retval [ 'anon' ] ) {
            logger( "avatar {$NEWNAME} is tagged as anon" );
        }
    }

    if ( $forced ) {
        logger( "avatar is forced to be saved" );
    }
    if ( ! $retval [ 'anon' ] || $forced ) {
        $bytes_written = file_put_contents( "{$mbla['filecache']}/{$NEWNAME}" , $tmp );
        logger( "avatar {$NEWNAME} saved in {$bytes_written} bytes" );
    }

    logger( "}" , - 1 );
    return $retval;
}

if ( ! function_exists( 'curlGet' ) ) {
    function curlGet( $URL ) {
        logger( "curlGet({$URL}) {" , + 1 );
        $ch = curl_init();
        $timeout = 3;
        curl_setopt( $ch , CURLOPT_URL , $URL );
        curl_setopt( $ch , CURLOPT_RETURNTRANSFER , 1 );
        curl_setopt( $ch , CURLOPT_CONNECTTIMEOUT , $timeout );
        $tmp = curl_exec( $ch );
        curl_close( $ch );
        logger( "}" , - 1 );
        return $tmp;
    }
}
?>