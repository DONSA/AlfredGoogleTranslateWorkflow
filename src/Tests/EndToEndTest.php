<?php

require __DIR__ . '/../GoogleTranslateWorkflow.php';

class EndToEndTest
{
    public function isInputIsNotCutOffTest()
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

        echo sprintf("%s: %s\n", __FUNCTION__, $status ? '✅' : '⛔️');
    }

    public function isOrderedTranslationTest()
    {
        $status = true;
        $workflow = new GoogleTranslateWorkflow();
        $output = $workflow->process('gt This is a test');

        $xml = simplexml_load_string($output);

        echo $output; exit;

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

        // foreach ($xml->children() as $item) {
        //     var_dump(explode('|', $item->attributes()->arg)[1]);
        // }
        // die;


        echo sprintf("%s: %s\n", __FUNCTION__, $status ? '✅' : '⛔');
    }
}

$test = new EndToEndTest();
// $test->isInputIsNotCutOffTest();
$test->isOrderedTranslationTest();
