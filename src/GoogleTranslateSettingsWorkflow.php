<?php

/**
 * The MIT License (MIT)
 *
 * Copyright (c) 2013-2016 Thomas Hempel <thomas@scriptme.de>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

require './GoogleTranslateWorkflowBase.php';

class GoogleTranslateSettingsWorkflow extends GoogleTranslateWorkflowBase
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

        if ($command == 'show') {
            $result = $this->showSettings();
        } else {
            $result = $this->set($command, $requestParts[0]);
        }

        $this->log($result);

        return $result;
    }

    /**
     * @param string $request
     *
     * @return string
     */
    public function store($request)
    {
        list($option, $value) = explode(':', $request);
        $this->settings[trim($option)] = trim($value);
        $this->saveSettings();

        return "{$option} set to {$value}";
    }

    /**
     * @return AlfredResult
     */
    protected function showSettings()
    {
        $xml = new AlfredResult();
        $xml->setShared('uid', 'setting');
        $this->log($this->settings, 'SHOW settings');

        foreach ($this->settings as $settingKey => $settingValue) {
            $xml->addItem([
                'arg' => $settingKey,
                'valid' => 'yes',
                'title' => "{$settingKey}={$settingValue}"
            ]);
        }

        return $xml;
    }

    /**
     * @param string $setting
     * @param string $value
     *
     * @return AlfredResult $xml
     */
    protected function set($setting, $value)
    {
        $xml = new AlfredResult();
        $xml->setShared('uid', 'setting');
        $setLength = strlen($setting);
        $validOptionKeys = array_keys($this->validOptions);

        if (!in_array($setting, $validOptionKeys)) {
            // Find valid options
            $valid = array_filter($validOptionKeys, function($value) use($setting, $setLength) {
                return ($setting  == strtolower(substr($value, 0, $setLength)));
            });

            if (count($valid) > 0) {
                foreach ($valid as $optionKey) {
                    $xml->addItem([
                        'title' => $this->validOptions[$optionKey],
                        'subtitle' => $optionKey,
                        'autocomplete' => $optionKey
                    ]);
                }
            } else {
                $xml->addItem(['title' => "Unknown option {$value}"]);
            }
        } else {
            $item = ['title' => $setting];

            if (empty($value)) {
                $item['subtitle'] = "Current value = {$this->settings[$setting]}";
            } else {
                $trimmedValue = strtolower(trim($value));

                if ($trimmedValue == 'default') {
                    $trimmedValue = $this->defaultSettings[$setting];
                }

                if ($this->languages->isAvailable($trimmedValue)) {
                    $item['subtitle'] = 'New value = ' . $trimmedValue;
                    $item['arg'] = "{$setting}:{$trimmedValue}";
                } else {
                    $requestedLanguages = explode(',', $trimmedValue);
                    $validLanguages = [];
                    foreach ($requestedLanguages as $languageKey) {
                        $trimmedKey = trim($languageKey);
                        if ($this->languages->isAvailable($trimmedKey)) {
                            $validLanguages[$trimmedKey] = $this->languages->map($trimmedKey);
                        }
                    }

                    if (count($validLanguages) > 0) {
                        $checkedValue = implode(',', array_keys($validLanguages));
                        $item['subtitle'] = "New value = " . implode(', ', $validLanguages) . " ({$checkedValue})";
                        $item['arg'] = "{$setting}:{$checkedValue}";
                    } else {
                        $item['subtitle'] = "Invalid value {$value}";
                    }
                }
            }

            $xml->addItem($item);
        }

        return $xml;
    }

}
