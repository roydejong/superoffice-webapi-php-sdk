<?php

namespace roydejong\SoWebApi;

use roydejong\SoWebApi\Data\SuperOfficeEnvironment;

/**
 * Configuration data for the SuperOffice API environment.
 */
class Config
{
    /**
     * Determines which cloud environment to use (sod, stage or online).
     */
    public string $environment;

    /**
     * Defines the customer / tenant ID.
     * Usually in the format "Cust12345".
     */
    public string $tenantId;

    /**
     * Client ID (Application ID).
     */
    public string $clientId;

    /**
     * Client secret (Application Token).
     */
    public string $clientSecret;

    /**
     * Private key for system user token signing (<RSAKeyValue> block).
     */
    public string $privateKey;

    /**
     * @param array|null $values Optional array of configuration values to set.
     */
    public function __construct(?array $values = null)
    {
        if ($values) {
            foreach ($values as $key => $value) {
                if (property_exists($this, $key) && is_string($value)) {
                    $this->$key = $value;
                } else {
                    throw new \InvalidArgumentException(
                        "Cannot set configuration key {$key}: invalid key");
                }
            }
        }
    }
}