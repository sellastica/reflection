<?php
namespace Sellastica\Reflection;

class ReflectionClass extends \Nette\Reflection\ClassType
{
	/** @var \Nette\Reflection\ClassType */
	private $classReflection;
	/** @var array */
	private $propertyDefaultValues;
	/** @var \Nette\Reflection\Property[] */
	private $propertyReflections = [];


	/**
	 * @param string $className
	 */
	public function __construct(string $className)
	{
		parent::__construct($className);

		$this->classReflection = new \Nette\Reflection\ClassType($className);
		$this->propertyDefaultValues = $this->classReflection->getDefaultProperties();
	}

	/**
	 * @param string $annotation
	 * @param bool $includeParentClassProperties
	 * @return ReflectionProperty[]
	 */
	public function filterProperties(string $annotation = null, bool $includeParentClassProperties = false)
	{
		$properties = [];
		foreach ($this->classReflection->getProperties() as $property) {
			if (!isset($annotation) || $this->getPropertyReflection($property->getName())->hasAnnotation($annotation)) {
				$properties[] = new ReflectionProperty(
					$property,
					$this->getPropertyDefaultValue($property)
				);
			}
		}

		if (true === $includeParentClassProperties && $this->classReflection->getParentClass()) {
			$reflectionClass = new self($this->classReflection->getParentClass()->getName());
			$properties = array_merge($reflectionClass->filterProperties($annotation), $properties);
		}

		return $properties;
	}

	/**
	 * @param \Nette\Reflection\Property $property
	 * @return mixed|null
	 */
	public function getPropertyDefaultValue(\Nette\Reflection\Property $property)
	{
		return isset($this->propertyDefaultValues[$property->getName()])
			? $this->propertyDefaultValues[$property->getName()]
			: null;
	}

	/**
	 * @param int $filter
	 * @param bool $includeParentMethods
	 * @return \Nette\Reflection\Method[]
	 */
	public function getMethods($filter = -1, bool $includeParentMethods = true)
	{
		$methods = parent::getMethods($filter);
		if (true === $includeParentMethods) {
			return $methods;
		} else {
			$localMethods = [];
			foreach ($methods as $method) {
				if ($method->getDeclaringClass()->getName() === $this->getName()) {
					$localMethods[] = $method;
				}
			}

			return $localMethods;
		}
	}

	/**
	 * @param array|ReflectionProperty[] $properties
	 * @return array
	 * @throws \InvalidArgumentException
	 */
	public function getUseStatements(array $properties): array
	{
		$use = [];
		foreach ($properties as $property) {
			if (!$property instanceof ReflectionProperty) {
				throw new \InvalidArgumentException('Property must be an instance of ReflectionPropertyMapper');
			}

			if (!$property->isResolved()) {
				$className = \Nette\DI\PhpReflection::expandClassName(
					$property->getType(), $property->getDeclaringClass()
				);
				if (!in_array($className, $use)) {
					$use[] = $className;
				}
			}
		}

		return $use;
	}

	/**
	 * @param string $property
	 * @return \Nette\Reflection\Property
	 */
	private function getPropertyReflection(string $property)
	{
		if (!isset($this->propertyReflections[$property])) {
			$this->propertyReflections[$property] = $this->classReflection->getProperty($property);
		}

		return $this->propertyReflections[$property];
	}
}