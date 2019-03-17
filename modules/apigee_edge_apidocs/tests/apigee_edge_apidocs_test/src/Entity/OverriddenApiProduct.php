<?php

/**
 * Copyright 2019 Google Inc.
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License version 2 as published by the
 * Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
 * or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public
 * License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc., 51
 * Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 */

namespace Drupal\apigee_edge_apidocs_test\Entity;

use Drupal\Core\Entity\ContentEntityBase;

/**
 * Class OverriddenApiProduct.
 * Allows using a reference field to an api_product entity without enabling
 * the apigee_edge module.
 *
 * @ContentEntityType(
 *   id = "api_product",
 *   label = @Translation("Overriden API Product"),
 *   handlers = {
 *     "storage" = "Drupal\Core\Entity\ContentEntityNullStorage",
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *   },
 * )
 */
final class OverriddenApiProduct extends ContentEntityBase {}
