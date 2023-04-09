<?php

namespace Elenyum\ApiDocBundle\Service;

use App\Kernel;
use Doctrine\DBAL\Types\Types;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Elenyum\ApiDocBundle\Annotation\Access;
use Elenyum\ApiDocBundle\Command\Maker\ConfigEditor;
use Exception;
use Nette\PhpGenerator\PhpNamespace;
use Nette\PhpGenerator\Printer;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Process\Process;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Regex;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;

class CreatorService
{
    // Какие контроллеры и для какой сущности нужно создать
    public array $creator = [];

    public function __construct(
        private readonly Filesystem $filesystem,
        private readonly Kernel $kernel,
        private readonly ManagerRegistry $registry
    ) {
    }

    public function create(array $data): array
    {
        $modulePath = $this->kernel->getProjectDir().'/module';

        //Возвращаем созданные файлы
        $createdStructure = [];

        foreach ($data as $moduleData) {
            $version = ucfirst($moduleData['version']);
            $moduleName = $moduleData['name'];
            $fullPath = $modulePath.'/'.ucfirst($moduleName).'/'.$version;
            $moduleNamespace = 'Module\\'.ucfirst($moduleName).'\\'.$version;

            foreach ($moduleData['entity'] as $entity) {
                $entityClass = $this->createEntityClass($entity, $moduleNamespace);
                $entityFile = $this->printNamespace($entityClass);
                $dirEntityFile =  $fullPath.'/Entity/'.$entity['class'].'.php';

                $fileIs = 'created';
                if (file_exists($dirEntityFile)) {
                    $fileIs = 'updated';
                }

                /** Для сущностей сразу создаём файл */
                $this->filesystem->dumpFile($dirEntityFile, $entityFile);
                $createdStructure[$moduleName][$fileIs][] = $dirEntityFile;
            }

            foreach ($this->creator as $entityName => $data) {
                if (!empty($data['repository'])) {
                    $createdStructure[$moduleName]['created'][] = $this->copyTemplateToModule(
                        'Repository.php', $data['repository'].'.php', $fullPath.'/Repository',
                        ['{%uModuleName%}', '{%entityName%}', '{%repositoryName%}', '{%version%}'],
                        [ucfirst($moduleName), $entityName, $data['repository'], $version]
                    );
                }

                if (!empty($data['service'])) {
                    $createdStructure[$moduleName]['created'][] = $this->copyTemplateToModule(
                        'Service.php', $data['service'].'.php', $fullPath.'/Service',
                        ['{%uModuleName%}', '{%lModuleName%}', '{%entityName%}'],
                        [ucfirst($moduleName), lcfirst($moduleName), $entityName]
                    );
                }

                if (!empty($data['controllers'])) {
                    /** Eсли есть создаем контроллеры обходим и создаем в зависимости от типа (get, list, post ...) */
                    foreach ($data['controllers'] as $templateName => $controller) {
                        foreach ($controller as $controllerName) {
                            $createdStructure[$moduleName]['created'][] = $this->copyTemplateToModule(
                                $templateName, $controllerName, $fullPath.'/Controller',
                                [
                                    '{%uModuleName%}',
                                    '{%lModuleName%}',
                                    '{%entityName%}',
                                    '{%lEntityName%}',
                                    '{%version%}'
                                ],
                                [ucfirst($moduleName), lcfirst($moduleName), $entityName, lcfirst($entityName), mb_strtolower($version)]
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
            if (isset($createdStructure[$moduleName]['created'])) {
                $createdStructure[$moduleName]['created'] = array_filter($createdStructure[$moduleName]['created']);
            }
            if (isset($createdStructure[$moduleName]['updated'])) {
                $createdStructure[$moduleName]['updated'] = array_filter($createdStructure[$moduleName]['updated']);
            }
            $this->addDoctrineConfigure($moduleName, $version);

            //Обновление структуры бд
            $process = new Process(['php', 'bin/console', 'd:s:u', '-f', '--em=' . mb_strtolower($moduleName)]);
            $process->setWorkingDirectory($this->kernel->getProjectDir());
            $process->setTimeout(3600);
            $process->setIdleTimeout(60);
            $process->run();
        }

        return $createdStructure;
    }

    /**
     * @throws Exception
     */
    private function createEntityClass(array $entity, string $moduleNamespace): PhpNamespace
    {
        $entityClass = $moduleNamespace.'\\Entity';

        $namespace = new PhpNamespace($entityClass);
        $namespace->addUse('Elenyum\ApiDocBundle\Entity\BaseEntity');
        $namespace->addUse('Doctrine\ORM\Mapping', 'ORM');
        $namespace->addUse('Symfony\Component\Serializer\Annotation\Groups');
        $namespace->addUse('Symfony\Component\Validator\Constraints', 'Assert');
        $namespace->addUse('Elenyum\ApiDocBundle\Annotation\Access');

        $class = $namespace->addClass($entity['class']);
        $class->setExtends('BaseEntity');

        $class->addAttribute('ORM\Table', ['name' => mb_strtolower($entity['class'])]);

        if (empty($entity['roles'])) {
            throw new Exception('Undefined roles');
        }

        $get = $entity['roles'][mb_strtoupper(Access::GET)] ?? [];
        $post = $entity['roles'][mb_strtoupper(Access::POST)] ?? [];
        $put = $entity['roles'][mb_strtoupper(Access::PUT)] ?? [];
        $delete = $entity['roles'][mb_strtoupper(Access::DELETE)] ?? [];

        $class->addAttribute('Access', [$get, $post, $put, $delete]);

        $mapType = [
            'ManyToMany',
            'ManyToOne',
            'OneToOne',
            'OneToMany'
        ];

        foreach ($entity['properties'] as $property) {
            $propertyName = $property['name'];
            $columnType = $property['column']['type'];
            $phpPropertyType = $this->getPropertyType($property['column']['type']);
            $addProperty = $class->addProperty($propertyName);
            if ($columnType === 'id') {
                $addProperty->addAttribute('ORM\Id');
                $addProperty->addAttribute('ORM\GeneratedValue');
                $addProperty->addAttribute('ORM\Column', ['type' => Types::INTEGER]);
                $addProperty->setType('int');
            } elseif (in_array($columnType, $mapType)) {
                /** @todo Тут в зависимости от типа добавляем атрибуты ManyToMany, ManyToOne... */
                unset($property['column']['type']);

                if (isset($property['column']['nullable'])) {
                    $addProperty->addAttribute('ORM\JoinColumn', ['nullable' => (bool) $property['column']['nullable']]);
                }
                unset($property['column']['nullable']);

                $targetEntity = null;
                $targetEntityClassShort = null;
                if (!empty($property['column']['targetEntity'])) {
                    $targetEntityClassShort = $property['column']['targetEntity'];
                    $targetEntity = $entityClass.'\\'.$targetEntityClassShort;
                }

                $mappedBy = null;
                if (isset($property['column']['mappedBy']) && !empty($property['column']['mappedBy'])) {
                    $mappedBy = $property['column']['mappedBy'];
                }

                switch ($columnType) {
                    case 'ManyToMany':
                        $prop = [];
                        $namespace->addUse('Doctrine\Common\Collections\Collection');
                        $phpPropertyType = 'Collection';
                        if (!empty($mappedBy)) {
                            $prop['mappedBy'] = $mappedBy;
                        }
                        if (!empty($targetEntity)) {
                            $prop['targetEntity'] = $targetEntity;
                        }
                        $addProperty->addAttribute('ORM\ManyToMany', $prop);
                        break;
                    case 'ManyToOne':
                        $prop = [];
                        $namespace->addUse($targetEntity);
                        $phpPropertyType = $targetEntityClassShort;
                        if (!empty($targetEntityClassShort)) {
                            $prop['targetEntity'] = $targetEntityClassShort;
                        }
                        $addProperty->addAttribute('ORM\ManyToOne', $prop);
                        break;
                    case 'OneToOne':
                        $prop = [];

                        if (!empty($mappedBy)) {
                            $prop['mappedBy'] = $mappedBy;
                        }

                        $namespace->addUse($targetEntity);
                        $phpPropertyType = $targetEntityClassShort;
                        if (!empty($targetEntityClassShort)) {
                            $prop['targetEntity'] = $targetEntityClassShort;
                        }

                        $addProperty->addAttribute('ORM\OneToOne', $prop);
                        break;
                    case 'OneToMany':
                        $prop = [];
                        $namespace->addUse('Doctrine\Common\Collections\Collection');
                        $phpPropertyType = 'Collection';
                        if (!empty($mappedBy)) {
                            $prop['mappedBy'] = $mappedBy;
                        }
                        if (!empty($targetEntity)) {
                            $prop['targetEntity'] = $targetEntity;
                        }
                        $addProperty->addAttribute('ORM\OneToMany', $prop);
                        break;
                }

            } else {
                $addProperty->addAttribute('ORM\Column', $property['column']);

                $addProperty->setType($phpPropertyType);
            }

            if (isset($property['validator']) && !empty($property['validator'])) {
                foreach ($property['validator'] as $key => $validation) {
                    $validatorType = $this->getValidator($key);
                    $addProperty->addAttribute($validatorType, $validation);
                }
            }

            if (!empty($property['group'])) {
                $addProperty->addAttribute('Groups', [$property['group']]);
            }

            $group = array_unique($property['group']);

            $setter = $class->addMethod('set'.ucfirst($propertyName));
            $setter->addParameter($propertyName)->setType($phpPropertyType);
            $setter->addBody('$this->'.$propertyName.' = $'.$propertyName.';'.PHP_EOL.PHP_EOL.'return $this;');
            $setter->setReturnType('self');

            $getter = $class->addMethod('get'.ucfirst($propertyName));
            $getter->addBody('return $this->'.$propertyName.';');
            $getter->setReturnType($phpPropertyType);
        }

        // Собираем группы и по ним определяем какие контролеры нужны для работы
        foreach ($group as $group) {
            $this->creator[$entity['class']]['controllers'][$this->getControllerTemplate($group)][] = $this->getControllerName(
                $group,
                $entity['class']
            );
        }
        // Если есть контроллер и у модуля нет сервисов и репозитория
        if (!empty($this->creator[$entity['class']]['controllers'])) {
            $this->creator[$entity['class']]['service'] = $entity['class'].'Service';
            $this->creator[$entity['class']]['repository'] = $entity['class'].'Repository';
            $class->addAttribute('ORM\Entity', ['repositoryClass' => $moduleNamespace.'\Repository\\' . $entity['class'].'Repository']);
        } else {
            $class->addAttribute('ORM\Entity');
        }

        return $namespace;
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

    private function getValidator(string $name): string
    {
        $map = [
            'notNull' => NotNull::class,
            'length' => Length::class,
            'regex' => Regex::class,
            'count' => Count::class,
        ];

        return str_replace('Symfony\Component\Validator\Constraints', 'Assert', $map[$name]);
    }

    private function getPropertyType(string $type): string
    {
        $map = [
            'id' => 'int',
            'array' => 'array',
            'ascii_string' => 'string',
            'bigint' => 'int',
            'binary' => 'string',
            'blob' => 'string',
            'boolean' => 'bool',
            'date' => \DateTime::class,
            'date_immutable' => \DateTimeImmutable::class,
            'dateinterval' => \DateInterval::class,
            'datetime' => \DateTime::class,
            'datetime_immutable' => \DateTimeImmutable::class,
            'datetimetz' => \DateTime::class,
            'datetimetz_immutable' => \DateTimeImmutable::class,
            'decimal' => 'string',
            'float' => 'float',
            'guid' => 'string',
            'integer' => 'int',
            'int' => 'int',
            'json' => 'array',
            'object' => 'array',
            'simple_array' => 'array',
            'smallint' => 'int',
            'string' => 'string',
            'text' => 'string',
            'time' => \DateTime::class,
            'time_immutable' => \DateTimeImmutable::class,
        ];

        return $map[$type] ?? $type;
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

    private function createDir(string $dirPath, string $dirName): void
    {
        try {
            $this->filesystem->mkdir(
                Path::normalize($dirPath.'/'.$dirName),
            );
        } catch (IOExceptionInterface $exception) {
            echo "An error occurred while creating your directory at ".$exception->getPath();
        }
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

    private function addDoctrineConfigure(string $moduleName, string $version = 'V1'): void
    {
        $configDir = $this->kernel->getProjectDir() . '/config/packages/doctrine.yaml';
        $config = new ConfigEditor($configDir);

        $value = $config->parse();
        $value['doctrine']['orm']['entity_managers'][lcfirst($moduleName)] = [
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

        $config->save($value);
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
}