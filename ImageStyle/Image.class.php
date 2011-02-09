<?php

/**
 * Provider Class, base class for other Third-Party Provider classes
 *
 *   my $image_style = $self->our_websys->get_image_style( $image_style_id, );
 *   my $image_style = $self->our_websys->get_image_style( $image_style_id,
 *                                                         { not_installed_is_ok => 1 }
 *                                                         );
 * 
 *   ###  Install and Initialize
 *   $image_style->install( { skin_style_id => undef, # means use the base SkinStyle
 *                            layout_url => "odyc://my.reseller/layouts/ecom/home/basic_featured_products_v1.0",
 *                            base_type => 'home',
 *                            },
 *                          );
 * 
 *   ###  Sub Objects
 *   my $layout_obj = $image_style->layout;
 *   my $skin_style_obj = $image_style->skin_style;
 * 
 * @author Dave Buchanan <dave@elikirk.com>
 * @package TSANet
 * @version $Id: Image.class.php,v 1.2 2010/08/06 03:04:56 elikirkd Exp $
 */
class ImageStyle__Image {
    public $filename = '';
    public $tmp_filename = '';
    public $orig_type = '';
    public $status = 'invalid';
    protected $width  = -1;
    protected $height = -1;
    public $img_rsc = null;
    public $tmp_cache_period = 300; # 5 mins

    public function __construct($filename) {

        ###  Check the type of image
        $this->filename = $filename;
        if ( preg_match('/(\.gif)$/i', $filename) ) { 
            $img_rsc = imagecreatefromgif($filename);
            $this->orig_type = 'gif';
        }
        else if ( preg_match('/(\.jpe?g)$/i', $filename) ) { 
            $img_rsc = imagecreatefromjpeg($filename);
            $this->orig_type = 'jpg';
        }
        else if ( preg_match('/(\.png)$/i', $filename) ) { 
            $img_rsc = imagecreatefrompng($filename);
            $this->orig_type = 'png';
        }
        
        ###  See if it failed...
        if ( ! $img_rsc ) { $this->status = 'error'; }
        ###  Save the resource
        else {
            $this->img_rsc = $img_rsc;
            $this->status = 'valid';
        }
    }

    public function replace_content($new_img_rsc) {
        ###  Swap
        $old_rsc = $this->img_rsc;
        $this->img_rsc = $new_img_rsc;
        imagedestroy($old_rsc); # Clean up memory
        
        ###  Clean Cache
        $this->width = -1;
        $this->height = -1;
        $this->tmp_filename = '';
    }

    public function tmp_filename() {
        global $DAX_CACHE_BASE;

        ###  Delete cache items older than 2 periods
        $cache_period = floor( time() / $this->tmp_cache_period );
        if ( is_dir( $DAX_CACHE_BASE .'/cache_dir' ) ) {
            foreach ( scandir( $DAX_CACHE_BASE .'/cache_dir' ) as $per ) { if ( $per != '.' && $per != '..' && $per < ($cache_period - 1) ) `rm -Rf $DAX_CACHE_BASE/cache_dir/$per`; }  
        }
        $cache_dir = $DAX_CACHE_BASE .'/cache_dir/'. $cache_period;
        if ( ! is_dir($cache_dir) ) mkdir($cache_dir,0775,true);
        if ( ! is_dir($cache_dir) ) return trigger_error("Could not create cache directory: ". $cache_dir, E_USER_ERROR);

        ###  Generate a random file name
        if ( $this->tmp_filename == '' ) {
            $chars = array_merge(range('a','z'),range('0','9'),range('A','Z'));
            while ( $this->tmp_filename == '' || file_exists($this->tmp_filename) ) {
                $this->tmp_filename = $cache_dir .'/gd2_tmp_'. ( $chars[ array_rand($chars) ]. $chars[ array_rand($chars) ]. $chars[ array_rand($chars) ]. $chars[ array_rand($chars) ]. $chars[ array_rand($chars) ].  
                                                                 $chars[ array_rand($chars) ]. $chars[ array_rand($chars) ]. $chars[ array_rand($chars) ]. $chars[ array_rand($chars) ]. $chars[ array_rand($chars) ]
                                                                 ) . '.gd2';
            }
        }
        imagegd2($this->img_rsc, $this->tmp_filename);

        return $this->tmp_filename;
    }

    public function width() {
        if ( $this->width == -1 ) { $this->width = imagesx($this->img_rsc); }
        return $this->width;
    }

