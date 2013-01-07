# tuiTextImageGeneratorPlugin

This is a Symfony 1.4 plugin to generate images from a URL route. It's part of our email template generator tool, and as such isn't designed to be used directly in a production environment (mostly because it doesn't cache). Typically it's used to create custom buttons, lay out text on image backgrounds, etc. 

By default it uses PHP's version of GD, but that's notoriously bad at font-handling (it kerns appallingly and doesn't respect baselines - just try rendering something in Zapfino with it), so it can also be configured to use a custom callback, and an IMagick-based alternative is included. 

## Installation

Standard Symfony plugin installation instructions apply:

* Put the plugin into your Symfony project's `plugins/` folder
* Load the plugin in your `config/ProjectConfiguration.php` class
* Configure it in `apps/frontend/config/app.yml` (or whatever)

## Image Links

The tuiTextImageGeneratorPlugin generates images on the fly with custom text. The module registers a route `/image/:image_name` and enables the module generateTextImage. To insert an image containing the text "Some Text" for the image button named "button" you can use

```php
<img src="<?php print url_for('@text_image?image_name=button&text=' . urlencode('Some Text')) ?>" />
```

**Note:** if you are printing a variable instead of the actual text, there is no need to use urlencode as symfony handles that for you:

```php
<img src="<?php print url_for('@text_image?image_name=button&text=' . $object->getMyfield(ESC_RAW) ?>" />
```

## Settings

The rest of the options for creating "button" including `background_image` are set in the `/apps/frontend/config/app.yml` file. Here is an example of possible settings for button:

```yaml
  textimage:
    button:
      width:             100
      min_height:        500
      font:              agendaspecial.ttf
      size:              14
      background_image:  tool_title_email_builder.gif
      background_colour: false
      bg_offset_x:       0
      bg_offset_y:       0
      offset_x:          20
      offset_y:          10
      h_centered:        false
      v_centered:        false
      transparency:      '#FFFFFF'
      colour:            '123,233,111'
      margin_x:          0
      align:             left
      leading:           0
```

If you remove a setting, the plugin will use "sensible" defaults. You do not therefore need to specify every value in the ``app.yml``.


## Settings explained (Default GD Renderer)

**NOTE:** The only required setting is the text. If you supply nothing else, the module will generate an image of that text in 14pt Agenda Special, black on a transparent white background, large enough to fit the text.

### text

**Possible values:** any UTF-8 string  
**Default value:** none

The text passed should be a UTF-8 encoded string. For convenience, the ~ (tilde) character is replaced with a line-break. If you set a `width` on the image and the text doesn't fit on one line, it is word-wrapped automatically.

### align

**Possible values**: left,right  
**Default value**: left

On multi-line text, aligns each line to the left or right of the text block.

### background_colour

**Possible values:** RGB triplet in decimal or hex (e.g. 255,255,255, or #FFFFFF)  
**Default value:** 255,255,255 (white)

Fills the image background. To make a transparent background, set the `transparency` setting to the same colour.

### background_image 

**Possible values:** false, image filename  
**Default value:** false

If set, adds an image under the text. You can use `bg_offset_x` and `bg_offset_y` to position the background image. 

The plugin looks for images by default in the `/data/images/` directory of your symfony project. If the image does not exist there, it will then look in the plugin's own `data/images` directory. You can also supply a full path or relative path to the image file, starting from the web directory (I think). The plugin checks this first.

### bg_offset_x, bg_offset_y 

**Possible values:** integer  
**Default value:** 0

If set, specifies the position in pixels of the top left of the background image, relative to the top left of the generated output image.

### colour

**Possible values:** RGB triplet in decimal or hex (e.g. 255,255,255, or #FFFFFF)  
**Default value:** 0,0,0 (black)

Sets the colour of the text.

### font 

**Possible values:** full or relative path to a .ttf file  
**Default value:** agendaspecial.ttf

The plugin will look for fonts by default in the `/data/fonts/` directory. If the font does not exist there, it will then look in the plugin's own `data/fonts` directory. You can also supply a full path or relative path to the font file, starting from the web directory. The plugin checks this first. We don't include a default font for licensing reasons.

### h_centered, v_centered 

**Possible values:** true, false  
**Default value:** false

This centers the position of the text block horizontally and/or vertically within the generated image. It does NOT centre each line of text within the block. If no width is set for the image, then the `h_centered` setting has no effect (since the image will be as large as the text block). Similarly, if `min_height` setting isn't set, then the `v_centered` setting has no effect.

### leading

**Possible values:** integer  
**Default value:** 0

If set, adds a gap between each line of text. 


### margin_x 

**Possible values:** integer  
**Default value:** 0

If set, adds horizontal spacing on either side of the text box.


### min_height 

**Possible values:** false, integer  
**Default value:** false

Sets the minimum height of the generated image. The image is always rendered tall enough to contain the text, but you can set this to make the image bigger if necessary (useful for making sure the background image isn't cut off).

### offset_x, offset_y 

**Possible values:** integer  
**Default value:** 0

Sets the position of the top left of the text box within the generated image. Useful for positioning text on a background image (e.g., creating buttons)


### size 

**Possible values:** integer  
**Default value:** 14

The font-size in points.


### transparency 

**Possible values:** RGB triplet in decimal or hex (e.g. 255,255,255, or #FFFFFF)  
**Default value:** 255,255,255 (white)

Sets which colour to use as the transparency colour. By default it's white, the same as the background, so generated images have a transparent background.


### width 

**Possible values:** false, integer  
**Default value:** false

Sets the width of the image, in pixels. If `width` is set to false (the default), then the image will be sized to fit the text box. If the width is shorter than the length of the longest line of text, the text is word-wrapped to fit.

### Using a custom generator

You can use the `callback`, `callbackoptions`, and `mime_type` settings to specify an alternate image generator. An Imagick-based alternative is included in the plugin, which requires the php-imagick extension to be installed on the server. Example usage:

```yaml
  textimage:
    imagickexample:
      callback: [imagicktextimage,generate]
      mime_type: image/jpg
      callbackoptions:
        font: 'Zapfino.ttf'
        size: 18
        
        # Format: gif, jpg, png
        format: jpg
        
        # If JPG, use this to set the compression quality 0-100
        quality: 100
        
        # colours can be some names (like black, white, silver, transparent), 
        # hex #RRGGBB, rgb(255,255,255), or with opacity rgba(0,0,0, 1)
        colour: rgba(255,0,0,0.5)
        background_colour: white
        
        background_image: testbg.jpg
        background_offset_x: 0  # position the background image, in pixels
        background_offset_y: 0
        
        # width and height can be set blank to make the image size fit the text
        width: 320
        height: 320
        
        offset_x: 0  # left margin if left aligned or centered, or right margin if right-aligned 
        offset_y: 25 # vertical margin
        
        align: center # or left, or right
```

The imagick generator produces better quality text with proper kerning-table and font hinting support, however it doesn't word-wrap, and it doesn't support variable leading. In the example above all the settings are optional, 

### callback

This is a standard PHP callback: either a string (which is the name of a function), or a two-item array (class name and static method). The function or static method needs to accept two arguments, the first is the text to render, and the second is an optional array of settings defined in the `callbackoptions` setting.

The method/function should return the image data to display. 

### callbackoptions 

An optional array that is passed as the second argument to your callback method/function.

### mime_type 

If your custom method doesn't produce GIF images like the default does, use this setting to change the content type that's sent before the callback is run.
