<?php

declare(strict_types = 1);

namespace Drupal\Tests\drupaleasy_repositories\Traits;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\NodeType;

/**
 * Provides a helper method for creating a repository content type with fields.
 */
trait RepositoryContentTypeTrait {

  /**
   * Creates a repository content type with fields.
   *
   * @return void
   *   Return nothing.
   */
  protected function createRepositoryContentType(): void {
    NodeType::create(['type' => 'repository', 'name' => 'Repository'])->save();

    // Create Description field.
    FieldStorageConfig::create([
      'entity_type' => 'node',
      'field_name' => 'field_description',
      'type' => 'text_long',
      'cardinality' => 1,
    ])->save();
    FieldConfig::create([
      'entity_type' => 'node',
      'field_name' => 'field_description',
      'bundle' => 'repository',
      'label' => 'Description',
    ])->save();

    // Create Hash field.
    FieldStorageConfig::create([
      'entity_type' => 'node',
      'field_name' => 'field_hash',
      'type' => 'string',
      'cardinality' => 1,
    ])->save();
    FieldConfig::create([
      'entity_type' => 'node',
      'field_name' => 'field_hash',
      'bundle' => 'repository',
      'label' => 'Hash',
    ])->save();

    // Create Machine name field.
    FieldStorageConfig::create([
      'field_name' => 'field_machine_name',
      'type' => 'string',
      'entity_type' => 'node',
      'cardinality' => 1,
    ])->save();
    FieldConfig::create([
      'field_name' => 'field_machine_name',
      'entity_type' => 'node',
      'bundle' => 'repository',
      'label' => 'Machine name',
    ])->save();

    // Create Number of open issues field.
    FieldStorageConfig::create([
      'field_name' => 'field_number_of_issues',
      'type' => 'integer',
      'entity_type' => 'node',
      'cardinality' => 1,
    ])->save();
    FieldConfig::create([
      'field_name' => 'field_number_of_issues',
      'entity_type' => 'node',
      'bundle' => 'repository',
      'label' => 'Number of open issues',
    ])->save();

    // Create Source field.
    FieldStorageConfig::create([
      'field_name' => 'field_source',
      'type' => 'string',
      'entity_type' => 'node',
      'cardinality' => 1,
    ])->save();
    FieldConfig::create([
      'field_name' => 'field_source',
      'entity_type' => 'node',
      'bundle' => 'repository',
      'label' => 'Source',
    ])->save();

    // Create URL field.
    FieldStorageConfig::create([
      'field_name' => 'field_url',
      'type' => 'link',
      'entity_type' => 'node',
      'cardinality' => 1,
    ])->save();
    FieldConfig::create([
      'field_name' => 'field_url',
      'entity_type' => 'node',
      'bundle' => 'repository',
      'label' => 'URL',
    ])->save();

    // Create the Repository URL field on the user entity.
    FieldStorageConfig::create([
      'entity_type' => 'user',
      'field_name' => 'field_repository_url',
      'type' => 'link',
      'cardinality' => -1,
    ])->save();
    FieldConfig::create([
      'entity_type' => 'user',
      'field_name' => 'field_repository_url',
      'bundle' => 'user',
      'label' => 'Repository URL',
    ])->save();
  }

}
