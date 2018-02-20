<?php

namespace Drupal\social_profile_fields\Form;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\field\FieldConfigStorage;
use Drupal\profile\ProfileStorage;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides confirmation form for resetting a vocabulary to alphabetical order.
 */
class SocialProfileFieldsFlushForm extends ConfirmFormBase {


  /**
   * @var \Drupal\profile\ProfileStorage
   */
  protected $profileStorage;

  /**
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;


  /**
   * @var \Drupal\field\FieldConfigStorage
   */
  protected $fieldStorage;


  /**
   * Constructs a new ExportUserConfirm.
   *
   * @param \Drupal\profile\ProfileStorage $profiel_storage
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   * @param \Drupal\field\FieldConfigStorage $field_storage
   */
  public function __construct(ProfileStorage $profiel_storage, ConfigFactory $config_factory, FieldConfigStorage $field_storage) {
    $this->profileStorage = $profiel_storage;
    $this->configFactory = $config_factory;
    $this->fieldStorage = $field_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager')->getStorage('profile'),
      $container->get('config.factory'),
      $container->get('entity.manager')->getStorage('field_config')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'social_profile_fields_flush_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Flush profile.');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('social_profile_fields.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Yes, continue');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $pids = \Drupal::entityQuery('profile')
      ->condition('type', 'profile')
      ->execute();

    $fields = $this->getUnselectedFields();

    $batch = array(
      'title' => t('Flushing profiles.'),
      'operations' => array(
        array(
          '\Drupal\social_profile_fields\SocialProfileFieldsBatch::performFlush',
          array($pids, $fields)
        ),
      ),
      'finished' => '\Drupal\social_profile_fields\SocialProfileFieldsBatch::performFlushFinishedCallback',
    );
    batch_set($batch);

    $form_state->setRedirectUrl($this->getCancelUrl());
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {

    $fields = $this->getUnselectedFields();
    $field_string = implode(', ', $fields);

    return $this->t('WARNING: Flushing profile data will permanently remove ALL data from the following fields from the database: @fields This cannot be undone. Are you sure you want to contine?', ["@fields" => $field_string]);
  }


  /**
   * @return array
   */
  protected function getUnselectedFields() {
    $profile_fields = $this->fieldStorage->loadByProperties(['entity_type' => 'profile', 'bundle' => 'profile']);
    $settings = $this->configFactory->get('social_profile_fields.settings');
    $empty = [];

    /** @var \Drupal\field\Entity\FieldConfig $value */
    foreach ($profile_fields as $key => $value) {
      $sval = $settings->get(str_replace('.', '_', $key));

      if (isset($sval) && $sval == FALSE) {
        $empty[] = $value->getName();
      }
    }
    return $empty;
  }

}
