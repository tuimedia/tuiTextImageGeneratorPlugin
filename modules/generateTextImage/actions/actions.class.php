<?php

class generateTextImageActions extends sfActions
{
  public function executeIndex(sfWebRequest $request)
  {
    $text       = $request->getParameter('text');
    $text_image = $request->getParameter('image_name');
    $settings   = sfConfig::get('app_textimage_' . $text_image);
    $filename   = sprintf('%08X', crc32($text_image.serialize($settings).$text));
    $mime_type  = isset($settings['mime_type']) ? $settings['mime_type'] : 'image/gif';

    $this->forward404Unless($settings);

    switch(strtolower($mime_type))
    {
      case 'image/gif':
        $filename .= '.gif';
        break;

      case 'image/png':
        $filename .= '.png';
        break;
      
      case 'image/jpeg':
        $filename .= '.jpg';
        break;
    }
    

    // Use the callback function/method instead, if set
    if (isset($settings['callback']))
    {
      $this->getResponse()->setContentType($mime_type);
      $this->getResponse()->setHttpHeader('Content-Disposition', 'filename='.$filename);
      
      $parameters = isset($settings['callbackoptions']) ? $settings['callbackoptions'] : array();
      
      try
      {
        return (isset($settings['pass_request_object']) && $settings['pass_request_object']) ? 
          $this->renderText( call_user_func($settings['callback'], $text, $parameters, $request) ) :
          $this->renderText( call_user_func($settings['callback'], $text, $parameters) );
      } catch (Exception $e)
      {
        $response = $this->getResponse();
        $response->setContentType('image/gif');
        $response->setHttpHeader('Content-Disposition', 'filename='.$filename);
        return $this->renderText(textImageGenerator::generateTextImage($e->getMessage()));
      }
      
    }


    try{
      $image = textImageGenerator::generateTextImage($text, $settings);
    } catch (Exception $e)
    {
      $response = $this->getResponse();
      $response->setContentType('image/gif');
      // $response->setContent();
      return $this->renderText(textImageGenerator::generateTextImage($e->getMessage()));
    }
    
    $response = $this->getResponse();
    $response->setContentType('image/gif');
    $response->setHttpHeader('Content-Disposition', 'filename='.$filename);
    
    return $this->renderText($image);

  }
}