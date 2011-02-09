<?php

require_once('ImageStyle/Image.class.php');

/**
 * Provider Class, base class for other Third-Party Provider classes
 *
 *   $image_style = $self->our_websys->get_image_style( $image_style_code, );
 *   $image_style = $self->our_websys->get_image_style( $image_style_code,
 *                                                      { not_installed_is_ok => 1 }
 *                                                      );
 *   Example Styles:
 *     array( 'size' => '152x159;mode=fill;avoid_upscale=true',
 *            'crop' => 'nocrop',
 *            'filter_script' => 'sharpen(2) | sharpen(2)',
 *            'frame' => 'RSC_ROOT/yellow_frame.png;offset_x=30;offset_y=15',
 *            'format' => 'png;png_quality=70%;png_alpha=true',
 *            )
 *     array( 'size' => '170x66;mode=fill',
 *            'crop' => 'nocrop',
 *            'filter_script' => 'sharpen(3)',
 *            'format' => 'jpeg;jpeg_quality=70%',
 *            )
 *     array( 'size' => '25x25;mode=fit;fill_extra=white',
 *            'crop' => 'nocrop',
 *            'filter_script' => 'sharpen(2) | sharpen(2)',
 *            'format' => 'jpeg;jpeg_quality=90%',
 *            )
 *     array( 'size' => '300x300;mode=fit',
 *            'crop' => 'nocrop',
 *            'filter_script' => 'sharpen(2)',
 *            'format' => 'jpeg;jpeg_quality=80%',
 *            )
 * 
 * 
 * @author Dave Buchanan <dave@elikirk.com>
 * @package TSANet
 * @version $Id: ImageStyle.class.php,v 1.5 2010/08/06 22:06:22 elikirkd Exp $
 */
class ImageStyle {
    public $style = null;

    public function __construct($style_code = '') {
        global $DAX_IMAGE_STYLES;

        ###  Fill or Get the Style
        if ( empty($style_code) ) {
            $this->style = array( 'size' => '300x300;mode=fit',
                                  'crop' => 'nocrop',
                                  'format' => 'jpeg;jpeg_quality=80%',
                                  'filter_script' => '',
                                  );
        }
        else if ( ! isset($DAX_IMAGE_STYLES[ $style_code ]) ) {
            return trigger_error("Invalid Style Code: ". $style_code, E_USER_ERROR);
        }
        else {
            $this->style = $DAX_IMAGE_STYLES[ $style_code ];
        }
    }


    #########################
    ###  Utility Functions

    private function irnd($num) { return sprintf("%.0f",$num); }

    private function parse_init_params($str) {
        $ary = preg_split('/\s*;\s*/',$str, 2);
        $init =                          $ary[0];
        $params_str = isset( $ary[1] ) ? $ary[1] : '';

        $params = array();
        if ( ! empty($params_str) ) {
            foreach ( preg_split('/\s*;\s*/',$params_str) as $param ) {
                if ( strpos($param,'=') === false ) list( $key, $value ) = array($param, '');
                else                            list( $key, $value ) = preg_split('/\s*=\s*/', $param, 2);
                $params[$key] = $value;
            }
        }
        

        return array( $init, $params );
    }

    public function read_color($color, $default = array(255,255,255)) {
        $fill_color = $default;

        ###  English color names
        $cnames = array( 'aqua'    => '00ffff',
                         'black'   => '000',
                         'blue'    => '00f',
                         'fuchsia' => 'f0f',
                         'gray'    => '808080',
                         'green'   => '008000',
                         'lime'    => '0f0',
                         'maroon'  => '800000',
                         'navy'    => '000080',
                         'olive'   => '808000',
                         'purple'  => '800080',
                         'red'     => 'f00',
                         'silver'  => 'C0C0C0',
                         'teal'    => '008080',
                         'white'   => 'fff',
                         'yellow'  => 'ff0',
                         );
        if ( isset($cnames[ $color ]) ) $color = $cnames[ $color ];
        
        if ( substr($color, 0, 1) == '#' ) $color = substr($color, 1);
        ###  Transpose FFF to array
        if ( preg_match('/^([0-9a-f])([0-9a-f])([0-9a-f])$/i',$color, $m) ) { 
            $fill_color = array(hexdec($m[1].$m[1]),hexdec($m[2].$m[2]),hexdec($m[3].$m[3]));
        }
        ###  Transpose FFFFFF to array
        else if ( preg_match('/^([0-9a-f][0-9a-f])([0-9a-f][0-9a-f])([0-9a-f][0-9a-f])$/i',$color, $m) ) { 
            $fill_color = array(hexdec($m[1]),hexdec($m[2]),hexdec($m[3]));
        }
        
        return $fill_color;
    }



