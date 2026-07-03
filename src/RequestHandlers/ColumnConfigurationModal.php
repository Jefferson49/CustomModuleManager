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
        $show_column_description    = Validator::queryParams($request)->string('show_column_description', '');
        $show_column_category       = Validator::queryParams($request)->string('show_column_category', '');
        $show_column_update_service = Validator::queryParams($request)->string('show_column_update_service', '');
        $show_column_downloads      = Validator::queryParams($request)->string('show_column_downloads', '');
        $show_column_enabled        = Validator::queryParams($request)->string('show_column_enabled', '');

        $this->layout = 'layouts/ajax';
        
        return $this->viewResponse(CustomModuleManager::viewsNamespace() . '::modals/column_configuration', [
            'title'                      => I18N::translate('Configure columns'),
            'show_column_description'    => $show_column_description,
            'show_column_category'       => $show_column_category,
            'show_column_update_service' => $show_column_update_service,
            'show_column_downloads'      => $show_column_downloads,
            'show_column_enabled'        => $show_column_enabled,
        ]);
    }
}
