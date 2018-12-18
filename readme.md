# How to use it
[![License](https://img.shields.io/badge/License-GPL%20v2-blue.svg)](https://img.shields.io/badge/License-GPL%20v2-blue.svg)   

## Output structure
`Kind of content | post id | post type/taxonomy`

## Commands
To get the id and post type by url:

`wp get-by-url --skip-plugins --skip-themes http://demo.test/2018/10/your-post`

Output:

`post | 33 | post`

To get the id and taxonomy by url:

`wp get-by-url --skip-plugins --skip-themes http://demo.test/tag/test/`

Output:

`term | 2 | post_tag`

### Why skip plugins and themes

It is a good practice in WP CLI when you have to execute commands that are heavy of resources to not load plugins and themes for a lot of reasons.

### Why the separator is pipe?

In that way is more easy to do script on it in bash or other languages