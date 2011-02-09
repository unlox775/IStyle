<?php
ini_set('display_errors',true);
require_once($_SERVER['DOCUMENT_ROOT'] .'/istyle/debug.inc.php');
require_once($_SERVER['DOCUMENT_ROOT'] .'/istyle/istyle.inc.php');
?>
<!-- /////  Upload Box  ///// -->
<div style="float: right; width: 400px; background: #eee; margin: 0 0 20px 20px; padding: 10px">
    <form method="POST"
        enctype="multipart/form-data"
        action="/istyle/upload.php"
        >
        <input type="hidden" name="redirect" value="/"/>
        Upload New Example Image(s):<br/>
        <input type="file" name="file1"/><br/>
        <input type="file" name="file2"/><br/>
        <input type="file" name="file3"/><br/>
        <input type="submit" value="Upload"/>
    </form>
</div>

<!-- /////  Documentation  ///// -->
<h2>ImageStyle System = On-the-Fly Image Styling (using GD)</h2>
<p>

    This is a simple PHP framework that provides powerful image
    styling and generation.  It is especially useful for areas
    where clients upload their own images, and those images need
    to be scaled, sharpened, framed or some other image
    manipulation to match the style of the web site.
    <b><u>Installation can be as simple as grabbing the source of this
    website, as Many places already seem to have GD for PHP
    compiled in.</b></u>

