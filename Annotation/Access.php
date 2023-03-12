<?php

/*
 * This file is part of the ElenyumApiDocBundle package.
 *
 * (c) Elenyum
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Elenyum\ApiDocBundle\Annotation;

use Attribute;
use Elenyum\ApiDocBundle\Entity\BaseEntity;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @Annotation
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class Access
{
    public const GET = 'get';
    public const POST = 'post';
    public const PUT = 'put';
    public const DELETE = 'delete';

    public function __construct(
        private readonly array $get,
        private readonly array $post,
        private readonly array $put,
        private readonly array $delete,
    ) {
    }

    /**
     * @param UserInterface|null $user
     * @param string $type - 'get', 'post', 'put', 'delete'
     * @param BaseEntity $entity
     * @return bool
     */
    public function isAllow(?UserInterface $user, string $type, BaseEntity $entity): bool
    {
        $prop = $this->{mb_strtolower($type)};
        // Если права не указаны значит доступна всем
        if (empty($prop)) {
            return true;
        // Если права указаны, а пользователя нет значит нет доступа
        } elseif (empty($user)) {
            return false;
        }

        // Если есть в сущности метод isAllow то проверяем по нему права
        if (method_exists($entity, 'isAllow')) {
            return $entity->isAllow($user, $type);
        }

        return count(array_intersect($user->getRoles(), $prop)) > 0;
    }
}
