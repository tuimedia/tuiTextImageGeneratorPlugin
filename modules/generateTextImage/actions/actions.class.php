<?php

class generateTextImageActions extends sfActions
{
  public function executeIndex(sfWebRequest $request)
  {
    $text_image = $request->getParameter('image_name');
    $settings = sfConfig::get('app_textimage_' . $text_image);

    $this->forward404Unless($settings);

    try{
      $image =  textImageGenerator::generateTextImage(
        $request->getParameter('text'),
        $settings
      );
    } catch (Exception $e)
    {
      $response = $this->getResponse();
      $response->setContentType('image/gif');
      // $response->setContent();
      return $this->renderText(textImageGenerator::generateTextImage($e->getMessage()));
    }
    
    $response = $this->getResponse();
    $response->setContentType('image/gif');
    return $this->renderText($image);

  }
}