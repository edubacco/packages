<?php
/**
 * Created by PhpStorm.
 * User: eduardosilvi
 * Date: 18/01/19
 * Time: 15.36
 */

namespace Terramar\Packages\Plugin\Bitbucket;


use Symfony\Component\Yaml\Yaml;

/**
 * Class SshKeysHelper
 *
 * @package terramar-packages
 * @author eduardosilvi
 */
class SshKeysHelper {

	public static function getKeys() {
		$configPath = getenv('MOTORK_SSH_PUBLIC_KEYS');
		if (!$configPath || !file_exists($configPath)) {
			return [];
		}
		$yamlFile = Yaml::parse(file_get_contents($configPath));
		return isset($yamlFile['keys'])? $yamlFile['keys'] : [];
	}

}