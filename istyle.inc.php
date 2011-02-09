<?
$DAX_IMAGE_STYLE_URL_ROOT = '/istyle';
$DAX_IMAGE_STYLE_DEFAULT_CODE = 'default';
$DAX_IMAGE_STYLE_ORIG_MAX_SIZE = '2'; # megabytes
$DAX_IMAGE_STYLE_ORIG_MAX_FORCE_ISTYLE_CODE = 'orig_max_force_istyle';
$DAX_CACHE_BASE =             $_SERVER['DOCUMENT_ROOT'] .'/istyle/tmp';
$DAX_IMAGE_STYLE_RSC_ROOT =   $_SERVER['DOCUMENT_ROOT'] .'/istyle/rsc';
$DAX_IMAGE_STYLE_ORIG_BASE =  $_SERVER['DOCUMENT_ROOT'] .'/istyle/orig';
$DAX_IMAGE_STYLE_CACHE_BASE = $_SERVER['DOCUMENT_ROOT'] .'/istyle';
$DAX_IMAGE_STYLES = array( 'default' => array( 'size' => '450x350;mode=fit;avoid_upscale=true',
                                                 'crop' => 'nocrop',
                                                 'filter_script' => 'sharpen(2)',
                                                 'format' => 'jpeg;jpeg_quality=80%',
                                                 ),
                             'orig_max_force_istyle' => array( 'size' => '1600x1600;mode=fit;avoid_upscale=true',
                                                               'crop' => 'nocrop',
                                                               'filter_script' => '',
                                                               'format' => 'jpeg;jpeg_quality=85%',
                                                               ),
                             'apple/vid_callout' => array( 'size' => '146x84;mode=fill',
                                                      'crop' => 'nocrop',
                                                      'filter_script' => 'sharpen(2)',
                                                      'format' => 'jpeg;jpeg_quality=95%',
                                                      ),
                             'apple/ipad_frame_1' => array( 'size' => '226x315;mode=fill',
                                                            'crop' => 'nocrop',
                                                            'filter_script' => 'sharpen(2)
                                                                                | frame( RSC_ROOT/empty_ipad_1.png
                                                                                         ; frame_order = top
                                                                                         ; border_left = 36
                                                                                         ; border_top = 41
                                                                                         ; border_right = 27
                                                                                         ; border_bottom = 68
                                                                                         )',
                                                            'format' => 'png;png_quality=0%;png_alpha=true',
                                                            ),
                             'apple/overview_mail_frame' => array( 'size' => '201x290;mode=fill',
                                                                   'crop' => 'nocrop',
                                                                   'filter_script' => 'sharpen(2)
                                                                                       | frame( RSC_ROOT/overview_mail_frame.png
                                                                                                ; frame_order = top
                                                                                                ; border_left = 347
                                                                                                ; border_top = 42
                                                                                                ; border_right = 0
                                                                                                ; border_bottom = 74
                                                                                                )',
                                                                   'format' => 'jpeg;jpeg_quality=85%',
                                                                   ),
                             'apple/overview_photos_frame' => array( 'size' => '224x290;mode=fill',
                                                                     'crop' => 'nocrop',
                                                                     'filter_script' => 'sharpen(2)
                                                                                         | frame( RSC_ROOT/overview_photos_frame.png
                                                                                                  ; frame_order = top
                                                                                                  ; border_left = 295
                                                                                                  ; border_top = 42
                                                                                                  ; border_right = 34
                                                                                                  ; border_bottom = 68
                                                                                                  )',
                                                                     'format' => 'png;png_quality=100%;png_alpha=true',
                                                                     ),
                             'apple/ipad_frame_portrait' => array( 'size' => '223x293;mode=fill',
                                                                   'crop' => 'nocrop',
                                                                   'filter_script' => 'sharpen(2)
                                                                                       | frame( RSC_ROOT/ipad_frame_portrait.png
                                                                                                ; frame_order = top
                                                                                                ; border_left = 34
                                                                                                ; border_top = 43
                                                                                                ; border_right = 33
                                                                                                ; border_bottom = 66
                                                                                                )',
                                                                   'format' => 'jpeg;jpeg_quality=85%',
                                                                   ),
                             'apple/ipad_frame_landscape' => array( 'size' => '280x210;mode=fill',
                                                                    'crop' => 'nocrop',
                                                                    'filter_script' => 'sharpen(2)
                                                                                        | frame( RSC_ROOT/ipad_frame_landscape.png
                                                                                                 ; frame_order = top
                                                                                                 ; border_left = 35
                                                                                                 ; border_top = 30
                                                                                                 ; border_right = 35
                                                                                                 ; border_bottom = 60
                                                                                                 )',
                                                                    'format' => 'jpeg;jpeg_quality=85%',
                                                                    ),
                             'apple/nav_promo' => array( 'size' => '161x84;mode=fill',
                                                         'crop' => 'nocrop',
                                                         'filter_script' => 'sharpen(2)
                                                                             | frame( RSC_ROOT/nav_promo_frame.png
                                                                                      ; frame_order = top
                                                                                      ; border_left = 16
                                                                                      ; border_top = 54
                                                                                      ; border_right = 15
                                                                                      ; border_bottom = 7
                                                                                      )',
                                                         'format' => 'jpeg;jpeg_quality=95%',
                                                         ),

                             );
