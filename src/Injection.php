<?php

namespace Krag;

use Psr\Container\ContainerInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerAwareInterface;

class Injection implements InjectionInterface, LoggerAwareInterface
{
    private ?ContainerInterface $leader = null;
    private ?ContainerInterface $follower = null;
    public \Psr\Log\LoggerInterface $logger;

    /**
     * @param array<int, string>|array<string, null|object> $singletons
     * @param array<string, string> $classMappings
     */
    public function __construct(
        protected array $singletons = [],
        protected array $classMappings = [],
        ?\Psr\Log\LoggerInterface $logger = null,
    ) {
        if (count($singletons) && array_is_list($singletons)) {
            $this->singletons = array_fill_keys($singletons, null);
        }
        $this->setDefaultClassMappings();
        $this->logger = $logger ?? new Log();
    }

    /**
     * @param array<int|string, mixed> $data
    */
    protected function trace(\Stringable|string $message, array $data = [], ?string $component = null): void
    {
        if ($this->logger instanceof LogInterface) {
            $component = (is_null($component)) ? static::class : $component;
            $this->logger->trace($message, $data, $component);
        } else {
            $this->logger->debug($message, $data);
        }
    }

    protected function setDefaultClassMappings(): void
    {
        $this->setClassMapping('Request', 'Krag\Request', 'Krag');
        $this->setClassMapping('Response', 'Krag\Response', 'Krag');
        $this->setClassMapping('LogEntry', 'Krag\LogEntry', 'Krag');
        $this->setClassMapping('App', 'Krag\App', 'Krag', true);
        $this->setClassMapping('DB', 'Krag\DB', 'Krag', true);
        $this->setClassMapping('HTTP', 'Krag\HTTP', 'Krag', true);
        $this->setClassMapping('Injection', 'Krag\Injection', 'Krag', true);
        $this->setClassMapping('Log', 'Krag\Log', 'Krag', true);
        $this->setClassMapping('Result', 'Krag\Result', 'Krag', true);
        $this->setClassMapping('Routing', 'Krag\Routing', 'Krag', true);
        $this->setClassMapping('SQL', 'Krag\SQL', 'Krag', true);
        $this->setClassMapping('Views', 'Krag\Views', 'Krag', true);
        $this->setClassMapping('\Psr\Log\LoggerInterface', 'Krag\Log');
    }

