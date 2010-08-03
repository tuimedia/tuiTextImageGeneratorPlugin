<?php
/* Generate (and return the path to) an image containing the given text. Line-wraps if necessary.
 * Sensible defaults are given for most options, but you MUST provide the following:
 *  - text
 *  - width
 *
 * This function needs to be customised for use outside the platinum template of the inspaminator
 * currently it assumes file paths, and has limited colour names (but you can set a 'colour' option
 * to override the internals).
 *
 * CAVEATS/KNOWN ISSUES:
 * - h_centered doesn't center each line of text separately, only the "text box" of all text. To get centered text, you should set the align option to "center" - although this isn't implemented yet.
 * - use_colour is ugly, bad code that uses globals, and relies on the getColours function.
 * - the "colour" and "transparency" options take a decimal RGB triplet as a string: eg, "255,255,255". It would be trivial to add support for hex triplets too (#FFFFFF)
 * - the "size" option is in points, not pixels, although GD doesn't seem to specify a DPI for the image - I assume that it's 72 DPI or something. All other measurements are in pixels, just to confuse you.
 *
 */

class textImageGenerator {
  
  public static function generateTextImage($text, $options = array()) {

    $default_options = array(
      'width'              => false,
      'min_height'         => false,
      'font'               => 'agendaspecial.ttf',
      'size'               => '11',
      'use_colour'         => false,
      'background'         => '',
      'background_folder'  => '',
      'background_colour'  => false,
      'bg_offset_x'        => 0,
      'bg_offset_y'        => 0,
      'offset_x'           => 0,
      'offset_y'           => 0,
      'h_centered'         => false,
      'v_centered'         => false,
      'transparency'       => '255,255,255',
      'colour'             => false,
      'margin_x'           => 0,
      'align'              => 'left',
      'leading'            => 0
    );


    // Convert ~ to new-lines
    $text = str_replace('~',PHP_EOL,$text);

    $options = array_merge($default_options, $options);

    // Specific colour override?
    if ($options['colour']) {
      $colour = $options['colour'];
    }

    list($red,$green,$blue) = explode(',',$colour);

    $fontpath = sfConfig::get('sf_web_dir').'/fonts/'.$options['font'];

    if ($options['width']) {
      // Make text to fit width - offset_x
      $text_lines = self::mb_wordwrap($text, $fontpath, $options['size'], $options['width'] - $options['offset_x'] - ($options['margin_x'] * 2));
      $text_lines = array_map('trim', $text_lines);
      $text = join("\n",$text_lines);
    } else {
      $text_lines = array(trim($text));
    }

    // Get height, width of the text itself
    list($blx,$bly, $brx,$bry, $trx,$try, $tlx,$tly) = imagettfbbox($options['size'],0,$fontpath,$text);
    $leftmost = ($blx < $tlx) ? $blx : $tlx;
    $rightmost = ($brx > $trx) ? $brx : $trx;
    $topmost = ($tly < $try) ? $tly : $try;
    $bottommost = ($bly > $bry) ? $bly : $bry;


    // Figure out the baseline height and the maximum line height
    $bbox_data = imagettfbbox($options['size'],0,$fontpath,'bF');
    $baseline_offset = $bbox_data[1] - $bbox_data[7];

    $bbox_data = imagettfbbox($options['size'],0,$fontpath,'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ');
    $max_line_height = $bbox_data[1] - $bbox_data[7];

    $text_width = $rightmost - $leftmost;
    $text_height = ($max_line_height * count($text_lines)) + ($options['leading'] * (count($text_lines) - 1));

    if (!$options['width']) {
      $options['width'] = $text_width;
    }


    // Create image
    $image_height = $options['min_height'] && (($text_height + $options['offset_y']) < $options['min_height']) ? $options['min_height'] : $text_height + $options['offset_y'];
    if ($options['background']) {
      $bg_path = sfConfig::get('sf_web_dir').'/'.$options['background_folder'].'/'.$options['background'];
      $bg_info = getimagesize($bg_path);
      if (($bg_info[1] + $options['bg_offset_y']) > $image_height) {
        $image_height = $bg_info[1] + $options['bg_offset_y'];
      }
    }

    $image = imagecreatetruecolor($options['width'],$image_height);

    // Generate colour, transparency, background
    list($tr,$tg,$tb) = explode(',',$options['transparency']);

    $transparency = imagecolorallocate($image, $tr,$tg,$tb);
    $image_colour = imagecolorallocate($image, $red, $green, $blue);

    imagefilledrectangle($image, 0,0, $options['width'], $image_height, $transparency);
    imagecolortransparent($image, $transparency);

    if ($options['background_colour']) {
      list($br, $bg, $bb) = explode(',',$options['background_colour']);
      $bg_colour = imagecolorallocate($image, $br, $bg, $bb);
      imagefilledrectangle($image, 0,0, $options['width'], $image_height, $bg_colour);
    }

    // Insert background if necessary
    if ($options['background']) {
      switch($bg_info[2]) {
        case IMAGETYPE_GIF:
          $bg = imagecreatefromgif($bg_path);
          break;
        case IMAGETYPE_JPEG:
          $bg = imagecreatefromjpeg($bg_path);
          break;
        case IMAGETYPE_PNG:
          $bg = imagecreatefrompng($bg_path);
          break;
        default:
          die("Unrecognised image type");
      }

      imagecopy($image, $bg, $options['bg_offset_x'],$options['bg_offset_y'], 0,0, $bg_info[0], $bg_info[1]);
    }


    // Add text
    if ($options['h_centered']) {
      $options['offset_x'] = floor(($options['width'] / 2) - ($text_width / 2));
    }
    if ($options['v_centered']) {
      $options['offset_y'] = floor(($image_height / 2) - ($text_height / 2));
    }



    $longest_line_baseline_x = $options['offset_x'] + $options['margin_x'];
    $first_line_baseline_y = $options['offset_y'] + $baseline_offset;

    $line_num = 1;
    $minimum_offset_x = $options['width'] - $text_width;
    // Now, render each line separately
    foreach ($text_lines as $line) {
      list($blx,$bly, $brx,$bry, $trx,$try, $tlx,$tly) = imagettfbbox($options['size'],0,$fontpath,$line);
      $leftmost = ($blx < $tlx) ? $blx : $tlx;
      $rightmost = ($brx > $trx) ? $brx : $trx;
      $topmost = ($tly < $try) ? $tly : $try;
      $bottommost = ($bly > $bry) ? $bly : $bry;

      // Figure out its width to find the individual horizontal offset to apply
      $line_width = $rightmost - $leftmost;
      $line_height = $bottommost - $topmost;

      if ($options['align'] == 'right') {
        $line_x = $minimum_offset_x + $longest_line_baseline_x + ($text_width - $line_width);
      } else {
        $line_x = $options['offset_x'] + ($options['margin_x'] / 2);
      }
      // Figure out the vertical offset from the line height and line number
      $line_y = $options['offset_y'] + ($max_line_height * $line_num - ($max_line_height - $baseline_offset)) + ($options['leading'] * ($line_num - 1));

      // Insert the line
      imagettftext($image, $options['size'], 0, $line_x, $line_y, $image_colour, $fontpath, $line);

      $line_num++;
    }






    // Save image
    $key = sprintf('%08X.gif',crc32($text.$colour.serialize($options)));

    return imagegif($image);
    imagedestroy($image);
    // Return URL
    return 'images/'.$key;

  }




