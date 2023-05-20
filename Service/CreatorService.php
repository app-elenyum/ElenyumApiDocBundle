<?php

namespace Elenyum\ApiDocBundle\Service;

use App\Kernel;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Elenyum\ApiDocBundle\Util\Editor\Entity\CreateEntity;
use Exception;
use Nette\PhpGenerator\PhpNamespace;
use Nette\PhpGenerator\Printer;
use Symfony\Component\Filesystem\Filesystem;

use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Component\Yaml\Yaml;

class CreatorService
{
    // Какие контроллеры и для какой сущности нужно создать
    public array $creator = [];

    public function __construct(
        private readonly Filesystem $filesystem,
        private readonly Kernel $kernel,
        private readonly ManagerRegistry $registry,
        private readonly CreateEntity $createEntity
    ) {
    }

    /**
     * @throws Exception
     */
    public function create(array $data): array
    {
        $sqlMigration = [];
        $modulePath = $this->getProjectDir().'/module';

        //Возвращаем созданные файлы
        $generatedFiles = [];

        //Если есть сущности для обновления то обновляем иначе пропускаем
        $updateEntity = 0;

        foreach ($data as $oldModuleName => $moduleData) {
            $version = ucfirst($moduleData['version']);
            $moduleName = $moduleData['name'];
            $fullPath = $modulePath.'/'.ucfirst($moduleName).'/'.$version;
            $moduleNamespace = 'Module\\'.ucfirst($moduleName).'\\'.$version;

            foreach ($moduleData['entity'] as $entity) {
                if (isset($entity['hash'])) {
                    unset($entity['hash']);
                }
                $updateEntity++;
                $createEntity = $this->createEntity->createEntityClass($entity, $moduleNamespace);
                $entityFile = $this->printNamespace($createEntity->getNamespace());
                $dirEntityFile = $fullPath.'/Entity/'.$entity['class'].'.php';

                $fileIs = 'created';
                if (isset($entity['oldClassName']) && $entity['class'] !== $entity['oldClassName']) {
                    $this->deleteEntity($entity['oldClassName'], $oldModuleName, $version);
                }
                if (file_exists($dirEntityFile)) {
                    $fileIs = 'updated';
                }

                /** Для сущностей сразу создаём файл */
                $this->filesystem->dumpFile($dirEntityFile, $entityFile);
                $generatedFiles[$moduleName][$fileIs][] = $dirEntityFile;

                /** @todo не создавать creator, а сразу по группам создавать контроллеры, сервис и репозиторий создавать по умолчанию */
                foreach ($createEntity->getGroups() as $group) {
                    $this->creator[$entity['class']]['controllers'][$this->getControllerTemplate(
                        $group
                    )][] = $this->getControllerName(
                        $group,
                        $entity['class']
                    );
                }
                $this->creator[$entity['class']]['service'] = $entity['class'].'Service';
                $this->creator[$entity['class']]['repository'] = $entity['class'].'Repository';
            }

            if ($updateEntity > 0) {
                foreach ($this->creator as $entityName => $data) {
                    $generatedFiles[$moduleName]['created'][] = $this->copyTemplateToModule(
                        'Repository.php', $data['repository'].'.php', $fullPath.'/Repository',
                        ['{%uModuleName%}', '{%entityName%}', '{%repositoryName%}', '{%version%}'],
                        [ucfirst($moduleName), $entityName, $data['repository'], $version]
                    );

                    $generatedFiles[$moduleName]['created'][] = $this->copyTemplateToModule(
                        'Service.php', $data['service'].'.php', $fullPath.'/Service',
                        ['{%uModuleName%}', '{%moduleName%}', '{%entityName%}'],
                        [ucfirst($moduleName), $moduleName, $entityName]
                    );

                    if (!empty($data['controllers'])) {
                        /** Eсли есть создаем контроллеры обходим и создаем в зависимости от типа (get, list, post ...) */
                        foreach ($data['controllers'] as $templateName => $controller) {
                            foreach ($controller as $controllerName) {
                                $generatedFiles[$moduleName]['created'][] = $this->copyTemplateToModule(
                                    $templateName, $controllerName, $fullPath.'/Controller',
                                    [
                                        '{%uModuleName%}',
                                        '{%lModuleName%}',
                                        '{%entityName%}',
                                        '{%lEntityName%}',
                                        '{%version%}',
                                    ],
                                    [
                                        ucfirst($moduleName),
                                        lcfirst($moduleName),
                                        $entityName,
                                        lcfirst($entityName),
                                        mb_strtolower($version),
                                    ]
                                );
                            }
                        }
                    }
                }


                $this->copyTemplateToModule(
                    'README.md', 'README.md', $fullPath.'/',
                    ['{%lModuleName%}'],
                    [lcfirst($moduleName)]
                );

                // Сбрасываем creator; @todo лучше использовать переменную вместо свойства класса
                $this->creator = [];
                // Отфильтровываем пустые значения @todo по хорошему пустых значений не должно быть
                if (isset($generatedFiles[$moduleName]['created'])) {
                    $generatedFiles[$moduleName]['created'] = array_filter($generatedFiles[$moduleName]['created']);
                }
                if (isset($generatedFiles[$moduleName]['updated'])) {
                    $generatedFiles[$moduleName]['updated'] = array_filter($generatedFiles[$moduleName]['updated']);
                }

                $em = $this->getEntityManager($moduleName);
                $metadatas = $em->getMetadataFactory()->getAllMetadata();
                $schemaTool = new SchemaTool($em);

                /** @todo Для отоброжения выполненых SQL запросов */
                $sqls = $schemaTool->getUpdateSchemaSql($metadatas, true);
                if (!empty($sqls)) {
                    $sqlMigration[] = $sqls;
                }

                $schemaTool->updateSchema($metadatas, true);
            }
        }

        return ['generatedFiles' => $generatedFiles, 'sqls' => $sqlMigration];
    }

