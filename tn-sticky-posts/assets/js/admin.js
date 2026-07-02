(function () {
    'use strict';

    function ready() {
        var selectAll = document.getElementById('cb-select-all-1');

        if (!selectAll) {
            return;
        }

        selectAll.addEventListener('change', function () {
            Array.prototype.forEach.call(document.querySelectorAll('input[name="tnsp_post_ids[]"]'), function (checkbox) {
                checkbox.checked = selectAll.checked;
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', ready);
    } else {
        ready();
    }
}());
