<?php

namespace App;

use Symfony\Component\Dotenv\Dotenv;

class GoogleTranslateWorkflowBase
{
    protected $debug = false;

    protected $workFlows;

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
        $this->workFlows = new Workflows();
        $this->languages = new Languages();

        $this->loadSettings();
    }

    public function loadSettings()
    {
        if (!isset($_SERVER['APP_ENV'])) {
            (new Dotenv())->load(__DIR__.'/../.env');
        }

        if (getenv('APP_ENV') === 'dev') {
            $this->debug = true;
        }

        $settings = null;
        if ($this->debug) {
            $settings = [
                'source' => getenv('SETTINGS_SOURCE'),
                'target' => getenv('SETTINGS_TARGET')
            ];
        } else {
            $filePath = $this->getConfigFilePath();
            if (file_exists($filePath)) {
                $settings = json_decode(file_get_contents($filePath), true);
            }
        }

        // Only set settings if anything is stored in config file, otherwise use the defaults.
        if (is_array($settings)) {
            $this->settings = $settings;
        } else {
            $this->settings = $this->defaultSettings;
        }
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
        return "{$this->workFlows->data()}/config.json";
    }

    protected function log($data, $title = null)
    {
        if ($this->debug) {
            $msg = (!empty($title) ? $title . ': ' : '') . print_r($data, TRUE);
            file_put_contents('php://stdout', "{$msg}\n");
        }
    }
}
