<?php

namespace Elenyum\ApiDocBundle\Command\Maker\Command;

use App\Kernel;
use Elenyum\ApiDocBundle\Command\Maker\ConfigEditor;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

#[AsCommand(
    name: 'module:create',
    description: 'This command create new empty module',
    aliases: ['md:c'],
    hidden: false
)]
class CreateModule extends Command
{
    public function __construct(
        private readonly Filesystem $filesystem,
        private readonly Kernel $kernel
    ) {
        parent::__construct();
    }

    protected function configure()
    {
        $this->addArgument('moduleName', InputArgument::REQUIRED, 'The module name of the module');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $version = 'v1';
        $moduleName = $input->getArgument('moduleName');

        $dir = $this->kernel->getProjectDir() . '/module';

        $fullPath = $dir.'/'.$moduleName.'/'.ucfirst($version);

        $this->createDir($dir, $moduleName);
        $this->createDir($fullPath, 'Controller');
        $io = new SymfonyStyle($input, $output);

        $nameEntity = $io->ask('Enter name entity', $moduleName);
        $nameRepository = $nameEntity.'Repository';

        $this->createDir($fullPath, 'Entity');
        $this->copyTemplateToModule(
            'Entity.php', $nameEntity.'.php', $fullPath.'/Entity',
            ['{%uModuleName%}', '{%lModuleName%}', '{%entityName%}', '{%repositoryName%}'],
            [ucfirst($moduleName), lcfirst($moduleName), $nameEntity, $nameRepository]
        );

        $this->createDir($fullPath, 'Repository');
        $this->copyTemplateToModule(
            'Repository.php', $nameRepository.'.php', $fullPath.'/Repository',
            ['{%uModuleName%}', '{%lModuleName%}', '{%entityName%}', '{%repositoryName%}'],
            [ucfirst($moduleName), lcfirst($moduleName), $nameEntity, $nameRepository]
        );

        $nameService = $nameEntity.'Service';
        $this->createDir($fullPath, 'Service');
        $this->copyTemplateToModule(
            'Service.php', $nameService.'.php', $fullPath.'/Service',
            ['{%uModuleName%}', '{%lModuleName%}', '{%entityName%}', '{%repositoryName%}'],
            [ucfirst($moduleName), lcfirst($moduleName), $nameEntity, $nameRepository]
        );

        $this->copyTemplateToModule(
            'README.md', 'README.md', $dir.'/'.$moduleName,
            ['{%lModuleName%}'],
            [lcfirst($moduleName)]
        );

        $controllers = [
            'DeleteController.php',
            'GetController.php',
            'ListController.php',
            'PostController.php',
            'PutController.php',
        ];
        foreach ($controllers as $controller) {
            $this->copyTemplateToModule(
                $controller, $controller, $fullPath.'/Controller',
                [
                    '{%uModuleName%}',
                    '{%lModuleName%}',
                    '{%repositoryName%}',
                    '{%entityName%}',
                    '{%lEntityName%}',
                ],
                [ucfirst($moduleName), lcfirst($moduleName), $nameRepository, $nameEntity, lcfirst($nameEntity)]
            );
        }

        $this->createDir($fullPath, 'Service');
        $this->addDoctrineConfigure($moduleName);

        $output->writeln('Module created: '.$moduleName);

        return Command::SUCCESS;
    }

    private function copyTemplateToModule(
        string $templateName,
        string $fileName,
        string $path,
        array $search,
        array $replace
    ): void {
        $controller = file_get_contents(__DIR__.'/templates/'.$templateName.'.tmp');
        $content = str_replace($search, $replace, $controller);
        $this->filesystem->appendToFile($path.'/'.$fileName, $content);
    }

    private function addDoctrineConfigure(string $moduleName): void
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
                    "dir" => '%kernel.project_dir%/module/'.ucfirst($moduleName).'/V1/Entity',
                    "prefix" => 'Module\\'.ucfirst($moduleName).'\V1\Entity',
                    "alias" => ucfirst($moduleName)."Module",
                ],
            ],
        ];

        $config->save($value);
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
}