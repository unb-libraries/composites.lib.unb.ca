<?php

namespace Drupal\pixel_crop\Plugin\ImageEffect;

use Drupal\image\Annotation\ImageEffect;
use Drupal\image\ImageEffectBase;
use Drupal\Core\Image\ImageInterface;

/**
 * Crop the image using pixel coordinates.
 *
 * @ImageEffect(
 *   id = "pixel_crop",
 *   label = @Translation("Pixel crop"),
 *   description = @Translation("Crop an image with exact pixel coordinates (x,y,width,height).")
 * )
 */
class PixelCrop extends ImageEffectBase {

  /**
   * Apply the effect to the given image.
   *
   * Note: Drupal 11 image effect interface requires applyEffect().
   *
   * @param \Drupal\Core\Image\ImageInterface $image
   *   The image to transform.
   *
   * @return bool
   *   TRUE on success, FALSE otherwise.
   */
  public function applyEffect(ImageInterface $image) {
    $x = isset($this->configuration['x']) ? (int) $this->configuration['x'] : 0;
    $y = isset($this->configuration['y']) ? (int) $this->configuration['y'] : 0;
    $width = isset($this->configuration['width']) ? (int) $this->configuration['width'] : 0;
    $height = isset($this->configuration['height']) ? (int) $this->configuration['height'] : 0;

    if ($width <= 0 || $height <= 0) {
      // Nothing to do.
      return FALSE;
    }

    $orig_w = $image->getWidth();
    $orig_h = $image->getHeight();

    // Clamp coordinates to image bounds.
    $x = max(0, min($x, $orig_w - 1));
    $y = max(0, min($y, $orig_h - 1));
    $width = min($width, $orig_w - $x);
    $height = min($height, $orig_h - $y);

    // Perform pixel crop using the toolkit.
    $image->crop($x, $y, $width, $height);

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'x' => 0,
      'y' => 0,
      'width' => 100,
      'height' => 100,
    ] + parent::defaultConfiguration();
  }

}