    #########################
    ###  Image Manipulation Code (GD)

    public function apply_style($img, $img_style = null) {
        if ( empty($img_style) ) $img_style = $this->style;

        ###  Check the image
        if ( gettype($img) == 'string' ) {
            $filename = $img;
            $img = new ImageStyle__Image($filename);
            if ( $img->status != 'valid' )
                return trigger_error("Invalid Image Filename (or corrupt file or invalid file type): ". $filename, E_USER_ERROR);
        }
        else if ( ! is_object($img) && is_a($img,'ImageStyle__Image') ) {
            return trigger_error("PHP Warning: argument 1 of ImageStyle->apply_style must be of type ImageStyle__Image or a filename", E_USER_ERROR);
        }

        ###  Get some image properties
        $img_x = $img->width();
        $img_y = $img->height();

#        bug( $img->status, $img->width(), $img->height() );
  
        ###  Put together the script
        $script = array();
        if ( ! empty( $img_style['filter_script'] ) && preg_match('/\S/', $img_style['filter_script']) ) $script = preg_split('/\s*\|\s*/', $img_style['filter_script']) ;
        ###  Add 'size' and 'crop' to the beginning if not in the script
        if ( ! in_array('size', $script) ) array_unshift( $script, 'size' );
        if ( ! in_array('crop', $script) ) array_unshift( $script, 'crop' );

        $func_map = array( 'crop'    => 'ias_crop',
                           'size'    => 'ias_size',
                           'sharpen' => 'ias_sharpen',
                           'frame'   => 'ias_frame',
                           'rotate'  => 'ias_rotate',
                           );
  
        ###  Run the Script
        $i = 0;
        foreach ( $script as $item ) {

#            bug( $item );

            list( $func, $params ) = array( '','' );
            ###  Split $item
            if      ( $item == 'child' ) { next; }
            else if ( $item == 'size' ) { list( $func, $params ) = array( $item, $img_style['size'] ); }
            else if ( $item == 'crop' ) { list( $func, $params ) = array( $item, $img_style['crop'] ); }
            else if ( preg_match('/^\s*(\w+)\s*\(\s*(.*)\s*\)$/s', $item, $m) ) {
                list( $func, $params ) = array( $m[1], $m[2] );
            }
            
            ###  Run it
            if ( ! isset( $func_map[$func] ) ) return trigger_error("No such func: $func ($item)", E_USER_ERROR);
            $method = $func_map[$func];
#            bug($method, $params);
            $rv = $this->$method($img, $img_style, $params);
            if ( empty( $rv ) ) return trigger_error("Bad RV: $rv", E_USER_ERROR);

#            bug( $img->status, $img->width(), $img->height() );
    
#            ### hack for testing
#            if ( $i++ == 1 ) break;
        }

        return $img;
    }

    public function apply_format( $output_filename = null, $img, $img_style = null ) {
        if ( empty($img_style) ) $img_style = $this->style;
    
        ###  Check the image
        if ( gettype($img) == 'string' ) {
            $filename = $img;
            $img = new ImageStyle__Image($filename);
            if ( $img->status != 'valid' )
                return trigger_error("Invalid Image Filename (or corrupt file or invalid file type): ". $filename, E_USER_ERROR);
        }
        else if ( ! is_object($img) && is_a($img,'ImageStyle__Image') ) {
            return trigger_error("PHP Warning: argument 1 of ImageStyle->apply_style must be of type ImageStyle__Image or a filename", E_USER_ERROR);
        }

        ###  Read the params
        list( $format, $params ) = $this->parse_init_params($img_style['format']);
        ###  Convert and save the image
        if ( $format == 'jpeg' ) {
            $pct = 100;  if ( ! empty($params['jpeg_quality']) && preg_match('/^(\d+(?:\.\d+)?)\%$/s', $params['jpeg_quality'], $m) ) $pct = floor($m[1]);

            return imagejpeg($img->img_rsc, $output_filename, $pct); 
        }
        else if ( $format == 'gif' ) { return imagegif($img->img_rsc, $output_filename); }
        else if ( $format == 'png' ) {
            ###  Convert 0 - 100% (quality) to 9 to 0 (compression factor)
            $pct = 3;    if ( ! empty($params['png_quality']) && preg_match('/^(\d+(?:\.\d+)?)\%$/s', $params['png_quality'], $m) ) $pct = floor((1 - ($m[1] / 100)) * 9);

            ###  If transparency, set flags...
            if ( ! empty( $params['png_alpha']) ) {
                imagealphablending($img->img_rsc, true);
                imagesavealpha($img->img_rsc, true);
            }
            
            return imagepng($img->img_rsc, $output_filename, $pct); 
        }

        ###  No types matched
        return trigger_error("Invalid Image Format: ". $format, E_USER_ERROR);
    }


