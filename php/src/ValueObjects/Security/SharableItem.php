<?php

namespace Kiniauth\ValueObjects\Security;

use Kiniauth\Traits\Account\AccountProject;
use Kiniauth\Traits\Security\Sharable;
use Kinikit\Core\Exception\WrongParameterTypeException;

/**
 * Used for returning sharable items for display purposes etc.
 */
class SharableItem {

    /**
     * @var string
     */
    private $itemTypeLabel;

    /**
     * @var string
     */
    private $itemLabel;

    /**
     * @var string
     */
    private $sharerName;

    /**
     * @var string
     */
    private $sharerLogo;


    public function __construct(mixed $sharable) {
        if (!in_array(Sharable::class, class_uses(get_class($sharable)))) {
            throw new WrongParameterTypeException("Sharable Items must be constructed with sharable objects");
        }
        $this->itemTypeLabel = $sharable->getSharableTypeLabel();
        $this->itemLabel = $sharable->getSharableTitle();

        // If account project extension, also pull out the name and logo
        if (in_array(AccountProject::class, class_uses(get_class($sharable)))) {
            $this->sharerName = $sharable->getAccountSummary()?->getName();
            $this->sharerLogo = $sharable->getAccountSummary()?->getLogo();
        }

    }

    /**
     * @return string
     */
    public function getItemTypeLabel(): string {
        return $this->itemTypeLabel;
    }

    /**
     * @return string
     */
    public function getItemLabel(): string {
        return $this->itemLabel;
    }

    /**
     * @return string
     */
    public function getSharerName(): string {
        return $this->sharerName;
    }

    /**
     * @return string
     */
    public function getSharerLogo(): string {
        return $this->sharerLogo;
    }


}