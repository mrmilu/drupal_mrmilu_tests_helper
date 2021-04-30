<?php

namespace Drupal\Tests\mrmilu_tests_helper\Unit;


use Drupal\Component\Serialization\Json;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;

abstract class MrMiluUnitTestBase extends UnitTestCase {

  protected $languageManager;
  protected $languages;
  protected $stringTranslation;
  protected $entityTypeManager;
  protected $entityStorages;
  protected $mockedEntities;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Mock language manager
    $this->languageManager = $this->createMock('\Drupal\Core\Language\LanguageManagerInterface');

    // Add english by default
    $english = $this->mockLanguage('en');
    $this->languageManager->expects($this->any())
      ->method('getCurrentLanguage')
      ->willReturn($english);

    $this->languageManager->expects($this->any())
      ->method('getLanguage')
      ->willReturnCallback(function ($langcode) {
          return $this->languages[$langcode];
        });

    $this->stringTranslation = $this->getStringTranslationStub();

    // Mock entityTypeManager
    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->entityTypeManager->expects($this->any())
      ->method('getStorage')
      ->willReturnCallback(function ($entityType) {
          return $this->entityStorages[$entityType];
        });

    // Build container
    $container = new ContainerBuilder();
    $container->set('language_manager', $this->languageManager);
    $container->set('string_translation', $this->stringTranslation);
    $container->set('entity_type.manager', $this->entityTypeManager);

    \Drupal::setContainer($container);

  }

  /**
   * @usage $english = $this->mockLanguage('en');
   *
   * @param $langcode
   * The langcode of the language to mock
   *
   * @return \Drupal\Core\Language\LanguageInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected function mockLanguage($langcode) {
    $language = $this->createMock('\Drupal\Core\Language\LanguageInterface');
    $language->expects($this->any())
      ->method('getId')
      ->willReturn($langcode);
    $this->languages[$langcode] = $language;
    return $language;
  }

  /**
   * @usage $node = $this->mockNode(1, 'article', 'My first article', TRUE)
   *
   * @param $id
   * @param $type
   * @param $title
   * @param bool $status
   *
   * @return \Drupal\node\NodeInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected function mockNode($id, $type, $title, $status = TRUE) {
    // First of all, mock nodeStorage if not yet mocked
    if (empty($this->entityStorages['node'])) {
      $nodeStorage = $this->createMock('Drupal\Core\Entity\EntityStorageInterface');
      // Allow this node to be loaded
      $nodeStorage->expects($this->any())
        ->method('load')
        ->willReturnCallback(function ($id) {
          if (!empty($this->mockedEntities['node'][$id])) {
            return $this->mockedEntities['node'][$id];
          }
          return NULL;
        });
      $this->entityStorages['node'] = $nodeStorage;
    }

    $node = $this->createMock('\Drupal\node\NodeInterface');
    // Mock $node->id();
    $node->expects($this->any())
      ->method('id')
      ->willReturn($id);
    // Mock $node->get('status') and $node->isPublished()
    $node->expects($this->any())
      ->method('get')
      ->with('status')
      ->willReturn((object) ['value' => $status]);
    $node->expects($this->any())
      ->method('isPublished')
      ->willReturn($status);
    // Mock $node->bundle()
    $node->expects($this->any())
      ->method('bundle')
      ->willReturn($type);
    // Mock $node->label()
    $node->expects($this->any())
      ->method('label')
      ->willReturn($title);

    $this->mockedEntities['node'][$id] = $node;
    return $node;
  }

  /**
   * @usage $user = $this->mockUser(1, 'example@test.com');
   *
   * @param $id
   * @param $email
   *
   * @return \Drupal\user\UserInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected function mockUser($id, $email) {
    // First of all, mock nodeStorage if not yet mocked
    if (empty($this->entityStorages['user'])) {
      $userStorage = $this->createMock('Drupal\Core\Entity\EntityStorageInterface');
      // Allow this node to be loaded
      $userStorage->expects($this->any())
        ->method('load')
        ->willReturnCallback(function ($id) {
          if (!empty($this->mockedEntities['user'][$id])) {
            return $this->mockedEntities['user'][$id];
          }
          return NULL;
        });
      $this->entityStorages['user'] = $userStorage;
    }
    $node = $this->createMock('\Drupal\user\UserInterface');
    // Mock $user->id()
    $node->expects($this->any())
      ->method('id')
      ->willReturn($id);
    // Mock $user->getEmail()
    $node->expects($this->any())
      ->method('getEmail')
      ->willReturn($email);

    $this->mockedEntities['user'][$id] = $node;
    return $node;
  }


  /**
   * Returns a request object with data in body. Useful to avoid guzzle requests (not allowed in unit tests).
   * This Request object could be passed as argument for a Controller method
   *
   * @usage
   * $data = ['name' => 'Jose', 'surname' => 'López'];
   * $request = $this->getRequestObjectFromData($data);
   *
   * @param array $data
   * Data to be encoded as json and used in body request
   *
   * @return Request
   */
  public function getRequestObjectFromData($data = []) {
    $jsonData = Json::encode($data);
    return new Request([], [], [], [], [], [], $jsonData);
  }

  /**
   * Asserts that a response returns a 400 HTTP status code and contains an error key.
   * Useful when testing an API.
   * Response data array must be like:
   * [
   *   'error:some-error:as-many-parts-as_you_want' : "Error description returned in position 0"
   * ]
   *
   * @usage $this->assertBadResponseWithErrorKey($response, 'error:some-error:as-many-parts-as_you_want', 'position 0');
   *
   * @param $response
   * @param $errorKey
   * @param null $errorContainsText
   * Assert that error description contains this text.
   */
  protected function assertBadResponseWithErrorKey($response, $errorKey, $errorContainsText = NULL) {
    $this->assertEquals(400, $response->getStatusCode());
    $decodedResponse = Json::decode($response->getContent());
    $this->assertNotEmpty($decodedResponse);
    $this->assertArrayHasKey($errorKey, $decodedResponse, sprintf('Response has not "%s" error key. First key of response is "%s" with message "%s"', $errorKey, array_key_first($decodedResponse), reset($decodedResponse)));
    if ($errorContainsText) {
      $this->assertStringContainsString($errorContainsText, $decodedResponse[$errorKey]);
    }
  }

}
