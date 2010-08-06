<?php

class imagicktextimage
{
  
  public static function generate($text, $params = array())
  {
    $default_params = array(
      'size'                => 48,
      'align'               => 'left',
      'colour'              => 'black',
      'font'                => 'Zapfino.ttf',
      'format'              => 'gif',
      'offset_x'            => 0,
      'offset_y'            => 0,
      'background_colour'   => 'white',
      'background_image'    => false,
      'background_offset_x' => 0,
      'background_offset_y' => 0,
      'width'               => false,
      'height'              => false,
      'quality'             => 95,
    );
    $params = array_merge($default_params, $params);


    /* Set up the main objects */
    $image = new Imagick();
    $draw = new ImagickDraw();

    /* Prepare the text */
    $draw->setFont(textImageGenerator::findFontFile($params['font']));
    $draw->setFontSize( $params['size'] );

    $text_colour = new ImagickPixel();
    $text_colour->setColor($params['colour']);
    $draw->setFillColor($text_colour);

    switch($params['align'])
    {
      case 'left':
        $draw->setGravity(Imagick::GRAVITY_NORTHWEST);
        break;
      
      case 'right':
        $draw->setGravity(Imagick::GRAVITY_NORTHEAST);
        break;
      
      case 'center':
        $draw->setGravity(Imagick::GRAVITY_NORTH);
        break;
    }


    // Get the size of the rendered text so we can make the image the right size
    $info = $image->queryFontMetrics($draw, $text);


    $background_colour = new ImagickPixel();
    $background_colour->setColor($params['background_colour']);


    /* Create the image */
    $width  = $params['width'] ? $params['width'] : $info['textWidth'];
    $height = $params['height'] ? $params['height'] : $info['textHeight'];

    $image->newImage($width, $height, $background_colour);


    // Composite background
    if ($params['background_image'])
    {
      $bg = new Imagick(textImageGenerator::findBackgroundImage($params['background_image']));
      $image->compositeImage($bg, Imagick::COMPOSITE_DEFAULT, $params['background_offset_x'],$params['background_offset_y']);
    }

    /* Create text */
    $image->annotateImage($draw, $params['offset_x'], $params['offset_y'], 0, $text);


    /* Output the image with headers */
    $image->setImageFormat($params['format']);
    
    if ($params['format'] == 'jpg')
    {
      $image->setImageCompressionQuality($params['quality']);
    }

    return $image->getImageBlob();
    
  }
  
}