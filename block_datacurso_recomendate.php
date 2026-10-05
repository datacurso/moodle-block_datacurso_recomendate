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

/**
 * Block class for DataCurso Recomendate.
 *
 * @package     block_datacurso_recomendate
 * @copyright   Josue <josue@datacurso.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Block class for displaying recommended courses.
 */
class block_datacurso_recomendate extends block_base {
    /**
     * Initializes the block title.
     *
     * @return void
     */
    public function init() {
        $this->title = get_string('blocktitle', 'block_datacurso_recomendate');
    }

    /**
     * Specifies the page formats where this block can be added.
     *
     * @return array
     */
    public function applicable_formats() {
        return ['my' => true, 'site' => true];
    }

    /**
     * Retrieves the course image URL.
     * Attempts to get a custom course image or generates a default one.
     *
     * @param int $courseid Course ID.
     * @return string|null Image URL or null.
     */
    protected function get_course_image($courseid) {
        global $CFG;

        try {
            // Method 1: Use the core_course_list_element class (Moodle 3.0+).
            if (class_exists('core_course_list_element')) {
                require_once($CFG->dirroot . '/course/lib.php');
                $course = get_course($courseid);
                $courseobj = new core_course_list_element($course);

                // Get the first available image.
                foreach ($courseobj->get_course_overviewfiles() as $file) {
                    if ($file->is_valid_image()) {
                        return moodle_url::make_pluginfile_url(
                            $file->get_contextid(),
                            $file->get_component(),
                            $file->get_filearea(),
                            null,
                            $file->get_filepath(),
                            $file->get_filename()
                        )->out(false);
                    }
                }
            } else {
                // Method 2: Search directly in the file area (fallback for older Moodle versions).
                $context = context_course::instance($courseid);
                $fs = get_file_storage();
                $files = $fs->get_area_files(
                    $context->id,
                    'course',
                    'overviewfiles',
                    false,
                    'filename',
                    false
                );

                if (!empty($files)) {
                    foreach ($files as $file) {
                        // Check if it is a valid image.
                        $mimetype = $file->get_mimetype();
                        if (strpos($mimetype, 'image/') === 0) {
                            return moodle_url::make_pluginfile_url(
                                $file->get_contextid(),
                                $file->get_component(),
                                $file->get_filearea(),
                                $file->get_itemid(),
                                $file->get_filepath(),
                                $file->get_filename()
                            )->out(false);
                        }
                    }
                }
            }

            // Method 3: Use Moodle's generated default image.
            return $this->get_default_course_image($courseid);
        } catch (Exception $e) {
            debugging('Error getting course image for ' . $courseid . ': ' . $e->getMessage(), DEBUG_DEVELOPER);
            return $this->get_default_course_image($courseid);
        }
    }

    /**
     * Generates a default course image URL.
     * Uses Moodle’s built-in system or a placeholder image.
     *
     * @param int $courseid Course ID.
     * @return string Default image URL.
     */
    protected function get_default_course_image($courseid) {
        global $OUTPUT;

        // Option 1: Use Moodle’s generated image system (Moodle 3.6+).
        if (method_exists($OUTPUT, 'get_generated_image_for_id')) {
            return $OUTPUT->get_generated_image_for_id($courseid);
        }

        // Option 2: Use the generic course image.
        if (method_exists($OUTPUT, 'image_url')) {
            return $OUTPUT->image_url('course_defaultimage', 'moodle')->out(false);
        }

        // Option 3: Generate a color pattern based on the course ID.
        return $this->generate_pattern_image_url($courseid);
    }

    /**
     * Generates a color-based pattern image URL for a course.
     * Uses an external placeholder service.
     *
     * @param int $courseid Course ID.
     * @return string Generated image URL.
     */
    protected function generate_pattern_image_url($courseid) {
        // Generate a color based on the course ID.
        $colors = [
            '#1f77b4', '#ff7f0e', '#2ca02c', '#d62728', '#9467bd',
            '#8c564b', '#e377c2', '#7f7f7f', '#bcbd22', '#17becf',
        ];
        $color = $colors[$courseid % count($colors)];
        $color = str_replace('#', '', $color);

        // Use placeholder.com or a custom image generator.
        return "https://via.placeholder.com/400x200/{$color}/ffffff?text=Course+{$courseid}";
    }

    /**
     * Returns the main content of the block.
     *
     * @return stdClass Block content.
     */
    public function get_content() {
        global $USER, $CFG, $OUTPUT;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->text = '';

        require_once($CFG->dirroot . '/local/datacurso_ratings/classes/recommendations/service.php');

        $viewmode = optional_param('viewmode', '', PARAM_ALPHA);
        if (empty($viewmode)) {
            $viewmode = 'cards';
        }

        $page = optional_param('page', 0, PARAM_INT);
        $perpage = 6;

        // Get recommendations.
        $recs = \local_datacurso_ratings\recommendations\service::get_recommendations_for_user($USER->id, 50);
        $total = count($recs);

        if (empty($recs)) {
            $this->content->text = $OUTPUT->notification(
                get_string('norecs', 'block_datacurso_recomendate'),
                'info'
            );
            return $this->content;
        }

        // Manual pagination.
        $pagedrecs = array_slice($recs, $page * $perpage, $perpage);

        // Build enhanced course list with images.
        $courses = [];
        foreach ($pagedrecs as $rec) {
            $courseurl = new moodle_url('/course/view.php', ['id' => $rec['courseid']]);

            // Get course image using the improved method.
            $imgurl = $this->get_course_image($rec['courseid']);

            $courses[] = [
                'fullname' => format_string($rec['fullname']),
                'courseurl' => $courseurl->out(false),
                'imageurl' => $imgurl,
                'hasimage' => !empty($imgurl),
                'course_satisfaction' => $rec['course_satisfaction'],
                'category_preference_pct' => $rec['category_preference_pct'],
                'score' => round($rec['score'], 2),
            ];
        }

        // View selector.
        $baseurl = new moodle_url('/my/index.php');
        $selector = html_writer::select(
            [
                'cards' => get_string('view_cards', 'block_datacurso_recomendate'),
                'list' => get_string('view_list', 'block_datacurso_recomendate'),
            ],
            'viewmode',
            $viewmode,
            false,
            [
                'id' => 'viewmode-selector',
                'class' => 'form-select',
            ]
        );

        // Pagination.
        $pagination = $OUTPUT->paging_bar($total, $page, $perpage, $baseurl);

        // Load AMD JS module.
        $this->page->requires->js_call_amd('block_datacurso_recomendate/selectview', 'init');

        $data = [
            'viewmode' => $viewmode,
            'viewmode_is_cards' => ($viewmode === 'cards'),
            'viewmode_is_list' => ($viewmode === 'list'),
            'selector' => $selector,
            'courses' => $courses,
            'pagination' => $pagination,
        ];

        $this->content->text = $OUTPUT->render_from_template(
            'block_datacurso_recomendate/recommendations',
            $data
        );

        return $this->content;
    }
}