    #########################
    ###  Image Filter Functions

    public function ias_crop( $img, $img_style, $params_str ) {
        if ( ! is_object($img) && is_a($img,'ImageStyle__Image') )
            return trigger_error("PHP Warning: argument 1 of ImageStyle->apply_style must be of type ImageStyle__Image", E_USER_ERROR);

        ###  Get some image properties
        $img_x = $img->width();
        $img_y = $img->height();
   
        ###  Do the crop
        list( $c_style, $params ) = $this->parse_init_params($params_str);
        if ( $c_style == 'equal') {
            ###  Crop Equally by percentage
            if ( ! empty($params['all']) && preg_match('/^(\d+(?:\.\d+)?)\%$/s', $params['all'], $m) ) {
                $pct = $m[1] / 200;
                $rv = imagecreatefromgd2part( $img->tmp_filename(),
                                              floor($img_x*$pct),           # start location, top
                                              floor($img_y*$pct),           # start location, left
                                              $this->irnd($img_x*(1-($pct*2))),  # width
                                              $this->irnd($img_y*(1-($pct*2)))   # height
                                              );
                $img->replace_content($rv);
            }
        }
  
        return true;
    }


    public function ias_size( $img, $img_style, $params_str ) {
        if ( ! is_object($img) && is_a($img,'ImageStyle__Image') )
            return trigger_error("PHP Warning: argument 1 of ImageStyle->apply_style must be of type ImageStyle__Image", E_USER_ERROR);

        ###  Get some image properties
        $img_x = $img->width();
        $img_y = $img->height();

        ###  Do the resize
        list( $size, $params ) = $this->parse_init_params($params_str);
        if ( preg_match('/^(\d+)x(\d+)$/s', $size, $m) ) {
            list($def_x, $def_y) = array($m[1], $m[2]);
            list($new_x, $new_y ) = array($def_x, $def_y);
            $mode = ! empty($params['mode']) ? $params['mode'] : 'fit';

            ###  Check Avoid Upscale
            if ( ! empty($params['avoid_upscale'])
                 && $params['avoid_upscale'] != 'false'
                 && (# If MODE = fill then if either param is larger than source...
                     ( $mode == 'fill'
                       && ( $def_x > $img_x
                            || $def_y > $img_y
                            )
                       )
                     # If MODE = fit then BOTH params must be larger than source
                     || ( $mode == 'fit'
                          && $def_x > $img_x
                          && $def_y > $img_y
                          )
                     )
                 ) {
                ###  We are too small, so, skip out...

                ###  UNLESS fill_extra is set...
                if ( isset( $params['fill_extra'] ) ) {
                    list ($grab_offset_x,$grab_offset_y,$grab_x,$grab_y) = array(0,0,$img_x,$img_y);
                    $place_offset_x = floor(($def_x - $img_x) / 2);
                    $place_offset_y = floor(($def_y - $img_y) / 2);
                    $fill_color = $this->read_color( $params['fill_extra'], array(255,255,255) );

                    ###  For when it's too wide or too tall
                    if ( $place_offset_x < 0 ) {
                        $grab_offset_x = $place_offset_x * -1;
                        $grab_x        = $def_x;
                        $place_offset_x = 0;
                    }
                    else if ( $place_offset_y < 0 ) {
                        $grab_offset_y = $place_offset_y * -1;
                        $grab_y        = $def_y;
                        $place_offset_y = 0;
                    }

                    ###  Just create a new img and plunk this image in the center
                    $new_rsc = imagecreatetruecolor( $def_x, $def_y );
                    $bg = imagecolorallocate($new_rsc, $fill_color[0], $fill_color[1], $fill_color[2]);
                    imagefill($new_rsc, 0, 0, $bg);
                    imagecopy($new_rsc, $img->img_rsc, $place_offset_x, $place_offset_y, $grab_offset_x, $grab_offset_y, $grab_x, $grab_y);
                    $img->replace_content($new_rsc);
                }

                return true;
            }

            ###  Fill: fill the dimensions completely with image and crop off the rest
            if ( $mode == 'fill' ) {
                ###  Fit Width and crop off the top and bottom ( target width(x) ratio is wider than source )
                if ( ($def_x/$def_y) >= ($img_x/$img_y) ) {
                    $crop_height = floor( $img_x * ($def_y/$def_x) );

                    ###  Use yadj if set
                    $top_gap;
                    if      ( isset($params['yadj']) && preg_match('/^(\-?\d+(?:\.\d+)?)\%$/s', $params['yadj'], $m) 
                              ) { $top_gap = floor( (($img_y-$crop_height)/2) + ( $m[1]/100 )* (($img_y-$crop_height)/2) ); }
                    else if ( isset($params['yadj']) && preg_match('/^(\-?\d+)px$/s', $params['yadj'], $m) 
                              ) { $top_gap = floor( (($img_y-$crop_height)/2) + $m[1] ); }
                    else {      $top_gap = floor( (($img_y-$crop_height)/2) ); }

                    $rv = imagecreatefromgd2part( $img->tmp_filename(),
                                                  0,           # start location, top
                                                  $top_gap,    # start location, left
                                                  $img_x,        # width
                                                  $crop_height # height
                                                  );
                    $img->replace_content($rv);
                }
                ###  Fit Height and chop off the sides
                else {
                    $crop_width = floor( $img_y * ($def_x/$def_y) );

                    ###  Use xadj if set
                    $left_gap;
                    if    ( ! empty($params['xadj']) && preg_match('/^(\-?\d+(?:\.\d+)?)\%$/s', $params['xadj'], $m) 
                            ) { $left_gap = floor( (($img_x-$crop_width)/2) + ( $m[1]/100 )* (($img_x-$crop_width)/2) ); }
                    else if ( ! empty($params['xadj']) && preg_match('/^(\-?\d+)px$/s', $params['xadj'], $m) 
                              ) { $left_gap = floor( (($img_x-$crop_width)/2) + $m[1] ); }
                    else {      $left_gap = floor( (($img_x-$crop_width)/2) ); }

                    $rv = imagecreatefromgd2part( $img->tmp_filename(),
                                                  $left_gap,    # start location, top
                                                  0,            # start location, left
                                                  $crop_width,  # width
                                                  $img_y          # height
                                                  );
                    $img->replace_content($rv);
                }

                ###  Do the resize
                $rv = $img->resize( $new_x, $new_y );
            }

            ###  Fit: scale the image to show the whole thing
            if ( $mode == 'fit' ) {
                ###  Empty space on top and bottom ( source width(x) ratio is wider than target )
                if ( ($img_x/$img_y) >= ($def_x/$def_y) ) {
                    $new_y = floor( $def_x * ($img_y/$img_x) );
                    if ( $new_y == 0 ) $new_y++;
                }
                ###  Empty space on the sides
                else {
                    $new_x = floor( $def_y * ($img_x/$img_y) );
                    if ( $new_x == 0 ) $new_x++;
                }

                ###  If they provided a fill_extra color, do it
                if ( isset( $params['fill_extra'] ) ) {
                    $fill_top    = floor(($def_x - $new_x) / 2);
                    $fill_left   = floor(($def_y - $new_y) / 2);
                    $fill_bottom = $def_y - $new_y - $fill_top;
                    $fill_right  = $def_x - $new_x - $fill_left;
                    
                    $fill_color = $this->read_color( $params['fill_extra'], array(255,255,255) );
                    

                    ###  Do the resize
                    $rv = $img->resize( $new_x, $new_y, $fill_color, $fill_top, $fill_right, $fill_bottom, $fill_left );
                }
                ###  Otherwise, just resize and get the dimentions in the box...
                $rv = $img->resize( $new_x, $new_y );
            }
        }

        return true;
    }

