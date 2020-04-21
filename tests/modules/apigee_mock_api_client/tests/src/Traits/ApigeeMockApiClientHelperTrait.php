<?php

/*
 * Copyright 2020 Google Inc.
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License version 2 as published by the
 * Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
 * or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public
 * License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc., 51
 * Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 */

namespace Drupal\Tests\apigee_mock_api_client\Traits;

use Apigee\Edge\Api\Management\Entity\App;
use Apigee\Edge\Api\Management\Entity\Company;
use Apigee\Edge\Api\Management\Entity\Organization;
use Apigee\MockClient\Generator\ApigeeSdkEntitySource;
use Drupal\apigee_edge\Entity\Developer;
use Drupal\apigee_edge\Entity\DeveloperApp;
use Drupal\apigee_edge\Entity\DeveloperAppInterface;
use Drupal\apigee_edge\Entity\DeveloperInterface;
use Drupal\apigee_edge_teams\Entity\Team;
use Drupal\apigee_edge_teams\Entity\TeamInterface;
use Drupal\Tests\apigee_edge\Traits\ApigeeEdgeUtilTestTrait;
use Drupal\user\UserInterface;
use Http\Message\RequestMatcher\RequestMatcher;

/**
 * Helper functions working with Apigee tests.
 */
trait ApigeeMockApiClientHelperTrait {

  use ApigeeEdgeUtilTestTrait;

  /**
   * The SDK connector service.
   *
   * @var \Drupal\apigee_edge\SDKConnectorInterface
   */
  protected $sdkConnector;

  /**
   * The mock handler stack is responsible for serving queued api responses.
   *
   * @var \Drupal\apigee_mock_api_client\MockHandlerStack
   */
  protected $stack;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The mock response factory service.
   *
   * @var \Apigee\MockClient\ResponseFactoryInterface
   */
  protected $mockResponseFactory;

  /**
   * If integration (real API connection) is enabled.
   *
   * @var bool
   */
  protected $integration_enabled;

  /**
   * Setup.
   */
  protected function apigeeTestHelperSetup() {
    $this->apigeeTestPropertiesSetup();
    $this->initAuth();
  }

  /**
   * Setup.
   */
  protected function apigeeTestPropertiesSetup() {
    $this->stack = $this->container->get('apigee_mock_api_client.mock_http_handler_stack');
    $this->sdkConnector = $this->container->get('apigee_edge.sdk_connector');
    $this->entityTypeManager = $this->container->get('entity_type.manager');
    $this->mockResponseFactory = $this->container->get('apigee_mock_api_client.response_factory');
    $this->integration_enabled = getenv('APIGEE_INTEGRATION_ENABLE');
  }

  /**
   * Initialize SDK connector.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function initAuth() {
    $this->createTestKey();
    $this->restoreKey();
  }

  /**
   * Add matched org response.
   *
   * @param string $organizationName
   *   The organization name, or empty to use the default from the credentials.
   */
  protected function addOrganizationMatchedResponse($organizationName = '') {
    $organizationName = $organizationName ?: $this->sdkConnector->getOrganization();

    $organization = new Organization(['name' => $organizationName]);
    $this->stack->on(
      new RequestMatcher("/v1/organizations/{$organization->id()}$", NULL, [
        'GET',
      ]),
      $this->mockResponseFactory->generateResponse(new ApigeeSdkEntitySource($organization))
    );
  }

  /**
   * Add matched developer response.
   *
   * @param \Drupal\user\UserInterface $developer
   *   The developer user to get properties from.
   */
  protected function addDeveloperMatchedResponse(UserInterface $developer) {
    $organization = $this->sdkConnector->getOrganization();
    $dev = new Developer([
      'email' => $developer->getEmail(),
      'developerId' => $developer->uuid(),
      'firstName' => $developer->first_name->value,
      'lastName' => $developer->last_name->value,
      'userName' => $developer->getAccountName(),
      'organizationName' => $organization,
    ]);

    $this->stack->on(
      new RequestMatcher("/v1/organizations/{$organization}/developers/{$developer->getEmail()}$", NULL, [
        'GET',
      ]),
      $this->mockResponseFactory->generateResponse(new ApigeeSdkEntitySource($developer))
    );
  }

  /**
   * Queues up a mock developer response.
   *
   * @param \Drupal\user\UserInterface $developer
   *   The developer user to get properties from.
   * @param string|null $response_code
   *   Add a response code to override the default.
   * @param array $context
   *   Extra keys to pass to the template.
   */
  protected function queueDeveloperResponse(UserInterface $developer, $response_code = NULL, array $context = []) {
    if (!empty($response_code)) {
      $context['status_code'] = $response_code;
    }

    $context['developer'] = $developer;
    $context['org_name'] = $this->sdkConnector->getOrganization();

    $this->stack->queueMockResponse(['get_developer' => $context]);
  }

