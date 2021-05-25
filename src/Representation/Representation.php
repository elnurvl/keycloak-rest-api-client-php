<?php

declare(strict_types=1);

namespace Fschmtt\Keycloak\Representation;

use Fschmtt\Keycloak\Exception\PropertyDoesNotExistException;
use Fschmtt\Keycloak\Json\JsonDecoder;

abstract class Representation implements RepresentationInterface
{
    public function __construct(...$properties)
    {
    }

    public static function from(array $properties): static
    {
        $representation = new static();

        foreach ($properties as $property => $value) {
            $representation = $representation->withProperty($property, $value);
        }

        return $representation;
    }

    public static function fromJson(string $json): static
    {
        return static::from(
            (new JsonDecoder())->decode($json)
        );
    }

    public function with(string $property, mixed $value): static
    {
        return $this->withProperty($property, $value);
    }

    public function __call(string $name, array $arguments): mixed
    {
        if (str_starts_with($name, 'get')) {
            return $this->__get(lcfirst(substr($name, 3)));
        }

        if (str_starts_with($name, 'with')) {
            return $this->with(lcfirst(substr($name, 4)), $arguments[0]);
        }

        throw new PropertyDoesNotExistException();
    }

    public function __get(string $name): mixed
    {
        $this->throwExceptionIfPropertyDoesNotExist($name);

        return $this->$name;
    }

    private function withProperty(string $property, mixed $value): static
    {
        $this->throwExceptionIfPropertyDoesNotExist($property);

        $clone = clone $this;
        $clone->$property = $value;

        return $clone;
    }

    private function throwExceptionIfPropertyDoesNotExist(string $property): void
    {
        if (!property_exists(static::class, $property)) {
            throw new PropertyDoesNotExistException(
                sprintf(
                    'Property "%s" does not exist in "%s"',
                    $property,
                    static::class,
                )
            );
        }
    }
}
