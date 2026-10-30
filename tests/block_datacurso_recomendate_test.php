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
     * @param int $count Number of recommended courses to create.
     * @param \moodle_url|null $pageurl URL of the page that hosts the block (defaults to the Dashboard).
     * @param array $candidate Extra properties for each recommended course (for example its fullname).
     * @return string The block content HTML.
     */
    private function render_block_with_recommendations(int $count = 1, ?\moodle_url $pageurl = null, array $candidate = []): string {
        global $DB;

        $generator = $this->getDataGenerator();
        $category = $generator->create_category();
        $user = $generator->create_user();

        // Courses the user is not enrolled in become recommendation candidates.
        for ($i = 0; $i < $count; $i++) {
            $generator->create_course(['category' => $category->id, 'visible' => 1] + $candidate);
        }

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
            // Workplace ratings are tenant-scoped and the recommendation service filters by the user's tenant.
            'tenant_id' => \tool_tenant\tenancy::get_tenant_id($user->id),
        ]);

        \cache::make('local_datacurso_ratings', 'recommendations')->purge();
        $this->setUser($user);

        $record = $generator->create_block('datacurso_recomendate');
        $page = new \moodle_page();
        $page->set_context(\context_system::instance());
        $page->set_url($pageurl ?? new \moodle_url('/my/index.php'));
        $block = block_instance('datacurso_recomendate', $record, $page);

        return $block->get_content()->text;
    }

    /**
     * The view selector uses the Bootstrap 5 form-select class, not the deprecated custom-select.
     *
     * Spec: MDL-E2E-014.
     */
    public function test_view_selector_uses_bootstrap5_class(): void {
        global $CFG, $DB;
        $this->resetAfterTest();

        // Recommendations need the ratings tables; CI installs the block without its dependency.
        if (!$DB->get_manager()->table_exists('local_datacurso_ratings')) {
            $this->markTestSkipped('local_datacurso_ratings is not installed.');
        }

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

    /**
     * The paging bar links point to the page that hosts the block, keeping its parameters.
     *
     * Spec: MDL-E2E-003.
     */
    public function test_pagination_links_use_current_page_url(): void {
        global $DB;
        $this->resetAfterTest();

        if (!$DB->get_manager()->table_exists('local_datacurso_ratings')) {
            $this->markTestSkipped('local_datacurso_ratings is not installed.');
        }

        // Seven recommendations need two pages of six.
        $html = $this->render_block_with_recommendations(7, new \moodle_url('/index.php', ['redirect' => 0]));

        $this->assertMatchesRegularExpression('/href="[^"]*\/index\.php\?redirect=0&amp;page=1"/', $html);
        $this->assertStringNotContainsString('/my/index.php', $html);
    }

    /**
     * A failure of the recommendations service degrades to a controlled message and is logged.
     *
     * Spec: MDL-INT-012.
     */
    public function test_service_failure_shows_error_message(): void {
        global $CFG;
        $this->resetAfterTest();

        require_once($CFG->dirroot . '/blocks/moodleblock.class.php');
        block_load_class('datacurso_recomendate');

        $this->setUser($this->getDataGenerator()->create_user());
        $record = $this->getDataGenerator()->create_block('datacurso_recomendate');
        $page = new \moodle_page();
        $page->set_context(\context_system::instance());
        $page->set_url('/my/index.php');

        $block = new class extends \block_datacurso_recomendate {
            /**
             * Simulate a broken recommendations service.
             *
             * @param int $userid User ID.
             * @return array
             */
            protected function get_recommendations(int $userid): array {
                throw new \dml_read_exception('Simulated service failure');
            }
        };
        $block->_load_instance($record, $page);

        $html = $block->get_content()->text;

        $this->assertDebuggingCalled();
        $this->assertStringContainsString(get_string('recserror', 'block_datacurso_recomendate'), $html);
        $this->assertStringNotContainsString('viewmode-selector', $html);
    }

    /**
     * Course names with special characters are escaped exactly once.
     *
     * Spec: MDL-INT-006.
     */
    public function test_course_name_is_escaped_once(): void {
        global $DB;
        $this->resetAfterTest();

        if (!$DB->get_manager()->table_exists('local_datacurso_ratings')) {
            $this->markTestSkipped('local_datacurso_ratings is not installed.');
        }

        $html = $this->render_block_with_recommendations(candidate: ['fullname' => 'Science & "Arts"']);

        $this->assertStringContainsString('>Science &amp; &quot;Arts&quot;</a>', $html);
        $this->assertStringContainsString('alt="Science &amp; &quot;Arts&quot;"', $html);
        $this->assertStringNotContainsString('&amp;amp;', $html);
        $this->assertStringNotContainsString('&amp;quot;', $html);
    }

    /**
     * Course names never inject markup into the block.
     *
     * Spec: MDL-INT-006.
     */
    public function test_course_name_does_not_inject_markup(): void {
        global $DB;
        $this->resetAfterTest();

        if (!$DB->get_manager()->table_exists('local_datacurso_ratings')) {
            $this->markTestSkipped('local_datacurso_ratings is not installed.');
        }

        $html = $this->render_block_with_recommendations(candidate: ['fullname' => '<script>alert(1)</script>Evil" onerror="x']);

        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringNotContainsString('" onerror="x', $html);
    }
}
