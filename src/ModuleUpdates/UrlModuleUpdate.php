<?php

/**
 * webtrees: online genealogy
 * Copyright (C) 2025 webtrees development team
 *                    <http://webtrees.net>
 *
 * CustomModuleManager (webtrees custom module):
 * Copyright (C) 2025 Markus Hemprich
 *                    <http://www.familienforschung-hemprich.de>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 *
 * 
 * CustomModuleManager
 *
 * A weebtrees(https://webtrees.net) 2.2 custom module to manage custom modules
 * 
 */

declare(strict_types=1);

namespace Jefferson49\Webtrees\Module\CustomModuleManager\ModuleUpdates;

use Fisharebest\Webtrees\I18N;
use Jefferson49\Webtrees\Module\CustomModuleManager\Exceptions\CustomModuleManagerException;


/**
 * Update API for a custom module, which is based on a simple download URL
 */
class UrlModuleUpdate extends AbstractModuleUpdate implements CustomModuleUpdateInterface 
{
    const NAME = 'URL';

    //The download URL
    protected string $download_url;

    //The documentation URL
    protected string $documentation_url;

    //The latest version of the module

    protected string $latest_version;


    /**
     * @param string $module_name  The custom module name
     * @param array  $params       The configuration parameters of the update service
     * 
     * @return void
     */
    public function __construct(string $module_name, array  $params) {

        $this->module_name    = $module_name;

        if (array_key_exists('download_url', $params)) {
            $this->download_url = $params['download_url'];
        }
        else {
            throw new CustomModuleManagerException(I18N::translate('Could not create the %s update service. Configuration parameter "%s" missing.', basename(str_replace('\\', '/', __CLASS__)) , 'download_url'));
        }

        if (array_key_exists('documentation_url', $params)) {
            $this->documentation_url = $params['documentation_url'];
        }
        else {
            $this->documentation_url = '';
        }

        if (array_key_exists('latest_version', $params)) {
            $this->latest_version = $params['latest_version'];
        }
        else {
            $this->latest_version = '';
        }

        $this->is_theme = self::identifyThemeFromConfig($module_name, $params);
    }

    /**
     * The name of the module update service
     *
     * @return string
     */
    public function name(): string {

        return self::NAME;
    }
    
    /**
     * Where can we download the module
     * 
     * @param  string $version  The version of the module; latest version if empty
     * @return string
     */
    public function downloadUrl(string $version = ''): string
    {
        return $this->download_url;
    }

    /**
     * Where can we find a documentation for the module
     * 
     * @return string
     */
    public function documentationUrl(): string 
    {
        return $this->documentation_url;
    }

    /**
     * Get the latest version of this module
     *
     * @return string
     */
    public function customModuleLatestVersion(): string
    {
        $module = $this->getModule();

        if ($module !== null) {
            return $module->customModuleLatestVersion();
        }

        return $this->latest_version;
    }
}
