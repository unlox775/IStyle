<?php
ini_set('display_errors',true);

###  Load configurations
require_once('./debug.inc.php');
require_once('./istyle.inc.php');

require_once('./ImageStyle.class.php');
require_once('./ImageStyle/Image.class.php');

# ###  Set the session name up here
# session_name($DAX_SESSION_NAME);
# session_set_cookie_params(0, '/',$COOKIE_DOMAIN); # default to a session cookie
# session_start();
# 
# ###  Must be a CMS dude...
# if ( ! isset( $_SESSION['edit_cms_mode'] ) ) {
#     header("HTTP/1.0 404 Not Found");
#     echo "<html><head>\n<title>404 NOT Found</title>\n</head><body>\n<h1>Not Found</h1>\n<p>The requested URL ". $_SERVER['REQUEST_URI'] ." was not found on this server.</p>\n</body></html>";
#     exit;
# }

###  Upload the files (ALTHOUGH THERE IS ONLY EVER ONE!!!)
foreach ( $_FILES as $key => $info ) {
    if ( empty( $info['name'] ) ) continue;
    $ret = imagestyle_upload_image($info);

    ###  Let them multi-upload if they are re-directing
    if ( ! empty( $_REQUEST['redirect'] ) ) {
        if ( ! empty($ret['error']) )
            return trigger_error("Error uploading file: ". $ret['error'], E_USER_ERROR);

        header("Location: ". $_REQUEST['redirect']);
    }
    else { 
        echo( "file=".      $ret['file']
              . ",name=".   $ret['name']
              . ",width=".  $ret['width']
              . ",height=". $ret['height']
              . ",type=".   $ret['type']
              . ",error=".  $ret['error']
              );
        exit;
    }
}

###  Let them multi-upload if they are re-directing
if ( ! empty( $_REQUEST['redirect'] ) )
    exit;

echo "file=,name=,width=0,height=0,type=,error=Empty Post";
exit;