    public function height() {
        if ( $this->height == -1 ) { $this->height = imagesy($this->img_rsc); }
        return $this->height;
    }

    public function resize($new_width, $new_height, $border_color = array(255,255,255), $border_top = 0, $border_right = 0, $border_bottom = 0, $border_left = 0) {
        $new_rsc = imagecreatetruecolor( $new_width  + $border_left + $border_right,
                                         $new_height + $border_top + $border_bottom
                                         );
        
        ###  Always set the BGcolor, because it defaults to BLACK
        $bg = imagecolorallocate($new_rsc, $border_color[0], $border_color[1], $border_color[2]);
        imagefill($new_rsc, 0, 0, $bg);
        imagealphablending($new_rsc, true);
        imagesavealpha($new_rsc, true);

        ###  Resize...
        imagecopyresampled($new_rsc, $this->img_rsc, $border_top, $border_left, 0, 0, $new_width, $new_height, $this->width(), $this->height());
        ###  Sharpen 40% ...
        UnsharpMask($new_rsc, 40, 0.5, 3);
        
        ###  Save the new stuff
        $old_rsc = $this->img_rsc;
        $this->img_rsc = $new_rsc;
        imagedestroy($old_rsc); # Clean up memory
        $this->width = -1;
        $this->height = -1;
    }

    public function sharpen($amount = 40, $radius = 0.5, $threshold = 3) {
        UnsharpMask($this->img_rsc, 40, 0.5, 3);
    }
    

}


#########################
###  COPIED FROM: http://vikjavev.no/computing/ump.php

/*

New: 
- In version 2.1 (February 26 2007) Tom Bishop has done some important speed enhancements.
- From version 2 (July 17 2006) the script uses the imageconvolution function in PHP 
version >= 5.1, which improves the performance considerably.


Unsharp masking is a traditional darkroom technique that has proven very suitable for 
digital imaging. The principle of unsharp masking is to create a blurred copy of the image
and compare it to the underlying original. The difference in colour values
between the two images is greatest for the pixels near sharp edges. When this 
difference is subtracted from the original image, the edges will be
accentuated. 

The Amount parameter simply says how much of the effect you want. 100 is 'normal'.
Radius is the radius of the blurring circle of the mask. 'Threshold' is the least
difference in colour values that is allowed between the original and the mask. In practice
this means that low-contrast areas of the picture are left unrendered whereas edges
are treated normally. This is good for pictures of e.g. skin or blue skies.

Any suggenstions for improvement of the algorithm, expecially regarding the speed
and the roundoff errors in the Gaussian blur process, are welcome.

*/

