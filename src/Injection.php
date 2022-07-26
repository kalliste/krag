<?php

namespace Krag;

use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

class Injection implements InjectionInterface, LoggerAwareInterface
{
    /**
     * @var array<int|string, object|callable|string>
     */
    private array $mappings = [];
    private ?ContainerInterface $leader = null;
    private ?ContainerInterface $follower = null;

    public function __construct(private LoggerInterface $logger)
    {
        $this->setDefaultMappings();
    }

    /**
     * @param array<int|string, mixed> $data
    */
    protected function trace(\Stringable|string $message, array $data = [], ?string $component = null): void
    {
        $this->logger->debug($message, $data);
    }

    protected function setDefaultMappings(): void
    {
        $this->setMapping('Krag\AppInterface', 'Krag\App');
        $this->setMapping('Krag\DBInterface', 'Krag\DB');
        $this->setMapping('Krag\HTTPInterface', 'Krag\HTTP');
        $this->setMapping('Krag\InjectionInterface', 'Krag\Injection');
        $this->setMapping('Krag\ResultInterface', 'Krag\Result');
        $this->setMapping('Krag\RoutingInterface', 'Krag\Routing');
        $this->setMapping('Krag\SQLInterface', 'Krag\SQL');
        $this->setMapping('Krag\ViewsInterface', 'Krag\Views');
        $this->setMapping('Psr\Log\LoggerInterface', $this->logger);
    }

