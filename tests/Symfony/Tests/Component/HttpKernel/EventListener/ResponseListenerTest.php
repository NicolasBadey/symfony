<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Tests\Component\HttpKernel\EventListener;

use Symfony\Component\HttpKernel\EventListener\ResponseListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\CoreEvents;
use Symfony\Component\EventDispatcher\EventDispatcher;

class ResponseListenerTest extends \PHPUnit_Framework_TestCase
{
    private $dispatcher;

    private $kernel;

    protected function setUp()
    {
        $this->dispatcher = new EventDispatcher();
        $listener = new ResponseListener('UTF-8');
        $this->dispatcher->addListener(CoreEvents::RESPONSE, array($listener, 'onCoreResponse'));

        $this->kernel = $this->getMock('Symfony\Component\HttpKernel\HttpKernelInterface');

    }
    public function testFilterDoesNothingForSubRequests()
    {
        $response = new Response('foo');

        $event = new FilterResponseEvent($this->kernel, new Request(), HttpKernelInterface::SUB_REQUEST, $response);
        $this->dispatcher->dispatch(CoreEvents::RESPONSE, $event);

        $this->assertEquals('', $event->getResponse()->headers->get('content-type'));
    }

    public function testFilterDoesNothingIfContentTypeIsSet()
    {
        $response = new Response('foo');
        $response->headers->set('Content-Type', 'text/plain');

        $event = new FilterResponseEvent($this->kernel, new Request(), HttpKernelInterface::MASTER_REQUEST, $response);
        $this->dispatcher->dispatch(CoreEvents::RESPONSE, $event);

        $this->assertEquals('text/plain', $event->getResponse()->headers->get('content-type'));
    }

    public function testFilterDoesNothingIfRequestFormatIsNotDefined()
    {
        $response = new Response('foo');

        $event = new FilterResponseEvent($this->kernel, Request::create('/'), HttpKernelInterface::MASTER_REQUEST, $response);
        $this->dispatcher->dispatch(CoreEvents::RESPONSE, $event);

        $this->assertEquals('text/html', $event->getResponse()->headers->get('content-type'));
    }

    public function testFilterSetContentType()
    {
        $response = new Response('foo');
        $request = Request::create('/');
        $request->setRequestFormat('json');

        $event = new FilterResponseEvent($this->kernel, $request, HttpKernelInterface::MASTER_REQUEST, $response);
        $this->dispatcher->dispatch(CoreEvents::RESPONSE, $event);

        $this->assertEquals('application/json', $event->getResponse()->headers->get('content-type'));
    }

    public function testFilterRemovesContentForHeadRequests()
    {
        $response = new Response('foo');
        $request = Request::create('/', 'HEAD');

        $event = new FilterResponseEvent($this->kernel, $request, HttpKernelInterface::MASTER_REQUEST, $response);
        $this->dispatcher->dispatch(CoreEvents::RESPONSE, $event);

        $this->assertEquals('', $response->getContent());
    }
}
