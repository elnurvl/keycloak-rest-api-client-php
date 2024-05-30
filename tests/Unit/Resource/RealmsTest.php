<?php

declare(strict_types=1);

namespace Fschmtt\Keycloak\Test\Unit\Resource;

use Fschmtt\Keycloak\Collection\RealmCollection;
use Fschmtt\Keycloak\Exception\FilesystemException;
use Fschmtt\Keycloak\Http\Command;
use Fschmtt\Keycloak\Http\CommandExecutor;
use Fschmtt\Keycloak\Http\Method;
use Fschmtt\Keycloak\Http\Query;
use Fschmtt\Keycloak\Http\QueryExecutor;
use Fschmtt\Keycloak\Representation\KeysMetadata;
use Fschmtt\Keycloak\Representation\Realm;
use Fschmtt\Keycloak\Resource\Realms;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Realms::class)]
class RealmsTest extends TestCase
{
    public function testGetAllRealms(): void
    {
        $query = new Query(
            '/admin/realms',
            RealmCollection::class,
        );

        $queryExecutor = $this->createMock(QueryExecutor::class);
        $queryExecutor->expects(static::once())
            ->method('executeQuery')
            ->with($query)
            ->willReturn(
                new RealmCollection([
                    new Realm(realm: 'realm-1'),
                    new Realm(realm: 'realm-2'),
                ])
            );

        $realms = new Realms(
            $this->createMock(CommandExecutor::class),
            $queryExecutor,
        );
        $realms = $realms->all();

        static::assertInstanceOf(RealmCollection::class, $realms);
        static::assertCount(2, $realms);
    }

    public function testImportRealm(): void
    {
        $command = new Command(
            '/admin/realms',
            Method::POST,
            [],
            new Realm(realm: 'imported-realm'),
        );

        $commandExecutor = $this->createMock(CommandExecutor::class);
        $commandExecutor->expects(static::once())
            ->method('executeCommand')
            ->with($command);

        $query = new Query(
            '/admin/realms/{realm}',
            Realm::class,
            [
                'realm' => 'imported-realm',
            ],
        );

        $queryExecutor = $this->createMock(QueryExecutor::class);
        $queryExecutor->expects(static::once())
            ->method('executeQuery')
            ->with($query)
            ->willReturn(
                new Realm(realm: 'imported-realm')
            );

        $realms = new Realms(
            $commandExecutor,
            $queryExecutor,
        );
        $realm = $realms->import(new Realm(realm: 'imported-realm'));

        static::assertSame('imported-realm', $realm->getRealm());
    }

    public function testImportRealmFromSingleFile(): void
    {
        $fixture = 'tests/Fixtures/test-realm.json';
        $json = file_get_contents($fixture);
        $realm = Realm::fromJson($json);
        $command = new Command(
            '/admin/realms',
            Method::POST,
            [],
            $realm,
        );

        $commandExecutor = $this->createMock(CommandExecutor::class);
        $commandExecutor->expects(static::once())
            ->method('executeCommand')
            ->with($command);

        $query = new Query(
            '/admin/realms/{realm}',
            Realm::class,
            [
                'realm' => 'test',
            ],
        );

        $queryExecutor = $this->createMock(QueryExecutor::class);
        $queryExecutor->expects(static::once())
            ->method('executeQuery')
            ->with($query)
            ->willReturn($realm);

        $realms = new Realms(
            $commandExecutor,
            $queryExecutor,
        );
        $realm = $realms->import($fixture);

        static::assertSame('test', $realm->getRealm());
    }

    public function testImportRealmFromArrayFile(): void
    {
        $fixture = 'tests/Fixtures/import.json';
        $json = file_get_contents($fixture);
        $realms = json_decode($json, true);
        $realmPayload = current(array_filter($realms, fn ($realm) => $realm['realm'] === 'test'));
        $realm = Realm::fromJson(json_encode($realmPayload));
        $command = new Command(
            '/admin/realms',
            Method::POST,
            [],
            $realm,
        );

        $commandExecutor = $this->createMock(CommandExecutor::class);
        $commandExecutor->expects(static::once())
            ->method('executeCommand')
            ->with($command);

        $query = new Query(
            '/admin/realms/{realm}',
            Realm::class,
            [
                'realm' => 'test',
            ],
        );

        $queryExecutor = $this->createMock(QueryExecutor::class);
        $queryExecutor->expects(static::once())
            ->method('executeQuery')
            ->with($query)
            ->willReturn($realm);

        $realms = new Realms(
            $commandExecutor,
            $queryExecutor,
        );
        $realm = $realms->import($fixture, 'test');

        static::assertSame('test', $realm->getRealm());
    }

    public function testImportRealmFromArrayThrowsExceptionIfRealmIsNotSpecified(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $fixture = 'tests/Fixtures/import.json';

        $commandExecutor = $this->createMock(CommandExecutor::class);

        $queryExecutor = $this->createMock(QueryExecutor::class);

        $realms = new Realms(
            $commandExecutor,
            $queryExecutor,
        );
        $realm = $realms->import($fixture);

        static::assertSame('test', $realm->getRealm());
    }

    public function testImportRealmThrowsExceptionIfFileNotFound(): void
    {
        $this->expectException(FilesystemException::class);
        $fixture = 'tests/Fixtures/non-existing-file.json';

        $commandExecutor = $this->createMock(CommandExecutor::class);

        $queryExecutor = $this->createMock(QueryExecutor::class);

        $realms = new Realms(
            $commandExecutor,
            $queryExecutor,
        );
        $realm = $realms->import($fixture, 'test');

        static::assertSame('test', $realm->getRealm());
    }

