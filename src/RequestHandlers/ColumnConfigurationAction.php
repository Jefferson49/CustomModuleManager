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

use Fisharebest\Webtrees\Registry;
use Fisharebest\Webtrees\Validator;
use Jefferson49\Webtrees\Module\CustomModuleManager\CustomModuleManager;
use Jefferson49\Webtrees\Module\CustomModuleManager\RequestHandlers\CustomModuleUpdatePage;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function redirect;
use function route;

class ColumnConfigurationAction implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $show_column_description    = Validator::parsedBody($request)->boolean('show_column_description', false);
        $show_column_category       = Validator::parsedBody($request)->boolean('show_column_category', false);
        $show_column_date_added     = Validator::parsedBody($request)->boolean('show_column_date_added', false);
        $show_column_update_service = Validator::parsedBody($request)->boolean('show_column_update_service', false);
        $show_column_downloads      = Validator::parsedBody($request)->boolean('show_column_downloads', false);
        $show_column_enabled        = Validator::parsedBody($request)->boolean('show_column_enabled', false);

        $custom_module_manager = Registry::container()->get(CustomModuleManager::class);

        $custom_module_manager->setPreference(CustomModuleManager::PREF_SHOW_COLUMN_DESCR, $show_column_description ? '1' : '0');
        $custom_module_manager->setPreference(CustomModuleManager::PREF_SHOW_COLUMN_CATEGORY, $show_column_category ? '1' : '0');
        $custom_module_manager->setPreference(CustomModuleManager::PREF_SHOW_COLUMN_DATE_ADDED, $show_column_date_added ? '1' : '0');
        $custom_module_manager->setPreference(CustomModuleManager::PREF_SHOW_COLUMN_UPD_SERV, $show_column_update_service ? '1' : '0');
        $custom_module_manager->setPreference(CustomModuleManager::PREF_SHOW_COLUMN_DOWNLOADS, $show_column_downloads ? '1' : '0');
        $custom_module_manager->setPreference(CustomModuleManager::PREF_SHOW_COLUMN_ENABLED, $show_column_enabled ? '1' : '0');

        return redirect(route(CustomModuleUpdatePage::class));
    }
}
