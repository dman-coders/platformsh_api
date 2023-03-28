<?php

namespace Drupal\platformsh_api\Entity;

use Platformsh\Client\Model\ApiResourceBase;

// Custom behavior here.
$field_keys = [
  'user' => 'owner',
  'organization' => 'organization_id',
];
$reference_keys = [
  'user' => 'owner',
  'organization' => 'organization_id',
];

/**
 * Defines the Project node entity.
 */
class Project extends ApiResource {

  protected array $field_keys = [
    'plan',
    'default_domain',
    'region',
    'namespace',
  ];

  protected array $reference_keys = [
    'user' => 'owner',
    'organization' => 'organization_id',
  ];

  protected string $title_key = 'title';

  /**
   * @param $remoteEntityID
   *
   * @return false|\Platformsh\Client\Model\Project
   */
  public function getResource($remoteEntityID): bool|ApiResourceBase {
    return $this->getApiClient()->getProject($remoteEntityID);
  }

  /**
   * There is something unexpected in the return data for a Project.
   *
   * If the owner_info:type = organization
   * then the owner ID refers to an organization ID, not a user.
   * Which means the 'owner' is ...
   * https://api.platform.sh/docs/#tag/Project/operation/get-projects
   * OK, `owner` is now deprecated (but still works sometimes)
   *
   * version old:
   * "owner": "c11a1480-894c-4363-897a-52549ea0e286",
   * "organization": "01GSXM6C326HKKNCV8Z0C0Z7WY"
   *
   * version newer:
   * "owner": "94d2a9e5-c20c-45fc-bffd-50f738c13459", # <- an org GUID
   * "owner_info": {"type": "organization"},
   * "organization_id": "01FF4NDBVSTHNZSTTWQVPBMMD8" # <- visible org slug
   *
   *
   * I see both 'organization` and `organization_id`
   *
   * @param $raw_data
   *
   * @return mixed
   */
  protected function alterData($raw_data): mixed {
    if (isset($raw_data['owner_info'])) {
      if ($raw_data['owner_info']['type'] == 'organization') {
        // This slightly changes our schema def.
        // The 'owner' is of type 'organization', there is no ref to a 'user'
        $this->reference_keys = [
          'organization' => 'owner',
        ];
      }
    }
    return $raw_data;
  }

}
