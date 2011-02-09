<?php

###  Load configurations
require_once('./debug.inc.php');
require_once('./istyle.inc.php');

require_once('./ImageStyle.class.php');

#########################
###  What do they want?!

###  If REQUEST_URI mode, pretend it is PATH_INFO mode...
if (  empty( $_SERVER['PATH_INFO'] ) ) {
    $mypath = preg_replace('@/index.php$@','',$_SERVER['SCRIPT_NAME'] );
    $_SERVER['PATH_INFO'] = preg_replace( '@^'. $mypath .'@', '', $_SERVER['REQUEST_URI']);
}

###  ###  DEBUG!!!
###  bug( array( 'dest_filename' => $dest_filename,
###                  'istyle_code' => $istyle_code,
###                  'file' => $file,
###                  'ext' => $ext,
###                  'SCRIPT_NAME'     => $_SERVER['SCRIPT_NAME']    ,
###                  'SCRIPT_FILENAME' => $_SERVER['SCRIPT_FILENAME'],
###                  'REQUEST_URI'     => $_SERVER['REQUEST_URI']    ,
###                  'PHP_SELF'        => $_SERVER['PHP_SELF']       ,
###                  'PATH_INFO'       => $_SERVER['PATH_INFO']      
###                  ) );
###  exit;



$dest_filename = $DAX_IMAGE_STYLE_CACHE_BASE . $_SERVER['PATH_INFO'];
if ( preg_match('@^/(.+?)/([^/]+?\.(jpe?g|gif|png))$@i', $_SERVER['PATH_INFO'], $m) ) {
    $istyle_code = $m[1];
    $file = $m[2];
    $ext = strtolower( $m[3] );

    ###  If they want orig, then switch...
    if ( $istyle_code == 'orig' ) $dest_filename = $DAX_IMAGE_STYLE_ORIG_BASE .'/'. $file;
    ###  Skip for invalid istyles...
    else if ( ! isset($DAX_IMAGE_STYLES[ $istyle_code ]) ) {
        header("HTTP/1.0 404 Not Found");
        echo "<html><head>\n<title>404 Not Found</title>\n</head><body>\n<h1>Not Found</h1>\n<p>The requested URL ". $_SERVER['REQUEST_URI'] ." (Style: ". $istyle_code .") was not found on this server.</p>\n</body></html>";
        exit;
    }
    
    ###  The source, if we are making a new one...
    $orig_filename = $DAX_IMAGE_STYLE_ORIG_BASE .'/'. $file;
    ###  Destinition Dir
    $dest_dir = $DAX_IMAGE_STYLE_CACHE_BASE .'/'. $istyle_code;
}
###  Bad path...
else {
    header("HTTP/1.0 404 Not Found");
    echo "<html><head>\n<title>404 Not Found</title>\n</head><body>\n<h1>Not Found</h1>\n<p>The requested URL ". $_SERVER['REQUEST_URI'] ." was not found on this server.</p>\n</body></html>";
    exit;
}




#########################
###  Make and Cache, if not ready...

if ( $istyle_code != 'orig'
     && ( on_alpha() #  Always regen if on "ALPHA"
          || ! file_exists($dest_filename)
          )
     ) {
    $istyle = new ImageStyle($istyle_code);

    ###  Prep dir...
    if ( ! is_dir($dest_dir) ) mkdir($dest_dir,0775,true);
    if ( ! is_dir($dest_dir) ) return trigger_error("Could not create cache directory: ". $dest_dir, E_USER_ERROR);


    ###  Run Image Style
    $img = $istyle->apply_style($orig_filename);
    ###  Save the file...
    $istyle->apply_format($dest_filename, $img);
}


#########################
###  Serve the file

$ext_mime = array( 'jpg' => 'image/jpeg',
                   'jpeg' => 'image/jpeg',
                   'gif' => 'image/gif',
                   'png' => 'image/png',
                   );
if ( isset( $ext_mime[ $ext ] ) ) $mime = $ext_mime[ $ext ];
###  This should NEVER happen...
else {
    header("HTTP/1.0 404 Not Found");
    echo "<html><head>\n<title>404 Not Found</title>\n</head><body>\n<h1>Not Found</h1>\n<p>The requested URL ". $_SERVER['REQUEST_URI'] ." was not found on this server.</p>\n</body></html>";
    exit;
}
                   
###  Send the right headers
header("Content-Type: ". $mime);
header("Content-Length: " . filesize($dest_filename));

$fh = fopen($dest_filename,'r');
fpassthru($fh);