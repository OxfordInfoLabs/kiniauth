<?php

namespace Kiniauth\ValueObjects\Util;

use Kinikit\Core\Util\ObjectArrayUtils;

class LabelValue {

    public function __construct(private string $label, private mixed $value) {
    }

    /**
     * @return string
     */
    public function getLabel(): string {
        return $this->label;
    }

    /**
     * @return mixed
     */
    public function getValue(): mixed {
        return $this->value;
    }

    /**
     * Generate an array of label values from an object array
     *
     * @param array $objects
     * @param string $labelMember
     * @param string $valueMember
     *
     * @return LabelValue[]
     */
    public static function generateFromObjectArray(array $objects, string $labelMember, string $valueMember) {
        $labels = ObjectArrayUtils::getMemberValueArrayForObjects($labelMember, $objects);
        $values = ObjectArrayUtils::getMemberValueArrayForObjects($valueMember, $objects);

        return array_map(function ($label) use (&$values) {
            return new LabelValue($label, array_shift($values));
        }, $labels);
    }

}