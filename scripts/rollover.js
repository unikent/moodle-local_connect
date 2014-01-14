/**
 * Calls a proxy script to populate rollover autocompletes
 */
M.local_rollover = {
    Y : null,
    transaction : [],
    init : function(Y) {
        Y.one('#id_target').plug(Y.Plugin.AutoComplete, {
          source: M.cfg.wwwroot + '/local/connect/ajax/course_search.php?name={query}'
        });

        Y.one('#id_source').plug(Y.Plugin.AutoComplete, {
          source: M.cfg.wwwroot + '/local/connect/ajax/course_search.php?name={query}&source=' + Y.one('#id_source_moodle').get("value")
        });

        Y.one('#id_source_moodle').on('change', function(e) {
            Y.one('#id_source').ac.set('source', M.cfg.wwwroot + '/local/connect/ajax/course_search.php?name={query}&source=' + e.target.get("value"));
        });
    }
}
