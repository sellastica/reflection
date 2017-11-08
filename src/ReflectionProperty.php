<?php
namespace Sellastica\Reflection;

class ReflectionProperty
{
	/** @var array */
	private static $defaultTypes = [
		'bool', 'boolean', 'string', 'true', 'false', 'null', 'int', 'float',
		'double', 'array', 'callable', 'mixed', 'resource', 'number', 'object',
	];

	/** @var \Nette\Reflection\Property */
	private $property;
	/** @var mixed */
	private $defaultValue;
	/** @var bool */
	private $nullable;
	/** @var array All types, e.g. [string, null] */
	private $types = [];
	/** @var string|null Main type. Can be null if more than 2 var types are defined, e.g. string|bool|null */
	private $type;


	/**
	 * @param \Nette\Reflection\Property $property
	 * @param null $defaultValue
	 */
	public function __construct(
		\Nette\Reflection\Property $property,
		$defaultValue = null
	)
	{
		$this->property = $property;
		$this->defaultValue = $defaultValue;
		$this->types = $this->parseTypes();
		$this->setTypeAndNullable();
	}

	private function setTypeAndNullable()
	{
		$nullKey = array_search('null', $this->types);
		$this->nullable = $nullKey !== false; //true, if $type contains null

		if (sizeof($this->types) == 0) {
			throw new \Exception('Property type is missing');
		} elseif (sizeof($this->types) == 1) {
			if ($this->nullable) {
				throw new \Exception('Property type cannot be null only');
			} else {
				$this->type = $this->types[0];
			}
		} elseif (sizeof($this->types) == 2 && (false !== $nullKey)) {
			$this->type = $nullKey == 1 ? $this->types[0] : $this->types[1]; //type has to be the orher option than "null"
		} else {
			$this->type = null;
		}
	}

	/**
	 * @return array
	 */
	private function parseTypes(): array
	{
		$annotation = trim($this->property->getAnnotation('var'));
		if (strpos($annotation, ' ') !== false) { //description is present after the var type
			$types = \Nette\Utils\Strings::before($annotation, ' ');
		} else {
			$types = $annotation;
		}

		return array_map(function($value) {
				if (in_array($this->type, self::$defaultTypes)) {
					//string, int...
					return strtolower($value);
				} else {
					//class name etc.
					return $value;
				}
			},
			explode('|', $types)
		);
	}

	/**
	 * @return string
	 */
	public function getName(): string
	{
		return $this->property->getName();
	}

	/**
	 * @return mixed
	 */
	public function getDefaultValue()
	{
		return $this->defaultValue;
	}

	/**
	 * @return bool
	 */
	public function isNullable(): bool
	{
		return $this->nullable;
	}

	/**
	 * @return string
	 */
	public function renderTypes(): string
	{
		return implode('|', $this->types);
	}

	/**
	 * @return string|null
	 */
	public function getType(): ?string
	{
		return $this->type;
	}

	/**
	 * @return array
	 */
	public function getTypes(): array
	{
		return $this->types;
	}

	/**
	 * @return bool
	 */
	public function isInt(): bool
	{
		return $this->type === 'int';
	}

	/**
	 * @return bool
	 */
	public function isString(): bool
	{
		return $this->type === 'string';
	}

	/**
	 * @param string $name
	 * @return bool
	 */
	public function hasAnnotation(string $name): bool
	{
		return $this->property->hasAnnotation($name);
	}

	/**
	 * @return \Nette\Reflection\ClassType
	 */
	public function getDeclaringClass(): \Nette\Reflection\ClassType
	{
		return $this->property->getDeclaringClass();
	}

	/**
	 * @param string $name
	 * @return \Nette\Reflection\IAnnotation
	 */
	public function getAnnotation(string $name): \Nette\Reflection\IAnnotation
	{
		return $this->property->getAnnotation($name);
	}

	/**
	 * Return true, if property is a default type or class name containing whole namespace
	 * Method is used by building class namespace from the file header
	 * @return bool
	 */
	public function isResolved(): bool
	{
		return in_array($this->type, self::$defaultTypes) || \Nette\Utils\Strings::startsWith($this->type, '\\');
	}

	/**
	 * @return bool
	 */
	public function isDefaultType(): bool
	{
		return in_array($this->type, self::$defaultTypes);
	}

	/**
	 * @return bool
	 */
	public function isUnknownType(): bool
	{
		return $this->type === null;
	}

	/**
	 * Returns true, if property is type of array or object[]
	 * @return bool
	 */
	public function isArrayType(): bool
	{
		return $this->type === 'array' || preg_match('~(.+)\[\]~', $this->type);
	}

	/**
	 * @return \Nette\Reflection\Property
	 */
	public function getPropertyReflection(): \Nette\Reflection\Property
	{
		return $this->property;
	}

	/**
	 * @return array
	 */
	public static function getDefaultTypes(): array
	{
		return self::$defaultTypes;
	}
}