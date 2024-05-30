<?php

declare(strict_types=1);

namespace Fschmtt\Keycloak\Test\Integration\Resource;

use Fschmtt\Keycloak\Collection\RealmCollection;
use Fschmtt\Keycloak\Representation\Realm;
use Fschmtt\Keycloak\Test\Integration\IntegrationTestBehaviour;
use PHPUnit\Framework\TestCase;

class RealmsTest extends TestCase
{
    use IntegrationTestBehaviour;

    public function testCanGetAllRealms(): void
    {
        $realms = $this->getKeycloak()->realms()->all();

        static::assertInstanceOf(RealmCollection::class, $realms);
        static::assertGreaterThanOrEqual(1, $realms->count());
    }

    public function testCanGetRealm(): void
    {
        $realm = $this->getKeycloak()->realms()->get(realm: 'master');

        static::assertEquals('master', $realm->getRealm());
    }

    public function testCanGetRealmKeys(): void
    {
        $realmkeys = $this->getKeycloak()->realms()->keys(realm: 'master');
        static::assertArrayHasKey('AES', $realmkeys->getActive());
        static::assertGreaterThan(1, $realmkeys->getKeys()->count());
    }

    public function testCanUpdateRealm(): void
    {
        $realm = $this->getKeycloak()->realms()->get(realm: 'master');

        static::assertFalse($realm->getRegistrationAllowed());

        $realm = $realm->withRegistrationAllowed(true);
        $realm = $this->keycloak->realms()->update($realm->getRealm(), $realm);

        static::assertTrue($realm->getRegistrationAllowed());
    }

    public function testCanImportRealm(): void
    {
        $realm = new Realm(id: 'testing-id', realm: 'testing-realm');
        $realm = $this->getKeycloak()->realms()->import(realmOrFile: $realm);

        static::assertEquals('testing-id', $realm->getId());
        static::assertEquals('testing-realm', $realm->getRealm());

        static::assertCount(2, $this->keycloak->realms()->all());
    }

    public function testCanImportRealmFromFile(): void
    {
        $realm = $this->getKeycloak()->realms()->import(realmOrFile: 'tests/Fixtures/test-realm.json');

        static::assertEquals('b6d9db69-39c9-489f-8d15-757bdc827e8d', $realm->getId());
        static::assertEquals('test', $realm->getRealm());

        static::assertCount(2, $this->keycloak->realms()->all());
    }

    public function testCanImportRealmFromArrayFile(): void
    {
        $realm = $this->getKeycloak()->realms()->import(realmOrFile: 'tests/Fixtures/import.json', name: 'test');

        static::assertEquals('b6d9db69-39c9-489f-8d15-757bdc827e8d', $realm->getId());
        static::assertEquals('test', $realm->getRealm());

        static::assertCount(2, $this->keycloak->realms()->all());
    }

    public function testCanClearCaches(): void
    {
        $this->expectNotToPerformAssertions();

        $realm = new Realm(realm: 'master');

        $this->getKeycloak()->realms()->clearKeysCache($realm->getRealm());
        $this->getKeycloak()->realms()->clearRealmCache($realm->getRealm());
        $this->getKeycloak()->realms()->clearUserCache($realm->getRealm());
    }

    public function testCanClearKeysCache(): void
    {
        $this->expectNotToPerformAssertions();

        $this->getKeycloak()->realms()->clearKeysCache('master');
    }

    public function testCanClearRealmCache(): void
    {
        $this->expectNotToPerformAssertions();

        $this->getKeycloak()->realms()->clearRealmCache('master');
    }

    public function testCanClearUserCache(): void
    {
        $this->expectNotToPerformAssertions();

        $this->getKeycloak()->realms()->clearUserCache('master');
    }

    public function testCanGetAdminEvents(): void
    {
        $adminEvents = $this->getKeycloak()->realms()->adminEvents('master');

        static::assertEmpty($adminEvents);
    }

    public function testCanDeleteAdminEvents(): void
    {
        $this->expectNotToPerformAssertions();

        $this->getKeycloak()->realms()->deleteAdminEvents('master');
    }
}
