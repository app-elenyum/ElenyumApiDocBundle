<?php

namespace Elenyum\ApiDocBundle\Normalizer;

use Elenyum\ApiDocBundle\Service\BaseServiceInterface;
use ReflectionProperty;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;

class ElenyumNormalizer extends AbstractObjectNormalizer
{
    /**
     * @var array
     */
    private static $setterAccessibleCache = [];

    /**
     * @var BaseServiceInterface|null
     */
    private ?BaseServiceInterface $service = null;

    public function setService(BaseServiceInterface $service): void
    {
        $this->service = $service;
    }

    /**
     * @return BaseServiceInterface|null
     */
    public function getService(): ?BaseServiceInterface
    {
        return $this->service;
    }

    /**
     * @param mixed $data
     * @param string|null $format
     * @return bool
     */
    public function supportsNormalization(mixed $data, string $format = null /* , array $context = [] */): bool
    {
        return parent::supportsNormalization($data, $format) && $this->supports($data::class);
    }

    /**
     * @param mixed $data
     * @param string $type
     * @param string|null $format
     * @return bool
     */
    public function supportsDenormalization(mixed $data, string $type, string $format = null /* , array $context = [] */): bool
    {
        return parent::supportsDenormalization($data, $type, $format) && $this->supports($type);
    }

    public function hasCacheableSupportsMethod(): bool
    {
        return __CLASS__ === static::class;
    }

    /**
     * Checks if the given class has any getter method.
     */
    private function supports(string $class): bool
    {
        $class = new \ReflectionClass($class);
        $methods = $class->getMethods(\ReflectionMethod::IS_PUBLIC);
        foreach ($methods as $method) {
            if ($this->isGetMethod($method)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks if a method's name matches /^(get|is|has).+$/ and can be called non-statically without parameters.
     */
    private function isGetMethod(\ReflectionMethod $method): bool
    {
        $methodLength = \strlen($method->name);

        return
            !$method->isStatic() &&
            (
                ((str_starts_with($method->name, 'get') && 3 < $methodLength) ||
                    (str_starts_with($method->name, 'is') && 2 < $methodLength) ||
                    (str_starts_with($method->name, 'has') && 3 < $methodLength)) &&
                0 === $method->getNumberOfRequiredParameters()
            )
            ;
    }

    protected function extractAttributes(object $object, string $format = null, array $context = []): array
    {
        $reflectionObject = new \ReflectionObject($object);
        $reflectionMethods = $reflectionObject->getMethods(\ReflectionMethod::IS_PUBLIC);

        $attributes = [];
        foreach ($reflectionMethods as $method) {
            if (!$this->isGetMethod($method)) {
                continue;
            }

            $attributeName = lcfirst(substr($method->name, str_starts_with($method->name, 'is') ? 2 : 3));

            if ($this->isAllowedAttribute($object, $attributeName, $format, $context)) {
                $attributes[] = $attributeName;
            }
        }

        return $attributes;
    }

    protected function getAttributeValue(object $object, string $attribute, string $format = null, array $context = []): mixed
    {
        $ucfirsted = ucfirst($attribute);

        $getter = 'get'.$ucfirsted;
        if (method_exists($object, $getter) && \is_callable([$object, $getter])) {
            return $object->$getter();
        }

        $isser = 'is'.$ucfirsted;
        if (method_exists($object, $isser) && \is_callable([$object, $isser])) {
            return $object->$isser();
        }

        $haser = 'has'.$ucfirsted;
        if (method_exists($object, $haser) && \is_callable([$object, $haser])) {
            return $object->$haser();
        }

        return null;
    }

    /**
     * @throws \ReflectionException
     */
    protected function setAttributeValue(object $object, string $attribute, mixed $value, string $format = null, array $context = [])
    {
        $setter = 'set'.ucfirst($attribute);
        $key = $object::class.':'.$setter;
        $value = $this->findTargetValue($object, $attribute, $value);

        if (!isset(self::$setterAccessibleCache[$key])) {
            self::$setterAccessibleCache[$key] = method_exists($object, $setter) && \is_callable([$object, $setter]) && !(new \ReflectionMethod($object, $setter))->isStatic();
        }

        if (self::$setterAccessibleCache[$key]) {
            $object->$setter($value);
        }
    }

    /**
     * @param object $object
     * @param string $attribute
     * @param mixed $value
     * @return mixed
     * @throws \ReflectionException
     */
    private function findTargetValue(object $object, string $attribute, mixed $value): mixed
    {
        $getManyAttribute = array_filter([
            (new ReflectionProperty($object, $attribute))->getAttributes(\Doctrine\ORM\Mapping\ManyToMany::class)[0] ?? null,
            (new ReflectionProperty($object, $attribute))->getAttributes(\Doctrine\ORM\Mapping\ManyToOne::class)[0] ?? null,
            (new ReflectionProperty($object, $attribute))->getAttributes(\Doctrine\ORM\Mapping\OneToMany::class)[0] ?? null,
            (new ReflectionProperty($object, $attribute))->getAttributes(\Doctrine\ORM\Mapping\OneToOne::class)[0] ?? null,
        ]);

        //Если есть признак что связано с другой сущностью то получаем все id и находим все связанные сущности
        if (!empty($getManyAttribute) && !empty($this->getService())) {
            $attr = $getManyAttribute[0];
            if(is_array($value)) {
                $ids = array_column($value, 'id');
            } else {
                $ids = $value;
            }
            $target = $attr->getArguments()['targetEntity'];
            if (!empty($target)) {
                $value = $this->getService()->getEntityManager()->getRepository($target)->findBy(['id' => $ids]);
            }
        }

        return $value;
    }
}