    public function ias_sharpen( $img, $img_style, $params_str ) {
        if ( ! is_object($img) && is_a($img,'ImageStyle__Image') )
            return trigger_error("PHP Warning: argument 1 of ImageStyle->apply_style must be of type ImageStyle__Image", E_USER_ERROR);

        ###  Do the sharpen
        list( $radius, $params ) = $this->parse_init_params($params_str);
        $img->sharpen( ( ! empty($params['amount'])    ? $params['amount']    : 50 ),
                       $radius,
                       ( ! empty($params['threshold']) ? $params['threshold'] : 0 )
                       );
        
        return true;
    }

    public function ias_frame( $img, $img_style, $params_str ) {
        global $DAX_IMAGE_STYLE_RSC_ROOT;

        if ( ! is_object($img) && is_a($img,'ImageStyle__Image') )
            return trigger_error("PHP Warning: argument 1 of ImageStyle->apply_style must be of type ImageStyle__Image", E_USER_ERROR);

        ###  Get some image properties
        $img_x = $img->width();
        $img_y = $img->height();

        list( $frame_img_filename, $params ) = $this->parse_init_params($params_str);
        ###  Swap RSC_ROOT
        $frame_img_filename = preg_replace('/RSC_ROOT/',$DAX_IMAGE_STYLE_RSC_ROOT,$frame_img_filename);

        ###  Read in the Frame Image
        if ( preg_match('/(\.gif)$/si', $frame_img_filename) ) { 
            $frame_img = imagecreatefromgif($frame_img_filename);
            $this->orig_type = 'gif';
        }
        else if ( preg_match('/(\.jpe?g)$/si', $frame_img_filename) ) { 
            $frame_img = imagecreatefromjpeg($frame_img_filename);
            $this->orig_type = 'jpg';
        }
        else if ( preg_match('/(\.png)$/si', $frame_img_filename) ) { 
            $frame_img = imagecreatefrompng($frame_img_filename);
            $this->orig_type = 'png';
        }
        if ( ! $frame_img ) return trigger_error("Cound not read frame image: ". $frame_img_filename, E_USER_ERROR);
        list( $fx, $fy) = array( imagesx($frame_img), imagesy($frame_img) );


        ######  Center the image in the frame
        if ( ! isset( $params['border_left']   ) ) $params['border_left']   = 0;
        if ( ! isset( $params['border_top']    ) ) $params['border_top']    = 0;
        if ( ! isset( $params['border_right']  ) ) $params['border_right']  = 0;
        if ( ! isset( $params['border_bottom'] ) ) $params['border_bottom'] = 0;
        list( $fx_i, $fy_i) = array( ($fx - $params['border_left'] - $params['border_right']), ($fy - $params['border_top'] - $params['border_bottom'] ) );
        $offset_x = $params['border_left'] + floor( ( $fx_i - $img_x ) / 2);
        $offset_y = $params['border_top'] + floor( ( $fy_i - $img_y ) / 2);


        ###  Fill: fill the dimensions completely with image and crop off the rest
        if ( ! empty($params['frame_order']) && $params['frame_order'] == 'bottom' ) {
            ###  Just superimpose the image over the frame
            imagecopy($frame_img, $img->img_rsc, $offset_x, $offset_y, 0, 0, $img_x, $img_y);
            $img->replace_content($frame_img);
        }
        else {
            ###  Make new image, and add in order...
            $new_rsc = imagecreatetruecolor( $fx, $fy );
            $bg = imagecolorallocatealpha($new_rsc, 255,255,255,127);
            imagefill($new_rsc, 0, 0, $bg);
            imagecopy($new_rsc, $img->img_rsc,   $offset_x, $offset_y, 0, 0, $img_x, $img_y);
            imagecopy($new_rsc, $frame_img,      0,         0,         0, 0, $fx, $fy);
            $img->replace_content($new_rsc);
        }
        
        return true;
    }

