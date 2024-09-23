<?php


namespace Kiniauth\Test\Objects\Security;

use Kiniauth\Objects\Security\WhitelistedIPRangeProfile;
use Kiniauth\Test\TestBase;

include_once "autoloader.php";

class WhitelistedIPRangeProfileTest extends TestBase {

    public function testCanVerifyIPv4AddressIsInRange() {

        $whitelistedIpRangeProfile = new WhitelistedIPRangeProfile(ipv4AddressRange: "192.168.0.0/24");
        $whitelistedIpRangeProfile->save();

        $this->assertTrue($whitelistedIpRangeProfile->isAddressWhitelisted("192.168.0.0"));
        $this->assertTrue($whitelistedIpRangeProfile->isAddressWhitelisted("192.168.0.4"));
        $this->assertTrue($whitelistedIpRangeProfile->isAddressWhitelisted("192.168.0.236"));

        $this->assertFalse($whitelistedIpRangeProfile->isAddressWhitelisted("192.168.1.0"));
        $this->assertFalse($whitelistedIpRangeProfile->isAddressWhitelisted("192.168.255.0"));
        $this->assertFalse($whitelistedIpRangeProfile->isAddressWhitelisted("192.168.74.75"));

    }

    public function testCanVerifyIPv6AddressIsInRange() {

        $whitelistedIpRangeProfile = new WhitelistedIPRangeProfile(ipv6AddressRange: "2001:db8::/32");
        $whitelistedIpRangeProfile->save();

        $this->assertTrue($whitelistedIpRangeProfile->isAddressWhitelisted("2001:db8:1:aaaa:ffff:ffff:ffff:4"));
        $this->assertTrue($whitelistedIpRangeProfile->isAddressWhitelisted("2001:db8:2:ffff:bbbb:ffff:ffff:3"));
        $this->assertTrue($whitelistedIpRangeProfile->isAddressWhitelisted("2001:db8:3:ffff:ffff:cccc:ffff:2"));
        $this->assertTrue($whitelistedIpRangeProfile->isAddressWhitelisted("2001:db8:4:ffff:ffff:ffff:dddd:1"));

        $this->assertFalse($whitelistedIpRangeProfile->isAddressWhitelisted("2001:db9::"));
        $this->assertFalse($whitelistedIpRangeProfile->isAddressWhitelisted("2002:db8::"));
        $this->assertFalse($whitelistedIpRangeProfile->isAddressWhitelisted("1:2:3:4:5:6:7:8"));

    }

}