<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Connect.
 *
 * @package    local_connect
 * @copyright  2015 Skylar Kelty <S.Kelty@kent.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * Connect Renderer.
 *
 * @copyright  2015 Skylar Kelty <S.Kelty@kent.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_connect_renderer extends plugin_renderer_base
{
    /**
     * Render the index page.
     */
    public function render_index() {
        echo <<<HTML5
        <p>
            <button class="btn btn-info" type="button" data-toggle="collapse" data-target="#key" aria-expanded="false" aria-controls="key">
                Show Key
            </button>
        </p>
        <div id="key" class="collapse">
            <div class="well">
                <ul>
                    <li class="status_key key_item">= Status indicator (normaly coloured)</li>
                    <li class="warning_key key_item">= Delivery is no longer in sds</li>
                    <li class="link_key key_item">= Link to active moodle delivery</li>
                    <li class="delete_key key_item">= Removed delivery from moodle</li>
                    <li class="unlink_key key_item">= Unlink child delivery from parent</li>
                    <li class="flag_key key_item">= Delivery shares its module code with an already created module</li>
                </ul>
            </div>
        </div>

        <div id="da_wrapper" class="row">
            <div id="dapage_app" class="col-xs-12 col-sm-10">
                <div class="table-responsive">
                    <table id="datable" class="table">
                        <thead>
                            <tr>
                                <th>Id</th>
                                <th>Status</th>
                                <th>Code</th>
                                <th>Name</th>
                                <th>Campus</th>
                                <th>Duration</th>
                                <th>Version</th>
                                <th>Options</th>
                            </tr>
                        </thead>
                        <tfoot>
                                <th></th>
                                <th id="filter-status"></th>
                                <th></th>
                                <th></th>
                                <th></th>
                                <th></th>
                                <th></th>
                                <th></th>
                        </tfoot>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>

            <div id="right_bar_wrap" class="col-xs-12 col-sm-2">
                <div class="data_refresh btn btn-info">Refresh deliveries</div>

                <div id="jobs_wrapper">
                    <div id="select_buttons" class="btn-group" role="group" aria-label="Selections">
                        <button id="select_all" type="button" class="btn btn-success">Select all</button>
                        <button id="deselect_all" type="button" class="btn btn-danger">Deselect all</button>
                    </div>

                    <div id="jobs">
                        <div class="job_number_text">you currently have</div>
                        <div id="job_number">0</div>
                        <div class="job_number_text">deliveries selected</div>
                        <div id="display_list_toggle">
                            <button>show deliveries</button>
                            <div class="arrow_border"></div>
                            <div class="arrow_light"></div>
                        </div>
                        <ul>
                        </ul>
                    </div>

                    <div id="process_jobs">
                        <button id="push_deliveries" disabled="disabled">No selection</button>
                        <button id="merge_deliveries" disabled="disabled">No selection</button>
                    </div>

                    <div id="options_bar">
                        <div id="status_toggle">
                            <div class="checkbox">
                                <label id="label-unprocessed" for="unprocessed">
                                    <input type="checkbox" name="unprocessed" value="unprocessed" id="unprocessed" class="status_checkbox" checked="checked">
                                    unprocessed
                                </label>
                            </div>
                            <div class="checkbox">
                                <label id="label-created_in_moodle" for="created_in_moodle">
                                    <input type="checkbox" name="created_in_moodle" value="created_in_moodle" id="created_in_moodle" class="status_checkbox">
                                    created in moodle
                                </label>
                            </div>
                        </div>
                        <div id="dasearch" class="form-group">
                            <input type="search" class="form-control" id="dasearch-box" name="dasearch-box" placeholder="Search" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
HTML5;
    }

    /**
     * Render the index page JS helpers.
     * @param $categories
     * @throws coding_exception
     */
    public function render_index_js($categories) {
        global $CFG;

        // Our categories.
        $catoptions = '';
        foreach ($categories as $id => $name) {
            $catoptions .= '<option value="'.$id.'">'.$name.'</option>';
        }

        $connecterror = get_string('connect_error', 'local_connect');

        echo <<<HTML5
        <div id="dialog-form" class="bootstrap" title="Edit details">
            <div id="edit_notifications"></div>
            <form>
                <input type="hidden" name="primary_child" id="primary_child" value="" />
                <div class="form-group">
                    <label for="shortname">Shortname</label>
                    <input type="text" class="form-control" id="shortname" name="shortname" placeholder="Shortname" disabled="disabled">
                </div>
                <div class="form-group">
                    <label for="shortname_ext">Shortname extension</label>
                    <input type="text" class="form-control" id="shortname_ext" name="shortname_ext" placeholder="AUT/SPR/SUM">
                </div>
                <div class="form-group">
                    <label for="fullname">Fullname</label>
                    <input type="text" class="form-control" id="fullname" name="fullname" placeholder="Fullname" disabled="disabled">
                </div>
                <div class="form-group">
                    <label for="category">Category</label>
                    <select name="category" id="category" class="form-control">
                        $catoptions
                    </select>
                </div>
            </form>
        </div>
        <div id="dialog-confirm" title="Confirm">
            <p>
                <span class="ui-icon ui-icon-alert" style="float:left; margin:0 7px 20px 0;"></span>
                These deliveries will be pushed as-is. If you want to edit a module's information, please push it separately.
            </p>
        </div>
        <div id="dialog_error">$connecterror</div>
HTML5;
    }

    /**
     * (BETA) Index.
     */
    public function render_beta($courses) {
        echo <<<HTML5
        <div id="da_wrapper" class="row">
            <div id="dapage_app" class="col-xs-12 col-sm-10">
                <div class="table-responsive">
HTML5;

        $table = new \flexible_table('da-courses');
        $table->define_columns(array(
            'delivery_key',
            'module_code',
            'module_name',
            'campus',
            'duration',
            'version',
            'actions'
        ));
        $table->define_headers(array(
            'Delivery key',
            'Module code',
            'Module name',
            'Campus',
            'Duration',
            'Version',
            ''
        ));
        $table->define_baseurl(new \moodle_url('/local/connect/beta.php'));
        $table->pagesize(15, count($courses));
        $table->setup();

        $chunkstart = $table->get_page_start();
        $chunksize = $table->get_page_size();

        $courses = array_slice($courses, $chunkstart, $chunksize);
        foreach ($courses as $course) {
            $table->add_data(array(
                $course->module_delivery_key,
                $course->module_code,
                $course->module_title,
                $course->campus->name,
                $course->module_length,
                $course->module_version,
                '<input name="id" value="' . $course->id . '" type="checkbox" />'
            ), 'row-' . $course->id);
        }

        $table->finish_output();


        echo <<<HTML5
                </div>
            </div>

            <div id="right_bar_wrap" class="col-xs-12 col-sm-2">
                <div id="jobs_wrapper">
                    <div id="select_buttons" class="btn-group" role="group" aria-label="Selections">
                        <button id="select_all" type="button" class="btn btn-success">Select all</button>
                        <button id="deselect_all" type="button" class="btn btn-danger">Deselect all</button>
                    </div>

                    <div id="jobs">
                        <div class="job_number_text">you currently have</div>
                        <div id="job_number">0</div>
                        <div class="job_number_text">deliveries selected</div>
                        <div id="display_list_toggle">
                            <button>show deliveries</button>
                            <div class="arrow_border"></div>
                            <div class="arrow_light"></div>
                        </div>
                        <ul>
                        </ul>
                    </div>

                    <div id="process_jobs">
                        <button id="push_deliveries" disabled="disabled">No selection</button>
                        <button id="merge_deliveries" disabled="disabled">No selection</button>
                    </div>

                    <div id="options_bar">
                        <div id="dasearch" class="form-group">
                            <input type="search" class="form-control" id="dasearch-box" name="dasearch-box" placeholder="Search" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
HTML5;
    }
}
