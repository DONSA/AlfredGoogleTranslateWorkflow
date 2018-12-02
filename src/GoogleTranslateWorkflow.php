<?php

require 'vendor/autoload.php';
require './GoogleTranslateWorkflowBase.php';

use Stichoza\GoogleTranslate\TranslateClient;

class GoogleTranslateWorkflow extends GoogleTranslateWorkflowBase
{
    /**
     * @param string $request
     *
     * @return AlfredResult
     */
    public function process($request)
    {
        $this->log($request);

        $requestParts = explode(' ', $request);
        $command = array_shift($requestParts);
        $phrase = (count($requestParts) > 0) ? implode(' ', $requestParts) : $command;

        if (strlen($phrase) < getenv(MIN_LENGTH)) {
            return $this->getSimpleMessage('More input needed', 'The word has to be longer than ' . getenv(MIN_LENGTH) .  ' characters');
        }

        list($sourceLanguage, $targetLanguage) = $this->extractLanguages($command);
        $this->log([$sourceLanguage, $targetLanguage]);
        $targetLanguages = explode(',', $targetLanguage);

        $googleResults = [];
        foreach ($targetLanguages as $targetLanguage) {
            $googleResults[$targetLanguage] = $this->fetchGoogleTranslation($sourceLanguage, $targetLanguage, $phrase);
        }
        $this->log($googleResults, 'Google Results');

        $result = $this->processGoogleResults($googleResults, $phrase, $sourceLanguage);
        $this->log($result);

        return $result;
    }

	/**
 	 * This extracts valid languages from an input string
	 *
	 * @param string $command
	 *
	 * @return array
 	 */
	protected function extractLanguages($command)
	{
		// First check whether both, source and target language, are set
		if (strpos($command, '>') > 0) {
			list($sourceLanguage, $targetLanguage) = explode('>', $command);
		} elseif (strpos($command, '<') > 0) {
			list($targetLanguage, $sourceLanguage) = explode('<', $command);
		} else {
			$targetLanguage = $command;
		}

		// Check if the source language is valid
		if (!$this->languages->isAvailable($sourceLanguage)) {
			$sourceLanguage = $this->settings['source'];
		}

		// Check if the target language is valid
		if (!$this->languages->isAvailable($targetLanguage)) {
			// If not, maybe multiple target languages are defined.
			// Try to parse multiple target languages
			$incomingTargetLanguages = explode(',', $targetLanguage);
			$targetLanguageList = [];
			foreach ($incomingTargetLanguages as $itl) {
				if ($this->languages->isAvailable($itl)) {
					$targetLanguageList[] = $itl;
				}
			}

            // If any valid target languages are selected, write them back as csl or just return the default
			if (\count($targetLanguageList) === 0) {
				$targetLanguage = $this->settings['target'];
			} else {
				$targetLanguage = implode(',', $targetLanguageList);
			}
		}

		return [
		    strtolower($sourceLanguage),
            strtolower($targetLanguage)
        ];
	}

    /**
     * @param string $sourceLanguage
     * @param string $targetLanguage
     * @param string $phrase
     *
     * @return array|string
     */
    protected function fetchGoogleTranslation($sourceLanguage, $targetLanguage, $phrase)
    {
        $response = [];
        $sourceLanguage = ($sourceLanguage === 'auto') ? null : $sourceLanguage;

        try {
            $client = new TranslateClient($sourceLanguage, $targetLanguage);
            $response = $client->translate($phrase);
        } catch (Exception $e) {
            $this->log($e->getMessage());
        }

        return $response;
    }

    protected function processGoogleResults(array $googleResults, $sourcePhrase, $sourceLanguage)
    {
        $xml = new AlfredResult();
        $xml->setShared('uid', 'mtranslate');

        if (!count($googleResults)) {
            $xml->addItem([
                'title' => 'No results found'
            ]);
        }

        foreach ($googleResults as $targetLanguage => $result) {
            if (is_array($result)) {
                $xml->addItem([
                    'arg' => $this->getUserURL($sourceLanguage, $targetLanguage, $sourcePhrase) . '|' . $result[0][0][0],
                    'valid' => 'yes',
                    'title' => $result[0][0][0],
                    'subtitle' => "{$sourcePhrase} ({$this->languages->map($result[1])})",
                    'icon' => $this->getFlag($targetLanguage)
                ]);
            } else {
                $xml->addItem([
                    'arg' => $this->getUserURL($sourceLanguage, $targetLanguage, $sourcePhrase) . '|' . $result,
                    'valid' => 'yes',
                    'title' => $result,
                    'subtitle' => "{$sourcePhrase} ({$this->languages->map($sourceLanguage)})",
                    'icon' => $this->getFlag($targetLanguage)
                ]);
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
    protected function getSimpleMessage($message, $subtitle = '')
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
        $iconFilename = "icons/{$language}.png";
        if (!file_exists($iconFilename)) {
            $iconFilename = 'icons/unknown.png';
        }

        return $iconFilename;
    }

}
