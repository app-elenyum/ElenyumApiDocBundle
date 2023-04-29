<?php

/*
 * This file is part of the ElenyumApiDocBundle package.
 *
 * (c) Elenyum
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Elenyum\ApiDocBundle\Tests\Functional\Controller;

use Elenyum\ApiDocBundle\Annotation\Model;
use Elenyum\ApiDocBundle\Tests\Functional\Entity\JMSComplex;
use Elenyum\ApiDocBundle\Tests\Functional\Entity\JMSDualComplex;
use Elenyum\ApiDocBundle\Tests\Functional\Entity\JMSNamingStrategyConstraints;
use Elenyum\ApiDocBundle\Tests\Functional\Entity\JMSUser;
use Elenyum\ApiDocBundle\Tests\Functional\Entity\NestedGroup\JMSChat;
use Elenyum\ApiDocBundle\Tests\Functional\Entity\NestedGroup\JMSChatRoomUser;
use Elenyum\ApiDocBundle\Tests\Functional\Entity\NestedGroup\JMSChatUser;
use Elenyum\ApiDocBundle\Tests\Functional\Entity\NestedGroup\JMSPicture;
use Elenyum\ApiDocBundle\Tests\Functional\Entity\VirtualProperty;
use OpenApi\Annotations as OA;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(host="api.example.com")
 */
class JMSController
{
    /**
     * @Route("/api/jms", methods={"GET"})
     * @OA\Response(
     *     response=200,
     *     description="Success",
     *     @Model(type=JMSUser::class)
     * )
     */
    public function userAction()
    {
    }

    /**
     * @Route("/api/yaml", methods={"GET"})
     * @OA\Response(
     *     response=200,
     *     description="Success",
     *     @Model(type=VirtualProperty::class)
     * )
     */
    public function yamlAction()
    {
    }

    /**
     * @Route("/api/jms_complex", methods={"GET"})
     * @OA\Response(
     *     response=200,
     *     description="Success",
     *     @Model(type=JMSComplex::class, groups={"list", "details", "User" : {"list"}})
     * )
     */
    public function complexAction()
    {
    }

    /**
     * @Route("/api/jms_complex_dual", methods={"GET"})
     * @OA\Response(
     *     response=200,
     *     description="Success",
     *     @Model(type=JMSDualComplex::class, groups={"Default", "complex" : {"User" : {"details"}}})
     * )
     */
    public function complexDualAction()
    {
    }

    /**
     * @Route("/api/jms_naming_strategy", methods={"GET"})
     * @OA\Response(
     *     response=200,
     *     description="Success",
     *     @Model(type=JMSNamingStrategyConstraints::class, groups={"Default"})
     * )
     */
    public function namingStrategyConstraintsAction()
    {
    }

    /**
     * @Route("/api/jms_chat", methods={"GET"})
     * @OA\Response(
     *     response=200,
     *     description="Success",
     *     @Model(type=JMSChat::class, groups={"Default", "members" : {"mini"}})
     * )
     */
    public function chatAction()
    {
    }

    /**
     * @Route("/api/jms_picture", methods={"GET"})
     * @OA\Response(
     *     response=200,
     *     description="Success",
     *     @Model(type=JMSPicture::class, groups={"mini"})
     * )
     */
    public function pictureAction()
    {
    }

    /**
     * @Route("/api/jms_mini_user", methods={"GET"})
     * @OA\Response(
     *     response=200,
     *     description="Success",
     *     @Model(type=JMSChatUser::class, groups={"mini"})
     * )
     */
    public function minUserAction()
    {
    }

    /**
     * @Route("/api/jms_mini_user_nested", methods={"GET"})
     * @OA\Response(
     *     response=200,
     *     description="Success",
     *     @Model(type=JMSChatRoomUser::class, groups={"mini", "friend": {"living":{"Default"}}})
     * )
     */
    public function minUserNestedAction()
    {
    }
}
