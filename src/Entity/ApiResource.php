<?php

namespace Drupal\platformsh_api\Entity;

use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\platformsh_api\ApiService;
use Exception;
use InvalidArgumentException;
use Platformsh\Client\Model\ApiResourceBase;
use Platformsh\Client\PlatformClient;

/**
 * Defines the generic resource that may be returned by an API lookup.
 *
 * @see \Platformsh\Client\Model\ApiResourceBase
 *
 */
abstract class ApiResource extends Node {

  use MessengerTrait;

  // Static properties that each subclass should define to say what is special to it.
  // Basically reflects the content data model that we care about.

  /**
   * @var string[]
   * Fields that get copied directly from the remote object into local field
   *   storage as-is
   */
  protected array $field_keys = [];

  /**
   * @var string[]
   * Fields that are GUID references, and need to be set to refer to other
   *   entities
   */
  protected array $reference_keys = [];

  /** @var string
   * What is the key that should be used as the title of this object?
   */
  protected string $title_key = 'title';


  // Active properties.
  protected ApiService $api_service;

  protected PlatformClient $api_client;

  protected string $remoteEntityID;


  /**
   * Fetch the actual data using the PHP API.
   *
   * This is expected to be overridden by the subclass to match the
   * \Platformsh\Client methods.
   *
   * @return ApiResourceBase|false
   * @see \Platformsh\Client\Model\ApiResourceBase::get()
   */
  abstract public function getResource($remoteEntityID): bool|ApiResourceBase;

  /**
   * Add logic specific to the content types.
   *
   * This is expected to be overridden by the subclass if extra
   * if/then logic is needed.
   *
   * @param $raw_data
   *
   * @return mixed
   */
  protected function alterData($raw_data) {
    return $raw_data;
  }

  /**
   * Utility
   *
   * @return \Platformsh\Client\PlatformClient
   */
  protected function getApiClient() {
    $this->api_service = \Drupal::service('platformsh_api.fetcher');
    $this->api_client = $this->api_service->getApiClient();
    return $this->api_client;
  }


  /**
   * Generic resource fetcher.
   * Should be able to fetch any model from the API.
   * Anything that is common to most models can be implemented here.
   * Anything that is unique to a model can be in its own corresponding class.
   *
   * @return bool success
   */
  public function refreshFromAPI(): bool {

    // Everything hinges on the field_id, which must be set by now.
    $remoteEntityID = $this->get('field_id')->value;
    if (empty($remoteEntityID)) {
      $this->messenger()->addError("No valid remote ID");
      return FALSE;
    }

    /** @var \Platformsh\Client\Model\ApiResourceBase $resource
     *   either a project or a user or whatever.
     */
    try {
      // Calling the API may fail for many reasons. Stay paranoid.
      $resource = $this->getResource($remoteEntityID);
      // The API may return without error, but still not have data - if project is invalid.
      if (empty($resource)) {
        $this->messenger()
          ->addError("API call returned empty. Probably an invalid entity ID. Update failed.");
        return FALSE;
      }
    } catch (Exception $e) {
      $this->messenger()->addError("API call failed: " . $e->getMessage());
      return FALSE;
    }

    $viable_fields = $this->getFields();
    $raw_dump = $resource->getData();
    // Recording whether a change actually happened is tedious,
    // but it will keep history much cleaner
    // as we can skip making updates with no changes.
    // Keep a detailed changelog in $updated.
    $updated = [];

    // Excise the links for brevity, and log the diagnostics.
    unset($raw_dump['_links']);
    $json_dump = json_encode($raw_dump, JSON_PRETTY_PRINT);
    $this->messenger()->addStatus($json_dump);

    try {
      if ($this->getTitle() != $resource->getProperty($this->title_key)) {
        $this->setTitle($resource->getProperty($this->title_key));
        $updated['title'] = true;
      }
    } catch (Exception $e) {
      // This should never happen, but it did, so...
      $this->messenger()
        ->addError("Resource doesn't have a " . $this->title_key . " property");
      $this->messenger()->addError(print_r($resource->getPropertyNames(), 1));
    }

    // Store the raw data for review.
    if (in_array('field_data', array_keys($viable_fields))) {
      if ($this->get('field_data') != $json_dump) {
        $this->set('field_data', $json_dump);
        $updated['field_data'] = true;
      }
    }

    // There may be special cases that the class needs to deal with.
    $this->alterData($raw_dump);

    // Now set the values we extracted
    foreach ($this->field_keys as $key_name) {
      $field_name = 'field_' . $key_name;
      if (
        isset($raw_dump[$key_name])
        &&
        in_array($field_name, array_keys($viable_fields))
        &&
        ($this->get($field_name) != $resource->getData()[$key_name])
      ) {
        $this->set($field_name, $resource->getData()[$key_name]);
        $updated[$field_name] = true;
      }
      else {
        # field miss-match. Either our the expected data did not come back,
        # or our content type doesn't have a place to store it.
        # This may happen as API and content model evolves.
      }
    }

    $updated += $this->autocreateTargetEntities($raw_dump);

    #$node->set('field_' . 'updated_at' , $response->getData()['updated_at']);

    // Take care, as this action may be called on hook_entity_presave. Avoid a loop.
    if (!empty($updated) && !$this->isNew()) {
      try {
        $this->save();
      } catch (EntityStorageException $e) {
        $this->messenger()->addError("Failed to save node " . $e->getMessage());
      }
    }
    else {
      $this->messenger()->addStatus("No extra save necessary");
    }
    return TRUE;
  }

