jQuery(document).ready(function($) {
    // Auto-scroll log viewer to bottom
    var $logViewer = $('.slix-debug-log-viewer');
    if ($logViewer.length) {
        $logViewer.scrollTop($logViewer[0].scrollHeight);
    }
    
    // Handle log clearing confirmation
    $('.slix-clear-log-form').on('submit', function(e) {
        if (!confirm('Are you sure you want to clear the log file? This cannot be undone.')) {
            e.preventDefault();
        }
    });

    //copy logger line
    $('#copy-savolix-log-snippet').on('click', function(){
        const text = $("#savolix-log-snippet").text();
        navigator.clipboard.writeText(text).then(function() {
            alert("Copied to clipboard!");
        });
    })

});