  // Ignore the following, it's just utility-crap we need for wrapping lines in images
  private static function mb_wordwrap($txt,$font,$size,$width) {
    $pointer = 0; // Current character position pointer
    $this_line_start = 0; // Starting character position of current line
    $this_line_strlen = 1; // How long is the current line
    $single_byte_stack = ""; // Variable for storing single byte word
    $sbs_line_width = 0; // Pixel width of the Single byte word
    $this_is_cr = FALSE; // Check if the character is new line code (ASCII=10)
    $result_lines = array(); // Array for storing the return result

    while ($pointer < mb_strlen($txt)) {
      $this_char = mb_substr($txt,$pointer,1);
      if (ord($this_char[0])==10) $this_is_cr = TRUE; // Check if it is a new line
      // Check current line width
      $tmp_line = mb_substr($txt, $this_line_start, $this_line_strlen);
      $tmp_line_bbox = imagettfbbox($size,0,$font,$tmp_line);
      $this_line_width = $tmp_line_bbox[2]-$tmp_line_bbox[0];

      // Prevent to cut off english word at the end of line
      // if this character is a alphanumeric character or open bracket, put it into stack
      if (self::is_alphanumeric($this_char, $single_byte_stack)) $single_byte_stack .= $this_char;
      // Check the width of single byte words
      if ($single_byte_stack != "") {
        $tmp_line_bbox = imagettfbbox($size,0,$font,$single_byte_stack);
        $sbs_line_width = $tmp_line_bbox[2]-$tmp_line_bbox[0];
      }

      if ($this_is_cr || $this_line_width > $width || $sbs_line_width >= $width) {
        // If last word is alphanumeric, put it to next line rather then cut it off
        if ($single_byte_stack != "" && self::is_alphanumeric($this_char, $single_byte_stack) && $sbs_line_width < $width) {
          $stack_len = mb_strlen($single_byte_stack);
          $this_line_strlen = $this_line_strlen - $stack_len + 1;
          $pointer = $pointer - $stack_len + 1;
        }
        // Move the current line to result array and reset all counter
        $result_lines[] = mb_substr($txt, $this_line_start, $this_line_strlen-1);
        if ($this_is_cr) {
          $pointer++;
          $this_is_cr=FALSE;
        }
        if ($sbs_line_width >= $width) $sbs_line_width = 0;
        $this_line_start = $pointer;
        $this_line_strlen = 1;
        $single_byte_stack = "";
      } else {
        if (!(self::is_alphanumeric($this_char, $single_byte_stack))) {
          $single_byte_stack = ""; // Clear stack if met multibyte character and not line end
        }
        $this_line_strlen++;
        $pointer++;
      }
    }
    // Move remained word to result
    $result_lines[] = mb_substr($txt, $this_line_start);

    return $result_lines;
  }

  private static function is_alphanumeric($character, $stack) {
    if (
    (ord($character)>=48 && ord($character)<=57) ||
            (ord($character)>=65 && ord($character)<=91) ||
            (ord($character)>=97 && ord($character)<=123) ||
            ord($character)==40 ||
            ord($character)==60 ||
            ($stack=="" && (ord($character)==34 || ord($character)==39))
    ) return TRUE;
    else return FALSE;
  }

}
