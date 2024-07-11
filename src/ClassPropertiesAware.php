<?php

namespace Pocket\Framework;

use ReflectionClass;
use ReflectionProperty;

/**
 * Class properties aware trait
 */
trait ClassPropertiesAware
{
    /**
     * @var ReflectionClass
     */
    private ReflectionClass $classReflection;

    /**
     * Get the class reflection of this class
     *
     * @return ReflectionClass
     */
    protected function getClassReflection(): ReflectionClass
    {
        if (isset($this->classReflection)) {
            return $this->classReflection;
        }

        return $this->classReflection = new ReflectionClass($this);
    }

    /**
     * Set class public properties
     *
     * @param array $options class public properties
     * @return void
     */
    public function setClassOptions(array $options): void
    {
        $rfClass = $this->getClassReflection();
        foreach ($options as $property => $value) {
            if (!$rfClass->hasProperty($property)) {
                continue;
            }

            $rfProperty = $rfClass->getProperty($property);
            if (!$rfProperty->isPublic()) {
                continue;
            }

            if ($rfProperty->isStatic()) {
                $rfProperty->setValue(null, $value);
            } else {
                $rfProperty->setValue($this, $value);
            }
        }
    }

    /**
     * Get class public properties
     *
     * @return array
     */
    public function getClassOptions(): array
    {
        $publicProperties = $this->getClassReflection()->getProperties(ReflectionProperty::IS_PUBLIC);

        $options = [];
        foreach ($publicProperties as $rfProperty) {
            if ($rfProperty->isStatic()) {
                $options[$rfProperty->getName()] = $rfProperty->getValue();
            } else {
                $options[$rfProperty->getName()] = $rfProperty->getValue($this);
            }
        }

        return $options;
    }

    /**
     * Get class default public properties
     *
     * @return array
     */
    public function getClassDefaultOptions(): array
    {
        $defaultProperties = $this->getClassReflection()->getDefaultProperties();
        $publicProperties = $this->getClassReflection()->getProperties(ReflectionProperty::IS_PUBLIC);

        $options = [];
        foreach ($publicProperties as $rfProperty) {
            $options[$rfProperty->getName()] = $defaultProperties[$rfProperty->getName()] ?? null;
        }

        return $options;
    }
}