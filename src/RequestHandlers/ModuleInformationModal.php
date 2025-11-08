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
use Fisharebest\Webtrees\Services\ModuleService;
use Fisharebest\Webtrees\Validator;
use Jefferson49\Webtrees\Module\CustomModuleManager\CustomModuleManager;
use Jefferson49\Webtrees\Module\CustomModuleManager\Factories\CustomModuleUpdateFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;


/**
 * Modal module information action
 */
class ModuleInformationModal implements RequestHandlerInterface
{
    use ViewResponseTrait;

    /**
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $module_name                = Validator::queryParams($request)->string('module_name', '');
        $module_update_service_name = Validator::queryParams($request)->string('module_update_service_name', '');
        $module_title               = Validator::queryParams($request)->string('module_title', '');
        $show_default_title         = Validator::queryParams($request)->boolean('show_default_title', false);
        $module_description         = Validator::queryParams($request)->string('module_description', '');
        $show_default_description   = Validator::queryParams($request)->boolean('show_default_description', false);
        $module_status              = Validator::queryParams($request)->string('module_status', '');
        $is_theme                   = Validator::queryParams($request)->boolean('is_theme', false);
        $category                   = Validator::queryParams($request)->string('category', '');
        $current_version            = Validator::queryParams($request)->string('current_version', '');
        $latest_version             = Validator::queryParams($request)->string('latest_version', '');
        $installation_folder        = Validator::queryParams($request)->string('installation_folder', '');
        $documentation_url          = Validator::queryParams($request)->string('documentation_url', '');

        $module_service = new ModuleService;
        $module = $module_service->findByName($module_name, true);
        $module_update_service = CustomModuleUpdateFactory::make($module_name);

        $this->layout = 'layouts/ajax';
        
        return $this->viewResponse(CustomModuleManager::viewsNamespace() . '::modals/module_information', [
                'title'                      => I18N::translate('Module Information'),
                'module_name'                => $module_name,
                'module'                     => $module,
                'module_update_service'      => $module_update_service,
                'module_update_service_name' => $module_update_service_name,
                'module_title'               => $module_title,
                'show_default_title'         => $show_default_title,
                'module_description'         => $module_description,
                'show_default_description'   => $show_default_description,
                'module_status'              => $module_status,
                'is_theme'                   => $is_theme,
                'category'                   => $category,
                'current_version'            => $current_version,
                'latest_version'             => $latest_version,
                'installation_folder'        => $installation_folder,
                'documentation_url'          => $documentation_url,
        ]);
    }
}
