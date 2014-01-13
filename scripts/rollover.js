/**
 * Calls a proxy script to populate rollover autocompletes
 */
M.local_rollover = {
    Y : null,
    transaction : [],
    init : function(Y) {
        Y.one('#rollover_target').plug(Y.Plugin.AutoComplete, {
          source: M.cfg.wwwroot + '/local/connect/ajax/course_search.php?name={query}'
        });

        Y.one('#source_id').plug(Y.Plugin.AutoComplete, {
          source: M.cfg.wwwroot + '/local/connect/ajax/course_search.php?name={query}&source=' + Y.one('#rollover_source').get("value")
        });

        Y.one('#rollover_source').on('change', function(e) {
            Y.one('#source_id').ac.set('source', M.cfg.wwwroot + '/local/connect/ajax/course_search.php?name={query}&source=' + e.target.get("value"));
        });
    }
}
