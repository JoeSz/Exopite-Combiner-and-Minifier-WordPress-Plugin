# Exopite Combiner and Minifier
## WordPress Plugin

Author link: http://www.joeszalai.org <br />
Tags: combine, minify, enqueued CSS, enqueued JavaScript, SEO, search engline optimization <br />
Requires at least: 4.8 <br />
Tested up to: 5.4.1 <br />
Stable tag: 4.8 <br />
License: GPLv3 or later <br />
License URI: http://www.gnu.org/licenses/gpl-3.0.html <br />
Version: 20200531

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

* Second method process the HTML source after WordPress render them and before sent to browser. It will create a separate Css/JS file for each page, make sure, all "in the footer" enqueued scripts are correctly processed. This method uses PHP DomDocument DOM Parser and Output Buffering.

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
exopite-combiner-minifier-scripts-ignore-external
exopite-combiner-minifier-to-skip<br />
exopite-combiner-minifier-process-styles<br />
exopite-combiner-minifier-process-scripts<br />
exopite-combiner-minifier-process-html

USAGE
-----

Install and activate.

No additional settings are required but you can change them in plguin options.

INSTALLATION
------------

1. [x] Upload `exopite-combiner-minifier` to the `/wp-content/plugins/exopite-combiner-minifier/` directory

2. [x] Activate the plugin through the 'Plugins' menu in WordPress

CHANGELOG
---------

= 20200531 = - 2020-05-31
* Added: can create a separate file for css and js method separatly
* Fix: sometimes saved list was empty, because try to save in the wrong place

=20200428 - 2020-04-28
* Added: Filters to manage skipped pages, scripts and styles.
* Fixed: Display debug infos.
* Move minify.org files to folder and remove them from include (for the time being)

=20200427 - 2020-04-24
* Fixed: Ignore external scripts and styles

= 20200415 - 2020-04-15 =
* Added: Filter to override process sripts or styles, useful if you want to skip certain pages.
* Added: Filter to override processed contents.
* Added: Option to skip styles.

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
* Update: Update Exopite Simple Options Framework

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

SUPPORT/UPDATES/CONTRIBUTIONS
-----------------------------

If you use my program(s), I would **greatly appreciate it if you kindly give me some suggestions/feedback**. If you solve some issue or fix some bugs or add a new feature, please share with me or mke a pull request. (But I don't have to agree with you or necessarily follow your advice.)<br/>
**Before open an issue** please read the readme (if any :) ), use google and your brain to try to solve the issue by yourself. After all, Github is for developers.<br/>
My **updates will be irregular**, because if the current stage of the program fulfills all of my needs or I do not encounter any bugs, then I have nothing to do.<br/>
**I provide no support.** I wrote these programs for myself. For fun. For free. In my free time. It does not have to work for everyone. However, that does not mean that I do not want to help.<br/>
I've always tested my codes very hard, but it's impossible to test all possible scenarios. Most of the problem could be solved by a simple google search in a matter of minutes. I do the same thing if I download and use a plugin and I run into some errors/bugs.

DISCLAMER
---------

NO WARRANTY OF ANY KIND! USE THIS SOFTWARES AND INFORMATIONS AT YOUR OWN RISK! READ DISCLAMER.TXT! <br />
License: GNU General Public License v3

DISCLAMER
---------

NO WARRANTY OF ANY KIND! USE THIS SOFTWARES AND INFORMATIONS AT YOUR OWN RISK!
[READ DISCLAMER!](https://joe.szalai.org/disclaimer/)
License: GNU General Public License v3

[![forthebadge](http://forthebadge.com/images/badges/built-by-developers.svg)](http://forthebadge.com) [![forthebadge](http://forthebadge.com/images/badges/for-you.svg)](http://forthebadge.com)