    public function setLogger(\Psr\Log\LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    private function setLeaderFollowerSanityCheck(?ContainerInterface $injection, ?ContainerInterface $other): void
    {
        if ($injection == $this) {
            throw new \InvalidArgumentException("Can't set an injection object to lead itself.");
        }
        if ($other) {
            throw new \InvalidArgumentException("Can't set both leader and follower of type Injection; pick a direction to chain.");
        }
    }

    public function setLeader(?ContainerInterface $injection): void
    {
        $this->setLeaderFollowerSanityCheck($injection, $this->follower);
        $this->leader = $injection;
    }

    public function setFollower(?ContainerInterface $injection): void
    {
        $this->setLeaderFollowerSanityCheck($injection, $this->leader);
        $this->follower = $injection;
    }

    public function setSingleton(string $class, ?object $obj = null): InjectionInterface
    {
        $this->singletons[$class] = $obj;
        return $this;
    }

    public function setClassMapping(
        string $fromClass,
        ?string $toClass = null,
        ?string $andNamespace = null,
        string|bool $andInterface = false,
    ): InjectionInterface {
        $fromClass = ltrim($fromClass, '\\');
        $toClass = $toClass ?? $fromClass;
        $this->classMappings[$fromClass] = $toClass;
        $andInterface = (is_bool($andInterface)) ? 'Interface' : $andInterface;
        if (is_null($andNamespace)) {
            if ($andInterface) {
                $this->classMappings[$fromClass.$andInterface] = $toClass;
            }
        } else {
            $namespace = rtrim($andNamespace, '\\').'\\';
            $this->classMappings[$namespace.$fromClass] = $toClass;
            if ($andInterface) {
                $this->classMappings[$namespace.$fromClass.$andInterface] = $toClass;
            }
        }
        return $this;
    }

    public function clone(mixed ...$withValues): Injection
    {
        $this->trace('Injection clone');
        $args = array_diff_key($withValues, ['singletons' => '', 'classMappings' => '', 'logger' => '']);
        $injection = new (static::class)(...$args);
        $injection->setLogger($withValues['logger'] ?? $this->logger);
        foreach ($this->singletons as $class => $obj) {
            $injection->setSingleton($class, $obj);
        }
        foreach ($this->classMappings as $fromClass => $toClass) {
            $injection->setClassMapping($fromClass, $toClass);
        }
        foreach ($withValues['singletons'] ?? [] as $class => $obj) {
            $injection->setSingleton($class, $obj);
        }
        foreach ($withValues['classMappings'] ?? [] as $fromClass => $toClass) {
            $injection->setClassMapping($fromClass, $toClass);
        }
        return $injection;
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

    protected function makeContainerArgumentFromMyself(string $type): ?Injection
    {
        if (static::class == $type) {
            $this->trace("matched myself");
            return $this;
        }
        return null;
    }

    /**
     * @param array<int|string, mixed> $withValues
     */
    protected function makeArgumentFromContainerGet(
        ?ContainerInterface $container,
        \ReflectionParameter $rParam,
        int $position,
        array $withValues,
        bool $preferProvided = false,
    ): mixed {
        $type = strval($rParam->getType());
        if ($container) {
            try {
                if ($container instanceof Injection) {
                    $obj = $container->get($type, $withValues, $preferProvided);
                } else {
                    $obj = $container->get($type);
                }
                $this->trace("matched by get");
                return $obj;
            } catch (NotFoundExceptionInterface $e) {
            }
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
    protected function makeArgumentFallback(
        \ReflectionParameter $rParam,
        int $position,
        array $withValues,
        bool $preferProvided = false,
    ): mixed {
        return null;
    }

    /**
     * @param array<int|string, mixed> $withValues
     */
    protected function makeArgumentForParameter(
        \ReflectionParameter $rParam,
        int $position,
        array $withValues,
        bool $preferProvided = false,
    ): mixed {
        $type = ltrim(strval($rParam->getType()), '?');
        $name = $rParam->getName();
        $this->trace("makeArgumentForParameter(type: $type, name: $name)");
        $arg = null;
        if ($preferProvided) {
            $arg = $this->makeArgumentFromValues($position, $name, $withValues);
        }
        $arg = $arg ?? $this->makeArgumentFromContainerGet($this->leader, $rParam, $position, $withValues, $preferProvided);
        $arg = $arg ?? $this->makeContainerArgumentFromMyself($type);
        $arg = $arg ?? $this->makeArgumentFromContainerGet($this, $rParam, $position, $withValues, $preferProvided);
        if (!$preferProvided) {
            $arg = $arg ?? $this->makeArgumentFromValues($position, $name, $withValues);
        }
        $arg = $arg ?? $this->makeArgumentFromDefaultValue($rParam);
        $arg = $arg ?? $this->makeArgumentFromContainerGet($this->follower, $rParam, $position, $withValues, $preferProvided);
        if (!$rParam->isOptional()) {
            $arg = $arg ?? $this->makeArgumentFallback($rParam, $position, $withValues, $preferProvided);
        }
        $this->trace((is_null($arg)) ? "Made null arg" : "Made non-null arg");
        return $arg;
    }

    /**
     * @param array<int|string, mixed> $withValues
     * @return array<int|string, mixed>
     */
    protected function makeArguments(
        \ReflectionFunctionAbstract $rMethod,
        array $withValues = [],
        bool $preferProvided = false,
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
    protected function makeNew(string $class, array $withValues = [], bool $preferProvided = false): ?object
    {
        $this->trace("makeNew $class");
        $rClass = new \ReflectionClass($class);
        $rConstructor = $rClass->getConstructor();
        $passArguments = $this->makeArguments($rConstructor, $withValues, $preferProvided);
        return $rClass->newInstanceArgs($passArguments);
    }

    /**
     * @param array<int|string, mixed> $withValues
     */
    protected function postMakeNew(string $class, array $withValues, object $obj): void
    {
        if ($obj instanceof \Psr\Log\LoggerAwareInterface) {
            $logger = $this->get('Log');
            $logger->setLogger($this->logger);
            $obj->setLogger($logger);
        }
        if (array_key_exists($class, $this->singletons)) {
            $this->singletons[$class] = $obj;
        }
    }

    /**
     * @param array<int|string, mixed> $withValues
     */
    protected function getFromContainer(?ContainerInterface $container, string $id, array $withValues, bool $preferProvided): mixed
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
        if ($class == static::class && $withValues == []) {
            return $this;
        }
        return null;
    }

    /**
     * @param array<int|string, mixed> $withValues
     */
    protected function getFromSingletons(string $class, array $withValues = []): mixed
    {
        if (array_key_exists($class, $this->singletons) && !is_null($this->singletons[$class])) {
            return $this->singletons[$class];
        }
        return null;
    }

    /**
     * @param array<int|string, mixed> $withValues
     */
    protected function getNew(string $class, array $withValues = [], bool $preferProvided = false): mixed
    {
        if (class_exists($class)) {
            $this->trace("Class $class exists, try makeNew");
            $obj = $this->makeNew($class, $withValues, $preferProvided);
            $this->postMakeNew($class, $withValues, $obj);
            return $obj;
        } else {
            $this->trace("Class $class does not exist");
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
        $class = $this->classMappings[$id] ?? $id;
        $obj = $obj ?? $this->getContainerFromMyself($class, $withValues, $preferProvided);
        $obj = $obj ?? $this->getFromSingletons($class, $withValues);
        $obj = $obj ?? $this->getNew($class, $withValues, $preferProvided);
        $obj = $obj ?? $this->getFromContainer($this->follower, $id, $withValues, $preferProvided);
        if (is_null($obj)) {
            throw new class ('Unable to make: '.$id) extends \InvalidArgumentException implements NotFoundExceptionInterface {};
        }
        return $obj;
    }

    public function has(string $id): bool
    {
        return array_key_exists($id, $this->classMappings);
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