    public function ias_rotate( $img, $img_style, $params_str ) {
        global $DAX_IMAGE_STYLE_RSC_ROOT;

        if ( ! is_object($img) && is_a($img,'ImageStyle__Image') )
            return trigger_error("PHP Warning: argument 1 of ImageStyle->apply_style must be of type ImageStyle__Image", E_USER_ERROR);

        ###  Scrub the percent
        list( $percent, $params ) = $this->parse_init_params($params_str);
        $percent = preg_replace('/[^\-\d\.]/','',$percent);
        if ( ! is_numeric($percent) ) return true;
        $percent = -1 * ( ( ($percent + (360 * 13) # <-- I guess PHP modulus of negative numbers is busted...  Add muitiples of 360, which shouldn't matter
                             ) * 100) % 36000) / 100;

        ###  Get the fill color
        $fill_color = ! empty($params['fill_extra']) ? $this->read_color( $params['fill_extra'], -1 ) : -1;
        if ( is_array($fill_color) ) $fill_color = imagecolorallocate($img->img_rsc, $fill_color[0], $fill_color[1], $fill_color[2]);
        else { $fill_color = imagecolorallocatealpha($img->img_rsc, 255,255,255,127); }

        ###  To get smooth edges, blow it up first ...
        $new_rsc = imagecreatetruecolor( $img->width() * 2, $img->height() * 2 );
        imagecopyresampled($new_rsc, $img->img_rsc, 0, 0, 0, 0, $img->width() * 2, $img->height() * 2, $img->width(), $img->height());

        ###  Rotate the image
        $rot_rsc = imagerotate($new_rsc, $percent, $fill_color);

        ###  ... then shrink it back down
        list($new_w, $new_h) = array(imagesx($rot_rsc), imagesy($rot_rsc));
        $new_rsc = imagecreatetruecolor( floor($new_w / 2), floor($new_h / 2) );
        ###  Need to fill the BG with something it it ends up black... (BUG in GD?!)
        $bg = imagecolorallocatealpha($img->img_rsc, 255,255,255,127);
        imagefill($new_rsc, 0, 0, $bg);
        ###  Now shrink...
        imagecopyresampled($new_rsc, $rot_rsc, 0, 0, 0, 0, floor($new_w / 2), floor($new_h / 2), $new_w, $new_h);
        $img->replace_content($new_rsc);
        
        return true;
    }
}