  /**
   * For each external entity that a Project refers to,
   * Ensure the named target exists, creating it if necessary.
   *
   * We have a list of `reference_keys`, eg 'owner'.
   * If this resources data contains avalue for `owner`, this will be a GUID.
   * We need to see if we already recognise that GUID, and if so, link to it.
   * If not, create a placeholder for it, and link to that.
   *
   * @var \Drupal\node\NodeInterface $node
   *
   * @param array $raw_data
   *
   * @return array A list of what, if anything, was updated.
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  function autocreateTargetEntities(array $raw_data): array {
    $updated = [];
    // References need extra help.
    foreach ($this->reference_keys as $key_type => $key_name) {
      /** @var \Drupal\Core\Field\FieldItemListInterface $value */
      $target_guid = $raw_data[$key_name];
      if (empty($target_guid)) { continue;  }

      // Fetch or create the target first
      $target = $this->api_service::getEntityById($target_guid);

      if (empty($target)) {
        // Attempt auto create.
        $entity_type_id = 'node';
        $target_data = [
          'bundle' => $key_type, # it's aliased as 'type'. yay.
          'type' => $key_type,
          'title' => $target_guid,
          'field_id' => $target_guid,
        ];
        $target = $this->autocreateTargetEntity($entity_type_id, $target_data);
      }

      if (!empty($target)) {
        $target_info = ['target_id' => $target->id()];
        if ($this->get('field_' . $key_name) != $target->id()) {
          $updated['field_' . $key_name] = true;
          // TODO: check this logic
        }
        $this->set('field_' . $key_name, $target_info);
      }
      else {
        throw new InvalidArgumentException("Could not find or auto create target entity " . $target_guid->getString());
      }
    }
    return $updated;
  }

  /**
   * Create a new node entity of the requested type.
   *
   * @param string $entity_type_id
   * @param array $values
   *
   * @return \Drupal\Core\Entity\EntityInterface
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  private function autocreateTargetEntity(string $entity_type_id, array $values) {
    $this->messenger()
      ->addStatus("Auto-creating an ${values['bundle']} called ${values['field_id']}");
    $entity = \Drupal::entityTypeManager()
      ->getStorage($entity_type_id)
      ->create($values);
    $entity->save();
    return $entity;
  }


}
