<?php

require './alfred.php';
require './workflows.php';
require './languages.php';

class GoogleTranslateWorkflowBase
{
    protected $DEBUG = false;

    protected $workflowsInstance;

    protected $languages;

    protected $settings;

    protected $defaultSettings = [
        'source' => 'auto',
        'target' => 'en'
    ];

    protected $validOptions = [
        'source' => 'Source Language',
        'target' => 'Target Language',
    ];

    public function __construct()
    {
        $this->workflowsInstance = new Workflows();
        $this->languages = new Languages();

        $this->loadSettings();
    }

    public function loadSettings()
    {
        $this->log('loadSettings');
        $settings = null;
        $filePath = $this->getConfigFilePath();
        if (file_exists($filePath)) {
            $settings = json_decode(file_get_contents($filePath), true);
        }

        // Only set settings if anything is stored in config file. Otherwise use the defaults.
        if (is_array($settings)) {
            $this->settings = $settings;
        } else {
            $this->settings = $this->defaultSettings;
        }

        // @TODO: Remove on next version
        $this->updateKeys();
    }

    protected function saveSettings()
    {
        file_put_contents($this->getConfigFilePath(), json_encode($this->settings));
    }

    /**
     * @return string
     */
    protected function getConfigFilePath()
    {
        return "{$this->workflowsInstance->data()}/config.json";
    }

    protected function log($data, $title = null)
    {
        if ($this->DEBUG) {
            $msg = (!empty($title) ? $title . ': ' : '') . print_r($data, TRUE);
            file_put_contents('php://stdout', "{$msg}\n");
        }
    }

    /**
     * Update source and target settings keys
     */
    private function updateKeys()
    {
        $updatedSettings = [];
        if (isset($this->settings['sourceLanguage']) || isset($this->settings['targetLanguage'])) {
            foreach ($this->settings as $key => $value) {
                $key = ($key === 'sourceLanguage') ? 'source' : $key;
                $key = ($key === 'targetLanguage') ? 'target' : $key;

                $updatedSettings[$key] = $value;
            }

            $this->settings = $updatedSettings;
            $this->saveSettings();
        }
    }
}
