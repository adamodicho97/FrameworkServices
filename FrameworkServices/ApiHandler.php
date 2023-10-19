<?php

namespace FrameworkServices;

use Curl\Curl;

class ApiHandler extends BaseServices
{
    /**
     * Authenticate App
     * 
     * @param (array) $appAuthData
     * @param (array) $orgAuthData
     * 
     * @return (array)
     */
    public function apiAuth(
        array $appAuthData,
        array $orgAuthData
    ): array {
        $frameCurl = new Curl();

        $frameCurl->setHeader('Content-Type', 'application/json');

        switch ($appAuthData['type']) {
            case 'api':
                $frameCurl->setHeader('Authorization', 'Bearer ' . $orgAuthData['apiKey']);
                break;
            case 'basic':
                $frameCurl->setBasicAuthentication($orgAuthData['apiUsername'], $orgAuthData['apiPassword']);
                break;
        }

        $requestURL = $appAuthData['url'];

        $firstParam = false;
        if (strpos($requestURL, '?') === false) {
            $firstParam = true;
        }

        if (!empty($appAuthData['urlParams'])) {
            foreach ($appAuthData['urlParams'] as $wholevalue) {
                foreach ($wholevalue as $key => $value) {
                    $requestURL .= $firstParam == true
                        ? '?' . $key . '=' . $value
                        : '&' . $key . '=' . $value;
                    $firstParam = false;
                }
            }
        }

        if (!empty($appAuthData['httpHeaders'])) {
            foreach ($appAuthData['httpHeaders'] as $wholevalue) {
                foreach ($wholevalue as $key => $value) {
                    $frameCurl->setHeader($key, $value);
                }
            }
        }

        $requestBody = [];
        if (!empty($appAuthData['requestBody'])) {
            foreach ($appAuthData['requestBody'] as $wholevalue) {
                foreach ($wholevalue as $key => $value) {
                    $requestBody[$key] = $value;
                }
            }
        }

        $frameCurl->{$appAuthData['request']}($requestURL, $requestBody);

        if ($frameCurl->error) {
            return [
                'status' => 'error',
                'content' => 'Error: ' . $frameCurl->errorMessage . "\n",
                'diagnosis' => $frameCurl->diagnose(true)
            ];
        } else {
            return [
                'status' => 'success',
                'content' => $frameCurl->response
            ];
        }

        $frameCurl->close();
    }

    /**
     * Process A trigger
     * 
     * @param (array) $pollingData
     * @param (array) $inputFields
     * @param (array) $userInputFields
     * 
     * @return (array)
     */
    public function apiTriggerPolling(
        array $pollingData,
        array $inputFields = [],
        array $userInputFields = []
    ): array {
        $frameCurl = new Curl();

        $requestURL = $pollingData['url'];
        $requestBody = [];

        $firstParam = false;
        if (strpos($requestURL, '?') === false) {
            $firstParam = true;
        }

        # Input fields need user input - not default values
        switch ($pollingData['request']) {
            case 'get':
                if (!empty($inputFields)) {
                    foreach ($inputFields as $key => $wholevalue) {
                        if (array_key_exists($wholevalue['key'], $userInputFields)) {
                            $requestURL .= $firstParam == true
                                ? '?' . $wholevalue['key'] . '=' . $userInputFields[$wholevalue['key']]
                                : '&' . $wholevalue['key'] . '=' . $userInputFields[$wholevalue['key']];
                            $firstParam = false;
                        }
                    }
                }
                break;
            case 'post': # send input data as JSON
                $frameCurl->setHeader('Content-Type', 'application/json');
                if (!empty($inputFields)) {
                    foreach ($inputFields as $key => $wholevalue) {
                        if (array_key_exists($wholevalue['key'], $userInputFields)) {
                            $requestBody[$key] = $userInputFields[$wholevalue['key']];
                        }
                    }
                }
                break;
        }

        if (!empty($pollingData['urlParams'])) {
            foreach ($pollingData['urlParams'] as $wholevalue) {
                foreach ($wholevalue as $key => $value) {
                    $requestURL .= $firstParam == true
                        ? '?' . $key . '=' . $value
                        : '&' . $key . '=' . $value;
                    $firstParam = false;
                }
            }
        }

        if (!empty($pollingData['httpHeaders'])) {
            foreach ($pollingData['httpHeaders'] as $wholevalue) {
                foreach ($wholevalue as $key => $value) {
                    $frameCurl->setHeader($key, $value);
                }
            }
        }

        if (!empty($pollingData['requestBody'])) {
            foreach ($pollingData['requestBody'] as $wholevalue) {
                foreach ($wholevalue as $key => $value) {
                    $requestBody[$key] = $value;
                }
            }
        }

        $frameCurl->{$pollingData['request']}($requestURL, $requestBody);

        if ($frameCurl->error) {
            return [
                'status' => 'error',
                'content' => 'Error: ' . $frameCurl->errorMessage . "\n",
                'diagnosis' => $frameCurl->diagnose(true)
            ];
        } else {
            return [
                'status' => 'success',
                'content' => $frameCurl->response
            ];
        }

        $frameCurl->close();
    }

