<?php

/*
 * Copyright (c) Terramar Labs
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

namespace Terramar\Packages\Plugin\Satis;

use Composer\Config;
use Composer\IO\ConsoleIO;
use Composer\Repository\ComposerRepository;
use Doctrine\ORM\EntityManager;
use Nice\Application;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Terramar\Packages\Controller\ContainerAwareController;
use Terramar\Packages\Entity\Package;
use Terramar\Packages\Event\PackageUpdateEvent;
use Terramar\Packages\Events;
use Terramar\Packages\Helper\ResqueHelper;

class InventoryController extends ContainerAwareController
{
    /**
     * Displays a list of available packages and versions.
     */
    public function indexAction(Application $app)
    {
        $repository = $this->getRepository();

        $contents = [];
        foreach ($repository->getPackages() as $package) {
            /* @var \Composer\Package\CompletePackage $package */
            $contents[$package->getName()]['name'] = $package->getName();
            $contents[$package->getName()]['versions'][] = $package->getPrettyVersion();
        }

        return new Response($app->get('templating')->render('Plugin/Satis/Inventory/index.html.twig', [
            'contents' => $contents,
        ]));
    }

    /**
     * Displays the details for a given package.
     */
    public function viewAction(Application $app, $id, $version = null)
    {
        $repository = $this->getRepository();

        $id = str_replace('+', '/', $id);
        if ($version) {
            $version = str_replace('+', '/', $version);
        }

        $packages = $repository->findPackages($id);

        usort($packages, function ($a, $b) {
            if ($a->getReleaseDate() > $b->getReleaseDate()) {
                return -1;
            }

            return 1;
        });

        $package = null;
        if ($version) {
            foreach ($packages as $p) {
                if ($p->getPrettyVersion() == $version) {
                    $package = $p;
                }
            }
        } else {
            /* @var \Composer\Package\CompletePackage $package */
            $package = $packages[0];
        }

        return new Response($app->get('templating')->render('Plugin/Satis/Inventory/view.html.twig', [
            'packages' => $packages,
            'package'  => $package,
        ]));
    }

    public function enqueueBuildAction(Application $app, Request $request, $id)
    {
	    $fqn = str_replace('+', '/', $id);

	    /** @var EntityManager $entityManager */
	    $entityManager = $app->get('doctrine.orm.entity_manager');
	    /** @var Package $package */
	    $package = $entityManager->getRepository('Terramar\Packages\Entity\Package')->findOneBy(['fqn' => $fqn]);
	    if (!$package) {
		    throw new NotFoundHttpException('Unable to locate Package');
	    }

	    ResqueHelper::autoConfigure($this->container);

	    $receivedData = json_decode($request->getContent());
	    $event = new PackageUpdateEvent($package, $receivedData);

	    /** @var \Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher */
	    $dispatcher = $app->get('event_dispatcher');
	    $dispatcher->dispatch(Events::PACKAGE_UPDATE, $event);

	    $flashBag = $request->getSession()->getBag('flashes');
	    if ($flashBag instanceof FlashBagInterface) {
		    $flashBag->add('info', "Package $id build enqueued");
	    }

	    /** @var \Nice\Router\UrlGenerator\SimpleUrlGenerator $routerUrlGenerator */
		$routerUrlGenerator = $app->get('router.url_generator');
	    return new RedirectResponse($routerUrlGenerator->generate('packages_view', ['id' => $id]));
    }

    /**
     * @return \Composer\Repository\ComposerRepository
     */
    private function getRepository()
    {
        $configuration = $this->container->getParameter('packages.configuration');

        $io = new ConsoleIO(new ArgvInput([]), new ConsoleOutput(), new HelperSet([]));
        $config = new Config();
        $config->merge([
            'config' => [
                'home' => $this->container->getParameter('app.root_dir'),
            ],
        ]);
        $repository = new ComposerRepository([
            'url' => 'file://' . $configuration['output_dir'] . '/packages.json',
        ], $io, $config);

        return $repository;
    }
}
