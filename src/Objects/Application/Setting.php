<?php


namespace Kiniauth\Objects\Application;

use Kinikit\Core\Configuration\FileResolver;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Persistence\ORM\ActiveRecord;


/**
 * Setting object for persisting settings.
 *
 * Class Setting
 *
 * @table ka_setting
 * @interceptors \Kiniauth\Objects\Application\SettingInterceptor
 */
class Setting extends ActiveRecord {

    private static $settingDefinitions;

    /**
     * @var integer
     * @primaryKey
     */
    private $parentAccountId;

    /**
     * @var string
     * @primaryKey
     * @column setting_key
     */
    private $key;

    /**
     * @var string
     */
    private $value;


    /**
     * @var integer
     * @primaryKey
     */
    private $valueIndex = 0;


    /**
     * Non-persisted definition field
     *
     * @unmapped
     * @var string
     */
    protected $title;

    /**
     * Non-persisted definition field
     *
     * @unmapped
     * @var string
     */
    protected $description;


    /**
     * Non-persisted definition field
     *
     * @unmapped
     * @var string
     */
    protected $type;


    /**
     * @return int
     */
    public function getParentAccountId() {
        return $this->parentAccountId;
    }

    /**
     * @param int $parentAccountId
     */
    public function setParentAccountId($parentAccountId) {
        $this->parentAccountId = $parentAccountId;
    }

    /**
     * @return string
     */
    public function getKey() {
        return $this->key;
    }

    /**
     * @param string $key
     */
    public function setKey($key) {
        $this->key = $key;
    }

    /**
     * @return string
     */
    public function getValue() {
        return $this->value;
    }

    /**
     * @param string $value
     */
    public function setValue($value) {
        $this->value = $value;
    }

    /**
     * @return int
     */
    public function getValueIndex() {
        return $this->valueIndex;
    }

    /**
     * @param int $valueIndex
     */
    public function setValueIndex($valueIndex) {
        $this->valueIndex = $valueIndex;
    }

    /**
     * @return string
     */
    public function getTitle() {
        if (!$this->title && $this->key) {
            $this->title = $this->getSettingDefinition()["title"] ?? null;
        }
        return $this->title;
    }

    /**
     * @return string
     */
    public function getDescription() {
        if (!$this->description && $this->key) {
            $this->description = $this->getSettingDefinition()["description"] ?? null;
        }
        return $this->description;
    }

    /**
     * @return string
     */
    public function getType() {
        if (!$this->type && $this->key) {
            $this->type = $this->getSettingDefinition()["type"] ?? null;
        }
        return $this->type;
    }


    /**
     * Get our setting definition (used in getters above).
     *
     * @return array
     */
    private function getSettingDefinition() {

        $defs = self::getSettingDefinitions();
        if (isset($defs[$this->getKey()])) {
            $def = $defs[$this->getKey()];
        }

        return $def;
    }


    /**
     * Get all setting definitions.  Cached for performance.
     */
    public static function getSettingDefinitions() {
        if (!self::$settingDefinitions) {

            $fileResolver = Container::instance()->get(FileResolver::class);

            $settingDefinitions = array();
            foreach ($fileResolver->getSearchPaths() as $sourceBase) {
                if (file_exists($sourceBase . "/Config/settings.json")) {
                    $newDefs = json_decode(file_get_contents($sourceBase . "/Config/settings.json"), true);
                    $settingDefinitions = array_merge($settingDefinitions, $newDefs);
                }
            }

            $indexedDefs = array();
            foreach ($settingDefinitions as $definition) {
                $indexedDefs[$definition["key"]] = $definition;
            }

            self::$settingDefinitions = $indexedDefs;
        }

        return self::$settingDefinitions;
    }


}
