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

/**
 * @Annotation
 *
 * Если добавлен данный атрибут то класс не доступен для редактирования
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class NotEditable
{
}