#########################
###  Image Upload Helper Functions

function imagestyle_upload_image($info) {
    global $DAX_IMAGE_STYLE_ORIG_BASE, $DAX_IMAGE_STYLE_DEFAULT_CODE, $DAX_IMAGE_STYLE_CACHE_BASE, $DAX_IMAGE_STYLE_URL_ROOT,
        $DAX_IMAGE_STYLES, $DAX_IMAGE_STYLE_ORIG_MAX_SIZE, $DAX_IMAGE_STYLE_ORIG_MAX_DIM, $DAX_IMAGE_STYLE_ORIG_MAX_FORCE_ISTYLE_CODE;

    ###  Set a HIGH-ish Memory limit just for this operation
    ini_set('memory_limit','128M');
    
    ###  Make sure it's an image
    if ( ! preg_match('/^(.+?)\.(gif|jpe?g|png)$/si', $info['name'], $m) ) {
        return( array( 'file' => $info['name'],
                       'name' => $info['name'],
                       'width' => 0,
                       'height' => 0,
                       'type' => '',
                       'error' => 'Un-recognized file type: '. $info['name'],
                       )
                );
    }
    $name = $m[1];
    $ext = $m[2];

    ###  Make sure the destination filename is available
    $dest_filename = preg_replace('/[^a-z0-9\.\-]+/i','_',$info['name']);
    $name = preg_replace('/[^a-z0-9\.\-]+/i','_',$name);
    if ( file_exists($DAX_IMAGE_STYLE_ORIG_BASE .'/'. $dest_filename) ) {
        $i = 1;
        while( file_exists($DAX_IMAGE_STYLE_ORIG_BASE .'/'. $name .'-'. $i .'.'. $ext) ) { $i++; }
        $dest_filename = $name .'-'. $i .'.'. $ext;
    }

    ###  Copy it into place
    if ( ! is_dir($DAX_IMAGE_STYLE_ORIG_BASE) ) mkdir( $DAX_IMAGE_STYLE_ORIG_BASE, 0777, true);
    if ( ! move_uploaded_file($info['tmp_name'], $DAX_IMAGE_STYLE_ORIG_BASE .'/'. $dest_filename) ) {
        return( array( 'file' => $info['name'],
                       'name' => $info['name'],
                       'width' => 0,
                       'height' => 0,
                       'type' => $ext,
                       'error' => 'Error during upload: ('. $DAX_IMAGE_STYLE_ORIG_BASE .'/'. $dest_filename .')',
                       )
                );
    }
    ###  If successful upload...

    ###  If the file is larger than $DAX_IMAGE_STYLE_ORIG_MAX_SIZE, shrink the original
    $shrink_orig = false;
    if ( ! empty( $DAX_IMAGE_STYLE_ORIG_MAX_SIZE )
         && ! empty( $DAX_IMAGE_STYLE_ORIG_MAX_FORCE_ISTYLE_CODE )
         && isset ( $DAX_IMAGE_STYLES[ $DAX_IMAGE_STYLE_ORIG_MAX_FORCE_ISTYLE_CODE ] )
         && ( $info['size'] / 1000000 ) > $DAX_IMAGE_STYLE_ORIG_MAX_SIZE
         ) {
        $shrink_orig = true;
    }

    ###  Get the ImageStyle for the next check...
    if ( ! $shrink_orig ) $img = new ImageStyle__Image($DAX_IMAGE_STYLE_ORIG_BASE .'/'. $dest_filename);

    ###  If the pic's dimentions are larger than $DAX_IMAGE_STYLE_ORIG_MAX_DIM, shrink the original
    if ( $shrink_orig
         || ( ! empty( $DAX_IMAGE_STYLE_ORIG_MAX_DIM )
              && ! empty( $DAX_IMAGE_STYLE_ORIG_MAX_FORCE_ISTYLE_CODE )
              && isset ( $DAX_IMAGE_STYLES[ $DAX_IMAGE_STYLE_ORIG_MAX_FORCE_ISTYLE_CODE ] )
              ###  The pic must fit inside a square ____x____
              && ( $img   ->width () > $DAX_IMAGE_STYLE_ORIG_MAX_DIM 
                   || $img->height() > $DAX_IMAGE_STYLE_ORIG_MAX_DIM 
                   )
              )
         ) {
        ###  Set a HIGH Memory limit just for this operation
        ini_set('memory_limit','256M');

        ###  Re-Save the file a little smaller
        $img = new ImageStyle__Image($DAX_IMAGE_STYLE_ORIG_BASE .'/'. $dest_filename);
        $istyle = new ImageStyle($DAX_IMAGE_STYLE_ORIG_MAX_FORCE_ISTYLE_CODE);
        $img = $istyle->apply_style($img);
        $istyle->apply_format($DAX_IMAGE_STYLE_ORIG_BASE .'/'. $dest_filename, $img);

        ###  NOW, get the new image as if it WAS the original
        $img = new ImageStyle__Image($DAX_IMAGE_STYLE_ORIG_BASE .'/'. $dest_filename);
    }

    ###  Get the Style
    $istyle_code = ( isset($_REQUEST['istyle_code']) ? $_REQUEST['istyle_code'] : $DAX_IMAGE_STYLE_DEFAULT_CODE );

    ###  If we are immediately forwarding them on to an istyle...
    ###    we need to Run the style, and see what the final resolution is
    if ( ! empty($istyle_code) ) {
        $istyle = new ImageStyle($istyle_code);

        ###  Create the dir if needed
        $dest_dir = $DAX_IMAGE_STYLE_CACHE_BASE .'/'. $istyle_code;
        if ( ! is_dir($dest_dir) ) mkdir($dest_dir,0775,true);
        if ( ! is_dir($dest_dir) ) return trigger_error("Could not create cache directory: ". $dest_dir, E_USER_ERROR);
        
        ###  Run Image Style
        $img = $istyle->apply_style($img);
        ###  Save the file...
        $istyle->apply_format($dest_dir .'/'. $dest_filename, $img);
    }

    ###  Success
    return( array( 'file' => $DAX_IMAGE_STYLE_URL_ROOT .'/'. ( empty($istyle_code) ? 'orig' : $istyle_code) .'/'. $dest_filename,
                   'simple_file_name' => $dest_filename,
                   'name' => $info['name'],
                   'width' => $img->width(),
                   'height' => $img->height(),
                   'type' => $ext,
                   'error' => null,
                   )
            );
}