function UnsharpMask($img, $amount, $radius, $threshold)    { 

////////////////////////////////////////////////////////////////////////////////////////////////  
////  
////                  Unsharp Mask for PHP - version 2.1.1  
////  
////    Unsharp mask algorithm by Torstein Hønsi 2003-07.  
////             thoensi_at_netcom_dot_no.  
////               Please leave this notice.  
////  
///////////////////////////////////////////////////////////////////////////////////////////////  



    // $img is an image that is already created within php using 
    // imgcreatetruecolor. No url! $img must be a truecolor image. 

    // Attempt to calibrate the parameters to Photoshop: 
    if ($amount > 500)    $amount = 500; 
    $amount = $amount * 0.016; 
    if ($radius > 50)    $radius = 50; 
    $radius = $radius * 2; 
    if ($threshold > 255)    $threshold = 255; 
     
    $radius = abs(round($radius));     // Only integers make sense. 
    if ($radius == 0) { 
        return $img; imagedestroy($img); break;        } 
    $w = imagesx($img); $h = imagesy($img); 
    $imgCanvas = imagecreatetruecolor($w, $h); 
    $imgBlur = imagecreatetruecolor($w, $h); 
     

    // Gaussian blur matrix: 
    //                         
    //    1    2    1         
    //    2    4    2         
    //    1    2    1         
    //                         
    ////////////////////////////////////////////////// 
         

    if (function_exists('imageconvolution')) { // PHP >= 5.1  
            $matrix = array(  
            array( 1, 2, 1 ),  
            array( 2, 4, 2 ),  
            array( 1, 2, 1 )  
        );  
        imagecopy ($imgBlur, $img, 0, 0, 0, 0, $w, $h); 
        imageconvolution($imgBlur, $matrix, 16, 0);  
    }  
    else {  

    // Move copies of the image around one pixel at the time and merge them with weight 
    // according to the matrix. The same matrix is simply repeated for higher radii. 
        for ($i = 0; $i < $radius; $i++)    { 
            imagecopy ($imgBlur, $img, 0, 0, 1, 0, $w - 1, $h); // left 
            imagecopymerge ($imgBlur, $img, 1, 0, 0, 0, $w, $h, 50); // right 
            imagecopymerge ($imgBlur, $img, 0, 0, 0, 0, $w, $h, 50); // center 
            imagecopy ($imgCanvas, $imgBlur, 0, 0, 0, 0, $w, $h); 

            imagecopymerge ($imgBlur, $imgCanvas, 0, 0, 0, 1, $w, $h - 1, 33.33333 ); // up 
            imagecopymerge ($imgBlur, $imgCanvas, 0, 1, 0, 0, $w, $h, 25); // down 
        } 
    } 

    if($threshold>0){ 
        // Calculate the difference between the blurred pixels and the original 
        // and set the pixels 
        for ($x = 0; $x < $w-1; $x++)    { // each row
            for ($y = 0; $y < $h; $y++)    { // each pixel 
                     
                $rgbOrig = ImageColorAt($img, $x, $y); 
                $rOrig = (($rgbOrig >> 16) & 0xFF); 
                $gOrig = (($rgbOrig >> 8) & 0xFF); 
                $bOrig = ($rgbOrig & 0xFF); 
                 
                $rgbBlur = ImageColorAt($imgBlur, $x, $y); 
                 
                $rBlur = (($rgbBlur >> 16) & 0xFF); 
                $gBlur = (($rgbBlur >> 8) & 0xFF); 
                $bBlur = ($rgbBlur & 0xFF); 
                 
                // When the masked pixels differ less from the original 
                // than the threshold specifies, they are set to their original value. 
                $rNew = (abs($rOrig - $rBlur) >= $threshold)  
                    ? max(0, min(255, ($amount * ($rOrig - $rBlur)) + $rOrig))  
                    : $rOrig; 
                $gNew = (abs($gOrig - $gBlur) >= $threshold)  
                    ? max(0, min(255, ($amount * ($gOrig - $gBlur)) + $gOrig))  
                    : $gOrig; 
                $bNew = (abs($bOrig - $bBlur) >= $threshold)  
                    ? max(0, min(255, ($amount * ($bOrig - $bBlur)) + $bOrig))  
                    : $bOrig; 
                 
                 
                             
                if (($rOrig != $rNew) || ($gOrig != $gNew) || ($bOrig != $bNew)) { 
                        $pixCol = ImageColorAllocate($img, $rNew, $gNew, $bNew); 
                        ImageSetPixel($img, $x, $y, $pixCol); 
                    } 
            } 
        } 
    } 
    else{ 
        for ($x = 0; $x < $w; $x++)    { // each row 
            for ($y = 0; $y < $h; $y++)    { // each pixel 
                $rgbOrig = ImageColorAt($img, $x, $y); 
                $rOrig = (($rgbOrig >> 16) & 0xFF); 
                $gOrig = (($rgbOrig >> 8) & 0xFF); 
                $bOrig = ($rgbOrig & 0xFF); 
                 
                $rgbBlur = ImageColorAt($imgBlur, $x, $y); 
                 
                $rBlur = (($rgbBlur >> 16) & 0xFF); 
                $gBlur = (($rgbBlur >> 8) & 0xFF); 
                $bBlur = ($rgbBlur & 0xFF); 
                 
                $rNew = ($amount * ($rOrig - $rBlur)) + $rOrig; 
                    if($rNew>255){$rNew=255;} 
                    elseif($rNew<0){$rNew=0;} 
                $gNew = ($amount * ($gOrig - $gBlur)) + $gOrig; 
                    if($gNew>255){$gNew=255;} 
                    elseif($gNew<0){$gNew=0;} 
                $bNew = ($amount * ($bOrig - $bBlur)) + $bOrig; 
                    if($bNew>255){$bNew=255;} 
                    elseif($bNew<0){$bNew=0;} 
                $rgbNew = ($rNew << 16) + ($gNew <<8) + $bNew; 
                    ImageSetPixel($img, $x, $y, $rgbNew); 
            } 
        } 
    } 
    imagedestroy($imgCanvas); 
    imagedestroy($imgBlur); 
     
    return $img; 

} 