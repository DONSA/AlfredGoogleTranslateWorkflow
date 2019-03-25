<?php

namespace Tests;

use App\GoogleTranslateWorkflow;
use PHPUnit\Framework\TestCase;

class WorkflowTest extends TestCase
{
    public function testInputIsNotCutOff()
    {
        $status = false;
        $workflow = new GoogleTranslateWorkflow();
        $output = $workflow->process('gt This is a test');

        $xml = simplexml_load_string($output);
        foreach ($xml->children() as $item) {
            $in = explode('|', $item->attributes()->arg);

            if (stripslashes($in[1]) === 'This is a test') {
                $status = true;
            }
        }

        $this->assertTrue($status);
    }

    public function testTranslationOrder()
    {
        $status = true;
        $workflow = new GoogleTranslateWorkflow();
        $output = $workflow->process('gt This is a test');

        $xml = simplexml_load_string($output);

        $in = explode('|', $xml->children()[0]->attributes()->arg);
        if ($in[1] !== 'Isto é um teste') {
            $status = false;
        }

        $in = explode('|', $xml->children()[1]->attributes()->arg);
        if ($in[1] !== 'This is a test') {
            $status = false;
        }

        $in = explode('|', $xml->children()[2]->attributes()->arg);
        if ($in[1] !== 'Detta är ett prov') {
            $status = false;
        }

        $this->assertTrue($status);
    }
}
