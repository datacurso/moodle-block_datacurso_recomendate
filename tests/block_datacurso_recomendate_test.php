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
 * Tests for the rendered content of the recommended courses block.
 *
 * @package     block_datacurso_recomendate
 * @category    test
 * @copyright   2026 Datacurso
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers      \block_datacurso_recomendate
 */
final class block_datacurso_recomendate_test extends \advanced_testcase {
    /**
     * Render the block for a user who has at least one recommendation.
     *
     * @return string The block content HTML.
     */
    private function render_block_with_recommendations(): string {
        global $DB;

        $generator = $this->getDataGenerator();
        $category = $generator->create_category();
        $user = $generator->create_user();

        // A course the user is not enrolled in becomes a recommendation candidate.
        $generator->create_course(['category' => $category->id, 'visible' => 1]);

        // A liked activity in another course of the same category builds a 100% category preference.
        $ratedcourse = $generator->create_course(['category' => $category->id, 'visible' => 1]);
        $ratedpage = $generator->create_module('page', ['course' => $ratedcourse->id]);
        $generator->enrol_user($user->id, $ratedcourse->id);
        $now = time();
        $DB->insert_record('local_datacurso_ratings', (object) [
            'userid' => $user->id,
            'cmid' => $ratedpage->cmid,
            'courseid' => $ratedcourse->id,
            'categoryid' => $category->id,
            'rating' => 1,
            'feedback' => '',
            'timecreated' => $now,
            'timemodified' => $now,
        ]);

        \cache::make('local_datacurso_ratings', 'recommendations')->purge();
        $this->setUser($user);

        $record = $generator->create_block('datacurso_recomendate');
        $page = new \moodle_page();
        $page->set_context(\context_system::instance());
        $page->set_url('/my/index.php');
        $block = block_instance('datacurso_recomendate', $record, $page);

        return $block->get_content()->text;
    }

    /**
     * The view selector uses the Bootstrap 5 form-select class, not the deprecated custom-select.
     *
     * Spec: MDL-E2E-014.
     */
    public function test_view_selector_uses_bootstrap5_class(): void {
        global $CFG;
        $this->resetAfterTest();

        $html = $this->render_block_with_recommendations();

        $this->assertMatchesRegularExpression('/<select[^>]*id="viewmode-selector"[^>]*>/', $html);
        preg_match('/<select[^>]*id="viewmode-selector"[^>]*>/', $html, $selectmatch);
        preg_match('/class="([^"]*)"/', $selectmatch[0], $classmatch);
        $classes = explode(' ', $classmatch[1] ?? '');

        $this->assertContains('form-select', $classes);
        // Moodle 4.5 core adds custom-select itself; from 5.0 it only lives in the deprecated BS4 layer.
        if ((int) $CFG->branch >= 500) {
            $this->assertNotContains('custom-select', $classes);
        }
    }
}
