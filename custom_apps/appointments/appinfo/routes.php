<?php
/**
 * @copyright Copyright (c) 2023 NextCloud App Build
 *
 * @author NextCloud App Build
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

return [
    'routes' => [
        // Page routes
        ['name' => 'page#index', 'url' => '/', 'verb' => 'GET'],
        
        // Appointment routes
        ['name' => 'appointment#getAll', 'url' => '/appointments', 'verb' => 'GET'],
        ['name' => 'appointment#get', 'url' => '/appointments/{id}', 'verb' => 'GET'],
        ['name' => 'appointment#create', 'url' => '/appointments', 'verb' => 'POST'],
        ['name' => 'appointment#update', 'url' => '/appointments/{id}', 'verb' => 'PUT'],
        ['name' => 'appointment#delete', 'url' => '/appointments/{id}', 'verb' => 'DELETE'],
        
        // Therapist routes
        ['name' => 'therapist#getAll', 'url' => '/therapists', 'verb' => 'GET'],
        ['name' => 'therapist#get', 'url' => '/therapists/{id}', 'verb' => 'GET'],
        ['name' => 'therapist#getSchedule', 'url' => '/therapists/{id}/schedule', 'verb' => 'GET'],
        ['name' => 'therapist#updateSchedule', 'url' => '/therapists/{id}/schedule', 'verb' => 'PUT'],
        
        // Billing routes
        ['name' => 'billing#createInvoice', 'url' => '/invoices', 'verb' => 'POST'],
        ['name' => 'billing#getInvoice', 'url' => '/invoices/{id}', 'verb' => 'GET'],
        ['name' => 'billing#processPayment', 'url' => '/payments', 'verb' => 'POST'],
    ]
];