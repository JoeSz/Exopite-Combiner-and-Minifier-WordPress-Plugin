# Exopite Combiner and Minifier
## WordPress Plugin

Author link: http://joe.szalai.org <br />
Tags: combine, minify, enqueued CSS, enqueued JavaScript, SEO, search engline optimization <br />
Requires at least: 4.8.0 <br />
Tested up to: 4.9.1 <br />
Stable tag: 4.8.0 <br />
License: GPLv3 or later <br />
License URI: http://www.gnu.org/licenses/gpl-3.0.html <br />

Combine and minify enqueued CSS and JavaScript files.

DESCRIPTION
-----------

External CSS and JavaScript files will be ignored.
I think, plugin and theme developers use CDN (or other external soruce) for a reason.

jQuery and jQuery migrate will be also ignored.
Some plugin and theme developers sometimes enqueue they JavaScript and CSS files in the footer
and in this case, those scripts are enqueued very late, that can be catched it earlier.

The plugin work with the enqueued list from WordPress, it can be also done, to process the source code
after output buffering, but sometimes plugin and theme developers use a conditional to enqueue resources
only on some pages. In this case, the plugin will be run every time on the different page,
and that would be more time, what we otherwise gain.

Uses:
CSS: CssMin http://code.google.com/p/cssmin/ <br />
JavaScript: JShrink https://github.com/tedious/JShrink

ACTION HOOKS
------------

exopite-combiner-minifier-styles-before-process <br />
exopite-combiner-minifier-styles-after-process <br />
exopite-combiner-minifier-scripts-before-process <br />
exopite-combiner-minifier-scripts-after-process

FILTER HOOKS
------------

exopite-combiner-minifier-process-styles <br />
exopite-combiner-minifier-process-scripts <br />
exopite-combiner-minifier-skip-wp_scripts <br />
exopite-combiner-minifier-skip-wp_styles <br />
exopite-combiner-minifier-wp_scripts-process-wp_includes <br />
exopite-combiner-minifier-wp_styles-process-wp_includes <br />
exopite-combiner-minifier-wp_scripts-ignore-external <br />
exopite-combiner-minifier-wp_styles-ignore-external <br />
exopite-combiner-minifier-enqueued-scripts-list <br />
exopite-combiner-minifier-enqueued-styles-list <br />
exopite-combiner-minifier-enqueued-scripts-contents <br />
exopite-combiner-minifier-enqueued-styles-contents <br />
exopite-combiner-minifier-scripts-file-path <br />
exopite-combiner-minifier-styles-file-path <br />
exopite-combiner-minifier-force-generate-scripts <br />
exopite-combiner-minifier-force-generate-styles <br />
exopite-combiner-minifier-scripts-last-modified <br />
exopite-combiner-minifier-styles-last-modified <br />
exopite-combiner-minifier-styles-file-url <br />
exopite-combiner-minifier-scripts-file-url

USAGE
-----

Install and activate.

No additional settings are required.

INSTALLATION
------------

1. [x] Upload `exopite-combiner-minifier` to the `/wp-content/plugins/exopite-combiner-minifier/` directory

OR

1. [ ] ~~Install plugin from WordPress repository (not yet)~~

2. [x] Activate the plugin through the 'Plugins' menu in WordPress

CHANGELOG
---------

= 20171223 - 2017-04-25 =
* Initial release.

LICENSE DETAILS
---------------

The GPL license of Exopite Multifilter grants you the right to use, study, share (copy), modify and (re)distribute the software, as long as these license terms are retained.

DISCLAMER
---------

NO WARRANTY OF ANY KIND! USE THIS SOFTWARES AND INFORMATIONS AT YOUR OWN RISK! READ DISCLAMER.TXT! <br />
License: GNU General Public License v3
