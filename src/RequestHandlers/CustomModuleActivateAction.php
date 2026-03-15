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

use Fisharebest\Webtrees\DB;
use Fisharebest\Webtrees\FlashMessages;
use Fisharebest\Webtrees\I18N;
use Fisharebest\Webtrees\Module\ModuleCustomInterface;
use Fisharebest\Webtrees\Services\ModuleService;
use Fisharebest\Webtrees\Validator;
use Jefferson49\Webtrees\Internationalization\MoreI18N;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function redirect;
use function route;

// Code from: Fisharebest\Webtrees\Http\RequestHandlers\ModulesAllAction
final class CustomModuleActivateAction implements RequestHandlerInterface
{
    public function __construct(
        private readonly ModuleService $module_service,
    ) {
    }
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $modules = $this->module_service->all(true);

        foreach ($modules as $module) {

            if ($module instanceof  ModuleCustomInterface) {
                $new_status = Validator::parsedBody($request)->boolean('status-' . $module->name(), false);
                $old_status = $module->isEnabled();

                if ($new_status !== $old_status) {

                    //ToDo: Check new database schema in webtrees 2.2.6?

                    DB::table('module')
                        ->where('module_name', '=', $module->name())
                        ->update(['status' => $new_status ? 'enabled' : 'disabled']);

                    if ($new_status) {
                        $message = MoreI18N::xlate('The module “%s” has been enabled.', $module->title());
                    } else {
                        $message = MoreI18N::xlate('The module “%s” has been disabled.', $module->title());
                    }

                    FlashMessages::addMessage($message, 'success');
                }
            }
        }

        return redirect(route(CustomModuleUpdatePage::class));
    }
}
