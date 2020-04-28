=== Plugin Name ===
Contributors: (this should be a list of wordpress.org userid's)
Donate link: https://joe.szalai.org
Tags: comments, spam
Requires at least: 4.8
Tested up to: 5.4
Stable tag: 4.8
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Version: 20200428

Combine and minify enqueued CSS and JavaScript files.

IMPORTANT
---------

I stop working on this Plugin for indefinite period. I have no time for it.

Also it has a problem, the combined JavaScript file stop working if any JavaScript resources has errors. And they has. Then the combined file will stop working in the point of error.

I see no work arround of this. I added try-catch blocks for the files but then some required code are out of scope (like for WooCommerce). To check all JavaScript file before combine them would be too time consuming.
Suggestions and feedback are welcome

DESCRIPTION
-----------

I wrote this plugin, because I tried several plugins promised to minify and combine my resources.
Unfortunately non of them did that without JavaScript and/or CSS errors.
This plugin still in early phase, I will do more tests and probably make a few corrections as well.


<b>External CSS and JavaScript files will be ignored.</b> <br />
I think, plugin and theme developers use CDN (or other external soruce) for a reason.

<b>jQuery and jQuery migrate will be also ignored.</b> <br />
Some plugin and theme developers sometimes enqueue they JavaScript and CSS files in the footer
and in this case, those scripts are enqueued very late, they can not be catched earlier. If jQuery
has processed, could cause depency issues.

<b>Process JavaScript and CSS file automatically</b> if no exist or one of the resource file is modified
based on the last modified time.

Convert relatvie url() src-s to absolute in css files.

The plugin work with the enqueued list from WordPress, it can be also done, to process the source code
after output buffering, but sometimes plugin and theme developers use a conditional to enqueue resources
only on some pages. In this case, the plugin will be run every time on the different page,
and that would be more time, what we otherwise gain.

Uses:
CSS: CssMin http://code.google.com/p/cssmin/ <br />
JavaScript: JShrink https://github.com/tedious/JShrink

NOTE
----
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
exopite-combiner-minifier-process-inline-scripts<br />
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
exopite-combiner-minifier-scripts-file-url<br />
exopite_combiner_minifier_styles_before_write_to_file<br />
exopite_combiner_minifier_scripts_before_write_to_file<br />
exopite-combiner-minifier-styles-process-wp_includes<br />
exopite-combiner-minifier-styles-ignore-external<br />
exopite-combiner-minifier-scripts-process-wp_includes<br />
exopite-combiner-minifier-scripts-ignore-external<br />
exopite-combiner-minifier-to-skip<br />
exopite-combiner-minifier-process-styles<br />
exopite-combiner-minifier-process-scripts<br />
exopite-combiner-minifier-process-html

USAGE
-----

Install and activate.

No additional settings are required.

INSTALLATION
------------

1. [x] Upload `exopite-combiner-minifier` to the `/wp-content/plugins/exopite-combiner-minifier/` directory

2. [x] Activate the plugin through the 'Plugins' menu in WordPress

CHANGELOG
---------
=20200428 - 2020-04-28
* Added: Filters to manage skipped pages, scripts and styles.
* Fixed: Display debug infos.
* Move minify.org files to folder and remove them from include (for the time being)

=20200427 - 2020-04-24
* Fixed: Ignore external scripts and styles

= 20200415 - 2020-04-15 =
*  Added: Filter to override process sripts or styles, useful if you want to skip certain pages.
*  Added: Filter to override processed contents.
*  Added: Option to skip styles.

= 20200303 - 2020-03-03 =
* Fix: process local urls start with "//".

= 20200117 - 2020-01-17 =
* Fix: DomDocument sometimes remove some elements from <script type="text/template">
       Remove all template content and add back after processing JSs and CSSs.

= 20191113 - 2019-11-13 =
* Update: Plugin Update Checker to 4.8

= 20190528 - 2019-05-28 =
* Update: Update Exopite Simple Options Framework

= 20190521 - 2019-05-21 =
* Update: Update Exopite Simple Options Framework

= 20190213 - 2019-02-13 =
Major rewrite.
* Replace PHP Simple HTML DOM Parser with DomDocument for performace gain.
* Added: new HTML, CSS and JavaScript minifier.
* Added: include first level @import to css.
* Added: option to insert CSS to header insed of using a file. (slower)
* Added: removing source map URLs in js files to avoid breaking.

= 20181123 - 2018-11-23 =
* Update: Exopite Simple Options Framework

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

SUPPORT/UPDATES
---------------

If you use my program(s), I would **greatly appreciate it if you kindly give me some suggestions/feedback**. If you solve some issue or fix some bugs or add a new feature, please share with me or mke a pull request. (But I don't have to agree with you or necessarily follow your advice.)<br/>
**Before open an issue** please read the readme (if any :) ), use google and your brain to try to solve the issue by yourself. After all, Github is for developers.<br/>
My **updates will be irregular**, because if the current stage of the program fulfills all of my needs or I do not encounter any bugs, then I have nothing to do.<br/>
**I provide no support.** I wrote these programs for myself. For fun. For free. In my free time. It does not have to work for everyone. However, that does not mean that I do not want to help.<br/>
I've always tested my codes very hard, but it's impossible to test all possible scenarios. Most of the problem could be solved by a simple google search in a matter of minutes. I do the same thing if I download and use a plugin and I run into some errors/bugs.

DISCLAIMER
----------

All softwares and informations are provided "as is", without warranty of any kind, express or implied, including but not limited to the warranties of merchant-ability, fitness for a particular purpose and non-infringement.

Please read: https://www.joeszalai.org/disclaimer/
