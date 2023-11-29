function closeSVGWindow(targetWindow) {
    targetWindow.document.write('<h3>SVG file printed.  Please close this window.</h3>');
    targetWindow.close();
}

function svgembed_printContent(path) {
    // Open window and load content
    var svgembed_print = window.open('', '_printwindow', 'location=no,height=400,width=600,scrollbars=yes,status=no');
    svgembed_print.document.write('<html><head></head><body><img src="' + decodeURIComponent(path) + '" ' +
                                  'style="width:100%;height:100%"></body></html>');
    svgembed_print.document.close();

    // Print
    setTimeout(function(){ svgembed_print.window.print(); }, 1000);
    setTimeout(function(){ closeSVGWindow(svgembed_print); }, 2000);
}

function svgembed_onMouseOver(object_id) {
    document.getElementById(object_id).className = document.getElementById(object_id).className + ' svgembed_print_border';
    return false;
}

function svgembed_onMouseOut(object_id) {
    document.getElementById(object_id).className = document.getElementById(object_id).className.replace(' svgembed_print_border', '');
    return false;
}

jQuery(function(){
    function updateSVGLinksInSVG(svgElement) {
        // Find all <a> elements within each SVG
        jQuery(svgElement).find('a').each(function() {
            var $link = jQuery(this);
            var href = $link.attr('href');

            // Check if the href matches the pattern
            if (href && href.startsWith('[[') && href.endsWith(']]')) {
                // Extract namespace, page, and section
                var parts = href.slice(2, -2).split(':');
                var namespace = parts[0];
                var pageSection = parts[1].split('#');
                var page = pageSection[0];
                var section = pageSection[1];

                // Construct the new URL (modify this according to your URL structure)
                var newHref = DOKU_BASE + namespace + '/' + page + (section ? '#' + section : '');

                // Update the href attribute
                $link.attr('href', newHref);

                $link.attr('target','_blank');
            }
        });
    }


    // Function to handle an object element
    function handleObject($obj) {
        var svgDoc = $obj[0].contentDocument; // Get the document of the object tag
        if (svgDoc) {
            var svg = svgDoc.querySelector('svg');
            if (svg) {
                
                setTimeout(function(){updateSVGLinksInSVG(svg); }, 1000);
                
            }
        }
    }

    // Find all object tags that are supposed to contain SVGs
    jQuery('object[type="image/svg+xml"]').each(function() {
        var $this = jQuery(this);

        // // Check if it's already loaded, or wait for the load event
        // if ($this[0].contentDocument) {
        //     handleObject($this);
        // } else {
            $this.on('load', function() {
                handleObject($this);
            });
        // }
    });
});