  /**
   * Queues up a mock developer response.
   *
   * @param \Drupal\apigee_edge\Entity\DeveloperInterface $developer
   *   The developer user to get properties from.
   * @param string|null $response_code
   *   Add a response code to override the default.
   *
   * @return \Drupal\user\Entity\User
   *   A user account with the same data as the created developer.
   */
  protected function queueDeveloperResponseFromDeveloper(DeveloperInterface $developer, $response_code = NULL) {
    $account = $this->entityTypeManager->getStorage('user')->create([
      'mail' => $developer->getEmail(),
      'name' => $developer->getUserName(),
      'first_name' => $developer->getFirstName(),
      'last_name' => $developer->getLastName(),
      'status' => ($developer->getStatus() == DeveloperInterface::STATUS_ACTIVE) ? 1 : 0,
    ]);

    $this->queueDeveloperResponse($account, $response_code);

    return $account;
  }

  /**
   * Queues up a mock company response.
   *
   * @param \Apigee\Edge\Api\Management\Entity\Company $company
   *   The cpmpany to get properties from.
   * @param string|null $response_code
   *   Add a response code to override the default.
   */
  protected function queueCompanyResponse(Company $company, $response_code = NULL) {
    $context = empty($response_code) ? [] : ['status_code' => $response_code];

    $context['company'] = $company;
    $context['org_name'] = $this->sdkConnector->getOrganization();

    $this->stack->queueMockResponse(['company' => $context]);
  }

  /**
   * Queues up a mock developers in a company response.
   *
   * @param array $developers
   *   An array of arrays containing developer emails and roles.
   * @param string|null $response_code
   *   Add a response code to override the default.
   */
  protected function queueDevsInCompanyResponse(array $developers, $response_code = NULL) {
    $context = empty($response_code) ? [] : ['status_code' => $response_code];

    $context['developers'] = $developers;

    $this->stack->queueMockResponse(['developers_in_company' => $context]);
  }

  /**
   * Helper to create a DeveloperApp entity.
   *
   * @return \Drupal\apigee_edge\Entity\DeveloperAppInterface
   *   A DeveloperApp entity.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function createDeveloperApp(): DeveloperAppInterface {
    /** @var \Drupal\apigee_edge\Entity\DeveloperAppInterface $entity */
    $entity = DeveloperApp::create([
      'appId' => 1,
      'name' => $this->randomMachineName(),
      'status' => App::STATUS_APPROVED,
      'displayName' => $this->randomMachineName(),
    ]);
    $entity->setOwner($this->account);
    $this->queueDeveloperAppResponse($entity);
    $entity->save();

    return $entity;
  }

  /**
   * Helper to create a Team entity.
   *
   * @return \Drupal\apigee_edge_teams\Entity\TeamInterface
   *   A Team entity.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function createTeam(): TeamInterface {
    /** @var \Drupal\apigee_edge_teams\Entity\TeamInterface $team */
    $team = Team::create([
      'name' => $this->randomMachineName(),
      'displayName' => $this->randomGenerator->name(),
    ]);
    $this->queueCompanyResponse($team->decorated());
    $this->queueDeveloperResponse($this->account);
    $team->save();

    return $team;
  }

  /**
   * Helper to add Edge entity response to stack.
   *
   * @param \Drupal\apigee_edge\Entity\DeveloperAppInterface $app
   *   The app.
   * @param int $response_code
   *   Response code, defaults to 200.
   */
  protected function queueDeveloperAppResponse(DeveloperAppInterface $app, $response_code = 200) {
    $this->stack->queueMockResponse([
      'get_developer_app' => [
        'status_code' => $response_code,
        'app' => [
          'appId' => $app->getAppId() ?: $this->randomMachineName(),
          'name' => $app->getName(),
          'status' => $app->getStatus(),
          'displayName' => $app->getDisplayName(),
          'developerId' => $app->getDeveloperId(),
        ],
      ],
    ]);
  }

  /**
   * Installs a given list of modules and rebuilds the cache.
   *
   * @param string[] $module_list
   *   An array of module names.
   *
   * @see \Drupal\Tests\toolbar\Functional\ToolbarCacheContextsTest::installExtraModules()
   */
  protected function installExtraModules(array $module_list) {
    \Drupal::service('module_installer')->install($module_list);
    // Installing modules updates the container and needs a router rebuild.
    $this->container = \Drupal::getContainer();
    $this->container->get('router.builder')->rebuildIfNeeded();
  }

}
