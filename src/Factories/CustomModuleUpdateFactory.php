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

namespace Jefferson49\Webtrees\Module\CustomModuleManager\Factories;

use Jefferson49\Webtrees\Module\CustomModuleManager\ModuleUpdates\CustomModuleUpdateInterface;


/**
 * Factory to create a custom module update service
 */
class CustomModuleUpdateFactory
{
    /**
     * Create a custom module update service
     * 
     * @param string $name         Name of the custom module update service
     * @param string $module_name  Name of the custom module
     * @param array  $params       Configuration parameters
     * 
     * @return CustomModuleUpdateInterface   A configured authorization provider. Null, if error 
     */
    public static function make(string $name, string $module_name, array  $params) : ?CustomModuleUpdateInterface
    {
        $name_space = str_replace('\\\\', '\\',__NAMESPACE__ );
        $name_space = str_replace('Factories', 'ModuleUpdates\\', $name_space);

        $module_update_service_names = self::getModuleUpdateServiceNames();

        foreach($module_update_service_names as $class_name) {
            if ($class_name === $name) {
                $class_name = $name_space . $class_name;
                return new $class_name($module_name, $params);
            }
        }

        //If no update service found
        return null;
    }

	/**
     * Return the names of all available custom module update services
     *
     * @return array<class_name => module_update_service_name>
     */ 

    public static function getModuleUpdateServiceNames(): array {

        $module_update_service_names = [];
        $name_space = str_replace('\\\\', '\\',__NAMESPACE__ );
        $name_space_module_updates = str_replace('Factories', 'ModuleUpdates\\', $name_space);

        foreach (get_declared_classes() as $class_name) { 
            if (strpos($class_name, $name_space_module_updates) !==  false) {
                if (in_array($name_space_module_updates . 'CustomModuleUpdateInterface', class_implements($class_name))) {
                    if (str_replace($name_space_module_updates, '',  $class_name) !== 'AbstractModuleUpdate') {
                        $class_name = str_replace($name_space_module_updates, '', $class_name);
                        $module_update_service_names[] = $class_name;    
                    }
                }
            }
        }

        return $module_update_service_names;
    }
}
