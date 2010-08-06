<?php

class generateTextImageActions extends sfActions
{
  public function executeIndex(sfWebRequest $request)
  {
    $text_image = $request->getParameter('image_name');
    $settings = sfConfig::get('app_textimage_' . $text_image);

    $this->forward404Unless($settings);

    // Use the callback function/method instead, if set
    if (isset($settings['callback']))
    {
      $mime_type = isset($settings['mime_type']) ? $settings['mime_type'] : 'image/gif';
      $this->getResponse()->setContentType($mime_type);
      
      $parameters = isset($settings['callbackoptions']) ? $settings['callbackoptions'] : array();
      
      return $this->renderText( call_user_func($settings['callback'], $request->getParameter('text'), $parameters) );
    }


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