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

namespace Jefferson49\Webtrees\Module\CustomModuleManager\RequestHandlers;

use Fisharebest\Webtrees\Http\ViewResponseTrait;
use Fisharebest\Webtrees\I18N;
use Fisharebest\Webtrees\Registry;
use Jefferson49\Webtrees\Module\CustomModuleManager\CustomModuleManager;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;


/**
 * Show module information modal
 */
class ColumnConfigurationModal implements RequestHandlerInterface
{
    use ViewResponseTrait;

    /**
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->layout = 'layouts/ajax';

        $custom_module_manager = Registry::container()->get(CustomModuleManager::class);
        
        return $this->viewResponse(CustomModuleManager::viewsNamespace() . '::modals/column_configuration', [
            'title'                      => I18N::translate('Configure columns'),
            CustomModuleManager::PREF_SHOW_COLUMN_DESCR      => boolval($custom_module_manager->getPreference(CustomModuleManager::PREF_SHOW_COLUMN_DESCR, '1')),
            CustomModuleManager::PREF_SHOW_COLUMN_CATEGORY   => boolval($custom_module_manager->getPreference(CustomModuleManager::PREF_SHOW_COLUMN_CATEGORY, '1')),
            CustomModuleManager::PREF_SHOW_COLUMN_DATE_ADDED => boolval($custom_module_manager->getPreference(CustomModuleManager::PREF_SHOW_COLUMN_DATE_ADDED, '1')),
            CustomModuleManager::PREF_SHOW_COLUMN_UPD_SERV   => boolval($custom_module_manager->getPreference(CustomModuleManager::PREF_SHOW_COLUMN_UPD_SERV, '1')),
            CustomModuleManager::PREF_SHOW_COLUMN_DOWNLOADS  => boolval($custom_module_manager->getPreference(CustomModuleManager::PREF_SHOW_COLUMN_DOWNLOADS, '1')),
            CustomModuleManager::PREF_RESPONSIVE_TABLE       => boolval($custom_module_manager->getPreference(CustomModuleManager::PREF_RESPONSIVE_TABLE, '0')),            
        ]);
    }
}
