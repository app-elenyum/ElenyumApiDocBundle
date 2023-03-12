<?php

namespace Elenyum\ApiDocBundle\Command\Maker\Command;

use App\Kernel;
use Elenyum\ApiDocBundle\Command\Maker\ConfigEditor;
use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

#[AsCommand(
    name: 'module:init',
    description: 'This command create module user and setting structure project, after init you need reload composer dump-autoload',
    aliases: ['md:c'],
    hidden: false
)]
class InitModule extends Command
{
    public function __construct(
        private readonly Filesystem $filesystem,
        private readonly Kernel $kernel
    ) {
        parent::__construct();
    }

    /**
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $dir = $this->kernel->getProjectDir();

        $this->configureServices();
        $this->configureRoutes();
        $this->addComposerConfig();
        $this->addDoctrineConfigure();
        $this->addResetPasswordConfigure();
        $this->configureSecurity();

        $this->filesystem->copy(__DIR__.'/templates/config/elenyum_api_doc.yaml', $dir.'/config/packages/elenyum_api_doc.yaml');
        $this->filesystem->copy(__DIR__.'/templates/config/routes/elenyum_api_doc.yaml', $dir.'/config/routes/elenyum_api_doc.yaml');

        /** Create event START */
        $this->filesystem->copy(__DIR__.'/templates/EventListener/ExceptionListener.php.tmp', $dir .'/src/EventListener/ExceptionListener.php');
        /** Create event END */

        /** Create module User START */
        $this->filesystem->copy(__DIR__.'/templates/module/User/V1/Controller/Authentication/LoginController.php.tmp', $dir .'/module/User/V1/Controller/Authentication/LoginController.php');
        $this->filesystem->copy(__DIR__.'/templates/module/User/V1/Controller/Authentication/LogoutController.php.tmp', $dir .'/module/User/V1/Controller/Authentication/LogoutController.php');
        $this->filesystem->copy(__DIR__.'/templates/module/User/V1/Controller/Authentication/ResetPasswordController.php.tmp', $dir .'/module/User/V1/Controller/Authentication/ResetPasswordController.php');
        $this->filesystem->copy(__DIR__.'/templates/module/User/V1/Controller/Authentication/ResetPasswordGenerateTokenController.php.tmp', $dir .'/module/User/V1/Controller/Authentication/ResetPasswordGenerateTokenController.php');

        $this->filesystem->copy(__DIR__.'/templates/module/User/V1/Controller/UserDeleteController.php.tmp', $dir .'/module/User/V1/Controller/UserDeleteController.php');
        $this->filesystem->copy(__DIR__.'/templates/module/User/V1/Controller/UserGetController.php.tmp', $dir .'/module/User/V1/Controller/UserGetController.php');
        $this->filesystem->copy(__DIR__.'/templates/module/User/V1/Controller/UserListController.php.tmp', $dir .'/module/User/V1/Controller/UserListController.php');
        $this->filesystem->copy(__DIR__.'/templates/module/User/V1/Controller/UserPostController.php.tmp', $dir .'/module/User/V1/Controller/UserPostController.php');
        $this->filesystem->copy(__DIR__.'/templates/module/User/V1/Controller/UserPutController.php.tmp', $dir .'/module/User/V1/Controller/UserPutController.php');

        $this->filesystem->copy(__DIR__.'/templates/module/User/V1/Controller/RoleDeleteController.php.tmp', $dir .'/module/User/V1/Controller/RoleDeleteController.php');
        $this->filesystem->copy(__DIR__.'/templates/module/User/V1/Controller/RoleGetController.php.tmp', $dir .'/module/User/V1/Controller/RoleGetController.php');
        $this->filesystem->copy(__DIR__.'/templates/module/User/V1/Controller/RoleListController.php.tmp', $dir .'/module/User/V1/Controller/RoleListController.php');
        $this->filesystem->copy(__DIR__.'/templates/module/User/V1/Controller/RolePostController.php.tmp', $dir .'/module/User/V1/Controller/RolePostController.php');
        $this->filesystem->copy(__DIR__.'/templates/module/User/V1/Controller/RolePutController.php.tmp', $dir .'/module/User/V1/Controller/RolePutController.php');

        $this->filesystem->copy(__DIR__.'/templates/module/User/V1/Entity/ResetPasswordRequest.php.tmp', $dir .'/module/User/V1/Entity/ResetPasswordRequest.php');
        $this->filesystem->copy(__DIR__.'/templates/module/User/V1/Entity/User.php.tmp', $dir .'/module/User/V1/Entity/User.php');
        $this->filesystem->copy(__DIR__.'/templates/module/User/V1/Entity/Role.php.tmp', $dir .'/module/User/V1/Entity/Role.php');

        $this->filesystem->copy(__DIR__.'/templates/module/User/V1/Event/UserBeforePostEvent.php.tmp', $dir .'/module/User/V1/Event/UserBeforePostEvent.php');
        $this->filesystem->copy(__DIR__.'/templates/module/User/V1/Event/UserBeforeResetEvent.php.tmp', $dir .'/module/User/V1/Event/UserBeforeResetEvent.php');

        $this->filesystem->copy(__DIR__.'/templates/module/User/V1/Repository/ResetPasswordRequestRepository.php.tmp', $dir .'/module/User/V1/Repository/ResetPasswordRequestRepository.php');
        $this->filesystem->copy(__DIR__.'/templates/module/User/V1/Repository/UserRepository.php.tmp', $dir .'/module/User/V1/Repository/UserRepository.php');

