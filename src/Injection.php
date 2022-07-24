<?php

namespace Krag;

use Psr\Log\LoggerAwareInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class Injection implements InjectionInterface, LoggerAwareInterface
{
    private ?InjectionInterface $leader = null;

    public function __construct(
        protected array $singletons = [],
        protected array $classMappings = [],
        public ?\Psr\Log\LoggerInterface $logger = null,
    ) {
        if (count($singletons) && array_is_list($singletons)) {
            $this->singletons = array_fill_keys($singletons, null);
        }
        $this->setDefaultClassMappings();
    }

    protected function setDefaultClassMappings()
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

    public function setInjection(InjectionInterface $injection): void
    {
        $this->leader = $injection;
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
        $toClass = $toClass ?? $fromClass;
        $this->classMappings[$fromClass] = $toClass;
        $andInterface = (is_bool($andInterface)) ? 'Interface' : $andInterface;
        if (is_null($andNamespace)) {
            if ($andInterface) {
                $this->classMappings[$fromClass.'Interface'] = $toClass;
            }
        } else {
            $namespace = rtrim($andNamespace, '\\').'\\';
            $this->classMappings[$namespace.$fromClass] = $toClass;
            if ($andInterface) {
                $this->classMappings[$namespace.$fromClass.'Interface'] = $toClass;
            }
        }
        return $this;
    }

    protected function matchParamToValues(int $position, string $name, array|object $withValues): mixed
    {
        if (is_object($withValues)) {
            return $withValues->$name ?? null;
        }
        if (array_is_list($withValues) && $position < count($withValues)) {
            return $withValues[$position];
        }
        if (array_key_exists($name, $withValues)) {
            return $withValues[$name];
        }
        return null;
    }

    protected function makeArgumentFallback(\ReflectionParameter $rParam): mixed
    {
        return null;
    }

    protected function makeArgumentForParameter(
        \ReflectionParameter $rParam,
        int $position,
        array|object $withValues,
        bool $preferProvided = false,
    ): mixed {
        $type = strval($rParam->getType());
        $name = $rParam->getName();
        $arg = null;
        if ($preferProvided) {
            $arg = $arg ?? $this->matchParamToValues($position, $name, $withValues);
        }
        $arg = $arg ?? ((static::class == $type) ? $this : null);
        try {
            $arg = $arg ?? $this->get($type);
        } catch (NotFoundExceptionInterface $e) {
        }
        if (!$preferProvided) {
            $arg = $arg ?? $this->matchParamToValues($position, $name, $withValues);
        }
        $arg = $arg ?? (($rParam->isOptional()) ? $rParam->getDefaultValue() : null);
        if (!$rParam->isOptional()) {
            $arg = $arg ?? $this->makeArgumentFallback($rParam);
        }
        return $arg;
    }

    protected function makeArgumentsForMethod(
        \ReflectionMethod $rMethod,
        array|object $withValues = [],
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

    protected function postMakeNew(string $class, array|object $withValues, object $obj)
    {
        if ($obj instanceof \Psr\Log\LoggerAwareInterface) {
            $logger = $this->get('Log');
            if ($this->logger && $logger instanceof \Psr\Log\LoggerAwareInterface) {
                $logger->setLogger($this->logger);
            }
            $obj->setLogger($logger);
        }
        if (array_key_exists($class, $this->singletons)) {
            $this->singletons[$class] = $obj;
        }
    }

    protected function makeNew(string $class, array|object $withValues = []): ?object
    {
        $rClass = new \ReflectionClass($class);
        $rConstructor = $rClass->getConstructor();
        $passArguments = $this->makeArgumentsForMethod($rConstructor, $withValues, true);
        return $rClass->newInstanceArgs($passArguments);
    }

    public function get(string $id, array|object $withValues = [])
    {
        if ($this->leader) {
            try {
                $result = $this->leader->get($id, $withValues);
                return $result;
            } catch (NotFoundExceptionInterface $e) {
            }
        }
        $class = $this->classMappings[$id] ?? $id;
        if ($class == static::class && $withValues == []) {
            return $this;
        }
        if (array_key_exists($class, $this->singletons) && !is_null($this->singletons[$class])) {
            return $this->singletons[$class];
        }
        if (class_exists($class)) {
            $obj = $this->makeNew($class, $withValues);
            $this->postMakeNew($class, $withValues, $obj);
            return $obj;
        }
        throw new class ('Unable to make: '.$id) extends \InvalidArgumentException implements NotFoundExceptionInterface {};
    }

    public function has(string $id): bool
    {
        return array_key_exists($id, $this->classMappings);
    }

    public function call(callable $method, array|object $withValues = []): mixed
    {
        $rMethod = new \ReflectionMethod($method);
        $arguments = $this->makeArgumentsForMethod($rMethod, $withValues, false);
        return call_user_func_array($method, $arguments);
    }
}
