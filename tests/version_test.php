<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace block_datacurso_recomendate;

/**
 * Tests for the plugin metadata declared in version.php.
 *
 * @package     block_datacurso_recomendate
 * @category    test
 * @copyright   2026 Datacurso
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversNothing
 */
final class version_test extends \advanced_testcase {
    /**
     * The declared supported range includes the Moodle branch the tests run on.
     *
     * Spec: MDL-INT-001 C1.
     */
    public function test_supported_range_covers_running_branch(): void {
        global $CFG;

        $pluginman = \core_plugin_manager::instance();
        $plugininfo = $pluginman->get_plugin_info('block_datacurso_recomendate');
        $branch = (int) $CFG->branch;

        $this->assertSame(
            \core_plugin_manager::VERSION_SUPPORTED,
            $pluginman->check_explicitly_supported($plugininfo, $branch),
            'Moodle ' . $branch . ' is outside $plugin->supported = ['
                . implode(', ', (array) $plugininfo->pluginsupported) . '].'
        );
    }

    /**
     * The declared supported range is two integers starting at Moodle 4.5.
     */
    public function test_supported_range_is_well_formed(): void {
        $plugininfo = \core_plugin_manager::instance()->get_plugin_info('block_datacurso_recomendate');
        $supported = $plugininfo->pluginsupported;

        $this->assertIsArray($supported);
        $this->assertCount(2, $supported);
        $this->assertIsInt($supported[0]);
        $this->assertIsInt($supported[1]);
        $this->assertSame(405, $supported[0]);
        $this->assertLessThanOrEqual($supported[1], $supported[0]);
    }
}
