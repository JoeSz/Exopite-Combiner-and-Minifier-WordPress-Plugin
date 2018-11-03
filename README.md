# Exopite Combiner and Minifier
## WordPress Plugin

Author link: http://www.joeszalai.org <br />
Tags: combine, minify, enqueued CSS, enqueued JavaScript, SEO, search engline optimization <br />
Requires at least: 4.8.0 <br />
Tested up to: 4.9.8 <br />
Stable tag: 4.9.8 <br />
License: GPLv3 or later <br />
License URI: http://www.gnu.org/licenses/gpl-3.0.html <br />
Version: 20181103

# I stop working on this Plugin for indefinite period. I have no time for it.

Also it has a problem, the combined JavaScript file stop working if any JavaScript resources has errors. And they has.
Then the combined file will stop working in the point of error.

I see no work arround of this. I added try-catch blocks for the files but then some required code are out of scope (like for WooCommerce).
To check all JavaScript file before combine them would be too time consuming.

## Suggestions and feedback are welcome

---

Combine and minify enqueued CSS and JavaScript files for SEO (site speed).

I wrote this plugin, because I tried several plugins promised to minify and combine my resources.
Unfortunately non of them did that without JavaScript and/or CSS errors.
This plugin still in early phase, I will do more tests and probably make a few corrections as well.

DESCRIPTION
-----------

<b>External CSS and JavaScript files will be ignored.</b> <br />
I think, plugin and theme developers use CDN (or other external soruce) for a reason.

<b>jQuery and jQuery migrate will be also ignored.</b> <br />
Some plugin and theme developers sometimes enqueue they JavaScript and CSS files in the footer
and in this case, those scripts are enqueued very late, they can not be catched earlier. If jQuery
has processed, could cause depency issues.

<b>Process JavaScript and CSS file automatically</b> if no exist or one of the resource file is modified
based on the last modified time.

Convert relatvie url('../..') src-s to absolute in css files.

<b>The plugin has two methods:</b>

* First method work with the enqueued list from WordPress, it can be also done, to process the source code
after output buffering, but sometimes plugin and theme developers use a conditional to enqueue resources
only on some pages. In this case, the plugin will be run every time on the different page,
and that would be more time, what we otherwise gain,

* Second method process the HTML source after WordPress render them and before sent to browser. It will create a separate Css/JS file for each page, make sure, all "in the footer" enqueued scripts are correctly processed. This method uses PHP Simple HTML DOM Parser and Output Buffering.

Uses:
Matthias Mullie Minify from https://www.minifier.org.


NOTE
----

Methode 1:
    The combined JavaScript file will be enqueued in the footer. This could cause depency issues, if some
    very late enqueued JavaScript file has an earlier JavaScript depency. I think, this is very rear, you could
    remove the file via <code>exopite-combiner-minifier-skip-wp_scripts</code> filter. (You could use as <code>array( 'jquery', ... )</code>)

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

No additional settings are required but you can change them in plguin options.

INSTALLATION
------------

1. [x] Upload `exopite-combiner-minifier` to the `/wp-content/plugins/exopite-combiner-minifier/` directory

OR

1. [ ] ~~Install plugin from WordPress repository (not yet)~~

2. [x] Activate the plugin through the 'Plugins' menu in WordPress

CHANGELOG
---------
= 20181103 - 2018-11-03 =
* Update: Exopite Simple Options Framework
* Add: option to turn "try catch" block for JavaScript on or off

= 20180817 - 2018-08-17 =
* Change: update url to new website

= 20180624 - 2018-06-24 =
This is a realative big update.
* Added: try catch for JavaScript to prevent broken script(s) break execution
* Added: inlcude style added by wp_add_inline_style
* Replaced: New minificator class from minifier.org. Better minify, less errors, faster and smaller file size
* Fixed: url() replacement in css typo

= 20180509 - 2018-05-09 =
* Added: Option to combine only
* Improvement: Add semicolon to JavaScript file end if not exist

= 20180113 - 2018-01-13 =
* Added: if enqueued file list changed, regenerate
* Fixed: hooks for method-2

= 20180107 - 2018-01-07 =
* Added: new options menu.
* Added: remove cached files.
* Added: method 2 to process the HTML source after WordPress render them and before sent to browser to prevent dependency issues if/for scripts enqueued in footer.
* Changed: replace JShrink with JSMinPlus

= 20171224 - 2017-12-24 =
* Fix scripts data collection.
* Do not run in admin area.

= 20171223 - 2017-12-23 =
* Initial release.

LICENSE DETAILS
---------------

The GPL license of Exopite Multifilter grants you the right to use, study, share (copy), modify and (re)distribute the software, as long as these license terms are retained.

DISCLAMER
---------

NO WARRANTY OF ANY KIND! USE THIS SOFTWARES AND INFORMATIONS AT YOUR OWN RISK! READ DISCLAMER.TXT! <br />
License: GNU General Public License v3