</p>
<p>

    Below are some examples of styles we actually used for one of
    our clients.  You can see, above each, the PHP style
    definition, which is just an array with settings in it.  They
    all begin with an un-named first parameter that is required,
    and optional name/value parameters following, each separated
    by a semicolon.  Space is ignored.  These are the options for
    each setting:

    <ul>
        <li>size: scale and possibly trim the image (only ever scales proportionally)
            <ul>
               <li>Required Param: the pixel dimensions, separated by an 'x'.  E.g. 100x200, 135x209, etc</li>
               <li>mode: how to adapt the image, options are: fit (scale it down, but don't crop) or fill (crop it so it fills the whole dimensions)</li>
               <li>fill_extra: Applies only to mode=fit (or either if "avoid_upscale" is set).  This will add color to the edges as needed to make the output exactly the dimensions.  The value is an HTML color entity (e.g. #fff or #cdcdcd)</li>
               <li>avoid_upscale: "true" or "false", whether to skip the size operation if the pic is smaller than the dimensions</li>
               <li>xadj and yadj: Applies only to mode=fill.  As it crops off part, these let you adjust relative to center.  Values may be negative.  E.g. "-80%", "4%", "40px"</li>
            </ul>
        </li>
        <li>crop: zoom in a bit on the image, especialy good when most of your images have a good padding of whitespace around the edges
            <ul>
               <li>Required Param: equal or nocrop.  equal means crop by percentage equally using the "all" param</li>
               <li>all: how much to crop off of all sides, e.g. "5%" means take 5% off the edges.  The final image will be 95% of what it was.</li>
            </ul>
        </li>
        <li>filter_script: This is a colon separated list of actions.

            <p>
                This is the literal set of actions to perform on
                the mage in order.  If "crop" and "size" are not
                mentioned (most people don't need to), they are
                automatically prepended to the script.  This way
                you can choose to do some operations before or
                in-between the crop and size.
            </p>
            <p>
                Each action is referred to by it's name, followed by the parameters (Required Param and name/value parameters; same syntax as above) in parenthesis.  These are the valid actions:
            </p>
            <ul>
                <li>crop: doesn't need to be mentioned in filter_script.  Params can be omitted, in which case it uses the "crop" option's value above.</li>
                <li>size: doesn't need to be mentioned in filter_script.  Params can be omitted, in which case it uses the "size" option's value above.</li>
                <li>sharpen: performs a sharpen operation on the image
                    <ul>
                        <li>Required Param: Unsharp mask radius. 2 is a good value</li>
                        <li>amount: Unsharp mask amount.  Default's to 50.  These values are adjusted to be as close to Photoshop's as possible.</li>
                        <li>threshold: Unsharp mask threshold.  Default's to 0, meaning sharpen the whole image</li>
                    </ul>
                </li>
                <li>frame: layer in another image as a frame (or anything you like).  It's pretty much assumed that the frame image is larger than the source image.
                    <ul>
                        <li>Required Param: the path to the image.  NOTE: you can (and probably should) use the "RSC_ROOT" keyword in your path, which pulls from the config value.  E.g. RSC_ROOT/yellow_frame.png</li>
                        <li>frame_order: top or bottom, default's to "top".  What layer order to insert the frame image at.  Top is only useful if your frame image is a PNG with alpha transparency.</li>
                        <li>border_(top|bottom|eft|right): These are in pixels and are used to center and position your source image over or under your frame.</li>
                    </ul>
                </li>
            </ul>
        </li>
        <li>format: What kind of file to save as, as well as compression options.
            <ul>
               <li>
                   Required Param: jpeg, gif or png.  NOTE: This
                   does NOT change the extension of the final
                   image.  This means that if your source image
                   was a PNG, but your output was a JPEG, the
                   file will still be named foo.png, but have
                   JPEG contents in it.  Furthermore, Apache
                   chooses the mime-type by extension, so it will
                   actually be server this way.  HOWEVER, So far
                   it appears that all browsers, even IE5+, just
                   do the right thing and it all still render's
                   fine.  This is an issue that isn't that easy
                   for us to fix, but just keep it in mind...
               </li>
               <li>jpeg_quality, png_quality: Values from 0% to 100%.  These values are passed into the GD engine, I don't know what PNG compression is tho.</li>
               <li>png_alpha: Set this to true if you want your PNG to have an alpha channel for transparency</li>
            </ul>
        </li>
        <li>rotate: Allows you to rotate the image to any degree
            <ul>
               <li>
                   Required Param: number of degrees to rotate (clockwise).  Negative is ok too.  E.g. "46.7deg"
               </li>
               <li>fill_extra: A color to fill the background with, because thus actually enlarges the image. The value is an HTML color entity (e.g. #fff or #cdcdcd).  Leave this blank to get transparent.</li>
            </ul>
        </li>
    </ul>
</p>
<br clear="all"/>
<br clear="all"/>

<h2>Examples:</h2>
<b>Original Images:<b/> (all squished to 100x100):<br/>
<div style="height: 200px; overflow: scroll; border: 1px solid black">
    <?php
    $files = array();
    foreach ( scandir($DAX_IMAGE_STYLE_ORIG_BASE) as $file ) {
        if ( is_dir($DAX_IMAGE_STYLE_ORIG_BASE .'/'. $file) ) continue;
        $files[] = $file;
        ?>
        <div style="float: left;  margin: 15px;">
            <img src="<?php echo $DAX_IMAGE_STYLE_URL_ROOT ?>/orig/<?php echo $file ?>" width=100 height=100>
        </div>
    <? } ?>
</div>

<?php foreach( $DAX_IMAGE_STYLES as $style_code => $x ) { ?>
    <br clear="all"/>
    <hr style="border: none; border-top: 2px dashed blue">
          <b>Style Code:</b> <?php echo $style_code ?>, <b>URL:</b> /istyle/<?php echo $style_code ?>/<i>img_name.jpg</i>, <b>Style definition:</b> (see below in <span style="color:red">RED ==&gt;</span>)<br/>
    <?php bug($x); ?>
    <b>Output Examples:</b><br/>      
    <div style="height: 200px; overflow: scroll; border: 1px solid black">
        <?php foreach ( $files as $file ) { ?>
            <div style="float: left;  margin: 15px;">
                <img src="<?php echo $DAX_IMAGE_STYLE_URL_ROOT ?>/<?php echo $style_code ?>/<?php echo $file ?>">
            </div>
        <? } ?>
    </div>
<? } ?>
