<?php

declare(strict_types=1);

namespace Drupal\Tests\neo_loader\Unit;

use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Routing\AdminContext;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\neo_loader\LoaderManagerInterface;
use Drupal\neo_loader\Settings\LoaderSettings;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Covers what the settings plugin declares it needs to be constructed.
 *
 * The constructor once declared its last three dependencies optional and then
 * dereferenced one of them a line later, which did not prevent an error — it
 * moved a type error that named the parameter and the caller into a null
 * dereference that named neither. Nothing was ever optional: create() passes
 * every service, the settings manager builds every settings plugin through the
 * container factory, and the class is final.
 *
 * The first test is structural rather than behavioural, and is the one that
 * matters most for recurrence: the nullable spelling arrived as three silent
 * `= NULL` defaults, so reflection is what stops it arriving that way again.
 */
#[Group('neo_loader')]
final class LoaderSettingsDependencyContractTest extends UnitTestCase {

  /**
   * Tests that no constructor parameter is optional or a request stack.
   */
  public function testDeclaresNoParameterOptionalAndNoRequestStackAmongThem(): void {
    $constructor = (new \ReflectionClass(LoaderSettings::class))->getConstructor();
    $this->assertNotNull($constructor, 'The settings plugin declares no constructor.');

    foreach ($constructor->getParameters() as $parameter) {
      $this->assertFalse(
        $parameter->isOptional(),
        'The constructor declares $' . $parameter->getName() . ' optional.',
      );
      // An untyped parameter always allows NULL, so only a declared type can
      // carry the nullable spelling this pins against.
      if ($parameter->hasType()) {
        $this->assertFalse(
          $parameter->allowsNull(),
          'The constructor lets $' . $parameter->getName() . ' be NULL.',
        );
      }
      $this->assertNotContains(
        RequestStack::class,
        $this->typeNames($parameter),
        'The constructor still asks for a request stack as $' . $parameter->getName() . '.',
      );
    }
  }

  /**
   * Tests that the plugin holds no resolved request.
   */
  public function testHoldsNoResolvedRequest(): void {
    $this->assertFalse(
      property_exists(LoaderSettings::class, 'request'),
      'The settings plugin still holds a resolved request nothing reads.',
    );
  }

  /**
   * Tests that create() builds it from a container with only its services.
   */
  public function testIsBuiltByCreateFromContainerHoldingOnlyItsServices(): void {
    $services = [
      'messenger' => $this->createMock(MessengerInterface::class),
      'form_builder' => $this->createMock(FormBuilderInterface::class),
      'plugin.manager.neo_loader' => $this->createMock(LoaderManagerInterface::class),
      'router.admin_context' => new AdminContext(
        $this->createMock(RouteMatchInterface::class),
      ),
    ];
    $container = $this->createMock(ContainerInterface::class);
    $container->method('get')->willReturnCallback(
      static function (string $id) use ($services) {
        if (!isset($services[$id])) {
          throw new ServiceNotFoundException($id);
        }
        return $services[$id];
      },
    );

    $settings = LoaderSettings::create(
      $container,
      [
        'config' => ['show_admin_paths' => FALSE],
        'variation' => [],
        'variation_id' => '',
      ],
      'neo_loader',
      ['configuration' => []],
    );

    $this->assertInstanceOf(LoaderSettings::class, $settings);
    $this->assertIsBool(
      $settings->routeIsApplicable(),
      'The plugin the container built cannot answer route applicability.',
    );
  }

  /**
   * Returns the class names a parameter's type declaration names.
   *
   * @param \ReflectionParameter $parameter
   *   The parameter.
   *
   * @return string[]
   *   The type names, without a leading separator.
   */
  private function typeNames(\ReflectionParameter $parameter): array {
    $type = $parameter->getType();
    if ($type === NULL) {
      return [];
    }
    $types = $type instanceof \ReflectionNamedType ? [$type] : $type->getTypes();

    return array_map(
      static fn ($type) => ltrim((string) $type, '?\\'),
      $types,
    );
  }

}
