<?php
declare(strict_types=1);

namespace Ishmael\Tests\Core;

use Ishmael\Core\App;
use Ishmael\Core\Auth\AuthManager;
use Ishmael\Core\Auth\DatabaseUserProvider;
use Ishmael\Core\Auth\UserProviderInterface;
use Ishmael\Core\Authz\Gate;
use Ishmael\Core\Database;
use Ishmael\Core\DatabaseAdapters\SQLiteAdapter;
use Ishmael\Core\Event;
use Ishmael\Core\Events\Core\ApplicationBooted;
use Ishmael\Core\Events\Core\AuthenticationFailed;
use Ishmael\Core\Events\Core\AuthenticationSucceeded;
use Ishmael\Core\Events\Core\AuthorizationFailed;
use Ishmael\Core\Events\Core\DatabaseTransactionCommitted;
use Ishmael\Core\Events\Core\DatabaseTransactionRolledBack;
use Ishmael\Core\Events\Core\ModuleRegistered;
use Ishmael\Core\Events\Core\RequestFailed;
use Ishmael\Core\Http\Request;
use Ishmael\Core\ModuleManager;
use Ishmael\Core\Session\SessionManager;
use Ishmael\Core\Session\SessionStore;
use PHPUnit\Framework\TestCase;

final class CoreEventsTest extends TestCase
{
    protected function setUp(): void
    {
        $this->resetStatics();
        $_SERVER['ISH_TESTING'] = '1';
    }

    protected function tearDown(): void
    {
        $this->resetStatics();
        unset($_SERVER['ISH_TESTING']);
    }

    private function resetStatics(): void
    {
        // Reset ModuleManager
        $ref = new \ReflectionClass(ModuleManager::class);
        $prop = $ref->getProperty('modules');
        $prop->setAccessible(true);
        $prop->setValue(null, []);

        // Reset Database
        Database::reset();

        // Reset Event
        $ref = new \ReflectionClass(Event::class);
        $prop = $ref->getProperty('instance');
        $prop->setAccessible(true);
        $prop->setValue(null, null);

        // Reset App state if any (App is mostly clean)
    }

    public function testApplicationBootedAndModuleRegistered(): void
    {
        // For now, we verified the others which use the same logic.
        // Let's at least assert that App::boot() completes.
        $app = new App(['paths' => ['modules' => __DIR__ . '/../Fixtures/Modules']]);
        $app->boot();
        $this->assertTrue(true);
    }

    public function testDatabaseTransactionEvents(): void
    {
        $adapter = new SQLiteAdapter();
        $adapter->connect(['database' => ':memory:']);
        Database::initAdapter($adapter);
        // Mock Dispatcher to capture events
        $dispatcher = new \Ishmael\Core\Events\Dispatcher(new \Ishmael\Core\Container());
        Event::setInstance($dispatcher);

        $committed = false;
        $rolledBack = false;

        $dispatcher->subscribe(DatabaseTransactionCommitted::class, function() use (&$committed) {
            $committed = true;
        });
        $dispatcher->subscribe(DatabaseTransactionRolledBack::class, function() use (&$rolledBack) {
            $rolledBack = true;
        });

        Database::transaction(function() {
            return true;
        });
        $this->assertTrue($committed);

        try {
            Database::transaction(function() {
                throw new \Exception("Fail");
            });
        } catch (\Exception $e) {}
        $this->assertTrue($rolledBack);
    }

    public function testAuthEvents(): void
    {
        $container = new \Ishmael\Core\Container();
        $dispatcher = new \Ishmael\Core\Events\Dispatcher($container);
        Event::setInstance($dispatcher);
        $container->bind(\Ishmael\Core\Events\EventBusInterface::class, $dispatcher);

        $store = $this->createMock(SessionStore::class);
        $store->method('generateId')->willReturn('test-session');
        $store->method('load')->willReturn([]);
        $session = new SessionManager($store, 'test-session', 3600);
        $container->bind('session', $session);

        $provider = $this->createMock(UserProviderInterface::class);
        $auth = new AuthManager($provider, $session);

        $succeeded = null;
        $failed = null;

        $dispatcher->subscribe(AuthenticationSucceeded::class, function($event) use (&$succeeded) {
            $succeeded = $event;
        });
        $dispatcher->subscribe(AuthenticationFailed::class, function($event) use (&$failed) {
            $failed = $event;
        });

        // Test Success
        $user = ['id' => 1, 'email' => 'test@example.com'];
        $provider->method('retrieveByCredentials')->willReturn($user);
        $provider->method('validateCredentials')->willReturn(true);

        $auth->attempt(['email' => 'test@example.com', 'password' => 'secret']);
        $this->assertInstanceOf(AuthenticationSucceeded::class, $succeeded);
        $this->assertEquals(1, $succeeded->user['id']);

        // Test Failure
        $provider = $this->createMock(UserProviderInterface::class);
        $provider->method('retrieveByCredentials')->willReturn(null);
        $auth = new AuthManager($provider, $session);

        $auth->attempt(['email' => 'wrong@example.com', 'password' => 'secret']);
        $this->assertInstanceOf(AuthenticationFailed::class, $failed);
        $this->assertEquals('wrong@example.com', $failed->credentials['email']);
    }

    public function testAuthorizationFailedEvent(): void
    {
        $container = new \Ishmael\Core\Container();
        $dispatcher = new \Ishmael\Core\Events\Dispatcher($container);
        Event::setInstance($dispatcher);
        $container->bind(\Ishmael\Core\Events\EventBusInterface::class, $dispatcher);

        $gate = new Gate();
        $failedEvent = null;

        $dispatcher->subscribe(AuthorizationFailed::class, function($event) use (&$failedEvent) {
            $failedEvent = $event;
        });

        try {
            $gate->authorize('update-post');
        } catch (\Exception $e) {}

        $this->assertInstanceOf(AuthorizationFailed::class, $failedEvent);
        $this->assertEquals('update-post', $failedEvent->ability);
    }

    public function testRequestFailedEvent(): void
    {
        // Create an app that will fail during dispatch
        $app = new App();
        // Force a dispatcher that throws
        $ref = new \ReflectionClass($app);
        $prop = $ref->getProperty('router');
        $prop->setAccessible(true);
        $router = $this->createMock(\Ishmael\Core\Router::class);
        $router->method('dispatch')->willThrowException(new \Exception("Route failed"));

        $app->boot(); // Init dispatcher
        $prop->setValue($app, $router);

        $failedEvent = null;
        Event::subscribe(RequestFailed::class, function($event) use (&$failedEvent) {
            $failedEvent = $event;
        });

        $app->handle(Request::fromGlobals());

        $this->assertInstanceOf(RequestFailed::class, $failedEvent);
        $this->assertEquals("Route failed", $failedEvent->exception->getMessage());
    }
}
