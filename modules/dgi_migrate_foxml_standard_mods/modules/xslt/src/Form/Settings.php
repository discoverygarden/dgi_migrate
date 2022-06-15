<?php

namespace Drupal\dgi_migrate_foxml_standard_mods_xslt\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Admin settings form.
 */
class Settings extends ConfigFormBase {

  const CONFIG = 'dgi_migrate_foxml_standard_mods_xslt.settings';

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [static::CONFIG];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'dgi_migrate_foxml_standard_mods_xslt_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(static::CONFIG);

    $form['xslt'] = [
      '#type' => 'textarea',
      '#title' => $this->t('XSLT'),
      '#default_value' => $this->denormalize($config->get('xslt')),
    ];

    $form['allowed_remotes'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Allowed remotes'),
      '#description' => $this->t('IP/CIDR specs allowed to access the given XSLT, one per line.'),
      '#default_value' => implode("\r\n", $config->get('allowed_remotes')),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Convert from stored Unix line-endings to Windows for presentation.
   *
   * @param string|null $string $string
   *   A string with Windows line-endings, as used by text areas.
   *
   * @return string
   *   The string with Unix line-endings.
   */
  protected function denormalize(?string $string) : string {
    return $string ? str_replace("\n", "\r\n", $string) : '';
  }

  /**
   * Convert from text areas Windows line-endings to Unix.
   *
   * @param string $string
   *   A string with Windows line-endings, as used by text areas.
   *
   * @return string
   *   The string with Unix line-endings.
   */
  protected function normalize(string $string) : string {
    return str_replace("\r\n", "\n", $string);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $config = $this->config(static::CONFIG);

    $xslt = $this->normalize($form_state->getValue('xslt'));
    $xslt_textfield_changed = $xslt !== $config->get('xslt');
    if ($xslt_textfield_changed) {
      if ($xslt) {
        $dom = new \DOMDocument();
        $result = $dom->loadXML($xslt);

        if ($result) {
          $form_state->setTemporaryValue('xslt', $xslt);
        }
        else {
          $form_state->setError($form['xslt'], $this->t('The passed XSLT does not appear to be valid XML.'));
        }
      }
      else {
        $form_state->setTemporaryValue('clear_xslt', TRUE);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config(static::CONFIG);

    if ($form_state->hasTemporaryValue('clear_xslt') && $form_state->getTemporaryValue('clear_xslt')) {
      $config->clear('xslt');
    }
    elseif ($form_state->hasTemporaryValue('xslt')) {
      $config->set('xslt', $form_state->getTemporaryValue('xslt'));
    }
    $config->set('allowed_remotes', array_map('trim', explode("\r\n", $form_state->getValue('allowed_remotes'))));

    $config->save();

    parent::submitForm($form, $form_state);
  }

}
