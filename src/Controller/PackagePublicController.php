<?php

/*
 * Copyright (c) Terramar Labs
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

namespace Terramar\Packages\Controller;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Nice\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Terramar\Packages\Entity\Package;
use Terramar\Packages\Entity\Remote;
use Terramar\Packages\Event\PackageEvent;
use Terramar\Packages\Events;


class PackagePublicController extends PackageController
{
	public function newAction(Application $app)
	{
		return new Response($app->get('templating')->render('Package/new.html.twig', [
			'package'   => new Package(),
		]));
	}

	public function createAction(Application $app, Request $request)
	{

		$package = new Package();
		$package->setName($request->get('name'));
		$package->setDescription($request->get('description'));
		$package->setExternalId($request->get('fqn'));

		$entityManager = $app->get('doctrine.orm.entity_manager');
		$remote = $entityManager->getRepository('Terramar\Packages\Entity\Remote')->findOneBy(['name' => 'Public']);
		if (empty($remote)) {
			$remote = $this->createFakePublicRemote($app);
		}
		$package->setRemote($remote); //this kind of package does not have a real remote
		$package->setFqn($request->get('fqn'));
		$package->setWebUrl($request->get('webUrl'));
		$package->setSshUrl($request->get('sshUrl'));
		$package->setEnabled($request->get('enabled', false));

		/** @var \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher */
		$eventDispatcher = $app->get('event_dispatcher');
		$event = new PackageEvent($package);
		$eventDispatcher->dispatch(Events::PACKAGE_CREATE, $event);

		/** @var \Doctrine\ORM\EntityManager $entityManager */
		$entityManager = $app->get('doctrine.orm.entity_manager');
		$entityManager->persist($package);
		$entityManager->flush();

		return new RedirectResponse($app->get('router.url_generator')->generate('manage_packages'));
	}

	/**
	 * @param Application $app
	 *
	 * @return Remote
	 * @throws \Doctrine\ORM\OptimisticLockException
	 */
	public function createFakePublicRemote(Application $app)
	{
		$remote = new Remote();
		$remote->setName('Public');
		$remote->setAdapter('');
		$remote->setEnabled(true);

		/** @var \Doctrine\ORM\EntityManager $entityManager */
		$entityManager = $app->get('doctrine.orm.entity_manager');
		$entityManager->persist($remote);
		$entityManager->flush();

		return $remote;
	}
}
