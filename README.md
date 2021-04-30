# Mr.Milú Tests Helper
Makes developers life easier providing some helper methods and base classes for testing.

## Unit tests
Provides a MrMiluUnitTestCaseBase.php class which provides some interesting functionality.

To develop a unit test you need to create a file in the ``modules/custom/my_module/tests/src/Unit`` folder. Classname should end in Test.php (i.e. ``MyExampleClassTest.php`)

```PHP
<?php

use Drupal\Tests\mrmilu_tests_helper\Unit\MrMiluUnitTestBase;

class MyUnitClassTest extends MrMiluUnitTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Prepare common data to use along all your tests. i.e.:
    $container = \Drupal::getContainer();
    $container->set('my_custom_service', $this->myCustomService);
    \Drupal::setContainer($container);
  }
}
```

### How to mock a service?
```php
<?php

use Drupal\Tests\mrmilu_tests_helper\Unit\MrMiluUnitTestBase;

class MyUnitClassTest extends MrMiluUnitTestBase {

  // Declare your services properties
  protected $myCustomService;
  protected $anotherService;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Mock your services
    $this->myCustomService = $this->createMock('\Drupal\MyModule\Service\MyServiceClassOrInterface');
    // Another option is to build manually the mock
    $this->anotherService = $this->getMockBuilder('\Drupal\MyModule\Service\AnotherService')
      ->onlyMethods(['myMethod', 'anotherMethod']) // This will mock only these methods. Other methods of the class will be executed fully
      ->getMock();

    // Add your services to the container to allow Drupal to use them
    $container = \Drupal::getContainer();
    $container->set('my_custom_service', $this->myCustomService);
    $container->set('another_service', $this->anotherService);
    \Drupal::setContainer(\$container);
  }
}
````
