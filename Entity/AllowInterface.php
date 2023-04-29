<?php

/*
 * This file is part of the ElenyumApiDocBundle package.
 *
 * (c) Elenyum
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Elenyum\ApiDocBundle\Entity;

use Symfony\Component\Security\Core\User\UserInterface;

/**
 * check in UserVoter
 */
interface AllowInterface
{
    /**
     * @param UserInterface $user
     * @param string $type - 'GET', 'POST', 'PUT', 'DELETE'
     * @return bool
     */
    public function isAllow(UserInterface $user, string $type): bool;
}