<?php


namespace Kiniauth\Services\Application;


use Kiniauth\Objects\Application\Setting;
use Kiniauth\Services\Security\SecurityService;

/**
 * Class SettingsService
 * @package Kiniauth\Services\Application
 * @noProxy
 */
class SettingsService {

    public function __construct(
        private SecurityService $securityService) {
    }


    /**
     * Get a setting by key and value
     *
     * @param $key string
     * @param $value string
     */
    public function getSettingByKeyAndValue($key, $value) {
        $matches = Setting::filter("WHERE key = ? AND value = ?", $key, $value);
        if (sizeof($matches) > 0) {
            return $matches[0];
        } else {
            return null;
        }
    }


    /**
     * Get a setting value for the supplied key, optionally supplying an account id
     * and user id to qualify the settings scope
     *
     * @param string $key
     * @param string $accountId
     * @param string $userId
     */
    public function getSettingValue($key, $accountId = null, $userId = null) {
        // Get parent account id.
        $activeParentAccountId = $this->securityService->getParentAccountId($accountId, $userId);

        /**
         * @var Setting[] $settings
         */
        $settings = Setting::filter("WHERE parentAccountId = ? AND setting_key = ?",
            $activeParentAccountId, $key);

        if (sizeof($settings) > 0) {
            if ($settings[0]->isMultiple()) {
                return array_map(function ($setting) {
                    return $setting->getValue();
                }, $settings);
            } else {
                return $settings[0]->getValue();
            }
        } else {
            return null;
        }

    }


    /**
     * Get parent account settings for
     */
    public function getParentAccountSettingValues($accountId = null, $userId = null) {

        // Get parent account id.
        $activeParentAccountId = $this->securityService->getParentAccountId($accountId, $userId);

        /**
         * @var Setting[] $settings
         */
        $settings = Setting::filter("WHERE parentAccountId = ?", $activeParentAccountId);


        $settingValues = [];

        foreach ($settings as $setting) {
            if (isset($settingValues[$setting->getKey()])) {
                if ($setting->isMultiple()) {
                    $settingValues[$setting->getKey()][] = $setting->getValue();
                } else {
                    $settingValues[$setting->getKey()] = $setting->getValue();
                }
            } else {
                if ($setting->isMultiple()) {
                    $settingValues[$setting->getKey()] = [$setting->getValue()];
                } else {
                    $settingValues[$setting->getKey()] = $setting->getValue();
                }
            }
        }

        return $settingValues;

    }

}
