<?php
/**
 * Created by PhpStorm.
 * User: eduardosilvi
 * Date: 29/01/19
 * Time: 16.07
 */

namespace Terramar\Packages\Plugin\GrafanaAnnotations;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Terramar\Packages\Events;
use Terramar\Packages\Event\PackageUpdateEvent;

/**
 * Class EventSubscriber
 *
 * @package terramar-packages
 * @author eduardosilvi
 */
class EventSubscriber implements EventSubscriberInterface {

	/**
	 * @var array;
	 */
	private $config;

	public function __construct($config) {
		$this->config = $config; //#package section
	}

	public function isPluginActive() {
		if (!isset($this->config['host']) || empty($this->config['host'])) {
			return false;
		}

		if (!isset($this->config['bearer_token']) || empty($this->config['bearer_token'])) {
			return false;
		}

		return true;
	}

	/**
	 * @return array
	 */
	public static function getSubscribedEvents()
	{
		return [
			Events::PACKAGE_UPDATE => ['onUpdatePackage', 0],
		];
	}



	public function onUpdatePackage(PackageUpdateEvent $event)
	{
		if (!$this->isPluginActive()) {
			return;
		}

		$package = $event->getPackage();

		$client = new Client([
			'base_uri' => $this->config['host'],
			'headers' => [
				'Authorization' => "Bearer {$this->config['bearer_token']}",
			]
		]);
		$response = $client->request('POST', '/api/annotations',
			[
				RequestOptions::JSON => [
					"time"        => round(microtime(true) * 1000), #millitime
					"isRegion"    => false,
					"tags"        => [ "packagist", "packageUpdate", $package->getFqn() ],
					"text"        => "New commit on package {$package->getFqn()}"
				]
			]
		);
	}

}