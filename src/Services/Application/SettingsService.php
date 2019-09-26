<?php


namespace Kiniauth\Services\Application;


use Kiniauth\Objects\Application\Setting;

/**
 * Class SettingsService
 * @package Kiniauth\Services\Application
 * @noProxy
 */
class SettingsService {


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

}