        $this->filesystem->copy(__DIR__.'/templates/module/User/V1/Service/UserService.php.tmp', $dir .'/module/User/V1/Service/UserService.php');
        $this->filesystem->copy(__DIR__.'/templates/module/User/V1/Service/RoleService.php.tmp', $dir .'/module/User/V1/Service/RoleService.php');

        $this->filesystem->copy(__DIR__.'/templates/module/User/V1/Security/UserVoter.php.tmp', $dir .'/module/User/V1/Security/UserVoter.php');

        $this->filesystem->copy(__DIR__.'/templates/module/User/README.md.tmp', $dir .'/module/User/README.md');
        /** Create module User END */

        $process = new Process(['composer', 'dump-autoload']);
        $process->setWorkingDirectory($dir);
        $process->mustRun();

        $process = new Process(['php', 'bin/console', 'd:s:u', '-f', "--em='user'"]);
        $process->setWorkingDirectory($dir);
        $process->setTimeout(3600);
        $process->setIdleTimeout(60);
        $process->run();

        echo $process->getOutput();

        return Command::SUCCESS;
    }

    private function addComposerConfig(): void
    {
        $dir = $this->kernel->getProjectDir();

        $myFile = json_decode(file_get_contents($dir.'/composer.json'));
        $myFile->autoload->{"psr-4"}->{"Module\\"} = 'module/';
        file_put_contents($dir.'/composer.json', json_encode($myFile, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
    }

    /**
     * @throws Exception
     */
    private function addDoctrineConfigure(): void
    {
        $configDir = $this->kernel->getProjectDir().'/config/packages/doctrine.yaml';
        if (!$this->filesystem->exists($configDir)) {
            throw new Exception('No`t install orm pack. Need install: composer require symfony/orm-pack');
        }
        $config = new ConfigEditor($configDir);
        $value = $config->parse();

        unset($value['doctrine']['dbal']['url']);
        unset($value['doctrine']['orm']['auto_generate_proxy_classes']);
        unset($value['doctrine']['orm']['naming_strategy']);
        unset($value['doctrine']['orm']['auto_mapping']);
        unset($value['doctrine']['orm']['mappings']);
        $value['doctrine']['dbal']['connections']['default']['url'] = '%env(resolve:DATABASE_URL)%';
        $value['doctrine']['orm']['default_entity_manager'] = 'user';
        $value['doctrine']['orm']['entity_managers']['user'] = [
            'connection' => 'default',
            'mappings' => [
                'User' => [
                    'is_bundle' => false,
                    'type' => 'attribute',
                    'dir' => '%kernel.project_dir%/module/User/V1/Entity',
                    'prefix' => 'Module\User\V1\Entity',
                    'alias' => 'UserModule',
                ],
            ],
        ];

        $config->save($value);
    }

    private function addResetPasswordConfigure(): void
    {
        $configDir = $this->kernel->getProjectDir().'/config/packages/reset_password.yaml';
        if (!$this->filesystem->exists($configDir)) {
            throw new Exception(
                'No`t install reset-password-bundle. Need install: composer require symfonycasts/reset-password-bundle'
            );
        }
        $config = new ConfigEditor($configDir);
        $value = $config->parse();

        $value['symfonycasts_reset_password']['request_password_repository'] = 'Module\User\V1\Repository\ResetPasswordRequestRepository';
        $config->save($value);
    }

    private function configureServices(): void
    {
        $configDir = $this->kernel->getProjectDir().'/config/services.yaml';
        if (!$this->filesystem->exists($configDir)) {
            throw new Exception(
                'No`t found config/services.yaml'
            );
        }
        $config = new ConfigEditor($configDir);
        $value = $config->parse();

        $value['services']['Module\\']['resource'] = '../module/';
        $value['services']['App\\EventListener\\ExceptionListener']['tags'] = [['name' => 'kernel.event_listener', 'event' => 'kernel.exception']];
        $config->save($value);
    }

    private function configureRoutes(): void
    {
        $configDir = $this->kernel->getProjectDir().'/config/routes.yaml';
        if (!$this->filesystem->exists($configDir)) {
            throw new Exception(
                'No`t found config/routes.yaml'
            );
        }
        $config = new ConfigEditor($configDir);
        $value = $config->parse();

        $value['module']['type'] = 'attribute';
        $value['module']['resource'] = '../module/';
        $config->save($value);
    }

    /**
     * @throws Exception
     */
    private function configureSecurity()
    {
        $configDir = $this->kernel->getProjectDir().'/config/packages/security.yaml';
        if (!$this->filesystem->exists($configDir)) {
            throw new Exception(
                'No`t found config/packages/security.yaml'
            );
        }
        $config = new ConfigEditor($configDir);
        $value = $config->parse();

        unset($value['security']['providers']['users_in_memory']);
        unset($value['security']['firewalls']['main']['provider']);

        $value['security']['providers']['app_user_provider']['entity']['class'] = 'Module\User\V1\Entity\User';
        $value['security']['providers']['app_user_provider']['entity']['property'] = 'email';
        $value['security']['firewalls']['main']['json_login']['check_path'] = 'userLogin';
        $value['security']['firewalls']['main']['logout']['target'] = 'userLogout';

        $config->save($value);
    }
}