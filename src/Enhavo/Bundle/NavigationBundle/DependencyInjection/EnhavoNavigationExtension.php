<?php

namespace Enhavo\Bundle\NavigationBundle\DependencyInjection;

use Sylius\Bundle\ResourceBundle\DependencyInjection\Extension\AbstractResourceExtension;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class EnhavoNavigationExtension extends AbstractResourceExtension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $config, ContainerBuilder $container)
    {
        $config = $this->processConfiguration(new Configuration(), $config);
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $this->registerResources('enhavo_navigation', $config['driver'], $config['resources'], $container);

        $container->setParameter('enhavo_navigation.items', $config['items']);
        $container->setParameter('enhavo_navigation.render.sets', $config['render']['sets']);

        $container->setParameter('enhavo_navigation.default.model', $config['default']['model']);
        $container->setParameter('enhavo_navigation.default.form', $config['default']['form']);
        $container->setParameter('enhavo_navigation.default.repository', $config['default']['repository']);
        $container->setParameter('enhavo_navigation.default.factory', $config['default']['factory']);
        $container->setParameter('enhavo_navigation.default.template', $config['default']['template']);

        $configFiles = array(
            'services.yml',
        );

        foreach ($configFiles as $configFile) {
            $loader->load($configFile);
        }
    }
}
