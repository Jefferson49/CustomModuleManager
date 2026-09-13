<?php

/**
 * webtrees: online genealogy
 * Copyright (C) 2026 webtrees development team
 *                    <http://webtrees.net>
 *
 * CustomModuleManager (webtrees custom module):
 * Copyright (C) 2026 Markus Hemprich
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

namespace Jefferson49\Webtrees\Module\CustomModuleManager\Enums;

use Fisharebest\Webtrees\I18N;

/**
 * The status of a custom module
 */
enum CustomModuleStatus: int
{
    case NOT_INSTALLED = -1;
    case DISABLED      =  0;
    case ENABLED       =  1;

    public function label(): string
    {
        return match ($this) {
            self::NOT_INSTALLED => I18N::translate('Not installed'),
            self::DISABLED      => I18N::translate('Disabled'),
            self::ENABLED       => I18N::translate('Enabled'),
        };
    }
}