    public function createStructure(string $moduleName, string $version = 'V1'): bool
    {
        $modulePath = $this->getProjectDir().'/module';
        $fullPath = $modulePath.'/'.ucfirst($moduleName).'/'.$version;

        $this->getFilesystem()->mkdir($fullPath.'/Entity');
        $this->getFilesystem()->mkdir($fullPath.'/Controller');
        $this->getFilesystem()->mkdir($fullPath.'/Repository');
        $this->getFilesystem()->mkdir($fullPath.'/Service');

        return true;
    }

    private function getControllerTemplate(string $group): string
    {
        $map = [
            'GET' => 'GetController.php',
            'LIST' => 'ListController.php',
            'POST_RES' => 'PostController.php',
            'POST_REQ' => 'PostController.php',
            'PUT_RES' => 'PutController.php',
            'PUT_REQ' => 'PutController.php',
            'DEL_RES' => 'DeleteController.php',
        ];

        return $map[$group];
    }

    private function getControllerName(string $group, string $entityClass): string
    {
        $type = $this->getControllerTemplate($group);

        return ucfirst($entityClass).ucfirst($type);
    }

    /**
     * @param PhpNamespace $namespace
     * @return string
     */
    private function printNamespace(PhpNamespace $namespace): string
    {
        $printer = new Printer(); // or PsrPrinter
        $printer->setTypeResolving(false);
        $printer->linesBetweenMethods = 1;

        return "<?php \n".$printer->printNamespace($namespace);
    }

    /**
     * Если файл есть то не создаем его и не переписываем (если нужно изменить то удаляем)
     *
     * @param string $templateName
     * @param string $fileName
     * @param string $path
     * @param array $search
     * @param array $replace
     * @return null|string
     */
    private function copyTemplateToModule(
        string $templateName,
        string $fileName,
        string $path,
        array $search,
        array $replace
    ): ?string {
        if ($this->filesystem->exists($path.'/'.$fileName)) {
            return null;
        }
        $content = str_replace(
            $search,
            $replace,
            file_get_contents(__DIR__.'/templates/'.$templateName.'.tmp')
        );

        $this->filesystem->appendToFile($path.'/'.$fileName, $content);

        return $path.'/'.$fileName;
    }

    public function addDoctrineConfigure(string $moduleName, string $version = 'V1'): void
    {
        $configDir = $this->getProjectDir().'/config/packages/doctrine.yaml';
        $file = fopen($configDir, 'r+');

        if (flock($file, LOCK_EX)) {
            $value = Yaml::parse(fread($file, filesize($configDir)));
            $value['doctrine']['orm']['entity_managers'][$moduleName] = [
                "connection" => 'default',
                "mappings" => [
                    ucfirst($moduleName) => [
                        "is_bundle" => false,
                        "type" => "attribute",
                        "dir" => '%kernel.project_dir%/module/'.ucfirst($moduleName).'/'.$version.'/Entity',
                        "prefix" => 'Module\\'.ucfirst($moduleName).'\\'.$version.'\Entity',
                        "alias" => ucfirst($moduleName)."Module",
                    ],
                ],
            ];
            ftruncate($file, 0);
            rewind($file);
            fwrite($file, Yaml::dump($value, 10));
            fflush($file);
            flock($file, LOCK_UN);
        } else {
            echo "Не удалось получить блокировку!";
        }
    }