    public function setLogger(\Psr\Log\LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    private function setLeaderFollowerSanityCheck(?ContainerInterface $other): void
    {
        if ($this == $other) {
            throw new \InvalidArgumentException("Can't set an injection object to lead or follow itself.");
        }
    }

    public function setLeader(?ContainerInterface $container): void
    {
        $this->setLeaderFollowerSanityCheck($container);
        $this->leader = $container;
    }

    public function setFollower(?ContainerInterface $container): void
    {
        $this->setLeaderFollowerSanityCheck($container);
        $this->follower = $container;
    }

    /**
     * @param string|array<int|string, string> $from
     */
    public function setMapping(string|array $from, object|callable|string $to): Injection
    {
        if (is_array($from)) {
            array_map(fn ($x) => $this->setMapping($x, $to), $from);
        } else {
            $this->mappings[$from] = $to;
        }
        return $this;
    }

    public function clone(?LoggerInterface $logger = null): Injection
    {
        $this->trace('Injection clone');
        $clone = new (static::class)($logger ?? $this->logger);
        foreach ($this->mappings as $from => $to) {
            $clone->setMapping($from, $to);
        }
        return $clone;
    }

    /**
     * @param array<int|string, mixed> $withValues
     */
    protected function makeArgumentFromValues(int $position, string $name, array $withValues): mixed
    {
        $this->trace("makeArgumentFromValues($position, $name, keys: [".implode(', ', array_keys($withValues))."])");
        if (array_is_list($withValues) && $position < count($withValues)) {
            $this->trace("matched by position");
            return $withValues[$position];
        }
        if (array_key_exists($name, $withValues)) {
            $this->trace("matched by name");
            return $withValues[$name];
        }
        return null;
    }

    protected function makeArgumentFromDefaultValue(\ReflectionParameter $rParam): mixed
    {
        if ($rParam->isOptional()) {
            $this->trace("matched by default value");
            return $rParam->getDefaultValue();
        }
        return null;
    }

    /**
     * @param array<int|string, mixed> $withValues
     */
    protected function makeArgumentForParameter(
        \ReflectionParameter $rParam,
        int $position,
        array $withValues,
        bool $preferProvided,
    ): mixed {
        $type = ltrim(strval($rParam->getType()), '?');
        $name = $rParam->getName();
        $this->trace("makeArgumentForParameter(type: $type, class: $name, withValues keys: ".implode(', ', array_keys($withValues)).")");
        $arg = null;
        if ($preferProvided) {
            $arg = $this->makeArgumentFromValues($position, $name, $withValues);
        }
        $arg = $arg ?? $this->getFromContainer($this, $type);
        if (!$preferProvided) {
            $arg = $arg ?? $this->makeArgumentFromValues($position, $name, $withValues);
        }
        $arg = $arg ?? $this->makeArgumentFromDefaultValue($rParam);
        $this->trace((is_null($arg)) ? "Made null arg for $name" : "Made non-null arg for $name");
        return $arg;
    }

    /**
     * @param array<int|string, mixed> $withValues
     * @return array<int|string, mixed>
     */
    protected function makeArguments(
        \ReflectionFunctionAbstract $rMethod,
        array $withValues,
        bool $preferProvided,
    ): array {
        $passArguments = [];
        $i = 0;
        foreach ($rMethod->getParameters() as $rParam) {
            $arg = $this->makeArgumentForParameter($rParam, $i, $withValues, $preferProvided);
            if (!is_null($arg) || !$rParam->isOptional()) {
                $passArguments[$rParam->getName()] = $arg;
            }
            $i++;
        }
        return $passArguments;
    }

    /**
     * @param array<int|string, mixed> $withValues
     */
    protected function getFromContainer(?ContainerInterface $container, string $id, array $withValues = [], bool $preferProvided = false): mixed
    {
        if ($container) {
            try {
                if ($container instanceof InjectionInterface) {
                    $result = $container->get($id, $withValues, $preferProvided);
                } else {
                    $result = $container->get($id);
                }
                return $result;
            } catch (NotFoundExceptionInterface $e) {
            }
        }
        return null;
    }

    /**
     * @param array<int|string, mixed> $withValues
     */
    protected function getContainerFromMyself(string $class, array $withValues, bool $preferProvided): ?Injection
    {
        if ($this instanceof $class) {
            return $this;
        }
        return null;
    }

    /**
     * @param array<int|string, mixed> $withValues
     */
    protected function getNew(string $class, array $withValues, bool $preferProvided): mixed
    {
        $this->trace("getNew $class");
        if (class_exists($class)) {
            $this->trace("class $class exists");
            $rClass = new \ReflectionClass($class);
            if (!$rClass->isEnum()) {
                $rConstructor = $rClass->getConstructor();
                $passArguments = $this->makeArguments($rConstructor, $withValues, $preferProvided);
                return $rClass->newInstanceArgs($passArguments);
            }
        }
        return null;
    }

    /**
     * @param array<int|string, mixed> $withValues
     */
    public function get(string $id, array $withValues = [], bool $preferProvided = true)
    {
        $this->trace("get $id");
        $obj = $this->getFromContainer($this->leader, $id, $withValues, $preferProvided);
        $mapped = $this->mappings[$id] ?? $id;
        if (is_object($mapped)) {
            return $mapped;
        }
        if (is_string($mapped)) {
            $obj = $obj ?? $this->getContainerFromMyself($mapped, $withValues, $preferProvided);
            $obj = $obj ?? $this->getNew($mapped, $withValues, $preferProvided);
            $obj = $obj ?? $this->getFromContainer($this->follower, $id, $withValues, $preferProvided);
        }
        if (is_callable($mapped)) {
            return $this->call($mapped, $withValues, $preferProvided);
        }
        if (is_null($obj)) {
            throw new class ('Unable to make: '.$id) extends \InvalidArgumentException implements NotFoundExceptionInterface {};
        }
        return $obj;
    }

    public function has(string $id): bool
    {
        return array_key_exists($id, $this->mappings);
    }

    /**
     * @param array<int|string, mixed> $withValues
     */
    public function call(callable $method, array $withValues = [], bool $preferProvided = false): mixed
    {
        $rMethod = new \ReflectionFunction($method);
        $arguments = $this->makeArguments($rMethod, $withValues, $preferProvided);
        return call_user_func_array($method, $arguments);
    }
}
