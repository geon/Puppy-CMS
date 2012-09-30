Puppy CMS
=========

An extremely simplistic CMS written in PHP, without any framework or database. Kind of neat.

(Oh, and it's MIT licensed. Do what you want with it.)

What?
-----

A simplistic CMS, suitable for a PHP programmer who needs to get small websites up quickly and easily, and have them editable over the web. Just log in and click the edit-link on any page. A pop-up page will let you edit the content HTML/PHP, enter the metadata and pick a template.

It is very small and you can easily keep the full source in your head. Perfect for hacking on.

Puppy is simplistic, but it still has some neat features:

* Redirection of old, changed URL:s. Just add a file with the old URL as the filename, and the new one as the content, to the folder `puppy/redirects/`
* Per page templates lets you Easily handle any special cases in your design. 
* Template variations by picking one of several CSS-files provided by the template.
* The content of a page can be plaintext, HTML or PHP.
* SEO friendly URL:s.
* Only a handful of files, tucked away in a separate directory. Hacking friendly!
* Simple editing interface.
* Fully editable 404-page.

Why?
----

Yesterday (2012-09-29), I was cleaning up some old code folders, and stumbled over a project I hadn't touched since 2008 or so. It represents the culmination of my homegrown PHP nano-framework I used in some variations for a number of clients before I switched to using more standardized frameworks.

This particular incarnation of the code was never used for anything, which is a shame, since it was the most polished version, and actually pretty good for the intended type of website. So, I decided to finish it and package it up. There you go!

How?
----

The root folder contains a .htaccess file rewriting the URL. Anything with alphanumerics or dashes only is rewritten to `puppy/page.php?ID=whatever-you-entered`. The file page.php checks for a matching document in the `pages/` folder, and serves if using the template and metadata specified in it's matching (JSON formatted) metadata file. If no document is found, the `redirects` folder is checked instead. If it has a matching file, the browser is redirected to the URL specified by it's content. As a last resort, a file named `404` is used. If nothing is entered after the root folder, the file `INDEX`is served.

Templates are just a folder with any name you like, containing a `template.php` file and whatever CSS and images it needs. The template should call the functions `renderHead()` (meta data), `renderControls()` (editing links) and `renderContent()` (the actual page content).

You can log in by visiting `rootFolder/puppy/` and enter the password in the field. All pages should then be editable via a link at the top of the page.

Pretty simple.