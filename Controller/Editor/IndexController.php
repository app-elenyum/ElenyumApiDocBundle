<?php

namespace Elenyum\ApiDocBundle\Controller\Editor;

use Doctrine\ORM\EntityManagerInterface;
use Elenyum\ApiDocBundle\Render\Html\AssetsMode;
use Elenyum\ApiDocBundle\Service\CreatorService;
use Elenyum\ApiDocBundle\Service\EditorService;
use Elenyum\ApiDocBundle\Util\Slugify;
use Module\User\V1\Entity\Role;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class IndexController extends AbstractController
{
    public const VIEW = 'view';
    public const GET_MODULES = 'get_modules';
    public const GET_ROLES = 'get_roles';
    public const ADD_ROLE = 'add_role';
    public const DEL_ROLE = 'del_role';
    public const GET_GROUPS = 'get_groups';
    public const GET_TYPES = 'get_types';
    public const SAVE = 'save';

    public function __construct(
        public EditorService $editorService,
        public CreatorService $creatorService
    ) {
    }

    /**
     * @param Request $request
     * @param EntityManagerInterface $em
     * @return Response|void
     * @throws \Exception
     */
    public function __invoke(Request $request, EntityManagerInterface $em)
    {
        $typeRequest = $request->get('r', 'view');

        switch ($typeRequest) {
            case self::VIEW:
                return $this->render('@ElenyumApiDoc/Module/edit.html.twig', [
                    'assets_mode' => AssetsMode::BUNDLE,
                ]);

            case self::GET_MODULES:
                return $this->json([
                    'success' => true,
                    'modules' => $this->editorService->getModules(),
                ]);

            case self::GET_ROLES:
                return $this->json([
                    'success' => true,
                    'roles' => $em->getRepository(Role::class)->findAll(),
                ]);

            case self::ADD_ROLE:
                $content = $request->getContent();
                $decode = json_decode($content, JSON_OBJECT_AS_ARRAY);
                $name = $decode['name'] ?? '';
                if (!empty($name)) {
                    $role = new Role();
                    $role->setName($name);
                    $systemName = 'ROLE_'.mb_strtoupper(Slugify::create($name));
                    $role->setSystemName($systemName);
                    $em->persist($role);
                    $em->flush();

                    return $this->json([
                        'success' => true,
                        'role' => $role->toArray('GET_EDITOR_ADD'),
                    ]);
                }
                break;

            case self::DEL_ROLE:
                $id = $request->get('id');
                if (empty($id)) {
                    return $this->json([
                        'success' => false,
                        'message' => 'not found id for deleted role',
                    ]);
                }
                $role = $em->find(Role::class, $id);
                if (!empty($role)) {
                    $em->remove($role);
                    $em->flush();

                    return $this->json([
                        'success' => true,
                    ]);
                }

                return $this->json([
                    'success' => false,
                    'message' => 'undefined error',
                ]);
            case self::GET_GROUPS:
                return $this->json([
                    'success' => true,
                    'groups' => $this->editorService->getGroups(),
                ]);
            case self::GET_TYPES:
                //Возвращаем типы данных
                return $this->json([
                    'success' => true,
                    'types' => $this->editorService->getTypes(),
                ]);
            case self::SAVE:
                $content = $request->getContent();
                $decode = json_decode($content, JSON_OBJECT_AS_ARRAY);
                $createdFiles = $this->creatorService->create($decode);

                return $this->json([
                    'success' => true,
                    'result' => $createdFiles,
                ]);
        }

        return $this->json([
            'success' => false,
            'message' => 'Not found error',
        ]);
    }
}