    public function deleteTableToByModule(string $module): void
    {
        $em = $this->getEntityManager($module);
        $metadatas = $em->getMetadataFactory()->getAllMetadata();
        $schemaTool = new SchemaTool($em);

        $schemaTool->dropSchema($metadatas);
    }

    public function deleteDoctrineConfigure(string $moduleName, string $version = 'V1'): void
    {
        $configDir = $this->getProjectDir().'/config/packages/doctrine.yaml';
        $file = fopen($configDir, 'r+');

        if (flock($file, LOCK_EX)) {
            $value = Yaml::parse(fread($file, filesize($configDir)));
            unset($value['doctrine']['orm']['entity_managers'][$moduleName]);
            ftruncate($file, 0);
            rewind($file);
            fwrite($file, Yaml::dump($value, 10));
            fflush($file);
            flock($file, LOCK_UN);
        } else {
            echo "Не удалось получить блокировку!";
        }
    }

    /**
     * Возвращает entity manager
     *
     * @param string $name
     * @return ObjectManager
     */
    public function getEntityManager(string $name): ObjectManager
    {
        return $this->registry->getManager($name);
    }

    /**
     * @return \Symfony\Component\Filesystem\Filesystem
     */
    public function getFilesystem(): Filesystem
    {
        return $this->filesystem;
    }

    /**
     * @return string
     */
    public function getProjectDir(): string
    {
        return $this->kernel->getProjectDir();
    }

    public function deleteEntity(string $entityName, string $moduleName, $version = 'V1'): bool
    {
        $fullPathToEntity = $this->getProjectDir().'/module/'.$moduleName.'/'.$version.'/Entity/'.$entityName.'.php';
        $fullPathToRepository = $this->getProjectDir().'/module/'.$moduleName.'/'.$version.'/Repository/'.$entityName.'Repository.php';
        $fullPathToService = $this->getProjectDir().'/module/'.$moduleName.'/'.$version.'/Service/'.$entityName.'Service.php';
        $fullPathToGetController = $this->getProjectDir().'/module/'.$moduleName.'/'.$version.'/Controller/'.$entityName.'GetController.php';
        $fullPathToListController = $this->getProjectDir().'/module/'.$moduleName.'/'.$version.'/Controller/'.$entityName.'ListController.php';
        $fullPathToPostController = $this->getProjectDir().'/module/'.$moduleName.'/'.$version.'/Controller/'.$entityName.'PostController.php';
        $fullPathToPutController = $this->getProjectDir().'/module/'.$moduleName.'/'.$version.'/Controller/'.$entityName.'PutController.php';
        $fullPathToDeleteController = $this->getProjectDir().'/module/'.$moduleName.'/'.$version.'/Controller/'.$entityName.'DeleteController.php';

        $this->getFilesystem()->remove($fullPathToEntity);
        $this->getFilesystem()->remove($fullPathToRepository);
        $this->getFilesystem()->remove($fullPathToService);
        $this->getFilesystem()->remove($fullPathToGetController);
        $this->getFilesystem()->remove($fullPathToListController);
        $this->getFilesystem()->remove($fullPathToPostController);
        $this->getFilesystem()->remove($fullPathToPutController);
        $this->getFilesystem()->remove($fullPathToDeleteController);

        $em = $this->getEntityManager($moduleName);
        $sql = 'DROP TABLE IF EXISTS '.mb_strtolower($entityName);
        $connection = $em->getConnection();
        $stmt = $connection->prepare($sql);
        $stmt->execute();

        return true;
    }

    public function deleteModule(string $moduleName): bool
    {
        $this->deleteTableToByModule($moduleName);
        $fullPathToEntity = $this->getProjectDir().'/module/'.$moduleName;
        $this->getFilesystem()->remove($fullPathToEntity);
        $this->deleteDoctrineConfigure($moduleName);

        return true;
    }
}