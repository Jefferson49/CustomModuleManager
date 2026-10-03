<?php

declare(strict_types=1);

namespace Jefferson49\Webtrees\Module\CustomModuleManager\ModuleUpdates;

use Jefferson49\Webtrees\Helpers\CodebergService;
use Jefferson49\Webtrees\Module\CustomModuleManager\CustomModuleManager;


/**
 * Update API for a custom module, which is hosted in a Codeberg repository
 */
class CodebergModuleUpdate extends PlatformModuleUpdate implements CustomModuleUpdateInterface
{
    const string NAME = 'Codeberg';
    const string URL  = 'https://codeberg.org';

    /**
     * @param string $module_name  The custom module name
     * @param array  $params       The configuration parameters of the update service
     *
     * @return void
     */
    public function __construct(string $module_name, array $params) {

        $this->platform_name    = self::NAME;
        $this->platform_url     = self::URL;
        $this->platform_service = CodebergService::class;

        parent::__construct($module_name, $params);
    }

    /**
     * Get the API token
     *
     * @return string
     */
    public function getApiToken(): string {

        return $this->custom_module_manager->getPreference(CustomModuleManager::PREF_CODEBERG_API_TOKEN, '');
    }
}