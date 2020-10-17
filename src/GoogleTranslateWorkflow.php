<?php

namespace App;

use function \count;
use Stichoza\GoogleTranslate\GoogleTranslate;

class GoogleTranslateWorkflow extends GoogleTranslateWorkflowBase
{
    /**
     * @param string $input
     *
     * @return AlfredResult
     * @throws \Exception
     */
    public function process(string $input)
    {
        [$source, $targets, $text] = $this->parseInput($input);

        if (strlen($text) < getenv('MIN_LENGTH')) {
            return $this->getSimpleMessage('More input needed', 'Input must be longer than ' . getenv('MIN_LENGTH') .  ' characters');
        }

        $response = [];
        foreach ($targets as $target) {
            $response[$target] = $this->fetchGoogleTranslation($source, $target, $text);
        }

        return $this->processResponse($response, $source, $targets, $text);
    }

    /**
     * @param string $command
     *
     * @return array
     */
    protected function extractLanguages($command)
    {
        $sourceLanguage = $targetLanguage = '';

        // First check whether both, source and target language, are set
        if (strpos($command, '>') > 0) {
            list($sourceLanguage, $targetLanguage) = explode('>', $command);
        } elseif (strpos($command, '<') > 0) {
            list($targetLanguage, $sourceLanguage) = explode('<', $command);
        }

        // Check if the source language is valid
        if (!$this->languages->isAvailable($sourceLanguage)) {
            $sourceLanguage = $this->settings['source'];
        }

        // Check if the target language is valid
        if (!$this->languages->isAvailable($targetLanguage)) {
            // If not, try to parse multiple target languages
            $incomingTargetLanguages = explode(',', $targetLanguage);
            $targetLanguageList = [];
            foreach ($incomingTargetLanguages as $itl) {
                if ($this->languages->isAvailable($itl)) {
                    $targetLanguageList[] = $itl;
                }
            }

            // If any valid target languages are selected write them back as csl or just return the default
            if (count($targetLanguageList) === 0) {
                $targetLanguage = explode(',', $this->settings['target']);
            } else {
                $targetLanguage = $targetLanguageList;
            }
        } else {
            $targetLanguage = [$targetLanguage];
        }

        return [
            $sourceLanguage,
            $targetLanguage,
        ];
    }

    /**
     * @param string $source
     * @param string $target
     * @param string $phrase
     *
     * @return array|string
     * @throws \Exception
     */
    protected function fetchGoogleTranslation($source, $target, $phrase)
    {
        $gt = new GoogleTranslate();
        $gt->setSource($source);
        $gt->setTarget($target);

        return $gt->getResponse($phrase);
    }

    /**
     * @param array $response
     * @param string $source
     * @param array $targets
     * @param string $text
     *
     * @return AlfredResult
     */
    protected function processResponse(array $response, string $source, array $targets, string $text)
    {
        $xml = new AlfredResult();

        if (!$response) {
            $xml->addItem([
                'title' => 'No results found'
            ]);
        }

        foreach ($response as $targetLanguage => $result) {
            $hasMultipleTargetLanguages = count($targets) > 1;
            $hasAlternativeTranslations = (bool) $result[1];
            $translation = $hasAlternativeTranslations ? $result[0][0][1] : $result[0][0][0];

            $xml->addItem([
                'uid' => $targetLanguage,
                'arg' => $this->getUserURL($source, $targetLanguage, $text) . '|' . $translation,
                'valid' => 'yes',
                'title' => $translation,
                'subtitle' => "{$text} ({$this->languages->map($source)})",
                'icon' => $this->getFlag($targetLanguage)
            ]);

            if (!$hasMultipleTargetLanguages && $hasAlternativeTranslations) {
                foreach ($result[1] as $alternatives) {
                    $xml->addItem([
                        'valid' => 'no',
                        'title' => ucfirst($alternatives[0]),
                    ]);

                    foreach ($alternatives[2] as $i => $alternative) {
                        $xml->addItem([
                            'arg' => $this->getUserURL($source, $targetLanguage, $text) . '|' . $alternative[0],
                            'valid' => 'yes',
                            'title' => ucfirst($alternative[0]),
                            'subtitle' => '(' . implode(', ', $alternative[1]) . ')',
                            'icon' => $this->getFlag($targetLanguage)
                        ]);
                    }
                }
            }
        }

        return $xml;
    }

    /**
     * @param string $message
     * @param string $subtitle
     *
     * @return AlfredResult
     */
    protected function getSimpleMessage(string $message, string $subtitle = '')
    {
        $xml = new AlfredResult();
        $xml->setShared('uid', 'mtranslate');
        $xml->addItem([
            'title' => $message,
            'subtitle' => $subtitle
        ]);

        return $xml;
    }

    /**
     * @param string $sourceLanguage
     * @param string $targetLanguage
     * @param string $phrase
     *
     * @return string
     */
    protected function getUserURL($sourceLanguage, $targetLanguage, $phrase)
    {
        return "https://translate.google.com/#{$sourceLanguage}/{$targetLanguage}/" . urlencode($phrase);
    }

    /**
     * @param string $language
     *
     * @return string
     */
    protected function getFlag($language)
    {
        $iconFilename = __DIR__ . "/icons/{$language}.png";
        if (!file_exists($iconFilename)) {
            $iconFilename = __DIR__ . '/icons/unknown.png';
        }

        return $iconFilename;
    }

    /**
     * @param string $input
     * @return array
     */
    private function parseInput(string $input)
    {
        $command = '';
        $text = trim($input);

        if (preg_match('/(?P<command>^[a-z-,]{2,}(>|<)[a-z-,]{2,})/', $input, $match)) {
            $command = strtolower($match['command']);
            $text = trim(str_replace($match['command'], '', $input));
        }

        [$source, $targets] = $this->extractLanguages($command);

        return [
            $source,
            $targets,
            $text
        ];
    }
}