    /**
     * Handle API Action Call
     * 
     * @param (array) $apiSettings
     * @param (array) $inputFields
     * @param (array) $userInputFields
     * 
     * @return (array)
     */
    public function apiAction(
        array $apiSettings,
        array $inputFields = [],
        array $userInputFields = []
    ): array {
        $frameCurl = new Curl();

        $requestURL = $apiSettings['url'];
        $requestBody = [];

        $firstParam = false;
        if (strpos($requestURL, '?') === false) {
            $firstParam = true;
        }

        switch ($apiSettings['request']) {
            case 'get': # send as a parameter
                if (!empty($inputFields)) {
                    foreach ($inputFields as $id => $wholevalue) {
                        if (array_key_exists($wholevalue['key'], $userInputFields)) {
                            $requestURL .= $firstParam == true
                                ? '?' . $wholevalue['key'] . '=' . $userInputFields[$wholevalue['key']]
                                : '&' . $wholevalue['key'] . '=' . $userInputFields[$wholevalue['key']];
                            $firstParam = false;
                        }
                    }
                }
                break;
            case 'post': # send input data as JSON
                $frameCurl->setHeader('Content-Type', 'application/json');
                if (!empty($inputFields)) {
                    foreach ($inputFields as $id => $wholevalue) {
                        if (array_key_exists($wholevalue['key'], $userInputFields)) {
                            $requestBody[$wholevalue['key']] = $userInputFields[$wholevalue['key']];
                        }
                    }
                }
                break;
        }

        if (!empty($apiSettings['urlParams'])) {
            foreach ($apiSettings['urlParams'] as $wholevalue) {
                foreach ($wholevalue as $key => $value) {
                    $requestURL .= $firstParam == true
                        ? '?' . $key . '=' . $value
                        : '&' . $key . '=' . $value;
                    $firstParam = false;
                }
            }
        }

        if (!empty($apiSettings['httpHeaders'])) {
            foreach ($apiSettings['httpHeaders'] as $wholevalue) {
                foreach ($wholevalue as $key => $value) {
                    $frameCurl->setHeader($key, $value);
                }
            }
        }

        if (!empty($apiSettings['requestBody'])) {
            foreach ($apiSettings['requestBody'] as $wholevalue) {
                foreach ($wholevalue as $key => $value) {
                    $requestBody[$key] = $value;
                }
            }
        }

        $frameCurl->{$apiSettings['request']}($requestURL, $requestBody);

        if ($frameCurl->error) {
            return [
                'status' => 'error',
                'content' => 'Error: ' . $frameCurl->errorMessage . "\n",
                'diagnosis' => $frameCurl->diagnose(true)
            ];
        } else {
            return [
                'status' => 'success',
                'content' => $frameCurl->response
            ];
        }

        $frameCurl->close();
    }
}
