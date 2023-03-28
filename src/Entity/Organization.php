<?php

namespace Drupal\platformsh_api\Entity;

use Platformsh\Client\Model\ApiResourceBase;

/**
 * Defines the Organization node entity.
 */
class Organization extends ApiResource {

  protected array $field_keys = [];
  protected array $reference_keys = [];
  protected string $title_key = 'name';

  /**
   * @param $remoteEntityID
   *
   * #@return false|\Platformsh\Client\Model\Organization
   */
  public function getResource($remoteEntityID): bool {
    return FALSE; # Not yet available?
    #$this->getApiClient()->getOrganization($remoteEntityID);
  }

}
