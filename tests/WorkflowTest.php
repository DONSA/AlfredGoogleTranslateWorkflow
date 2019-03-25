<?php

namespace Tests;

use App\GoogleTranslateWorkflow;
use PHPUnit\Framework\TestCase;

class WorkflowTest extends TestCase
{
    private $items;

    /**
     * @throws \Exception
     */
    public function setUp()
    {
        parent::setUp();

        $workflow = new GoogleTranslateWorkflow();
        $workflow->setSettings([
            'source' => 'auto',
            'target' => 'pt,en,sv'
        ]);

        $output = $workflow->process('gt This is a test');

        $this->items = simplexml_load_string($output);
    }

    /**
     * @throws \Exception
     */
    public function testInputIsNotCutOff()
    {
        $this->assertNotContains('gt', $this->getTranslation($this->items->item[0]->attributes()->arg));
    }

    /**
     * @throws \Exception
     */
    public function testInputHasCorrectUid()
    {
        $this->assertEquals('pt', $this->items->item[0]->attributes()->uid);
        $this->assertEquals('en', $this->items->item[1]->attributes()->uid);
        $this->assertEquals('sv', $this->items->item[2]->attributes()->uid);
    }

    /**
     * @throws \Exception
     */
    public function testTranslationOrder()
    {
        $this->assertEquals('Isto é um teste', $this->getTranslation($this->items->item[0]->attributes()->arg));
        $this->assertEquals('This is a test', $this->getTranslation($this->items->item[1]->attributes()->arg));
        $this->assertEquals('Detta är ett prov', $this->getTranslation($this->items->item[2]->attributes()->arg));
    }

    /**
     * @param string $arg
     *
     * @return string
     */
    private function getTranslation($arg)
    {
        return explode('|', $arg)[1];
    }

    /**
     * @param string $arg
     *
     * @return string
     */
    private function getUrl($arg)
    {
        return explode('|', $arg)[0];
    }
}
