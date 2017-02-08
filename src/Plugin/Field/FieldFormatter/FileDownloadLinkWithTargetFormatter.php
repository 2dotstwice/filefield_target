<?php
/**
 * @file
 */

namespace Drupal\filefield_target\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Template\Attribute;
use Drupal\file_entity\Entity\FileEntity;
use Drupal\file_entity\Plugin\Field\FieldFormatter\FileDownloadLinkFormatter;

/**
 * @FieldFormatter(
 *   id = "file_download_link_with_target",
 *   label = @Translation("Download link with target"),
 *   description = @Translation("Displays a link that will force the browser to download the file, with a target set."),
 *   field_types = {
 *     "file",
 *     "image"
 *   }
 * )
 */
class FileDownloadLinkWithTargetFormatter extends FileDownloadLinkFormatter {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);

    $element['target'] = [
      '#type' => 'select',
      '#required' => TRUE,
      '#default_value' => $this->getSetting('target'),
      '#options' => [
        '_blank' => '_blank',
        '_self' => '_self',
        '_parent' => '_parent',
        '_top' => '_top',
      ],
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return FileDownloadLinkFormatter::defaultSettings() + ['target' => '_blank'];
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = array();
    // For token replace, we also want to use the parent entity of the file.
    $parent_entity = $items->getParent()->getValue();
    if (!empty($parent_entity)) {
      $parent_entity_type = $parent_entity->getEntityType()->id();
      $token_data[$parent_entity_type] = $parent_entity;
    }
    /** @var FileEntity $file */
    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $file) {
      // Prepare the text and the URL of the link.
      $mime_type = $file->getMimeType();
      $token_data['file'] = $file;
      $link_text = $this->token->replace($this->getSetting('text'), $token_data);
      // Set options as per anchor format described at
      // http://microformats.org/wiki/file-format-examples
      $url_attributes = [
        'type' => $mime_type . '; length=' . $file->getSize(),
        'target' => $this->getSetting('target'),
      ];
      $download_url = $file->downloadUrl(['attributes' => $url_attributes]);
      if ($file->access('download')) {
        $elements[$delta] = [
          '#theme' => 'file_entity_download_link',
          '#file' => $file,
          '#download_link' => Link::fromTextAndUrl($link_text, $download_url),
          '#icon' => file_icon_class($mime_type),
          '#attributes' => new Attribute(),
          '#file_size' => format_size($file->getSize()),
        ];
      }
      else {
        $elements[$delta] = [
          '#markup' => $this->getSetting('access_message'),
        ];
      }
      $this->renderer->addCacheableDependency($elements[$delta], $file);
    }

    return $elements;
  }
}