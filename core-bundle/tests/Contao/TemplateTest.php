<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Contao;

use Contao\BackendTemplate;
use Contao\CoreBundle\Tests\TestCase;
use Contao\System;
use Exception;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Tests the Template class.
 *
 * @author Martin Auswöger <martin@auswoeger.com>
 *
 * @group contao3
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class TemplateTest extends TestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $fs = new Filesystem();
        $fs->mkdir($this->getRootDir().'/templates');

        \define('TL_ROOT', $this->getRootDir());
        \define('TL_MODE', 'BE');

        System::setContainer($this->mockContainerWithContaoScopes());
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        $fs = new Filesystem();
        $fs->remove($this->getRootDir().'/templates');

        parent::tearDown();
    }

    /**
     * Tests replacing variables.
     */
    public function testReplacesTheVariables()
    {
        file_put_contents(
            $this->getRootDir().'/templates/test_template.html5',
            '<?= $this->value ?>'
        );

        $template = new BackendTemplate('test_template');
        $template->setData(['value' => 'test']);

        $obLevel = ob_get_level();
        $this->assertSame('test', $template->parse());
        $this->assertSame($obLevel, ob_get_level());
    }

    /**
     * Tests throwing an exceptions inside a template.
     */
    public function testHandlesExceptions()
    {
        file_put_contents(
            $this->getRootDir().'/templates/test_template.html5',
            'test<?php throw new Exception ?>'
        );

        $template = new BackendTemplate('test_template');
        $obLevel = ob_get_level();

        ob_start();

        try {
            $template->parse();
            $this->fail('Parse should throw an exception');
        } catch (Exception $e) {
            // Ignore
        }

        $this->assertSame('', ob_get_clean());
        $this->assertSame($obLevel, ob_get_level());
    }

    /**
     * Tests throwing an exceptions inside a template block.
     */
    public function testHandlesExceptionsInsideBlocks()
    {
        file_put_contents($this->getRootDir().'/templates/test_template.html5', <<<'EOF'
<?php
    echo 'test1';
    $this->block('a');
    echo 'test2';
    $this->block('b');
    echo 'test3';
    $this->block('c');
    echo 'test4';
    throw new Exception;
EOF
        );

        $template = new BackendTemplate('test_template');
        $obLevel = ob_get_level();

        ob_start();

        try {
            $template->parse();
            $this->fail('Parse should throw an exception');
        } catch (Exception $e) {
            // Ignore
        }

        $this->assertSame('', ob_get_clean());
        $this->assertSame($obLevel, ob_get_level());
    }

    /**
     * Tests throwing an exceptions inside a parent template.
     */
    public function testHandlesExceptionsInParentTemplate()
    {
        file_put_contents($this->getRootDir().'/templates/test_parent.html5', <<<'EOF'
<?php
    echo 'test1';
    $this->block('a');
    echo 'test2';
    $this->endblock();
    $this->block('b');
    echo 'test3';
    $this->endblock();
    $this->block('c');
    echo 'test4';
    $this->block('d');
    echo 'test5';
    $this->block('e');
    echo 'test6';
    throw new Exception;
EOF
        );

        file_put_contents($this->getRootDir().'/templates/test_template.html5', <<<'EOF'
<?php
    echo 'test1';
    $this->extend('test_parent');
    echo 'test2';
    $this->block('a');
    echo 'test3';
    $this->parent();
    echo 'test4';
    $this->endblock('a');
    echo 'test5';
    $this->block('b');
    echo 'test6';
    $this->endblock('b');
    echo 'test7';
EOF
        );

        $template = new BackendTemplate('test_template');
        $obLevel = ob_get_level();

        ob_start();

        try {
            $template->parse();
            $this->fail('Parse should throw an exception');
        } catch (Exception $e) {
            // Ignore
        }

        $this->assertSame('', ob_get_clean());
        $this->assertSame($obLevel, ob_get_level());
    }

    /**
     * Tests parsing nested blocks.
     */
    public function testParsesNestedBlocks()
    {
        file_put_contents($this->getRootDir().'/templates/test_parent.html5', '');

        file_put_contents($this->getRootDir().'/templates/test_template.html5', <<<'EOF'
<?php
    echo 'test1';
    $this->extend('test_parent');
    echo 'test2';
    $this->block('a');
    echo 'test3';
    $this->block('b');
    echo 'test4';
    $this->endblock('b');
    echo 'test5';
    $this->endblock('a');
    echo 'test6';
EOF
        );

        $template = new BackendTemplate('test_template');
        $obLevel = ob_get_level();

        ob_start();

        try {
            $template->parse();
            $this->fail('Parse should throw an exception');
        } catch (Exception $e) {
            // Ignore
        }

        $this->assertSame('', ob_get_clean());
        $this->assertSame($obLevel, ob_get_level());
    }
}
