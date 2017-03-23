<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Extension\Document\Areabrick;

interface AreabrickManagerInterface
{
    /**
     * Registers an areabrick on the manager
     *
     * @param AreabrickInterface $brick
     */
    public function register(AreabrickInterface $brick);

    /**
     * Fetches a brick by ID
     *
     * @param string $id
     *
     * @return AreabrickInterface
     */
    public function getBrick($id);

    /**
     * Lists all registered areabricks
     *
     * @return AreabrickInterface[]
     */
    public function getBricks();

    /**
     * Enables an areabrick
     *
     * @param string|AreabrickInterface $brick
     */
    public function enable($brick);

    /**
     * Disables an areabrick
     *
     * @param string|AreabrickInterface $brick
     */
    public function disable($brick);

    /**
     * Determines if an areabrick is enabled. Bricks are enabled by default an can be switched off by setting
     * the state explicitely to false in the extension config.
     *
     * @param string|AreabrickInterface $brick
     *
     * @return bool
     */
    public function isEnabled($brick);
}