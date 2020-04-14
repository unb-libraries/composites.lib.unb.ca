<?php

/**
 * @file
 * Contains comp_install.install.
 */

use Drupal\node\Entity\Node;

// Create default nodes.
// static pages.
// Introduction.
$title = 'Introduction';
$body = file_get_contents('modules/custom/comp_install/data/intro.html');
$path = '/introduction';
new_static_page($title, $body, $path);

// About.
$title = 'About';
$body = file_get_contents('modules/custom/comp_install/data/about.html');
$path = '/about';
new_static_page($title, $body, $path);

// Acknowledgements.
$title = 'Acknowledgements';
$body = file_get_contents('modules/custom/comp_install/data/ack.html');
$path = '/acknowledgements';
new_static_page($title, $body, $path);

/**
 * Creates a static page with internal URL alias.
 */
function new_static_page($title, $body, $path) {
  $node = Node::create([
    'type' => 'static_page',
    'title' => $title,
    'body' => [
      'format' => 'full_html',
      'value' => $body,
    ],
    'path' => [
      'alias' => $path,
    ],
  ]);

  $node->save();
}
