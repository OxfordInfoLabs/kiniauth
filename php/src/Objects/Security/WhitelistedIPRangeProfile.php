<?php


namespace Kiniauth\Objects\Security;


use Kinikit\Persistence\ORM\ActiveRecord;

/**
 * Class WhitelistedIPRangeProfile
 * @package Kiniauth\Objects\Security
 *
 * @table ka_whitelisted_ip_range_profile
 * @generate
 */
class WhitelistedIPRangeProfile extends ActiveRecord {

    /**
     * @var integer
     */
    private ?int $id;

    /**
     * IPv4 CIDR Range
     *
     * @var string
     */
    private ?string $ipv4AddressRange;

    /**
     * IPv6 CIDR Range
     *
     * @var string
     */
    private ?string $ipv6AddressRange;

    /**
     * @param int $id
     * @param string $ipv4AddressRange
     * @param string $ipv6AddressRange
     */
    public function __construct(?string $ipv4AddressRange = null, ?string $ipv6AddressRange = null, ?int $id = null) {
        $this->id = $id;
        $this->ipv4AddressRange = $ipv4AddressRange;
        $this->ipv6AddressRange = $ipv6AddressRange;
    }


    /**
     * @return int
     */
    public function getId(): ?int {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getIpv4AddressRange(): ?string {
        return $this->ipv4AddressRange;
    }

    /**
     * @param string $ipv4AddressRange
     */
    public function setIpv4AddressRange(?string $ipv4AddressRange): void {
        $this->ipv4AddressRange = $ipv4AddressRange;
    }

    /**
     * @return string
     */
    public function getIpv6AddressRange(): ?string {
        return $this->ipv6AddressRange;
    }

    /**
     * @param string $ipv6AddressRange
     */
    public function setIpv6AddressRange(?string $ipv6AddressRange): void {
        $this->ipv6AddressRange = $ipv6AddressRange;
    }


    /**
     * Return a boolean indicating whether or not the passed address is whitelisted
     * using the defined rules.  Handles both ipv4 and ipv6 addresses
     *
     * @return bool
     * @var string $ip
     */
    public function isAddressWhitelisted(string $ip): bool {

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            list($subnet, $mask) = explode('/', $this->getIpv4AddressRange() ?? "/");

            if ($mask <= 0) {
                return false;
            }
            $ip_bin_string = sprintf("%032b", ip2long($ip));
            $net_bin_string = sprintf("%032b", ip2long($subnet));
            return (substr_compare($ip_bin_string, $net_bin_string, 0, $mask) === 0);

        } else if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {

            list($subnet, $mask) = explode('/', $this->getIpv6AddressRange() ?? "/");

            $subnet = inet_pton($subnet);
            $ip = inet_pton($ip);
            $mask = intval($mask);

            $binMask = str_repeat("f", $mask / 4);
            switch ($mask % 4) {
                case 0:
                    break;
                case 1:
                    $binMask .= "8";
                    break;
                case 2:
                    $binMask .= "c";
                    break;
                case 3:
                    $binMask .= "e";
                    break;
            }
            $binMask = str_pad($binMask, 32, '0');
            $binMask = pack("H*", $binMask);

            return ($ip & $binMask) == $subnet;

        } else {
            return false;
        }


    }

}