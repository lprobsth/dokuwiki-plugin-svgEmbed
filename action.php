<?php
/**
 * Index all SVG files that contain links on the task runner event.
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Lukas Probsthain <lukas.probsthain@gmail.com>
 */
// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();


use dokuwiki\File\PageResolver;
use dokuwiki\File\MediaResolver;
use dokuwiki\Cache\Cache; 

/**
 * Class svgembed_plugin_action
 */
class action_plugin_svgembed extends DokuWiki_Action_Plugin {

    /**
     * Register event handlers.
     *
     * @param Doku_Event_Handler $controller The plugin controller
     */
    public function register(Doku_Event_Handler $controller) {
        $controller->register_hook('FETCH_MEDIA_STATUS', 'BEFORE', $this, 'index_svg_file');
        $controller->register_hook('MEDIA_SENDFILE', 'BEFORE', $this, 'render_svg_file');
    }


    public function index_svg_file(Doku_Event &$event, $param) {
        global $conf;

        $name = $event->data['media'];

        $isSVG = preg_match('/\.svg$/i', trim($name));

        if(!$isSVG) {
            return;
        }

        // TODO: implement method for checking if we should update the index
        // otherwise we load the SVG each time!

        $svg_file = sprintf('%s/%s', $conf['mediadir'], str_replace(':', '/', $name));

        $wiki_links = [];

        // read the SVG file and extract all the links
        if (file_exists($svg_file) && ($svg_fp = fopen($svg_file, 'r'))) {
            $dom = new DOMDocument();
            $dom->load($svg_file);

            $links = $dom->getElementsByTagName('a');
            foreach ($links as $link) {
                $href = $link->getAttribute('href');
                // Process the link
                if (preg_match('/\[\[(.*?)(?:#(.*?))?\]\]/', $href, $matches)) {
                    // Process the link
                    $wiki_links[] = [$matches[1],$matches[2]];
                }
            }
        } else {
            // file does not exist!
            return;
        }

        if(count($wiki_links)<=0) {
            return;
        }

        // read meta data and check if references should be updated
        $key = ['relation' => ['references' => []]];

        $media_resolver = new MediaResolver($name);
        $page_resolver = new PageResolver($name);

        foreach($wiki_links as $link) {
            $full_link = $page_resolver->resolveId($link[0]);
            // $full_link = resolve_id(getNS($name),$link[0]);
            $key['relation']['references'][$full_link] = True;
        }

        p_set_metadata($name, $key);

        idx_get_indexer()->addMetaKeys($name, 'relation_references', array_keys($key['relation']['references']));

        return;

        // update metadata
    }

    public function render_svg_file(Doku_Event &$event, $param) {
        // check if this is a SVG file
        global $conf;

        $name = $event->data['media'];

        $isSVG = preg_match('/\.svg$/i', trim($name));

        if(!$isSVG) {
            return;
        }

        // check meta data if we have references
        if(($references = p_get_metadata($name, 'relation references', METADATA_DONT_RENDER)) == null) {
            // we have no references for this SVG!
            // no need to render!
            return True;
        }

        $svgFilePath = $event->data['file'];

        if(!file_exists($svgFilePath)) {
            return;
        }

        $cacheID = md5($svgFilePath); // Example: Using MD5 hash of file path as cache ID

        // check if we have a cache file
        $cache = new Cache($cacheID, '.svg.cache');

        // check if the date of the SVG is newer than our cache file
        // might happen because: new SVG, modified SVG (update links)
        // the cache class does this automatically based on the timestamps of the file dependencies
        if($cache->useCache(['files' => [$svgFilePath]])) {
            // cache is valid
            $event->data['file'] = $cache->cache;
        } else {
            // we should render the file

            // read SVG
            if (file_exists($svgFilePath) && ($svg_fp = fopen($svgFilePath, 'r'))) {
                $dom = new DOMDocument();
                $dom->load($svgFilePath);

                $links = $dom->getElementsByTagName('a');

                $page_resolver = new PageResolver($name);
                $media_resolver = new MediaResolver($name);
                foreach ($links as $link) {
                    $href = $link->getAttribute('href');
                    // Process the link
                    if (preg_match('/\[\[(.*?)(?:#(.*?))?\]\]/', $href, $matches)) {
                        // Process the link
                        $id = $media_resolver->resolveId($matches[1]);
                        if(media_exists($id)) {
                            $resolved_href = ml($id);
                            $link->setAttribute('href',$resolved_href);
                            $link->setAttribute('class','wikilink1');
                        } else {
                            $id = $page_resolver->resolveId($matches[1]);
                            $resolved_href = wl($id);
                            $link->setAttribute('href',$resolved_href);

                            if(page_exists($id)) {
                                $link->setAttribute('class','wikilink1');
                            }else {
                                $link->setAttribute('class','wikilink2');
                            }
                        }

                        $link->setAttribute('target',$conf['target']['interwiki']);
                    }
                }

                $dom->save($cache->cache);

                $event->data['file'] = $cache->cache;
            } else {
                // file does not exist!
                return;
            }

            // loop over link elements

            // resolve relative links

            // save chache file and redirect event
        }
    }

    public function update_svg_links_on_move(Doku_Event &$event, $param) {
        // read SVG

        // loop over link elements

        // resolve links after move

        // update link

        // save SVG
    }
}