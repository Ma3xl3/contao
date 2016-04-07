<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\EventListener;

use Contao\CoreBundle\DependencyInjection\Compiler\AddSessionBagsPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

/**
 * Tests the AddSessionBagsPass class.
 *
 * @author Leo Feyer <https:/github.com/leofeyer>
 */
class AddSessionBagsPassTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $pass = new AddSessionBagsPass();

        $this->assertInstanceOf('Contao\CoreBundle\DependencyInjection\Compiler\AddSessionBagsPass', $pass);
    }

    /**
     * Tests processing the pass.
     */
    public function testProcess()
    {
        $container = new ContainerBuilder();
        $container->setDefinition('session', new Definition('Symfony\Component\HttpFoundation\Session\Session'));

        $pass = new AddSessionBagsPass();
        $pass->process($container);

        $this->assertTrue($container->hasDefinition('session'));
        $this->assertTrue($container->hasDefinition('contao.session_bag.contao_backend'));
        $this->assertTrue($container->hasDefinition('contao.session_bag.contao_frontend'));

        $methodCalls = $container->findDefinition('session')->getMethodCalls();

        $this->assertCount(2, $methodCalls);
        $this->assertEquals('registerBag', $methodCalls[0][0]);
        $this->assertEquals('registerBag', $methodCalls[1][0]);
        $this->assertInstanceOf('Symfony\Component\DependencyInjection\Reference', $methodCalls[0][1][0]);
        $this->assertInstanceOf('Symfony\Component\DependencyInjection\Reference', $methodCalls[1][1][0]);
        $this->assertEquals('contao.session_bag.contao_backend', (string) $methodCalls[0][1][0]);
        $this->assertEquals('contao.session_bag.contao_frontend', (string) $methodCalls[1][1][0]);
    }

    /**
     * Tests processing the pass without a session.
     */
    public function testProcessWithoutSession()
    {
        $container = new ContainerBuilder();

        $pass = new AddSessionBagsPass();
        $pass->process($container);

        $this->assertFalse($container->hasDefinition('session'));
        $this->assertFalse($container->hasDefinition('contao.session_bag.contao_backend'));
        $this->assertFalse($container->hasDefinition('contao.session_bag.contao_frontend'));
    }
}
