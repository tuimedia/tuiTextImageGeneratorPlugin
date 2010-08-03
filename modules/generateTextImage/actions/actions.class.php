<?php

class generateTextImageActions extends sfActions
{
  public function executeIndex(sfWebRequest $request)
  {
    $text_image = $request->getParameter('image_name');
    $settings = sfConfig::get('app_textimage_' . $text_image);

    $this->forward404Unless($settings);

    $image =  textImageGenerator::generateTextImage(
      $request->getParameter('text'),
      array(
        'width' => $settings['width'],
        'min_height' => $settings['min_height'],
        'font' => $settings['font'],
        'size' => $settings['size'],
        'background' => $settings['background'],
        'background_folder' => $settings['background_folder'],
        'background_colour' => $settings['background_colour'],
        'h_centered' => $settings['h_centered'],
        'v_centered' => $settings['v_centered'],
        'transparency' => $settings['transparency'],
        'colour' => $settings['colour'],
        'margin_x' => $settings['margin_x'],
        'align' => $settings['align'],
        'offset_x' => $settings['offset_x'],
        'offset_y' => $settings['offset_y'],
        'leading' => $settings['leading']
        )
      );

    $response = $this->getResponse();
    $response->setContentType('image/gif');
    $response->setContent($image);

    return sfView::NONE;

  }
}