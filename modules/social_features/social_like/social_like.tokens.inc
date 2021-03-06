<?php

/**
 * @file
 * Builds placeholder replacement tokens for Social Like module.
 */

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\votingapi\Entity\Vote;

/**
 * Implements hook_token_info().
 */
function social_like_token_info() {

  $type = [
    'name' => t('Social Like'),
    'description' => t('Tokens from the social like module.'),
  ];

  $social_like['liked_entity'] = [
    'name' => t('URL of the liked entity.'),
    'description' => t('URL of the entity the like was created for'),
  ];

  $social_like['liked_content_type'] = [
    'name' => t('The liked content type.'),
    'description' => t('The type of the content that was liked'),
  ];

  return [
    'types' => ['social_like' => $type],
    'tokens' => [
      'social_like' => $social_like,
    ],
  ];
}

/**
 * Implements hook_tokens().
 */
function social_like_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $replacements = [];
  if ($type == 'social_like' && !empty($data['message'])) {
    /** @var \Drupal\message\Entity\Message $message */
    $message = $data['message'];
    /** @var \Drupal\votingapi\Entity\Vote $vote */
    if ($vote = Vote::load($message->field_message_related_object->target_id)) {
      foreach ($tokens as $name => $original) {
        switch ($name) {

          case 'liked_entity':
            $storage = \Drupal::entityTypeManager()->getStorage($vote->getVotedEntityType());
            $entity = $storage->load($vote->getVotedEntityId());
            $url_options = ['absolute' => TRUE];
            $replacements[$original] = $entity->toUrl('canonical', $url_options)->toString();
            break;

          case 'liked_content_type':
            $storage = \Drupal::entityTypeManager()->getStorage($vote->getVotedEntityType());
            $entity = $storage->load($vote->getVotedEntityId());
            $content_type = $entity->getEntityTypeId();
            // Check if the content type is node.
            if ($content_type === 'node') {
              // Then get the bundle name.
              $content_type = Unicode::strtolower(\Drupal::entityTypeManager()
                ->getStorage('node_type')
                ->load($entity->bundle())
                ->label());
            }
            if ($content_type === 'post' || $content_type === 'photo' || $content_type === 'comment') {
              $content_type = Unicode::strtolower($entity->getEntityType()->getLabel());
            }

            $replacements[$original] = $content_type;
            break;

        }
      }
    }
  }
  return $replacements;
}
