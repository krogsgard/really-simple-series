# Really Simple Series

*Contributors*: krogsgard, mordauk  
*Donate Link*: http://krogsgard.com/donate/  
*Tags*: series, really simple series, post series, simple post series  
*Tested up to*: 3.4.1  
*Requires at least*: 3.4  
*Stable Tag*: 0.2  
*License*: GPLv2 or later

Really Simple Series allows you to turn normal blog categories into their own post series.

## Description

Other series plugins create a custom taxonomy where each series is a term. Most people probably don't need a new taxonomy. Really Simple Series lets you turn any category into its own series with the click of a checkbox.

To turn on a series, you simple go to the *edit category* screen and check the box to enable Really Simple Series. Once you do, the plugin will automatically reverse the order of all posts in that category so that the oldest show up first. It will also automatically insert a list of all posts in the series at the bottom of each post in that category / series.

## Screenshots

![Screenshot of front-end of a website showing list of two links to other posts in the series](assets/screenshot-1.png)  
_Default display of a series at the bottom of a post._

---

![Screenshot of the category edit page with the extra Really Simple Series toggle option](assets/screenshot-2.png)  
_Edit category screen to enable a category for a series._

## Installation

### Upload

1. Download the latest tagged archive (choose the "zip" option).
* Go to the __Plugins__ → __Add New__ screen and click the __Upload__ tab.
* Upload the zipped archive directly.
* Go to the Plugins screen and click __Activate__.

### Manual

1. Download the latest tagged archive (choose the "zip" option).
* Unzip the archive.
* Copy the folder to your `/wp-content/plugins/` directory.
* Go to the Plugins screen and click __Activate__.

Check out the Codex for more information about [installing plugins manually](http://codex.wordpress.org/Managing_Plugins#Manual_Plugin_Installation).

### Git

In a terminal, browse to your `/wp-content/plugins/` directory and clone this repository:

~~~sh
git clone git@github.com:krogsgard/really-simple-series.git
~~~

Then go to your Plugins screen and click __Activate__.

## Updates

This plugin supports the [GitHub Updater](https://github.com/afragen/github-updater) plugin, so if you install that, this plugin becomes automatically updateable direct from GitHub.

## Options

There is no configuration of this plugin. Just activate, enable a category for Really Simple Series, and enjoy.

You just have to click within the edit *category* screen to enable a series. There are no other options. There is a shortcode available called 
`[rsseries cat="148"]`, where "`148`" is a specific category ID, that can be used to display a series of your choice. Do not use the `[rsseries]`` shortcode without a category parameter, or it may show all of your blog posts.

## Frequently Asked Questions

### Why does this require WordPress 3.4?
There are no known conflicts with older versions of WordPress, but really you should never be behind on major releases.

## Feedback

* You can leave feedback in the WordPress.org forums.
* You can leave feedback on the [plugin homepage](http://krogsgard.com/really-simple-series/)
* You can send me a message on Twitter [@krogsgard](http://twitter.com/krogsgard).

## Changelog

See the [change log](CHANGELOG.md). This project follows [semantic versioning](http://semver.org).