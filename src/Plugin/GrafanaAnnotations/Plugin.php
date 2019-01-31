<?php
/**
 * Created by PhpStorm.
 * User: eduardosilvi
 * Date: 29/01/19
 * Time: 14.39
 */

namespace Terramar\Packages\Plugin\GrafanaAnnotations;


use Terramar\Packages\Plugin\PluginInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Class Plugin
 *
 * @package terramar-packages
 * @author eduardosilvi
 */
class Plugin implements PluginInterface {

	/**
	 * Configure the given ContainerBuilder.
	 *
	 * This method allows a plugin to register additional services with the
	 * service container.
	 *
	 * @param ContainerBuilder $container
	 */
	public function configure(ContainerBuilder $container)
	{

		$container->register('packages.plugin.grafana_annotations.subscriber',
			'Terramar\Packages\Plugin\GrafanaAnnotations\EventSubscriber')
					->addArgument('%packages.configuration%')

		          ->addTag('kernel.event_subscriber');
	}

	/**
	 * Get the plugin name.
	 *
	 * @return string
	 */
	public function getName()
	{
		return 'GrafanaAnnotations';
	}

	/**
	 */
	public function getVersion()
	{
		return;
	}
}