    public function testImportRealmThrowsExceptionIfPathIsDir(): void
    {
        $this->expectException(FilesystemException::class);
        $fixture = 'tests/Fixtures';

        $commandExecutor = $this->createMock(CommandExecutor::class);

        $queryExecutor = $this->createMock(QueryExecutor::class);

        $realms = new Realms(
            $commandExecutor,
            $queryExecutor,
        );
        $realm = $realms->import($fixture, 'test');

        static::assertSame('test', $realm->getRealm());
    }

    public function testUpdateRealm(): void
    {
        $updatedRealm = new Realm(realm: 'updated-realm', displayName: 'Updated Realm');

        $command = new Command(
            '/admin/realms/{realm}',
            Method::PUT,
            [
                'realm' => 'to-be-updated-realm',
            ],
            $updatedRealm,
        );

        $commandExecutor = $this->createMock(CommandExecutor::class);
        $commandExecutor->expects(static::once())
            ->method('executeCommand')
            ->with($command);

        $query = new Query(
            '/admin/realms/{realm}',
            Realm::class,
            [
                'realm' => 'updated-realm',
            ],
        );

        $queryExecutor = $this->createMock(QueryExecutor::class);
        $queryExecutor->expects(static::once())
            ->method('executeQuery')
            ->with($query)
            ->willReturn(
                new Realm(
                    displayName: 'Updated Realm',
                    realm: 'updated-realm',
                )
            );

        $realms = new Realms(
            $commandExecutor,
            $queryExecutor,
        );
        $realm = $realms->update('to-be-updated-realm', $updatedRealm);

        static::assertSame('Updated Realm', $realm->getDisplayName());
    }

    public function testDeleteRealm(): void
    {
        $command = new Command(
            '/admin/realms/{realm}',
            Method::DELETE,
            [
                'realm' => 'to-be-deleted-realm',
            ],
        );

        $commandExecutor = $this->createMock(CommandExecutor::class);
        $commandExecutor->expects(static::once())
            ->method('executeCommand')
            ->with($command);

        $realms = new Realms(
            $commandExecutor,
            $this->createMock(QueryExecutor::class),
        );
        $realms->delete('to-be-deleted-realm');
    }

    public function testGetAdminEvents(): void
    {
        $query = new Query(
            '/admin/realms/{realm}/admin-events',
            'array',
            [
                'realm' => 'realm-with-admin-events',
            ],
        );

        $queryExecutor = $this->createMock(QueryExecutor::class);
        $queryExecutor->expects(static::once())
            ->method('executeQuery')
            ->with($query)
            ->willReturn([
                [], [],
            ]);

        $realms = new Realms(
            $this->createMock(CommandExecutor::class),
            $queryExecutor,
        );
        $adminEvents = $realms->adminEvents('realm-with-admin-events');

        static::assertCount(2, $adminEvents);
    }

    public function testDeleteAdminEvents(): void
    {
        $command = new Command(
            '/admin/realms/{realm}/admin-events',
            Method::DELETE,
            [
                'realm' => 'realm-with-admin-events',
            ],
        );

        $commandExecutor = $this->createMock(CommandExecutor::class);
        $commandExecutor->expects(static::once())
            ->method('executeCommand')
            ->with($command);

        $realms = new Realms(
            $commandExecutor,
            $this->createMock(QueryExecutor::class),
        );
        $realms->deleteAdminEvents('realm-with-admin-events');
    }

    public function testClearKeysCache(): void
    {
        $command = new Command(
            '/admin/realms/{realm}/clear-keys-cache',
            Method::POST,
            [
                'realm' => 'realm-with-cache',
            ],
        );

        $commandExecutor = $this->createMock(CommandExecutor::class);
        $commandExecutor->expects(static::once())
            ->method('executeCommand')
            ->with($command);

        $realms = new Realms(
            $commandExecutor,
            $this->createMock(QueryExecutor::class),
        );
        $realms->clearKeysCache('realm-with-cache');
    }

    public function testClearRealmCache(): void
    {
        $command = new Command(
            '/admin/realms/{realm}/clear-realm-cache',
            Method::POST,
            [
                'realm' => 'realm-with-cache',
            ],
        );

        $commandExecutor = $this->createMock(CommandExecutor::class);
        $commandExecutor->expects(static::once())
            ->method('executeCommand')
            ->with($command);

        $realms = new Realms(
            $commandExecutor,
            $this->createMock(QueryExecutor::class),
        );
        $realms->clearRealmCache('realm-with-cache');
    }

    public function testClearUserCache(): void
    {
        $command = new Command(
            '/admin/realms/{realm}/clear-user-cache',
            Method::POST,
            [
                'realm' => 'realm-with-cache',
            ],
        );

        $commandExecutor = $this->createMock(CommandExecutor::class);
        $commandExecutor->expects(static::once())
            ->method('executeCommand')
            ->with($command);

        $realms = new Realms(
            $commandExecutor,
            $this->createMock(QueryExecutor::class),
        );
        $realms->clearUserCache('realm-with-cache');
    }

    public function testGetKeys(): void
    {
        $query = new Query(
            '/admin/realms/{realm}/keys',
            KeysMetadata::class,
            [
                'realm' => 'realm-with-keys',
            ],
        );

        $queryExecutor = $this->createMock(QueryExecutor::class);
        $queryExecutor->expects(static::once())
            ->method('executeQuery')
            ->with($query)
            ->willReturn(new KeysMetadata());

        $realms = new Realms(
            $this->createMock(CommandExecutor::class),
            $queryExecutor,
        );

        $realms->keys('realm-with-keys');
    }
}
