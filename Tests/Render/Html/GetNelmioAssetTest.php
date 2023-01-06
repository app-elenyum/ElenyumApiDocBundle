<?php

/*
 * This file is part of the ElenyumApiDocBundle package.
 *
 * (c) Elenyum
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Elenyum\ApiDocBundle\Tests\Render\Html;

use Elenyum\ApiDocBundle\Render\Html\AssetsMode;
use Elenyum\ApiDocBundle\Render\Html\GetElenyumAsset;
use Elenyum\ApiDocBundle\Tests\Functional\WebTestCase;
use Twig\TwigFunction;

class GetElenyumAssetTest extends WebTestCase
{
    /** @dataProvider provideAsset */
    public function test($mode, $asset, $expectedContent)
    {
        static::bootKernel();
        /** @var GetElenyumAsset $getElenyumAsset */
        $getElenyumAsset = static::getContainer()->get('elenyum_api_doc.render_docs.html.asset');
        /** @var TwigFunction */
        $twigFunction = $getElenyumAsset->getFunctions()[0];
        self::assertSame($expectedContent, $twigFunction->getCallable()->__invoke($mode, $asset));
    }

    public function provideAsset()
    {
        $cdnDir = 'https://cdn.jsdelivr.net/gh/elenyum/ElenyumApiDocBundle/Resources/public';
        $resourceDir = __DIR__.'/../../../Resources/public';

        return $this->provideCss($cdnDir, $resourceDir)
            + $this->provideJs($cdnDir, $resourceDir)
            + $this->provideImage($cdnDir);
    }

    private function provideCss($cdnDir, $resourceDir)
    {
        return [
            'bundled css' => [
                AssetsMode::BUNDLE,
                'style.css',
                '<link rel="stylesheet" href="/bundles/elenyumapidoc/style.css">',
            ],
            'cdn css' => [
                AssetsMode::CDN,
                'style.css',
                '<link rel="stylesheet" href="'.$cdnDir.'/style.css">',
            ],
            'offline css' => [
                AssetsMode::OFFLINE,
                'style.css',
                '<style>'.file_get_contents($resourceDir.'/style.css').'</style>',
            ],
            'external css' => [
                AssetsMode::BUNDLE,
                'https://cdn.com/my.css',
                '<link rel="stylesheet" href="https://cdn.com/my.css">',
            ],
        ];
    }

    private function provideJs($cdnDir, $resourceDir)
    {
        return [
            'bundled js' => [
                AssetsMode::BUNDLE,
                'init-swagger-ui.js',
                '<script src="/bundles/elenyumapidoc/init-swagger-ui.js"></script>',
            ],
            'cdn js' => [
                AssetsMode::CDN,
                'init-swagger-ui.js',
                '<script src="'.$cdnDir.'/init-swagger-ui.js"></script>',
            ],
            'offline js' => [
                AssetsMode::OFFLINE,
                'init-swagger-ui.js',
                '<script>'.file_get_contents($resourceDir.'/init-swagger-ui.js').'</script>',
            ],
            'external js' => [
                AssetsMode::BUNDLE,
                'https://cdn.com/my.js',
                '<script src="https://cdn.com/my.js"></script>',
            ],
        ];
    }

    private function provideImage($cdnDir)
    {
        return [
            'bundled image' => [
                AssetsMode::BUNDLE,
                'logo.png',
                '/bundles/elenyumapidoc/logo.png',
            ],
            'cdn image' => [
                AssetsMode::CDN,
                'logo.png',
                $cdnDir.'/logo.png',
            ],
            'offline image fallbacks to cdn' => [
                AssetsMode::OFFLINE,
                'logo.png',
                $cdnDir.'/logo.png',
            ],
        ];
    }
}
